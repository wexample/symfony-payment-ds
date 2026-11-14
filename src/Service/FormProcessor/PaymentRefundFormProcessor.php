<?php

namespace Wexample\SymfonyPaymentDs\Service\FormProcessor;

use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyMoney\Helper\MoneyHelper;
use Wexample\SymfonyPayment\Entity\Payment;
use Wexample\SymfonyPayment\Service\PaymentService;
use Wexample\SymfonyPaymentDs\Controller\Pages\PaymentAdminController;
use Wexample\SymfonyPaymentDs\Form\PaymentRefundForm;

/**
 * A refund of a payment, from its administration page: through its provider,
 * or recorded only for a payment made by hand. What the provider refuses comes
 * back as an error on the form: a sentence of ours, the provider's own words
 * (which may name its internals) going to the log.
 */
class PaymentRefundFormProcessor extends AbstractFormProcessor
{
    final public const string ERROR_REFUSED = '@form::error.refused';

    private ?Payment $payment = null;

    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        private readonly PaymentService $payments,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($formFactory, $requestStack, $urlGenerator);
    }

    /**
     * The payment of the submission handled, not of the next visitor's.
     */
    public function reset(): void
    {
        parent::reset();
        $this->payment = null;
    }

    public function forPayment(Payment $payment): static
    {
        $this->payment = $payment;

        return $this;
    }

    public function createForm(
        $data = null,
        array $options = []
    ): FormInterface {
        // Given its payment by the payment's page: sent from anywhere else,
        // there is none to refund.
        if (null === $this->payment) {
            throw new NotFoundHttpException('No payment to refund.');
        }

        $decimals = MoneyHelper::getDecimals($this->payment->getCurrencyCode());

        return parent::createForm($data, $options + [
            PaymentRefundForm::OPTION_DECIMALS => $decimals,
            PaymentRefundForm::OPTION_REFUNDABLE => ($this->payment->getAmount() - $this->payment->getAmountRefunded()) / 10 ** $decimals,
        ]);
    }

    public function formIsValid(FormInterface $form): bool
    {
        if (! parent::formIsValid($form)) {
            return false;
        }

        try {
            $this->payments->refund(
                $this->payment,
                // Read in the major unit, kept in the minor one, without a float
                // product rounding a cent away.
                MoneyHelper::fromDecimal($form->get(PaymentRefundForm::FIELD_AMOUNT)->getData() ?? 0, $this->payment->getCurrencyCode()),
                $form->get(PaymentRefundForm::FIELD_REASON)->getData() ?: null,
            );
        } catch (Throwable $exception) {
            $this->logger->error('The refund of payment {payment} failed: {message}', [
                'payment' => (string) $this->payment->getId(),
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);
            $form->addError(new FormError(self::ERROR_REFUSED));

            return false;
        }

        return true;
    }

    public function onValid(FormInterface $form)
    {
        $this->redirectToRoute(PaymentAdminController::ROUTE_SHOW_NAME, [
            'id' => (string) $this->payment->getId(),
            PaymentAdminController::PARAMETER_DONE => 'refund',
        ]);
    }
}
