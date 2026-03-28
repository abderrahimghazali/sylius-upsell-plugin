<?php

declare(strict_types=1);

namespace Abderrahim\SyliusUpsellPlugin\Repository;

use Abderrahim\SyliusUpsellPlugin\Entity\UpsellRelation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Core\Model\ProductInterface;

/**
 * @extends ServiceEntityRepository<UpsellRelation>
 */
class UpsellRelationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UpsellRelation::class);
    }

    /**
     * @return UpsellRelation[]
     */
    public function findBySourceProduct(ProductInterface $product, int $limit = 4): array
    {
        return $this->createQueryBuilder('ur')
            ->andWhere('ur.sourceProduct = :product')
            ->setParameter('product', $product)
            ->orderBy('ur.position', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products most frequently bought together with the given product.
     *
     * @return array<array{product_id: int, purchase_count: int}>
     */
    public function findCoPurchasedProducts(
        ProductInterface $product,
        int $minThreshold = 3,
        int $limit = 4,
    ): array {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<'SQL'
            SELECT pv2.product_id AS product_id, COUNT(DISTINCT oi1.order_id) AS purchase_count
            FROM sylius_order_item oi1
            INNER JOIN sylius_product_variant pv1 ON pv1.id = oi1.variant_id
            INNER JOIN sylius_order_item oi2 ON oi1.order_id = oi2.order_id
            INNER JOIN sylius_product_variant pv2 ON pv2.id = oi2.variant_id
            INNER JOIN sylius_order o ON o.id = oi1.order_id
            INNER JOIN sylius_product p ON p.id = pv2.product_id
            WHERE pv1.product_id = :productId
              AND pv2.product_id != :productId
              AND o.state = 'fulfilled'
              AND p.enabled = 1
              AND (pv2.tracked = 0 OR pv2.on_hand > 0)
            GROUP BY pv2.product_id
            HAVING COUNT(DISTINCT oi1.order_id) >= :threshold
            ORDER BY purchase_count DESC
            LIMIT %d
        SQL;

        $sql = sprintf($sql, $limit);

        $result = $conn->executeQuery($sql, [
            'productId' => $product->getId(),
            'threshold' => $minThreshold,
        ]);

        return $result->fetchAllAssociative();
    }
}
