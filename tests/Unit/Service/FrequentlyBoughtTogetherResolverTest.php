<?php

declare(strict_types=1);

namespace Tests\Abderrahim\SyliusUpsellPlugin\Unit\Service;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellConfiguration;
use Abderrahim\SyliusUpsellPlugin\Entity\UpsellRelation;
use Abderrahim\SyliusUpsellPlugin\Repository\UpsellRelationRepository;
use Abderrahim\SyliusUpsellPlugin\Service\FrequentlyBoughtTogetherResolver;
use Abderrahim\SyliusUpsellPlugin\Service\UpsellConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class FrequentlyBoughtTogetherResolverTest extends TestCase
{
    private UpsellRelationRepository&MockObject $upsellRelationRepository;
    private ProductRepositoryInterface&MockObject $productRepository;
    private UpsellConfigurationProvider&MockObject $configurationProvider;
    private ChannelContextInterface&MockObject $channelContext;
    private CacheInterface&MockObject $cache;
    private FrequentlyBoughtTogetherResolver $resolver;

    protected function setUp(): void
    {
        $this->upsellRelationRepository = $this->createMock(UpsellRelationRepository::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->configurationProvider = $this->createMock(UpsellConfigurationProvider::class);
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->resolver = new FrequentlyBoughtTogetherResolver(
            $this->upsellRelationRepository,
            $this->productRepository,
            $this->configurationProvider,
            $this->channelContext,
            $this->cache,
        );
    }

    public function testReturnsEmptyArrayWhenDisabled(): void
    {
        $product = $this->createMock(ProductInterface::class);

        $config = new UpsellConfiguration();
        $config->setEnabled(false);

        $this->configurationProvider
            ->method('getConfiguration')
            ->willReturn($config);

        $result = $this->resolver->resolve($product);

        self::assertSame([], $result);
    }

    public function testReturnsManualRelationsWhenAvailable(): void
    {
        $sourceProduct = $this->createMock(ProductInterface::class);
        $relatedProduct = $this->createMock(ProductInterface::class);
        $relatedProduct->method('isEnabled')->willReturn(true);

        $config = new UpsellConfiguration();
        $config->setEnabled(true);

        $this->configurationProvider
            ->method('getConfiguration')
            ->willReturn($config);

        $relation = $this->createMock(UpsellRelation::class);
        $relation->method('getRelatedProduct')->willReturn($relatedProduct);
        $relation->method('getDiscount')->willReturn(10);

        $this->upsellRelationRepository
            ->method('findBySourceProduct')
            ->with($sourceProduct, 4)
            ->willReturn([$relation]);

        $result = $this->resolver->resolve($sourceProduct);

        self::assertCount(1, $result);
        self::assertSame($relatedProduct, $result[0]['product']);
        self::assertSame(10, $result[0]['discount']);
    }

    public function testFallsBackToAlgorithmicWhenNoManualRelations(): void
    {
        $sourceProduct = $this->createMock(ProductInterface::class);
        $sourceProduct->method('getId')->willReturn(1);

        $relatedProduct = $this->createMock(ProductInterface::class);
        $relatedProduct->method('isEnabled')->willReturn(true);

        $config = new UpsellConfiguration();
        $config->setEnabled(true);
        $config->setFallbackStrategy(UpsellConfiguration::FALLBACK_ALGORITHMIC);

        $this->configurationProvider
            ->method('getConfiguration')
            ->willReturn($config);

        $this->upsellRelationRepository
            ->method('findBySourceProduct')
            ->willReturn([]);

        $channel = $this->createMock(ChannelInterface::class);
        $channel->method('getCode')->willReturn('WEB');
        $this->channelContext->method('getChannel')->willReturn($channel);

        // Cache should execute the callback
        $this->cache
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->method('expiresAfter')->willReturn($item);

                return $callback($item);
            });

        $this->upsellRelationRepository
            ->method('findCoPurchasedProducts')
            ->with($sourceProduct, 3, 4)
            ->willReturn([
                ['product_id' => 42, 'purchase_count' => 10],
            ]);

        $this->productRepository
            ->method('find')
            ->with(42)
            ->willReturn($relatedProduct);

        $result = $this->resolver->resolve($sourceProduct);

        self::assertCount(1, $result);
        self::assertSame($relatedProduct, $result[0]['product']);
        self::assertNull($result[0]['discount']);
    }

    public function testReturnsEmptyWhenFallbackDisabled(): void
    {
        $product = $this->createMock(ProductInterface::class);

        $config = new UpsellConfiguration();
        $config->setEnabled(true);
        $config->setFallbackStrategy(UpsellConfiguration::FALLBACK_DISABLED);

        $this->configurationProvider
            ->method('getConfiguration')
            ->willReturn($config);

        $this->upsellRelationRepository
            ->method('findBySourceProduct')
            ->willReturn([]);

        $result = $this->resolver->resolve($product);

        self::assertSame([], $result);
    }

    public function testReturnsEmptyWhenManualOnlyAndNoManualRelations(): void
    {
        $product = $this->createMock(ProductInterface::class);

        $config = new UpsellConfiguration();
        $config->setEnabled(true);
        $config->setFallbackStrategy(UpsellConfiguration::FALLBACK_MANUAL_ONLY);

        $this->configurationProvider
            ->method('getConfiguration')
            ->willReturn($config);

        $this->upsellRelationRepository
            ->method('findBySourceProduct')
            ->willReturn([]);

        $result = $this->resolver->resolve($product);

        self::assertSame([], $result);
    }

    public function testExcludesDisabledRelatedProducts(): void
    {
        $sourceProduct = $this->createMock(ProductInterface::class);

        $enabledProduct = $this->createMock(ProductInterface::class);
        $enabledProduct->method('isEnabled')->willReturn(true);

        $disabledProduct = $this->createMock(ProductInterface::class);
        $disabledProduct->method('isEnabled')->willReturn(false);

        $config = new UpsellConfiguration();
        $config->setEnabled(true);

        $this->configurationProvider
            ->method('getConfiguration')
            ->willReturn($config);

        $relation1 = $this->createMock(UpsellRelation::class);
        $relation1->method('getRelatedProduct')->willReturn($enabledProduct);
        $relation1->method('getDiscount')->willReturn(null);

        $relation2 = $this->createMock(UpsellRelation::class);
        $relation2->method('getRelatedProduct')->willReturn($disabledProduct);
        $relation2->method('getDiscount')->willReturn(5);

        $this->upsellRelationRepository
            ->method('findBySourceProduct')
            ->willReturn([$relation1, $relation2]);

        $result = $this->resolver->resolve($sourceProduct);

        self::assertCount(1, $result);
        self::assertSame($enabledProduct, $result[0]['product']);
    }
}
