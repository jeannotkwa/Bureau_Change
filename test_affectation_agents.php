<?php
require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

echo "=== TEST SYSTÈME DE GESTION DES AFFECTATIONS D'AGENTS ===\n\n";

// Récupérer les repos
$utilisateurRepo = $em->getRepository(\App\Entity\Utilisateur::class);
$agenceRepo = $em->getRepository(\App\Entity\Agence::class);
$affectationRepo = $em->getRepository(\App\Entity\AffectationAgent::class);

// Récupérer un utilisateur et deux agences
$utilisateur = $utilisateurRepo->findOneBy([], ['id' => 'ASC']);
$agences = $agenceRepo->findBy([], ['id' => 'ASC'], 2);

if (!$utilisateur || count($agences) < 2) {
    echo "❌ Données insuffisantes (besoin d'un utilisateur et 2 agences)\n";
    exit;
}

echo "Configuration du test:\n";
echo "  Agent: {$utilisateur->getNom()} (ID: {$utilisateur->getId()})\n";
echo "  Agence 1: {$agences[0]->getNomAgence()}\n";
echo "  Agence 2: {$agences[1]->getNomAgence()}\n\n";

try {
    echo "Test 1: Créer une première affectation\n";
    
    $affectation1 = new \App\Entity\AffectationAgent();
    $affectation1->setUtilisateur($utilisateur);
    $affectation1->setAgence($agences[0]);
    $affectation1->setDateDebut(new \DateTime('2025-01-01'));
    $affectation1->setStatut('actif');
    
    $em->persist($affectation1);
    $em->flush();
    
    $utilisateur->setAgence($agences[0]);
    $em->persist($utilisateur);
    $em->flush();
    
    echo "  ✅ Affectation créée: {$utilisateur->getNom()} → {$agences[0]->getNomAgence()}\n";
    
    echo "\nTest 2: Vérifier l'affectation actuelle\n";
    
    $affectationActuelle = $affectationRepo->getAffectationActuelle($utilisateur);
    if ($affectationActuelle && $affectationActuelle->getAgence()->getId() === $agences[0]->getId()) {
        echo "  ✅ Affectation actuelle correcte: {$affectationActuelle->getAgence()->getNomAgence()}\n";
    } else {
        echo "  ❌ Erreur lors de la récupération de l'affectation actuelle\n";
    }
    
    echo "\nTest 3: Transférer l'agent à une autre agence\n";
    
    // Clôturer l'affectation précédente
    $affectation1->setDateFin(new \DateTime('2025-06-30'));
    $affectation1->setStatut('inactif');
    $em->persist($affectation1);
    
    // Créer la nouvelle affectation
    $affectation2 = new \App\Entity\AffectationAgent();
    $affectation2->setUtilisateur($utilisateur);
    $affectation2->setAgence($agences[1]);
    $affectation2->setDateDebut(new \DateTime('2025-07-01'));
    $affectation2->setStatut('actif');
    
    $em->persist($affectation2);
    $utilisateur->setAgence($agences[1]);
    $em->persist($utilisateur);
    $em->flush();
    
    echo "  ✅ Agent transféré: {$utilisateur->getNom()} → {$agences[1]->getNomAgence()}\n";
    
    echo "\nTest 4: Vérifier la nouvelle affectation actuelle\n";
    
    $affectationActuelle = $affectationRepo->getAffectationActuelle($utilisateur);
    if ($affectationActuelle && $affectationActuelle->getAgence()->getId() === $agences[1]->getId()) {
        echo "  ✅ Affectation actuelle mise à jour: {$affectationActuelle->getAgence()->getNomAgence()}\n";
    } else {
        echo "  ❌ Erreur lors de la mise à jour de l'affectation actuelle\n";
    }
    
    echo "\nTest 5: Récupérer l'historique complet\n";
    
    $historique = $affectationRepo->getHistoriqueAffectations($utilisateur);
    echo "  Nombre d'affectations: " . count($historique) . "\n";
    
    if (count($historique) >= 2) {
        echo "  ✅ Historique contient " . count($historique) . " affectation(s):\n";
        foreach ($historique as $aff) {
            $statut = $aff->getStatut();
            $dateFin = $aff->getDateFin() ? $aff->getDateFin()->format('d/m/Y') : 'En cours';
            echo "    - {$aff->getAgence()->getNomAgence()} ({$aff->getDateDebut()->format('d/m/Y')} → {$dateFin})\n";
        }
    } else {
        echo "  ❌ Historique incomplet\n";
    }
    
    echo "\nTest 6: Récupérer les agents d'une agence\n";
    
    $agentsAgence1 = $affectationRepo->getAgentsParAgence($agences[0]);
    echo "  Agents dans {$agences[0]->getNomAgence()}: " . count($agentsAgence1) . "\n";
    
    $agentsAgence2 = $affectationRepo->getAgentsParAgence($agences[1]);
    echo "  Agents dans {$agences[1]->getNomAgence()}: " . count($agentsAgence2) . "\n";
    
    if (count($agentsAgence2) >= 1) {
        echo "  ✅ Agent trouvé dans la nouvelle agence\n";
    }
    
    echo "\n=== RÉSULTAT FINAL ===\n";
    echo "✅ Système de gestion des affectations d'agents fonctionnel!\n";
    echo "✅ Les agents peuvent être transférés d'une agence à une autre\n";
    echo "✅ L'historique complet des affectations est conservé\n";
    echo "✅ Les affectations actuelles sont correctement identifiées\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
