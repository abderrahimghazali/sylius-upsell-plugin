<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Form\Type;

use Sylius\Bundle\AdminBundle\Form\Type\ProductAutocompleteType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class UpsellOfferType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'sylius.ui.name',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'sylius.ui.enabled',
                'required' => false,
            ])
            ->add('triggerProduct', ProductAutocompleteType::class, [
                'label' => 'upsell.form.trigger_product',
                'required' => false,
            ])
            ->add('offerProduct', ProductAutocompleteType::class, [
                'label' => 'upsell.form.offer_product',
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('discountPercent', IntegerType::class, [
                'label' => 'upsell.form.discount_percent',
                'constraints' => [new Assert\Range(min: 0, max: 100)],
                'attr' => ['min' => 0, 'max' => 100],
            ])
            ->add('headline', TextType::class, [
                'label' => 'upsell.form.headline',
                'required' => false,
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('body', TextareaType::class, [
                'label' => 'upsell.form.body',
                'required' => false,
            ])
            ->add('ctaLabel', TextType::class, [
                'label' => 'upsell.form.cta_label',
                'required' => false,
                'constraints' => [new Assert\Length(max: 100)],
            ])
            ->add('declineLabel', TextType::class, [
                'label' => 'upsell.form.decline_label',
                'required' => false,
                'constraints' => [new Assert\Length(max: 100)],
            ])
            ->add('priority', IntegerType::class, [
                'label' => 'sylius.ui.priority',
                'constraints' => [new Assert\PositiveOrZero()],
                'attr' => ['min' => 0],
            ])
            ->add('startsAt', DateTimeType::class, [
                'label' => 'upsell.form.starts_at',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('endsAt', DateTimeType::class, [
                'label' => 'upsell.form.ends_at',
                'required' => false,
                'widget' => 'single_text',
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'upsell_offer';
    }
}
