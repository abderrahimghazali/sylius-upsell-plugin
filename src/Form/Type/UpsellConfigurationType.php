<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Form\Type;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellConfiguration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class UpsellConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('enabled', CheckboxType::class, [
                'label' => 'upsell.form.enabled',
                'required' => false,
            ])
            ->add('minCoPurchaseThreshold', IntegerType::class, [
                'label' => 'upsell.form.min_co_purchase_threshold',
                'constraints' => [new Assert\Positive()],
                'attr' => ['min' => 1],
            ])
            ->add('maxProductsShown', IntegerType::class, [
                'label' => 'upsell.form.max_products_shown',
                'constraints' => [new Assert\Range(min: 1, max: 10)],
                'attr' => ['min' => 1, 'max' => 10],
            ])
            ->add('sectionTitle', TextType::class, [
                'label' => 'upsell.form.section_title',
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('showDiscountBadge', CheckboxType::class, [
                'label' => 'upsell.form.show_discount_badge',
                'required' => false,
            ])
            ->add('fallbackStrategy', ChoiceType::class, [
                'label' => 'upsell.form.fallback_strategy',
                'choices' => [
                    'upsell.form.strategy_algorithmic' => UpsellConfiguration::FALLBACK_ALGORITHMIC,
                    'upsell.form.strategy_manual_only' => UpsellConfiguration::FALLBACK_MANUAL_ONLY,
                    'upsell.form.strategy_disabled' => UpsellConfiguration::FALLBACK_DISABLED,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpsellConfiguration::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'upsell_configuration';
    }
}
