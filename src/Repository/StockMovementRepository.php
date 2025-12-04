<?php

namespace App\Repository;

use App\Entity\StockMovement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Product;
use App\Enum\StockMovementType;

/**
 * @extends ServiceEntityRepository<StockMovement>
 */
class StockMovementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockMovement::class);
    }

    public function calculateStockForProduct(Product $product):int
    {
        $qb = $this->createQueryBuilder('sm')
            ->select('SUM (CASE
                WHEN sm.type = :inType THEN sm.quantity
                WHEN sm.type = :outType THEN -sm.quantity
                WHEN sm.type = :adjType AND sm.quantity >= 0 THEN sm.quantity
                ELSE -sm.quantity
            END) AS current_stock')
            ->where('sm.product = :product')
            ->setParameter('product', $product)
            ->setParameter('inType', StockMovementType::IN)
            ->setParameter('outType', StockMovementType::OUT)
            ->setParameter('adjType', StockMovementType::ADJUSTMENT)
            ->getQuery();

            $result = $qb->getSingleScalarResult();

            return (int) $result?: 0;
    }
}
