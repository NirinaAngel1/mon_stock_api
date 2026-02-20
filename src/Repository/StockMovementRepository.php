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

    public function findByProduct(Product $product, int $limit = 20):array
    {
        $qb = $this->createQueryBuilder('sm')
            ->where('sm.product = :product')
            ->setParameter('product', $product)
            ->orderBy('sm.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();

            return $qb->getResult();
    }

    public function findLastMovements(Product $product, int $limit = 20):array
    {
        $qb = $this->createQueryBuilder('sm')
            ->leftJoin('sm.product', 'p')
            ->addSelect('p')
            ->leftJoin('sm.user','u')
            ->addSelect('u')
            ->where('sm.product = :product')
            ->setParameter('product', $product)
            ->orderBy('sm.date','DESC')
            ->setMaxResults($limit)
            ->getQuery();

            return $qb->getResult();
    }

    public function findLastMovementGlobal(int $limit = 10):array
    {
        $qb = $this->createQueryBuilder('sm')
            ->leftJoin('sm.product', 'p')
            ->addSelect('p')
            ->leftJoin('sm.user','u')
            ->addSelect('u')
            ->orderBy('sm.date','DESC')
            ->setMaxResults($limit)
            ->getQuery();

            return $qb->getResult();
    }

    public function getStockExpression(string $alias ='sm'):string
    {
        return "COALESCE(SUM(
                    CASE 
                        WHEN $alias.type = :inType THEN $alias.quantity
                        WHEN $alias.type = :outType THEN -$alias.quantity
                        WHEN $alias.type = :adjType THEN $alias.quantity
                        ELSE 0
                    END
                ), 0)";
    }

    public function calculateStockForProduct(Product $product):int
    {
        $strExpr = $this->getStockExpression();
        $qb = $this->createQueryBuilder('sm')
            ->select("$strExpr AS current_stock")
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
