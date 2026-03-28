<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductUpsellType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('upsellRelations', CollectionType::class, [
                'entry_type' => UpsellRelationType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'prototype' => true,
                'attr' => [
                    'class' => 'upsell-relations-collection',
                ],
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'product_upsell';
    }
}
