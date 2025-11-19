<div class="content-wrapper">
    <section class="content-header fade-in-up">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h1><i class="fas fa-receipt"></i> Nouvelle Dépense</h1>
                </div>
                <div class="col-md-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/dashboard" class="transition-colors"><i class="fas fa-home"></i> Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Nouvelle Dépense</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container">
            <!-- Résumé des dépenses -->
            <div class="row mb-4">
                    <div class="col-md-12 fade-in-up delay-1">
                        <div class="expense-summary-card card hover-lift">
                            <div class="summary-item">
                                <i class="fas fa-list-ul"></i>
                                <div class="summary-content">
                                    <span class="summary-label">Nombre de dépenses</span>
                                    <span id="expense-count" class="summary-value">1</span>
                                </div>
                            </div>
                            <div class="summary-item">
                                <i class="fas fa-coins"></i>
                                <div class="summary-content">
                                    <span class="summary-label">Montant total</span>
                                    <span id="total-amount" class="summary-value">0 €</span>
                                </div>
                            </div>
                            <div class="summary-item">
                                <i class="fas fa-wallet"></i>
                                <div class="summary-content">
                                    <span class="summary-label">Budget restant</span>
                                    <span id="remaining-budget" class="summary-value"><?= htmlspecialchars($budget) ?> </span>
                                    <div class="budget-progress">
                                        <div class="progress-bar" style="width: 0%"></div>
                                    </div>
                                    <div class="budget-status">
                                        <span>0%</span>
                                        <span>Budget:<?= htmlspecialchars($budget) ?> </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <div class="row">
                <div class="col-md-12 fade-in-up delay-2">
                    <div class="card hover-lift">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-plus-circle"></i> Ajouter des dépenses
                            </h3>
                            <div class="card-tools">
                                <button type="button" id="add-expense" class="btn btn-tool btn-action hover-lift transition-all">
                                    <i class="fas fa-plus"></i> Nouvelle dépense
                                </button>
                                <a href="/expenses/list" class="btn btn-tool btn-action hover-lift transition-all">
                                    <i class="fas fa-list"></i> Liste des dépenses
                                </a>
                            </div>
                        </div>

                        <div id="global-message" class="message alert" style="display: none;"></div>

                        <form id="expense-form" action="/expenses/create" method="POST">
                            <div class="card-body">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                
                                <div id="expense-container" class="expense-scroll">
                                    <div class="expense-item card fade-in">
                                        <div class="expense-header">
                                            <span class="expense-number badge">#1</span>
                                            <button type="button" class="btn-remove transition-all" title="Supprimer cette dépense">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="category">Type de dépense</label>
                                                    <div class="input-group">
                                                        <i class="fas fa-tag input-group-icon"></i>
                                                        <select class="form-control select2 category-select transition-all" name="category[]" required>
                                                                <option value="" disabled selected>Choisir un type</option>
                                                                <?php foreach($categories as $cat): ?>
                                                                    <option value="<?= htmlspecialchars($cat) ?>"
                                                                            data-icon="<?= $cat === 'fixe' ? 'calendar-check' : ($cat === 'epargne' ? 'piggy-bank' : 'shopping-cart') ?>">
                                                                        <?= ucfirst(htmlspecialchars($cat)) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="amount">Montant</label>
                                                    <div class="input-group">
                                                        <i class="fas fa-euro-sign input-group-icon"></i>
                                                        <input type="number"
                                                            class="form-control amount-input transition-all"
                                                            name="amount[]"
                                                            placeholder="0.00"
                                                            min="0"
                                                            step="0.01"
                                                            required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="date">Date de paiement</label>
                                                    <div class="input-group">
                                                        <i class="fas fa-calendar-alt input-group-icon"></i>
                                                        <input type="date"
                                                            class="form-control transition-all"
                                                            name="date[]"
                                                            required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="status">Statut</label>
                                                    <div class="input-group">
                                                        <i class="fas fa-check-circle input-group-icon"></i>
                                                        <select class="form-control transition-all" name="status[]" required>
                                                            <option value="pending">En attente</option>
                                                            <option value="paid">Payé</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <div class="input-group">
                                                <i class="fas fa-align-left input-group-icon"></i>
                                                <textarea class="form-control transition-all"
                                                        name="description[]"
                                                        rows="2"
                                                        placeholder="Détails de la dépense..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary hover-lift transition-all">
                                    <i class="fas fa-save"></i> Enregistrer les dépenses
                                </button>
                                <a href="/dashboard" class="btn btn-cancel transition-all">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
// Form submission with loading state
document.getElementById('expense-form').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;

    // Show toast notification
    const loadingToast = toast.loading('Enregistrement des dépenses en cours...');
});

// Input validation feedback
document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('blur', function() {
        if (this.value && this.checkValidity()) {
            this.classList.add('success');
            this.classList.remove('error');
        } else if (this.value && !this.checkValidity()) {
            this.classList.add('error');
            this.classList.remove('success');
        }
    });

    input.addEventListener('input', function() {
        this.classList.remove('error', 'success');
    });
});

// Add expense item animation
document.getElementById('add-expense')?.addEventListener('click', function() {
    const newExpense = document.querySelector('.expense-item').cloneNode(true);
    newExpense.classList.add('scale-in');
    document.getElementById('expense-container').appendChild(newExpense);

    // Show toast notification
    toast.success('Nouvelle dépense ajoutée');
});
</script>