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

    public function getMonthlyMovements(): array
    {
        //récupération des mouvements de l'année en cours
        $qb = $this->createQueryBuilder('sm')
            ->where('sm.date >= :start')
            ->andWhere('sm.date <= :end')
            ->setParameter('start', new \DateTime(date('Y-01-01 00:00:00')))
            ->setParameter('end', new \DateTime(date('Y-12-31 23:59:59')))
            ->getQuery()
            ->getResult();

            $months = [
                1 => 'Janvier',
                2 => 'Février',
                3 => 'Mars',
                4 => 'Avril',
                5 => 'Mai',
                6 => 'Juin',
                7 => 'Juillet',
                8 => 'Aout',
                9 => 'Septembre',
                10 => 'Octobre',
                11 => 'Novembre',
                12 => 'Decembre'
             ];

             //initialisation du tableau avec 0 pour chaque mois
             $statsByMonth = array_fill(1, 12, 0);

             foreach ($qb as $movement){
                $monthIndex = (int)$movement->getDate()->format('m');
                $statsByMonth[$monthIndex]++;
             }

             $labels = [];
             $data = [];

             foreach($statsByMonth as $index => $count){
                $labels[]=$months[$index];
                $data[]=$count;
             }

            return [
                'labels' => $labels,
                'data' => $data
            ];
    }
}
