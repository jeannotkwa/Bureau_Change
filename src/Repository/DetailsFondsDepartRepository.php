<?php

namespace App\Repository;

use App\Entity\DetailsFondsDepart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DetailsFondsDepart>
 */
class DetailsFondsDepartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DetailsFondsDepart::class);
    }

    public function getSoldeByAgenceAndDevise(int $agenceId, int $deviseId): float
    {
        $result = $this->createQueryBuilder('d')
            ->select('COALESCE(SUM(d.montant), 0) as solde')
            ->where('d.agence = :agence')
            ->andWhere('d.devise = :devise')
            ->setParameter('agence', $agenceId)
            ->setParameter('devise', $deviseId)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    public function getSoldesByAgence(int $agenceId): array
    {
        return $this->createQueryBuilder('d')
            ->select('dev.id', 'dev.libelle', 'dev.sigle', 'dev.tauxAchat', 'dev.tauxVente', 'COALESCE(SUM(d.montant), 0) as montant')
            ->leftJoin('d.devise', 'dev')
            ->where('d.agence = :agence')
            ->setParameter('agence', $agenceId)
            ->groupBy('dev.id')
            ->orderBy('dev.sigle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getSoldesByDevise(): array
    {
        $results = $this->createQueryBuilder('d')
            ->select('dev.id', 'dev.libelle', 'dev.sigle', 'dev.tauxAchat', 'dev.tauxVente', 'COALESCE(SUM(d.montant), 0) as solde')
            ->leftJoin('d.devise', 'dev')
            ->groupBy('dev.id')
            ->orderBy('dev.sigle', 'ASC')
            ->getQuery()
            ->getResult();
        
        // Transform results to be compatible with template
        $soldes = [];
        foreach ($results as $result) {
            $soldes[] = [
                'devise' => [
                    'id' => $result['id'],
                    'libelle' => $result['libelle'],
                    'sigle' => $result['sigle'],
                    'tauxAchat' => $result['tauxAchat'],
                    'tauxVente' => $result['tauxVente'],
                ],
                'solde' => floatval($result['solde']),
            ];
        }
        return $soldes;
    }
}
