<?php

namespace Wexample\SymfonyPaymentDs\Traits;

use Wexample\SymfonyPaymentDs\WexampleSymfonyPaymentDsBundle;
use Wexample\SymfonyHelpers\Traits\BundleClassTrait;

trait SymfonyPaymentDsBundleClassTrait
{
    use BundleClassTrait;

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyPaymentDsBundle::class;
    }
}
