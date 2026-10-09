<?php

namespace Wexample\SymfonyPaymentDs\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Wexample\SymfonyPayment\Entity\Payment;
use Wexample\SymfonyPayment\Interface\PayableInterface;
use Wexample\SymfonyPayment\Service\PaymentAccessService;
use Wexample\SymfonyPayment\Service\PaymentService;

/**
 * Paying a payable from the interface: its payment, opened — or the open one
 * taken up again —, sent to the provider, and where the browser goes next —
 * the provider's own page, or the return page, which waits for the outcome or
 * says how to pay by hand.
 */
class PaymentCheckoutService
{
    final public const string ROUTE_RETURN = 'payments_return';

    /**
     * @param list<string> $methods
     */
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PaymentAccessService $access,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire(param: 'wexample_symfony_payment_ds.methods')]
        private readonly array $methods,
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
     * Where the browser goes to pay `$payable` by `$method`.
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
        $initiation = $this->payments->initiate($payment, $returnUrl);

        return $initiation?->redirectUrl ?? $returnUrl;
    }

    /**
     * The page waiting for the outcome of a payment, signed so that the
     * browser which started it reads it without being asked who it is.
     */
    public function getReturnUrl(Payment $payment): string
    {
        return $this->urlGenerator->generate(
            self::ROUTE_RETURN,
            ['id' => (string) $payment->getId(), 'token' => $this->access->createToken($payment)],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
