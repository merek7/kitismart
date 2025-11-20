# Cron Jobs - KitiSmart

## Script `process_recurrences.php`

Ce script traite automatiquement les dépenses récurrentes dont la date d'exécution est arrivée.

### Fonctionnement

1. Récupère toutes les récurrences actives dont `next_execution_date <= aujourd'hui`
2. Pour chaque récurrence :
   - Crée une dépense correspondante
   - Met à jour `last_execution_date`
   - Calcule et met à jour `next_execution_date`
3. Génère un rapport avec statistiques (succès/erreurs)

### Installation du Cron Job

#### 1. Ouvrir la configuration crontab

```bash
crontab -e
```

#### 2. Ajouter l'une des configurations suivantes

**Option A : Exécution quotidienne à 2h du matin (recommandé)**
```cron
0 2 * * * /usr/bin/php /path/to/kitismart/cron/process_recurrences.php >> /var/log/kitismart_cron.log 2>&1
```

**Option B : Exécution toutes les 6 heures**
```cron
0 */6 * * * /usr/bin/php /path/to/kitismart/cron/process_recurrences.php >> /var/log/kitismart_cron.log 2>&1
```

**Option C : Exécution toutes les heures (pour tests)**
```cron
0 * * * * /usr/bin/php /path/to/kitismart/cron/process_recurrences.php >> /var/log/kitismart_cron.log 2>&1
```

⚠️ **Important:** Remplacer `/path/to/kitismart` par le chemin absolu du projet

#### 3. Vérifier le chemin de PHP

```bash
which php
# Output: /usr/bin/php (ou autre)
```

Utiliser le chemin retourné dans la commande cron.

### Logs

Les logs sont écrits dans `/var/log/kitismart_cron.log`

#### Créer le fichier de log (première fois)

```bash
sudo touch /var/log/kitismart_cron.log
sudo chown www-data:www-data /var/log/kitismart_cron.log
sudo chmod 664 /var/log/kitismart_cron.log
```

#### Consulter les logs

```bash
# Logs complets
cat /var/log/kitismart_cron.log

# 50 dernières lignes
tail -n 50 /var/log/kitismart_cron.log

# Suivre en temps réel
tail -f /var/log/kitismart_cron.log

# Filtrer les erreurs
grep "❌" /var/log/kitismart_cron.log

# Filtrer les succès
grep "✅" /var/log/kitismart_cron.log
```

### Test Manuel

Pour tester le script sans attendre le cron :

```bash
cd /path/to/kitismart
php cron/process_recurrences.php
```

**Output attendu :**
```
[2025-11-20 14:30:00] ✅ Connexion BD réussie
[2025-11-20 14:30:00] 🔄 Début du traitement des récurrences...
[2025-11-20 14:30:00] 📋 3 récurrence(s) à traiter

[2025-11-20 14:30:00] 🔄 Traitement récurrence #1: Loyer
   - Montant: 150000.00 FCFA
   - Fréquence: monthly
   - Date prévue: 2025-11-20
   ✅ Dépense créée avec succès (ID: 42)
   📅 Prochaine exécution: 2025-12-20

...

============================================================
[2025-11-20 14:30:00] 📊 RÉSUMÉ DU TRAITEMENT
   ✅ Succès: 3
   ❌ Erreurs: 0
   📝 Total traité: 3
============================================================
```

### Rotation des Logs (optionnel)

Pour éviter que les logs ne deviennent trop volumineux :

Créer `/etc/logrotate.d/kitismart` :

```
/var/log/kitismart_cron.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 664 www-data www-data
}
```

### Surveillance (Monitoring)

#### Vérifier si le cron s'exécute

```bash
# Logs système du cron
grep CRON /var/log/syslog | grep kitismart

# Dernière exécution
stat /var/log/kitismart_cron.log
```

#### Alertes par email (optionnel)

Ajouter `MAILTO` dans crontab :

```cron
MAILTO=admin@example.com

0 2 * * * /usr/bin/php /path/to/kitismart/cron/process_recurrences.php >> /var/log/kitismart_cron.log 2>&1
```

Le cron enverra un email en cas d'erreur (exit code ≠ 0).

### Dépannage

#### Le script ne s'exécute pas

1. Vérifier que le script est exécutable :
   ```bash
   chmod +x cron/process_recurrences.php
   ```

2. Vérifier les permissions du fichier :
   ```bash
   ls -l cron/process_recurrences.php
   ```

3. Tester le script manuellement :
   ```bash
   php cron/process_recurrences.php
   ```

4. Vérifier les logs cron :
   ```bash
   grep CRON /var/log/syslog | tail -20
   ```

#### Erreurs de connexion BD

- Vérifier que `.env` est accessible
- Vérifier les credentials PostgreSQL
- Vérifier que le serveur BD est démarré

#### Aucune récurrence traitée

- Vérifier que des récurrences actives existent
- Vérifier que `next_execution_date` est passée
- Consulter les logs pour plus de détails

### Fréquences Supportées

| Fréquence | Interval | Exemple |
|-----------|----------|---------|
| `daily` | Tous les jours | Quota journalier |
| `weekly` | Toutes les semaines | Courses hebdomadaires |
| `bimonthly` | Tous les 15 jours | Salaire bimensuel |
| `monthly` | Tous les mois | Loyer, abonnements |
| `yearly` | Tous les ans | Assurance annuelle |

### Architecture

```
Cron Job (quotidien à 2h)
    ↓
process_recurrences.php
    ↓
ExpenseRecurrence::getDueRecurrences()
    ↓
Pour chaque récurrence:
    ExpenseRecurrence::execute(id)
        ↓
        1. Expense::create() → Nouvelle dépense
        2. Mise à jour last_execution_date
        3. Calcul next_execution_date
    ↓
Rapport succès/erreurs
```

### Sécurité

✅ Le script utilise l'autoloader Composer
✅ Gestion complète des exceptions
✅ Logs détaillés pour audit
✅ Exit codes appropriés pour monitoring
✅ Permissions fichier restreintes (chmod 750)

### Support

En cas de problème :
1. Consulter les logs : `cat /var/log/kitismart_cron.log`
2. Tester manuellement : `php cron/process_recurrences.php`
3. Vérifier la BD : Tables `expenserecurrence` et `expense`
