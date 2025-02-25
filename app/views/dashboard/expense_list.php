<!-- Dashboard/expense_list.php -->
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-6">
          <h1><i class="fas fa-receipt"></i> Liste des Dépenses</h1>
        </div>
        <div class="col-md-6">
          <ol class="breadcrumb">
            <li class="breadcrumb-item">
              <a href="/dashboard"
                ><i class="fas fa-home"></i> Tableau de bord</a
              >
            </li>
            <li class="breadcrumb-item active">Liste des Dépenses</li>
          </ol>
        </div>
      </div>
    </div>
  </section>
  <section class="expense-summary">
    <div class="container">
      <div class="expense-summary-card">
        <div class="summary-item">
          <i class="fas fa-wallet icon icon-wallet" aria-hidden="true"></i>
          <div class="summary-content">
            <span class="summary-label">Total des dépenses</span>
            <span class="summary-value" id="total-amount">
              <?= number_format($stats['total'], 2, ',', ' ') ?>
            </span>
          </div>
        </div>
        <div class="summary-item">
          <i class="fas fa-clock icon icon-clock" aria-hidden="true"></i>
          <div class="summary-content">
            <span class="summary-label">En attente</span>
            <span class="summary-value" id="pending-amount">
              <?= number_format($stats['pending'], 2, ',', ' ') ?>
            </span>
          </div>
        </div>
        <div class="summary-item">
          <i class="fas fa-check-circle icon icon-check" aria-hidden="true"></i>
          <div class="summary-content">
            <span class="summary-label">Payées</span>
            <span class="summary-value" id="paid-amount">
              <?= number_format($stats['paid'], 2, ',', ' ') ?>
            </span>
          </div>
        </div>
        <div class="summary-item">
          <i class="fas fa-receipt icon icon-receipt" aria-hidden="true"></i>
          <div class="summary-content">
            <span class="summary-label">Nombre de dépenses</span>
            <span class="summary-value" id="expenses-count">
              <?= count($expenses) ?>
            </span>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="filter-section">
  <div class="container">
    <div class="filter-card">
      <div class="filter-controls">
        <div class="filter-group">
          <label for="filter-category">Catégorie</label>
          <select id="filter-category" name="category">
          <option value="">Toutes les catégories</option>
            <?php foreach ($categories as $category): ?>
            <option value="<?= $category?>">
              <?= htmlspecialchars($category) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-group">
          <label for="filter-status">Statut</label>
          <select id="filter-status" name="status">
            <option value="">Tous les statuts</option>
            <option value="pending">En attente</option>
            <option value="paid">Payées</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="filter-date">Date</label>
          <input type="date" id="filter-date" name="date">
        </div>
    </div>
      </div>
    </div>
  </div>
</section>

<section class="expenses-sections">
  <div class="containers">
    <div class="expenses-grids">
      <?php if (empty($expenses)): ?>
        <div class="alert-infos" role="alert">
          Aucune dépense trouvée. Créez votre première dépense en cliquant sur "Nouvelle dépense".
        </div>
      <?php else: ?>
        <?php foreach ($expenses as $expense): ?>
          <?php
            $statusClass = $expense->status === 'paid' ? 'successs' : 'warnings';
            $statusText = $expense->status === 'paid' ? 'Payé' : 'En attente';
            $categoryName = '';
            foreach ($categories as $category) {
                if ($category == $expense->categorie_id) {
                    $categoryName = $category;
                    break;
                }
            }
            ?>
          <div class="expense-cards" 
               data-category="<?= $expense->categorie_id ?>" 
               data-status="<?= $expense->status  ?>" 
               data-date="<?= $expense->created_at ?>" 
               data-amount="<?= $expense->amount ?>">
            
            <div class="expense-icons">
              <i class="fas fa-wallet"></i>
            </div>

            <div class="expense-contents">
              <div class="expense-headers">
                <span class="status-badges badge-<?= $statusClass ?>"><?= $statusText ?></span>
                <span class="expense-dates"><?= date('d/m/Y', strtotime($expense->created_at)) ?></span>
              </div>

              <h4 class="expense-titles"><?= htmlspecialchars($expense->description) ?></h4>

              <div class="expense-detailss">
                <span class="category-badges"><?= htmlspecialchars($categoryName) ?></span>
                <p class="expense-amounts"><?= number_format($expense->amount, 2, ',', ' ') ?> FCFA</p>
              </div>

              <?php if (!empty($expense->description)): ?>
                <p class="expense-descriptions">
                  <i class="fas fa-info-circle"></i>
                  <?= htmlspecialchars($expense->description) ?>
                </p>
              <?php endif; ?>

              <div class="expense-actionss">
                <?php if ($expense->status !== 'paid'): ?>
                  <button type="button" class="btns btn-sms btn-successs mark-paid-btn" data-id="<?= $expense->id ?>">
                    <i class="fas fa-check"></i> Marquer payé
                  </button>
                <?php endif; ?>
                
                <button type="button" class="btns btn-sms btn-primarys edit-expense-btn" data-id="<?= $expense->id ?>">
                  <i class="fas fa-edit"></i> Modifier
                </button>
                
                <button type="button" class="btns btn-sms btn-dangers delete-expense-btn" data-id="<?= $expense->id ?>">
                  <i class="fas fa-trash"></i> Supprimer
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>



