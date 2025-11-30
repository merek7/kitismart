<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-eye"></i> Budget Partagé</h1>
        <div class="welcome-guest">
            <i class="fas fa-user-circle"></i>
            <span>Bienvenue, <strong><?= htmlspecialchars($guestName) ?></strong></span>
        </div>
    </div>

    <div class="page-content">
        <div class="container">
            <!-- Carte des permissions -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="permissions-overview-card">
                        <div class="permissions-header">
                            <i class="fas fa-shield-alt"></i>
                            <span>Vos autorisations</span>
                        </div>
                        <div class="permissions-list">
                            <div class="permission-item <?= $permissions['can_view'] ? 'active' : 'inactive' ?>">
                                <i class="fas fa-eye"></i>
                                <span>Consulter</span>
                            </div>
                            <div class="permission-item <?= $permissions['can_add'] ? 'active' : 'inactive' ?>">
                                <i class="fas fa-plus-circle"></i>
                                <span>Ajouter</span>
                            </div>
                            <div class="permission-item <?= $permissions['can_edit'] ? 'active' : 'inactive' ?>">
                                <i class="fas fa-edit"></i>
                                <span>Modifier</span>
                            </div>
                            <div class="permission-item <?= $permissions['can_delete'] ? 'active' : 'inactive' ?>">
                                <i class="fas fa-trash"></i>
                                <span>Supprimer</span>
                            </div>
                            <div class="permission-item <?= $permissions['can_view_stats'] ? 'active' : 'inactive' ?>">
                                <i class="fas fa-chart-pie"></i>
                                <span>Statistiques</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Résumé du budget -->
            <?php if ($stats): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="budget-summary-card">
                        <div class="summary-header">
                            <h3><i class="fas fa-wallet"></i> <?= htmlspecialchars($budget->name ?? 'Budget') ?></h3>
                            <span class="budget-period">
                                <i class="fas fa-calendar-alt"></i>
                                Du <?= date('d/m/Y', strtotime($budget->start_date)) ?>
                                <?= $budget->end_date ? ' au ' . date('d/m/Y', strtotime($budget->end_date)) : ' (En cours)' ?>
                            </span>
                        </div>
                        <div class="summary-stats">
                            <div class="stat-card stat-initial">
                                <div class="stat-icon">
                                    <i class="fas fa-coins"></i>
                                </div>
                                <div class="stat-info">
                                    <span class="stat-label">Budget Initial</span>
                                    <span class="stat-value"><?= number_format($budget->initial_amount, 0, ',', ' ') ?> FCFA</span>
                                </div>
                            </div>
                            <div class="stat-card stat-spent">
                                <div class="stat-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="stat-info">
                                    <span class="stat-label">Total Dépensé</span>
                                    <span class="stat-value"><?= number_format($stats['total'], 0, ',', ' ') ?> FCFA</span>
                                </div>
                            </div>
                            <div class="stat-card stat-remaining">
                                <div class="stat-icon">
                                    <i class="fas fa-piggy-bank"></i>
                                </div>
                                <div class="stat-info">
                                    <span class="stat-label">Restant</span>
                                    <span class="stat-value"><?= number_format($budget->remaining_amount, 0, ',', ' ') ?> FCFA</span>
                                </div>
                            </div>
                        </div>
                        <!-- Barre de progression -->
                        <?php
                            $percentage = $budget->initial_amount > 0
                                ? (($budget->initial_amount - $budget->remaining_amount) / $budget->initial_amount) * 100
                                : 0;
                            $progressClass = $percentage < 50 ? 'progress-success' :
                                            ($percentage < 80 ? 'progress-warning' : 'progress-danger');
                        ?>
                        <div class="budget-progress-wrapper">
                            <div class="progress-info">
                                <span>Utilisation du budget</span>
                                <span class="percentage"><?= round($percentage, 1) ?>%</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill <?= $progressClass ?>" style="width: <?= min($percentage, 100) ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Liste des dépenses -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card expenses-card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list-ul"></i> Liste des Dépenses
                                <?php if (!empty($expenses)): ?>
                                <span class="expenses-count"><?= count($expenses) ?></span>
                                <?php endif; ?>
                            </h3>
                            <?php if ($permissions['can_add']): ?>
                            <div class="card-tools">
                                <button type="button" id="add-expense-btn" class="btn btn-primary btn-action">
                                    <i class="fas fa-plus-circle"></i> Nouvelle dépense
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($expenses)): ?>
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="fas fa-inbox"></i>
                                    </div>
                                    <h4>Aucune dépense</h4>
                                    <p>Ce budget ne contient pas encore de dépenses.</p>
                                    <?php if ($permissions['can_add']): ?>
                                    <button type="button" class="btn btn-primary" id="add-expense-btn-empty">
                                        <i class="fas fa-plus-circle"></i> Ajouter une dépense
                                    </button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="expense-filters mb-3">
                                    <div class="row">
                                        <div class="col-md-8 mb-2">
                                            <div class="input-with-icon">
                                                <i class="fas fa-search"></i>
                                                <input type="text" id="expense-search" class="form-control" placeholder="Rechercher par description...">
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <div class="input-with-icon">
                                                <i class="fas fa-calendar-alt"></i>
                                                <input type="date" id="expense-date-filter" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="expenses-list">
                                    <?php foreach ($expenses as $expense): ?>
                                        <div class="expense-item expense-item-clickable"
                                             data-id="<?= $expense->id ?>"
                                             data-description="<?= htmlspecialchars($expense->description ?: 'Sans description') ?>"
                                             data-amount="<?= $expense->amount ?>"
                                             data-category="<?= htmlspecialchars($expense->category ?? 'Non catégorisé') ?>"
                                             data-status="<?= $expense->status ?>"
                                             data-date="<?= $expense->payment_date ?>"
                                             data-has-attachments="<?= !empty($expense->attachments_count) && $expense->attachments_count > 0 ? '1' : '0' ?>">
                                            <?php
                                            $moisFr = ['', 'jan', 'fév', 'mar', 'avr', 'mai', 'juin', 'juil', 'août', 'sep', 'oct', 'nov', 'déc'];
                                            $moisNum = (int)date('n', strtotime($expense->payment_date));
                                            ?>
                                            <div class="expense-date">
                                                <span class="date-day"><?= date('d', strtotime($expense->payment_date)) ?></span>
                                                <span class="date-month"><?= $moisFr[$moisNum] ?></span>
                                            </div>
                                            <div class="expense-details">
                                                <div class="expense-description">
                                                    <?= htmlspecialchars($expense->description ?: 'Sans description') ?>
                                                    <?php if (!empty($expense->attachments_count) && $expense->attachments_count > 0): ?>
                                                        <span class="attachment-badge">
                                                            <i class="fas fa-paperclip"></i> <?= $expense->attachments_count ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="expense-meta">
                                                    <span class="expense-category">
                                                        <i class="fas fa-tag"></i>
                                                        <?= htmlspecialchars(ucfirst($expense->category ?? 'Non catégorisé')) ?>
                                                    </span>
                                                    <span class="expense-status status-<?= $expense->status ?>">
                                                        <?php if ($expense->status === 'paid'): ?>
                                                            <i class="fas fa-check-circle"></i> Payé
                                                        <?php else: ?>
                                                            <i class="fas fa-clock"></i> En attente
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="expense-amount <?= $expense->status === 'paid' ? 'paid' : 'pending' ?>">
                                                <?= number_format($expense->amount, 0, ',', ' ') ?> FCFA
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($permissions['can_add']): ?>
<!-- Modal d'ajout de dépense amélioré -->
<div id="expense-modal" class="modal-overlay">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Ajouter une dépense
                </h5>
                <button type="button" class="close-modal" data-dismiss="modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="add-expense-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category">Type de dépense <span class="text-danger">*</span></label>
                                <div class="input-with-icon select-wrapper">
                                    <i class="fas fa-tag"></i>
                                    <select class="form-control select2-category" id="category" name="category" required>
                                        <option value="" disabled selected>Choisir un type</option>
                                        <optgroup label="Catégories par défaut">
                                            <?php foreach($categories as $cat): ?>
                                                <option value="<?= htmlspecialchars($cat) ?>"
                                                        data-icon="<?= $cat === 'fixe' ? 'calendar-check' : ($cat === 'epargne' ? 'piggy-bank' : 'shopping-cart') ?>">
                                                    <?= ucfirst(htmlspecialchars($cat)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <?php if (!empty($customCategories)): ?>
                                            <optgroup label="Catégories personnalisées">
                                                <?php foreach($customCategories as $customCat): ?>
                                                    <option value="custom_<?= $customCat->id ?>"
                                                            data-icon="<?= htmlspecialchars($customCat->icon) ?>"
                                                            data-color="<?= htmlspecialchars($customCat->color) ?>">
                                                        <?= htmlspecialchars($customCat->name) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                    </select>
                                    <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount">Montant (FCFA) <span class="text-danger">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-coins"></i>
                                    <input type="number"
                                           class="form-control"
                                           id="amount"
                                           name="amount"
                                           required
                                           min="0"
                                           step="1"
                                           placeholder="0">
                                    <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="payment_date">Date de paiement <span class="text-danger">*</span></label>
                                <div class="input-with-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                    <input type="date"
                                           class="form-control"
                                           id="payment_date"
                                           name="payment_date"
                                           required
                                           value="<?= date('Y-m-d') ?>">
                                    <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="status">Statut <span class="text-danger">*</span></label>
                                <div class="input-with-icon select-wrapper">
                                    <i class="fas fa-flag"></i>
                                    <select class="form-control select2-status" id="status" name="status" required>
                                        <option value="pending" data-icon="clock">En attente</option>
                                        <option value="paid" data-icon="check-circle">Payé</option>
                                    </select>
                                    <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <div class="input-with-icon">
                            <i class="fas fa-align-left"></i>
                            <textarea class="form-control"
                                      id="description"
                                      name="description"
                                      rows="3"
                                      placeholder="Détails de la dépense (optionnel)..."></textarea>
                        </div>
                    </div>

                    <!-- Section Pièces jointes -->
                    <div class="form-group attachments-section-create">
                        <label><i class="fas fa-paperclip"></i> Pièces jointes (optionnel)</label>
                        <div class="attachment-upload-zone">
                            <input type="file" class="attachment-file-input" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx" multiple style="display: none;">
                            <div class="upload-buttons">
                                <button type="button" class="btn btn-outline-primary btn-sm add-attachment-shared-btn">
                                    <i class="fas fa-paperclip"></i> <span class="desktop-only">Ajouter fichiers</span>
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm mobile-only take-photo-shared-btn">
                                    <i class="fas fa-camera"></i> Photo
                                </button>
                            </div>
                            <small class="form-text text-muted">Images, PDF, Word, Excel (max 5 MB chacun)</small>
                            <div class="attachments-preview-list"></div>
                        </div>
                    </div>

                    <div id="expense-message" class="alert-container"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="submit" form="add-expense-form" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal de détails de dépense -->
<div id="expense-details-modal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle"></i> Détails de la dépense
                </h5>
                <button type="button" class="close-modal" data-dismiss="modal" id="details-modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="expense-details-content">
                    <div class="detail-row">
                        <label><i class="fas fa-align-left"></i> Description</label>
                        <div id="detail-description" class="detail-value"></div>
                    </div>
                    <div class="detail-row">
                        <label><i class="fas fa-coins"></i> Montant</label>
                        <div id="detail-amount" class="detail-value detail-amount-value"></div>
                    </div>
                    <div class="detail-row">
                        <label><i class="fas fa-tag"></i> Catégorie</label>
                        <div id="detail-category" class="detail-value"></div>
                    </div>
                    <div class="detail-row">
                        <label><i class="fas fa-calendar-alt"></i> Date</label>
                        <div id="detail-date" class="detail-value"></div>
                    </div>
                    <div class="detail-row">
                        <label><i class="fas fa-flag"></i> Statut</label>
                        <div id="detail-status" class="detail-value"></div>
                    </div>

                    <!-- Section pièces jointes -->
                    <div class="detail-attachments" id="detail-attachments-section" style="display: none;">
                        <label><i class="fas fa-paperclip"></i> Pièces jointes</label>
                        <div id="detail-attachments-list" class="attachments-list-readonly"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Fermer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modale de visualisation de fichiers -->
<div id="file-viewer-modal" class="modal-overlay">
    <div class="modal-container file-viewer-container">
        <div class="modal-header">
            <i class="fas fa-file modal-icon"></i>
            <h3 id="file-viewer-title">Visualisation du fichier</h3>
            <button type="button" class="modal-close-btn" id="file-viewer-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body file-viewer-body">
            <div id="file-viewer-content"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" id="file-viewer-cancel">
                <i class="fas fa-times"></i> Fermer
            </button>
        </div>
    </div>
</div>

<!-- Select2 JS supprimé d'ici, doit être chargé après jQuery dans le layout -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser Select2 pour les catégories
    if (typeof $ !== 'undefined' && $.fn.select2) {
        function formatCategory(option) {
            if (!option.id) return option.text;
            var icon = $(option.element).data('icon') || 'tag';
            var color = $(option.element).data('color') || '';
            var iconHtml = '<i class="fas fa-' + icon + '"' + (color ? ' style="color:' + color + '"' : '') + '></i> ';
            return $('<span>' + iconHtml + option.text + '</span>');
        }

        $('.select2-category').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#expense-modal'),
            templateResult: formatCategory,
            templateSelection: formatCategory,
            placeholder: 'Choisir un type',
            allowClear: false
        });

        function formatStatus(option) {
            if (!option.id) return option.text;
            var icon = $(option.element).data('icon') || 'flag';
            var iconClass = option.id === 'paid' ? 'text-success' : 'text-warning';
            return $('<span><i class="fas fa-' + icon + ' ' + iconClass + '"></i> ' + option.text + '</span>');
        }

        $('.select2-status').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#expense-modal'),
            templateResult: formatStatus,
            templateSelection: formatStatus,
            minimumResultsForSearch: Infinity
        });
    }

    // Gestion du modal
    const modal = document.getElementById('expense-modal');
    const addExpenseBtn = document.getElementById('add-expense-btn');
    const addExpenseBtnEmpty = document.getElementById('add-expense-btn-empty');
    const closeModalBtns = document.querySelectorAll('[data-dismiss="modal"]');

    function openModal() {
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (addExpenseBtn) addExpenseBtn.addEventListener('click', openModal);
    if (addExpenseBtnEmpty) addExpenseBtnEmpty.addEventListener('click', openModal);

    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', closeModal);
    });

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }

    // La gestion du formulaire et des pièces jointes est dans /assets/js/budget/shared_dashboard.js
});
</script>
