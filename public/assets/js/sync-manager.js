/**
 * Gestionnaire de synchronisation pour les données hors ligne
 */
class SyncManager {
  constructor() {
    this.syncing = false;
    this.syncInterval = null;
    this.onlineListenerAttached = false;
  }

  /**
   * Initialiser le gestionnaire de synchronisation
   */
  async init() {
    console.log('[SyncManager] Initialisation...');

    // Initialiser le stockage hors ligne
    await window.offlineStorage.init();

    // Nettoyer les anciennes requêtes HEAD (vérifications de connectivité) qui ne doivent pas être synchronisées
    await this.cleanupHeadRequests();

    // Écouter les messages du Service Worker
    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
      navigator.serviceWorker.addEventListener('message', (event) => {
        if (event.data.type === 'SAVE_OFFLINE_REQUEST') {
          this.handleOfflineRequest(event.data.data);
        } else if (event.data.type === 'SYNC_OFFLINE_DATA') {
          this.syncAll();
        }
      });
    }

    // Écouter le retour en ligne
    if (!this.onlineListenerAttached) {
      window.addEventListener('online', () => {
        console.log('[SyncManager] Connexion rétablie');
        this.showNotification('Connexion rétablie', 'Synchronisation des données...', 'info');
        this.syncAll();
      });

      window.addEventListener('offline', () => {
        console.log('[SyncManager] Hors ligne');
        this.showNotification('Mode hors ligne', 'Vos données seront synchronisées quand vous serez en ligne', 'warning');
      });

      this.onlineListenerAttached = true;
    }

    // Afficher le nombre d'éléments en attente
    await this.updateSyncBadge();

    // Si en ligne et qu'il y a des données en attente, synchroniser immédiatement
    const counts = await window.offlineStorage.getPendingCount();
    if (navigator.onLine && counts.total > 0) {
      console.log(`[SyncManager] ${counts.total} élément(s) en attente - Synchronisation immédiate`);
      setTimeout(() => {
        this.syncAll();
      }, 2000); // Attendre 2 secondes que la page se charge
    }

    // Synchroniser périodiquement
    this.startPeriodicSync();
  }

  /**
   * Nettoyer les requêtes HEAD inutiles de IndexedDB
   */
  async cleanupHeadRequests() {
    try {
      const db = window.offlineStorage.db;
      if (!db) return;

      const transaction = db.transaction(['pendingRequests'], 'readwrite');
      const store = transaction.objectStore('pendingRequests');
      const request = store.getAll();

      request.onsuccess = () => {
        const allRequests = request.result;
        const headRequests = allRequests.filter(item => item.method === 'HEAD');

        if (headRequests.length > 0) {
          console.log(`[SyncManager] Nettoyage de ${headRequests.length} requête(s) HEAD inutile(s)`);

          headRequests.forEach(req => {
            store.delete(req.id);
          });
        }
      };
    } catch (error) {
      console.error('[SyncManager] Erreur lors du nettoyage des requêtes HEAD:', error);
    }
  }

  /**
   * Gérer une requête hors ligne
   */
  async handleOfflineRequest(requestData) {
    // Ne pas sauvegarder les requêtes HEAD
    if (requestData.method === 'HEAD') {
      console.log('[SyncManager] Requête HEAD ignorée (vérification de connectivité)');
      return;
    }

    await window.offlineStorage.saveRequest(requestData);
    this.showNotification(
      'Enregistré hors ligne',
      'Vos données seront synchronisées dès que possible',
      'info'
    );
    this.updateSyncBadge();
  }

  /**
   * Synchroniser toutes les données
   */
  async syncAll(showNotifications = true) {
    if (this.syncing) {
      console.log('[SyncManager] Synchronisation déjà en cours');
      return;
    }

    if (!navigator.onLine) {
      console.log('[SyncManager] Hors ligne, synchronisation impossible');
      return;
    }

    this.syncing = true;

    try {
      let successCount = 0;
      let errorCount = 0;

      console.log('[SyncManager] Début de la synchronisation...');

      // Synchroniser les requêtes en attente (interceptées par le Service Worker)
      const pendingRequests = await window.offlineStorage.getPendingRequests();
      console.log(`[SyncManager] ${pendingRequests.length} requête(s) en attente à synchroniser`);

      for (const requestData of pendingRequests) {
        try {
          await this.syncPendingRequest(requestData);
          await window.offlineStorage.deleteSynced('pendingRequests', requestData.id);
          successCount++;
          console.log('[SyncManager] Requête synchronisée avec succès');
        } catch (error) {
          console.error('[SyncManager] Erreur de synchronisation de requête:', error);
          errorCount++;
        }
      }

      // Synchroniser les dépenses
      const pendingExpenses = await window.offlineStorage.getPendingExpenses();
      console.log(`[SyncManager] ${pendingExpenses.length} dépense(s) à synchroniser`);

      for (const expense of pendingExpenses) {
        try {
          await this.syncExpense(expense);
          await window.offlineStorage.deleteSynced('offlineExpenses', expense.id);
          successCount++;
          console.log('[SyncManager] Dépense synchronisée avec succès');
        } catch (error) {
          console.error('[SyncManager] Erreur de synchronisation de dépense:', error);
          errorCount++;
        }
      }

      // Synchroniser les budgets
      const pendingBudgets = await window.offlineStorage.getPendingBudgets();
      console.log(`[SyncManager] ${pendingBudgets.length} budget(s) à synchroniser`);

      for (const budget of pendingBudgets) {
        try {
          await this.syncBudget(budget);
          await window.offlineStorage.deleteSynced('offlineBudgets', budget.id);
          successCount++;
          console.log('[SyncManager] Budget synchronisé avec succès');
        } catch (error) {
          console.error('[SyncManager] Erreur de synchronisation de budget:', error);
          errorCount++;
        }
      }

      // Afficher le résultat seulement si demandé
      if (showNotifications) {
        if (successCount > 0) {
          this.showNotification(
            'Synchronisation réussie',
            `${successCount} élément(s) synchronisé(s)`,
            'success'
          );
        }

        if (errorCount > 0) {
          this.showNotification(
            'Erreurs de synchronisation',
            `${errorCount} élément(s) n'ont pas pu être synchronisés`,
            'error'
          );
        }
      }

      this.updateSyncBadge();

    } catch (error) {
      console.error('[SyncManager] Erreur lors de la synchronisation:', error);
      this.showNotification(
        'Erreur de synchronisation',
        'Une erreur est survenue',
        'error'
      );
    } finally {
      this.syncing = false;
    }
  }

  /**
   * Synchroniser une requête générique
   */
  async syncRequest(request) {
    // Parser le body pour reconstruire un FormData
    const formData = new FormData();

    if (request.body) {
      // Le body est une chaîne URLencoded, il faut la parser
      const params = new URLSearchParams(request.body);
      for (const [key, value] of params.entries()) {
        formData.append(key, value);
      }
    }

    const response = await fetch(request.url, {
      method: request.method,
      body: formData
      // Ne pas envoyer les headers originaux, FormData gère ça automatiquement
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    return response.json();
  }

  /**
   * Normaliser les données d'une dépense avant synchronisation
   * Gère à la fois l'ancien format (avec category[], amount[], etc.) et le nouveau format
   */
  normalizeExpenseData(expense) {
    const normalized = {};

    // Copier les métadonnées importantes (csrf_token, etc.)
    Object.keys(expense).forEach(key => {
      if (key !== 'id' && key !== 'timestamp' && key !== 'synced' && !key.includes('[]')) {
        normalized[key] = expense[key];
      }
    });

    // Vérifier si on a un format avec des tableaux (category[], amount[], etc.)
    const hasArrayFormat = Object.keys(expense).some(key => key.endsWith('[]'));

    if (hasArrayFormat) {
      console.log('[SyncManager] Détection format tableau - conversion en cours');

      // Collecter tous les champs en tableau
      const arrays = {};
      Object.keys(expense).forEach(key => {
        if (key.endsWith('[]')) {
          const baseKey = key.replace('[]', '');
          // Si c'est une chaîne, la convertir en tableau
          arrays[baseKey] = typeof expense[key] === 'string' ? [expense[key]] : expense[key];
        }
      });

      // Créer le tableau d'expenses
      if (Object.keys(arrays).length > 0) {
        const arrayKeys = Object.keys(arrays);
        const itemCount = Math.max(...arrayKeys.map(key => arrays[key]?.length || 0));

        normalized.expenses = [];

        for (let i = 0; i < itemCount; i++) {
          const expenseItem = {};
          arrayKeys.forEach(key => {
            if (arrays[key][i] !== undefined && arrays[key][i] !== '') {
              // Mapper les noms de champs
              const mappedKey = this.mapFieldName(key);
              expenseItem[mappedKey] = arrays[key][i];
            }
          });

          // Ne garder que les dépenses complètes
          if (expenseItem.description && expenseItem.amount) {
            normalized.expenses.push(expenseItem);
          }
        }
      }
    } else if (expense.expenses && Array.isArray(expense.expenses)) {
      // Format déjà correct avec un tableau d'expenses
      normalized.expenses = expense.expenses;
    } else {
      // Format objet simple - peut-être une dépense unique
      // Vérifier si on a les champs d'une dépense
      if (expense.description || expense.amount || expense.category_type) {
        const singleExpense = {};
        ['description', 'amount', 'category_type', 'payment_date', 'status'].forEach(field => {
          if (expense[field] !== undefined) {
            singleExpense[field] = expense[field];
          }
        });

        if (singleExpense.description && singleExpense.amount) {
          normalized.expenses = [singleExpense];
        }
      }
    }

    return normalized;
  }

  /**
   * Mapper les noms de champs
   */
  mapFieldName(fieldName) {
    const mapping = {
      'category': 'category_type',
      'date': 'payment_date',
      'status': 'status',
      'amount': 'amount',
      'description': 'description'
    };
    return mapping[fieldName] || fieldName;
  }

  /**
   * Synchroniser une requête en attente (interceptée par le Service Worker)
   */
  async syncPendingRequest(requestData) {
    console.log('[SyncManager] Synchronisation de la requête:', requestData);

    // Parser le body multipart/form-data en FormData
    let bodyToSend = requestData.body;
    let headersToSend = { ...requestData.headers };

    // Si le body est en multipart/form-data, le parser
    if (requestData.body && headersToSend['content-type']?.includes('multipart/form-data')) {
      console.log('[SyncManager] Parsing multipart/form-data body...');

      try {
        const formData = this.parseMultipartFormData(requestData.body, headersToSend['content-type']);

        // Convertir FormData en objet JSON pour expenses/create
        if (requestData.url.includes('/expenses/create')) {
          const expenseData = this.formDataToExpenseJSON(formData);
          bodyToSend = JSON.stringify(expenseData);
          headersToSend['content-type'] = 'application/json';
          console.log('[SyncManager] Body converti en JSON:', expenseData);
        } else {
          // Pour autres requêtes, recréer le FormData
          bodyToSend = formData;
          delete headersToSend['content-type']; // Laissez le navigateur définir la boundary
        }
      } catch (error) {
        console.error('[SyncManager] Erreur de parsing multipart:', error);
        // Fallback: essayer d'envoyer tel quel
      }
    }

    const options = {
      method: requestData.method,
      headers: {
        ...headersToSend,
        'X-Sync-Request': 'true' // Marquer cette requête comme une synchronisation
      },
      body: bodyToSend
    };

    console.log('[SyncManager] Envoi de la requête:', requestData.url, options);

    const response = await fetch(requestData.url, options);

    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(`HTTP ${response.status}: ${errorText}`);
    }

    return response.json();
  }

  /**
   * Parser un body multipart/form-data en FormData
   */
  parseMultipartFormData(body, contentType) {
    const formData = new FormData();

    // Extraire la boundary
    const boundaryMatch = contentType.match(/boundary=(.+?)(?:;|$)/);
    if (!boundaryMatch) {
      throw new Error('Boundary non trouvé dans content-type');
    }

    const boundary = '--' + boundaryMatch[1];
    const parts = body.split(boundary).filter(part => part.trim() && !part.includes('--'));

    for (const part of parts) {
      // Extraire le nom du champ
      const nameMatch = part.match(/name="([^"]+)"/);
      if (!nameMatch) continue;

      const fieldName = nameMatch[1];

      // Extraire la valeur (après les headers, séparés par \r\n\r\n)
      const valueMatch = part.split('\r\n\r\n')[1];
      if (!valueMatch) continue;

      const value = valueMatch.trim();
      formData.append(fieldName, value);
    }

    return formData;
  }

  /**
   * Convertir FormData en objet JSON pour expenses/create
   */
  formDataToExpenseJSON(formData) {
    const data = {};
    const arrays = {};

    for (const [key, value] of formData.entries()) {
      if (key.endsWith('[]')) {
        const baseKey = key.replace('[]', '');
        if (!arrays[baseKey]) arrays[baseKey] = [];
        arrays[baseKey].push(value);
      } else {
        data[key] = value;
      }
    }

    // Créer le tableau d'expenses
    if (Object.keys(arrays).length > 0) {
      const arrayKeys = Object.keys(arrays);
      const itemCount = arrays[arrayKeys[0]]?.length || 0;

      data.expenses = [];

      for (let i = 0; i < itemCount; i++) {
        const expense = {};
        arrayKeys.forEach(key => {
          if (arrays[key][i] !== undefined && arrays[key][i] !== '') {
            const mappedKey = this.mapFieldName(key);
            expense[mappedKey] = arrays[key][i];
          }
        });

        if (expense.description && expense.amount) {
          data.expenses.push(expense);
        }
      }
    }

    return data;
  }

  /**
   * Synchroniser une dépense
   */
  async syncExpense(expense) {
    console.log('[SyncManager] Données brutes de l\'expense:', expense);

    // Normaliser les données au cas où elles viennent de l'ancien format
    const normalizedExpense = this.normalizeExpenseData(expense);

    console.log('[SyncManager] Données normalisées:', normalizedExpense);
    console.log('[DEBUG] JSON à envoyer:', JSON.stringify(normalizedExpense));

    // Le serveur attend du JSON, pas du FormData
    const response = await fetch('/expenses/create', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Sync-Request': 'true' // Marquer cette requête comme une synchronisation
      },
      body: JSON.stringify(normalizedExpense)
    });

    if (!response.ok) {
      // Tenter de récupérer le message d'erreur du serveur
      let errorMessage = `HTTP ${response.status}: ${response.statusText}`;
      try {
        const errorData = await response.text();
        console.error('[DEBUG] Réponse du serveur:', errorData);
        errorMessage += ` - ${errorData}`;
      } catch (e) {
        console.error('[DEBUG] Impossible de lire la réponse d\'erreur');
      }
      throw new Error(errorMessage);
    }

    return response.json();
  }

  /**
   * Synchroniser un budget
   */
  async syncBudget(budget) {
    console.log('[DEBUG] Données brutes du budget:', budget);

    // Préparer les données en excluant id, timestamp, synced
    const budgetData = {};
    Object.keys(budget).forEach(key => {
      if (key !== 'id' && key !== 'timestamp' && key !== 'synced') {
        console.log(`[DEBUG] Ajout à budgetData: ${key} = ${budget[key]}`);
        budgetData[key] = budget[key];
      }
    });

    console.log('[DEBUG] JSON à envoyer:', JSON.stringify(budgetData));

    // Le serveur attend du JSON, pas du FormData
    const response = await fetch('/budget/create', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Sync-Request': 'true' // Marquer cette requête comme une synchronisation
      },
      body: JSON.stringify(budgetData)
    });

    if (!response.ok) {
      // Tenter de récupérer le message d'erreur du serveur
      let errorMessage = `HTTP ${response.status}: ${response.statusText}`;
      try {
        const errorData = await response.text();
        console.error('[DEBUG] Réponse du serveur:', errorData);
        errorMessage += ` - ${errorData}`;
      } catch (e) {
        console.error('[DEBUG] Impossible de lire la réponse d\'erreur');
      }
      throw new Error(errorMessage);
    }

    return response.json();
  }

  /**
   * Mettre à jour le badge de synchronisation
   */
  async updateSyncBadge() {
    const counts = await window.offlineStorage.getPendingCount();
    const badge = document.getElementById('sync-badge');

    if (badge) {
      if (counts.total > 0) {
        badge.textContent = counts.total;
        badge.style.display = 'inline-block';
      } else {
        badge.style.display = 'none';
      }
    }
  }

  /**
   * Démarrer la synchronisation périodique
   */
  startPeriodicSync() {
    // Vérifier la connectivité au serveur toutes les 10 secondes
    this.syncInterval = setInterval(async () => {
      // Vérifier s'il y a des données en attente
      const counts = await window.offlineStorage.getPendingCount();

      if (counts.total > 0 && !this.syncing) {
        console.log('[SyncManager] Données en attente - Vérification de la connectivité...');

        // Tester si le serveur répond
        const isServerReachable = await this.checkServerConnection();

        if (isServerReachable) {
          console.log('[SyncManager] ✅ Serveur accessible - Lancement de la synchronisation');
          this.syncAll(true); // Afficher les notifications pour la synchronisation automatique
        } else {
          console.log('[SyncManager] ❌ Serveur inaccessible - Nouvelle tentative dans 10s');
        }
      }
    }, 10 * 1000); // 10 secondes
  }

  /**
   * Vérifier si le serveur est accessible
   */
  async checkServerConnection() {
    try {
      // Faire une requête légère vers une route qui existe toujours
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 3000); // Timeout 3s

      const response = await fetch('/dashboard', {
        method: 'HEAD', // Requête légère qui ne charge pas tout le contenu
        signal: controller.signal,
        cache: 'no-cache'
      });

      clearTimeout(timeoutId);
      return response.ok;
    } catch (error) {
      return false;
    }
  }

  /**
   * Arrêter la synchronisation périodique
   */
  stopPeriodicSync() {
    if (this.syncInterval) {
      clearInterval(this.syncInterval);
      this.syncInterval = null;
    }
  }

  /**
   * Afficher une notification
   */
  showNotification(title, message, type = 'info') {
    // Créer une notification visuelle
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
      <div class="notification-content">
        <strong>${title}</strong>
        <p>${message}</p>
      </div>
      <button class="notification-close">&times;</button>
    `;

    document.body.appendChild(notification);

    // Fermeture au clic
    notification.querySelector('.notification-close').addEventListener('click', () => {
      notification.remove();
    });

    // Auto-fermeture après 5 secondes
    setTimeout(() => {
      if (notification.parentNode) {
        notification.remove();
      }
    }, 5000);

    // Notification du navigateur si disponible
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(title, {
        body: message,
        icon: '/assets/img/icon-192x192.png'
      });
    }
  }

  /**
   * Demander la permission pour les notifications
   */
  async requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
      await Notification.requestPermission();
    }
  }
}

// Instance globale
window.syncManager = new SyncManager();

// CSS pour les notifications
const syncManagerStyle = document.createElement('style');
syncManagerStyle.textContent = `
  .notification {
    position: fixed;
    top: 20px;
    right: 20px;
    max-width: 400px;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10000;
    animation: slideIn 0.3s ease-out;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }

  .notification-info {
    background-color: #2196F3;
    color: white;
  }

  .notification-success {
    background-color: #4CAF50;
    color: white;
  }

  .notification-warning {
    background-color: #FF9800;
    color: white;
  }

  .notification-error {
    background-color: #F44336;
    color: white;
  }

  .notification-content {
    flex: 1;
  }

  .notification-content strong {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 1rem;
  }

  .notification-content p {
    margin: 0;
    font-size: 0.875rem;
    opacity: 0.9;
  }

  .notification-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    margin-left: 1rem;
    line-height: 1;
  }

  @keyframes slideIn {
    from {
      transform: translateX(400px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }

  #sync-badge {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background-color: #F44336;
    color: white;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: none;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
    box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
    z-index: 9999;
    cursor: pointer;
    transition: all 0.3s ease;
    animation: pulse 2s infinite;
  }

  #sync-badge:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(244, 67, 54, 0.6);
    animation: none;
  }

  #sync-badge[style*="display: inline"] {
    display: flex !important;
  }

  @keyframes pulse {
    0%, 100% {
      transform: scale(1);
      box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
    }
    50% {
      transform: scale(1.05);
      box-shadow: 0 6px 16px rgba(244, 67, 54, 0.6);
    }
  }
`;
document.head.appendChild(syncManagerStyle);
