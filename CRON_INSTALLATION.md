# 📘 Guide d'Installation du Cron Job - KitiSmart

Ce guide couvre l'installation du script `process_recurrences.php` sur **tous les environnements** : Windows, Linux, Mac, et serveurs de production.

---

## 🪟 WINDOWS (Développement Local)

### Pourquoi Windows n'a pas crontab ?

Windows n'utilise pas `cron`, mais un outil équivalent : **Task Scheduler (Planificateur de tâches)**.

---

### Option 1 : Exécution Manuelle (Recommandé pour le développement)

C'est la méthode la plus simple pour tester les récurrences en développement.

```bash
# Ouvrir PowerShell ou CMD dans le dossier du projet
cd C:\Users\VotreNom\kitismart

# Exécuter le script
php cron/process_recurrences.php
```

**Avantages :**
- ✅ Contrôle total
- ✅ Voir les logs en direct
- ✅ Idéal pour tester

**Quand l'utiliser :** À chaque fois que tu veux créer les dépenses récurrentes en dev

---

### Option 2 : Task Scheduler Windows (Exécution automatique)

Pour automatiser l'exécution, même en développement local Windows.

#### Étape 1 : Ouvrir le Planificateur de tâches

1. Appuyer sur `Windows + R`
2. Taper : `taskschd.msc`
3. Appuyer sur `Entrée`

#### Étape 2 : Créer une nouvelle tâche

1. Dans le menu de droite, cliquer sur **"Créer une tâche..."**
2. Onglet **Général** :
   - Nom : `KitiSmart - Récurrences`
   - Description : `Traitement automatique des dépenses récurrentes`
   - Cocher : **"Exécuter même si l'utilisateur n'est pas connecté"** (optionnel)

#### Étape 3 : Configurer le déclencheur

1. Onglet **Déclencheurs** → Cliquer sur **"Nouveau..."**
2. Configuration pour exécution quotidienne :
   - **Lancer la tâche :** Selon une planification
   - **Paramètres :** Quotidienne
   - **Répéter tous les :** 1 jour
   - **Démarrer le :** Aujourd'hui
   - **À :** `02:00:00` (2h du matin)
   - Cocher : **"Activée"**
   - Cliquer sur **"OK"**

#### Étape 4 : Configurer l'action

1. Onglet **Actions** → Cliquer sur **"Nouveau..."**
2. Configuration :
   - **Action :** Démarrer un programme
   - **Programme/script :** `C:\php\php.exe` (chemin de PHP sur votre système)
   - **Ajouter des arguments :** `C:\Users\VotreNom\kitismart\cron\process_recurrences.php`
   - **Commencer dans :** `C:\Users\VotreNom\kitismart`
   - Cliquer sur **"OK"**

#### Étape 5 : Paramètres avancés

1. Onglet **Paramètres** :
   - Cocher : **"Autoriser la tâche à être exécutée à la demande"**
   - Cocher : **"Exécuter la tâche dès que possible après un démarrage planifié manqué"**
   - Cliquer sur **"OK"**

#### Étape 6 : Tester la tâche

1. Dans la liste des tâches, faire un **clic droit** sur `KitiSmart - Récurrences`
2. Cliquer sur **"Exécuter"**
3. Vérifier les logs dans `C:\kitismart\logs\recurrences.log` (si configuré)

---

### Trouver le chemin de PHP sur Windows

```bash
# Dans PowerShell ou CMD
where php

# Résultat attendu (exemple) :
# C:\php\php.exe
# OU
# C:\xampp\php\php.exe
# OU
# C:\laragon\bin\php\php-8.1\php.exe
```

Utiliser ce chemin dans le Task Scheduler.

---

### Logs sur Windows

#### Créer un fichier de log

```bash
# Dans PowerShell (en tant qu'administrateur)
New-Item -Path "C:\kitismart\logs" -ItemType Directory -Force
New-Item -Path "C:\kitismart\logs\recurrences.log" -ItemType File -Force
```

#### Modifier l'action du Task Scheduler pour logger

**Arguments :**
```
C:\Users\VotreNom\kitismart\cron\process_recurrences.php >> C:\kitismart\logs\recurrences.log 2>&1
```

#### Consulter les logs

```bash
# Dans PowerShell
Get-Content C:\kitismart\logs\recurrences.log -Tail 50
```

---

## 🐧 LINUX / MAC (Développement & Production)

### Option 1 : Exécution Manuelle

```bash
# Aller dans le dossier du projet
cd /home/user/kitismart

# Exécuter le script
php cron/process_recurrences.php
```

---

### Option 2 : Cron Job (Automatique)

#### Étape 1 : Créer le fichier de log

```bash
# Créer le fichier
sudo touch /var/log/kitismart_cron.log

# Permissions
sudo chown $USER:$USER /var/log/kitismart_cron.log
sudo chmod 664 /var/log/kitismart_cron.log
```

#### Étape 2 : Ouvrir crontab

```bash
# Pour l'utilisateur courant (développement)
crontab -e

# OU pour www-data (production)
sudo crontab -u www-data -e
```

#### Étape 3 : Ajouter la ligne cron

**Pour développement (test toutes les heures) :**
```cron
0 * * * * /usr/bin/php /home/user/kitismart/cron/process_recurrences.php >> /var/log/kitismart_cron.log 2>&1
```

**Pour production (quotidien à 2h) :**
```cron
0 2 * * * /usr/bin/php /var/www/kitismart/cron/process_recurrences.php >> /var/log/kitismart_cron.log 2>&1
```

#### Étape 4 : Vérifier l'installation

```bash
# Lister les cron jobs
crontab -l

# OU pour www-data
sudo crontab -u www-data -l
```

#### Étape 5 : Consulter les logs

```bash
# Afficher les 50 dernières lignes
tail -n 50 /var/log/kitismart_cron.log

# Suivre en temps réel
tail -f /var/log/kitismart_cron.log

# Filtrer les erreurs
grep "❌" /var/log/kitismart_cron.log

# Filtrer les succès
grep "✅" /var/log/kitismart_cron.log
```

---

## 🚀 SERVEUR DE PRODUCTION

### Prérequis

1. Accès SSH au serveur
2. Permissions sudo (ou accès utilisateur www-data)
3. PHP installé et accessible en ligne de commande

---

### Installation Complète

#### Étape 1 : Connexion SSH

```bash
ssh user@votre-serveur.com
```

#### Étape 2 : Vérifier PHP

```bash
# Trouver le chemin de PHP
which php
# Résultat attendu : /usr/bin/php

# Vérifier la version
php -v
# Doit être >= 7.4
```

#### Étape 3 : Créer le fichier de log

```bash
sudo touch /var/log/kitismart_cron.log
sudo chown www-data:www-data /var/log/kitismart_cron.log
sudo chmod 664 /var/log/kitismart_cron.log
```

#### Étape 4 : Tester le script manuellement

```bash
# Se placer dans le dossier du projet
cd /var/www/kitismart

# Exécuter en tant que www-data
sudo -u www-data php cron/process_recurrences.php
```

**Output attendu :**
```
[2025-11-20 14:30:00] ✅ Connexion BD réussie
[2025-11-20 14:30:00] 🔄 Début du traitement des récurrences...
[2025-11-20 14:30:00] 📋 0 récurrence(s) à traiter
[2025-11-20 14:30:00] ℹ️  Aucune récurrence à exécuter aujourd'hui
```

#### Étape 5 : Installer le cron

```bash
# Ouvrir crontab pour www-data
sudo crontab -u www-data -e

# Si c'est la première fois, choisir un éditeur (nano est le plus simple)
# Taper : 1 (pour nano)
```

**Ajouter cette ligne à la fin du fichier :**
```cron
# KitiSmart - Traitement des dépenses récurrentes (quotidien à 2h)
0 2 * * * /usr/bin/php /var/www/kitismart/cron/process_recurrences.php >> /var/log/kitismart_cron.log 2>&1
```

**Sauvegarder et quitter :**
- Appuyer sur `Ctrl + O` (sauvegarder)
- Appuyer sur `Entrée` (confirmer)
- Appuyer sur `Ctrl + X` (quitter)

#### Étape 6 : Vérifier l'installation

```bash
# Lister les cron jobs de www-data
sudo crontab -u www-data -l

# Vérifier que le service cron est actif
sudo systemctl status cron
```

#### Étape 7 : Surveiller les logs

```bash
# Logs en temps réel
sudo tail -f /var/log/kitismart_cron.log

# Logs système du cron
grep CRON /var/log/syslog | grep kitismart
```

---

## 📋 SYNTAXE CRON (Référence)

```
* * * * * commande
│ │ │ │ │
│ │ │ │ └─── Jour de la semaine (0-6, 0=Dimanche)
│ │ │ └───── Mois (1-12)
│ │ └─────── Jour du mois (1-31)
│ └───────── Heure (0-23)
└─────────── Minute (0-59)
```

### Exemples de fréquences

| Fréquence | Syntaxe | Description |
|-----------|---------|-------------|
| Toutes les heures | `0 * * * *` | À la minute 0 de chaque heure |
| Toutes les 6 heures | `0 */6 * * *` | À 0h, 6h, 12h, 18h |
| Quotidien à 2h | `0 2 * * *` | Tous les jours à 2h00 |
| Quotidien à 23h | `0 23 * * *` | Tous les jours à 23h00 |
| Le 1er de chaque mois | `0 2 1 * *` | 1er jour à 2h00 |
| Tous les lundis | `0 2 * * 1` | Chaque lundi à 2h00 |

---

## 🔍 DÉPANNAGE

### Problème : Le cron ne s'exécute pas

#### Sur Windows

1. **Vérifier la tâche :**
   - Ouvrir Task Scheduler
   - Vérifier que la tâche est **Activée**
   - Vérifier l'onglet **Historique** pour voir les exécutions

2. **Tester manuellement :**
   ```bash
   php C:\Users\VotreNom\kitismart\cron\process_recurrences.php
   ```

3. **Vérifier le chemin de PHP :**
   ```bash
   where php
   ```

#### Sur Linux/Mac

1. **Vérifier que cron est actif :**
   ```bash
   sudo systemctl status cron
   # Si inactif :
   sudo systemctl start cron
   ```

2. **Vérifier les permissions :**
   ```bash
   ls -l cron/process_recurrences.php
   # Doit être exécutable (x)
   chmod +x cron/process_recurrences.php
   ```

3. **Tester manuellement :**
   ```bash
   sudo -u www-data php cron/process_recurrences.php
   ```

4. **Consulter les logs système :**
   ```bash
   grep CRON /var/log/syslog | tail -20
   ```

---

### Problème : Erreur de connexion à la base de données

1. **Vérifier que `.env` est accessible :**
   ```bash
   ls -la /var/www/kitismart/.env
   # Permissions : -rw-r--r--
   ```

2. **Vérifier les variables d'environnement :**
   ```bash
   cat /var/www/kitismart/.env | grep DB_
   ```

3. **Tester la connexion manuellement :**
   ```bash
   php -r "require 'vendor/autoload.php'; \
           \$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); \
           \$dotenv->load(); \
           echo getenv('DB_HOST');"
   ```

---

### Problème : Aucune récurrence traitée

1. **Vérifier qu'il y a des récurrences actives :**
   - Aller sur `/expenses/recurrences`
   - Vérifier que des récurrences ont le badge "Active"

2. **Vérifier la date d'exécution :**
   - Les récurrences ne s'exécutent que si `next_execution_date <= aujourd'hui`
   - Attendre la date prévue

3. **Forcer une exécution en changeant la date :**
   ```sql
   -- Dans la base de données
   UPDATE expenserecurrence
   SET next_execution_date = CURRENT_DATE
   WHERE id = 1;
   ```

---

## 📧 ALERTES PAR EMAIL (Optionnel)

### Sur Linux (avec mail installé)

```bash
# Installer mailutils
sudo apt-get install mailutils

# Modifier crontab
sudo crontab -u www-data -e

# Ajouter en haut :
MAILTO=admin@example.com

# Le cron enverra un email en cas d'erreur (exit code ≠ 0)
```

---

## 🔄 ROTATION DES LOGS (Optionnel)

Pour éviter que les logs deviennent trop volumineux.

### Sur Linux

Créer `/etc/logrotate.d/kitismart` :

```bash
sudo nano /etc/logrotate.d/kitismart
```

Contenu :
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

Tester :
```bash
sudo logrotate -f /etc/logrotate.d/kitismart
```

---

## ✅ CHECKLIST FINALE

### Développement (Windows)

- [ ] PHP installé et accessible
- [ ] Tester manuellement : `php cron/process_recurrences.php`
- [ ] (Optionnel) Configurer Task Scheduler

### Développement (Linux/Mac)

- [ ] Créer fichier log : `/var/log/kitismart_cron.log`
- [ ] Permissions : `chmod 664 /var/log/kitismart_cron.log`
- [ ] Ajouter cron : `crontab -e`
- [ ] Vérifier : `crontab -l`

### Production

- [ ] Connexion SSH
- [ ] Créer log : `sudo touch /var/log/kitismart_cron.log`
- [ ] Permissions : `sudo chown www-data:www-data /var/log/kitismart_cron.log`
- [ ] Tester : `sudo -u www-data php cron/process_recurrences.php`
- [ ] Installer cron : `sudo crontab -u www-data -e`
- [ ] Vérifier : `sudo crontab -u www-data -l`
- [ ] Surveiller : `sudo tail -f /var/log/kitismart_cron.log`

---

## 📞 SUPPORT

En cas de problème persistant :

1. **Vérifier les logs :**
   - Windows : `C:\kitismart\logs\recurrences.log`
   - Linux : `/var/log/kitismart_cron.log`

2. **Tester manuellement :**
   ```bash
   php cron/process_recurrences.php
   ```

3. **Vérifier la base de données :**
   - Table `expenserecurrence` existe ?
   - Il y a des récurrences actives ?
   - Les dates `next_execution_date` sont correctes ?

4. **Consulter les logs système :**
   - Windows : Event Viewer → Task Scheduler
   - Linux : `/var/log/syslog`

---

## 🎯 RÉSUMÉ RAPIDE

| Environnement | Commande |
|---------------|----------|
| **Windows (Manuel)** | `php cron/process_recurrences.php` |
| **Windows (Auto)** | Task Scheduler → Créer tâche |
| **Linux/Mac (Dev)** | `crontab -e` → Ajouter ligne |
| **Production** | `sudo crontab -u www-data -e` |
| **Vérifier cron** | `crontab -l` ou `sudo crontab -u www-data -l` |
| **Voir logs** | `tail -f /var/log/kitismart_cron.log` |

---

**Dernière mise à jour :** 2025-11-20
