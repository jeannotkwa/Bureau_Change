<?php

namespace App\Controller;

use App\Repository\AgenceRepository;
use App\Repository\TransactionRepository;
use App\Repository\DetailsFondsDepartRepository;
use App\Repository\FondsDepartRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin-report')]
class AdminReportController extends AbstractController
{
    #[Route('/agencies-overview', name: 'app_admin_agencies_overview')]
    public function agenciesOverview(
        AgenceRepository $agenceRepository,
        TransactionRepository $transactionRepository,
        DetailsFondsDepartRepository $detailsFondsRepository,
        FondsDepartRepository $fondsRepository
    ): Response
    {
        // Vérifier que l'utilisateur est super admin
        $user = $this->getUser();
        $roles = $user->getRoles();
        
        if (!in_array('ROLE_ADMIN', $roles)) {
            throw $this->createAccessDeniedException('Accès refusé: réservé au super administrateur');
        }

        // Récupérer toutes les agences
        $agences = $agenceRepository->findBy([], ['nomAgence' => 'ASC']);

        // Préparer les données pour chaque agence
        $agencesData = [];
        
        foreach ($agences as $agence) {
            $agenceId = $agence->getId();
            
            // Récupérer les transactions de l'agence
            $transactions = $transactionRepository->findBy(
                ['agence' => $agenceId],
                ['dateTransaction' => 'DESC', 'id' => 'DESC']
            );
            
            // Récupérer les mouvements de fonds de l'agence (table FondsDepart)
            $fonds = $fondsRepository->findBy(
                ['agence' => $agenceId],
                ['dateJour' => 'DESC', 'id' => 'DESC']
            );
            
            // Calculer les soldes par devise pour cette agence (DetailsFondsDepart)
            $soldesAgence = $detailsFondsRepository->getSoldesByAgence($agenceId);
            
            // Statistiques
            $totalAchats = array_reduce($transactions, function($sum, $t) {
                return $sum + ($t->getNatureOperation() === 'achat' ? $t->getMontantTotal() : 0);
            }, 0);
            
            $totalVentes = array_reduce($transactions, function($sum, $t) {
                return $sum + ($t->getNatureOperation() === 'vente' ? $t->getMontantTotal() : 0);
            }, 0);
            
            $agencesData[] = [
                'agence' => $agence,
                'transactions' => array_slice($transactions, 0, 5), // 5 dernières
                'transactions_count' => count($transactions),
                'fonds' => array_slice($fonds, 0, 5), // 5 derniers
                'fonds_count' => count($fonds),
                'soldes' => $soldesAgence,
                'stats' => [
                    'total_achats' => $totalAchats,
                    'total_ventes' => $totalVentes,
                    'total_transactions' => count($transactions),
                ]
            ];
        }

        return $this->render('admin_report/agencies_overview.html.twig', [
            'agences_data' => $agencesData,
        ]);
    }
}
