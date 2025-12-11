<?php

namespace App\Repository;

use App\Entity\AffectationAgent;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AffectationAgent>
 *
 * @method AffectationAgent|null find($id, $lockMode = null, $lockVersion = null)
 * @method AffectationAgent|null findOneBy(array $criteria, array $orderBy = null)
 * @method AffectationAgent[]    findAll()
 * @method AffectationAgent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffectationAgentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffectationAgent::class);
    }

    /**
     * Récupère l'affectation actuelle d'un agent
     */
    public function getAffectationActuelle(Utilisateur $utilisateur): ?AffectationAgent
    {
        return $this->createQueryBuilder('a')
            ->where('a.utilisateur = :utilisateur')
            ->andWhere('a.statut = :statut')
            ->andWhere('a.dateFin IS NULL')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('statut', 'actif')
            ->orderBy('a.dateDebut', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère l'historique complet des affectations d'un agent
     */
    public function getHistoriqueAffectations(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('a.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les agents actuellement affectés à une agence
     */
    public function getAgentsParAgence($agence): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.utilisateur', 'u')
            ->where('a.agence = :agence')
            ->andWhere('a.statut = :statut')
            ->andWhere('a.dateFin IS NULL')
            ->setParameter('agence', $agence)
            ->setParameter('statut', 'actif')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère l'historique des affectations d'une agence
     */
    public function getHistoriqueAffectationsAgence($agence): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.utilisateur', 'u')
            ->where('a.agence = :agence')
            ->setParameter('agence', $agence)
            ->orderBy('a.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
