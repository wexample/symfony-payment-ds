<?php

namespace Wexample\SymfonyPaymentDs\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Wexample\SymfonyPayment\Class\ManualPaymentGateway;
use Wexample\SymfonyPayment\Entity\Payment;
use Wexample\SymfonyPayment\Interface\PayableInterface;
use Wexample\SymfonyPayment\Service\PaymentAccessService;
use Wexample\SymfonyPayment\Service\PaymentService;
use Wexample\SymfonyRemotePayment\Class\PaymentInitiation;
use Wexample\SymfonyRemotePayment\Service\PaymentProviderRegistry;

/**
 * Paying from the interface, in three moves any flow puts together — a page
 * paying one payable, as a purchase tunnel paying a cart:
 *
 * - the payment opened and sent to its provider: `start()` for a payable
 *   alone; the payable's own package does it otherwise (a cart's checkout);
 * - where the browser goes next (`next()`): the provider's page, or the page
 *   the flow comes back to;
 * - what that page shows (`describeOutcome()`, drawn by the
 *   `partials/payment-outcome` template): the provider's form to confirm,
 *   the wait, how to pay by hand, or the outcome.
 */
class PaymentCheckoutService
{
    final public const string ROUTE_RETURN = 'payments_return';

    /** Back from a provider's form in the browser: the outcome is now awaited. */
    final public const string PARAMETER_CONFIRMED = 'confirmed';

    /**
     * @param list<string> $methods
     * @param array{holder: ?string, iban: ?string, bic: ?string} $bankTransfer
     */
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentAccessService $access,
        private readonly PaymentProviderRegistry $providers,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire(param: 'wexample_symfony_payment_ds.methods')]
        private readonly array $methods,
        #[Autowire(param: 'wexample_symfony_payment_ds.bank_transfer')]
        private readonly array $bankTransfer,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Where the browser goes to pay `$payable` by `$method`, alone: its
     * payment opened, or the open one taken up again, and sent.
     */
    public function start(
        PayableInterface $payable,
        string $method,
        ?UserInterface $user = null,
    ): string {
        $payment = $this->payments->getOrCreateForPayable(
            $payable,
            $method,
            customerEmail: $user && method_exists($user, 'getEmail') ? $user->getEmail() : null,
            ownerReference: $this->access->ownerReferenceOf($user),
        );

        $returnUrl = $this->getReturnUrl($payment);

        return $this->next($this->payments->initiate($payment, $returnUrl), $returnUrl);
    }

    /**
     * Where the browser goes once a payment was sent: the provider's page
     * when it has one, the page the flow comes back to otherwise.
     */
    public function next(
        ?PaymentInitiation $initiation,
        string $returnUrl
    ): string {
        return $initiation?->redirectUrl ?? $returnUrl;
    }

    /**
     * The return page of payment-ds, signed so that the browser which
     * started the payment reads it without being asked who it is.
     */
    public function getReturnUrl(Payment $payment): string
    {
        return $this->urlGenerator->generate(
            self::ROUTE_RETURN,
            ['id' => (string) $payment->getId(), 'token' => $this->access->createToken($payment)],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * What the page a payment comes back to shows of it — `$returnUrl`, that
     * page's own address: the provider's form while its payer has to confirm
     * it there — not once back from it, `$confirmed` —, how to pay by hand,
     * the status to wait on.
     *
     * @return array<string, mixed> The variables of `partials/payment-outcome`.
     */
    public function describeOutcome(
        Payment $payment,
        string $returnUrl,
        bool $confirmed = false,
    ): array {
        $confirming = ! $confirmed && $payment->getProvider() && $payment->getClientSecret() && $payment->getStatus()->isOpen()
            ? $this->providers->findBrowserConfirmation($payment->getProvider())
            : null;

        return [
            'payment' => $payment,
            'confirmation' => $confirming ? [
                'component' => $confirming->getBrowserConfirmationComponent(),
                'props' => $confirming->getBrowserConfirmationOptions() + [
                    'clientSecret' => $payment->getClientSecret(),
                    'returnUrl' => $returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').self::PARAMETER_CONFIRMED.'=1',
                ],
            ] : null,
            'manual' => ManualPaymentGateway::NAME === $payment->getProvider(),
            'bank_transfer' => $this->bankTransfer,
            'status_url' => $this->urlGenerator->generate('payment_status', [
                'id' => (string) $payment->getId(),
                'token' => $this->access->createToken($payment),
            ]),
        ];
    }
}
