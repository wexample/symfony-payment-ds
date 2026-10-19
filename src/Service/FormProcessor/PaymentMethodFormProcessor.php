<?php

namespace Wexample\SymfonyPaymentDs\Service\FormProcessor;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyPayment\Interface\PayableInterface;
use Wexample\SymfonyPaymentDs\Form\PaymentMethodForm;
use Wexample\SymfonyPaymentDs\Service\PaymentCheckoutService;

/**
 * The method chosen for a payable, and the browser sent where paying by it
 * goes on: the provider's page, or the page waiting for the outcome.
 */
class PaymentMethodFormProcessor extends AbstractFormProcessor
{
    private ?PayableInterface $payable = null;

    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator,
        private readonly PaymentCheckoutService $checkout,
        private readonly Security $security,
    ) {
        parent::__construct($formFactory, $requestStack, $urlGenerator);
    }

    /**
     * What the submission handled pays for, not the next visitor's.
     */
    public function reset(): void
    {
        parent::reset();
        $this->payable = null;
    }

    public function forPayable(PayableInterface $payable): static
    {
        $this->payable = $payable;

        return $this;
    }

    public function createForm(
        $data = null,
        array $options = []
    ): FormInterface {
        return parent::createForm($data, $options + [
            PaymentMethodForm::OPTION_METHODS => $this->checkout->getMethods(),
        ]);
    }

    public function onValid(FormInterface $form)
    {
        $this->redirect($this->checkout->start(
            $this->payable,
            (string) $form->get(PaymentMethodForm::FIELD_METHOD)->getData(),
            $this->security->getUser(),
        ));
    }
}
