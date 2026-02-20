<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\StockMovement;
use App\Enum\StockMovementType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findProductsByCriteria(int $page, int $limit, array $filters = []):array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p','c')
            ->leftJoin('p.category','c')
            ->orderBy('p.id','ASC');

            // application de toutes les filtres
            $this->applyFilters($qb, $filters, true);

            // pagination
            $offset = max(0, ($page - 1) * $limit);
            $limit = max(1, $limit);

            $qb->setMaxResults($limit)
                ->setFirstResult($offset);
                
            return $qb->getQuery()->getResult();

    }

    public function countProductsByCriteria(array $filters = []):int
    {
        $qb = $this->createQueryBuilder('p')
                    ->select('COUNT(p.id)');

        // application des filtres
        
        $this->applyFilters($qb, $filters);
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    // fonction applyFilters
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        // filtre par catégorie
        if(!empty($filters['categoryId'])){
            if(!in_array('c', $qb->getAllAliases(), true)){
            $qb->leftJoin('p.category','c');
            }

            $qb->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $filters['categoryId']);
        }

        // filtre par nom
            if (!empty($filters['name'])) {
                $qb->andWhere('LOWER(p.name) LIKE LOWER(:productName)')
                    ->setParameter('productName', '%' . $filters['name'] . '%');
            }

            // filtre pour un champ de recherche global
            if(!empty($filters['search'])){
                $qb->andWhere('LOWER(p.name) LIKE LOWER(:searchTerm) OR LOWER(p.description) LIKE LOWER(:searchTerm)')
                    ->setParameter('searchTerm', '%'.$filters['search'].'%');
            }

        //filtre par prix minimum/maximum
        if (isset($filters['minPrice']) && is_numeric($filters['minPrice']))
        {
            $qb->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice',$filters['minPrice']);
        }
        if(isset($filters['maxPrice']) && is_numeric($filters['maxPrice']))
        {
            $qb->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', $filters['maxPrice']);
        }
    }

    public function findAllWithStock():array
    {
        $stockExpr = $this->getEntityManager()
                            ->getRepository(StockMovement::class)
                            ->getStockExpression('sm');

        $qb = $this->createQueryBuilder('prod')
            ->leftJoin(
                StockMovement::class,
                'sm',
                'WITH',
                'sm.product = prod'
            )
            ->select("prod.id, prod.name, $stockExpr as stock")
                ->groupBy('prod.id')
                ->setParameter('inType', StockMovementType::IN)
                ->setParameter('outType', StockMovementType::OUT)
                ->setParameter('adjType', StockMovementType::ADJUSTMENT)
                ->getQuery();

                return $qb->getArrayResult();
    }

    public function findOutOfStock():array
    {
        $stockExpr = $this->getEntityManager()
                            ->getRepository(StockMovement::class)
                            ->getStockExpression('sm');

        $qb = $this->createQueryBuilder('prod')
            ->leftJoin(
                StockMovement::class,
                'sm',
                'WITH',
                'sm.product = prod'
            )
            ->select("prod.id, prod.name, $stockExpr AS stock")
                ->groupBy('prod.id')
                ->having("$stockExpr <= 0")
                ->setParameter('inType', StockMovementType::IN)
                ->setParameter('outType', StockMovementType::OUT)
                ->setParameter('adjType', StockMovementType::ADJUSTMENT)
                ->getQuery();

                return $qb->getArrayResult();
    }


    //Methode pour récupérer le nombre de out of stock
    public function CountOutOfStock():int
    {
        $stockExpr = $this->getEntityManager()
                            ->getRepository(StockMovement::class)
                            ->getStockExpression('sm');

        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->leftJoin(StockMovement::class, 'sm', 'WITH', 'sm.product = p')
            ->groupBy('p.id')
            ->having("$stockExpr <= 0")
            ->setParameter('inType', StockMovementType::IN)
            ->setParameter('outType', StockMovementType::OUT)
            ->setParameter('adjType', StockMovementType::ADJUSTMENT);

            return (int) $qb->getQuery()->getSingleScalarResult();
            }

    public function findLowStock():array
    {

        $stockExpr = $this->getEntityManager()
                    ->getRepository(StockMovement::class)
                    ->getStockExpression('sm'); 

        $qb = $this->createQueryBuilder('p')
            ->leftJoin(StockMovement::class,'sm','WITH','sm.product = p')
            ->select('p.id, p.name, p.lowStockThreshold')
            ->addSelect("$stockExpr AS stock")
            ->groupBy('p.id')
            ->having("$stockExpr <= p.lowStockThreshold")
            ->setParameter('inType', StockMovementType::IN)
            ->setParameter('outType', StockMovementType::OUT)
            ->setParameter('adjType', StockMovementType::ADJUSTMENT)
            ->getQuery();

            return $qb->getArrayResult();
    }
}
