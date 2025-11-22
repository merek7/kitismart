# Migrations Base de Données

Ce dossier contient les migrations SQL pour la base de données KitiSmart.

## Structure

Les fichiers de migration sont numérotés séquentiellement :
- `001_*.sql` - Migration initiale
- `002_*.sql` - Deuxième migration
- `003_*.sql` - Troisième migration
- etc.

## Utilisation

### Avec RedBeanPHP (Mode Freeze)

Ce projet utilise RedBeanPHP qui peut créer automatiquement les tables en mode "fluid".
Pour passer en mode "freeze" (production), les migrations doivent être exécutées manuellement.

### Exécution manuelle

Pour exécuter une migration :

```bash
mysql -u username -p database_name < migrations/003_create_notification_settings.sql
```

Ou via phpMyAdmin en important le fichier SQL.

## Migrations Disponibles

### 001_create_custom_categories.sql
Crée la table `customcategory` pour les catégories personnalisées des utilisateurs.

**Colonnes:**
- `user_id` - ID de l'utilisateur (FK vers table user)
- `name` - Nom de la catégorie (VARCHAR 100)
- `icon` - Classe FontAwesome pour l'icône (par défaut: 'fa-tag')
- `color` - Couleur hexadécimale (par défaut: '#0d9488')
- `description` - Description optionnelle de la catégorie
- `is_active` - Statut actif/archivé (1 = actif, 0 = supprimé)
- `created_at` - Date de création
- `updated_at` - Date de dernière modification

### 002_create_budget_history.sql
Crée la table `budgethistory` pour l'historique des budgets archivés par mois.

**Colonnes:**
- `user_id` - ID de l'utilisateur (FK vers table user)
- `original_budget_id` - ID du budget original (optionnel)
- `month` - Mois du budget (1-12)
- `year` - Année du budget
- `total_budget` - Montant total du budget
- `total_spent` - Total dépensé
- `total_remaining` - Reste du budget
- `expenses_count` - Nombre de dépenses
- `archived_at` - Date d'archivage
- `notes` - Notes optionnelles

### 003_create_notification_settings.sql
Crée la table `notificationsettings` pour gérer les préférences de notifications des utilisateurs.

**Colonnes:**
- `user_id` - ID de l'utilisateur (FK vers table user)
- `budget_alert_80` - Alerte à 80% du budget (activée par défaut)
- `budget_alert_100` - Alerte à 100% du budget (activée par défaut)
- `expense_alert_threshold` - Seuil d'alerte pour dépenses importantes (50 000 FCFA par défaut)
- `expense_alert_enabled` - Activer les alertes de dépenses
- `monthly_summary` - Récapitulatif mensuel activé
- `summary_day` - Jour du mois pour envoyer le récapitulatif (1-28)
- `email_enabled` - Activer/désactiver toutes les notifications email

## Notes

- RedBeanPHP crée automatiquement les tables en mode "fluid"
- Ces migrations sont fournies pour référence et pour environnements de production
- Toujours tester les migrations dans un environnement de développement d'abord
