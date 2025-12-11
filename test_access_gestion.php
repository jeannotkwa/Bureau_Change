<?php
require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "=== TEST D'ACCÈS GESTION FINANCIÈRE ===\n\n";

// Récupérer un utilisateur avec ROLE_USER uniquement
$userRepo = $em->getRepository(\App\Entity\Utilisateur::class);
$user = $userRepo->findOneBy(['email' => 'patricia@bureau.cd']);

if (!$user) {
    echo "❌ Utilisateur Patricia non trouvé\n";
    exit;
}

echo "Utilisateur testé: {$user->getNom()} ({$user->getEmail()})\n";
echo "Rôles: " . implode(', ', $user->getRoles()) . "\n\n";

// Vérifier les rôles
$hasRoleUser = in_array('ROLE_USER', $user->getRoles());
$hasRoleAdmin = in_array('ROLE_ADMIN', $user->getRoles());

echo "✓ A ROLE_USER: " . ($hasRoleUser ? 'OUI' : 'NON') . "\n";
echo "✓ A ROLE_ADMIN: " . ($hasRoleAdmin ? 'OUI' : 'NON') . "\n\n";

echo "=== ROUTES ACCESSIBLES ===\n";
echo "Selon security.yaml:\n";
echo "- /fonds/* : devrait être accessible avec ROLE_USER\n";
echo "- /transferts/* : devrait être accessible avec ROLE_USER\n";
echo "- /operations-diverses/* : devrait être accessible avec ROLE_USER\n";
echo "- /admin/* : accessible uniquement avec ROLE_ADMIN\n\n";

if ($hasRoleUser && !$hasRoleAdmin) {
    echo "✅ Cet utilisateur DEVRAIT voir la section GESTION FINANCIÈRE\n";
} else {
    echo "❌ Problème de configuration des rôles\n";
}
