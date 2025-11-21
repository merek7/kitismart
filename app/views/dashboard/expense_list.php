<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-receipt"></i> Liste des Dépenses</h1>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a>
            <span>/</span>
            <span>Liste des Dépenses</span>
        </div>
    </div>

    <div class="page-content">
        <!-- Résumé des dépenses -->
        <div class="expense-summary-card">
            <div class="summary-item">
                <i class="fas fa-wallet"></i>
                <div class="summary-content">
                    <span class="summary-label">Total des dépenses</span>
                    <span class="summary-value" id="total-amount"><?= number_format($stats['total'], 2, ',', ' ') ?> FCFA</span>
                </div>
            </div>
            <div class="summary-item">
                <i class="fas fa-clock"></i>
                <div class="summary-content">
                    <span class="summary-label">En attente</span>
                    <span class="summary-value" id="pending-amount"><?= number_format($stats['pending'], 2, ',', ' ') ?> FCFA</span>
                </div>
            </div>
            <div class="summary-item">
                <i class="fas fa-check-circle"></i>
                <div class="summary-content">
                    <span class="summary-label">Payées</span>
                    <span class="summary-value" id="paid-amount"><?= number_format($stats['paid'], 2, ',', ' ') ?> FCFA</span>
                </div>
            </div>
            <div class="summary-item">
                <i class="fas fa-receipt"></i>
                <div class="summary-content">
                    <span class="summary-label">Nombre de dépenses</span>
                    <span class="summary-value" id="expenses-count"><?= count($expenses) ?></span>
                </div>
            </div>
        </div>

        <!-- Section de filtrage -->
        <div class="filter-card">
            <div class="filter-controls">
                <div class="filter-group filter-search">
                    <label for="filter-search">Rechercher</label>
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="filter-search" name="search" class="form-control" placeholder="Rechercher une dépense...">
                    </div>
                </div>
                <div class="filter-group">
                    <label for="filter-category">Catégorie</label>
                    <select id="filter-category" class="filter-select" name="category">
                        <option value="">Toutes les catégories</option>
                        <optgroup label="Catégories par défaut">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category ?>"><?= ucfirst(htmlspecialchars($category)) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php if (!empty($customCategories)): ?>
                            <optgroup label="Mes catégories personnalisées">
                                <?php foreach ($customCategories as $customCat): ?>
                                    <option value="custom_<?= $customCat->id ?>">
                                        <?= htmlspecialchars($customCat->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-status">Statut</label>
                    <select id="filter-status" class="filter-select" name="status">
                        <option value="">Tous les statuts</option>
                        <option value="pending">En attente</option>
                        <option value="paid">Payé</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filter-date">Date</label>
                    <input type="date" id="filter-date" name="date" class="form-control">
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="button" id="reset-filters" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </button>
                </div>
            </div>
        </div>

        <!-- Grille des dépenses -->
        <div class="expenses-grid">
            <?php if (empty($expenses)): ?>
                <div class="alert-info" role="alert">
                    <i class="fas fa-info-circle"></i>
                    Aucune dépense trouvée. Créez votre première dépense en cliquant sur "Nouvelle dépense".
                </div>
            <?php else: ?>
                <?php foreach ($expenses as $expense): ?>
                    <?php
                        $statusClass = $expense->status === 'paid' ? 'success' : 'warning';
                        $statusText = $expense->status === 'paid' ? 'Payé' : 'En attente';

                        // Récupérer la catégorie (personnalisée ou par défaut)
                        $categoryName = 'Autre';
                        $categoryIcon = 'fa-wallet';
                        $categoryColor = '#6b7280';

                        // Vérifier d'abord si c'est une catégorie personnalisée
                        if (!empty($expense->custom_category_id)) {
                            $customCat = \App\Models\CustomCategory::findById(
                                (int)$expense->custom_category_id,
                                (int)$_SESSION['user_id']
                            );
                            if ($customCat && $customCat->id) {
                                $categoryName = $customCat->name;
                                $categoryIcon = $customCat->icon;
                                $categoryColor = $customCat->color;
                            }
                        }
                        // Sinon, récupérer la catégorie par défaut
                        elseif (!empty($expense->categorie_id)) {
                            $categorie = \App\Models\Categorie::findById((int)$expense->categorie_id);
                            $categoryName = $categorie && $categorie->id ? $categorie->type : 'Autre';
                        }
                    ?>
                    <div class="expense-card"
                         data-id="<?= $expense->id ?>"
                         data-category="<?= $categoryName ?>"
                         data-status="<?= $expense->status ?>"
                         data-date="<?= $expense->payment_date ?>"
                         data-amount="<?= $expense->amount ?>"
                         data-description="<?= htmlspecialchars($expense->description) ?>">

                        <div class="expense-icon">
                            <i class="fas <?= $categoryIcon ?>" style="color: <?= $categoryColor ?>"></i>
                        </div>

                        <div class="expense-content">
                            <div class="expense-header">
                                <span class="status-badge badge-<?= $statusClass ?>"><?= $statusText ?></span>
                                <span class="expense-date"><?= date('d/m/Y', strtotime($expense->payment_date)) ?></span>
                            </div>

                            <h4 class="expense-title"><?= htmlspecialchars($expense->description) ?></h4>

                            <div class="expense-details">
                                <span class="category-badge"><?= ucfirst(htmlspecialchars($categoryName)) ?></span>
                                <p class="expense-amount"><?= number_format($expense->amount, 2, ',', ' ') ?> FCFA</p>
                            </div>

                            <div class="expense-actions">
                                <?php if ($expense->status !== 'paid'): ?>
                                    <button type="button" class="btn btn-sm btn-success mark-paid-btn" data-id="<?= $expense->id ?>">
                                        <i class="fas fa-check"></i> Marquer payé
                                    </button>
                                <?php endif; ?>

                                <button type="button" class="btn btn-sm btn-primary edit-expense-btn"
                                        data-id="<?= $expense->id ?>"
                                        data-description="<?= htmlspecialchars($expense->description) ?>"
                                        data-amount="<?= $expense->amount ?>"
                                        data-category="<?= $categoryName ?>"
                                        data-date="<?= $expense->payment_date ?>"
                                        data-status="<?= $expense->status ?>">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>

                                <button type="button" class="btn btn-sm btn-danger delete-expense-btn" data-id="<?= $expense->id ?>">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if (isset($lastPage) && $lastPage > 1): ?>
            <div class="pagination-container">
                <div class="pagination-info">
                    Page <?= $page ?? 1 ?> sur <?= $lastPage ?>
                </div>
                <div class="pagination-buttons">
                    <?php if (isset($previousPage) && $previousPage >= 1): ?>
                        <a href="/expenses/list?page=<?= $previousPage ?>" class="btn btn-secondary">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </a>
                    <?php endif; ?>

                    <?php if (isset($nextPage) && $nextPage <= $lastPage): ?>
                        <a href="/expenses/list?page=<?= $nextPage ?>" class="btn btn-primary">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modale d'édition de dépense -->
<div id="edit-expense-modal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <i class="fas fa-edit modal-icon"></i>
            <h3>Modifier la dépense</h3>
        </div>
        <div class="modal-body">
            <form id="edit-expense-form">
                <input type="hidden" id="edit-expense-id">

                <div class="form-group">
                    <label for="edit-description">Description</label>
                    <div class="input-with-icon">
                        <i class="fas fa-align-left"></i>
                        <input type="text" class="form-control" id="edit-description" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit-amount">Montant (FCFA)</label>
                        <div class="input-with-icon">
                            <i class="fas fa-coins"></i>
                            <input type="number" class="form-control" id="edit-amount" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit-category">Catégorie</label>
                        <div class="input-with-icon">
                            <i class="fas fa-tag"></i>
                            <select class="form-control" id="edit-category" required>
                                <optgroup label="Catégories par défaut">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category ?>"><?= ucfirst(htmlspecialchars($category)) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php if (!empty($customCategories)): ?>
                                    <optgroup label="Mes catégories personnalisées">
                                        <?php foreach ($customCategories as $customCat): ?>
                                            <option value="custom_<?= $customCat->id ?>">
                                                <?= htmlspecialchars($customCat->name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit-date">Date de paiement</label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar-alt"></i>
                            <input type="date" class="form-control" id="edit-date" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit-status">Statut</label>
                        <div class="input-with-icon">
                            <i class="fas fa-check-circle"></i>
                            <select class="form-control" id="edit-status">
                                <option value="pending">En attente</option>
                                <option value="paid">Payé</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" id="edit-modal-cancel">
                <i class="fas fa-times"></i> Annuler
            </button>
            <button type="button" class="btn btn-primary" id="edit-modal-confirm">
                <i class="fas fa-check"></i> Enregistrer
            </button>
        </div>
    </div>
</div>

<!-- Modale de confirmation de suppression -->
<div id="delete-expense-modal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header modal-header-danger">
            <i class="fas fa-exclamation-triangle modal-icon"></i>
            <h3>Confirmer la suppression</h3>
        </div>
        <div class="modal-body">
            <p>Voulez-vous vraiment supprimer cette dépense ?</p>
            <div class="modal-info modal-info-danger">
                <i class="fas fa-info-circle"></i>
                <span>Cette action est irréversible.</span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" id="delete-modal-cancel">
                <i class="fas fa-times"></i> Annuler
            </button>
            <button type="button" class="btn btn-danger" id="delete-modal-confirm">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </div>
    </div>
</div>
