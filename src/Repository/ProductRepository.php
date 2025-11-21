<?php

namespace App\Repository;

use App\Entity\Product;
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
        
        $this->applyFilters($qb, $filters, false);
        
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    // fonction applyFilters
    
    private function applyFilters(QueryBuilder $qb, array $filters, bool $isJoinDone = false): void
    {
        // filtre par catégorie
        if(!empty($filters['categoryId'])){
            if(!$isJoinDone){
                $qb->leftJoin('p.category','c', true);
            }
            $qb->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $filters['categoryId']);
        }

        // filtre par nom
            if (!empty($filters['name'])) {
                $qb->andWhere('LOWER(p.name) LIKE LOWER(:productName)')
                    ->setParameter('productName', '%' . $filters['name'] . '%');
            }

            // filtre pour un champ de recherche
            if(!empty($filters['search'])){
                $qb->andWhere('LOWER(p.name) LIKE LOWER(:searchTerm) OR LOWER(p.description) LIKE LOWER(:searchTerm)')
                    ->setParameter('searchTerm', '%'.$filters['search'].'%');
            }

        //filtre par prix minimum
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


    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
