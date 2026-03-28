<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Repository;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellImpression;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UpsellImpression>
 */
class UpsellImpressionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UpsellImpression::class);
    }

    /**
     * @return array{impressions: int, accepted: int, declined: int, revenue: int}
     */
    public function getSummary(\DateTimeInterface $from, \DateTimeInterface $to, string $channelCode): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT
                COUNT(*) AS impressions,
                SUM(CASE WHEN action = 'accepted' THEN 1 ELSE 0 END) AS accepted,
                SUM(CASE WHEN action = 'declined' THEN 1 ELSE 0 END) AS declined,
                SUM(CASE WHEN action = 'accepted' THEN revenue ELSE 0 END) AS revenue
            FROM upsell_impression
            WHERE created_at BETWEEN :from AND :to
              AND channel = :channel
        SQL;

        $result = $conn->executeQuery($sql, [
            'from' => $from->format('Y-m-d 00:00:00'),
            'to' => $to->format('Y-m-d 23:59:59'),
            'channel' => $channelCode,
        ])->fetchAssociative();

        return [
            'impressions' => (int) ($result['impressions'] ?? 0),
            'accepted' => (int) ($result['accepted'] ?? 0),
            'declined' => (int) ($result['declined'] ?? 0),
            'revenue' => (int) ($result['revenue'] ?? 0),
        ];
    }

    /**
     * @return array<array{offer_name: string, impressions: int, accepted: int, revenue: int}>
     */
    public function getBreakdownByOffer(\DateTimeInterface $from, \DateTimeInterface $to, string $channelCode): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT
                COALESCE(uo.name, 'Frequently Bought Together') AS offer_name,
                i.type,
                COUNT(*) AS impressions,
                SUM(CASE WHEN i.action = 'accepted' THEN 1 ELSE 0 END) AS accepted,
                SUM(CASE WHEN i.action = 'accepted' THEN i.revenue ELSE 0 END) AS revenue
            FROM upsell_impression i
            LEFT JOIN upsell_offer uo ON uo.id = i.upsell_offer_id
            WHERE i.created_at BETWEEN :from AND :to
              AND i.channel = :channel
            GROUP BY uo.id, uo.name, i.type
            ORDER BY revenue DESC
        SQL;

        return $conn->executeQuery($sql, [
            'from' => $from->format('Y-m-d 00:00:00'),
            'to' => $to->format('Y-m-d 23:59:59'),
            'channel' => $channelCode,
        ])->fetchAllAssociative();
    }

    /**
     * @return array<array{date: string, revenue: int}>
     */
    public function getDailyRevenue(\DateTimeInterface $from, \DateTimeInterface $to, string $channelCode): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT
                DATE(created_at) AS date,
                SUM(CASE WHEN action = 'accepted' THEN revenue ELSE 0 END) AS revenue
            FROM upsell_impression
            WHERE created_at BETWEEN :from AND :to
              AND channel = :channel
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        SQL;

        return $conn->executeQuery($sql, [
            'from' => $from->format('Y-m-d 00:00:00'),
            'to' => $to->format('Y-m-d 23:59:59'),
            'channel' => $channelCode,
        ])->fetchAllAssociative();
    }
}
