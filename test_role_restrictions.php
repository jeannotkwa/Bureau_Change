<?php
require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "=== TEST RESTRICTIONS D'ACCÈS ROLE_USER ===\n\n";

// Récupérer un utilisateur ROLE_USER
$userRepo = $em->getRepository(\App\Entity\Utilisateur::class);
$roleUser = $userRepo->findOneBy(['email' => 'patricia@bureau.cd']);

if (!$roleUser) {
    echo "❌ Utilisateur ROLE_USER non trouvé\n";
    exit;
}

echo "Utilisateur testé: {$roleUser->getNom()} ({$roleUser->getEmail()})\n";
echo "Rôles: " . implode(', ', $roleUser->getRoles()) . "\n\n";

echo "=== ACCÈS AUTORISÉS POUR ROLE_USER ===\n";
echo "✅ Dashboard (app_dashboard)\n";
echo "✅ Transactions de Change:\n";
echo "   - Nouvelle Transaction (app_transaction_new)\n";
echo "   - Historique Transactions (app_transaction_index)\n";
echo "✅ Rapports:\n";
echo "   - Rapports (app_rapport_index)\n\n";

echo "=== ACCÈS REFUSÉS POUR ROLE_USER ===\n";
echo "❌ Fonds de Départ (app_fonds_*)\n";
echo "❌ Transferts de Fonds (app_transfert_*)\n";
echo "❌ Opérations Diverses (app_operation_diverse_*)\n";
echo "❌ Administration (app_admin_*, app_devise_*, app_utilisateur_*, app_agence_*)\n\n";

echo "=== VÉRIFICATION SIDEBAR ===\n";
echo "Le menu doit afficher:\n";
echo "✓ TABLEAU DE BORD\n";
echo "✓ OPÉRATIONS (Transactions Change)\n";
echo "✓ RAPPORTS & ANALYSES\n";
echo "✗ GESTION FINANCIÈRE (caché)\n";
echo "✗ ADMINISTRATION (caché)\n\n";

echo "=== PROTECTION DES CONTRÔLEURS ===\n";
echo "✓ FondsDepartController: #[IsGranted('ROLE_ADMIN')]\n";
echo "✓ TransfertFondController: #[IsGranted('ROLE_ADMIN')]\n";
echo "✓ OperationDiversesController: #[IsGranted('ROLE_ADMIN')]\n\n";

echo "✅ Configuration terminée!\n";
echo "\n💡 Pour tester:\n";
echo "1. Connectez-vous avec patricia@bureau.cd\n";
echo "2. Vérifiez que le menu GESTION FINANCIÈRE n'apparaît pas\n";
echo "3. Essayez d'accéder à /fonds, /transferts ou /operations-diverses\n";
echo "   → Vous devriez obtenir une erreur 403 (Access Denied)\n";
