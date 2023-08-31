<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Alcohol;

class AlcoholRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alcohol::class);
    }

    public function findByCriteria($nameFilter, $typeFilter, $limit, $offset)
    {
        $qb = $this->createQueryBuilder('a');

        if ($nameFilter) {
            $qb->andWhere('LOWER(a.name) LIKE :name')
               ->setParameter('name', '%' . strtolower($nameFilter) . '%');
        }

        if ($typeFilter) {
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $typeFilter);
        }

        return $qb->setMaxResults($limit)
                  ->setFirstResult($offset)
                  ->getQuery()
                  ->getResult();
    }

    public function countByCriteria($nameFilter, $typeFilter)
    {
        $qb = $this->createQueryBuilder('a')
                   ->select('COUNT(a.id)');

        if ($nameFilter) {
            $qb->andWhere('LOWER(a.name) LIKE :name')
               ->setParameter('name', '%' . strtolower($nameFilter) . '%');
        }

        if ($typeFilter) {
            $qb->andWhere('a.type = :type')
               ->setParameter('type', $typeFilter);
        }

        return $qb->getQuery()
                  ->getSingleScalarResult();
    }
}
