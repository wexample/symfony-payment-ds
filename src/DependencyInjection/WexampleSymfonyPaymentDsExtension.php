<?php

namespace Wexample\SymfonyPaymentDs\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;

class WexampleSymfonyPaymentDsExtension extends AbstractWexampleSymfonyExtension
{
    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('wexample_symfony_payment_ds.methods', $config['methods']);
        $container->setParameter('wexample_symfony_payment_ds.bank_transfer', $config['bank_transfer']);

        $this->loadConfig(
            __DIR__,
            $container
        );
    }
}
