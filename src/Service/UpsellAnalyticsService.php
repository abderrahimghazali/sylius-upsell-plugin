<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Service;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellImpression;
use Abderrahim\SyliusUpsellPlugin\Entity\UpsellOffer;
use Abderrahim\SyliusUpsellPlugin\Repository\UpsellImpressionRepository;
use Doctrine\ORM\EntityManagerInterface;

class UpsellAnalyticsService
{
    public function __construct(
        private readonly UpsellImpressionRepository $impressionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function recordImpression(
        string $type,
        ?string $orderToken,
        string $productCode,
        string $channel,
        ?UpsellOffer $offer = null,
    ): UpsellImpression {
        $impression = new UpsellImpression();
        $impression->setType($type);
        $impression->setOrderToken($orderToken);
        $impression->setProductCode($productCode);
        $impression->setChannel($channel);
        $impression->setAction(UpsellImpression::ACTION_SHOWN);
        $impression->setUpsellOffer($offer);

        $this->entityManager->persist($impression);
        $this->entityManager->flush();

        return $impression;
    }

    public function recordAccepted(int $impressionId, int $revenue): void
    {
        $impression = $this->impressionRepository->find($impressionId);

        if (null === $impression) {
            return;
        }

        $impression->setAction(UpsellImpression::ACTION_ACCEPTED);
        $impression->setRevenue($revenue);

        $this->entityManager->flush();
    }

    public function recordDeclined(int $impressionId): void
    {
        $impression = $this->impressionRepository->find($impressionId);

        if (null === $impression) {
            return;
        }

        $impression->setAction(UpsellImpression::ACTION_DECLINED);

        $this->entityManager->flush();
    }

    /**
     * @return array{impressions: int, accepted: int, declined: int, revenue: int, rate: float}
     */
    public function getSummary(\DateTimeInterface $from, \DateTimeInterface $to, string $channelCode): array
    {
        $summary = $this->impressionRepository->getSummary($from, $to, $channelCode);

        $summary['rate'] = $summary['impressions'] > 0
            ? round(($summary['accepted'] / $summary['impressions']) * 100, 1)
            : 0.0;

        return $summary;
    }

    /**
     * @return array<array{offer_name: string, impressions: int, accepted: int, revenue: int}>
     */
    public function getBreakdownByOffer(\DateTimeInterface $from, \DateTimeInterface $to, string $channelCode): array
    {
        return $this->impressionRepository->getBreakdownByOffer($from, $to, $channelCode);
    }

    /**
     * @return array<array{date: string, revenue: int}>
     */
    public function getDailyRevenue(\DateTimeInterface $from, \DateTimeInterface $to, string $channelCode): array
    {
        return $this->impressionRepository->getDailyRevenue($from, $to, $channelCode);
    }
}
