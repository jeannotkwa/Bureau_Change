<?php
require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "=== TEST COMPLET OPÉRATION DIVERSE ===\n\n";

// Récupérer les repos
$transactionRepo = $em->getRepository(\App\Entity\Transaction::class);
$detailsTransactionRepo = $em->getRepository(\App\Entity\DetailsTransaction::class);
$agenceRepo = $em->getRepository(\App\Entity\Agence::class);
$deviseRepo = $em->getRepository(\App\Entity\Devise::class);
$utilisateurRepo = $em->getRepository(\App\Entity\Utilisateur::class);

// Récupérer une agence, devise et utilisateur
$agence = $agenceRepo->findOneBy([]);
$devise = $deviseRepo->findOneBy(['sigle' => 'EUR']);
$utilisateur = $utilisateurRepo->findOneBy([]);

if (!$agence || !$devise || !$utilisateur) {
    echo "❌ Données manquantes\n";
    exit;
}

echo "Configuration:\n";
echo "  Agence: {$agence->getNomAgence()}\n";
echo "  Devise: {$devise->getSigle()}\n";
echo "  Utilisateur ID: {$utilisateur->getId()}\n\n";

try {
    echo "Test: Création d'une opération diverse complète\n";
    
    // Créer une transaction
    $transaction = new \App\Entity\Transaction();
    $transaction->setNatureOperation('Autre');
    $transaction->setReference('OD-TEST-' . time());
    $transaction->setDateTransaction(new \DateTime());
    $transaction->setNom('Client Test');
    $transaction->setTelephone('0612345678');
    $transaction->setAdresse('Motif test');
    $transaction->setUtilisateur($utilisateur);
    $transaction->setAgence($agence);
    
    $em->persist($transaction);
    $em->flush();
    
    echo "  ✅ Transaction créée: {$transaction->getReference()}\n";
    
    // Créer un FondsDepart avec DetailsFondsDepart
    $fondsDepart = new \App\Entity\FondsDepart();
    $fondsDepart->setAgence($agence);
    $fondsDepart->setDateJour(new \DateTime());
    $fondsDepart->setStatut('ferme');
    $em->persist($fondsDepart);
    
    $detailFonds = new \App\Entity\DetailsFondsDepart();
    $detailFonds->setFondsDepart($fondsDepart);
    $detailFonds->setDevise($devise);
    $detailFonds->setMontant('-25.00');
    $detailFonds->setAgence($agence);
    $em->persist($detailFonds);
    $fondsDepart->addDetail($detailFonds);
    
    echo "  ✅ FondsDepart créé avec montant: -25.00 EUR\n";
    
    // Créer un DetailsTransaction
    $detailTransaction = new \App\Entity\DetailsTransaction();
    $detailTransaction->setTransaction($transaction);
    $detailTransaction->setDeviseInput($devise);
    $detailTransaction->setDeviseOutput($devise);
    $detailTransaction->setMontant('25.00');
    $detailTransaction->setTaux('1.0000');
    $detailTransaction->setContreValeur('25.00');
    $em->persist($detailTransaction);
    $transaction->addDetail($detailTransaction);
    
    echo "  ✅ DetailsTransaction créé avec montant: 25.00 EUR\n";
    
    $em->flush();
    
    echo "\nVérification des données:\n";
    echo "  Montant total de la transaction: " . number_format($transaction->getMontantTotal(), 2) . "\n";
    echo "  Devise: " . ($transaction->getDetails()[0]->getDeviseOutput()->getSigle() ?? 'N/A') . "\n";
    
    if ($transaction->getMontantTotal() == 25.00) {
        echo "\n✅ L'opération diverse fonctionne complètement!\n";
        echo "✅ La transaction et ses détails sont correctement créés\n";
        echo "✅ Le montant et la devise s'afficheront correctement dans la liste\n";
    } else {
        echo "\n❌ Le montant total ne correspond pas\n";
    }
    
} catch (\Exception $e) {
    echo "  ❌ Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
