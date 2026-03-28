<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Controller\Shop;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellOffer;
use Abderrahim\SyliusUpsellPlugin\Service\PostPurchaseOfferResolver;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Promotion\Model\PromotionActionInterface;
use Sylius\Component\Promotion\Model\PromotionCouponInterface;
use Sylius\Component\Promotion\Model\PromotionInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PostPurchaseController extends AbstractController
{
    public function __construct(
        private readonly PostPurchaseOfferResolver $offerResolver,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ChannelContextInterface $channelContext,
        private readonly LocaleContextInterface $localeContext,
        private readonly FactoryInterface $promotionFactory,
        private readonly FactoryInterface $promotionCouponFactory,
        private readonly FactoryInterface $promotionActionFactory,
        private readonly FactoryInterface $orderFactory,
        private readonly FactoryInterface $orderItemFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function offerAction(string $orderToken): JsonResponse
    {
        /** @var OrderInterface|null $order */
        $order = $this->orderRepository->findOneBy(['tokenValue' => $orderToken]);

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

    public function acceptAction(string $orderToken): JsonResponse
    {
        /** @var OrderInterface|null $originalOrder */
        $originalOrder = $this->orderRepository->findOneBy(['tokenValue' => $orderToken]);

        if (null === $originalOrder) {
            return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $offer = $this->offerResolver->resolve($originalOrder);

        if (null === $offer) {
            return new JsonResponse(['error' => 'No offer available'], Response::HTTP_GONE);
        }

        $variant = $this->resolveVariant($offer);
        if (null === $variant) {
            return new JsonResponse(['error' => 'Product unavailable'], Response::HTTP_GONE);
        }

        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        $localeCode = $this->localeContext->getLocaleCode();

        // Create promotion with single-use coupon
        $couponCode = $this->generateCouponCode($offer);
        $coupon = $this->createUpsellPromotion($offer, $couponCode, $channel);

        // Create new cart order
        /** @var OrderInterface $newOrder */
        $newOrder = $this->orderFactory->createNew();
        $newOrder->setChannel($channel);
        $newOrder->setLocaleCode($localeCode);
        $newOrder->setCurrencyCode($channel->getBaseCurrency()->getCode());
        $newOrder->setCustomer($originalOrder->getCustomer());

        $this->entityManager->persist($newOrder);

        // Add offer product to cart
        /** @var \Sylius\Component\Core\Model\OrderItemInterface $orderItem */
        $orderItem = $this->orderItemFactory->createNew();
        $orderItem->setVariant($variant);
        $orderItem->setUnitPrice($variant->getChannelPricingForChannel($channel)?->getPrice() ?? 0);
        $orderItem->setOrder($newOrder);
        $newOrder->addItem($orderItem);

        // Apply coupon
        $newOrder->setPromotionCoupon($coupon);

        $this->entityManager->flush();

        $checkoutUrl = $this->urlGenerator->generate('sylius_shop_checkout_start');

        return new JsonResponse([
            'checkoutUrl' => $checkoutUrl,
            'orderToken' => $newOrder->getTokenValue(),
            'couponCode' => $couponCode,
        ]);
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

    private function generateCouponCode(UpsellOffer $offer): string
    {
        return sprintf('UPSELL-%d-%s', $offer->getId(), strtoupper(bin2hex(random_bytes(3))));
    }

    private function createUpsellPromotion(UpsellOffer $offer, string $couponCode, ChannelInterface $channel): PromotionCouponInterface
    {
        $promotionCode = 'upsell_offer_' . $offer->getId() . '_' . strtolower(bin2hex(random_bytes(3)));

        /** @var PromotionInterface $promotion */
        $promotion = $this->promotionFactory->createNew();
        $promotion->setCode($promotionCode);
        $promotion->setName(sprintf('Upsell: %s', $offer->getName()));
        $promotion->setCouponBased(true);
        $promotion->setExclusive(false);
        $promotion->setPriority(0);
        $promotion->addChannel($channel);

        /** @var PromotionActionInterface $action */
        $action = $this->promotionActionFactory->createNew();
        $action->setType('order_percentage_discount');
        $action->setConfiguration([
            $channel->getCode() => ['percentage' => $offer->getDiscountPercent() / 100],
        ]);
        $promotion->addAction($action);

        $this->entityManager->persist($promotion);

        /** @var PromotionCouponInterface $coupon */
        $coupon = $this->promotionCouponFactory->createNew();
        $coupon->setCode($couponCode);
        $coupon->setUsageLimit(1);
        $coupon->setExpiresAt(new \DateTime('+24 hours'));
        $coupon->setPromotion($promotion);

        $this->entityManager->persist($coupon);
        $this->entityManager->flush();

        return $coupon;
    }
}
