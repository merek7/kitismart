/**
 * Savings Goals - JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ==========================================
    // ÉLÉMENTS DOM
    // ==========================================
    const modalGoal = document.getElementById('modal-goal');
    const modalAddSavings = document.getElementById('modal-add-savings');
    const modalHistory = document.getElementById('modal-history');
    const formGoal = document.getElementById('form-goal');
    const formAddSavings = document.getElementById('form-add-savings');
    const toastContainer = document.getElementById('toast-container');

    // ==========================================
    // UTILITAIRES
    // ==========================================
    function formatMoney(value) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'decimal',
            maximumFractionDigits: 0
        }).format(value) + ' FCFA';
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    function openModal(modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    async function fetchApi(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, message: 'Erreur de connexion' };
        }
    }

    // ==========================================
    // GESTION DES MODALES
    // ==========================================

    // Ouvrir modal nouvel objectif
    document.getElementById('btn-new-goal')?.addEventListener('click', () => {
        document.getElementById('modal-goal-title').innerHTML = '<i class="fas fa-bullseye"></i> Nouvel Objectif';
        formGoal.reset();
        document.getElementById('goal-id').value = '';
        document.getElementById('goal-current').closest('.form-group').style.display = '';
        openModal(modalGoal);
    });

    document.getElementById('btn-first-goal')?.addEventListener('click', () => {
        document.getElementById('modal-goal-title').innerHTML = '<i class="fas fa-bullseye"></i> Nouvel Objectif';
        formGoal.reset();
        document.getElementById('goal-id').value = '';
        openModal(modalGoal);
    });

    // Fermer modales
    document.getElementById('close-goal-modal')?.addEventListener('click', () => closeModal(modalGoal));
    document.getElementById('btn-cancel-goal')?.addEventListener('click', () => closeModal(modalGoal));
    document.getElementById('close-savings-modal')?.addEventListener('click', () => closeModal(modalAddSavings));
    document.getElementById('btn-cancel-savings')?.addEventListener('click', () => closeModal(modalAddSavings));
    document.getElementById('close-history-modal')?.addEventListener('click', () => closeModal(modalHistory));

    // Fermer au clic sur overlay
    [modalGoal, modalAddSavings, modalHistory].forEach(modal => {
        modal?.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(modal);
        });
    });

    // ==========================================
    // CRUD OBJECTIFS
    // ==========================================

    // Créer/Modifier objectif
    formGoal?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(formGoal);
        const goalId = formData.get('goal_id');
        const isEdit = goalId && goalId !== '';

        const data = {
            csrf_token: formData.get('csrf_token'),
            name: formData.get('name'),
            description: formData.get('description'),
            target_amount: formData.get('target_amount'),
            current_amount: formData.get('current_amount') || 0,
            target_date: formData.get('target_date'),
            icon: formData.get('icon'),
            color: formData.get('color'),
            priority: formData.get('priority')
        };

        const url = isEdit ? `/savings/goals/${goalId}/update` : '/savings/goals/create';
        const result = await fetchApi(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (result.success) {
            showToast(result.message);
            closeModal(modalGoal);
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(result.message, 'error');
        }
    });

    // Boutons d'action sur les cartes
    document.querySelectorAll('.goal-card').forEach(card => {
        const goalId = card.dataset.goalId;

        // Éditer
        card.querySelector('.btn-edit-goal')?.addEventListener('click', () => {
            const goal = {
                name: card.querySelector('.goal-name').textContent,
                description: card.querySelector('.goal-description')?.textContent || '',
                icon: card.querySelector('.goal-icon i').className.replace('fas ', ''),
                color: getComputedStyle(card.querySelector('.goal-header')).getPropertyValue('--goal-color').trim()
            };

            // Extraire les montants
            const amounts = card.querySelectorAll('.amount-value');
            const currentAmountText = amounts[0]?.textContent.replace(/[^\d]/g, '') || '0';
            const targetAmountText = amounts[1]?.textContent.replace(/[^\d]/g, '') || '0';

            document.getElementById('modal-goal-title').innerHTML = '<i class="fas fa-edit"></i> Modifier l\'Objectif';
            document.getElementById('goal-id').value = goalId;
            document.getElementById('goal-name').value = goal.name;
            document.getElementById('goal-description').value = goal.description;
            document.getElementById('goal-target').value = targetAmountText;
            document.getElementById('goal-current').closest('.form-group').style.display = 'none';

            // Sélectionner l'icône
            const iconInput = formGoal.querySelector(`input[name="icon"][value="${goal.icon}"]`);
            if (iconInput) iconInput.checked = true;

            // Sélectionner la couleur
            const colorInput = formGoal.querySelector(`input[name="color"][value="${goal.color}"]`);
            if (colorInput) colorInput.checked = true;

            openModal(modalGoal);
        });

        // Supprimer
        card.querySelector('.btn-delete-goal')?.addEventListener('click', async () => {
            if (!confirm('Voulez-vous vraiment supprimer cet objectif ?')) return;

            const csrfToken = document.querySelector('[name="csrf_token"]').value;
            const result = await fetchApi(`/savings/goals/${goalId}/delete`, {
                method: 'POST',
                body: JSON.stringify({ csrf_token: csrfToken })
            });

            if (result.success) {
                showToast(result.message);
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                setTimeout(() => card.remove(), 300);
            } else {
                showToast(result.message, 'error');
            }
        });

        // Ajouter épargne (bouton principal)
        card.querySelector('.btn-add-savings')?.addEventListener('click', () => {
            openAddSavingsModal(goalId, card);
        });

        // Historique
        card.querySelector('.btn-history')?.addEventListener('click', async () => {
            const result = await fetchApi(`/savings/goals/${goalId}/history`);

            if (result.success) {
                const content = document.getElementById('history-content');
                if (result.history.length === 0) {
                    content.innerHTML = `
                        <div class="empty-state" style="padding: 2rem;">
                            <i class="fas fa-inbox" style="font-size: 2rem; color: #9ca3af; margin-bottom: 1rem;"></i>
                            <p>Aucune contribution enregistrée</p>
                        </div>
                    `;
                } else {
                    content.innerHTML = `
                        <div class="goal-summary">
                            <h4>${result.goal.name}</h4>
                            <p>${formatMoney(result.goal.current_amount)} / ${formatMoney(result.goal.target_amount)}</p>
                        </div>
                        <ul class="history-list">
                            ${result.history.map(item => `
                                <li class="history-item">
                                    <div>
                                        <span class="amount ${item.amount >= 0 ? 'positive' : 'negative'}">
                                            ${item.amount >= 0 ? '+' : ''}${formatMoney(item.amount)}
                                        </span>
                                        ${item.note ? `<span class="note">${item.note}</span>` : ''}
                                    </div>
                                    <span class="date">${item.date}</span>
                                </li>
                            `).join('')}
                        </ul>
                    `;
                }
                openModal(modalHistory);
            } else {
                showToast(result.message, 'error');
            }
        });

    });

    // ==========================================
    // MODAL AJOUTER ÉPARGNE
    // ==========================================
    function openAddSavingsModal(goalId, card) {
        document.getElementById('savings-goal-id').value = goalId;

        const name = card.querySelector('.goal-name').textContent;
        const amounts = card.querySelectorAll('.amount-value');
        const current = amounts[0]?.textContent || '0';
        const target = amounts[1]?.textContent || '0';
        const progressFill = card.querySelector('.progress-fill');
        const progress = progressFill ? parseFloat(progressFill.style.width) : 0;

        document.getElementById('savings-goal-summary').innerHTML = `
            <h4>${name}</h4>
            <p>${current} / ${target}</p>
            <div class="progress-mini">
                <div class="progress-mini-fill" style="width: ${progress}%"></div>
            </div>
        `;

        document.getElementById('savings-amount').value = '';
        document.getElementById('savings-note').value = '';
        openModal(modalAddSavings);
    }

    // Quick amounts dans modal
    document.querySelectorAll('.quick-amounts button').forEach(btn => {
        btn.addEventListener('click', () => {
            const amount = btn.dataset.amount;
            const input = document.getElementById('savings-amount');
            const hiddenInput = document.getElementById('savings-amount_raw');

            // Mettre à jour le champ hidden (valeur brute)
            if (hiddenInput) {
                hiddenInput.value = amount;
            }

            // Formater pour l'affichage
            if (window.AmountFormatter && window.AmountFormatter.format) {
                input.value = window.AmountFormatter.format(amount);
            } else {
                input.value = amount;
            }
        });
    });

    // Soumettre ajout d'épargne
    formAddSavings?.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(formAddSavings);
        const goalId = formData.get('goal_id');

        const result = await fetchApi(`/savings/goals/${goalId}/add`, {
            method: 'POST',
            body: JSON.stringify({
                csrf_token: formData.get('csrf_token'),
                amount: formData.get('amount'),
                note: formData.get('note')
            })
        });

        if (result.success) {
            showToast(result.message);
            closeModal(modalAddSavings);

            // Mettre à jour la carte ou recharger si objectif atteint
            if (result.goal.is_completed) {
                setTimeout(() => location.reload(), 1000);
            } else {
                const card = document.querySelector(`[data-goal-id="${goalId}"]`);
                if (card) updateGoalCard(card, result.goal);
            }
        } else {
            showToast(result.message, 'error');
        }
    });

    // ==========================================
    // MISE À JOUR VISUELLE DES CARTES
    // ==========================================
    function updateGoalCard(card, data) {
        // Mettre à jour le montant actuel
        const currentAmount = card.querySelector('.amount-current .amount-value');
        if (currentAmount) {
            currentAmount.innerHTML = `${formatMoney(data.current_amount).replace(' FCFA', '')} <small>FCFA</small>`;
        }

        // Mettre à jour la barre de progression
        const progressFill = card.querySelector('.progress-fill');
        if (progressFill) {
            progressFill.style.width = `${data.progress}%`;
            progressFill.className = 'progress-fill';
            if (data.progress < 30) progressFill.classList.add('low');
            else if (data.progress < 70) progressFill.classList.add('medium');
            else progressFill.classList.add('high');
        }

        // Mettre à jour le pourcentage
        const progressPercent = card.querySelector('.progress-percent');
        if (progressPercent) {
            progressPercent.textContent = `${data.progress}%`;
        }

        // Mettre à jour le reste
        const remaining = card.querySelector('.remaining');
        if (remaining && data.remaining > 0) {
            remaining.textContent = `Reste: ${formatMoney(data.remaining)}`;
        }

        // Animation de feedback
        card.style.transition = 'transform 0.2s ease';
        card.style.transform = 'scale(1.02)';
        setTimeout(() => {
            card.style.transform = '';
        }, 200);
    }

    // ==========================================
    // RACCOURCIS CLAVIER
    // ==========================================
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal(modalGoal);
            closeModal(modalAddSavings);
            closeModal(modalHistory);
        }
    });
});
