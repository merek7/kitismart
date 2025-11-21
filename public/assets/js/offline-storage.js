/**
 * Gestion du stockage hors ligne avec IndexedDB
 */
class OfflineStorage {
  constructor() {
    this.dbName = 'KitiSmartDB';
    this.version = 1;
    this.db = null;
  }

  /**
   * Initialiser la base de données IndexedDB
   */
  async init() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, this.version);

      request.onerror = () => {
        console.error('[IndexedDB] Erreur d\'ouverture:', request.error);
        reject(request.error);
      };

      request.onsuccess = () => {
        this.db = request.result;
        console.log('[IndexedDB] Base de données ouverte avec succès');
        resolve(this.db);
      };

      request.onupgradeneeded = (event) => {
        const db = event.target.result;

        // Store pour les requêtes en attente
        if (!db.objectStoreNames.contains('pendingRequests')) {
          const pendingStore = db.createObjectStore('pendingRequests', {
            keyPath: 'id',
            autoIncrement: true
          });
          pendingStore.createIndex('timestamp', 'timestamp', { unique: false });
          pendingStore.createIndex('synced', 'synced', { unique: false });
        }

        // Store pour les dépenses en mode hors ligne
        if (!db.objectStoreNames.contains('offlineExpenses')) {
          const expensesStore = db.createObjectStore('offlineExpenses', {
            keyPath: 'id',
            autoIncrement: true
          });
          expensesStore.createIndex('timestamp', 'timestamp', { unique: false });
          expensesStore.createIndex('synced', 'synced', { unique: false });
        }

        // Store pour les budgets en mode hors ligne
        if (!db.objectStoreNames.contains('offlineBudgets')) {
          const budgetsStore = db.createObjectStore('offlineBudgets', {
            keyPath: 'id',
            autoIncrement: true
          });
          budgetsStore.createIndex('timestamp', 'timestamp', { unique: false });
          budgetsStore.createIndex('synced', 'synced', { unique: false });
        }

        console.log('[IndexedDB] Base de données créée/mise à jour');
      };
    });
  }

  /**
   * Sauvegarder une requête pour synchronisation ultérieure
   */
  async saveRequest(requestData) {
    if (!this.db) await this.init();

    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction(['pendingRequests'], 'readwrite');
      const store = transaction.objectStore('pendingRequests');

      const data = {
        ...requestData,
        synced: false,
        timestamp: Date.now()
      };

      const request = store.add(data);

      request.onsuccess = () => {
        console.log('[IndexedDB] Requête sauvegardée:', data);
        resolve(request.result);
      };

      request.onerror = () => {
        console.error('[IndexedDB] Erreur de sauvegarde:', request.error);
        reject(request.error);
      };
    });
  }

  /**
   * Sauvegarder une dépense hors ligne
   */
  async saveOfflineExpense(expenseData) {
    if (!this.db) await this.init();

    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction(['offlineExpenses'], 'readwrite');
      const store = transaction.objectStore('offlineExpenses');

      const data = {
        ...expenseData,
        synced: false,
        timestamp: Date.now()
      };

      const request = store.add(data);

      request.onsuccess = () => {
        console.log('[IndexedDB] Dépense hors ligne sauvegardée:', data);
        resolve(request.result);
      };

      request.onerror = () => {
        console.error('[IndexedDB] Erreur de sauvegarde:', request.error);
        reject(request.error);
      };
    });
  }

  /**
   * Sauvegarder un budget hors ligne
   */
  async saveOfflineBudget(budgetData) {
    if (!this.db) await this.init();

    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction(['offlineBudgets'], 'readwrite');
      const store = transaction.objectStore('offlineBudgets');

      const data = {
        ...budgetData,
        synced: false,
        timestamp: Date.now()
      };

      const request = store.add(data);

      request.onsuccess = () => {
        console.log('[IndexedDB] Budget hors ligne sauvegardé:', data);
        resolve(request.result);
      };

      request.onerror = () => {
        console.error('[IndexedDB] Erreur de sauvegarde:', request.error);
        reject(request.error);
      };
    });
  }

  /**
   * Récupérer toutes les requêtes non synchronisées
   */
  async getPendingRequests() {
    if (!this.db) await this.init();

    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction(['pendingRequests'], 'readonly');
      const store = transaction.objectStore('pendingRequests');
      const request = store.getAll();

      request.onsuccess = () => {
        // Filtrer pour ne garder que les éléments non synchronisés
        const pending = request.result.filter(item => item.synced === false);
        resolve(pending);
      };

      request.onerror = () => {
        reject(request.error);
      };
    });
  }

  /**
   * Récupérer toutes les dépenses non synchronisées
   */
  async getPendingExpenses() {
    if (!this.db) await this.init();

    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction(['offlineExpenses'], 'readonly');
      const store = transaction.objectStore('offlineExpenses');
      const request = store.getAll();

      request.onsuccess = () => {
        // Filtrer pour ne garder que les éléments non synchronisés
        const pending = request.result.filter(item => item.synced === false);
        resolve(pending);
      };

      request.onerror = () => {
        reject(request.error);
      };
    });
  }

  /**
   * Récupérer tous les budgets non synchronisés
   */
  async getPendingBudgets() {
    if (!this.db) await this.init();

    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction(['offlineBudgets'], 'readonly');
      const store = transaction.objectStore('offlineBudgets');
      const request = store.getAll();

      request.onsuccess = () => {
        // Filtrer pour ne garder que les éléments non synchronisés
        const pending = request.result.filter(item => item.synced === false);
        resolve(pending);
      };

      request.onerror = () => {
        reject(request.error);
      };
    });
  }

  /**
   * Marquer une requête comme synchronisée
   */
  async markAsSynced(storeName, id) {
    if (!this.db) await this.init();

    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.get(id);

      request.onsuccess = () => {
        const data = request.result;
        if (data) {
          data.synced = true;
          const updateRequest = store.put(data);

          updateRequest.onsuccess = () => {
            console.log(`[IndexedDB] Élément ${id} marqué comme synchronisé`);
            resolve();
          };

          updateRequest.onerror = () => {
            reject(updateRequest.error);
          };
        } else {
          resolve();
        }
      };

      request.onerror = () => {
        reject(request.error);
      };
    });
  }

  /**
   * Supprimer une requête synchronisée
   */
  async deleteSynced(storeName, id) {
    if (!this.db) await this.init();

    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([storeName], 'readwrite');
      const store = transaction.objectStore(storeName);
      const request = store.delete(id);

      request.onsuccess = () => {
        console.log(`[IndexedDB] Élément ${id} supprimé`);
        resolve();
      };

      request.onerror = () => {
        reject(request.error);
      };
    });
  }

  /**
   * Compter les éléments en attente de synchronisation
   */
  async getPendingCount() {
    if (!this.db) await this.init();

    // NOTE: On ignore les pendingRequests car on ne les synchronise plus
    // On compte uniquement les expenses et budgets
    const [expenses, budgets] = await Promise.all([
      this.getPendingExpenses(),
      this.getPendingBudgets()
    ]);

    return {
      requests: 0,  // Ignoré
      expenses: expenses.length,
      budgets: budgets.length,
      total: expenses.length + budgets.length
    };
  }

  /**
   * Vider tous les stores
   */
  async clearAll() {
    if (!this.db) await this.init();

    const stores = ['pendingRequests', 'offlineExpenses', 'offlineBudgets'];

    return Promise.all(
      stores.map(storeName => {
        return new Promise((resolve, reject) => {
          const transaction = this.db.transaction([storeName], 'readwrite');
          const store = transaction.objectStore(storeName);
          const request = store.clear();

          request.onsuccess = () => resolve();
          request.onerror = () => reject(request.error);
        });
      })
    );
  }
}

// Instance globale
window.offlineStorage = new OfflineStorage();
