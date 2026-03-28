<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SyliusUpsellExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));

        // Load resource configurations
        $loader->load('resources/upsell_offer.yaml');

        // Load grid configurations
        $loader->load('grids/admin/upsell_offer.yaml');

        $container->prependExtensionConfig('twig', [
            'paths' => [
                __DIR__ . '/../../templates' => 'SyliusUpsellPlugin',
            ],
        ]);

        $container->prependExtensionConfig('sylius_twig_hooks', [
            'hooks' => [
                // Phase 1: FBT on product page
                'sylius_shop.product.show.content.after' => [
                    'upsell_frequently_bought_together' => [
                        'template' => '@SyliusUpsellPlugin/Shop/frequently_bought_together.html.twig',
                        'priority' => 10,
                    ],
                ],
                // Phase 2: Post-purchase on thank-you page
                'sylius_shop.order.thank_you.content' => [
                    'upsell_post_purchase_offer' => [
                        'template' => '@SyliusUpsellPlugin/Shop/post_purchase_offer.html.twig',
                        'priority' => 50,
                    ],
                ],
            ],
        ]);
    }
}
