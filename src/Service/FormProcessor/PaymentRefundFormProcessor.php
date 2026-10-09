<?php

namespace Wexample\SymfonyPaymentDs\Service\FormProcessor;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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
 * back as an error on the form.
 */
class PaymentRefundFormProcessor extends AbstractFormProcessor
{
    private ?Payment $payment = null;

    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        private readonly PaymentService $payments,
    ) {
        parent::__construct($formFactory, $requestStack, $urlGenerator);
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

        $decimals = MoneyHelper::getDecimals($this->payment->getCurrencyCode());

        try {
            $this->payments->refund(
                $this->payment,
                (int) round((float) $form->get(PaymentRefundForm::FIELD_AMOUNT)->getData() * 10 ** $decimals),
                $form->get(PaymentRefundForm::FIELD_REASON)->getData() ?: null,
            );
        } catch (Throwable $exception) {
            $form->addError(new FormError($exception->getMessage()));

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
