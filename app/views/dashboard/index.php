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
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="user-info">
                <h1>Bonjour, <?= htmlspecialchars($userName) ?></h1>
            </div>
        </header>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php else: ?>
            <main class="dashboard-content">
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <h3>Budget Initial</h3>
                        <p class="stat-value">
                            <i class="fas fa-wallet"></i>
                            <?= number_format($activeBudget->initial_amount, 2) ?> €
                        </p>
                    </div>
                    <div class="stat-card">
                        <h3>Dépenses du mois</h3>
                        <p class="stat-value">
                            <i class="fas fa-shopping-cart"></i>
                            <?= number_format($activeBudget->initial_amount - $activeBudget->remaining_amount, 2) ?> €
                        </p>
                    </div>
                    <div class="stat-card">
                        <h3>Budget restant</h3>
                        <p class="stat-value <?= $activeBudget->remaining_amount < 0 ? 'text-danger' : 'text-success' ?>">
                            <i class="fas fa-<?= $activeBudget->remaining_amount < 0 ? 'exclamation-triangle' : 'piggy-bank' ?>"></i>
                            <?= number_format($activeBudget->remaining_amount, 2) ?> €
                        </p>
                    </div>
                </div>

                <div class="dashboard-actions">
                    <a href="/budget/create" class="btn-primary">
                        <i class="fas fa-plus"></i> Créer un budget
                    </a>
                    <a href="/expense/create" class="btn-primary">
                        <i class="fas fa-plus"></i> Ajouter une dépense
                    </a>
                </div>

                <?php if (!empty($budgetSummary['expenses_categories'])): ?>
                    <div class="expenses-summary">
                        <h3>Répartition des dépenses</h3>
                        <div class="categories-grid">
                            <?php foreach ($budgetSummary['expenses_categories'] as $category): ?>
                                <div class="category-card">
                                    <h4><?= ucfirst($category['category']) ?></h4>
                                    <p class="amount"><?= number_format($category['total'], 2) ?> €</p>
                                    <p class="count"><?= $category['count'] ?> dépense(s)</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        <?php endif; ?>
    </div>
</body>
</html>