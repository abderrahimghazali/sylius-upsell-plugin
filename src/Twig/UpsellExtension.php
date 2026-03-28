<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Twig;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellConfiguration;
use Abderrahim\SyliusUpsellPlugin\Service\FrequentlyBoughtTogetherResolver;
use Abderrahim\SyliusUpsellPlugin\Service\UpsellConfigurationProvider;
use Sylius\Component\Core\Model\ProductInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class UpsellExtension extends AbstractExtension
{
    public function __construct(
        private readonly FrequentlyBoughtTogetherResolver $resolver,
        private readonly UpsellConfigurationProvider $configurationProvider,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('upsell_frequently_bought_together', $this->getFrequentlyBoughtTogether(...)),
            new TwigFunction('upsell_configuration', $this->getConfiguration(...)),
        ];
    }

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
