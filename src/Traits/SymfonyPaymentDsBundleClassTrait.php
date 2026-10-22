<?php

namespace Wexample\SymfonyPaymentDs\Traits;

use Wexample\SymfonyHelpers\Traits\BundleClassTrait;
use Wexample\SymfonyPaymentDs\WexampleSymfonyPaymentDsBundle;

trait SymfonyPaymentDsBundleClassTrait
{
    use BundleClassTrait;

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyPaymentDsBundle::class;
    }
}
