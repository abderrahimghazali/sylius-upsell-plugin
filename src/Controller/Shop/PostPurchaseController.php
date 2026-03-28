<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Controller\Shop;

use Abderrahim\SyliusUpsellPlugin\Service\PostPurchaseOfferResolver;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class PostPurchaseController extends AbstractController
{
    public function __construct(
        private readonly PostPurchaseOfferResolver $offerResolver,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ChannelContextInterface $channelContext,
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

        $variant = $offer->getOfferVariant();
        if (null === $variant) {
            $product = $offer->getOfferProduct();
            $variants = $product?->getVariants();
            $variant = ($variants && !$variants->isEmpty()) ? $variants->first() : null;
        }

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
}
