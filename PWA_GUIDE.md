# Guide PWA - KitiSmart

## 🎯 Fonctionnalités PWA Implémentées

Votre application KitiSmart est maintenant une **Progressive Web App (PWA)** complète avec :

✅ **Mode Hors Ligne** - Les pages visitées restent accessibles sans connexion
✅ **Stockage Local** - Les dépenses et budgets sont sauvegardés localement (IndexedDB)
✅ **Synchronisation Auto** - Les données sont synchronisées automatiquement au retour en ligne
✅ **Cache Intelligent** - Les ressources statiques sont mises en cache
✅ **Notifications** - Alertes visuelles pour les actions hors ligne
✅ **Installable** - L'app peut être installée sur mobile/desktop

---

## 🧪 Comment Tester la PWA

### 1️⃣ Démarrer le Serveur

```bash
php -S localhost:8000 -t public
```

### 2️⃣ Ouvrir l'Application

Ouvrez votre navigateur et allez sur : `http://localhost:8000`

### 3️⃣ Vérifier l'Installation du Service Worker

1. Ouvrez **DevTools** (F12)
2. Allez dans l'onglet **Application** (Chrome) ou **Storage** (Firefox)
3. Dans la section **Service Workers**, vous devriez voir :
   - ✅ `service-worker.js` - Status: **Activated**

### 4️⃣ Tester le Mode Hors Ligne

#### Test 1 : Pages Déjà Visitées

1. **Connectez-vous** à votre compte
2. **Visitez plusieurs pages** :
   - Dashboard (`/dashboard`)
   - Créer une dépense (`/expenses/create`)
   - Créer un budget (`/budget/create`)
   - Récurrences (`/expenses/recurrences`)

3. **Arrêtez le serveur PHP** :
   ```bash
   # Dans le terminal où tourne le serveur, faites Ctrl+C
   ```

4. **Naviguez entre les pages** :
   - Les pages déjà visitées s'affichent normalement ✅
   - Un indicateur "Hors ligne" apparaît en haut de la page 🔴
   - Les nouvelles pages affichent une page d'erreur hors ligne

#### Test 2 : Enregistrement de Dépenses Hors Ligne

1. **Arrêtez le serveur** (Ctrl+C)

2. **Remplissez le formulaire** de création de dépense :
   - Description : "Dépense hors ligne"
   - Montant : 50
   - Catégorie : Transport
   - Date : Aujourd'hui

3. **Soumettez le formulaire** :
   - ✅ Message : "Dépense enregistrée hors ligne"
   - ✅ La dépense est stockée dans **IndexedDB**
   - ✅ Un badge rouge apparaît (nombre d'éléments à synchroniser)

4. **Redémarrez le serveur** :
   ```bash
   php -S localhost:8000 -t public
   ```

5. **Attendez quelques secondes** :
   - ✅ Message : "Connexion rétablie"
   - ✅ Message : "Synchronisation réussie"
   - ✅ La dépense apparaît dans votre dashboard
   - ✅ Le badge disparaît

#### Test 3 : Enregistrement de Budget Hors Ligne

Même processus que pour les dépenses, mais avec le formulaire de budget.

---

## 🔍 Vérifier les Données dans IndexedDB

1. **DevTools** (F12) → **Application** → **IndexedDB**
2. Ouvrez la base **KitiSmartDB**
3. Vous verrez 3 stores :
   - `offlineExpenses` - Dépenses en attente
   - `offlineBudgets` - Budgets en attente
   - `pendingRequests` - Requêtes génériques en attente

---

## 📱 Installer l'Application (Optionnel)

### Sur Desktop (Chrome/Edge)

1. Cliquez sur l'icône **"Installer"** dans la barre d'adresse
2. Ou Menu → **Installer KitiSmart**

### Sur Mobile (Android)

1. Menu → **Ajouter à l'écran d'accueil**
2. L'app s'ouvre en mode standalone (sans barre d'adresse)

### Sur iOS (Safari)

1. Bouton **Partager**
2. **Ajouter à l'écran d'accueil**

---

## 🎨 Indicateurs Visuels

| Indicateur | Signification |
|-----------|---------------|
| 🟢 En ligne | Connexion active |
| 🔴 Hors ligne | Mode hors ligne activé |
| Badge rouge (nombre) | Éléments en attente de synchronisation |
| Notification verte | Action réussie |
| Notification orange | Sauvegarde hors ligne |
| Notification rouge | Erreur |

---

## 🚀 Fonctionnalités Avancées

### Synchronisation Automatique

- **Au retour en ligne** : Synchronisation immédiate
- **Toutes les 5 minutes** : Vérification périodique
- **Manuel** : Actualiser la page force une synchronisation

### Gestion du Cache

Le Service Worker met en cache :
- ✅ Pages HTML (stratégie Network First)
- ✅ CSS, JS, Images (stratégie Cache First)
- ✅ Ressources CDN (Font Awesome, jQuery, Select2)

### Stockage

- **IndexedDB** : Données structurées (dépenses, budgets)
- **Cache API** : Ressources statiques
- **Pas de limite de quota** pour les données essentielles

---

## 🐛 Dépannage

### Le Service Worker ne s'installe pas

```bash
# Vérifiez que vous êtes en HTTPS ou localhost
# Le Service Worker nécessite une connexion sécurisée
```

### Les données ne se synchronisent pas

1. Ouvrez la **Console** (F12)
2. Cherchez les messages `[SyncManager]`
3. Vérifiez que vous êtes bien en ligne (🟢)

### Réinitialiser la PWA

1. **DevTools** → **Application**
2. **Clear storage** → **Clear site data**
3. Rechargez la page

---

## 📊 Architecture Technique

```
┌─────────────────────────────────────────┐
│           Service Worker                │
│  (service-worker.js)                    │
│  - Cache statique                       │
│  - Interception réseau                  │
│  - Gestion hors ligne                   │
└────────────┬────────────────────────────┘
             │
    ┌────────┴────────┐
    │                 │
┌───▼────┐      ┌────▼─────────────────┐
│ Cache  │      │    IndexedDB          │
│  API   │      │  (offline-storage.js) │
│        │      │  - offlineExpenses    │
│        │      │  - offlineBudgets     │
│        │      │  - pendingRequests    │
└────────┘      └──────┬────────────────┘
                       │
                ┌──────▼──────────────┐
                │   Sync Manager       │
                │  (sync-manager.js)   │
                │  - Auto-sync         │
                │  - Retry logic       │
                └──────┬───────────────┘
                       │
                ┌──────▼──────────────┐
                │  Offline Forms       │
                │ (offline-forms.js)   │
                │  - Form interception │
                │  - UI indicators     │
                └─────────────────────┘
```

---

## 🎓 Ce Que Vous Avez Appris

- ✅ Création d'un **Service Worker**
- ✅ Gestion du **cache** (stratégies Cache First / Network First)
- ✅ Stockage avec **IndexedDB**
- ✅ **Background Sync** pour synchronisation différée
- ✅ **Manifest.json** pour l'installabilité
- ✅ Interception de **requêtes réseau**
- ✅ Gestion des états **online/offline**

---

## 📝 Notes Importantes

⚠️ **Les icônes** : Vous devez créer les icônes dans `/public/assets/img/` (72x72 à 512x512 px)

⚠️ **HTTPS en production** : Les Service Workers nécessitent HTTPS (sauf localhost)

⚠️ **Cookies et sessions** : La session PHP peut expirer - prévoir un refresh token

---

## 🔗 Ressources

- [MDN - Progressive Web Apps](https://developer.mozilla.org/fr/docs/Web/Progressive_web_apps)
- [Google - Service Worker](https://developers.google.com/web/fundamentals/primers/service-workers)
- [IndexedDB Guide](https://developer.mozilla.org/fr/docs/Web/API/IndexedDB_API)

---

**Bravo ! Votre application fonctionne maintenant hors ligne ! 🎉**
