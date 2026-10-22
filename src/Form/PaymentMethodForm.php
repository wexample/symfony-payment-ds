<?php

namespace Wexample\SymfonyPaymentDs\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyForms\Form\Type\RadioInputType;

/**
 * How to pay: one of the methods offered, the first one picked. A page load,
 * and not a script's: the provider's page may be where it leads.
 */
class PaymentMethodForm extends AbstractForm
{
    final public const string FIELD_METHOD = 'method';

    final public const string OPTION_METHODS = 'methods';

    public static bool $ajax = false;

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(self::OPTION_METHODS);
        $resolver->setAllowedTypes(self::OPTION_METHODS, 'array');
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $methods = $options[self::OPTION_METHODS];

        $builder->add(self::FIELD_METHOD, RadioInputType::class, [
            self::FIELD_OPTION_NAME_LABEL => true,
            self::FIELD_OPTION_NAME_REQUIRED => true,
            self::FIELD_OPTION_NAME_MAPPED => false,
            'expanded' => true,
            'choices' => $methods,
            'data' => $methods[0] ?? null,
        ]);

        $this->builderAddSubmit($builder, 'action.pay');
    }
}
