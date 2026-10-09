<?php

namespace Wexample\SymfonyPaymentDs\Helper;

use Wexample\SymfonyPayment\Enum\PaymentStatus;

/**
 * The tone a payment's status is drawn in, a marker's: what is done in the
 * tone of success, what failed in error's, what goes on running, what was
 * given back in info and warning's, the rest neutral. The table of the
 * payments reads the same, `PAYMENT_STATUS_TONES` in its script.
 */
final class PaymentStatusHelper
{
    public const array TONES = [
        'created' => 'neutral',
        'pending' => 'running',
        'processing' => 'running',
        'succeeded' => 'success',
        'failed' => 'error',
        'canceled' => 'disabled',
        'partially_refunded' => 'warning',
        'refunded' => 'info',
    ];

    public static function tone(PaymentStatus|string $status): string
    {
        $value = $status instanceof PaymentStatus ? $status->value : $status;

        return self::TONES[$value] ?? 'neutral';
    }
}
