<?php

namespace Wexample\SymfonyPaymentDs\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wexample_symfony_payment_ds');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('methods')
                    ->info('The ways of paying offered, in this order: each a method a provider supports (`card`, `sepa_debit`) or the manual gateway does (`transfer`, `cash`, `cheque`). One alone is not asked for.')
                    ->scalarPrototype()->end()
                    ->defaultValue(['card'])
                ->end()
                ->arrayNode('bank_transfer')
                    ->info('Where a transfer is sent, shown to whoever chose to pay by transfer.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('holder')->defaultNull()->end()
                        ->scalarNode('iban')->defaultNull()->end()
                        ->scalarNode('bic')->defaultNull()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
