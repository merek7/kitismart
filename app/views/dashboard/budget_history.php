<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-6">
          <h1><i class="fas fa-history"></i> Historique des Budgets</h1>
        </div>
        <div class="col-md-6">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a></li>
            <li class="breadcrumb-item active">Historique</li>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <!-- Statistiques globales -->
  <section class="history-summary">
    <div class="container">
      <div class="stats-grid">
        <div class="stat-card">
          <i class="fas fa-folder-open icon"></i>
          <div class="stat-content">
            <span class="stat-label">Total budgets</span>
            <span class="stat-value"><?= $stats['total_budgets'] ?></span>
          </div>
        </div>

        <div class="stat-card">
          <i class="fas fa-check-circle icon icon-success"></i>
          <div class="stat-content">
            <span class="stat-label">Budgets actifs</span>
            <span class="stat-value"><?= $stats['active_budgets'] ?></span>
          </div>
        </div>

        <div class="stat-card">
          <i class="fas fa-archive icon icon-secondary"></i>
          <div class="stat-content">
            <span class="stat-label">Budgets clôturés</span>
            <span class="stat-value"><?= $stats['closed_budgets'] ?></span>
          </div>
        </div>

        <div class="stat-card">
          <i class="fas fa-chart-line icon icon-warning"></i>
          <div class="stat-content">
            <span class="stat-label">Utilisation moyenne</span>
            <span class="stat-value"><?= $stats['average_usage'] ?>%</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Filtres -->
  <section class="filter-section">
    <div class="container">
      <div class="filter-card">
        <form id="filter-form" method="GET" action="/budgets/history">
          <div class="filter-controls">
            <div class="filter-group">
              <label for="filter-year">Année</label>
              <select id="filter-year" name="year">
                <option value="">Toutes les années</option>
                <?php foreach ($availableYears as $year): ?>
                  <option value="<?= $year ?>" <?= $selectedYear == $year ? 'selected' : '' ?>>
                    <?= $year ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="filter-group">
              <label for="filter-month">Mois</label>
              <select id="filter-month" name="month">
                <option value="">Tous les mois</option>
                <?php
                $months = [
                  1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                  5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                  9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
                ];
                foreach ($months as $num => $name): ?>
                  <option value="<?= $num ?>" <?= $selectedMonth == $num ? 'selected' : '' ?>>
                    <?= $name ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="filter-group">
              <label for="filter-status">Statut</label>
              <select id="filter-status" name="status">
                <option value="">Tous les statuts</option>
                <option value="actif" <?= $selectedStatus === 'actif' ? 'selected' : '' ?>>Actif</option>
                <option value="cloturer" <?= $selectedStatus === 'cloturer' ? 'selected' : '' ?>>Clôturé</option>
              </select>
            </div>

            <div class="filter-actions">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filtrer
              </button>
              <a href="/budgets/history" class="btn btn-secondary">
                <i class="fas fa-redo"></i> Réinitialiser
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </section>

  <!-- Graphique d'évolution -->
  <section class="chart-section">
    <div class="container">
      <div class="chart-card">
        <h3><i class="fas fa-chart-area"></i> Évolution sur les 12 derniers mois</h3>
        <canvas id="evolutionChart"></canvas>
      </div>
    </div>
  </section>

  <!-- Liste des budgets -->
  <section class="budgets-section">
    <div class="container">
      <div class="section-header">
        <h3><i class="fas fa-list"></i> Tous les budgets</h3>
        <a href="/budgets/history/export" class="btn btn-export">
          <i class="fas fa-download"></i> Exporter (CSV)
        </a>
      </div>

      <div class="budgets-grid">
        <?php if (empty($budgets)): ?>
          <div class="alert-infos">
            Aucun budget trouvé avec les filtres sélectionnés.
          </div>
        <?php else: ?>
          <?php foreach ($budgets as $budget): ?>
            <?php
              $spent = $budget->initial_amount - $budget->remaining_amount;
              $usagePercent = round(($spent / $budget->initial_amount) * 100, 2);
              $statusClass = $budget->status === 'actif' ? 'active' : 'closed';
              $statusLabel = $budget->status === 'actif' ? 'Actif' : 'Clôturé';
            ?>
            <div class="budget-card" data-id="<?= $budget->id ?>">
              <div class="budget-header">
                <span class="budget-status status-<?= $statusClass ?>"><?= $statusLabel ?></span>
                <span class="budget-date">
                  <i class="fas fa-calendar"></i>
                  <?= date('d/m/Y', strtotime($budget->start_date)) ?>
                  <?php if ($budget->end_date): ?>
                    - <?= date('d/m/Y', strtotime($budget->end_date)) ?>
                  <?php endif; ?>
                </span>
              </div>

              <div class="budget-amounts">
                <div class="amount-item">
                  <span class="amount-label">Budget initial</span>
                  <span class="amount-value"><?= number_format($budget->initial_amount, 2, ',', ' ') ?> FCFA</span>
                </div>
                <div class="amount-item">
                  <span class="amount-label">Dépensé</span>
                  <span class="amount-value spent"><?= number_format($spent, 2, ',', ' ') ?> FCFA</span>
                </div>
                <div class="amount-item">
                  <span class="amount-label">Restant</span>
                  <span class="amount-value remaining"><?= number_format($budget->remaining_amount, 2, ',', ' ') ?> FCFA</span>
                </div>
              </div>

              <div class="budget-progress-bar">
                <div class="progress-fill <?= $usagePercent > 100 ? 'over-budget' : ($usagePercent > 80 ? 'warning' : '') ?>"
                     style="width: <?= min($usagePercent, 100) ?>%"></div>
              </div>
              <div class="budget-progress-label"><?= $usagePercent ?>% utilisé</div>

              <div class="budget-actions">
                <button class="btn btn-sm btn-view" data-id="<?= $budget->id ?>">
                  <i class="fas fa-eye"></i> Voir détails
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<!-- Modal de détails -->
<div class="modal fade" id="budgetDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Détails du budget</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modal-content">
        <div class="text-center">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <p>Chargement...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Données pour le graphique
const chartData = <?= json_encode($chartData) ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
