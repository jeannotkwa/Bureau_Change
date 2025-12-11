<?php

namespace App\Repository;

use App\Entity\FondsDepart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FondsDepart>
 */
class FondsDepartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FondsDepart::class);
    }

    public function findLatestByAgence(int $agenceId): ?FondsDepart
    {
        return $this->createQueryBuilder('f')
            ->where('f.agence = :agence')
            ->setParameter('agence', $agenceId)
            ->orderBy('f.dateJour', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByAgenceAndDate(int $agenceId, \DateTime $date): ?FondsDepart
    {
        return $this->createQueryBuilder('f')
            ->where('f.agence = :agence')
            ->andWhere('f.dateJour = :date')
            ->setParameter('agence', $agenceId)
            ->setParameter('date', $date->format('Y-m-d'))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
