/**
 * Budget Switcher - Gestion du changement de budget actif
 */
(function() {
    'use strict';

    const switcher = document.getElementById('budgetSwitcher');
    const btn = document.getElementById('budgetSwitcherBtn');
    const dropdown = document.getElementById('budgetSwitcherDropdown');
    const budgetList = document.getElementById('budgetList');
    const currentBudgetName = document.getElementById('currentBudgetName');
    const currentBudgetColor = document.getElementById('currentBudgetColor');

    if (!switcher || !btn) return;

    // Toggle dropdown
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        switcher.classList.toggle('open');
        if (switcher.classList.contains('open')) {
            loadBudgets();
        }
    });

    // Fermer au clic extérieur
    document.addEventListener('click', function(e) {
        if (!switcher.contains(e.target)) {
            switcher.classList.remove('open');
        }
    });

    // Charger les budgets
    async function loadBudgets() {
        try {
            const response = await fetch('/budget/switch/list');
            const data = await response.json();

            if (data.success) {
                renderBudgetList(data.budgets);
                updateCurrentBudget(data.budgets);
            }
        } catch (error) {
            console.error('Erreur chargement budgets:', error);
            budgetList.innerHTML = '<div class="p-3 text-center text-muted">Erreur de chargement</div>';
        }
    }

    // Afficher la liste des budgets
    function renderBudgetList(budgets) {
        if (budgets.length === 0) {
            budgetList.innerHTML = '<div class="p-3 text-center text-muted">Aucun budget actif</div>';
            return;
        }

        budgetList.innerHTML = budgets.map(budget => {
            const typeClass = budget.type === 'secondaire' ? 'secondaire' : 'principal';
            const typeLabel = budget.type === 'secondaire' ? 'Annexe' : 'Principal';
            const remaining = formatCurrency(budget.remaining_amount);

            return `
                <div class="budget-item ${budget.is_current ? 'active' : ''}" data-id="${budget.id}">
                    <span class="budget-color-dot" style="background-color: ${budget.color}"></span>
                    <div class="budget-item-info">
                        <div class="budget-item-name">${escapeHtml(budget.name)}</div>
                        <div class="budget-item-meta">
                            <span class="budget-item-type ${typeClass}">${typeLabel}</span>
                            <span>${remaining}</span>
                        </div>
                    </div>
                    ${budget.is_current ? '<i class="fas fa-check budget-item-check"></i>' : ''}
                    ${budget.type === 'secondaire' ? `
                        <div class="budget-item-actions">
                            <button class="btn-close-budget" data-id="${budget.id}" title="Clôturer ce budget">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;
        }).join('');

        // Event listeners pour switch
        budgetList.querySelectorAll('.budget-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.closest('.btn-close-budget')) return;
                const budgetId = this.dataset.id;
                switchBudget(budgetId);
            });
        });

        // Event listeners pour clôture
        budgetList.querySelectorAll('.btn-close-budget').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const budgetId = this.dataset.id;
                closeBudget(budgetId);
            });
        });
    }

    // Mettre à jour l'affichage du budget courant
    function updateCurrentBudget(budgets) {
        const current = budgets.find(b => b.is_current);
        if (current) {
            currentBudgetName.textContent = current.name;
            currentBudgetColor.style.backgroundColor = current.color;
        } else if (budgets.length > 0) {
            // Si pas de budget courant, prendre le premier
            currentBudgetName.textContent = budgets[0].name;
            currentBudgetColor.style.backgroundColor = budgets[0].color;
        } else {
            currentBudgetName.textContent = 'Aucun budget';
            currentBudgetColor.style.backgroundColor = '#9ca3af';
        }
    }

    // Changer de budget
    async function switchBudget(budgetId) {
        try {
            const response = await fetch('/budget/switch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ budget_id: budgetId })
            });

            const data = await response.json();

            if (data.success) {
                // Mettre à jour l'affichage
                currentBudgetName.textContent = data.budget.name;
                currentBudgetColor.style.backgroundColor = data.budget.color;

                // Fermer le dropdown
                switcher.classList.remove('open');

                // Recharger la page pour afficher les données du nouveau budget
                window.location.reload();
            } else {
                alert(data.message || 'Erreur lors du changement de budget');
            }
        } catch (error) {
            console.error('Erreur switch budget:', error);
            alert('Erreur lors du changement de budget');
        }
    }

    // Clôturer un budget secondaire
    async function closeBudget(budgetId) {
        if (!confirm('Voulez-vous vraiment clôturer ce budget ? Cette action est irréversible.')) {
            return;
        }

        try {
            const response = await fetch('/budget/close', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ budget_id: budgetId })
            });

            const data = await response.json();

            if (data.success) {
                // Recharger les budgets
                loadBudgets();
                // Si c'était le budget actif, recharger la page
                window.location.reload();
            } else {
                alert(data.message || 'Erreur lors de la clôture du budget');
            }
        } catch (error) {
            console.error('Erreur clôture budget:', error);
            alert('Erreur lors de la clôture du budget');
        }
    }

    // Utilitaires
    function formatCurrency(amount) {
        return new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount) + ' F';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Charger au démarrage pour afficher le budget courant
    loadBudgets();
})();
