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
