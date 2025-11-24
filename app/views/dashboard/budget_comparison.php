<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div class="comparison-container">
    <!-- Header -->
    <div class="page-header">
        <div class="header-content">
            <h1><i class="fas fa-balance-scale"></i> Comparaison de Budgets</h1>
            <p class="header-subtitle">Analysez et comparez vos budgets pour mieux comprendre vos habitudes de dépenses</p>
        </div>
    </div>

    <!-- Sélection des budgets -->
    <div class="selection-card">
        <div class="card-header">
            <h3><i class="fas fa-check-square"></i> Sélectionner les budgets à comparer</h3>
            <span class="selection-hint">Choisissez 2 à 4 budgets</span>
        </div>
        <div class="card-body">
            <form id="comparison-form" method="GET" action="/budget/comparison" data-no-sync="true">
                <div class="budgets-grid" id="budgets-selection">
                    <?php foreach ($availableBudgets as $budget): ?>
                        <?php
                        $isSelected = in_array($budget['id'], $selectedIds);
                        $statusClass = $budget['status'] === 'actif' ? 'status-active' : 'status-closed';
                        $typeLabel = $budget['type'] === 'principal' ? 'Principal' : 'Secondaire';
                        ?>
                        <label class="budget-select-card <?= $isSelected ? 'selected' : '' ?>" data-budget-id="<?= $budget['id'] ?>">
                            <input type="checkbox" name="budgets[]" value="<?= $budget['id'] ?>" <?= $isSelected ? 'checked' : '' ?>>
                            <div class="budget-card-content">
                                <div class="budget-color-bar" style="background-color: <?= htmlspecialchars($budget['color'] ?? '#0d9488') ?>"></div>
                                <div class="budget-info">
                                    <div class="budget-header">
                                        <span class="budget-name"><?= htmlspecialchars($budget['name'] ?? 'Budget') ?></span>
                                        <span class="budget-badge <?= $statusClass ?>"><?= $budget['status'] === 'actif' ? 'Actif' : 'Clôturé' ?></span>
                                    </div>
                                    <div class="budget-type"><?= $typeLabel ?></div>
                                    <div class="budget-period">
                                        <i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($budget['period'] ?? '') ?>
                                    </div>
                                    <div class="budget-amount">
                                        <span class="amount-initial"><?= number_format($budget['initial'], 0, ',', ' ') ?> FCFA</span>
                                        <span class="amount-spent">Dépensé: <?= number_format($budget['spent'], 0, ',', ' ') ?> FCFA</span>
                                    </div>
                                </div>
                                <div class="check-indicator">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($availableBudgets)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Aucun budget disponible pour la comparaison</p>
                        <a href="/budget" class="btn btn-primary">Créer un budget</a>
                    </div>
                <?php else: ?>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-compare" id="compare-btn" disabled>
                            <i class="fas fa-chart-bar"></i> Comparer les budgets sélectionnés
                            <span class="selection-count">(0 sélectionnés)</span>
                        </button>
                        <button type="button" class="btn btn-secondary" id="clear-selection">
                            <i class="fas fa-times"></i> Effacer la sélection
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($comparison && !empty($comparison['data'])): ?>
    <!-- Résultats de comparaison -->
    <div class="comparison-results" id="comparison-results">

        <!-- Actions d'export -->
        <div class="export-actions">
            <a href="/budget/comparison/export-pdf?<?= http_build_query(['budgets' => $selectedIds]) ?>" class="btn btn-outline-primary" target="_blank">
                <i class="fas fa-file-pdf"></i> Exporter en PDF
            </a>
        </div>

        <!-- Cartes de résumé -->
        <div class="comparison-cards">
            <?php foreach ($comparison['data'] as $index => $data): ?>
                <div class="comparison-summary-card" style="--card-color: <?= htmlspecialchars($data['budget']->color ?? '#0d9488') ?>">
                    <div class="card-color-accent"></div>
                    <div class="card-content">
                        <div class="card-header">
                            <h4><?= htmlspecialchars($data['budget']->name ?? 'Budget') ?></h4>
                            <span class="card-period"><?= htmlspecialchars($data['period'] ?? '') ?></span>
                        </div>

                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-label">Budget Initial</span>
                                <span class="stat-value"><?= number_format($data['initial'], 0, ',', ' ') ?> <small>FCFA</small></span>
                            </div>
                            <div class="stat-item stat-spent">
                                <span class="stat-label">Total Dépensé</span>
                                <span class="stat-value"><?= number_format($data['spent'], 0, ',', ' ') ?> <small>FCFA</small></span>
                            </div>
                            <div class="stat-item stat-remaining">
                                <span class="stat-label">Restant</span>
                                <span class="stat-value"><?= number_format($data['remaining'], 0, ',', ' ') ?> <small>FCFA</small></span>
                            </div>
                            <div class="stat-item stat-usage">
                                <span class="stat-label">Utilisation</span>
                                <span class="stat-value"><?= $data['usage_percent'] ?>%</span>
                            </div>
                        </div>

                        <!-- Barre de progression -->
                        <div class="usage-bar">
                            <?php
                            $usageClass = $data['usage_percent'] < 50 ? 'usage-low' : ($data['usage_percent'] < 80 ? 'usage-medium' : 'usage-high');
                            ?>
                            <div class="usage-fill <?= $usageClass ?>" style="width: <?= min($data['usage_percent'], 100) ?>%"></div>
                        </div>

                        <!-- Répartition par catégorie -->
                        <div class="category-breakdown">
                            <h5>Répartition par catégorie</h5>
                            <div class="category-bars">
                                <div class="category-item">
                                    <span class="category-label"><i class="fas fa-home"></i> Fixe</span>
                                    <span class="category-value"><?= number_format($data['categories']['fixe'] ?? 0, 0, ',', ' ') ?></span>
                                </div>
                                <div class="category-item">
                                    <span class="category-label"><i class="fas fa-shopping-bag"></i> Divers</span>
                                    <span class="category-value"><?= number_format($data['categories']['diver'] ?? 0, 0, ',', ' ') ?></span>
                                </div>
                                <div class="category-item">
                                    <span class="category-label"><i class="fas fa-piggy-bank"></i> Épargne</span>
                                    <span class="category-value"><?= number_format($data['categories']['epargne'] ?? 0, 0, ',', ' ') ?></span>
                                </div>
                            </div>
                            <?php if (!empty($data['custom_categories'])): ?>
                            <h5 class="mt-3">Catégories personnalisées</h5>
                            <div class="category-bars">
                                <?php foreach ($data['custom_categories'] as $catName => $catTotal): ?>
                                <div class="category-item custom-category">
                                    <span class="category-label"><i class="fas fa-tag"></i> <?= htmlspecialchars($catName ?? '') ?></span>
                                    <span class="category-value"><?= number_format($catTotal ?? 0, 0, ',', ' ') ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="expense-count">
                            <i class="fas fa-receipt"></i> <?= $data['expense_count'] ?> dépense(s)
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($comparison['differences'])): ?>
        <!-- Tableau des différences (pour 2 budgets) -->
        <div class="differences-card">
            <div class="card-header">
                <h3><i class="fas fa-exchange-alt"></i> Évolution entre les deux budgets</h3>
            </div>
            <div class="card-body">
                <div class="differences-grid">
                    <?php
                    $diff = $comparison['differences'];
                    $items = [
                        ['label' => 'Budget Initial', 'key' => 'initial', 'icon' => 'fa-coins'],
                        ['label' => 'Dépenses', 'key' => 'spent', 'icon' => 'fa-shopping-cart'],
                        ['label' => 'Restant', 'key' => 'remaining', 'icon' => 'fa-piggy-bank'],
                        ['label' => 'Taux d\'utilisation', 'key' => 'usage_percent', 'icon' => 'fa-percent', 'isPercent' => true]
                    ];
                    foreach ($items as $item):
                        $value = $diff[$item['key']]['value'];
                        $percent = $diff[$item['key']]['percent'];
                        $isPositive = $value >= 0;
                        $isPercent = isset($item['isPercent']) && $item['isPercent'];
                    ?>
                    <div class="diff-item">
                        <div class="diff-icon">
                            <i class="fas <?= $item['icon'] ?>"></i>
                        </div>
                        <div class="diff-content">
                            <span class="diff-label"><?= $item['label'] ?></span>
                            <span class="diff-value <?= $isPositive ? 'positive' : 'negative' ?>">
                                <?= $isPositive ? '+' : '' ?><?= $isPercent ? $value . ' pts' : number_format($value, 0, ',', ' ') . ' FCFA' ?>
                                <?php if (!$isPercent && $percent != 0): ?>
                                    <small>(<?= $isPositive ? '+' : '' ?><?= $percent ?>%)</small>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="diff-arrow <?= $isPositive ? 'up' : 'down' ?>">
                            <i class="fas fa-arrow-<?= $isPositive ? 'up' : 'down' ?>"></i>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Différences par catégorie -->
                <div class="category-differences">
                    <h4>Par catégorie</h4>
                    <div class="cat-diff-grid">
                        <?php
                        $catDiff = $diff['categories'];
                        $cats = [
                            ['label' => 'Charges Fixes', 'key' => 'fixe', 'color' => '#ef4444'],
                            ['label' => 'Dépenses Diverses', 'key' => 'diver', 'color' => '#f59e0b'],
                            ['label' => 'Épargne', 'key' => 'epargne', 'color' => '#10b981']
                        ];
                        foreach ($cats as $cat):
                            $catValue = $catDiff[$cat['key']]['value'];
                            $catPercent = $catDiff[$cat['key']]['percent'];
                            $catIsPositive = $catValue >= 0;
                        ?>
                        <div class="cat-diff-item">
                            <span class="cat-diff-label" style="--cat-color: <?= $cat['color'] ?>"><?= $cat['label'] ?></span>
                            <span class="cat-diff-value <?= $catIsPositive ? 'positive' : 'negative' ?>">
                                <?= $catIsPositive ? '+' : '' ?><?= number_format($catValue, 0, ',', ' ') ?>
                                <?php if ($catPercent != 0): ?>
                                    <small>(<?= $catIsPositive ? '+' : '' ?><?= $catPercent ?>%)</small>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Graphiques -->
        <div class="charts-section">
            <div class="chart-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Comparaison des montants</h3>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="amounts-chart"></canvas>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-pie"></i> Répartition par catégorie</h3>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="categories-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphique radar -->
        <div class="chart-card chart-full-width">
            <div class="card-header">
                <h3><i class="fas fa-spider"></i> Analyse comparative</h3>
            </div>
            <div class="card-body">
                <div class="chart-container chart-container-radar">
                    <canvas id="radar-chart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Données pour JavaScript -->
    <script>
        window.comparisonData = <?= json_encode($comparison['chart_data']) ?>;
        window.comparisonDetails = <?= json_encode($comparison['data']) ?>;
    </script>
    <?php endif; ?>
</div>
