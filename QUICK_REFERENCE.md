# 🎯 RÉFÉRENCE RAPIDE - Dashboard Intelligent

## 📋 Rôles et leurs Vues

### 🔴 ROLE_ADMIN → Dashboard Admin
**Fichier**: `admin_dashboard.html.twig`
- Performance par Agence
- Soldes Globaux
- Achats/Ventes par Devise (Global)
- Transactions Récentes (Système entier)

### 🔵 ROLE_CAISSIER → Dashboard Caissier
**Fichier**: `caissier_dashboard.html.twig`
- **Soldes en Caisse** (PRIORITAIRE)
- Achats/Ventes par Devise
- Transactions Récentes (Agence)

### 🟠 ROLE_RESPONSABLE_AGENCE → Dashboard Responsable
**Fichier**: `responsable_dashboard.html.twig`
- Soldes en Caisse
- Top Agents (Classement)
- Résumé Performance
- Transactions Récentes

### 🔵 ROLE_AGENT_CHANGE → Dashboard Agent
**Fichier**: `agent_dashboard.html.twig`
- Achats/Ventes par Devise
- Top Agents (si données)
- Soldes Agence
- Transactions Récentes

### ⚪ ROLE_USER → Dashboard Standard
**Fichier**: `user_dashboard.html.twig`
- Statistiques Basiques
- Soldes Disponibles
- Devises Actives
- Transactions Récentes

---

## 🗂️ Structure des Fichiers

```
✅ src/Controller/DashboardController.php
   ├── index()                    ← Route principale /
   ├── getUserRoleTemplate()      ← Détermination du template
   ├── getAdminDashboardData()    ← Données admin
   └── getAgentDashboardData()    ← Données agent/autre

✅ templates/dashboard/
   ├── index.html.twig           ← Router principal
   └── partials/                 ← Templates spécialisés
       ├── admin_dashboard.html.twig
       ├── agent_dashboard.html.twig
       ├── caissier_dashboard.html.twig
       ├── responsable_dashboard.html.twig
       └── user_dashboard.html.twig
```

---

## 🔄 Flux Simplifié

```
1. User Login → Connexion
2. Visite / (Route app_dashboard)
3. DashboardController::index() s'exécute
4. Détecte le rôle → Templates approprié
5. Récupère données correctes
6. Affiche dashboard intelligent
```

---

## 💡 Points Clés

| Aspect | Détail |
|--------|--------|
| **Sécurité** | Filtrage au niveau contrôleur |
| **Performance** | 15 trans (admin), 10 trans (autres) |
| **Couleurs** | Badges de rôle visuels |
| **Format** | Montants français (virgule décimale) |
| **Responsive** | Tables adaptatives |
| **Icons** | Boxicons (bx-*) |

---

## ⚙️ Détails Techniques

### getUserRoleTemplate() - Mappe des Rôles

```php
ROLE_ADMIN           → 'admin'
ROLE_CAISSIER        → 'caissier'
ROLE_RESPONSABLE_AGENCE → 'responsable'
ROLE_AGENT_CHANGE    → 'agent'
(Défaut)             → 'user'
```

### Données Transmises

```php
[
    'user_role' => string,
    'is_admin' => boolean,
    'user_agence' => Agence,
    'user_name' => string,
    'devises' => Collection,
    'stats' => [...],
    'soldes' => [...],
    'recent_transactions' => Collection,
]
```

---

## 🧪 Test Rapide

### Terminal
```bash
# Vider le cache
php bin/console cache:clear

# Vérifier l'erreur PHP (optionnel)
php -l src/Controller/DashboardController.php

# Lancer le serveur
symfony server:start
```

### Dans le Navigateur
```
http://localhost:8000/
```

---

## 📖 Documentation Complète

- **DASHBOARD_INTELLIGENT.md** - Guide complet
- **TEST_DASHBOARD.php** - Cas de test
- **MODIFICATIONS_DASHBOARD.md** - Changelog

---

## ✨ Avantages

✅ Chaque rôle voit ses données  
✅ Interface optimisée par rôle  
✅ Sécurité renforcée  
✅ Code maintenable  
✅ Facile d'étendre  
✅ Pas de fuite de données  

---

## 🚀 Statut

**✅ COMPLET ET FONCTIONNEL**

Testé et prêt pour :
- Production
- Déploiement
- Utilisation multi-rôle

---

*Dashboard Intelligent v1.0 - 12 Décembre 2025*
