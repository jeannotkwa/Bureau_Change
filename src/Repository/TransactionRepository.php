<?php

namespace App\Repository;

use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findByAgenceAndDateRange(\DateTime $startDate, \DateTime $endDate, int $agenceId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.agence = :agence')
            ->andWhere('t.dateTransaction BETWEEN :start AND :end')
            ->setParameter('agence', $agenceId)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('t.dateTransaction', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentTransactions(int $limit = 10, ?int $agenceId = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($agenceId) {
            $qb->where('t.agence = :agence')
               ->setParameter('agence', $agenceId);
        }

        return $qb->getQuery()->getResult();
    }
}
