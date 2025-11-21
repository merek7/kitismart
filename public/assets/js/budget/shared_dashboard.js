// Shared Budget Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Add expense button
    const addExpenseBtn = document.getElementById('add-expense-btn');
    const expenseModal = document.getElementById('expense-modal');

    if (addExpenseBtn && expenseModal) {
        addExpenseBtn.addEventListener('click', function() {
            expenseModal.classList.add('active');
        });
    }

    // Close modal buttons
    document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) {
                modal.classList.remove('active');
            }
        });
    });

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });

    // Add expense form submission
    const addExpenseForm = document.getElementById('add-expense-form');
    if (addExpenseForm) {
        addExpenseForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const expenseMessage = document.getElementById('expense-message');
            const submitBtn = addExpenseForm.querySelector('button[type="submit"]');

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';

            // Get form data
            const formData = {
                csrf_token: addExpenseForm.querySelector('input[name="csrf_token"]').value,
                description: document.getElementById('description').value,
                amount: parseFloat(document.getElementById('amount').value),
                payment_date: document.getElementById('payment_date').value,
                category_type: document.getElementById('category_type').value,
                status: document.getElementById('status').value
            };

            try {
                const response = await fetch('/budget/shared/expense/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    showMessage(expenseMessage, 'Dépense ajoutée avec succès ! Rechargement...', 'success');

                    // Reload page after 1 second
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showMessage(expenseMessage, data.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
                }
            } catch (error) {
                console.error('Erreur:', error);
                showMessage(expenseMessage, 'Erreur lors de l\'ajout de la dépense', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
            }
        });
    }
});

// Show message function
function showMessage(element, message, type) {
    element.textContent = message;
    element.className = `message ${type}`;
    element.style.display = 'flex';

    setTimeout(() => {
        element.style.display = 'none';
    }, 5000);
}
