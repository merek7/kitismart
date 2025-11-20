<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-wallet"></i> Nouveau Budget</h1>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a>
            <span>/</span>
            <span>Nouveau Budget</span>
        </div>
    </div>

    <div class="page-content">
        <?php if(isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div id="global-message" class="message"></div>

        <!-- Section de duplication de budget -->
        <div class="duplicate-budget-section">
            <div class="duplicate-header">
                <i class="fas fa-copy"></i>
                <span>Dupliquer depuis un budget existant</span>
            </div>
            <div class="duplicate-content">
                <div class="form-group">
                    <label for="budget-select">Sélectionner un budget à dupliquer (optionnel)</label>
                    <div class="input-with-icon">
                        <i class="fas fa-history"></i>
                        <select class="form-control" id="budget-select">
                            <option value="">-- Créer un nouveau budget --</option>
                            <?php if(isset($previousBudgets) && !empty($previousBudgets)): ?>
                                <?php foreach($previousBudgets as $budget): ?>
                                    <option value="<?= $budget->id ?>"
                                            data-name="<?= htmlspecialchars($budget->name ?? '') ?>"
                                            data-amount="<?= $budget->initial_amount ?>"
                                            data-description="<?= htmlspecialchars($budget->description ?? '') ?>">
                                        <?= htmlspecialchars($budget->name ?? 'Budget') ?> - <?= number_format($budget->initial_amount, 2, ',', ' ') ?> FCFA
                                        (<?= date('d/m/Y', strtotime($budget->start_date)) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" id="load-budget-btn" disabled>
                    <i class="fas fa-download"></i> Charger les données
                </button>
            </div>
        </div>

        <!-- Formulaire de création -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle"></i> Créer un Budget</h3>
            </div>

            <form id="budget-form" action="/budget/create" method="POST">
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Nom du Budget</label>
                            <div class="input-with-icon">
                                <i class="fas fa-tag"></i>
                                <input type="text"
                                    class="form-control"
                                    id="name"
                                    name="name"
                                    placeholder="Ex: Budget vacances été 2025"
                                    required>
                                <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="amount">Montant Total</label>
                            <div class="input-with-icon">
                                <i class="fas fa-coins"></i>
                                <input type="number"
                                    class="form-control"
                                    id="amount"
                                    name="amount"
                                    step="0.01"
                                    min="0.01"
                                    placeholder="0.00"
                                    required>
                                <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="start_date">Date de début</label>
                            <div class="input-with-icon">
                                <i class="fas fa-calendar-alt"></i>
                                <input type="date"
                                    class="form-control"
                                    id="start_date"
                                    name="start_date"
                                    required>
                                <span class="validation-icon"><i class="fas fa-check-circle"></i></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description (optionnel)</label>
                        <div class="input-with-icon">
                            <i class="fas fa-align-left"></i>
                            <textarea class="form-control"
                                    id="description"
                                    name="description"
                                    rows="4"
                                    placeholder="Décrivez l'objectif et les détails de ce budget..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer
                    </button>
                    <a href="/dashboard" class="btn btn-cancel">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
