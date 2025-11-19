# 💰 KitiSmart - Gestion de Budget Personnel

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-12%2B-green)](https://www.postgresql.org/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

Application web de gestion budgétaire développée avec une architecture MVC PHP personnalisée. Gérez vos dépenses, suivez votre budget et visualisez vos finances en temps réel avec des graphiques interactifs.

![KitiSmart Dashboard](logo2.svg)

---

## ✨ Fonctionnalités

### 🔐 Authentification Sécurisée
- ✅ Inscription avec confirmation par email
- ✅ Connexion sécurisée avec hashage bcrypt
- ✅ Réinitialisation de mot de passe par email
- ✅ Protection CSRF sur tous les formulaires
- ✅ Rate limiting anti-brute force

### 💰 Gestion de Budget
- ✅ Création de budgets mensuels/périodiques
- ✅ Suivi du budget initial et solde restant
- ✅ Clôture automatique des budgets précédents
- ✅ Un seul budget actif par utilisateur
- ✅ Alertes intelligentes (60%, 80%, 100%)

### 💸 Suivi des Dépenses
- ✅ Ajout de dépenses avec catégories personnalisées
- ✅ 3 types de catégories : Charges fixes, Divers, Épargne
- ✅ Statuts : En attente / Payé
- ✅ Modification et suppression de dépenses
- ✅ Liste paginée (6 dépenses par page)
- ✅ Création en lot de dépenses
- ✅ Réplication automatique des charges fixes

### 📊 Visualisations & Analytics
- ✅ Dashboard interactif avec Chart.js
- ✅ Graphique en camembert : Répartition par catégorie
- ✅ Graphique en barres : Dépenses par type
- ✅ Graphique de progression du budget
- ✅ Alertes visuelles code couleur (vert/orange/rouge)
- ✅ Statistiques en temps réel

### 📥 Export de Données
- ✅ Export CSV avec encodage UTF-8 (Excel compatible)
- ✅ Export PDF/Print-friendly avec graphiques
- ✅ Rapports mensuels détaillés

### ⚙️ Paramètres Utilisateur
- ✅ Modification du profil (nom, email)
- ✅ Changement de mot de passe sécurisé
- ✅ Suppression de compte avec confirmation
- ✅ Gestion complète des données

### 🔍 Audit & Traçabilité
- ✅ Historique des actions utilisateur
- ✅ Enregistrement IP et User-Agent
- ✅ Piste d'audit des dépenses
- ✅ Logs détaillés des transactions

---

## 🛠️ Stack Technologique

### Backend
- **PHP 7.4+** avec typage strict (`declare(strict_types=1)`)
- **Architecture MVC** personnalisée
- **RedBeanPHP 5.7** - ORM léger et flexible
- **AltoRouter 2.0** - Routage RESTful
- **PHPMailer 6.9** - Envoi d'emails
- **Whoops 2.15** - Gestion d'erreurs (dev)
- **Respect\Validation 2.2** - Validation de données
- **vlucas/phpdotenv 5.5** - Variables d'environnement

### Base de Données
- **PostgreSQL 12+** (recommandé) ou **MySQL 5.7+**
- Support multi-driver via PDO
- Migration MySQL → PostgreSQL automatisée

### Frontend
- **HTML5**, **CSS3**, **JavaScript ES6+**
- **Chart.js 4.4.0** - Graphiques interactifs
- **Font Awesome 6.0** - Icons
- **jQuery 3.6.0** - DOM manipulation

### Sécurité
- **CSRF Protection** sur tous les formulaires
- **Password Hashing** avec bcrypt
- **Prepared Statements** pour toutes les requêtes SQL
- **Rate Limiting** sur l'authentification
- **Input Validation** multicouche

---

## 📦 Installation

### Prérequis

```bash
# PHP 7.4+ avec extensions
php -v
php -m | grep -E "pdo|pgsql|mbstring"

# PostgreSQL 12+
psql --version

# Composer
composer --version
```

### Installation Rapide

```bash
# 1. Cloner le repository
git clone https://github.com/votre-username/kitismart.git
cd kitismart

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env.example .env
nano .env  # Éditez vos credentials

# 4. Créer la base de données PostgreSQL
sudo -u postgres psql
CREATE DATABASE kiti;
CREATE USER kitiadmin WITH PASSWORD 'votre_password';
GRANT ALL PRIVILEGES ON DATABASE kiti TO kitiadmin;
\q

# 5. Démarrer le serveur de développement
cd public
php -S localhost:8090
```

Accédez à [http://localhost:8090](http://localhost:8090)

---

## ⚙️ Configuration

### Fichier .env

```env
# Mode d'application
APP_ENV=dev  # dev ou production

# Base de données (PostgreSQL recommandé)
DB_DRIVER=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_NAME=kiti
DB_USER=postgres
DB_PASS=votre_password_securise
DB_CHARSET=utf8mb4

# Email
MAIL_FROM=noreply@kitismart.com
MAIL_FROM_NAME=KitiSmart
APP_URL=http://localhost:8090

# SMTP (Mailtrap pour dev, votre SMTP pour prod)
SMTP_HOST=sandbox.smtp.mailtrap.io
SMTP_PORT=2525
SMTP_USERNAME=votre_username
SMTP_PASSWORD=votre_password
SMTP_ENCRYPTION=tls
```

### Basculer entre MySQL et PostgreSQL

Il suffit de changer la variable `DB_DRIVER` dans `.env` :

```env
DB_DRIVER=mysql   # Pour MySQL
DB_DRIVER=pgsql   # Pour PostgreSQL
```

---

## 🔄 Migration MySQL → PostgreSQL

Un script automatisé est fourni pour migrer toutes vos données :

```bash
# 1. Configurer les credentials dans migrate_to_postgresql.php
nano migrate_to_postgresql.php

# 2. Lancer la migration
php migrate_to_postgresql.php

# 3. Mettre à jour .env
DB_DRIVER=pgsql
```

📖 **Documentation complète** : [MIGRATION_POSTGRESQL.md](MIGRATION_POSTGRESQL.md)

---

## 📂 Structure du Projet

```
kitismart/
├── public/                    # Point d'entrée web
│   ├── index.php             # Bootstrap de l'application
│   └── assets/               # CSS, JS, Images
│       ├── css/
│       ├── js/
│       │   ├── auth/
│       │   └── dashboard/
│       │       └── charts.js # Graphiques Chart.js
│       └── images/
├── app/                       # Code applicatif
│   ├── controllers/          # Contrôleurs (9 fichiers)
│   │   ├── HomeController.php
│   │   ├── BudgetController.php
│   │   ├── ExpenseController.php
│   │   ├── SettingsController.php
│   │   ├── ExportController.php
│   │   └── ...
│   ├── models/               # Modèles (7 fichiers)
│   │   ├── User.php
│   │   ├── Budget.php
│   │   ├── Expense.php
│   │   ├── Categorie.php
│   │   └── ...
│   ├── views/                # Vues (15 fichiers)
│   │   ├── auth/            # Authentification
│   │   ├── dashboard/       # Dashboard & Expenses
│   │   ├── emails/          # Templates d'emails
│   │   └── layouts/         # Layouts globaux
│   ├── core/                 # Framework MVC
│   │   ├── Router.php
│   │   ├── Controller.php
│   │   ├── Database.php
│   │   └── Config.php
│   ├── Utils/                # Utilitaires
│   │   ├── Csrf.php
│   │   └── Mailer.php
│   ├── validators/           # Validateurs
│   ├── Exceptions/           # Exceptions personnalisées
│   └── routes.php            # Définition des routes
├── vendor/                    # Dépendances Composer
├── .env                       # Configuration (NE PAS VERSIONNER)
├── .env.example              # Template de configuration
├── composer.json             # Dépendances PHP
├── migrate_to_postgresql.php # Script de migration
├── MIGRATION_POSTGRESQL.md   # Guide de migration
└── README.md                 # Ce fichier
```

---

## 🚀 Utilisation

### 1. Création de Compte

1. Accédez à `/register`
2. Remplissez le formulaire
3. Confirmez votre email (lien valide 20 minutes)

### 2. Premier Budget

1. Connectez-vous
2. Créez votre premier budget
3. Définissez le montant initial

### 3. Ajout de Dépenses

1. Dashboard → "Nouvelle Dépense"
2. Choisissez une catégorie (ou créez-en une)
3. Renseignez le montant et la description
4. Statut : En attente ou Payé

### 4. Visualisations

Le dashboard affiche automatiquement :
- 📊 Graphique de progression du budget
- 🥧 Répartition par catégorie
- 📈 Dépenses par type
- ⚠️ Alertes intelligentes

### 5. Export de Rapports

- **CSV** : Dashboard → "Export CSV" (compatible Excel)
- **PDF** : Dashboard → "Export PDF" (imprimable)

---

## 🔐 Sécurité

### Bonnes Pratiques Implémentées

✅ **Authentification**
- Mots de passe hashés avec `password_hash()` (bcrypt)
- Tokens de confirmation/réinitialisation sécurisés
- Expiration automatique des tokens

✅ **Protection CSRF**
- Token unique par session
- Validation sur tous les formulaires POST

✅ **Injection SQL**
- Requêtes préparées (PDO Prepared Statements)
- RedBeanPHP ORM avec paramètres bindés

✅ **XSS**
- `htmlspecialchars()` sur toutes les sorties
- Validation des entrées utilisateur

✅ **Session**
- Cookie HTTPOnly et Secure (production)
- Régénération d'ID après authentification

### Configuration Production

```php
// public/index.php
if ($_ENV['APP_ENV'] === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
    R::freeze(true); // Freeze RedBean schema
}
```

---

## 📊 Schéma de la Base de Données

```sql
┌─────────────┐
│   users     │
├─────────────┤
│ id (PK)     │
│ nom         │
│ email       │
│ password    │
│ status      │
│ created_at  │
└──────┬──────┘
       │
       │ 1:N
       │
┌──────▼──────┐
│   budget    │
├─────────────┤
│ id (PK)     │
│ user_id(FK) │
│ start_date  │
│ end_date    │
│ initial_amt │
│ remain_amt  │
│ status      │
└──────┬──────┘
       │
       │ 1:N
       │
┌──────▼──────────┐     ┌──────────────┐
│   categorie     │     │   expense    │
├─────────────────┤     ├──────────────┤
│ id (PK)         │ 1:N │ id (PK)      │
│ type            │◄────┤ budget_id(FK)│
│ name            │     │ categorie_id │
│ budget_id (FK)  │     │ amount       │
└─────────────────┘     │ payment_date │
                        │ description  │
                        │ status       │
                        │ is_fixed     │
                        └──────────────┘
```

---

## 🧪 Tests

### Tests Manuels

```bash
# Tester l'inscription
curl -X POST http://localhost:8090/register \
  -d "nom=Test User" \
  -d "email=test@example.com" \
  -d "password=Test1234"

# Tester la connexion
curl -X POST http://localhost:8090/login \
  -d "email=test@example.com" \
  -d "password=Test1234"
```

### Checklist de Test

- [ ] Inscription + Confirmation email
- [ ] Connexion / Déconnexion
- [ ] Création de budget
- [ ] Ajout de dépense
- [ ] Modification de dépense
- [ ] Marquage comme payé
- [ ] Export CSV
- [ ] Export PDF
- [ ] Modification profil
- [ ] Changement mot de passe
- [ ] Visualisations Chart.js

---

## 🐛 Dépannage

### Erreur de connexion à la base de données

```bash
# Vérifier que PostgreSQL est démarré
sudo service postgresql status

# Vérifier les credentials
psql -U postgres -d kiti
```

### Les graphiques ne s'affichent pas

```bash
# Vérifier que Chart.js est chargé
# Ouvrir la console navigateur (F12)
# Vérifier les erreurs JavaScript
```

### Emails non envoyés

```bash
# Vérifier les logs PHP
tail -f /var/log/php/error.log

# Vérifier la config SMTP dans .env
```

---

## 🤝 Contribution

Les contributions sont les bienvenues ! Voici comment contribuer :

1. **Fork** le projet
2. Créez une branche (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add AmazingFeature'`)
4. Pushez vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une **Pull Request**

### Guidelines

- Suivre le style de code existant
- Typage strict PHP (`declare(strict_types=1)`)
- Commenter le code complexe
- Tester avant de commit

---

## 📝 Roadmap

### Version 2.0 (À venir)

- [ ] API REST pour applications mobiles
- [ ] Notifications push
- [ ] Budgets partagés (famille/équipe)
- [ ] Prévisions basées sur IA
- [ ] Import bancaire automatique (OFX/QIF)
- [ ] Multi-devises
- [ ] Dark mode
- [ ] Application mobile (React Native)

### Version 1.1 (En cours)

- [x] Dashboard avec graphiques Chart.js
- [x] Export PDF des rapports
- [x] Module Paramètres complet
- [x] Migration PostgreSQL
- [x] Alertes budget intelligentes

---

## 📜 Licence

Ce projet est sous licence **MIT**. Consultez le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 👤 Auteur

**KitiSmart Team**

- GitHub: [@votre-username](https://github.com/votre-username)
- Email: contact@kitismart.com

---

## 🙏 Remerciements

- [RedBeanPHP](https://redbeanphp.com/) - ORM excellent et simple
- [Chart.js](https://www.chartjs.org/) - Graphiques magnifiques
- [AltoRouter](https://altorouter.com/) - Routeur PHP rapide
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) - Envoi d'emails
- [Font Awesome](https://fontawesome.com/) - Icons

---

<div align="center">

**⭐ Si ce projet vous est utile, n'hésitez pas à lui donner une étoile ! ⭐**

Made with ❤️ by KitiSmart Team

</div>
