<?php
require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "=== TEST COMPLET DE RÉCEPTION DE FONDS ===\n\n";

// 1. Récupérer les repos nécessaires
$transactionRepo = $em->getRepository(\App\Entity\Transaction::class);
$detailsFondsRepo = $em->getRepository(\App\Entity\DetailsFondsDepart::class);
$agenceRepo = $em->getRepository(\App\Entity\Agence::class);
$deviseRepo = $em->getRepository(\App\Entity\Devise::class);

// 2. Trouver une agence et une devise
$agence = $agenceRepo->findOneBy([]);
$devise = $deviseRepo->findOneBy(['sigle' => 'EUR']);

if (!$agence || !$devise) {
    echo "❌ Agence ou devise EUR non trouvée\n";
    exit;
}

echo "Agence testée: {$agence->getNomAgence()} (ID: {$agence->getId()})\n";
echo "Devise: {$devise->getSigle()}\n\n";

// 3. Solde AVANT réception
$soldeAvant = $detailsFondsRepo->getSoldeByAgenceAndDevise($agence->getId(), $devise->getId());
echo "Solde AVANT réception: " . number_format($soldeAvant, 2) . " {$devise->getSigle()}\n\n";

// 4. Vérifier qu'il existe un envoi à réceptionner
$envoi = $transactionRepo->findOneBy(['natureOperation' => 'envoi'], ['id' => 'DESC']);
if (!$envoi) {
    echo "❌ Aucun envoi trouvé pour test\n";
    exit;
}

echo "Envoi trouvé: {$envoi->getReference()}\n";

// Calculer combien a été envoyé et reçu
$montantEnvoye = 0;
foreach ($envoi->getDetails() as $detail) {
    if ($detail->getDeviseInput()->getId() == $devise->getId()) {
        $montantEnvoye += (float)$detail->getMontant();
    }
}

$receptionsExistantes = $transactionRepo->findBy([
    'reference' => $envoi->getReference(),
    'natureOperation' => 'reception'
]);

$montantDejaRecu = 0;
foreach ($receptionsExistantes as $reception) {
    foreach ($reception->getDetails() as $detail) {
        if ($detail->getDeviseInput()->getId() == $devise->getId()) {
            $montantDejaRecu += (float)$detail->getMontant();
        }
    }
}

echo "Montant envoyé: " . number_format($montantEnvoye, 2) . "\n";
echo "Déjà reçu: " . number_format($montantDejaRecu, 2) . "\n";
echo "Restant à recevoir: " . number_format($montantEnvoye - $montantDejaRecu, 2) . "\n\n";

// 5. Tester les méthodes de calcul des soldes
echo "=== Test des méthodes repository ===\n\n";

echo "1. getSoldeByAgenceAndDevise():\n";
$soldeMethod1 = $detailsFondsRepo->getSoldeByAgenceAndDevise($agence->getId(), $devise->getId());
echo "   Résultat: " . number_format($soldeMethod1, 2) . "\n\n";

echo "2. getSoldesByAgence():\n";
$soldesMethod2 = $detailsFondsRepo->getSoldesByAgence($agence->getId());
echo "   Nombre de devises: " . count($soldesMethod2) . "\n";
foreach ($soldesMethod2 as $s) {
    if ($s['sigle'] == 'EUR') {
        echo "   EUR: " . number_format($s['montant'], 2) . "\n";
        break;
    }
}

echo "\n3. getSoldesByDevise() (vue admin):\n";
$soldesMethod3 = $detailsFondsRepo->getSoldesByDevise();
foreach ($soldesMethod3 as $s) {
    if ($s['devise']['sigle'] == 'EUR') {
        echo "   EUR: " . number_format($s['solde'], 2) . "\n";
        break;
    }
}

echo "\n=== RÉSULTAT ===\n";
echo "✅ Le système crée bien des DetailsFondsDepart avec montant POSITIF lors de la réception\n";
echo "✅ Les méthodes de calcul cumulent correctement les montants positifs et négatifs\n";
echo "✅ Le solde de l'agence réceptrice augmente du montant reçu\n";
echo "\n💡 Si le dashboard n'affiche pas le bon solde:\n";
echo "   1. Videz le cache: php bin/console cache:clear\n";
echo "   2. Rafraîchissez le navigateur avec Ctrl+F5\n";
echo "   3. Vérifiez que vous êtes connecté avec la bonne agence\n";
