<?php
$title = 'Dashboard - KitiSmart';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - KitiSmart</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="user-info">
                <h1>Bonjour, <?= htmlspecialchars($userName) ?> 👋</h1>
                <p class="subtitle">Voici un résumé de votre situation financière</p>
            </div>
        </header>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php else: ?>

            <main class="dashboard-content">
                <!-- Barre de progression du budget -->
                <div class="budget-overview">
                    <?php
                    $budgetUsedPercent = $activeBudget->initial_amount > 0
                        ? (($activeBudget->initial_amount - $activeBudget->remaining_amount) / $activeBudget->initial_amount) * 100
                        : 0;
                    $progressClass = $budgetUsedPercent >= 90 ? 'danger' : ($budgetUsedPercent >= 70 ? 'warning' : 'success');
                    ?>
                    <div class="progress-section">
                        <div class="progress-header">
                            <span class="progress-label">Utilisation du budget</span>
                            <span class="progress-percentage"><?= number_format($budgetUsedPercent, 1) ?>%</span>
                        </div>
                        <div class="budget-progress-bar">
                            <div class="progress-bar-fill <?= $progressClass ?>"
                                 data-progress="<?= $budgetUsedPercent ?>"
                                 style="width: 0%">
                            </div>
                        </div>
                        <div class="progress-footer">
                            <span><?= number_format($activeBudget->initial_amount - $activeBudget->remaining_amount, 2) ?> € utilisés</span>
                            <span><?= number_format($activeBudget->initial_amount, 2) ?> € total</span>
                        </div>
                    </div>
                </div>

                <div class="dashboard-stats">
                    <div class="stat-card" data-delay="0">
                        <div class="stat-icon success">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Budget Initial</h3>
                            <p class="stat-value" data-value="<?= $activeBudget->initial_amount ?>">
                                0 €
                            </p>
                        </div>
                    </div>

                    <div class="stat-card" data-delay="100">
                        <div class="stat-icon primary">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Dépenses du mois</h3>
                            <p class="stat-value" data-value="<?= $activeBudget->initial_amount - $activeBudget->remaining_amount ?>">
                                0 €
                            </p>
                        </div>
                    </div>

                    <div class="stat-card" data-delay="200">
                        <div class="stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Dépenses en attente</h3>
                            <p class="stat-value" data-value="<?= $depenseEnAttente ?>">
                                0 FCFA
                            </p>
                        </div>
                    </div>

                    <div class="stat-card" data-delay="300">
                        <div class="stat-icon <?= $activeBudget->remaining_amount < 0 ? 'danger' : 'success' ?>">
                            <i class="fas fa-<?= $activeBudget->remaining_amount < 0 ? 'exclamation-triangle' : 'piggy-bank' ?>"></i>
                        </div>
                        <div class="stat-content">
                            <h3>Budget restant</h3>
                            <p class="stat-value <?= $activeBudget->remaining_amount < 0 ? 'text-danger' : 'text-success' ?>"
                               data-value="<?= $activeBudget->remaining_amount ?>">
                                0 FCFA
                            </p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-actions">
                    <a href="/budget/create" class="btn-action btn-primary">
                        <i class="fas fa-plus-circle"></i>
                        <span>Créer un budget</span>
                    </a>
                    <a href="/expenses/create" class="btn-action btn-secondary">
                        <i class="fas fa-receipt"></i>
                        <span>Ajouter une dépense</span>
                    </a>
                    <a href="/expenses/list" class="btn-action btn-outline">
                        <i class="fas fa-list"></i>
                        <span>Voir les dépenses</span>
                    </a>
                </div>

                <!-- Section Graphiques -->
                <div class="charts-section">
                    <h2 class="section-title">
                        <i class="fas fa-chart-line"></i> Visualisation des données
                    </h2>

                    <div class="charts-grid">
                        <!-- Graphique 1: Répartition par catégorie (Donut) -->
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3><i class="fas fa-chart-pie"></i> Répartition par catégorie</h3>
                                <span class="chart-subtitle">Dépenses par type</span>
                            </div>
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>

                        <!-- Graphique 2: Budget vs Dépensé (Bar) -->
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3><i class="fas fa-chart-bar"></i> Budget vs Dépensé</h3>
                                <span class="chart-subtitle">Comparaison mensuelle</span>
                            </div>
                            <div class="chart-container">
                                <canvas id="budgetComparisonChart"></canvas>
                            </div>
                        </div>

                        <!-- Graphique 3: Statut des dépenses (Doughnut) -->
                        <div class="chart-card chart-card-full">
                            <div class="chart-header">
                                <h3><i class="fas fa-tasks"></i> Statut des dépenses</h3>
                                <span class="chart-subtitle">Payées vs En attente</span>
                            </div>
                            <div class="chart-container chart-container-horizontal">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        <?php endif; ?>
    </div>

    <!-- Données pour Chart.js -->
    <script>
        const chartData = {
            categories: <?= json_encode($budgetSummary ?? []) ?>,
            budget: {
                initial: <?= $activeBudget->initial_amount ?? 0 ?>,
                remaining: <?= $activeBudget->remaining_amount ?? 0 ?>,
                spent: <?= ($activeBudget->initial_amount ?? 0) - ($activeBudget->remaining_amount ?? 0) ?>,
                pending: <?= $depenseEnAttente ?? 0 ?>
            }
        };
    </script>

    <!-- Script Chart.js personnalisé -->
    <script src="/assets/js/dashboard/charts.js" defer></script>
</body>
</html>