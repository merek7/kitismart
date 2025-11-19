# 🔄 Guide de Migration MySQL vers PostgreSQL - KitiSmart

Ce guide vous accompagne dans la migration complète de votre application KitiSmart de MySQL vers PostgreSQL.

## 📋 Table des matières

1. [Prérequis](#prérequis)
2. [Étape 1: Installation de PostgreSQL](#étape-1-installation-de-postgresql)
3. [Étape 2: Création de la base de données](#étape-2-création-de-la-base-de-données)
4. [Étape 3: Configuration de l'application](#étape-3-configuration-de-lapplication)
5. [Étape 4: Migration des données](#étape-4-migration-des-données)
6. [Étape 5: Tests et validation](#étape-5-tests-et-validation)
7. [Retour en arrière](#retour-en-arrière)
8. [FAQ](#faq)

---

## ✅ Prérequis

- **PostgreSQL 12+** installé sur votre système
- **PHP PDO PostgreSQL** extension activée
- **Accès root/sudo** pour les commandes système
- **Sauvegarde complète** de votre base MySQL actuelle

### Vérification des prérequis

```bash
# Vérifier PostgreSQL
psql --version

# Vérifier l'extension PHP PDO PostgreSQL
php -m | grep pdo_pgsql

# Vérifier que le serveur PostgreSQL est démarré
sudo service postgresql status
```

---

## 🔧 Étape 1: Installation de PostgreSQL

### Sur Ubuntu/Debian

```bash
# Mettre à jour les paquets
sudo apt update

# Installer PostgreSQL
sudo apt install postgresql postgresql-contrib

# Démarrer le service
sudo service postgresql start

# Vérifier le statut
sudo service postgresql status
```

### Sur macOS (avec Homebrew)

```bash
brew install postgresql
brew services start postgresql
```

### Sur Windows

Téléchargez l'installateur depuis [postgresql.org](https://www.postgresql.org/download/windows/)

---

## 🗄️ Étape 2: Création de la base de données

### Option A: Utiliser l'utilisateur postgres par défaut

```bash
# Se connecter à PostgreSQL
sudo -u postgres psql

# Dans le prompt psql:
CREATE DATABASE kiti;

# Optionnel: Créer un utilisateur dédié
CREATE USER kitiadmin WITH PASSWORD 'votre_mot_de_passe_securise';
GRANT ALL PRIVILEGES ON DATABASE kiti TO kitiadmin;

# Quitter psql
\q
```

### Option B: Utiliser pgAdmin

1. Ouvrez pgAdmin
2. Créez une nouvelle base de données nommée `kiti`
3. Configurez l'encodage UTF8

---

## ⚙️ Étape 3: Configuration de l'application

### 3.1 Sauvegarder votre configuration actuelle

```bash
cd /home/user/kitismart
cp .env .env.mysql.backup
```

### 3.2 Mettre à jour le fichier `.env`

Le fichier `.env` a déjà été mis à jour avec les paramètres PostgreSQL:

```env
APP_ENV=dev

# Database Configuration
DB_DRIVER=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_NAME=kiti
DB_USER=postgres
DB_PASS=votre_mot_de_passe  # ⚠️ Remplacez par votre mot de passe PostgreSQL
DB_CHARSET=utf8mb4
```

**⚠️ IMPORTANT:** Modifiez `DB_PASS` avec votre mot de passe PostgreSQL réel.

### 3.3 Vérification de la configuration

Les fichiers suivants ont été mis à jour pour supporter PostgreSQL:

- ✅ `app/core/Database.php` - Support multi-driver (MySQL/PostgreSQL)
- ✅ `.env` - Configuration PostgreSQL
- ✅ `.env.example` - Exemple de configuration

---

## 🚀 Étape 4: Migration des données

### 4.1 Préparer le script de migration

Le script `migrate_to_postgresql.php` a été créé à la racine du projet.

**Avant de lancer la migration:**

1. Ouvrez `migrate_to_postgresql.php`
2. Vérifiez et modifiez les configurations si nécessaire:

```php
// Configuration MySQL source
$mysqlConfig = [
    'host' => 'localhost',
    'port' => '3306',
    'dbname' => 'kiti',
    'user' => 'admin',
    'pass' => ''  // Votre mot de passe MySQL
];

// Configuration PostgreSQL destination
$postgresConfig = [
    'host' => 'localhost',
    'port' => '5432',
    'dbname' => 'kiti',
    'user' => 'postgres',
    'pass' => ''  // ⚠️ REMPLACEZ PAR VOTRE MOT DE PASSE PostgreSQL
];
```

### 4.2 Lancer la migration

```bash
cd /home/user/kitismart
php migrate_to_postgresql.php
```

### 4.3 Ce que fait le script

Le script effectue automatiquement:

1. ✅ Connexion aux deux bases de données (MySQL et PostgreSQL)
2. ✅ Vérification des tables existantes
3. ✅ Migration des données dans l'ordre correct (respect des clés étrangères):
   - `users`
   - `useraudit`
   - `budget`
   - `categorie`
   - `expense`
   - `expense_audit`
4. ✅ Mise à jour des séquences PostgreSQL
5. ✅ Affichage des statistiques

### 4.4 Sortie attendue

```
=================================================
  MIGRATION MYSQL vers POSTGRESQL - KitiSmart
=================================================

📡 Connexion à MySQL...
✅ Connecté à MySQL

📡 Connexion à PostgreSQL...
✅ Connecté à PostgreSQL

🚀 Début de la migration des données...

📋 Migration de la table 'users'...
   📊 Nombre de lignes à migrer: 15
   ✅ 15 lignes migrées avec succès

📋 Migration de la table 'budget'...
   📊 Nombre de lignes à migrer: 23
   ✅ 23 lignes migrées avec succès

[...]

=================================================
  ✅ MIGRATION TERMINÉE AVEC SUCCÈS!
=================================================
```

---

## ✅ Étape 5: Tests et validation

### 5.1 Démarrer l'application avec PostgreSQL

```bash
cd /home/user/kitismart/public
php -S localhost:8090
```

### 5.2 Checklist de validation

Testez les fonctionnalités suivantes:

- [ ] **Authentification**
  - [ ] Connexion avec un compte existant
  - [ ] Inscription d'un nouveau compte
  - [ ] Confirmation par email
  - [ ] Réinitialisation de mot de passe

- [ ] **Gestion des budgets**
  - [ ] Affichage des budgets existants
  - [ ] Création d'un nouveau budget
  - [ ] Clôture d'un budget

- [ ] **Gestion des dépenses**
  - [ ] Affichage de la liste des dépenses
  - [ ] Ajout d'une nouvelle dépense
  - [ ] Modification d'une dépense
  - [ ] Marquage comme payé
  - [ ] Suppression d'une dépense

- [ ] **Dashboard**
  - [ ] Affichage des statistiques
  - [ ] Calcul du solde restant
  - [ ] Répartition par catégories

### 5.3 Vérification des données dans PostgreSQL

```bash
# Se connecter à PostgreSQL
psql -U postgres -d kiti

# Dans psql, vérifier les tables:
\dt

# Vérifier le nombre de lignes dans chaque table:
SELECT 'users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'budget', COUNT(*) FROM budget
UNION ALL
SELECT 'expense', COUNT(*) FROM expense
UNION ALL
SELECT 'categorie', COUNT(*) FROM categorie;

# Quitter
\q
```

### 5.4 Vérification des logs

```bash
# Vérifier les logs d'erreur PHP
tail -f /var/log/php/error.log

# Si vous utilisez le serveur PHP intégré
# Les erreurs s'afficheront directement dans le terminal
```

---

## 🔙 Retour en arrière

Si vous rencontrez des problèmes et souhaitez revenir à MySQL:

### 1. Restaurer la configuration

```bash
cd /home/user/kitismart
cp .env.mysql.backup .env
```

### 2. Modifier le driver dans `.env`

```env
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=kiti
DB_USER=admin
DB_PASS=
```

### 3. Redémarrer l'application

```bash
cd /home/user/kitismart/public
php -S localhost:8090
```

---

## ❓ FAQ

### Q: Puis-je utiliser les deux bases de données en parallèle?

**R:** Oui! Le code supporte maintenant les deux. Il suffit de changer `DB_DRIVER` dans `.env` entre `pgsql` et `mysql`.

### Q: Mes données MySQL seront-elles supprimées?

**R:** Non, la migration copie les données. Votre base MySQL reste intacte.

### Q: RedBeanPHP fonctionne-t-il avec PostgreSQL?

**R:** Oui, RedBeanPHP supporte parfaitement PostgreSQL. Le mode "unfrozen" créera automatiquement les tables si nécessaire.

### Q: Que faire si la migration échoue?

**R:**
1. Vérifiez que PostgreSQL est démarré
2. Vérifiez vos credentials dans `migrate_to_postgresql.php`
3. Vérifiez que la base `kiti` existe dans PostgreSQL
4. Consultez les messages d'erreur détaillés du script

### Q: Comment vérifier que PostgreSQL est bien utilisé?

**R:** Ajoutez temporairement dans votre code:

```php
// Dans public/index.php après la connexion DB
error_log("DB Driver: " . $_ENV['DB_DRIVER']);
```

Ou vérifiez dans `psql`:

```sql
SELECT pid, usename, application_name, client_addr
FROM pg_stat_activity
WHERE datname = 'kiti';
```

### Q: Les performances sont-elles différentes?

**R:** PostgreSQL offre généralement de meilleures performances pour les requêtes complexes et une meilleure conformité SQL. Vous devriez constater des améliorations.

### Q: Dois-je modifier mon code applicatif?

**R:** Non! Grâce à RedBeanPHP et PDO, aucune modification du code applicatif n'est nécessaire. L'abstraction de la base de données est gérée automatiquement.

---

## 🎯 Avantages de PostgreSQL

✅ **Conformité SQL** - Meilleure adhérence aux standards SQL
✅ **Types de données avancés** - JSON, Array, UUID natifs
✅ **Performances** - Optimisations pour les requêtes complexes
✅ **Transactions robustes** - MVCC (Multi-Version Concurrency Control)
✅ **Extensions puissantes** - PostGIS, pg_trgm, etc.
✅ **Open source réel** - Licence MIT, pas de versions commerciales
✅ **Communauté active** - Support et documentation excellents

---

## 📞 Support

Si vous rencontrez des problèmes:

1. Vérifiez les logs PostgreSQL: `/var/log/postgresql/`
2. Vérifiez les logs PHP
3. Consultez la documentation PostgreSQL: https://www.postgresql.org/docs/
4. Documentation RedBeanPHP: https://redbeanphp.com/

---

## 🏁 Conclusion

Félicitations! Vous avez migré KitiSmart vers PostgreSQL. Votre application bénéficie maintenant d'une base de données plus robuste et performante.

**Prochaines étapes recommandées:**

- [ ] Configurer les sauvegardes automatiques PostgreSQL
- [ ] Optimiser les index (RedBeanPHP les crée automatiquement)
- [ ] Surveiller les performances avec `pg_stat_statements`
- [ ] Configurer `postgresql.conf` pour la production

Bonne continuation! 🚀
