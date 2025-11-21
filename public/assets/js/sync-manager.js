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
   * Gérer une requête hors ligne
   */
  async handleOfflineRequest(requestData) {
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
  async syncAll() {
    if (this.syncing) {
      console.log('[SyncManager] Synchronisation déjà en cours');
      return;
    }

    if (!navigator.onLine) {
      console.log('[SyncManager] Hors ligne, synchronisation impossible');
      return;
    }

    this.syncing = true;
    console.log('[SyncManager] Début de la synchronisation...');

    try {
      let successCount = 0;
      let errorCount = 0;

      // Synchroniser les requêtes en attente
      const pendingRequests = await window.offlineStorage.getPendingRequests();
      for (const request of pendingRequests) {
        try {
          await this.syncRequest(request);
          await window.offlineStorage.deleteSynced('pendingRequests', request.id);
          successCount++;
        } catch (error) {
          console.error('[SyncManager] Erreur de synchronisation:', error);
          errorCount++;
        }
      }

      // Synchroniser les dépenses
      const pendingExpenses = await window.offlineStorage.getPendingExpenses();
      for (const expense of pendingExpenses) {
        try {
          await this.syncExpense(expense);
          await window.offlineStorage.deleteSynced('offlineExpenses', expense.id);
          successCount++;
        } catch (error) {
          console.error('[SyncManager] Erreur de synchronisation de dépense:', error);
          errorCount++;
        }
      }

      // Synchroniser les budgets
      const pendingBudgets = await window.offlineStorage.getPendingBudgets();
      for (const budget of pendingBudgets) {
        try {
          await this.syncBudget(budget);
          await window.offlineStorage.deleteSynced('offlineBudgets', budget.id);
          successCount++;
        } catch (error) {
          console.error('[SyncManager] Erreur de synchronisation de budget:', error);
          errorCount++;
        }
      }

      // Afficher le résultat
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
   * Synchroniser une dépense
   */
  async syncExpense(expense) {
    const formData = new FormData();
    Object.keys(expense).forEach(key => {
      if (key !== 'id' && key !== 'timestamp' && key !== 'synced') {
        formData.append(key, expense[key]);
      }
    });

    const response = await fetch('/expenses/create', {
      method: 'POST',
      body: formData
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    return response.json();
  }

  /**
   * Synchroniser un budget
   */
  async syncBudget(budget) {
    const formData = new FormData();
    Object.keys(budget).forEach(key => {
      if (key !== 'id' && key !== 'timestamp' && key !== 'synced') {
        formData.append(key, budget[key]);
      }
    });

    const response = await fetch('/budget/create', {
      method: 'POST',
      body: formData
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
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
    // Synchroniser toutes les 5 minutes si en ligne
    this.syncInterval = setInterval(() => {
      if (navigator.onLine && !this.syncing) {
        this.syncAll();
      }
    }, 5 * 60 * 1000);
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
