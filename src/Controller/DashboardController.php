<?php

namespace App\Controller;

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
        $user = $this->getUser();
        $agenceId = $user->getAgence()?->getId();
        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles);

        // Récupérer les transactions récentes
        $recentTransactions = $transactionRepository->findRecentTransactions(10, $isAdmin ? null : $agenceId);

        // Récupérer les soldes des fonds
        if ($isAdmin) {
            // Super admin : voir tous les soldes par devise
            $soldes = $fondsDepartRepository->getSoldesByDevise();
        } else {
            // Utilisateurs réguliers : voir seulement les soldes de leur agence
            $soldes = $fondsDepartRepository->getSoldesByAgence($agenceId);
        }

        // Récupérer toutes les devises actives
        $devises = $deviseRepository->findActiveDevises();

        // Statistiques du jour
        $today = new \DateTime();
        $todayTransactions = $transactionRepository->findByAgenceAndDateRange(
            $today,
            $today,
            $agenceId
        );

        $stats = [
            'transactions_today' => count($todayTransactions),
            'total_achats' => 0,
            'total_ventes' => 0,
        ];

        foreach ($todayTransactions as $transaction) {
            if ($transaction->getNatureOperation() === 'achat') {
                $stats['total_achats'] += $transaction->getMontantTotal();
            } else {
                $stats['total_ventes'] += $transaction->getMontantTotal();
            }
        }

        return $this->render('dashboard/index.html.twig', [
            'recent_transactions' => $recentTransactions,
            'soldes' => $soldes,
            'devises' => $devises,
            'stats' => $stats,
        ]);
    }
}
