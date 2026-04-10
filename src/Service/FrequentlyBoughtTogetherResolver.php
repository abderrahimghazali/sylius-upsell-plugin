<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Service;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellConfiguration;
use Abderrahim\SyliusUpsellPlugin\Entity\UpsellRelation;
use Abderrahim\SyliusUpsellPlugin\Repository\UpsellRelationRepository;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class FrequentlyBoughtTogetherResolver
{
    /** @param ProductRepositoryInterface<\Sylius\Component\Core\Model\ProductInterface> $productRepository */
    public function __construct(
        private readonly UpsellRelationRepository $upsellRelationRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly UpsellConfigurationProvider $configurationProvider,
        private readonly ChannelContextInterface $channelContext,
        private readonly CacheInterface $cache,
    ) {}

    /**
     * @return array<array{product: ProductInterface, discount: int|null}>
     */
    public function resolve(ProductInterface $product): array
    {
        $config = $this->configurationProvider->getConfiguration();

        if (!$config->isEnabled()) {
            return [];
        }

        $maxProducts = $config->getMaxProductsShown();

        // Try manual relations first
        $manualRelations = $this->upsellRelationRepository->findBySourceProduct($product, $maxProducts);

        if (\count($manualRelations) > 0) {
            return $this->formatManualResults($manualRelations);
        }

        // Fall back to algorithmic if configured
        if ($config->getFallbackStrategy() === UpsellConfiguration::FALLBACK_DISABLED
            || $config->getFallbackStrategy() === UpsellConfiguration::FALLBACK_MANUAL_ONLY) {
            return [];
        }

        return $this->resolveAlgorithmic($product, $config);
    }

    /**
     * @param UpsellRelation[] $relations
     * @return array<array{product: ProductInterface, discount: int|null}>
     */
    private function formatManualResults(array $relations): array
    {
        $results = [];

        foreach ($relations as $relation) {
            $relatedProduct = $relation->getRelatedProduct();
            if (null !== $relatedProduct && $relatedProduct->isEnabled()) {
                $results[] = [
                    'product' => $relatedProduct,
                    'discount' => $relation->getDiscount(),
                ];
            }
        }

        return $results;
    }

    /**
     * @return array<array{product: ProductInterface, discount: int|null}>
     */
    private function resolveAlgorithmic(ProductInterface $product, UpsellConfiguration $config): array
    {
        $channelCode = $this->channelContext->getChannel()->getCode();
        $cacheKey = sprintf('product.fbt.%d.%s', $product->getId(), $channelCode);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($product, $config): array {
            $item->expiresAfter(3600);

            $coPurchased = $this->upsellRelationRepository->findCoPurchasedProducts(
                $product,
                $config->getMinCoPurchaseThreshold(),
                $config->getMaxProductsShown(),
            );

            $productIds = array_map(fn(array $row) => (int) $row['product_id'], $coPurchased);

            if ([] === $productIds) {
                return [];
            }

            /** @var ProductInterface[] $products */
            $products = $this->productRepository->findBy(['id' => $productIds]);

            // Index by ID to preserve co-purchase ranking order
            $indexed = [];
            foreach ($products as $p) {
                $indexed[$p->getId()] = $p;
            }

            $results = [];
            foreach ($productIds as $id) {
                $relatedProduct = $indexed[$id] ?? null;
                if (null !== $relatedProduct && $relatedProduct->isEnabled()) {
                    $results[] = [
                        'product' => $relatedProduct,
                        'discount' => null,
                    ];
                }
            }

            return $results;
        });
    }
}
