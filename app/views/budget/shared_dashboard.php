<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-eye"></i> Consultation du Budget</h1>
    </div>

    <div class="page-content">
        <div class="container">
            <!-- Informations du budget -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card budget-overview-card">
                        <div class="card-body">
                            <h3><i class="fas fa-wallet"></i> <?= htmlspecialchars($budget->name ?? 'Budget') ?></h3>
                            <div class="budget-details">
                                <div class="budget-period">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Période: Du <?= date('d/m/Y', strtotime($budget->start_date)) ?>
                                    <?= $budget->end_date ? ' au ' . date('d/m/Y', strtotime($budget->end_date)) : ' (En cours)' ?>
                                    </span>
                                </div>
                                <?php if ($stats): ?>
                                    <div class="budget-stats">
                                        <div class="stat-item">
                                            <div class="stat-icon">
                                                <i class="fas fa-coins"></i>
                                            </div>
                                            <div class="stat-details">
                                                <div class="stat-label">Budget Initial</div>
                                                <div class="stat-value"><?= number_format($budget->initial_amount, 0, ',', ' ') ?> FCFA</div>
                                            </div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-icon">
                                                <i class="fas fa-shopping-cart"></i>
                                            </div>
                                            <div class="stat-details">
                                                <div class="stat-label">Dépensé</div>
                                                <div class="stat-value text-danger"><?= number_format($stats['total'], 0, ',', ' ') ?> FCFA</div>
                                            </div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-icon">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                            <div class="stat-details">
                                                <div class="stat-label">Restant</div>
                                                <div class="stat-value text-success"><?= number_format($budget->remaining_amount, 0, ',', ' ') ?> FCFA</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Barre de progression -->
                                    <div class="budget-progress-bar">
                                        <?php
                                            $percentage = $budget->initial_amount > 0
                                                ? (($budget->initial_amount - $budget->remaining_amount) / $budget->initial_amount) * 100
                                                : 0;
                                            $progressClass = $percentage < 50 ? 'bg-success' :
                                                            ($percentage < 80 ? 'bg-warning' : 'bg-danger');
                                        ?>
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar <?= $progressClass ?>"
                                                 role="progressbar"
                                                 style="width: <?= min($percentage, 100) ?>%"
                                                 aria-valuenow="<?= $percentage ?>"
                                                 aria-valuemin="0"
                                                 aria-valuemax="100">
                                                <?= round($percentage, 1) ?>% utilisé
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions de l'invité -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card permissions-card">
                        <div class="card-body">
                            <h5><i class="fas fa-key"></i> Vos permissions</h5>
                            <div class="permissions-badges">
                                <?php if ($permissions['can_view']): ?>
                                    <span class="badge badge-primary"><i class="fas fa-eye"></i> Consulter</span>
                                <?php endif; ?>
                                <?php if ($permissions['can_add']): ?>
                                    <span class="badge badge-success"><i class="fas fa-plus"></i> Ajouter</span>
                                <?php endif; ?>
                                <?php if ($permissions['can_edit']): ?>
                                    <span class="badge badge-warning"><i class="fas fa-edit"></i> Modifier</span>
                                <?php endif; ?>
                                <?php if ($permissions['can_delete']): ?>
                                    <span class="badge badge-danger"><i class="fas fa-trash"></i> Supprimer</span>
                                <?php endif; ?>
                                <?php if ($permissions['can_view_stats']): ?>
                                    <span class="badge badge-info"><i class="fas fa-chart-bar"></i> Statistiques</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bouton d'ajout de dépense si permission -->
            <?php if ($permissions['can_add']): ?>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" id="add-expense-btn">
                            <i class="fas fa-plus-circle"></i> Ajouter une dépense
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Liste des dépenses -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list"></i> Dépenses
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($expenses)): ?>
                                <div class="no-expenses">
                                    <i class="fas fa-inbox"></i>
                                    <p>Aucune dépense enregistrée pour le moment</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Catégorie</th>
                                                <th>Montant</th>
                                                <th>Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($expenses as $expense): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y', strtotime($expense->payment_date)) ?></td>
                                                    <td><?= htmlspecialchars($expense->description) ?></td>
                                                    <td>
                                                        <span class="category-badge">
                                                            <?= htmlspecialchars($expense->category ?? 'Non catégorisé') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong><?= number_format($expense->amount, 0, ',', ' ') ?> FCFA</strong>
                                                    </td>
                                                    <td>
                                                        <?php if ($expense->status === 'paid'): ?>
                                                            <span class="badge badge-success">
                                                                <i class="fas fa-check-circle"></i> Payé
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge badge-warning">
                                                                <i class="fas fa-clock"></i> En attente
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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
<!-- Modal d'ajout de dépense -->
<div id="expense-modal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Ajouter une dépense
                </h5>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="add-expense-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="form-group">
                        <label for="description">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control"
                                  id="description"
                                  name="description"
                                  rows="2"
                                  required
                                  placeholder="Détails de la dépense..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="amount">Montant (FCFA) <span class="text-danger">*</span></label>
                        <input type="number"
                               class="form-control"
                               id="amount"
                               name="amount"
                               required
                               min="0"
                               step="1"
                               placeholder="0">
                    </div>

                    <div class="form-group">
                        <label for="payment_date">Date <span class="text-danger">*</span></label>
                        <input type="date"
                               class="form-control"
                               id="payment_date"
                               name="payment_date"
                               required
                               value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label for="category_type">Catégorie</label>
                        <select class="form-control" id="category_type" name="category_type">
                            <option value="variable">Variable</option>
                            <option value="fixe">Fixe</option>
                            <option value="epargne">Épargne</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Statut</label>
                        <select class="form-control" id="status" name="status">
                            <option value="pending">En attente</option>
                            <option value="paid">Payé</option>
                        </select>
                    </div>

                    <div id="expense-message" class="message"></div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<link rel="stylesheet" href="/assets/css/budget/shared_dashboard.css">
<script src="/assets/js/budget/shared_dashboard.js"></script>
