$(document).ready(function () {
  // Initialiser le graphique d'évolution
  if (typeof chartData !== 'undefined' && chartData.labels.length > 0) {
    initEvolutionChart();
  }

  // Gestion du clic sur "Voir détails"
  $('.btn-view').on('click', function () {
    const budgetId = $(this).data('id');
    loadBudgetDetails(budgetId);
  });

  /**
   * Initialiser le graphique d'évolution
   */
  function initEvolutionChart() {
    const ctx = document.getElementById('evolutionChart').getContext('2d');

    const chart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: chartData.labels,
        datasets: [
          {
            label: 'Budget initial',
            data: chartData.initial,
            borderColor: '#0d9488',
            backgroundColor: 'rgba(13, 148, 136, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
          },
          {
            label: 'Dépensé',
            data: chartData.spent,
            borderColor: '#ef4444',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
          },
          {
            label: 'Restant',
            data: chartData.remaining,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            position: 'top',
            labels: {
              usePointStyle: true,
              padding: 20,
              font: {
                size: 12,
                weight: '600'
              }
            }
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            padding: 12,
            titleFont: {
              size: 14,
              weight: 'bold'
            },
            bodyFont: {
              size: 13
            },
            callbacks: {
              label: function (context) {
                let label = context.dataset.label || '';
                if (label) {
                  label += ': ';
                }
                label += new Intl.NumberFormat('fr-FR', {
                  style: 'currency',
                  currency: 'XOF',
                  minimumFractionDigits: 0
                }).format(context.parsed.y);
                return label;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return new Intl.NumberFormat('fr-FR', {
                  notation: 'compact',
                  minimumFractionDigits: 0
                }).format(value) + ' FCFA';
              }
            },
            grid: {
              color: 'rgba(0, 0, 0, 0.05)'
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });
  }

  /**
   * Charger les détails d'un budget
   */
  function loadBudgetDetails(budgetId) {
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('budgetDetailModal'));
    modal.show();

    // Réinitialiser le contenu
    $('#modal-content').html(`
      <div class="text-center">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p>Chargement...</p>
      </div>
    `);

    // Requête AJAX
    $.ajax({
      url: `/budgets/${budgetId}`,
      method: 'GET',
      success: function (response) {
        if (response.success) {
          displayBudgetDetails(response.budget, response.expenses);
        } else {
          showError('Erreur lors du chargement des détails');
        }
      },
      error: function (xhr) {
        let errorMessage = 'Erreur lors du chargement des détails';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }
        showError(errorMessage);
      }
    });
  }

  /**
   * Afficher les détails du budget dans le modal
   */
  function displayBudgetDetails(budget, expenses) {
    const statusClass = budget.status === 'actif' ? 'active' : 'closed';
    const statusLabel = budget.status === 'actif' ? 'Actif' : 'Clôturé';

    let html = `
      <div class="budget-detail">
        <div class="detail-header">
          <h4>
            Budget du ${formatDate(budget.start_date)}
            ${budget.end_date ? ' au ' + formatDate(budget.end_date) : ''}
          </h4>
          <span class="budget-status status-${statusClass}">${statusLabel}</span>
        </div>

        <div class="detail-stats">
          <div class="stat-item">
            <span class="stat-label">Budget initial</span>
            <span class="stat-value">${formatCurrency(budget.initial_amount)}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Total dépensé</span>
            <span class="stat-value spent">${formatCurrency(budget.total_expenses)}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Dépenses payées</span>
            <span class="stat-value">${formatCurrency(budget.paid_expenses)}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Dépenses en attente</span>
            <span class="stat-value warning">${formatCurrency(budget.pending_expenses)}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Restant</span>
            <span class="stat-value remaining">${formatCurrency(budget.remaining_amount)}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Utilisation</span>
            <span class="stat-value">${budget.usage_percent}%</span>
          </div>
        </div>

        <div class="detail-progress">
          <div class="progress-bar">
            <div class="progress-fill ${budget.usage_percent > 100 ? 'over-budget' : (budget.usage_percent > 80 ? 'warning' : '')}"
                 style="width: ${Math.min(budget.usage_percent, 100)}%"></div>
          </div>
        </div>

        <div class="expenses-header mt-4">
          <h5>
            <i class="fas fa-receipt"></i> Dépenses détaillées
            <span class="expense-count-badge">${budget.expense_count}</span>
          </h5>
          ${expenses.length > 3 ? `
            <div class="expense-search-box">
              <input type="text" class="form-control form-control-sm" id="expenseSearch" placeholder="Rechercher...">
            </div>
          ` : ''}
        </div>

        ${expenses.length > 0 ? `
          <div class="expenses-list" id="expensesList">
            ${expenses.map(expense => {
              // Définir l'icône et la couleur selon la catégorie
              let categoryIcon = 'fa-wallet';
              let categoryColor = '#6b7280';
              let categoryName = 'Autre';

              // Logique simplifiée pour les catégories
              if (expense.category) {
                categoryName = expense.category;
                if (expense.category === 'fixe') {
                  categoryIcon = 'fa-calendar-check';
                  categoryColor = '#3b82f6';
                } else if (expense.category === 'diver') {
                  categoryIcon = 'fa-shopping-cart';
                  categoryColor = '#8b5cf6';
                } else if (expense.category === 'epargne') {
                  categoryIcon = 'fa-piggy-bank';
                  categoryColor = '#10b981';
                }
              }

              return `
                <div class="expense-item" data-description="${escapeHtml(expense.description).toLowerCase()}">
                  <div class="expense-left">
                    <div class="expense-category-icon" style="background-color: ${categoryColor}20; color: ${categoryColor}">
                      <i class="fas ${categoryIcon}"></i>
                    </div>
                    <div class="expense-info">
                      <div class="expense-title">
                        <strong>${escapeHtml(expense.description)}</strong>
                        <span class="expense-category-badge" style="background-color: ${categoryColor}20; color: ${categoryColor}">
                          ${categoryName}
                        </span>
                      </div>
                      <span class="expense-date">
                        <i class="far fa-calendar"></i> ${formatDate(expense.payment_date)}
                      </span>
                    </div>
                  </div>
                  <div class="expense-right">
                    <span class="expense-amount">${formatCurrency(expense.amount)}</span>
                    <span class="expense-status status-${expense.status}">
                      <i class="fas ${expense.status === 'paid' ? 'fa-check-circle' : 'fa-clock'}"></i>
                      ${expense.status === 'paid' ? 'Payé' : 'En attente'}
                    </span>
                  </div>
                </div>
              `;
            }).join('')}
          </div>

          ${expenses.length > 3 ? `
            <script>
              document.getElementById('expenseSearch')?.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const expenseItems = document.querySelectorAll('#expensesList .expense-item');
                expenseItems.forEach(item => {
                  const description = item.getAttribute('data-description');
                  if (description.includes(searchTerm)) {
                    item.style.display = 'flex';
                  } else {
                    item.style.display = 'none';
                  }
                });
              });
            </script>
          ` : ''}
        ` : '<div class="no-expenses"><i class="fas fa-inbox"></i><p>Aucune dépense enregistrée pour ce budget</p></div>'}
      </div>
    `;

    $('#modal-content').html(html);
  }

  /**
   * Afficher un message d'erreur dans le modal
   */
  function showError(message) {
    $('#modal-content').html(`
      <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-circle"></i> ${message}
      </div>
    `);
  }

  /**
   * Formater une date
   */
  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    });
  }

  /**
   * Formater un montant en devise
   */
  function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(amount) + ' FCFA';
  }

  /**
   * Échapper le HTML
   */
  function escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
  }

  // Animation au survol des cartes
  $('.budget-card, .stat-card').hover(
    function () {
      $(this).css('box-shadow', '0 8px 16px rgba(0, 0, 0, 0.15)');
    },
    function () {
      $(this).css('box-shadow', '0 2px 8px rgba(0, 0, 0, 0.08)');
    }
  );
});
