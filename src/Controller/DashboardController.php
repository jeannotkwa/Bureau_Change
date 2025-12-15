<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\TransactionRepository;
use App\Repository\DetailsFondsDepartRepository;
use App\Repository\DeviseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        TransactionRepository $transactionRepository,
        DetailsFondsDepartRepository $fondsDepartRepository,
        DeviseRepository $deviseRepository
    ): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $agenceId = $user?->getAgence()?->getId();
        $roles = $user?->getRoles() ?? [];
        
        // Déterminer le rôle principal
        $roleTemplate = $this->getUserRoleTemplate($roles);
        $isAdmin = in_array('ROLE_ADMIN', $roles);

        // Récupérer les données base
        $today = new \DateTime();
        
        // Template de données commun
        $viewData = [
            'user_role' => $roleTemplate,
            'is_admin' => $isAdmin,
            'user_agence' => $user?->getAgence(),
            'user_name' => $user?->getNom(),
            'devises' => $deviseRepository->findActiveDevises(),
        ];

        // Données spécifiques au rôle
        if ($isAdmin) {
            $viewData = array_merge($viewData, $this->getAdminDashboardData(
                $transactionRepository,
                $fondsDepartRepository,
                $deviseRepository,
                $today
            ));
        } else {
            $viewData = array_merge($viewData, $this->getAgentDashboardData(
                $transactionRepository,
                $fondsDepartRepository,
                $agenceId,
                $today
            ));
        }

        return $this->render('dashboard/index.html.twig', $viewData);
    }

    /**
     * Déterminer le template à utiliser selon les rôles
     */
    private function getUserRoleTemplate(array $roles): string
    {
        if (in_array('ROLE_ADMIN', $roles)) {
            return 'admin';
        } elseif (in_array('ROLE_CAISSIER', $roles)) {
            return 'caissier';
        } elseif (in_array('ROLE_RESPONSABLE_AGENCE', $roles)) {
            return 'responsable';
        } elseif (in_array('ROLE_AGENT_CHANGE', $roles)) {
            return 'agent';
        } else {
            return 'user';
        }
    }

    /**
     * Données pour le dashboard administrateur (super-admin)
     */
    private function getAdminDashboardData(
        TransactionRepository $transactionRepository,
        DetailsFondsDepartRepository $fondsDepartRepository,
        DeviseRepository $deviseRepository,
        \DateTime $today
    ): array
    {
        // Toutes les transactions du jour
        $todayTransactions = $transactionRepository->findBy(
            ['dateTransaction' => $today],
            ['dateTransaction' => 'DESC', 'id' => 'DESC']
        );

        // Soldes globaux par devise
        $soldes = $fondsDepartRepository->getSoldesByDevise();

        // Statistiques globales
        $totalAchats = 0;
        $totalVentes = 0;
        $achatsParDevise = [];
        $ventesParDevise = [];

        foreach ($todayTransactions as $transaction) {
            $montant = $transaction->getMontantTotal();

            if ($transaction->getNatureOperation() === 'achat') {
                $totalAchats += $montant;
                // Récupérer la devise depuis les détails
                $firstDetail = $transaction->getDetails()->first();
                if ($firstDetail) {
                    $devise = $firstDetail->getDeviseOutput()?->getLibelle() ?? 'Inconnu';
                    $achatsParDevise[$devise] = ($achatsParDevise[$devise] ?? 0) + $montant;
                }
            } else {
                $totalVentes += $montant;
                $firstDetail = $transaction->getDetails()->first();
                if ($firstDetail) {
                    $devise = $firstDetail->getDeviseInput()?->getLibelle() ?? 'Inconnu';
                    $ventesParDevise[$devise] = ($ventesParDevise[$devise] ?? 0) + $montant;
                }
            }
        }

        // Récupérer les agences via les transactions (au lieu d'utiliser EntityManager)
        $agencesFromTransactions = [];
        foreach ($todayTransactions as $transaction) {
            $agence = $transaction->getAgence();
            if ($agence) {
                $agencesFromTransactions[$agence->getId()] = $agence;
            }
        }
        
        $agencesStats = [];
        foreach ($agencesFromTransactions as $agence) {
            $agenceTransactions = $transactionRepository->findBy(
                ['agence' => $agence, 'dateTransaction' => $today],
                ['dateTransaction' => 'DESC']
            );

            $agencesStats[$agence->getNomAgence()] = [
                'count' => count($agenceTransactions),
                'montant' => array_sum(array_map(fn($t) => $t->getMontantTotal(), $agenceTransactions))
            ];
        }

        // Transactions récentes globales
        $recentTransactions = $transactionRepository->findRecentTransactions(15, null);

        return [
            'stats' => [
                'total_transactions' => count($todayTransactions),
                'total_achats' => $totalAchats,
                'total_ventes' => $totalVentes,
                'achats_par_devise' => $achatsParDevise,
                'ventes_par_devise' => $ventesParDevise,
                'agences_stats' => $agencesStats,
            ],
            'soldes' => $soldes,
            'recent_transactions' => $recentTransactions,
            'all_agencies' => $agencesFromTransactions,
        ];
    }

    /**
     * Données pour le dashboard agent/caissier (par agence)
     */
    private function getAgentDashboardData(
        TransactionRepository $transactionRepository,
        DetailsFondsDepartRepository $fondsDepartRepository,
        ?int $agenceId,
        \DateTime $today
    ): array
    {
        // Transactions de l'agence du jour
        if ($agenceId) {
            $todayTransactions = $transactionRepository->findBy(
                ['agence' => $agenceId, 'dateTransaction' => $today],
                ['dateTransaction' => 'DESC', 'id' => 'DESC']
            );

            $soldes = $fondsDepartRepository->getSoldesByAgence($agenceId);
        } else {
            $todayTransactions = [];
            $soldes = [];
        }

        // Statistiques de l'agence
        $totalAchats = 0;
        $totalVentes = 0;
        $achatsParDevise = [];
        $ventesParDevise = [];
        $topAgents = [];

        foreach ($todayTransactions as $transaction) {
            $montant = $transaction->getMontantTotal();
            $agent = $transaction->getUtilisateur()?->getNom() ?? 'Inconnu';

            if ($transaction->getNatureOperation() === 'achat') {
                $totalAchats += $montant;
                $firstDetail = $transaction->getDetails()->first();
                if ($firstDetail) {
                    $devise = $firstDetail->getDeviseOutput()?->getLibelle() ?? 'Inconnu';
                    $achatsParDevise[$devise] = ($achatsParDevise[$devise] ?? 0) + $montant;
                }
            } else {
                $totalVentes += $montant;
                $firstDetail = $transaction->getDetails()->first();
                if ($firstDetail) {
                    $devise = $firstDetail->getDeviseInput()?->getLibelle() ?? 'Inconnu';
                    $ventesParDevise[$devise] = ($ventesParDevise[$devise] ?? 0) + $montant;
                }
            }

            // Compter les transactions par agent
            $topAgents[$agent] = ($topAgents[$agent] ?? 0) + 1;
        }

        // Trier les top agents
        arsort($topAgents);
        $topAgents = array_slice($topAgents, 0, 5, true);

        // Transactions récentes de l'agence
        $recentTransactions = $transactionRepository->findRecentTransactions(10, $agenceId);

        return [
            'stats' => [
                'total_transactions' => count($todayTransactions),
                'total_achats' => $totalAchats,
                'total_ventes' => $totalVentes,
                'achats_par_devise' => $achatsParDevise,
                'ventes_par_devise' => $ventesParDevise,
                'top_agents' => $topAgents,
            ],
            'soldes' => $soldes,
            'recent_transactions' => $recentTransactions,
        ];
    }
}
