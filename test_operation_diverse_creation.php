<?php
require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "=== TEST CRÉATION OPÉRATION DIVERSE ===\n\n";

// Récupérer les repos
$transactionRepo = $em->getRepository(\App\Entity\Transaction::class);
$fondsRepo = $em->getRepository(\App\Entity\FondsDepart::class);
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

echo "Test 1: Création d'une transaction 'Autre' SANS identite_id\n";

try {
    // Créer une transaction sans identite (comme dans opération diverse)
    $transaction = new \App\Entity\Transaction();
    $transaction->setNatureOperation('Autre');
    $transaction->setReference('TEST-OD-' . time());
    $transaction->setDateTransaction(new \DateTime());
    $transaction->setNom('Client Test');
    $transaction->setTelephone('0612345678');
    $transaction->setAdresse('Motif test');
    $transaction->setUtilisateur($utilisateur);
    $transaction->setAgence($agence);
    // NB: Pas de setIdentite() - NULL est maintenant autorisé
    
    $em->persist($transaction);
    $em->flush();
    
    echo "  ✅ Transaction créée avec succès (sans identite_id)\n";
    echo "  ID: {$transaction->getId()}\n";
    
    echo "\nTest 2: Création de FondsDepart associé\n";
    
    // Créer un FondsDepart pour cette opération
    $fondsDepart = new \App\Entity\FondsDepart();
    $fondsDepart->setAgence($agence);
    $fondsDepart->setDateJour(new \DateTime());
    $fondsDepart->setStatut('ferme');
    $em->persist($fondsDepart);
    
    // Créer un détail de fonds
    $detailFonds = new \App\Entity\DetailsFondsDepart();
    $detailFonds->setFondsDepart($fondsDepart);
    $detailFonds->setDevise($devise);
    $detailFonds->setMontant('-10.00'); // Montant négatif (sortie)
    $detailFonds->setAgence($agence);
    $em->persist($detailFonds);
    
    $fondsDepart->addDetail($detailFonds);
    $em->flush();
    
    echo "  ✅ FondsDepart créé avec DetailsFondsDepart\n";
    echo "  Montant: -10.00 EUR\n";
    
    echo "\n=== RÉSULTAT ===\n";
    echo "✅ Les opérations diverses peuvent maintenant être enregistrées!\n";
    echo "✅ identite_id est maintenant nullable\n";
    echo "✅ FondsDepart et DetailsFondsDepart sont correctement liés\n";
    
} catch (\Exception $e) {
    echo "  ❌ Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
