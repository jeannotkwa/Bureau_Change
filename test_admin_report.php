<?php
// Test script pour vérifier le AdminReportController
require_once __DIR__ . '/config/bootstrap.php';

use App\Repository\AgenceRepository;
use App\Repository\TransactionRepository;
use App\Repository\DetailsFondsDepartRepository;

// Récupérer les services depuis le container
$container = require __DIR__ . '/config/bootstrap.php';
$em = $container->get('doctrine.orm.default_entity_manager');

$agenceRepo = $em->getRepository('App\Entity\Agence');
$transactionRepo = $em->getRepository('App\Entity\Transaction');
$fondsRepo = $em->getRepository('App\Entity\DetailsFondsDepart');

// Récupérer toutes les agences
$agences = $agenceRepo->findBy([], ['nomAgence' => 'ASC']);

echo "=== TEST ADMIN REPORT CONTROLLER ===\n\n";
echo "Nombre d'agences: " . count($agences) . "\n\n";

$agencesData = [];

foreach ($agences as $agence) {
    $agenceId = $agence->getId();
    
    // Récupérer les transactions
    $transactions = $transactionRepo->findBy(
        ['agence' => $agenceId],
        ['dateTransaction' => 'DESC', 'id' => 'DESC']
    );
    
    // Récupérer les fonds
    $fonds = $fondsRepo->findBy(
        ['agence' => $agenceId],
        ['dateJour' => 'DESC', 'id' => 'DESC']
    );
    
    // Soldes
    $soldes = $fondsRepo->getSoldesByAgence($agenceId);
    
    // Statistiques
    $totalAchats = array_reduce($transactions, function($sum, $t) {
        return $sum + ($t->getNatureOperation() === 'achat' ? $t->getMontantTotal() : 0);
    }, 0);
    
    $totalVentes = array_reduce($transactions, function($sum, $t) {
        return $sum + ($t->getNatureOperation() === 'vente' ? $t->getMontantTotal() : 0);
    }, 0);
    
    echo "--- AGENCE: " . $agence->getNomAgence() . " ---\n";
    echo "  Transactions: " . count($transactions) . "\n";
    echo "  Total Achats: " . $totalAchats . " CDF\n";
    echo "  Total Ventes: " . $totalVentes . " CDF\n";
    echo "  Mouvements Fonds: " . count($fonds) . "\n";
    echo "  Soldes de Devises: " . count($soldes) . "\n";
    
    if (count($soldes) > 0) {
        echo "  Détail des soldes:\n";
        foreach (array_slice($soldes, 0, 3) as $solde) {
            echo "    - " . $solde->getDevise()->getSigle() . ": " . $solde->getMontant() . "\n";
        }
    }
    echo "\n";
}

echo "=== TEST TERMINÉ ===\n";
?>
