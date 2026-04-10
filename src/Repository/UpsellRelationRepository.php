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
     * @return list<array<string, mixed>>
     */
    public function findCoPurchasedProducts(
        ProductInterface $product,
        int $minThreshold = 3,
        int $limit = 4,
    ): array {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        $orderItemTable = $em->getClassMetadata(\Sylius\Component\Core\Model\OrderItem::class)->getTableName();
        $variantTable = $em->getClassMetadata(\Sylius\Component\Core\Model\ProductVariant::class)->getTableName();
        $orderTable = $em->getClassMetadata(\Sylius\Component\Core\Model\Order::class)->getTableName();
        $productTable = $em->getClassMetadata(\Sylius\Component\Core\Model\Product::class)->getTableName();

        $sql = <<<SQL
            SELECT pv2.product_id AS product_id, COUNT(DISTINCT oi1.order_id) AS purchase_count
            FROM {$orderItemTable} oi1
            INNER JOIN {$variantTable} pv1 ON pv1.id = oi1.variant_id
            INNER JOIN {$orderItemTable} oi2 ON oi1.order_id = oi2.order_id
            INNER JOIN {$variantTable} pv2 ON pv2.id = oi2.variant_id
            INNER JOIN {$orderTable} o ON o.id = oi1.order_id
            INNER JOIN {$productTable} p ON p.id = pv2.product_id
            WHERE pv1.product_id = :productId
              AND pv2.product_id != :productId
              AND o.state = 'fulfilled'
              AND p.enabled = :enabled
              AND (pv2.tracked = :notTracked OR pv2.on_hand > 0)
            GROUP BY pv2.product_id
            HAVING COUNT(DISTINCT oi1.order_id) >= :threshold
            ORDER BY purchase_count DESC
            LIMIT :limit
        SQL;

        $result = $conn->executeQuery($sql, [
            'productId' => $product->getId(),
            'threshold' => $minThreshold,
            'limit' => $limit,
            'enabled' => true,
            'notTracked' => false,
        ], [
            'productId' => \Doctrine\DBAL\ParameterType::INTEGER,
            'threshold' => \Doctrine\DBAL\ParameterType::INTEGER,
            'limit' => \Doctrine\DBAL\ParameterType::INTEGER,
            'enabled' => \Doctrine\DBAL\ParameterType::BOOLEAN,
            'notTracked' => \Doctrine\DBAL\ParameterType::BOOLEAN,
        ]);

        return $result->fetchAllAssociative();
    }
}
