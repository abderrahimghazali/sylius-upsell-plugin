<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Service;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellOffer;
use Abderrahim\SyliusUpsellPlugin\Repository\UpsellOfferRepository;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;

final class PostPurchaseOfferResolver
{
    public function __construct(
        private readonly UpsellOfferRepository $offerRepository,
    ) {}

    public function resolve(OrderInterface $order): ?UpsellOffer
    {
        $productIds = $this->extractProductIds($order);

        $offers = $this->offerRepository->findMatchingOffers(
            $productIds,
            new \DateTime(),
        );

        foreach ($offers as $offer) {
            // Exclude products already in the completed order
            $offerProductId = $offer->getOfferProduct()?->getId();
            if (null !== $offerProductId && \in_array($offerProductId, $productIds, true)) {
                continue;
            }

            // Ensure offer product is enabled
            if (null === $offer->getOfferProduct() || !$offer->getOfferProduct()->isEnabled()) {
                continue;
            }

            return $offer;
        }

        return null;
    }

    /**
     * @return int[]
     */
    private function extractProductIds(OrderInterface $order): array
    {
        $ids = [];

        /** @var OrderItemInterface $item */
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            if (null !== $product && null !== $product->getId()) {
                $ids[] = $product->getId();
            }
        }

        return array_unique($ids);
    }
}
