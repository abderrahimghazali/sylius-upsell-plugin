<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Controller\Shop;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellOffer;
use Abderrahim\SyliusUpsellPlugin\Repository\UpsellOfferRepository;
use Abderrahim\SyliusUpsellPlugin\Service\PostPurchaseOfferResolver;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class PostPurchaseController extends AbstractController
{
    public function __construct(
        private readonly PostPurchaseOfferResolver $offerResolver,
        private readonly UpsellOfferRepository $offerRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ChannelContextInterface $channelContext,
        private readonly CartContextInterface $cartContext,
        private readonly OrderModifierInterface $orderModifier,
        private readonly OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        private readonly FactoryInterface $orderItemFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function offerAction(int $orderId): JsonResponse
    {
        /** @var OrderInterface|null $order */
        $order = $this->orderRepository->find($orderId);

        if (null === $order) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $offer = $this->offerResolver->resolve($order);

        if (null === $offer) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        $variant = $this->resolveVariant($offer);
        if (null === $variant) {
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        $channelPricing = $variant->getChannelPricingForChannel($channel);
        $originalPrice = $channelPricing?->getPrice() ?? 0;
        $discountedPrice = (int) round($originalPrice * (100 - $offer->getDiscountPercent()) / 100);

        $product = $offer->getOfferProduct();
        $image = $product?->getImagesByType('main')->first();

        return new JsonResponse([
            'offerId' => $offer->getId(),
            'headline' => $offer->getHeadline(),
            'body' => $offer->getBody(),
            'ctaLabel' => $offer->getCtaLabel(),
            'declineLabel' => $offer->getDeclineLabel(),
            'discountPercent' => $offer->getDiscountPercent(),
            'product' => [
                'name' => $product?->getName(),
                'slug' => $product?->getSlug(),
                'variantCode' => $variant->getCode(),
                'originalPrice' => $originalPrice,
                'discountedPrice' => $discountedPrice,
                'currency' => $channel->getBaseCurrency()?->getCode(),
                'image' => $image ? $image->getPath() : null,
            ],
        ]);
    }

    public function acceptAction(int $offerId): JsonResponse
    {
        /** @var \Abderrahim\SyliusUpsellPlugin\Entity\UpsellOffer|null $offer */
        $offer = $this->offerRepository->find($offerId);

        if (null === $offer || !$offer->isEnabled()) {
            return new JsonResponse(['error' => 'Offer not available'], Response::HTTP_GONE);
        }

        $variant = $this->resolveVariant($offer);
        if (null === $variant) {
            return new JsonResponse(['error' => 'Product unavailable'], Response::HTTP_GONE);
        }

        /** @var OrderInterface $cart */
        $cart = $this->cartContext->getCart();

        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();

        /** @var \Sylius\Component\Core\Model\OrderItemInterface $orderItem */
        $orderItem = $this->orderItemFactory->createNew();
        $orderItem->setVariant($variant);

        // Apply upsell discount
        if ($offer->getDiscountPercent() > 0) {
            $originalPrice = $variant->getChannelPricingForChannel($channel)?->getPrice() ?? 0;
            $discountedPrice = (int) round($originalPrice * (100 - $offer->getDiscountPercent()) / 100);
            $orderItem->setUnitPrice($discountedPrice);
            $orderItem->setImmutable(true);
        }

        $this->orderItemQuantityModifier->modify($orderItem, 1);
        $this->orderModifier->addToOrder($cart, $orderItem);

        $this->entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    private function resolveVariant(UpsellOffer $offer): ?ProductVariantInterface
    {
        if (null !== $offer->getOfferVariant()) {
            return $offer->getOfferVariant();
        }

        $product = $offer->getOfferProduct();
        if (null === $product) {
            return null;
        }

        $variants = $product->getVariants();

        return $variants->isEmpty() ? null : $variants->first();
    }
}
