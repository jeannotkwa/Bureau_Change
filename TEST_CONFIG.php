<?php

/**
 * CONFIGURATION DE TEST - Dashboard Intelligent
 * 
 * Ce fichier contient des exemples de configuration des utilisateurs
 * pour tester le dashboard avec différents rôles
 */

// ===== EXEMPLE DE DONNÉES SQL POUR TEST =====

/**
 * Créer différents utilisateurs pour test:
 * 
 * INSERT INTO utilisateurs VALUES (
 *     DEFAULT,
 *     'Admin System',
 *     'admin@test.com',
 *     '["ROLE_ADMIN"]',  -- Voir toutes les données globales
 *     'hashed_password',
 *     'actif',
 *     NULL,              -- Pas d'agence assignée
 *     NOW()
 * );
 * 
 * INSERT INTO utilisateurs VALUES (
 *     DEFAULT,
 *     'Caissier Kinshasa',
 *     'caissier1@test.com',
 *     '["ROLE_CAISSIER"]',
 *     'hashed_password',
 *     'actif',
 *     1,                 -- Agence ID 1 (Kinshasa)
 *     NOW()
 * );
 * 
 * INSERT INTO utilisateurs VALUES (
 *     DEFAULT,
 *     'Responsable Kinshasa',
 *     'responsable1@test.com',
 *     '["ROLE_RESPONSABLE_AGENCE"]',
 *     'hashed_password',
 *     'actif',
 *     1,                 -- Agence ID 1
 *     NOW()
 * );
 * 
 * INSERT INTO utilisateurs VALUES (
 *     DEFAULT,
 *     'Agent Change Kinshasa',
 *     'agent1@test.com',
 *     '["ROLE_AGENT_CHANGE"]',
 *     'hashed_password',
 *     'actif',
 *     1,                 -- Agence ID 1
 *     NOW()
 * );
 * 
 * INSERT INTO utilisateurs VALUES (
 *     DEFAULT,
 *     'Utilisateur Simple',
 *     'user@test.com',
 *     '["ROLE_USER"]',
 *     'hashed_password',
 *     'actif',
 *     2,                 -- Agence ID 2
 *     NOW()
 * );
 */

// ===== COMMANDES SYMFONY POUR CRÉER LES UTILISATEURS =====

/**
 * Créer un utilisateur admin:
 * php bin/console make:user
 * (Répondre: admin@test.com, ROLE_ADMIN, sans hash, etc.)
 * 
 * Ou via SQL directement après migration
 */

// ===== STRUCTURE DES RÔLES DANS LE CODE =====

/**
 * src/Security/security.yaml contient:
 * 
 * role_hierarchy:
 *     ROLE_ADMIN: [ROLE_USER]
 *     ROLE_SUPER_ADMIN: [ROLE_ADMIN, ROLE_ALLOWED_TO_SWITCH]
 * 
 * access_control:
 *     - { path: ^/login, roles: PUBLIC_ACCESS }
 *     - { path: ^/admin, roles: ROLE_ADMIN }
 *     - { path: ^/, roles: ROLE_USER }
 */

// ===== EXEMPLE DE TEST COMPORTEMENT =====

class DashboardTestExamples
{
    /**
     * Cas 1: Admin accède au dashboard
     * 
     * Utilisateur: admin@test.com (ROLE_ADMIN)
     * Résultat attendu:
     * - Badge "Super Admin" (rouge)
     * - Voir ALL agences dans tableau
     * - Soldes globaux par devise
     * - Transactions de TOUT le système
     */
    public function testAdminAccess()
    {
        // Contenu qui devrait être affiché:
        // - "Performance par Agence" (Kinshasa, Goma, Bukavu, etc.)
        // - "Soldes Globaux par Devise" (USD, EUR, GBP, etc.)
        // - Achats/Ventes par Devise (GLOBAL)
        // - Les 15 dernières transactions du système
    }

    /**
     * Cas 2: Caissier accède au dashboard
     * 
     * Utilisateur: caissier1@test.com (ROLE_CAISSIER)
     * Agence: Kinshasa (ID=1)
     * Résultat attendu:
     * - Badge "Caissier" (bleu)
     * - Alert: "Bienvenue ... agence Kinshasa"
     * - Soldes en Caisse (RELIEF)
     *   - Vert: Soldes positifs
     *   - Rouge: Soldes négatifs ("À Provisionner")
     * - Achats/Ventes du jour (Kinshasa uniquement)
     * - 10 transactions récentes (Kinshasa)
     */
    public function testCaissierAccess()
    {
        // Contenu qui devrait être affiché:
        // - "Soldes en Caisse" en évidence
        // - Couleur vert/rouge selon le montant
        // - SEULEMENT données de Kinshasa
        // - PAS d'informations d'autres agences
    }

    /**
     * Cas 3: Responsable accède au dashboard
     * 
     * Utilisateur: responsable1@test.com (ROLE_RESPONSABLE_AGENCE)
     * Agence: Kinshasa (ID=1)
     * Résultat attendu:
     * - Badge "Responsable" (orange)
     * - Alert: "Tableau de Bord du Responsable d'Agence ... Kinshasa"
     * - Top Agents: Classement des agents par transactions
     *   #1 Agent A - 15 transactions (45%)
     *   #2 Agent B - 12 transactions (36%)
     *   etc.
     * - Résumé Performance
     * - Soldes Agence
     */
    public function testResponsableAccess()
    {
        // Contenu qui devrait être affiché:
        // - Top Agents avec rang et pourcentage
        // - Cards de résumé performance
        // - Données agence + équipe
        // - Soldes avec statut (OK / À Reconstituer)
    }

    /**
     * Cas 4: Agent accède au dashboard
     * 
     * Utilisateur: agent1@test.com (ROLE_AGENT_CHANGE)
     * Agence: Kinshasa (ID=1)
     * Résultat attendu:
     * - Badge "Agent" (bleu primaire)
     * - Statistiques: Transactions/Achats/Ventes
     * - Achats/Ventes par Devise
     * - Soldes de l'Agence
     * - 10 transactions récentes
     */
    public function testAgentAccess()
    {
        // Contenu qui devrait être affiché:
        // - Vue opérationnelle simple
        // - Pas de données sensibles
        // - Agence Kinshasa uniquement
    }

    /**
     * Cas 5: Utilisateur standard
     * 
     * Utilisateur: user@test.com (ROLE_USER)
     * Agence: Goma (ID=2)
     * Résultat attendu:
     * - Pas de badge spécifique
     * - Dashboard basique
     * - Données agence uniquement
     */
    public function testUserAccess()
    {
        // Contenu qui devrait être affiché:
        // - Dashboard simple et épuré
        // - Données de Goma
    }
}

// ===== CHECKLIST DE VALIDATION MANUELLE =====

/**
 * Pour chaque rôle, vérifier:
 * 
 * ✓ Le badge de rôle s'affiche et a la bonne couleur
 * ✓ L'alert de bienvenue est pertinente au rôle
 * ✓ Les données affichées correspondent au rôle
 * ✓ Aucune data d'autres agences n'est visible
 * ✓ Les montants sont au format français (virgule, espaces)
 * ✓ Les tables sont responsives
 * ✓ Les icônes Boxicons s'affichent
 * ✓ Les couleurs des statuts sont correctes
 * ✓ Les transactions affichées sont pertinentes
 * ✓ Les soldes sont corrects pour l'agence
 * ✓ Les top agents s'affichent (si données)
 * ✓ Les devises actives sont listées
 * ✓ Les taux achat/vente s'affichent
 * ✓ Pas d'erreur JavaScript console
 * ✓ Pas d'erreur PHP logs
 */

// ===== DONNÉES DE TEST =====

/**
 * Créer des transactions de test pour voir des données:
 * 
 * Pour chaque agence:
 * - 5-10 transactions achat (devises variées)
 * - 5-10 transactions vente (devises variées)
 * - Dates variées (aujourd'hui, hier, etc.)
 * - Utilisateurs variés (pour top agents)
 * 
 * Exemple SQL:
 * INSERT INTO transactions VALUES (
 *     DEFAULT,
 *     'REF123456',
 *     'Client Test',
 *     NULL,
 *     'Kinshasa, RDC',
 *     '+243912345678',
 *     'achat',
 *     CURDATE(),
 *     1,              -- Agent ID
 *     1,              -- Agence ID
 *     NOW()
 * );
 */

// ===== NOTES IMPORTANTES =====

/**
 * ⚠️ SÉCURITÉ:
 * - Toujours utiliser des comptes de test différents
 * - Ne pas partager les mots de passe
 * - Vérifier les logs de sécurité
 * - Tester les accès refusés
 * 
 * ⚠️ DONNÉES:
 * - Vérifier que les filtres agence fonctionnent
 * - Vérifier que les calculs sont corrects
 * - Vérifier les formatages
 * - Vérifier les valeurs null/zéro
 * 
 * ⚠️ PERFORMANCE:
 * - Mesurer le temps de chargement
 * - Vérifier les requêtes BD (Query Monitor)
 * - Vérifier qu'il n'y a pas de N+1 queries
 * 
 * ⚠️ UX:
 * - Tester sur mobile/tablet
 * - Vérifier accessibilité (contraste, labels)
 * - Tester dans différents navigateurs
 */

echo "✅ Configuration de Test - Complète\n";
echo "📋 Utilisez ce fichier comme guide pour tester chaque rôle\n";
