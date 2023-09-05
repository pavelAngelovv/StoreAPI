<?php

namespace App\Repository;

use App\Entity\Alcohol;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class AlcoholRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alcohol::class);
    }

    public function findByCriteria(?string $nameFilter, ?string $typeFilter, int $limit, int $offset): Paginator
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

        $paginator = new Paginator($qb);
        $paginator->getQuery()
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $paginator;
    }
}
