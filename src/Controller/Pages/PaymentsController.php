<?php

namespace Wexample\SymfonyPaymentDs\Controller\Pages;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyHelpers\Controller\AbstractController;
use Wexample\SymfonyHelpers\Helper\VariableHelper;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyPayment\Entity\Payment;
use Wexample\SymfonyPayment\Repository\PaymentRepository;
use Wexample\SymfonyPayment\Service\PayableRegistry;
use Wexample\SymfonyPayment\Service\PaymentAccessService;
use Wexample\SymfonyPaymentDs\Service\FormProcessor\PaymentMethodFormProcessor;
use Wexample\SymfonyPaymentDs\Service\PaymentCheckoutService;
use Wexample\SymfonyPaymentDs\Traits\SymfonyPaymentDsBundleClassTrait;

/**
 * The payer's side: paying something, waiting for the outcome, and their
 * payments — each one, and its receipt once paid.
 *
 * Paying asks who pays; the return page does not — it is signed for the
 * browser that started the payment, as the status it polls is. A payment
 * belongs to the user its owner reference names: anyone else is told it does
 * not exist.
 */
#[Route(path: '/payments/', name: 'payments_')]
final class PaymentsController extends AbstractPagesController
{
    use SymfonyPaymentDsBundleClassTrait;

    final public const string ROUTE_INDEX = VariableHelper::INDEX;

    final public const string ROUTE_SHOW = 'show';

    final public const string ROUTE_PAY = 'pay';

    final public const string ROUTE_RETURN = 'return';

    final public const string ROUTE_RECEIPT = 'receipt';


    #[Route(path: '', name: self::ROUTE_INDEX)]
    #[IsGranted('IS_AUTHENTICATED')]
    public function index(): Response
    {
        return $this->renderPage(self::ROUTE_INDEX);
    }

    #[Route(path: 'pay/{payableType}/{payableId}', name: self::ROUTE_PAY)]
    #[IsGranted('IS_AUTHENTICATED')]
    public function pay(
        string $payableType,
        string $payableId,
        Request $request,
        PaymentMethodFormProcessor $processor,
        PayableRegistry $payables,
    ): Response {
        $payable = $payables->find($payableType, $payableId) ?? throw $this->createNotFoundException();
        $processor->forPayable($payable);

        if ($response = $processor->handleSubmissionResponse($request)) {
            return $response;
        }

        return $this->renderPage(self::ROUTE_PAY, [
            'payable' => $payable,
            'payment_method_form' => $processor->createForm()->createView(),
        ]);
    }

    #[Route(path: '{id}/return', name: self::ROUTE_RETURN, requirements: ['id' => Requirement::UUID])]
    public function return(
        string $id,
        Request $request,
        PaymentRepository $repository,
        PaymentAccessService $access,
        PayableRegistry $payables,
        PaymentCheckoutService $checkout,
    ): Response {
        $payment = $repository->find($id);

        if (! $payment || ! $access->isTokenValid($payment, $request->query->getString('token'))) {
            throw $this->createNotFoundException();
        }

        $payable = $payables->resolve($payment);

        return $this->renderPage(self::ROUTE_RETURN, $checkout->describeOutcome(
            $payment,
            $checkout->getReturnUrl($payment),
            $request->query->has(PaymentCheckoutService::PARAMETER_CONFIRMED),
        ) + [
            // Paying again is paying the same thing afresh.
            'retry_url' => $payable ? $this->generateUrl('payments_pay', [
                'payableType' => $payable::getPayableType(),
                'payableId' => $payable->getPayableId(),
            ]) : null,
            'owned' => $access->isOwnedBy($payment, $this->getUser()),
        ]);
    }

    #[Route(path: '{id}', name: self::ROUTE_SHOW, requirements: ['id' => Requirement::UUID], options: AbstractController::ROUTE_OPTIONS_ONLY_EXPOSE)]
    #[IsGranted('IS_AUTHENTICATED')]
    public function show(
        string $id,
        PaymentRepository $repository,
        PaymentAccessService $access,
    ): Response {
        return $this->renderPage(self::ROUTE_SHOW, [
            'payment' => $this->findOwned($id, $repository, $access),
        ]);
    }

    #[Route(path: '{id}/receipt', name: self::ROUTE_RECEIPT, requirements: ['id' => Requirement::UUID])]
    #[IsGranted('IS_AUTHENTICATED')]
    public function receipt(
        string $id,
        PaymentRepository $repository,
        PaymentAccessService $access,
    ): Response {
        $payment = $this->findOwned($id, $repository, $access);

        if (! $payment->getStatus()->isPaid()) {
            throw $this->createNotFoundException();
        }

        return $this->renderPage(self::ROUTE_RECEIPT, [
            'payment' => $payment,
        ]);
    }

    private function findOwned(
        string $id,
        PaymentRepository $repository,
        PaymentAccessService $access,
    ): Payment {
        $payment = $repository->find($id);

        if (! $payment || ! $access->isOwnedBy($payment, $this->getUser())) {
            throw $this->createNotFoundException();
        }

        return $payment;
    }
}
