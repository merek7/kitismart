<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h1><i class="fas fa-receipt"></i> Nouvelle Dépense</h1>
                </div>
                <div class="col-md-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a></li>
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
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                                        </div>
                                                        <select class="form-control select2 category-select" name="category[]" required>
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
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-euro-sign"></i></span>
                                                        </div>
                                                        <input type="number"
                                                            class="form-control amount-input"
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
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                        </div>
                                                        <input type="date"
                                                            class="form-control"
                                                            name="date[]"
                                                            required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="status">Statut</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                                                        </div>
                                                        <select class="form-control" name="status[]" required>
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
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                                </div>
                                                <textarea class="form-control"
                                                        name="description[]"
                                                        rows="2"
                                                        placeholder="Détails de la dépense..."></textarea>
                                            </div>
                                        </div>
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
    </section>
</div>