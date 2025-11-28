<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-wallet"></i> Gestion du Budget</h1>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a>
            <span>/</span>
            <span>Budget</span>
        </div>
    </div>

    <div class="page-content">
        <?php if(isset($activeBudget) && $activeBudget): ?>
        <!-- Budget Actif -->
        <div class="card mb-4" style="border-left: 4px solid #0d9488;">
            <div class="card-header" style="background: linear-gradient(135deg, #0d9488, #0f766e); color: white;">
                <h3 class="card-title" style="margin: 0;"><i class="fas fa-check-circle"></i> Budget Actif</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h4 style="margin: 0 0 10px 0;"><?= htmlspecialchars($activeBudget->name ?? 'Budget') ?></h4>
                        <p style="margin: 0; color: #666;">
                            <i class="fas fa-calendar"></i> Depuis le <?= date('d/m/Y', strtotime($activeBudget->start_date)) ?>
                        </p>
                        <p style="margin: 5px 0 0 0;">
                            <strong style="color: #0d9488;"><?= number_format($activeBudget->remaining_amount, 0, ',', ' ') ?> FCFA</strong>
                            <span style="color: #999;">restants sur <?= number_format($activeBudget->initial_amount, 0, ',', ' ') ?> FCFA</span>
                        </p>
                    </div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="/budget/<?= $activeBudget->id ?>/share" class="btn btn-primary" style="background: linear-gradient(135deg, #667eea, #764ba2); border: none;">
                            <i class="fas fa-share-alt"></i> Partager ce budget
                        </a>
                        <a href="/budget/shares/manage" class="btn btn-secondary">
                            <i class="fas fa-list"></i> Mes partages
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

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

                    <!-- Type de budget -->
                    <div class="form-group">
                        <label>Type de budget</label>
                        <div class="budget-type-selector">
                            <label class="budget-type-option">
                                <input type="radio" name="type" value="principal" checked>
                                <div class="budget-type-card">
                                    <div class="budget-type-icon" style="background: rgba(13, 148, 136, 0.15); color: #0d9488;">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                    <div class="budget-type-info">
                                        <strong>Budget Principal</strong>
                                        <span>Remplace votre budget actuel (salaire mensuel)</span>
                                    </div>
                                </div>
                            </label>
                            <label class="budget-type-option">
                                <input type="radio" name="type" value="secondaire">
                                <div class="budget-type-card">
                                    <div class="budget-type-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
                                        <i class="fas fa-folder-plus"></i>
                                    </div>
                                    <div class="budget-type-info">
                                        <strong>Budget Annexe</strong>
                                        <span>Projet à part (travaux, voyage, etc.)</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

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
                            <label for="amount">Montant du Budget</label>

                            <!-- Toggle Switch pour budget indéfini -->
                            <div class="budget-limit-toggle">
                                <div class="toggle-container">
                                    <input type="checkbox" id="unlimited-budget" name="unlimited_budget" class="toggle-input">
                                    <label for="unlimited-budget" class="toggle-label">
                                        <div class="toggle-switch">
                                            <span class="toggle-option toggle-limited">
                                                <i class="fas fa-wallet"></i>
                                                <span>Montant défini</span>
                                            </span>
                                            <span class="toggle-option toggle-unlimited">
                                                <i class="fas fa-infinity"></i>
                                                <span>Montant indéfini</span>
                                            </span>
                                        </div>
                                    </label>
                                </div>
                                <small class="toggle-hint">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="hint-limited">Indiquez le montant prévu pour ce budget</span>
                                    <span class="hint-unlimited" style="display: none;">Utile si le coût final est inconnu (rénovations, projets évolutifs...)</span>
                                </small>
                            </div>

                            <!-- Champ montant -->
                            <div class="input-with-icon" id="amount-field">
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

                    <!-- Couleur du budget -->
                    <div class="form-group">
                        <label>Couleur du budget</label>
                        <div class="color-selector">
                            <?php
                            $colors = [
                                '#0d9488' => 'Teal',
                                '#3b82f6' => 'Bleu',
                                '#8b5cf6' => 'Violet',
                                '#ec4899' => 'Rose',
                                '#f59e0b' => 'Orange',
                                '#10b981' => 'Vert',
                                '#ef4444' => 'Rouge',
                                '#6366f1' => 'Indigo',
                            ];
                            $first = true;
                            foreach ($colors as $hex => $name):
                            ?>
                                <label class="color-option" title="<?= $name ?>">
                                    <input type="radio" name="color" value="<?= $hex ?>" <?= $first ? 'checked' : '' ?>>
                                    <span class="color-dot" style="background-color: <?= $hex ?>"></span>
                                </label>
                            <?php
                            $first = false;
                            endforeach;
                            ?>
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
