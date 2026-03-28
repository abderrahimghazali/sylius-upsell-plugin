<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Form\Type;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellRelation;
use Sylius\Bundle\ProductBundle\Form\Type\ProductAutocompleteChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class UpsellRelationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('relatedProduct', ProductAutocompleteChoiceType::class, [
                'label' => 'upsell.form.related_product',
            ])
            ->add('position', HiddenType::class, [
                'attr' => ['class' => 'upsell-position'],
            ])
            ->add('discount', IntegerType::class, [
                'label' => 'upsell.form.discount_percentage',
                'required' => false,
                'attr' => ['min' => 0, 'max' => 100, 'placeholder' => '0'],
                'constraints' => [
                    new Assert\Range(min: 0, max: 100),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpsellRelation::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'upsell_relation';
    }
}
