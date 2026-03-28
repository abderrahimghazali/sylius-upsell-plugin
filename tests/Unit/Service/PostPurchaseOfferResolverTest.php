<?php

declare(strict_types=1);

namespace Tests\Abderrahim\SyliusUpsellPlugin\Unit\Service;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellOffer;
use Abderrahim\SyliusUpsellPlugin\Repository\UpsellOfferRepository;
use Abderrahim\SyliusUpsellPlugin\Service\PostPurchaseOfferResolver;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;

final class PostPurchaseOfferResolverTest extends TestCase
{
    private UpsellOfferRepository&MockObject $offerRepository;
    private PostPurchaseOfferResolver $resolver;

    protected function setUp(): void
    {
        $this->offerRepository = $this->createMock(UpsellOfferRepository::class);
        $this->resolver = new PostPurchaseOfferResolver($this->offerRepository);
    }

    public function testReturnsNullWhenNoOffersMatch(): void
    {
        $order = $this->createOrderWithProductIds([1, 2]);

        $this->offerRepository
            ->method('findMatchingOffers')
            ->willReturn([]);

        self::assertNull($this->resolver->resolve($order));
    }

    public function testReturnsHighestPriorityOffer(): void
    {
        $order = $this->createOrderWithProductIds([1]);

        $offerProduct = $this->createMock(ProductInterface::class);
        $offerProduct->method('getId')->willReturn(99);
        $offerProduct->method('isEnabled')->willReturn(true);

        $offer = $this->createMock(UpsellOffer::class);
        $offer->method('getOfferProduct')->willReturn($offerProduct);

        $this->offerRepository
            ->method('findMatchingOffers')
            ->willReturn([$offer]);

        self::assertSame($offer, $this->resolver->resolve($order));
    }

    public function testExcludesProductsAlreadyInOrder(): void
    {
        $order = $this->createOrderWithProductIds([1, 5]);

        // Offer product is product 5, which is already in the order
        $alreadyInOrder = $this->createMock(ProductInterface::class);
        $alreadyInOrder->method('getId')->willReturn(5);
        $alreadyInOrder->method('isEnabled')->willReturn(true);

        $excludedOffer = $this->createMock(UpsellOffer::class);
        $excludedOffer->method('getOfferProduct')->willReturn($alreadyInOrder);

        // Second offer has a different product
        $differentProduct = $this->createMock(ProductInterface::class);
        $differentProduct->method('getId')->willReturn(99);
        $differentProduct->method('isEnabled')->willReturn(true);

        $validOffer = $this->createMock(UpsellOffer::class);
        $validOffer->method('getOfferProduct')->willReturn($differentProduct);

        $this->offerRepository
            ->method('findMatchingOffers')
            ->willReturn([$excludedOffer, $validOffer]);

        self::assertSame($validOffer, $this->resolver->resolve($order));
    }

    public function testExcludesDisabledOfferProducts(): void
    {
        $order = $this->createOrderWithProductIds([1]);

        $disabledProduct = $this->createMock(ProductInterface::class);
        $disabledProduct->method('getId')->willReturn(10);
        $disabledProduct->method('isEnabled')->willReturn(false);

        $offer = $this->createMock(UpsellOffer::class);
        $offer->method('getOfferProduct')->willReturn($disabledProduct);

        $this->offerRepository
            ->method('findMatchingOffers')
            ->willReturn([$offer]);

        self::assertNull($this->resolver->resolve($order));
    }

    public function testReturnsNullWhenOfferProductIsNull(): void
    {
        $order = $this->createOrderWithProductIds([1]);

        $offer = $this->createMock(UpsellOffer::class);
        $offer->method('getOfferProduct')->willReturn(null);

        $this->offerRepository
            ->method('findMatchingOffers')
            ->willReturn([$offer]);

        self::assertNull($this->resolver->resolve($order));
    }

    /**
     * @param int[] $productIds
     */
    private function createOrderWithProductIds(array $productIds): OrderInterface&MockObject
    {
        $items = [];
        foreach ($productIds as $id) {
            $product = $this->createMock(ProductInterface::class);
            $product->method('getId')->willReturn($id);

            $item = $this->createMock(OrderItemInterface::class);
            $item->method('getProduct')->willReturn($product);

            $items[] = $item;
        }

        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn(new ArrayCollection($items));

        return $order;
    }
}
