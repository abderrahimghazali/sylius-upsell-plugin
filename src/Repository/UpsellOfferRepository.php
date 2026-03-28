<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Repository;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellOffer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UpsellOffer>
 */
class UpsellOfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UpsellOffer::class);
    }

    /**
     * Find enabled offers matching any of the given product IDs or catch-all (null trigger).
     *
     * @param int[] $productIds
     * @return UpsellOffer[]
     */
    public function findMatchingOffers(array $productIds, \DateTimeInterface $now): array
    {
        $qb = $this->createQueryBuilder('o')
            ->andWhere('o.enabled = :enabled')
            ->setParameter('enabled', true)
            ->andWhere('o.startsAt IS NULL OR o.startsAt <= :now')
            ->andWhere('o.endsAt IS NULL OR o.endsAt >= :now')
            ->setParameter('now', $now)
            ->orderBy('o.priority', 'DESC')
        ;

        if (\count($productIds) > 0) {
            $qb->andWhere('o.triggerProduct IS NULL OR o.triggerProduct IN (:productIds)')
                ->setParameter('productIds', $productIds);
        } else {
            $qb->andWhere('o.triggerProduct IS NULL');
        }

        return $qb->getQuery()->getResult();
    }
}
