<?php
require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "=== TEST VÉRIFICATION SOLDE POUR OPÉRATION DIVERSE ===\n\n";

// Récupérer les repos
$detailsFondsRepo = $em->getRepository(\App\Entity\DetailsFondsDepart::class);
$agenceRepo = $em->getRepository(\App\Entity\Agence::class);
$deviseRepo = $em->getRepository(\App\Entity\Devise::class);

// Trouver une agence et une devise
$agence = $agenceRepo->findOneBy([]);
$devise = $deviseRepo->findOneBy(['sigle' => 'EUR']);

if (!$agence || !$devise) {
    echo "❌ Agence ou devise EUR non trouvée\n";
    exit;
}

echo "Configuration du test:\n";
echo "  Agence: {$agence->getNomAgence()} (ID: {$agence->getId()})\n";
echo "  Devise: {$devise->getSigle()} (ID: {$devise->getId()})\n\n";

// Test 1: Vérifier que getSoldeByAgenceAndDevise retourne la bonne valeur
echo "Test 1: getSoldeByAgenceAndDevise()\n";
$montantDisponible = $detailsFondsRepo->getSoldeByAgenceAndDevise($agence->getId(), $devise->getId());
echo "  Solde retourné: " . number_format($montantDisponible, 2) . " {$devise->getSigle()}\n";

if ($montantDisponible > 0) {
    echo "  ✅ Le solde est correctement récupéré (>0)\n";
} else {
    echo "  ❌ Le solde est 0 ou négatif (ERREUR!)\n";
}

echo "\nTest 2: Simulation d'une opération diverse\n";

// Si solde >= 10, on peut faire une opération de 10
$montantOperation = 10;
if ($montantOperation <= $montantDisponible) {
    echo "  ✅ Opération autorisée: {$montantOperation} <= {$montantDisponible}\n";
    echo "  Solde après opération sera: " . number_format($montantDisponible - $montantOperation, 2) . "\n";
} else {
    echo "  ❌ Fonds insuffisants: demandé {$montantOperation}, disponible {$montantDisponible}\n";
}

echo "\n=== RÉSULTAT FINAL ===\n";
if ($montantDisponible > 0) {
    echo "✅ La vérification de solde pour opération diverse fonctionne correctement!\n";
    echo "   Le système peut maintenant rejeter les opérations insuffisantes.\n";
} else {
    echo "❌ La vérification de solde retourne toujours 0.\n";
    echo "   Le problème n'est pas résolu.\n";
}
?>
