<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-receipt"></i> Nouvelle Dépense</h1>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a>
            <span>/</span>
            <span>Nouvelle Dépense</span>
        </div>
    </div>

    <div class="page-content">
        <div class="container">
            <!-- Résumé des dépenses -->
            <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="expense-summary-card">
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
                                    <span id="total-amount" class="summary-value">0 FCFA</span>
                                </div>
                            </div>
                            <div class="summary-item">
                                <i class="fas fa-wallet"></i>
                                <div class="summary-content">
                                    <span class="summary-label">Budget restant</span>
                                    <span id="remaining-budget" class="summary-value" data-initial-budget="<?= htmlspecialchars($budget) ?>"><?= htmlspecialchars($budget) ?> FCFA</span>
                                    <div class="budget-progress">
                                        <div class="progress-bar" style="width: 0%"></div>
                                    </div>
                                    <div class="budget-status">
                                        <span>0%</span>
                                        <span>Budget: <?= htmlspecialchars($budget) ?> FCFA</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-plus-circle"></i> Ajouter des dépenses
                            </h3>
                            <div class="card-tools">
                                <button type="button" id="add-expense" class="btn btn-tool btn-action">
                                    <i class="fas fa-plus"></i> Nouvelle dépense
                                </button>
                                <a href="/expenses/list" class="btn btn-tool btn-action">
                                    <i class="fas fa-list"></i> Liste des dépenses
                                </a>
                            </div>
                        </div>

                        <div id="global-message" class="message"></div>

                        <form id="expense-form" action="/expenses/create" method="POST">
                            <div class="card-body">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                
                                <div id="expense-container" class="expense-scroll">
                                    <div class="expense-item">
                                        <div class="expense-header">
                                            <span class="expense-number">#1</span>
                                            <button type="button" class="btn-remove" title="Supprimer cette dépense">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="category">Type de dépense</label>
                                                    <div class="input-with-icon select-wrapper">
                                                        <i class="fas fa-tag"></i>
                                                        <select class="form-control select2 category-select" name="category[]" required>
                                                            <option value="" disabled selected>Choisir un type</option>
                                                            <optgroup label="Catégories par défaut">
                                                                <?php foreach($categories as $cat): ?>
                                                                    <option value="<?= htmlspecialchars($cat) ?>"
                                                                            data-icon="<?= $cat === 'fixe' ? 'calendar-check' : ($cat === 'epargne' ? 'piggy-bank' : 'shopping-cart') ?>"
                                                                            data-type="default">
                                                                        <?= ucfirst(htmlspecialchars($cat)) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </optgroup>
                                                            <?php if (!empty($customCategories)): ?>
                                                                <optgroup label="Mes catégories personnalisées">
                                                                    <?php foreach($customCategories as $customCat): ?>
                                                                        <option value="custom_<?= $customCat->id ?>"
                                                                                data-icon="<?= htmlspecialchars($customCat->icon) ?>"
                                                                                data-color="<?= htmlspecialchars($customCat->color) ?>"
                                                                                data-type="custom">
                                                                            <i class="fas <?= htmlspecialchars($customCat->icon) ?>"></i> <?= htmlspecialchars($customCat->name) ?>
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
                                                    <label for="amount">Montant</label>
                                                    <div class="input-with-icon">
                                                        <i class="fas fa-coins"></i>
                                                        <input type="number"
                                                            class="form-control amount-input"
                                                            name="amount[]"
                                                            placeholder="0.00"
                                                            min="0"
                                                            step="0.01"
                                                            required>
                                                        <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="date">Date de paiement</label>
                                                    <div class="input-with-icon">
                                                        <i class="fas fa-calendar-alt"></i>
                                                        <input type="date"
                                                            class="form-control"
                                                            name="date[]"
                                                            required>
                                                        <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="status">Statut</label>
                                                    <div class="input-with-icon select-wrapper">
                                                        <i class="fas fa-flag"></i>
                                                        <select class="form-control" name="status[]" required>
                                                            <option value="pending">En attente</option>
                                                            <option value="paid">Payé</option>
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
                                                        name="description[]"
                                                        rows="2"
                                                        placeholder="Détails de la dépense..."></textarea>
                                            </div>
                                        </div>

                                        <!-- Sélecteur d'objectif d'épargne (affiché seulement si catégorie = epargne) -->
                                        <?php if (!empty($savingsGoals)): ?>
                                        <div class="form-group savings-goal-selector" style="display: none;">
                                            <label><i class="fas fa-bullseye"></i> Lier à un objectif d'épargne</label>
                                            <div class="savings-goals-list">
                                                <label class="savings-goal-option">
                                                    <input type="radio" name="savings_goal_id[]" value="" checked>
                                                    <div class="goal-option-content">
                                                        <span class="goal-option-name">Ne pas lier à un objectif</span>
                                                        <span class="goal-option-desc">L'épargne ne sera pas suivie</span>
                                                    </div>
                                                </label>
                                                <?php foreach ($savingsGoals as $goal):
                                                    $progress = $goal->target_amount > 0 ? round(($goal->current_amount / $goal->target_amount) * 100, 1) : 0;
                                                    $remaining = max(0, $goal->target_amount - $goal->current_amount);
                                                ?>
                                                <label class="savings-goal-option">
                                                    <input type="radio" name="savings_goal_id[]" value="<?= $goal->id ?>">
                                                    <div class="goal-option-content" style="--goal-color: <?= htmlspecialchars($goal->color ?? '#0d9488') ?>">
                                                        <div class="goal-option-icon">
                                                            <i class="fas <?= htmlspecialchars($goal->icon ?? 'fa-piggy-bank') ?>"></i>
                                                        </div>
                                                        <div class="goal-option-info">
                                                            <span class="goal-option-name"><?= htmlspecialchars($goal->name ?? '') ?></span>
                                                            <span class="goal-option-progress"><?= $progress ?>% - Reste <?= number_format($remaining, 0, ',', ' ') ?> FCFA</span>
                                                        </div>
                                                        <div class="goal-option-bar">
                                                            <div class="goal-bar-fill" style="width: <?= $progress ?>%"></div>
                                                        </div>
                                                    </div>
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer les dépenses
                                </button>
                                <a href="/dashboard" class="btn btn-cancel">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation -->
<div id="confirmation-modal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle"></i> Confirmer l'enregistrement
                </h5>
            </div>
            <div class="modal-body">
                <p>Vous êtes sur le point d'enregistrer :</p>
                <ul class="confirmation-list">
                    <li>
                        <i class="fas fa-list"></i>
                        <strong id="modal-expense-count">0</strong> dépense(s)
                    </li>
                    <li>
                        <i class="fas fa-coins"></i>
                        Pour un montant total de <strong id="modal-total-amount">0 FCFA</strong>
                    </li>
                </ul>
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i> Cette action mettra à jour votre budget.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" id="modal-cancel" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="button" id="modal-confirm" class="btn btn-primary">
                    <i class="fas fa-check"></i> Confirmer
                </button>
            </div>
        </div>
    </div>
</div>