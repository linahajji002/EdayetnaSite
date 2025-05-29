<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    //    /**
    //     * @return Produit[] Returns an array of Produit objects
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


    // src/Repository/ProduitRepository.php

    // src/Repository/ProduitRepository.php

    public function findTopPromotedProducts(int $limit = 2): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.id_promotion', 'promo')
            ->where('promo IS NOT NULL')  // Get only products with promotions
            ->orderBy('promo.prix_nouv', 'ASC') // Sort by lowest promo price
            ->setMaxResults($limit) // Limit results to top 2
            ->getQuery()
            ->getResult();
    }




    //    public function findOneBySomeField($value): ?Produit
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
