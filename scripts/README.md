# Scripts KitiSmart

Ce dossier contient les scripts CLI et cron pour KitiSmart.

## execute_recurrences.php

**Description:** Exécute automatiquement les dépenses récurrences dues

**Usage manuel:**
```bash
cd /path/to/kitismart
php scripts/execute_recurrences.php
```

**Configuration CRON (recommandé):**

Exécuter quotidiennement à 6h du matin:
```bash
crontab -e
```

Ajouter cette ligne:
```
0 6 * * * cd /path/to/kitismart && php scripts/execute_recurrences.php >> /var/log/kitismart_recurrences.log 2>&1
```

**Fonctionnement:**
1. Récupère toutes les récurrences actives avec `next_execution_date <= aujourd'hui`
2. Pour chaque récurrence:
   - Crée une dépense dans le budget
   - Met à jour `last_execution_date`
   - Calcule et enregistre `next_execution_date`
3. Affiche un résumé (succès/erreurs)

**Fréquences supportées:**
- `daily` - Quotidien (+1 jour)
- `weekly` - Hebdomadaire (+7 jours)
- `bimonthly` - Bimensuel (+15 jours)
- `monthly` - Mensuel (+1 mois)
- `yearly` - Annuel (+1 an)

**Logs:**
Le script utilise `error_log()` et affiche les résultats dans stdout.
Pour logger dans un fichier, utiliser la redirection cron ci-dessus.

**Code de sortie:**
- `0` - Succès (toutes récurrences exécutées)
- `1` - Échec (une ou plusieurs erreurs)

## Développement

Pour tester en local sans attendre le cron:
```bash
# Créer une récurrence avec next_execution_date = aujourd'hui
# Puis exécuter:
php scripts/execute_recurrences.php
```

## Production

Vérifier que le cron tourne:
```bash
crontab -l
```

Consulter les logs:
```bash
tail -f /var/log/kitismart_recurrences.log
```
