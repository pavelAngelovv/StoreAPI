<?php

namespace App\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Alcohol;

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

    public function countByCriteria(?string $nameFilter, ?string $typeFilter): int
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
