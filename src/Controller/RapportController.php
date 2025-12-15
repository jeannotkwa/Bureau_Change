<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\TransactionRepository;
use App\Repository\DetailsFondsDepartRepository;
use App\Repository\DeviseRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rapports')]
class RapportController extends AbstractController
{
    private TransactionRepository $transactionRepository;
    private DetailsFondsDepartRepository $fondsDepartRepository;
    private DeviseRepository $deviseRepository;

    public function __construct(
        TransactionRepository $transactionRepository,
        DetailsFondsDepartRepository $fondsDepartRepository,
        DeviseRepository $deviseRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->fondsDepartRepository = $fondsDepartRepository;
        $this->deviseRepository = $deviseRepository;
    }

    #[Route('/', name: 'app_rapport_index')]
    public function index(
        Request $request
    ): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $roles = $user->getRoles();
        
        // Déterminer le rôle principal pour le routage
        $roleTemplate = $this->getUserRoleTemplate($roles);
        $isAdmin = in_array('ROLE_ADMIN', $roles);
        $isResponsable = in_array('ROLE_RESPONSABLE_AGENCE', $roles);
        $agenceId = $user->getAgence()?->getId();
        
        // Récupération des filtres
        $dateDebut = $request->query->get('date_debut', date('Y-m-01')); // Début du mois
        $dateFin = $request->query->get('date_fin', date('Y-m-d')); // Aujourd'hui
        $deviseFilter = $request->query->get('devise');
        $agenceFilter = $request->query->get('agence');
        
        // Préparer les données selon le rôle
        $viewData = [
            'user_role' => $roleTemplate,
            'is_admin' => $isAdmin,
            'is_responsable' => $isResponsable,
            'user_agence' => $user->getAgence(),
            'user_name' => $user->getNom(),
            'devises' => $this->deviseRepository->findActiveDevises(),
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'devise_filter' => $deviseFilter,
            'agence_filter' => $agenceFilter,
        ];
        
        // Données spécifiques au rôle
        if ($isAdmin) {
            $viewData = array_merge($viewData, $this->getAdminReportData(
                $this->transactionRepository,
                $this->fondsDepartRepository,
                $dateDebut,
                $dateFin,
                $deviseFilter,
                $agenceFilter
            ));
        } elseif ($isResponsable) {
            $viewData = array_merge($viewData, $this->getResponsableReportData(
                $this->transactionRepository,
                $this->fondsDepartRepository,
                $agenceId,
                $dateDebut,
                $dateFin,
                $deviseFilter
            ));
        } else {
            $viewData = array_merge($viewData, $this->getAgentReportData(
                $this->transactionRepository,
                $this->fondsDepartRepository,
                $agenceId,
                $dateDebut,
                $dateFin,
                $deviseFilter
            ));
        }
        
        return $this->render('rapport/index.html.twig', $viewData);
    }
    
    /**
     * Export PDF du rapport selon le rôle
     */
    #[Route('/export-pdf', name: 'app_rapport_export_pdf')]
    public function exportPdf(
        Request $request
    ): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $roles = $user->getRoles();
        $roleTemplate = $this->getUserRoleTemplate($roles);
        $isAdmin = in_array('ROLE_ADMIN', $roles);
        $isResponsable = in_array('ROLE_RESPONSABLE_AGENCE', $roles);
        $agenceId = $user->getAgence()?->getId();
        
        // Récupération des filtres
        $dateDebut = $request->query->get('date_debut', date('Y-m-01'));
        $dateFin = $request->query->get('date_fin', date('Y-m-d'));
        $deviseFilter = $request->query->get('devise');
        $agenceFilter = $request->query->get('agence');
        
        // Préparer les données selon le rôle
        $viewData = [
            'user_role' => $roleTemplate,
            'is_admin' => $isAdmin,
            'is_responsable' => $isResponsable,
            'user_agence' => $user->getAgence(),
            'user_name' => $user->getNom(),
            'devises' => $this->deviseRepository->findActiveDevises(),
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'devise_filter' => $deviseFilter,
            'agence_filter' => $agenceFilter,
        ];
        
        // Données spécifiques au rôle
        if ($isAdmin) {
            $viewData = array_merge($viewData, $this->getAdminReportData(
                $this->transactionRepository,
                $this->fondsDepartRepository,
                $dateDebut,
                $dateFin,
                $deviseFilter,
                $agenceFilter
            ));
        } elseif ($isResponsable) {
            $viewData = array_merge($viewData, $this->getResponsableReportData(
                $this->transactionRepository,
                $this->fondsDepartRepository,
                $agenceId,
                $dateDebut,
                $dateFin,
                $deviseFilter
            ));
        } else {
            $viewData = array_merge($viewData, $this->getAgentReportData(
                $this->transactionRepository,
                $this->fondsDepartRepository,
                $agenceId,
                $dateDebut,
                $dateFin,
                $deviseFilter
            ));
        }
        
        // Générer le HTML du rapport
        $html = $this->renderView('rapport/pdf_template.html.twig', $viewData);
        
        // Configuration DOMPDF
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($pdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Nom du fichier
        $filename = sprintf(
            'rapport_%s_%s_%s.pdf',
            $roleTemplate,
            date('Y-m-d'),
            time()
        );
        
        // Retourner le PDF
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            ]
        );
    }
    
    /**
     * Détermine le template selon le rôle de l'utilisateur
     */
    private function getUserRoleTemplate(array $roles): string
    {
        if (in_array('ROLE_ADMIN', $roles)) return 'admin';
        if (in_array('ROLE_RESPONSABLE_AGENCE', $roles)) return 'responsable';
        if (in_array('ROLE_AGENT_CHANGE', $roles)) return 'agent';
        if (in_array('ROLE_CAISSIER', $roles)) return 'caissier';
        return 'user';
    }
    
    /**
     * Données de rapport pour les administrateurs (vue globale)
     */
    private function getAdminReportData(
        TransactionRepository $transactionRepository,
        DetailsFondsDepartRepository $fondsDepartRepository,
        string $dateDebut,
        string $dateFin,
        ?string $deviseFilter,
        ?string $agenceFilter
    ): array
    {
        $debut = new \DateTime($dateDebut);
        $fin = new \DateTime($dateFin);
        
        // Transactions sur la période avec filtres optionnels
        $criteria = [
            'dateTransaction >= ' => $debut,
            'dateTransaction <= ' => $fin,
        ];
        
        $transactions = $transactionRepository->createQueryBuilder('t')
            ->where('t.dateTransaction >= :debut')
            ->andWhere('t.dateTransaction <= :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('t.dateTransaction', 'DESC')
            ->getQuery()
            ->getResult();
        
        // Filtrer par agence si spécifié
        if ($agenceFilter) {
            $transactions = array_filter($transactions, fn($t) => 
                $t->getAgence() && $t->getAgence()->getId() == $agenceFilter
            );
        }
        
        // Statistiques globales
        $totalAchats = 0;
        $totalVentes = 0;
        $achatsParDevise = [];
        $ventesParDevise = [];
        $transactionsParAgence = [];
        $achatsParJour = [];
        $ventesParJour = [];
        
        foreach ($transactions as $transaction) {
            $montant = $transaction->getMontantTotal();
            $dateKey = $transaction->getDateTransaction()->format('Y-m-d');
            $agenceNom = $transaction->getAgence() ? $transaction->getAgence()->getNomAgence() : 'Sans agence';
            
            // Initialiser le compteur pour cette agence
            if (!isset($transactionsParAgence[$agenceNom])) {
                $transactionsParAgence[$agenceNom] = ['achats' => 0, 'ventes' => 0, 'count' => 0];
            }
            $transactionsParAgence[$agenceNom]['count']++;
            
            if ($transaction->getNatureOperation() === 'achat') {
                $totalAchats += $montant;
                $transactionsParAgence[$agenceNom]['achats'] += $montant;
                $achatsParJour[$dateKey] = ($achatsParJour[$dateKey] ?? 0) + $montant;
                
                // Devise
                $firstDetail = $transaction->getDetails()->first();
                if ($firstDetail && $firstDetail->getDeviseOutput()) {
                    $devise = $firstDetail->getDeviseOutput()->getLibelle();
                    $achatsParDevise[$devise] = ($achatsParDevise[$devise] ?? 0) + $montant;
                }
            } else {
                $totalVentes += $montant;
                $transactionsParAgence[$agenceNom]['ventes'] += $montant;
                $ventesParJour[$dateKey] = ($ventesParJour[$dateKey] ?? 0) + $montant;
                
                // Devise
                $firstDetail = $transaction->getDetails()->first();
                if ($firstDetail && $firstDetail->getDeviseInput()) {
                    $devise = $firstDetail->getDeviseInput()->getLibelle();
                    $ventesParDevise[$devise] = ($ventesParDevise[$devise] ?? 0) + $montant;
                }
            }
        }
        
        // Soldes globaux par devise
        $soldes = $fondsDepartRepository->getSoldesByDevise();
        
        return [
            'stats' => [
                'total_transactions' => count($transactions),
                'total_achats' => $totalAchats,
                'total_ventes' => $totalVentes,
                'marge_brute' => $totalVentes - $totalAchats,
                'achats_par_devise' => $achatsParDevise,
                'ventes_par_devise' => $ventesParDevise,
                'transactions_par_agence' => $transactionsParAgence,
                'achats_par_jour' => $achatsParJour,
                'ventes_par_jour' => $ventesParJour,
            ],
            'transactions' => $transactions,
            'soldes' => $soldes,
        ];
    }
    
    /**
     * Données de rapport pour les responsables d'agence
     */
    private function getResponsableReportData(
        TransactionRepository $transactionRepository,
        DetailsFondsDepartRepository $fondsDepartRepository,
        ?int $agenceId,
        string $dateDebut,
        string $dateFin,
        ?string $deviseFilter
    ): array
    {
        if (!$agenceId) {
            return ['stats' => [], 'transactions' => [], 'soldes' => []];
        }
        
        $debut = new \DateTime($dateDebut);
        $fin = new \DateTime($dateFin);
        
        // Transactions de l'agence sur la période
        $transactions = $transactionRepository->createQueryBuilder('t')
            ->where('t.agence = :agence')
            ->andWhere('t.dateTransaction >= :debut')
            ->andWhere('t.dateTransaction <= :fin')
            ->setParameter('agence', $agenceId)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('t.dateTransaction', 'DESC')
            ->getQuery()
            ->getResult();
        
        // Statistiques par agent
        $statsParAgent = [];
        $totalAchats = 0;
        $totalVentes = 0;
        $achatsParDevise = [];
        $ventesParDevise = [];
        
        foreach ($transactions as $transaction) {
            $montant = $transaction->getMontantTotal();
            $agent = $transaction->getUtilisateur();
            $agentNom = $agent ? $agent->getNom() : 'Non défini';
            
            if (!isset($statsParAgent[$agentNom])) {
                $statsParAgent[$agentNom] = [
                    'achats' => 0,
                    'ventes' => 0,
                    'count' => 0,
                    'agent' => $agent
                ];
            }
            
            $statsParAgent[$agentNom]['count']++;
            
            if ($transaction->getNatureOperation() === 'achat') {
                $totalAchats += $montant;
                $statsParAgent[$agentNom]['achats'] += $montant;
                
                $firstDetail = $transaction->getDetails()->first();
                if ($firstDetail && $firstDetail->getDeviseOutput()) {
                    $devise = $firstDetail->getDeviseOutput()->getLibelle();
                    $achatsParDevise[$devise] = ($achatsParDevise[$devise] ?? 0) + $montant;
                }
            } else {
                $totalVentes += $montant;
                $statsParAgent[$agentNom]['ventes'] += $montant;
                
                $firstDetail = $transaction->getDetails()->first();
                if ($firstDetail && $firstDetail->getDeviseInput()) {
                    $devise = $firstDetail->getDeviseInput()->getLibelle();
                    $ventesParDevise[$devise] = ($ventesParDevise[$devise] ?? 0) + $montant;
                }
            }
        }
        
        // Trier les agents par performance (total achats + ventes)
        uasort($statsParAgent, function($a, $b) {
            return ($b['achats'] + $b['ventes']) <=> ($a['achats'] + $a['ventes']);
        });
        
        // Soldes de l'agence par devise
        $soldes = $fondsDepartRepository->getSoldesByAgence($agenceId);
        
        return [
            'stats' => [
                'total_transactions' => count($transactions),
                'total_achats' => $totalAchats,
                'total_ventes' => $totalVentes,
                'marge_brute' => $totalVentes - $totalAchats,
                'achats_par_devise' => $achatsParDevise,
                'ventes_par_devise' => $ventesParDevise,
                'stats_par_agent' => $statsParAgent,
            ],
            'transactions' => $transactions,
            'soldes' => $soldes,
        ];
    }
    
    /**
     * Données de rapport pour les agents/caissiers
     */
    private function getAgentReportData(
        TransactionRepository $transactionRepository,
        DetailsFondsDepartRepository $fondsDepartRepository,
        ?int $agenceId,
        string $dateDebut,
        string $dateFin,
        ?string $deviseFilter
    ): array
    {
        if (!$agenceId) {
            return ['stats' => [], 'transactions' => [], 'soldes' => []];
        }
        
        $debut = new \DateTime($dateDebut);
        $fin = new \DateTime($dateFin);
        
        // Transactions de l'agence (vue simplifiée pour agents)
        $transactions = $transactionRepository->createQueryBuilder('t')
            ->where('t.agence = :agence')
            ->andWhere('t.dateTransaction >= :debut')
            ->andWhere('t.dateTransaction <= :fin')
            ->setParameter('agence', $agenceId)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('t.dateTransaction', 'DESC')
            ->setMaxResults(50) // Limiter pour les agents
            ->getQuery()
            ->getResult();
        
        // Statistiques simples
        $totalAchats = 0;
        $totalVentes = 0;
        $achatsParDevise = [];
        $ventesParDevise = [];
        
        foreach ($transactions as $transaction) {
            $montant = $transaction->getMontantTotal();
            
            if ($transaction->getNatureOperation() === 'achat') {
                $totalAchats += $montant;
                
                $firstDetail = $transaction->getDetails()->first();
                if ($firstDetail && $firstDetail->getDeviseOutput()) {
                    $devise = $firstDetail->getDeviseOutput()->getLibelle();
                    $achatsParDevise[$devise] = ($achatsParDevise[$devise] ?? 0) + $montant;
                }
            } else {
                $totalVentes += $montant;
                
                $firstDetail = $transaction->getDetails()->first();
                if ($firstDetail && $firstDetail->getDeviseInput()) {
                    $devise = $firstDetail->getDeviseInput()->getLibelle();
                    $ventesParDevise[$devise] = ($ventesParDevise[$devise] ?? 0) + $montant;
                }
            }
        }
        
        // Soldes de l'agence
        $soldes = $fondsDepartRepository->getSoldesByAgence($agenceId);
        
        return [
            'stats' => [
                'total_transactions' => count($transactions),
                'total_achats' => $totalAchats,
                'total_ventes' => $totalVentes,
                'achats_par_devise' => $achatsParDevise,
                'ventes_par_devise' => $ventesParDevise,
            ],
            'transactions' => $transactions,
            'soldes' => $soldes,
        ];
    }
}
