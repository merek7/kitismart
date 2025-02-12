<?php
$title = 'Dashboard - KitiSmart';
?>

<div class="dashboard-container">
    <header class="dashboard-header">
        <div class="user-info">
            <h1>Bonjour, <?= htmlspecialchars($userName) ?></h1>
        </div>
        <div class="header-actions">
            <button id="logoutBtn" class="btn-danger">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </button>
        </div>
    </header>

    <main class="dashboard-content">
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Dépenses du mois</h3>
                <p class="stat-value">0 €</p>
            </div>
            <div class="stat-card">
                <h3>Budget restant</h3>
                <p class="stat-value">0 €</p>
            </div>
        </div>

        <div class="dashboard-actions">
            <button class="btn-primary" id="addExpenseBtn">
                <i class="fas fa-plus"></i> Ajouter une dépense
            </button>
        </div>
    </main>
</div> 