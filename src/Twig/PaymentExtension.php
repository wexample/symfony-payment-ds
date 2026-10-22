<?php

namespace Wexample\SymfonyPaymentDs\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Wexample\SymfonyPaymentDs\Helper\PaymentStatusHelper;

class PaymentExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('payment_status_tone', PaymentStatusHelper::tone(...)),
        ];
    }
}
