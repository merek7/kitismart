<?php
$styles = ['dashboard/financial_planner.css'];
$pageScripts = ['amount-formatter.js', 'dashboard/financial_planner.js'];
?>
<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-calculator"></i> Planificateur Financier</h1>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a>
            <span>/</span>
            <span>Planificateur</span>
        </div>
    </div>

    <div class="page-content">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($needsTagging && $mainBudget): ?>
        <!-- Alerte pour taguer le budget principal -->
        <div class="alert alert-warning tagging-alert">
            <div class="alert-icon">
                <i class="fas fa-tag"></i>
            </div>
            <div class="alert-content">
                <h4>Taguez votre budget pour utiliser le planificateur</h4>
                <p>Le planificateur a besoin de connaître la source de vos revenus. Indiquez si votre budget principal "<strong><?= htmlspecialchars($mainBudget->name) ?></strong>" (<?= number_format($mainBudget->initial_amount, 0, ',', ' ') ?> FCFA) est un salaire, une prime, etc.</p>
                <div class="tag-form">
                    <select id="tag-source" class="form-control">
                        <option value="">-- Sélectionnez une source --</option>
                        <?php foreach ($sources as $key => $label): ?>
                            <option value="<?= $key ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="btn-tag-budget" class="btn btn-primary" data-budget-id="<?= $mainBudget->id ?>">
                        <i class="fas fa-check"></i> Taguer ce budget
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Résumé financier -->
        <div class="planner-summary">
            <div class="summary-card income-card">
                <div class="summary-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="summary-content">
                    <span class="summary-label">Salaire mensuel</span>
                    <span class="summary-value" id="monthly-income"><?= number_format($summary['monthly_income'] ?? 0, 0, ',', ' ') ?> FCFA</span>
                </div>
            </div>
            <div class="summary-card bonus-card">
                <div class="summary-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <div class="summary-content">
                    <span class="summary-label">Revenus exceptionnels</span>
                    <span class="summary-value" id="exceptional-income"><?= number_format($summary['exceptional_income'] ?? 0, 0, ',', ' ') ?> FCFA</span>
                </div>
            </div>
            <div class="summary-card expense-card">
                <div class="summary-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="summary-content">
                    <span class="summary-label">Charges fixes/mois</span>
                    <span class="summary-value"><?= number_format($summary['monthly_expenses'] ?? 0, 0, ',', ' ') ?> FCFA</span>
                </div>
            </div>
            <div class="summary-card available-card">
                <div class="summary-icon">
                    <i class="fas fa-piggy-bank"></i>
                </div>
                <div class="summary-content">
                    <span class="summary-label">Disponible/mois</span>
                    <span class="summary-value <?= ($summary['monthly_available'] ?? 0) < 0 ? 'text-danger' : '' ?>" id="monthly-available">
                        <?= number_format($summary['monthly_available'] ?? 0, 0, ',', ' ') ?> FCFA
                    </span>
                </div>
            </div>
        </div>

        <div class="planner-grid">
            <!-- Vos revenus par source -->
            <div class="planner-card">
                <div class="card-header">
                    <h3><i class="fas fa-coins"></i> Vos revenus (budgets tagués)</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($budgetsBySource)): ?>
                        <div class="income-breakdown">
                            <?php foreach ($budgetsBySource as $source => $data): ?>
                                <div class="income-item">
                                    <div class="income-info">
                                        <span class="income-source">
                                            <i class="fas <?= $source === 'salaire' ? 'fa-briefcase' : ($source === 'prime' ? 'fa-star' : 'fa-money-bill') ?>"></i>
                                            <?= htmlspecialchars($data['label']) ?>
                                        </span>
                                        <span class="income-count"><?= $data['count'] ?> budget(s)</span>
                                    </div>
                                    <div class="income-amount">
                                        <strong><?= number_format($data['total'], 0, ',', ' ') ?> FCFA</strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="info-note">
                            <i class="fas fa-info-circle"></i>
                            <span>Pour ajouter des revenus, créez un budget et sélectionnez une source (salaire, prime...)</span>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-info-circle"></i>
                            <p>Aucun budget tagué. Créez un budget et sélectionnez une source de revenu.</p>
                            <a href="/budget/create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Créer un budget
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Charges fixes détaillées -->
            <div class="planner-card">
                <div class="card-header">
                    <h3><i class="fas fa-receipt"></i> Vos charges fixes</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($fixedExpenses['items'])): ?>
                        <div class="expenses-breakdown">
                            <?php foreach (array_slice($fixedExpenses['items'], 0, 6) as $expense): ?>
                                <div class="expense-item">
                                    <span class="expense-desc"><?= htmlspecialchars($expense['description']) ?></span>
                                    <span class="expense-amount"><?= number_format($expense['amount'], 0, ',', ' ') ?> FCFA</span>
                                </div>
                            <?php endforeach; ?>
                            <div class="expense-total">
                                <span>Total mensuel</span>
                                <strong><?= number_format($fixedExpenses['total'], 0, ',', ' ') ?> FCFA</strong>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state small">
                            <p>Aucune charge fixe enregistrée.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Simulateur d'objectif -->
        <div class="planner-card simulator-card">
            <div class="card-header">
                <h3><i class="fas fa-magic"></i> Simulateur intelligent</h3>
            </div>
            <div class="card-body">
                <div class="simulator-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sim-name">Nom du projet</label>
                            <div class="input-with-icon">
                                <i class="fas fa-tag"></i>
                                <input type="text" id="sim-name" class="form-control" placeholder="Ex: Voiture, Terrain, Mariage...">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="sim-target">Montant à atteindre</label>
                            <div class="input-with-icon">
                                <i class="fas fa-bullseye"></i>
                                <input type="number" id="sim-target" class="form-control" placeholder="Ex: 5 000 000" min="1000" data-format-amount="true">
                                <span class="input-suffix">FCFA</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sim-additional">
                                Revenu additionnel
                                <small class="text-muted">(prime, bonus, freelance...)</small>
                            </label>
                            <div class="input-group-combo">
                                <div class="input-with-icon flex-grow">
                                    <i class="fas fa-plus-circle"></i>
                                    <input type="number" id="sim-additional" class="form-control" placeholder="Ex: 100 000" min="0" data-format-amount="true">
                                </div>
                                <select id="sim-additional-period" class="form-control period-select">
                                    <option value="month">/ mois</option>
                                    <option value="quarter">/ trimestre</option>
                                    <option value="year">/ an</option>
                                    <option value="once">unique</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="sim-months">
                                Délai souhaité
                                <small class="text-muted">(optionnel)</small>
                            </label>
                            <div class="input-with-icon">
                                <i class="fas fa-calendar-alt"></i>
                                <input type="number" id="sim-months" class="form-control" placeholder="Laisser vide pour auto" min="1">
                                <span class="input-suffix">mois</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" id="btn-simulate" class="btn btn-primary">
                            <i class="fas fa-calculator"></i> Calculer mes options
                        </button>
                    </div>
                </div>

                <div id="simulation-result" class="simulation-result" style="display: none;">
                    <!-- Résultats injectés par JS -->
                </div>
            </div>
        </div>

        <!-- Suggestions automatiques -->
        <?php if (!empty($suggestions) && ($summary['monthly_available'] ?? 0) > 0): ?>
        <div class="planner-card suggestions-card">
            <div class="card-header">
                <h3><i class="fas fa-lightbulb"></i> Ce que vous pouvez atteindre</h3>
            </div>
            <div class="card-body">
                <div class="suggestions-grid">
                    <?php foreach ($suggestions as $suggestion): ?>
                        <div class="suggestion-card <?= $suggestion['is_realistic'] ? 'realistic' : 'ambitious' ?>">
                            <div class="suggestion-icon">
                                <i class="fas <?= $suggestion['icon'] ?>"></i>
                            </div>
                            <div class="suggestion-content">
                                <h4><?= htmlspecialchars($suggestion['name']) ?></h4>
                                <div class="suggestion-amount">
                                    <?= number_format($suggestion['amount'], 0, ',', ' ') ?> FCFA
                                </div>
                                <div class="suggestion-timeline">
                                    <?php if ($suggestion['is_realistic']): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> <?= $suggestion['years_with_bonus'] ?> ans
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> <?= $suggestion['years_needed'] ?> ans
                                        </span>
                                    <?php endif; ?>
                                    <small><?= number_format($suggestion['monthly_savings'], 0, ',', ' ') ?> FCFA/mois</small>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline use-suggestion" 
                                    data-name="<?= htmlspecialchars($suggestion['name']) ?>"
                                    data-amount="<?= $suggestion['amount'] ?>">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Conseiller IA - Style Chat -->
        <div class="ai-chat-container">
            <div class="ai-chat-header">
                <div class="ai-avatar">
                    <div class="ai-avatar-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="ai-avatar-pulse"></div>
                </div>
                <div class="ai-header-info">
                    <h3>Kiti Coach</h3>
                    <span class="ai-status-text">
                        <span class="status-dot online"></span>
                        En ligne • <span id="ai-count">5</span>/5 conseils restants
                    </span>
                </div>
            </div>
            
            <div class="ai-chat-body" id="ai-chat-body">
                <!-- Message de bienvenue -->
                <div class="ai-message bot">
                    <div class="message-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="message-content">
                        <div class="message-bubble">
                            Bonjour ! 👋 Je suis votre <strong>Coach financier IA</strong>. 
                            Comment puis-je vous aider à atteindre vos objectifs aujourd'hui ?
                        </div>
                        <div class="message-time">Maintenant</div>
                    </div>
                </div>
                
                <!-- Suggestions cliquables -->
                <div class="ai-suggestions" id="ai-suggestions">
                    <button class="suggestion-chip" data-prompt="general">
                        <i class="fas fa-chart-line"></i> Analyser mes finances
                    </button>
                    <button class="suggestion-chip" data-prompt="savings">
                        <i class="fas fa-piggy-bank"></i> Conseils épargne
                    </button>
                    <button class="suggestion-chip" data-prompt="optimize">
                        <i class="fas fa-cut"></i> Réduire mes dépenses
                    </button>
                    <button class="suggestion-chip" data-prompt="goal" id="chip-goal" style="display: none;">
                        <i class="fas fa-bullseye"></i> Atteindre mon objectif
                    </button>
                </div>
                
                <!-- Zone de typing -->
                <div class="ai-typing" id="ai-typing" style="display: none;">
                    <div class="message-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
            
            <div class="ai-chat-footer">
                <div class="ai-input-container">
                    <input type="text" class="ai-input" id="ai-custom-question" placeholder="Ex: Comment économiser 50 000 FCFA par mois ?" maxlength="200">
                    <button class="ai-send-btn" id="ai-send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="ai-footer-hint">
                    <i class="fas fa-lightbulb"></i> Questions financières uniquement (épargne, budget, dépenses...)
                </div>
            </div>
        </div>

        <!-- Objectifs en cours -->
        <?php if (!empty($savingsGoals)): ?>
        <div class="planner-card">
            <div class="card-header">
                <h3><i class="fas fa-flag-checkered"></i> Vos objectifs en cours</h3>
                <a href="/savings/goals" class="btn btn-sm btn-outline">Gérer</a>
            </div>
            <div class="card-body">
                <div class="goals-grid">
                    <?php foreach ($savingsGoals as $goal): 
                        $progress = \App\Models\SavingsGoal::getProgressPercent($goal);
                        $suggested = \App\Models\SavingsGoal::getSuggestedMonthlySavings($goal);
                        $remaining = $goal->target_amount - $goal->current_amount;
                        $monthsLeft = $suggested > 0 ? ceil($remaining / $suggested) : 0;
                    ?>
                        <div class="goal-card clickable" 
                             style="border-left-color: <?= htmlspecialchars($goal->color ?? '#0d9488') ?>"
                             data-goal-id="<?= $goal->id ?>"
                             data-goal-name="<?= htmlspecialchars($goal->name) ?>"
                             data-goal-target="<?= $goal->target_amount ?>"
                             data-goal-current="<?= $goal->current_amount ?>"
                             data-goal-monthly="<?= $goal->monthly_contribution ?? 0 ?>"
                             data-goal-date="<?= $goal->target_date ?>"
                             data-goal-progress="<?= $progress ?>"
                             data-goal-remaining="<?= $remaining ?>"
                             data-goal-months="<?= $monthsLeft ?>"
                             data-goal-icon="<?= htmlspecialchars($goal->icon ?? 'fa-piggy-bank') ?>"
                             data-goal-color="<?= htmlspecialchars($goal->color ?? '#0d9488') ?>">
                            <div class="goal-header">
                                <div class="goal-icon" style="background: <?= htmlspecialchars($goal->color ?? '#0d9488') ?>20; color: <?= htmlspecialchars($goal->color ?? '#0d9488') ?>">
                                    <i class="fas <?= htmlspecialchars($goal->icon ?? 'fa-piggy-bank') ?>"></i>
                                </div>
                                <div class="goal-info">
                                    <h4><?= htmlspecialchars($goal->name) ?></h4>
                                    <span class="goal-amount"><?= number_format($goal->target_amount, 0, ',', ' ') ?> FCFA</span>
                                </div>
                                <i class="fas fa-chevron-right goal-arrow"></i>
                            </div>
                            <div class="goal-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $progress ?>%; background: <?= htmlspecialchars($goal->color ?? '#0d9488') ?>"></div>
                                </div>
                                <span class="progress-text"><?= $progress ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de création d'objectif d'épargne -->
<div id="create-goal-modal" class="modal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-piggy-bank"></i> Créer un objectif d'épargne</h3>
            <button type="button" class="modal-close" id="close-goal-modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="goal-recap">
                <div class="recap-item">
                    <span class="recap-label">Objectif</span>
                    <span class="recap-value" id="recap-name">-</span>
                </div>
                <div class="recap-item">
                    <span class="recap-label">Montant cible</span>
                    <span class="recap-value" id="recap-amount">-</span>
                </div>
                <div class="recap-item">
                    <span class="recap-label">Épargne mensuelle</span>
                    <span class="recap-value" id="recap-monthly">-</span>
                </div>
                <div class="recap-item">
                    <span class="recap-label">Date cible</span>
                    <span class="recap-value" id="recap-date">-</span>
                </div>
            </div>
            
            <div class="goal-customization">
                <div class="form-group">
                    <label>Icône</label>
                    <div class="icon-selector">
                        <?php 
                        $defaultIcons = ['fa-piggy-bank', 'fa-car', 'fa-home', 'fa-plane', 'fa-ring', 'fa-laptop', 'fa-graduation-cap', 'fa-gift', 'fa-umbrella-beach', 'fa-briefcase'];
                        foreach ($defaultIcons as $icon): 
                        ?>
                            <button type="button" class="icon-option <?= $icon === 'fa-piggy-bank' ? 'selected' : '' ?>" data-icon="<?= $icon ?>">
                                <i class="fas <?= $icon ?>"></i>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Couleur</label>
                    <div class="color-selector">
                        <?php 
                        $colors = ['#0d9488', '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#ef4444', '#6366f1'];
                        foreach ($colors as $color): 
                        ?>
                            <button type="button" class="color-option <?= $color === '#0d9488' ? 'selected' : '' ?>" data-color="<?= $color ?>" style="background: <?= $color ?>"></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="cancel-goal-modal">Annuler</button>
            <button type="button" class="btn btn-primary" id="confirm-create-goal">
                <i class="fas fa-check"></i> Créer l'objectif
            </button>
        </div>
    </div>
</div>

<!-- Modal d'aperçu d'objectif -->
<div id="goal-preview-modal" class="modal">
    <div class="modal-backdrop"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-piggy-bank" id="preview-icon"></i> <span id="preview-title">Objectif</span></h3>
            <button type="button" class="modal-close" id="close-preview-modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="preview-progress-circle" id="preview-progress-circle">
                <svg viewBox="0 0 100 100">
                    <circle class="progress-bg" cx="50" cy="50" r="45"></circle>
                    <circle class="progress-fill" cx="50" cy="50" r="45" id="preview-circle"></circle>
                </svg>
                <div class="progress-center">
                    <span class="progress-percent" id="preview-percent">0%</span>
                    <span class="progress-label">complété</span>
                </div>
            </div>
            
            <div class="preview-details">
                <div class="preview-row">
                    <span class="preview-label">Montant cible</span>
                    <span class="preview-value" id="preview-target">-</span>
                </div>
                <div class="preview-row">
                    <span class="preview-label">Épargné</span>
                    <span class="preview-value success" id="preview-current">-</span>
                </div>
                <div class="preview-row">
                    <span class="preview-label">Reste à épargner</span>
                    <span class="preview-value" id="preview-remaining">-</span>
                </div>
                <div class="preview-row">
                    <span class="preview-label">Mensualité prévue</span>
                    <span class="preview-value" id="preview-monthly">-</span>
                </div>
                <div class="preview-row">
                    <span class="preview-label">Date cible</span>
                    <span class="preview-value" id="preview-date">-</span>
                </div>
                <div class="preview-row">
                    <span class="preview-label">Temps restant</span>
                    <span class="preview-value" id="preview-time">-</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="close-preview-btn">Fermer</button>
            <a href="/savings/goals" class="btn btn-primary">
                <i class="fas fa-cog"></i> Gérer les objectifs
            </a>
        </div>
    </div>
</div>

<script>
// Données PHP pour JS
window.PLANNER_DATA = {
    monthlyIncome: <?= $summary['monthly_income'] ?? 0 ?>,
    exceptionalIncome: <?= $summary['exceptional_income'] ?? 0 ?>,
    monthlyExpenses: <?= $summary['monthly_expenses'] ?? 0 ?>,
    monthlyAvailable: <?= $summary['monthly_available'] ?? 0 ?>,
    yearlyAvailable: <?= $summary['yearly_available'] ?? 0 ?>,
    needsTagging: <?= ($needsTagging ?? false) ? 'true' : 'false' ?>,
    mainBudgetId: <?= ($mainBudget->id ?? 0) ?>
};
</script>
