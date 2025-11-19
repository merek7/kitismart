<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6 fade-in-up">
                    <h1><i class="fas fa-chart-line"></i> Tableau de bord</h1>
                    <p class="text-muted mb-0">
                        Bonjour, <strong><?= htmlspecialchars($userName) ?></strong> 👋
                    </p>
                </div>
                <div class="col-md-6 text-right fade-in-up delay-1">
                    <div class="btn-group">
                        <a href="/expenses/create" class="btn btn-primary hover-lift transition-all">
                            <i class="fas fa-plus"></i> Nouvelle dépense
                        </a>
                        <a href="/expenses/export/csv" class="btn btn-outline-success hover-lift transition-all">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </a>
                        <a href="/expenses/export/pdf" class="btn btn-outline-danger hover-lift transition-all" target="_blank">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php else: ?>

                <!-- Budget Alert -->
                <?php if ($percentUsed >= 80): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Alerte !</strong> Vous avez utilisé <?= number_format($percentUsed, 1) ?>% de votre budget.
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php elseif ($percentUsed >= 60): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <i class="fas fa-info-circle"></i>
                        <strong>Attention !</strong> Vous avez utilisé <?= number_format($percentUsed, 1) ?>% de votre budget.
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Cards des statistiques -->
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-4 fade-in-up delay-1">
                        <div class="stat-card bg-primary text-white hover-lift card">
                            <div class="stat-icon pulse">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="stat-content">
                                <h6>Budget Initial</h6>
                                <h3><?= number_format($activeBudget->initial_amount, 2, ',', ' ') ?> €</h3>
                                <small>Période en cours</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4 fade-in-up delay-2">
                        <div class="stat-card bg-danger text-white hover-lift card">
                            <div class="stat-icon pulse">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-content">
                                <h6>Total Dépensé</h6>
                                <h3><?= number_format($activeBudget->initial_amount - $activeBudget->remaining_amount, 2, ',', ' ') ?> €</h3>
                                <small><?= count($budgetSummary['categories']) ?> catégories</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4 fade-in-up delay-3">
                        <div class="stat-card bg-warning text-white hover-lift card">
                            <div class="stat-icon pulse">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <h6>En Attente</h6>
                                <h3><?= number_format($depenseEnAttente, 2, ',', ' ') ?> €</h3>
                                <small>Dépenses non payées</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4 fade-in-up delay-4">
                        <div class="stat-card bg-<?= $alertLevel ?> text-white hover-lift card">
                            <div class="stat-icon pulse">
                                <i class="fas fa-piggy-bank"></i>
                            </div>
                            <div class="stat-content">
                                <h6>Budget Restant</h6>
                                <h3><?= number_format($activeBudget->remaining_amount, 2, ',', ' ') ?> €</h3>
                                <small><?= number_format(100 - $percentUsed, 1) ?>% disponible</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="row">
                    <!-- Graphique de progression -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie text-primary"></i>
                                    Progression du Budget
                                </h5>
                            </div>
                            <div class="card-body text-center">
                                <div style="height: 250px; position: relative;">
                                    <canvas id="budgetProgressChart"
                                            data-initial="<?= $activeBudget->initial_amount ?>"
                                            data-remaining="<?= $activeBudget->remaining_amount ?>"></canvas>
                                </div>
                                <div class="mt-3">
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar bg-<?= $alertLevel ?>"
                                             role="progressbar"
                                             style="width: <?= $percentUsed ?>%"
                                             aria-valuenow="<?= $percentUsed ?>"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            <?= number_format($percentUsed, 1) ?>%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Graphique des catégories -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie text-success"></i>
                                    Répartition par Catégorie
                                </h5>
                            </div>
                            <div class="card-body">
                                <div style="height: 250px; position: relative;">
                                    <canvas id="categoryPieChart"
                                            data-categories='<?= json_encode($budgetSummary['categories']) ?>'></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Graphique des types -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar text-warning"></i>
                                    Dépenses par Type
                                </h5>
                            </div>
                            <div class="card-body">
                                <div style="height: 250px; position: relative;">
                                    <canvas id="typeBarChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liste des catégories détaillée -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-list"></i>
                                    Détail des Catégories
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Catégorie</th>
                                                <th>Type</th>
                                                <th class="text-center">Nombre de dépenses</th>
                                                <th class="text-right">Total</th>
                                                <th class="text-right">% du budget</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($budgetSummary['categories'] as $cat): ?>
                                                <?php
                                                $percentOfBudget = 0;
                                                if ($activeBudget->initial_amount > 0) {
                                                    $percentOfBudget = ($cat['total'] / $activeBudget->initial_amount) * 100;
                                                }
                                                ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                                                    <td>
                                                        <span class="badge badge-<?= $cat['type'] === 'fixe' ? 'danger' : ($cat['type'] === 'epargne' ? 'success' : 'info') ?>">
                                                            <?= htmlspecialchars($cat['type_label']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-center"><?= $cat['count'] ?></td>
                                                    <td class="text-right">
                                                        <strong><?= number_format($cat['total'], 2, ',', ' ') ?> €</strong>
                                                    </td>
                                                    <td class="text-right">
                                                        <span class="text-muted"><?= number_format($percentOfBudget, 1) ?>%</span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="thead-light">
                                            <tr>
                                                <th colspan="2">TOTAL</th>
                                                <th class="text-center"><?= array_sum(array_column($budgetSummary['categories'], 'count')) ?></th>
                                                <th class="text-right">
                                                    <strong><?= number_format($budgetSummary['total_spent'], 2, ',', ' ') ?> €</strong>
                                                </th>
                                                <th class="text-right">
                                                    <strong><?= number_format($percentUsed, 1) ?>%</strong>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-bolt"></i>
                                    Actions Rapides
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="/expenses/create" class="quick-action-card">
                                            <div class="icon bg-primary">
                                                <i class="fas fa-plus"></i>
                                            </div>
                                            <div class="content">
                                                <h6>Nouvelle Dépense</h6>
                                                <small>Ajouter une dépense</small>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="/expenses/list" class="quick-action-card">
                                            <div class="icon bg-info">
                                                <i class="fas fa-list"></i>
                                            </div>
                                            <div class="content">
                                                <h6>Voir les Dépenses</h6>
                                                <small>Liste complète</small>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="/budget/create" class="quick-action-card">
                                            <div class="icon bg-success">
                                                <i class="fas fa-wallet"></i>
                                            </div>
                                            <div class="content">
                                                <h6>Nouveau Budget</h6>
                                                <small>Créer un budget</small>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="/settings" class="quick-action-card">
                                            <div class="icon bg-secondary">
                                                <i class="fas fa-cog"></i>
                                            </div>
                                            <div class="content">
                                                <h6>Paramètres</h6>
                                                <small>Gérer votre compte</small>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </section>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="/assets/js/dashboard/charts.js"></script>

<style>
/* Stat Cards */
.stat-card {
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.2);
}

.stat-icon {
    font-size: 48px;
    opacity: 0.3;
}

.stat-content h6 {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    opacity: 0.9;
}

.stat-content h3 {
    font-size: 28px;
    font-weight: bold;
    margin: 0;
}

.stat-content small {
    font-size: 12px;
    opacity: 0.8;
}

/* Quick Action Cards */
.quick-action-card {
    display: flex;
    align-items: center;
    padding: 20px;
    border-radius: 10px;
    background: #f8f9fa;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.quick-action-card:hover {
    background: #fff;
    border-color: #007bff;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,123,255,0.2);
    text-decoration: none;
    color: #007bff;
}

.quick-action-card .icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: white;
    font-size: 20px;
}

.quick-action-card .content h6 {
    margin: 0 0 5px 0;
    font-weight: 600;
    font-size: 14px;
}

.quick-action-card .content small {
    color: #666;
    font-size: 12px;
}

/* Cards */
.card {
    border: none;
    border-radius: 12px;
}

.card-header {
    border-bottom: 1px solid #e9ecef;
    padding: 1.25rem 1.5rem;
}

/* Table */
.table thead th {
    border-top: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.row > div {
    animation: fadeIn 0.5s ease forwards;
}

.row > div:nth-child(1) { animation-delay: 0.1s; }
.row > div:nth-child(2) { animation-delay: 0.2s; }
.row > div:nth-child(3) { animation-delay: 0.3s; }
.row > div:nth-child(4) { animation-delay: 0.4s; }
</style>
