<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Twig;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellConfiguration;
use Abderrahim\SyliusUpsellPlugin\Service\FrequentlyBoughtTogetherResolver;
use Abderrahim\SyliusUpsellPlugin\Service\UpsellConfigurationProvider;
use Sylius\Component\Core\Model\ProductInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class UpsellRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly FrequentlyBoughtTogetherResolver $resolver,
        private readonly UpsellConfigurationProvider $configurationProvider,
    ) {}

    /**
     * @return array<array{product: ProductInterface, discount: int|null}>
     */
    public function getFrequentlyBoughtTogether(ProductInterface $product): array
    {
        return $this->resolver->resolve($product);
    }

    public function getConfiguration(): UpsellConfiguration
    {
        return $this->configurationProvider->getConfiguration();
    }
}
