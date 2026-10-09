<?php

namespace Wexample\SymfonyPaymentDs\Controller\Pages;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Throwable;
use Wexample\SymfonyHelpers\Controller\AbstractController;
use Wexample\SymfonyHelpers\Helper\VariableHelper;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyPayment\Class\ManualPaymentGateway;
use Wexample\SymfonyPayment\Entity\Payment;
use Wexample\SymfonyPayment\Repository\PaymentRepository;
use Wexample\SymfonyPayment\Service\PayableRegistry;
use Wexample\SymfonyPayment\Service\PaymentService;
use Wexample\SymfonyPaymentDs\Service\FormProcessor\PaymentRefundFormProcessor;
use Wexample\SymfonyPaymentDs\Traits\SymfonyPaymentDsBundleClassTrait;

/**
 * Every payment, for whoever holds the administration role of
 * symfony-payment: the list, narrowed and sorted, and each payment with what
 * can be done to it — refunded, in whole or in part; confirmed, when paid by
 * hand; canceled while open; read again from its provider.
 *
 * No role set: the pages do not exist (404). Set and not held: refused (403).
 * The actions post with the token of the page and come back to it saying
 * what was done, or why not.
 */
#[Route(path: '/admin/payments/', name: 'payment_admin_')]
final class PaymentAdminController extends AbstractPagesController
{
    use SymfonyPaymentDsBundleClassTrait;

    final public const string ROUTE_INDEX = VariableHelper::INDEX;

    final public const string ROUTE_SHOW = 'show';

    final public const string ROUTE_SHOW_NAME = 'payment_admin_show';

    final public const string ROUTE_ACTION = 'action';

    final public const string PARAMETER_DONE = 'done';

    final public const string PARAMETER_REFUSED = 'refused';

    final public const string CSRF_TOKEN_ID = 'payment-admin-action';

    final public const string ACTION_SYNCHRONIZE = 'synchronize';

    final public const string ACTION_CONFIRM = 'confirm';

    final public const string ACTION_CANCEL = 'cancel';

    #[Route(path: '', name: self::ROUTE_INDEX)]
    public function index(
        #[Autowire(param: 'wexample_symfony_payment.administration.role')]
        ?string $administrationRole,
    ): Response {
        $this->assertMayAdminister($administrationRole);

        return $this->renderPage(self::ROUTE_INDEX);
    }

    #[Route(path: '{id}', name: self::ROUTE_SHOW, requirements: ['id' => Requirement::UUID], options: AbstractController::ROUTE_OPTIONS_ONLY_EXPOSE)]
    public function show(
        string $id,
        Request $request,
        PaymentRefundFormProcessor $refundProcessor,
        PayableRegistry $payables,
        PaymentRepository $repository,
        #[Autowire(param: 'wexample_symfony_payment.administration.role')]
        ?string $administrationRole,
    ): Response {
        $this->assertMayAdminister($administrationRole);
        $payment = $this->find($id, $repository);
        $refundable = $payment->getStatus()->isPaid() && $payment->getAmountRefunded() < $payment->getAmount();

        if ($refundable) {
            $refundProcessor->forPayment($payment);

            if ($response = $refundProcessor->handleSubmissionResponse($request)) {
                return $response;
            }
        }

        return $this->renderPage(self::ROUTE_SHOW, [
            'payment' => $payment,
            'payable' => $payables->resolve($payment),
            'actions' => $this->getActions($payment),
            'csrf_token_id' => self::CSRF_TOKEN_ID,
            'done' => $request->query->get(self::PARAMETER_DONE),
            'refused' => $request->query->get(self::PARAMETER_REFUSED),
            'payment_refund_form' => $refundable ? $refundProcessor->createForm()->createView() : null,
        ]);
    }

    #[Route(path: '{id}/{action}', name: self::ROUTE_ACTION, requirements: ['id' => Requirement::UUID], methods: [Request::METHOD_POST])]
    public function action(
        string $id,
        string $action,
        Request $request,
        PaymentService $payments,
        PaymentRepository $repository,
        #[Autowire(param: 'wexample_symfony_payment.administration.role')]
        ?string $administrationRole,
    ): RedirectResponse {
        $this->assertMayAdminister($administrationRole);
        $payment = $this->find($id, $repository);

        if (! in_array($action, $this->getActions($payment), true)
            || ! $this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $request->request->getString('_token'))) {
            return $this->back($payment, self::PARAMETER_REFUSED, $action);
        }

        try {
            match ($action) {
                self::ACTION_SYNCHRONIZE => $payments->synchronize($payment),
                self::ACTION_CONFIRM => $payments->confirmManualPayment($payment),
                self::ACTION_CANCEL => $payments->cancel($payment),
            };
        } catch (Throwable) {
            return $this->back($payment, self::PARAMETER_REFUSED, $action);
        }

        return $this->back($payment, self::PARAMETER_DONE, $action);
    }

    /**
     * What may be done to a payment where it stands.
     *
     * @return list<string>
     */
    private function getActions(Payment $payment): array
    {
        $manual = ManualPaymentGateway::NAME === $payment->getProvider();
        $open = $payment->getStatus()->isOpen();

        return array_values(array_filter([
            ! $manual && $payment->getProviderReference() ? self::ACTION_SYNCHRONIZE : null,
            $manual && $open ? self::ACTION_CONFIRM : null,
            $open ? self::ACTION_CANCEL : null,
        ]));
    }

    private function back(Payment $payment, string $parameter, string $action): RedirectResponse
    {
        return $this->redirectToRoute(self::ROUTE_SHOW_NAME, ['id' => (string) $payment->getId(), $parameter => $action]);
    }

    private function find(string $id, PaymentRepository $repository): Payment
    {
        return $repository->find($id) ?? throw $this->createNotFoundException();
    }

    private function assertMayAdminister(?string $administrationRole): void
    {
        if (null === $administrationRole) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted($administrationRole);
    }
}
