<?php

namespace App\Repository;

use App\Entity\Atelierenligne;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Atelierenligne>
 */
class AtelierenligneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Atelierenligne::class);
    }

    
    public function searchByTerm(string $term)
    {
        return $this->createQueryBuilder('m')
            ->where('m.titre LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->getQuery()
            ->getResult();
    }
}
