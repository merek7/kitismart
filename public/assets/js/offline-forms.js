/**
 * Gestionnaire de formulaires hors ligne
 */
class OfflineForms {
  constructor() {
    this.formsInitialized = false;
  }

  /**
   * Initialiser la gestion des formulaires hors ligne
   */
  init() {
    if (this.formsInitialized) return;

    console.log('[OfflineForms] Initialisation...');

    // Intercepter les soumissions de formulaires de dépenses
    this.interceptExpenseForm();

    // Intercepter les soumissions de formulaires de budget
    this.interceptBudgetForm();

    // Afficher le statut de connexion
    this.showConnectionStatus();

    this.formsInitialized = true;
  }

  /**
   * Normaliser les données du formulaire de dépenses
   * Transforme les tableaux (category[], amount[], etc.) en objets individuels
   */
  normalizeExpenseFormData(formData) {
    const data = {};
    const arrays = {};

    // Collecter toutes les données
    formData.forEach((value, key) => {
      // Si le champ se termine par [], c'est un tableau
      if (key.endsWith('[]')) {
        const baseKey = key.replace('[]', '');
        if (!arrays[baseKey]) {
          arrays[baseKey] = [];
        }
        arrays[baseKey].push(value);
      } else {
        // Champ simple (comme csrf_token)
        data[key] = value;
      }
    });

    // Si on a des tableaux, créer un tableau d'objets expenses
    if (Object.keys(arrays).length > 0) {
      // Vérifier qu'on a au moins un élément
      const arrayKeys = Object.keys(arrays);
      const itemCount = arrays[arrayKeys[0]]?.length || 0;

      if (itemCount > 0) {
        data.expenses = [];

        // Créer un objet pour chaque dépense
        for (let i = 0; i < itemCount; i++) {
          const expense = {};
          arrayKeys.forEach(key => {
            if (arrays[key][i] !== undefined && arrays[key][i] !== '') {
              // Mapper les noms de champs
              const mappedKey = this.mapFieldName(key);
              expense[mappedKey] = arrays[key][i];
            }
          });

          // Ne garder que les dépenses complètes
          if (expense.description && expense.amount) {
            data.expenses.push(expense);
          }
        }
      }
    }

    console.log('[OfflineForms] Données normalisées:', data);
    return data;
  }

  /**
   * Mapper les noms de champs du formulaire vers les noms attendus par le serveur
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
   * Intercepter le formulaire de dépenses
   */
  interceptExpenseForm() {
    const expenseForm = document.querySelector('form[action*="/expenses"]');
    if (!expenseForm) return;

    // NE PAS intercepter si on est sur la page expense_create qui a déjà son propre gestionnaire AJAX
    if (window.location.pathname.includes('/expenses/create')) {
      console.log('[OfflineForms] Page expense_create détectée - interception désactivée (gestion AJAX native)');
      return;
    }

    console.log('[OfflineForms] Formulaire de dépenses trouvé');

    expenseForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(expenseForm);

      // Transformer les données du formulaire en structure correcte
      const expenseData = this.normalizeExpenseFormData(formData);

      // Si hors ligne, sauvegarder localement
      if (!navigator.onLine) {
        console.log('[OfflineForms] Hors ligne - Sauvegarde locale');

        try {
          await window.offlineStorage.saveOfflineExpense(expenseData);

          // Mettre à jour le badge
          if (window.syncManager) {
            await window.syncManager.updateSyncBadge();
          }

          window.syncManager.showNotification(
            'Dépense enregistrée hors ligne',
            'Elle sera synchronisée dès que vous serez en ligne',
            'success'
          );

          // Optionnel : réinitialiser le formulaire
          expenseForm.reset();

          // NE PAS rediriger automatiquement - laisser l'utilisateur sur la page
          // setTimeout(() => {
          //   window.location.href = '/dashboard';
          // }, 2000);

        } catch (error) {
          console.error('[OfflineForms] Erreur:', error);
          window.syncManager.showNotification(
            'Erreur',
            'Impossible de sauvegarder la dépense',
            'error'
          );
        }

        return;
      }

      // Si en ligne, soumettre normalement en JSON
      try {
        console.log('[OfflineForms] En ligne - Envoi au serveur en JSON');
        console.log('[OfflineForms] Données à envoyer:', expenseData);

        const response = await fetch(expenseForm.action, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(expenseData)
        });

        if (response.ok) {
          const result = await response.json();

          if (result.success) {
            window.syncManager.showNotification(
              'Dépense enregistrée',
              'Votre dépense a été enregistrée avec succès',
              'success'
            );

            expenseForm.reset();

            setTimeout(() => {
              window.location.href = '/dashboard';
            }, 1500);
          } else {
            window.syncManager.showNotification(
              'Erreur',
              result.message || 'Une erreur est survenue',
              'error'
            );
          }
        } else {
          const errorText = await response.text();
          console.error('[OfflineForms] Erreur serveur:', response.status, errorText);
          throw new Error(`Erreur serveur: ${response.status}`);
        }

      } catch (error) {
        console.error('[OfflineForms] Erreur de soumission:', error);

        // En cas d'erreur réseau, sauvegarder hors ligne
        await window.offlineStorage.saveOfflineExpense(expenseData);

        // Mettre à jour le badge
        if (window.syncManager) {
          await window.syncManager.updateSyncBadge();
        }

        window.syncManager.showNotification(
          'Dépense enregistrée hors ligne',
          'Elle sera synchronisée dès que possible',
          'warning'
        );

        expenseForm.reset();

        // NE PAS rediriger automatiquement - laisser l'utilisateur sur la page
        // setTimeout(() => {
        //   window.location.href = '/dashboard';
        // }, 2000);
      }
    });
  }

  /**
   * Intercepter le formulaire de budget
   */
  interceptBudgetForm() {
    const budgetForm = document.querySelector('form[action*="/budget"]');
    if (!budgetForm) return;

    console.log('[OfflineForms] Formulaire de budget trouvé');

    budgetForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(budgetForm);
      const budgetData = {};

      formData.forEach((value, key) => {
        budgetData[key] = value;
      });

      // Si hors ligne, sauvegarder localement
      if (!navigator.onLine) {
        console.log('[OfflineForms] Hors ligne - Sauvegarde locale');

        try {
          await window.offlineStorage.saveOfflineBudget(budgetData);

          // Mettre à jour le badge
          if (window.syncManager) {
            await window.syncManager.updateSyncBadge();
          }

          window.syncManager.showNotification(
            'Budget enregistré hors ligne',
            'Il sera synchronisé dès que vous serez en ligne',
            'success'
          );

          budgetForm.reset();

          setTimeout(() => {
            window.location.href = '/dashboard';
          }, 2000);

        } catch (error) {
          console.error('[OfflineForms] Erreur:', error);
          window.syncManager.showNotification(
            'Erreur',
            'Impossible de sauvegarder le budget',
            'error'
          );
        }

        return;
      }

      // Si en ligne, soumettre normalement
      try {
        const response = await fetch(budgetForm.action, {
          method: 'POST',
          body: formData
        });

        if (response.ok) {
          const result = await response.json();

          if (result.success) {
            window.syncManager.showNotification(
              'Budget enregistré',
              'Votre budget a été enregistré avec succès',
              'success'
            );

            budgetForm.reset();

            setTimeout(() => {
              window.location.href = '/dashboard';
            }, 1500);
          } else {
            window.syncManager.showNotification(
              'Erreur',
              result.message || 'Une erreur est survenue',
              'error'
            );
          }
        } else {
          throw new Error('Erreur serveur');
        }

      } catch (error) {
        console.error('[OfflineForms] Erreur de soumission:', error);

        // En cas d'erreur réseau, sauvegarder hors ligne
        await window.offlineStorage.saveOfflineBudget(budgetData);

        // Mettre à jour le badge
        if (window.syncManager) {
          await window.syncManager.updateSyncBadge();
        }

        window.syncManager.showNotification(
          'Budget enregistré hors ligne',
          'Il sera synchronisé dès que possible',
          'warning'
        );

        budgetForm.reset();

        setTimeout(() => {
          window.location.href = '/dashboard';
        }, 2000);
      }
    });
  }

  /**
   * Afficher le statut de connexion
   */
  showConnectionStatus() {
    // Créer l'indicateur de statut
    const statusIndicator = document.createElement('div');
    statusIndicator.id = 'connection-status';
    statusIndicator.className = navigator.onLine ? 'online' : 'offline';
    statusIndicator.innerHTML = `
      <span class="status-icon">${navigator.onLine ? '🟢' : '🔴'}</span>
      <span class="status-text">${navigator.onLine ? 'En ligne' : 'Hors ligne'}</span>
    `;
    document.body.appendChild(statusIndicator);

    // Mettre à jour lors des changements de statut
    window.addEventListener('online', () => {
      statusIndicator.className = 'online';
      statusIndicator.innerHTML = `
        <span class="status-icon">🟢</span>
        <span class="status-text">En ligne</span>
      `;
    });

    window.addEventListener('offline', () => {
      statusIndicator.className = 'offline';
      statusIndicator.innerHTML = `
        <span class="status-icon">🔴</span>
        <span class="status-text">Hors ligne</span>
      `;
    });
  }
}

// Instance globale
window.offlineForms = new OfflineForms();

// CSS pour le statut de connexion
const offlineFormsStyle = document.createElement('style');
offlineFormsStyle.textContent = `
  #connection-status {
    position: fixed;
    bottom: 80px;
    right: 20px;
    padding: 0.75rem 1.25rem;
    border-radius: 25px;
    font-size: 0.875rem;
    font-weight: 600;
    z-index: 9998;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
  }

  #connection-status.online {
    background-color: #4CAF50;
    color: white;
  }

  #connection-status.offline {
    background-color: #F44336;
    color: white;
  }

  .status-icon {
    font-size: 0.75rem;
  }

  .status-text {
    font-size: 0.875rem;
  }
`;
document.head.appendChild(offlineFormsStyle);

// Initialiser au chargement de la page
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    window.offlineForms.init();
  });
} else {
  window.offlineForms.init();
}
