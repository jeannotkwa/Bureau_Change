# 🚀 GUIDE DE DÉMARRAGE RAPIDE
## Bureau de Change - Application Symfony

### 📋 PRÉ-REQUIS
- ✅ PHP 8.1+ installé (avec WAMP)
- ✅ Composer installé
- ✅ MySQL/MariaDB actif
- ✅ Extension PHP : pdo_mysql, intl, mbstring

---

## ⚡ DÉMARRAGE EN 5 MINUTES

### 1️⃣ Vérifier l'installation
```bash
cd c:\wamp64\www\currence-app\currency-exchange-symfony

# Vérifier PHP
php -v

# Vérifier Composer
composer -V
```

### 2️⃣ Installer les dépendances (si nécessaire)
```bash
composer install
```

### 3️⃣ Créer et initialiser la base de données
```bash
# Méthode 1 : Via Symfony (recommandé)
php bin/console doctrine:database:create

# Méthode 2 : Via MySQL direct
mysql -u root -p < database_init.sql
```

### 4️⃣ Exécuter les migrations Doctrine
```bash
# Créer les migrations à partir des entités
php bin/console make:migration

# Exécuter les migrations
php bin/console doctrine:migrations:migrate
```

### 5️⃣ Charger les données de test (optionnel)
```bash
# Si vous avez utilisé le script SQL, les données sont déjà chargées
# Sinon, importez le fichier database_init.sql dans phpMyAdmin
```

### 6️⃣ Démarrer le serveur
```bash
# Méthode 1 : Avec Symfony CLI (recommandé)
symfony server:start

# Méthode 2 : Avec PHP built-in server
php -S localhost:8000 -t public

# Méthode 3 : Via WAMP
# Accéder à http://localhost/currence-app/currency-exchange-symfony/public
```

---

## 🔐 CONNEXION

**URL:** http://localhost:8000/login

### Comptes de test :
| Email | Mot de passe | Rôle |
|-------|-------------|------|
| admin@bureau.cd | admin123 | SUPER_ADMIN |
| jean.kabongo@bureau.cd | admin123 | ADMIN |
| marie.tshala@bureau.cd | admin123 | USER |

---

## 📁 STRUCTURE DU PROJET

```
currency-exchange-symfony/
├── 📂 config/              Configuration Symfony
├── 📂 public/              Point d'entrée + Assets
│   └── 📂 assets/         Template Minia (copié)
├── 📂 src/
│   ├── 📂 Controller/     ✅ SecurityController, DashboardController
│   ├── 📂 Entity/         ✅ 8 entités créées
│   ├── 📂 Repository/     ✅ Repositories créés
│   ├── 📂 Form/           ⚠️ À créer
│   └── 📂 Service/        ⚠️ À créer
├── 📂 templates/          ✅ Templates Twig créés
├── 📄 .env                ✅ Configuré
└── 📄 README_SYMFONY.md   📖 Documentation complète
```

---

## ✅ CE QUI EST DÉJÀ FAIT

### Entités Doctrine (100%)
- ✅ Utilisateur (UserInterface)
- ✅ Agence
- ✅ Devise
- ✅ TypeIdentite
- ✅ Transaction
- ✅ DetailsTransaction
- ✅ FondsDepart
- ✅ DetailsFondsDepart

### Sécurité (100%)
- ✅ Configuration security.yaml
- ✅ Authentification par formulaire
- ✅ Gestion des rôles (USER, ADMIN, SUPER_ADMIN)
- ✅ SecurityController
- ✅ Page de login

### Interface (80%)
- ✅ Template Minia intégré
- ✅ Layout base.html.twig
- ✅ Header, Sidebar, Footer
- ✅ Dashboard avec statistiques
- ✅ Affichage transactions récentes
- ✅ Affichage soldes devises

### Base de données (100%)
- ✅ Script SQL complet
- ✅ Données de test
- ✅ Vues et procédures stockées

---

## 🔨 CE QU'IL RESTE À FAIRE

### 1. Créer les contrôleurs manquants
```bash
php bin/console make:controller TransactionController
php bin/console make:controller DeviseController
php bin/console make:controller AgenceController
php bin/console make:controller UtilisateurController
php bin/console make:controller FondsController
php bin/console make:controller RapportController
```

### 2. Créer les FormTypes
```bash
php bin/console make:form TransactionType
php bin/console make:form DeviseType
php bin/console make:form AgenceType
php bin/console make:form UtilisateurType
php bin/console make:form FondsDepartType
```

### 3. Créer les Services métier
Créer dans `src/Service/` :
- `TransactionService.php` - Logique des transactions
- `FondsService.php` - Gestion des fonds
- `PdfService.php` - Génération PDF (DOMPDF installé)
- `RapportService.php` - Génération rapports

### 4. Créer les templates Twig manquants
- `templates/transaction/` - CRUD transactions
- `templates/devise/` - Gestion devises
- `templates/agence/` - Gestion agences
- `templates/utilisateur/` - Gestion utilisateurs
- `templates/fonds/` - Gestion fonds
- `templates/rapport/` - Rapports

---

## 🎯 FONCTIONNALITÉS PRIORITAIRES

### Phase 1 : Transactions (URGENT)
1. Formulaire nouvelle transaction
2. Liste des transactions
3. Détails d'une transaction
4. Impression reçu (PDF)
5. Validation soldes avant transaction

### Phase 2 : Gestion Devises
1. Liste des devises
2. Ajout/Modification devise
3. Mise à jour taux de change
4. Historique des taux

### Phase 3 : Fonds de Départ
1. Affichage soldes par devise
2. Ajout fonds de départ
3. Transfert entre agences
4. Historique des fonds

### Phase 4 : Rapports
1. Rapport journalier
2. Rapport mensuel
3. Historique soldes
4. Export Excel/PDF

---

## 🐛 DÉPANNAGE

### Erreur "Class not found"
```bash
composer dump-autoload
php bin/console cache:clear
```

### Erreur de migration
```bash
php bin/console doctrine:schema:update --force
```

### Problème d'assets
```bash
# Vérifier que les assets sont copiés
ls public/assets/

# Si manquants, recopier
xcopy "c:\wamp64\www\currence-app\Template-Admin\assets" "public\assets\" /E /I /Y
```

### Erreur 500
```bash
# Consulter les logs
tail -f var/log/dev.log

# Mode debug
# Dans .env : APP_ENV=dev
```

---

## 📚 COMMANDES UTILES

### Développement
```bash
# Créer une entité
php bin/console make:entity NomEntite

# Créer un contrôleur
php bin/console make:controller NomController

# Créer un formulaire
php bin/console make:form NomFormType

# Créer une migration
php bin/console make:migration

# Lister les routes
php bin/console debug:router

# Vider le cache
php bin/console cache:clear
```

### Base de données
```bash
# Créer la BDD
php bin/console doctrine:database:create

# Mettre à jour le schéma
php bin/console doctrine:schema:update --force

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Charger des fixtures
php bin/console doctrine:fixtures:load
```

### Production
```bash
# Optimiser Composer
composer install --no-dev --optimize-autoloader

# Vider le cache prod
APP_ENV=prod php bin/console cache:clear

# Compiler les assets
php bin/console asset-map:compile
```

---

## 📞 SUPPORT

### Documentation
- Symfony: https://symfony.com/doc/current/index.html
- Doctrine: https://www.doctrine-project.org/projects/doctrine-orm/en/latest/
- Twig: https://twig.symfony.com/doc/

### Fichiers importants
- `README_SYMFONY.md` - Documentation complète
- `database_init.sql` - Script SQL
- `.env` - Configuration environnement
- `config/packages/security.yaml` - Sécurité

---

## ✨ PROCHAINES ÉTAPES

1. ✅ Lancer l'application
2. ✅ Se connecter avec admin@bureau.cd
3. 🔨 Créer le formulaire de transaction
4. 🔨 Implémenter la logique métier
5. 🔨 Générer les PDF
6. 🔨 Créer les rapports

**Bonne chance! 🚀**
