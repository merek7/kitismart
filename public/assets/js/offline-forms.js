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
   * Intercepter le formulaire de dépenses
   */
  interceptExpenseForm() {
    const expenseForm = document.querySelector('form[action*="/expenses"]');
    if (!expenseForm) return;

    console.log('[OfflineForms] Formulaire de dépenses trouvé');

    expenseForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(expenseForm);
      const expenseData = {};

      formData.forEach((value, key) => {
        expenseData[key] = value;
      });

      // Si hors ligne, sauvegarder localement
      if (!navigator.onLine) {
        console.log('[OfflineForms] Hors ligne - Sauvegarde locale');

        try {
          await window.offlineStorage.saveOfflineExpense(expenseData);

          window.syncManager.showNotification(
            'Dépense enregistrée hors ligne',
            'Elle sera synchronisée dès que vous serez en ligne',
            'success'
          );

          // Optionnel : réinitialiser le formulaire
          expenseForm.reset();

          // Rediriger ou afficher un message
          setTimeout(() => {
            window.location.href = '/dashboard';
          }, 2000);

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

      // Si en ligne, soumettre normalement
      try {
        const response = await fetch(expenseForm.action, {
          method: 'POST',
          body: formData
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
          throw new Error('Erreur serveur');
        }

      } catch (error) {
        console.error('[OfflineForms] Erreur de soumission:', error);

        // En cas d'erreur réseau, sauvegarder hors ligne
        await window.offlineStorage.saveOfflineExpense(expenseData);

        window.syncManager.showNotification(
          'Dépense enregistrée hors ligne',
          'Elle sera synchronisée dès que possible',
          'warning'
        );

        expenseForm.reset();

        setTimeout(() => {
          window.location.href = '/dashboard';
        }, 2000);
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
const style = document.createElement('style');
style.textContent = `
  #connection-status {
    position: fixed;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    z-index: 9998;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
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
document.head.appendChild(style);

// Initialiser au chargement de la page
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    window.offlineForms.init();
  });
} else {
  window.offlineForms.init();
}
