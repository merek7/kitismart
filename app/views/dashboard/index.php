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
                    <!-- ajoutons une card qui va boucle sur les depense pour avoir les depense en attente -->
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
                        <h3>Dépenses en attente</h3>
                        <p class="stat-value text-warning">
                            <i class="fas fa-clock"></i>
                            <?= number_format($depenseEnAttente, 2) ?> FCFA
                        </p>
                    </div>
                    <div class="stat-card">
                        <h3>Budget restant</h3>
                        <p class="stat-value <?= $activeBudget->remaining_amount < 0 ? 'text-danger' : 'text-success' ?>">
                            <i class="fas fa-<?= $activeBudget->remaining_amount < 0 ? 'exclamation-triangle' : 'piggy-bank' ?>"></i>
                            <?= number_format($activeBudget->remaining_amount, 2) ?> FCFA
                        </p>
                    </div>
                </div>

                <div class="dashboard-actions">
                    <a href="/budget/create" class="btn-primary">
                        <i class="fas fa-plus"></i> Créer un budget
                    </a>
                    <a href="/expenses/create" class="btn-primary">
                        <i class="fas fa-plus"></i> Ajouter une dépense
                    </a>
                </div>

            </main>
        <?php endif; ?>
    </div>
</body>
</html>