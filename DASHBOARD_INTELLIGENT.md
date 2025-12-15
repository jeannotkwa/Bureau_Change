# Tableau de Bord Intelligent - Documentation

## 📊 Aperçu

Le tableau de bord a été complètement restructuré pour être **intelligent et adaptatif** selon le rôle de l'utilisateur. Chaque rôle voit des informations différentes et pertinentes pour ses responsabilités.

## 🎯 Rôles et Leurs Dashboards

### 1. **ROLE_ADMIN** - Administrateur Système
**Badge**: `Super Admin` (rouge)

#### Vue : Administration Globale
- **Statistiques principales** :
  - Total des transactions du jour (tous les appareils)
  - Total achats (CDF) - global
  - Total ventes (CDF) - global
  - Nombre d'agences

- **Panels spécialisés** :
  - **Performance par Agence** : Tableau comparatif de chaque agence avec leurs nombres de transactions et montants
  - **Soldes Globaux** : Tous les soldes par devise (vue globale du système)
  - **Achats/Ventes par Devise** : Analyse complète des opérations
  - **Transactions Récentes** : Les 15 dernières transactions du système avec détails complets (y compris l'agence)

#### Cas d'usage
- Suivi de la performance globale
- Vérification des disparités entre agences
- Analyse des devises critiques
- Décisions stratégiques

---

### 2. **ROLE_CAISSIER** - Caissier d'Agence
**Badge**: `Caissier` (bleu)

#### Vue : Focus sur les Soldes
- **Alert spéciale** : Bienvenue avec l'agence assignée
- **Statistiques principales** :
  - Transactions du jour (agence)
  - Total achats (CDF)
  - Total ventes (CDF)

- **Panels spécialisés** :
  - **Soldes en Caisse** (PRIORITAIRE - en relief) :
    - Table avec codes couleur : vert (disponible) / rouge (à provisionner)
    - Affichage clair du montant pour chaque devise
    - Taux achat/vente visibles
  - **Achats/Ventes par Devise** : Récapitulatif des opérations du jour
  - **Transactions Récentes** : Dernières transactions de son agence

#### Cas d'usage
- Vérification des soldes en caisse avant/après transactions
- Identification rapide des devises épuisées
- Traçabilité des opérations effectuées
- Gestion de la caisse quotidienne

---

### 3. **ROLE_RESPONSABLE_AGENCE** - Responsable d'Agence
**Badge**: `Responsable` (orange)

#### Vue : Management d'Agence
- **Alert spéciale** : Bienvenue du responsable d'agence
- **KPIs d'Agence** :
  - Total transactions (agence)
  - Total achats (CDF)
  - Total ventes (CDF)

- **Panels spécialisés** :
  - **Soldes en Caisse** : Tableau détaillé avec statut (OK / À Reconstituer)
  - **Achats/Ventes par Devise** : Analyse des devises
  - **Top Agents** : Classement des agents par nombre de transactions
    - Affiche le rang (#1, #2, etc.)
    - Pourcentage de contribution
  - **Résumé Performance** : Cards avec total transactions et total opérations
  - **Transactions Récentes** : Dernières transactions de l'agence

#### Cas d'usage
- Suivi de performance de l'équipe
- Identification des agents les plus productifs
- Gestion des soldes de l'agence
- Vérification du bon fonctionnement de la journée
- Détection des problèmes de trésorerie

---

### 4. **ROLE_AGENT_CHANGE** - Agent de Change
**Badge**: `Agent` (bleu primaire)

#### Vue : Agent Opérationnel
- **Statistiques principales** :
  - Transactions du jour (agence)
  - Total achats (CDF)
  - Total ventes (CDF)
  - Votre agence (nom court)

- **Panels spécialisés** :
  - **Achats/Ventes par Devise** : Détail des opérations du jour
  - **Top Agents** (si applicable) : Vue des meilleurs agents du jour
  - **Soldes de Votre Agence** : Tableau compact des soldes
  - **Transactions Récentes** : Ses opérations et celles de ses collègues

#### Cas d'usage
- Vérification des soldes avant transactions
- Suivi de ses opérations
- Comparaison avec les collègues
- Consultation rapide des données

---

### 5. **Rôle par Défaut** - Utilisateur Standard
**Badge**: Aucun

#### Vue : Dashboard Basique
- **Statistiques basiques** :
  - Nombre total de transactions
  - Achats (CDF)
  - Ventes (CDF)

- **Panels standards** :
  - **Soldes Disponibles** : Vue simple des soldes
  - **Devises Actives** : Liste des devises en circulation
  - **Transactions Récentes** : Dernières transactions

#### Cas d'usage
- Utilisateurs non assignés à un rôle spécifique
- Accès basique au système

---

## 🔧 Architecture Technique

### Structure des Fichiers

```
templates/dashboard/
├── index.html.twig                 (Template principal - routage intelligent)
└── partials/
    ├── admin_dashboard.html.twig         (Vue admin)
    ├── agent_dashboard.html.twig         (Vue agent)
    ├── caissier_dashboard.html.twig      (Vue caissier)
    ├── responsable_dashboard.html.twig   (Vue responsable)
    └── user_dashboard.html.twig          (Vue standard)
```

### Contrôleur DashboardController.php

Méthodes clés :

#### `index()`
- Point d'entrée unique
- Détecte le rôle de l'utilisateur
- Récupère les données appropriées
- Passe les données au template

#### `getUserRoleTemplate(array $roles): string`
Détermine le template à utiliser selon les rôles :
- `'admin'` ← ROLE_ADMIN
- `'caissier'` ← ROLE_CAISSIER
- `'responsable'` ← ROLE_RESPONSABLE_AGENCE
- `'agent'` ← ROLE_AGENT_CHANGE
- `'user'` ← défaut

#### `getAdminDashboardData()`
- Récupère toutes les transactions du jour
- Calcule les soldes globaux par devise
- Statistiques par agence
- Achats/ventes par devise

#### `getAgentDashboardData()`
- Récupère les transactions de l'agence
- Soldes de l'agence uniquement
- Top agents (classement)
- Statistiques locales

### Template Principal (index.html.twig)

```twig
{% if user_role == 'admin' %}
    {% include 'dashboard/partials/admin_dashboard.html.twig' %}
{% elseif user_role == 'caissier' %}
    {% include 'dashboard/partials/caissier_dashboard.html.twig' %}
<!-- etc. -->
{% endif %}
```

---

## 📈 Données Transmises au Template

```php
$viewData = [
    'user_role' => 'admin|caissier|responsable|agent|user',
    'is_admin' => true/false,
    'user_agence' => Agence object,
    'user_name' => string,
    'devises' => Collection<Devise>,
    'stats' => [
        'total_transactions' => int,
        'total_achats' => float,
        'total_ventes' => float,
        'achats_par_devise' => array,
        'ventes_par_devise' => array,
        'agences_stats' => array (admin only),
        'top_agents' => array (agent/responsable),
    ],
    'soldes' => array,
    'recent_transactions' => Collection<Transaction>,
    'all_agencies' => Collection<Agence> (admin only),
];
```

---

## 🎨 Codification Couleurs

| Rôle | Couleur | Signification |
|------|---------|---------------|
| Admin | Rouge (danger) | Accès complet système |
| Caissier | Bleu (info) | Opérationnel, focus soldes |
| Responsable | Orange (warning) | Management agence |
| Agent | Bleu primaire | Opérationnel standard |
| Utilisateur | Gris (secondary) | Accès limité |

---

## ✅ Avantages du Système

1. **Pertinence** : Chaque utilisateur voit les infos dont il a besoin
2. **Sécurité** : Pas d'affichage de données sensibles à qui ne peut les voir
3. **Efficacité** : Interface optimisée par rôle
4. **Maintenabilité** : Séparation claire des templates par rôle
5. **Extensibilité** : Facile d'ajouter un nouveau rôle/dashboard

---

## 🔄 Comment Ajouter un Nouveau Rôle

1. Créer un nouveau fichier dans `templates/dashboard/partials/` :
   ```twig
   <!-- templates/dashboard/partials/nouveau_role_dashboard.html.twig -->
   ```

2. Mettre à jour `getUserRoleTemplate()` dans le contrôleur :
   ```php
   } elseif (in_array('ROLE_NOUVEAU', $roles)) {
       return 'nouveau_role';
   }
   ```

3. Mettre à jour le template principal :
   ```twig
   {% elseif user_role == 'nouveau_role' %}
       {% include 'dashboard/partials/nouveau_role_dashboard.html.twig' %}
   ```

---

## 📝 Notes

- Les données sont toujours filtrées au niveau contrôleur (pas de logique métier dans les templates)
- Les devises actives sont toujours affichées pour permettre les consultations
- Les soldes sont recalculés en fonction du rôle/agence
- Les transactions affichées dépendent du périmètre (global pour admin, agence pour les autres)
