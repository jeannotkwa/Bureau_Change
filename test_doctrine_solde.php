<?php
require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "=== Test de getSoldesByAgence avec Doctrine ===\n\n";

$repo = $em->getRepository(\App\Entity\DetailsFondsDepart::class);
$agenceId = 1; // Agence ville

$soldes = $repo->getSoldesByAgence($agenceId);

echo "Nombre de résultats : " . count($soldes) . "\n\n";

if (empty($soldes)) {
    echo "❌ AUCUN résultat retourné par getSoldesByAgence!\n";
} else {
    foreach ($soldes as $solde) {
        echo "Résultat: ";
        print_r($solde);
        echo "\n";
    }
}
