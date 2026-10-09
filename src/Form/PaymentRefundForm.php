<?php

namespace Wexample\SymfonyPaymentDs\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyForms\Form\Type\NumberInputType;
use Wexample\SymfonyForms\Form\Type\TextInputType;

/**
 * Giving money back: how much — what is left by default, less for a part —,
 * in the currency's major unit as it is read, and why.
 */
class PaymentRefundForm extends AbstractForm
{
    final public const string FIELD_AMOUNT = 'amount';

    final public const string FIELD_REASON = 'reason';

    /** What may still be refunded, in the major unit. */
    final public const string OPTION_REFUNDABLE = 'refundable';

    final public const string OPTION_DECIMALS = 'decimals';

    public static bool $ajax = false;

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired([self::OPTION_REFUNDABLE, self::OPTION_DECIMALS]);
    }

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add(self::FIELD_AMOUNT, NumberInputType::class, [
                self::FIELD_OPTION_NAME_LABEL => true,
                self::FIELD_OPTION_NAME_REQUIRED => true,
                self::FIELD_OPTION_NAME_MAPPED => false,
                'scale' => $options[self::OPTION_DECIMALS],
                'data' => $options[self::OPTION_REFUNDABLE],
                'constraints' => [
                    new GreaterThan(0),
                    new LessThanOrEqual($options[self::OPTION_REFUNDABLE]),
                ],
            ])
            ->add(self::FIELD_REASON, TextInputType::class, [
                self::FIELD_OPTION_NAME_LABEL => true,
                self::FIELD_OPTION_NAME_REQUIRED => false,
                self::FIELD_OPTION_NAME_MAPPED => false,
            ]);

        $this->builderAddSubmit($builder, 'action.refund');
    }
}
