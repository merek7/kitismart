<div class="savings-container">
    <!-- Header -->
    <div class="page-header">
        <div class="header-content">
            <h1><i class="fas fa-bullseye"></i> Objectifs d'Épargne</h1>
            <p class="header-subtitle">Définissez vos objectifs et suivez votre progression vers vos rêves</p>
        </div>
        <button class="btn btn-primary" id="btn-new-goal">
            <i class="fas fa-plus"></i> Nouvel Objectif
        </button>
    </div>

    <!-- Stats Overview -->
    <div class="stats-overview">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-target"></i></div>
            <div class="stat-content">
                <span class="stat-value"><?= $stats['active_count'] ?></span>
                <span class="stat-label">Objectifs actifs</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
            <div class="stat-content">
                <span class="stat-value"><?= $stats['completed_count'] ?></span>
                <span class="stat-label">Objectifs atteints</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon primary"><i class="fas fa-piggy-bank"></i></div>
            <div class="stat-content">
                <span class="stat-value"><?= number_format($stats['total_saved'], 0, ',', ' ') ?></span>
                <span class="stat-label">Total épargné (FCFA)</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning"><i class="fas fa-chart-line"></i></div>
            <div class="stat-content">
                <span class="stat-value"><?= $stats['overall_progress'] ?>%</span>
                <span class="stat-label">Progression globale</span>
            </div>
        </div>
    </div>

    <!-- Goals Grid -->
    <?php if (empty($goalsData)): ?>
    <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-seedling"></i></div>
        <h3>Aucun objectif d'épargne</h3>
        <p>Commencez à épargner pour vos projets en créant votre premier objectif !</p>
        <button class="btn btn-primary btn-lg" id="btn-first-goal">
            <i class="fas fa-plus"></i> Créer mon premier objectif
        </button>
    </div>
    <?php else: ?>
    <div class="goals-grid">
        <?php foreach ($goalsData as $item):
            $goal = $item['goal'];
            $progress = $item['progress'];
            $remaining = $item['remaining'];
            $monthly = $item['monthly_suggestion'];
            $isCompleted = $goal->status === 'atteint';
            $progressClass = $progress < 30 ? 'low' : ($progress < 70 ? 'medium' : 'high');
        ?>
        <div class="goal-card <?= $isCompleted ? 'completed' : '' ?>" data-goal-id="<?= $goal->id ?>">
            <div class="goal-header" style="--goal-color: <?= htmlspecialchars($goal->color ?? '#0d9488') ?>">
                <div class="goal-icon">
                    <i class="fas <?= htmlspecialchars($goal->icon ?? 'fa-piggy-bank') ?>"></i>
                </div>
                <div class="goal-actions">
                    <?php if (!$isCompleted): ?>
                    <button class="btn-icon btn-add-savings" title="Ajouter de l'épargne">
                        <i class="fas fa-plus-circle"></i>
                    </button>
                    <?php endif; ?>
                    <button class="btn-icon btn-edit-goal" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-history" title="Historique">
                        <i class="fas fa-history"></i>
                    </button>
                    <button class="btn-icon btn-delete-goal danger" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

            <div class="goal-body">
                <h3 class="goal-name"><?= htmlspecialchars($goal->name ?? '') ?></h3>
                <?php if ($goal->description): ?>
                <p class="goal-description"><?= htmlspecialchars($goal->description) ?></p>
                <?php endif; ?>

                <?php if ($isCompleted): ?>
                <div class="completed-badge">
                    <i class="fas fa-trophy"></i> Objectif atteint !
                </div>
                <?php endif; ?>

                <div class="goal-amounts">
                    <div class="amount-current">
                        <span class="amount-label">Épargné</span>
                        <span class="amount-value"><?= number_format($goal->current_amount, 0, ',', ' ') ?> <small>FCFA</small></span>
                    </div>
                    <div class="amount-target">
                        <span class="amount-label">Objectif</span>
                        <span class="amount-value"><?= number_format($goal->target_amount, 0, ',', ' ') ?> <small>FCFA</small></span>
                    </div>
                </div>

                <div class="progress-section">
                    <div class="progress-bar">
                        <div class="progress-fill <?= $progressClass ?>" style="width: <?= $progress ?>%"></div>
                    </div>
                    <div class="progress-info">
                        <span class="progress-percent"><?= $progress ?>%</span>
                        <?php if (!$isCompleted && $remaining > 0): ?>
                        <span class="remaining">Reste: <?= number_format($remaining, 0, ',', ' ') ?> FCFA</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($goal->target_date): ?>
                <div class="goal-deadline">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Échéance: <?= date('d/m/Y', strtotime($goal->target_date)) ?></span>
                    <?php if ($monthly && !$isCompleted): ?>
                    <span class="monthly-suggestion">
                        <i class="fas fa-calculator"></i> ~<?= number_format($monthly, 0, ',', ' ') ?> FCFA/mois
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($goal->priority === 'haute'): ?>
                <div class="priority-badge high">
                    <i class="fas fa-star"></i> Priorité haute
                </div>
                <?php elseif ($goal->priority === 'basse'): ?>
                <div class="priority-badge low">
                    <i class="fas fa-arrow-down"></i> Priorité basse
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Créer/Modifier Objectif -->
<div class="modal-overlay" id="modal-goal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-goal-title"><i class="fas fa-bullseye"></i> Nouvel Objectif</h2>
            <button class="modal-close" id="close-goal-modal">&times;</button>
        </div>
        <form id="form-goal">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="goal_id" id="goal-id" value="">

            <div class="form-group">
                <label for="goal-name">Nom de l'objectif *</label>
                <div class="input-with-icon">
                    <i class="fas fa-bullseye"></i>
                    <input type="text" class="form-control" id="goal-name" name="name" required placeholder="Ex: Voyage au Maroc">
                </div>
            </div>

            <div class="form-group">
                <label for="goal-description">Description</label>
                <div class="input-with-icon">
                    <i class="fas fa-align-left"></i>
                    <textarea class="form-control" id="goal-description" name="description" rows="2" placeholder="Décrivez votre objectif..."></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="goal-target">Montant cible * (FCFA)</label>
                    <div class="input-with-icon">
                        <i class="fas fa-coins"></i>
                        <input type="number" class="form-control amount-field" id="goal-target" name="target_amount" required min="1000" step="1000" placeholder="500 000" data-format-amount="true">
                    </div>
                </div>
                <div class="form-group">
                    <label for="goal-current">Montant initial (FCFA)</label>
                    <div class="input-with-icon">
                        <i class="fas fa-piggy-bank"></i>
                        <input type="number" class="form-control amount-field" id="goal-current" name="current_amount" min="0" step="100" value="0" placeholder="0" data-format-amount="true">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="goal-date">Date cible</label>
                    <div class="input-with-icon">
                        <i class="fas fa-calendar-alt"></i>
                        <input type="date" class="form-control" id="goal-date" name="target_date">
                    </div>
                </div>
                <div class="form-group">
                    <label for="goal-priority">Priorité</label>
                    <div class="input-with-icon">
                        <i class="fas fa-flag"></i>
                        <select class="form-control" id="goal-priority" name="priority">
                            <option value="basse">Basse</option>
                            <option value="normale" selected>Normale</option>
                            <option value="haute">Haute</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Icône</label>
                <div class="icon-picker" id="icon-picker">
                    <?php foreach ($availableIcons as $icon => $label): ?>
                    <label class="icon-option" title="<?= htmlspecialchars($label) ?>">
                        <input type="radio" name="icon" value="<?= $icon ?>" <?= $icon === 'fa-piggy-bank' ? 'checked' : '' ?>>
                        <i class="fas <?= $icon ?>"></i>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Couleur</label>
                <div class="color-picker" id="color-picker">
                    <?php foreach ($availableColors as $color => $label): ?>
                    <label class="color-option" title="<?= htmlspecialchars($label) ?>" style="--color: <?= $color ?>">
                        <input type="radio" name="color" value="<?= $color ?>" <?= $color === '#0d9488' ? 'checked' : '' ?>>
                        <span class="color-circle"></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" id="btn-cancel-goal">Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ajouter Épargne -->
<div class="modal-overlay" id="modal-add-savings">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Ajouter de l'épargne</h2>
            <button class="modal-close" id="close-savings-modal">&times;</button>
        </div>
        <form id="form-add-savings">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="goal_id" id="savings-goal-id" value="">

            <div class="goal-summary" id="savings-goal-summary">
                <!-- Rempli par JS -->
            </div>

            <div class="form-group">
                <label for="savings-amount">Montant à ajouter * (FCFA)</label>
                <div class="input-with-icon">
                    <i class="fas fa-coins"></i>
                    <input type="number" class="form-control amount-field" id="savings-amount" name="amount" required min="100" step="100" placeholder="10 000" data-format-amount="true">
                </div>
            </div>

            <div class="quick-amounts">
                <button type="button" class="btn btn-outline btn-sm" data-amount="5000">5 000</button>
                <button type="button" class="btn btn-outline btn-sm" data-amount="10000">10 000</button>
                <button type="button" class="btn btn-outline btn-sm" data-amount="25000">25 000</button>
                <button type="button" class="btn btn-outline btn-sm" data-amount="50000">50 000</button>
            </div>

            <div class="form-group">
                <label for="savings-note">Note (optionnel)</label>
                <div class="input-with-icon">
                    <i class="fas fa-sticky-note"></i>
                    <input type="text" class="form-control" id="savings-note" name="note" placeholder="Ex: Salaire de janvier">
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" id="btn-cancel-savings">Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Historique -->
<div class="modal-overlay" id="modal-history">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-history"></i> Historique des contributions</h2>
            <button class="modal-close" id="close-history-modal">&times;</button>
        </div>
        <div class="history-content" id="history-content">
            <!-- Rempli par JS -->
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-container" id="toast-container"></div>
