<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<div class="content-wrapper">
  <section class="content-header fade-in-up">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-6">
          <h1><i class="fas fa-receipt"></i> Liste des Dépenses</h1>
        </div>
        <div class="col-md-6">
          <ol class="breadcrumb">
            <li class="breadcrumb-item">
              <a href="/dashboard" class="transition-colors"><i class="fas fa-home"></i> Tableau de bord</a>
            </li>
            <li class="breadcrumb-item active">Liste des Dépenses</li>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <section class="expense-summary">
    <div class="container">
      <div class="expense-summary-card card fade-in-up delay-1">
        <div class="summary-item hover-grow transition-all">
          <i class="fas fa-wallet icon icon-wallet pulse" aria-hidden="true"></i>
          <div class="summary-content">
            <span class="summary-label">Total des dépenses</span>
            <span class="summary-value" id="total-amount">
              <?= number_format($stats['total'], 2, ',', ' ') ?>
            </span>
          </div>
        </div>
        <div class="summary-item hover-grow transition-all">
          <i class="fas fa-clock icon icon-clock pulse" aria-hidden="true"></i>
          <div class="summary-content">
            <span class="summary-label">En attente</span>
            <span class="summary-value" id="pending-amount">
              <?= number_format($stats['pending'], 2, ',', ' ') ?>
            </span>
          </div>
        </div>
        <div class="summary-item hover-grow transition-all">
          <i class="fas fa-check-circle icon icon-check pulse" aria-hidden="true"></i>
          <div class="summary-content">
            <span class="summary-label">Payées</span>
            <span class="summary-value" id="paid-amount">
              <?= number_format($stats['paid'], 2, ',', ' ') ?>
            </span>
          </div>
        </div>
        <div class="summary-item hover-grow transition-all">
          <i class="fas fa-receipt icon icon-receipt pulse" aria-hidden="true"></i>
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
      <div class="filter-card card fade-in-up delay-2">
        <div class="filter-controls">
          <div class="filter-group input-group">
            <label for="filter-category">Catégorie</label>
            <i class="fas fa-tag input-group-icon"></i>
            <select id="filter-category" name="category" class="form-control transition-all">
              <option value="">Toutes les catégories</option>
              <?php foreach ($categories as $category): ?>
              <option value="<?= $category?>">
                <?= htmlspecialchars($category) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-group input-group">
            <label for="filter-status">Statut</label>
            <i class="fas fa-filter input-group-icon"></i>
            <select id="filter-status" name="status" class="form-control transition-all">
              <option value="">Tous les statuts</option>
              <option value="pending">En attente</option>
              <option value="paid">Payées</option>
            </select>
          </div>
          <div class="filter-group input-group">
            <label for="filter-date">Date</label>
            <i class="fas fa-calendar-alt input-group-icon"></i>
            <input type="date" id="filter-date" name="date" class="form-control transition-all">
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="expenses-sections">
    <div class="containers">
      <div class="expenses-grids">
        <?php if (empty($expenses)): ?>
          <div class="alert-infos empty-state fade-in" role="alert">
            <div class="empty-state-icon"><i class="fas fa-receipt"></i></div>
            <div class="empty-state-title">Aucune dépense trouvée</div>
            <div class="empty-state-description">
              Créez votre première dépense en cliquant sur "Nouvelle dépense".
            </div>
            <a href="/expenses/create" class="btn btn-primary hover-lift">
              <i class="fas fa-plus"></i> Nouvelle dépense
            </a>
          </div>
        <?php else: ?>
          <?php $delay = 1; foreach ($expenses as $expense): ?>
            <?php
              $statusClass = $expense->status === 'paid' ? 'successs' : 'warnings';
              $statusText = $expense->status === 'paid' ? 'Payé' : 'En attente';
              // Table de correspondance entre IDs et indices
              $categoryIndexMap = [
                1 => 0, // ID 1 -> indice 0 ("fixe")
                2 => 2, // ID 2 -> indice 2 ("epargne")
                3 => 1  // ID 3 -> indice 1 ("diver")
              ];

              $categoryName = isset($categoryIndexMap[$expense->categorie_id]) && isset($categories[$categoryIndexMap[$expense->categorie_id]]) ?
                  $categories[$categoryIndexMap[$expense->categorie_id]] : '';
            ?>
            <div class="expense-cards card hover-lift fade-in-up delay-<?= min($delay++, 5) ?>"
                 data-category="<?= $expense->categorie_id ?>"
                 data-categoryname="<?= $categoryName ?>"
                 data-status="<?= $expense->status  ?>"
                 data-date="<?= $expense->created_at ?>"
                 data-amount="<?= $expense->amount ?>">

              <div class="expense-icons pulse">
                <i class="fas fa-wallet"></i>
              </div>

              <div class="expense-contents">
                <div class="expense-headers">
                  <span class="status-badges badge-<?= $statusClass ?> badge"><?= $statusText ?></span>
                  <span class="expense-dates"><?= date('d/m/Y', strtotime($expense->created_at)) ?></span>
                </div>

                <h4 class="expense-titles"><?= htmlspecialchars($expense->description) ?></h4>

                <div class="expense-detailss">
                  <span class="category-badges badge"><?= htmlspecialchars($categoryName) ?></span>
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
                    <button type="button" class="btns btn-sms btn-successs mark-paid-btn hover-lift transition-all" data-id="<?= $expense->id ?>">
                      <i class="fas fa-check"></i> Marquer payé
                    </button>
                  <?php endif; ?>

                  <button type="button" class="btns btn-sms btn-primarys edit-expense-btn hover-lift transition-all" data-id="<?= $expense->id ?>">
                    <i class="fas fa-edit"></i> Modifier
                  </button>

                  <button type="button" class="btns btn-sms btn-dangers delete-expense-btn hover-lift transition-all" data-id="<?= $expense->id ?>">
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

  <!-- Ajout de la pagination -->
  <section class="pagination-section fade-in-up delay-3">
    <div class="container">
      <div class="pagination-wrapper">
        <?php if ($previousPage >= 1): ?>
          <a href="?page=<?= $previousPage ?>" class="pagination-btn btn hover-lift transition-all">
            <i class="fas fa-chevron-left"></i> Précédent
          </a>
        <?php endif; ?>

        <div class="pagination-info">
          Page <?= $currentPage ?> sur <?= $lastPage ?>
        </div>

        <?php if ($currentPage < $lastPage): ?>
          <a href="?page=<?= $nextPage ?>" class="pagination-btn btn hover-lift transition-all">
            Suivant <i class="fas fa-chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Modal de modification de dépense -->
  <div class="modal fade" id="editExpenseModal" tabindex="-1" role="dialog" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog scale-in" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editExpenseModalLabel">Modifier la dépense</h5>
        </div>
        <div class="modal-body">
          <form id="edit-expense-form">
            <input type="hidden" id="edit-expense-id" name="id">

            <div class="form-group">
              <label for="edit-description">Description</label>
              <div class="input-group">
                <i class="fas fa-align-left input-group-icon"></i>
                <input type="text" class="form-control transition-all" id="edit-description" name="description" required>
              </div>
            </div>

            <div class="form-group">
              <label for="edit-amount">Montant</label>
              <div class="input-group">
                <i class="fas fa-euro-sign input-group-icon"></i>
                <input type="number" class="form-control transition-all" id="edit-amount" name="amount" step="0.01" min="0" required>
              </div>
            </div>

            <div class="form-group">
              <label for="edit-category">Catégorie</label>
              <div class="input-group">
                <i class="fas fa-tag input-group-icon"></i>
                <select class="form-control transition-all" id="edit-category" name="category_type" required>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?= $category ?>"><?= ucfirst(htmlspecialchars($category)) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label for="edit-date">Date</label>
              <div class="input-group">
                <i class="fas fa-calendar-alt input-group-icon"></i>
                <input type="date" class="form-control transition-all" id="edit-date" name="payment_date" required>
              </div>
            </div>

            <div class="form-group">
              <label for="edit-status">Statut</label>
              <div class="input-group">
                <i class="fas fa-check-circle input-group-icon"></i>
                <select class="form-control transition-all" id="edit-status" name="status">
                  <option value="pending">En attente</option>
                  <option value="paid">Payé</option>
                </select>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary hover-lift transition-all" data-dismiss="modal">Annuler</button>
          <button type="button" class="btn btn-primary hover-lift transition-all" id="save-expense-edit">Enregistrer</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
// Filter functionality with toast notifications
document.querySelectorAll('#filter-category, #filter-status, #filter-date').forEach(filter => {
  filter.addEventListener('change', function() {
    filterExpenses();
    toast.info('Filtres appliqués');
  });
});

function filterExpenses() {
  const category = document.getElementById('filter-category').value;
  const status = document.getElementById('filter-status').value;
  const date = document.getElementById('filter-date').value;

  document.querySelectorAll('.expense-cards').forEach(card => {
    let show = true;

    if (category && card.dataset.categoryname !== category) show = false;
    if (status && card.dataset.status !== status) show = false;
    if (date && !card.dataset.date.includes(date)) show = false;

    card.style.display = show ? 'block' : 'none';
    if (show) {
      card.classList.add('fade-in');
    }
  });
}

// Mark as paid with toast notification
document.querySelectorAll('.mark-paid-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const expenseId = this.dataset.id;
    this.classList.add('btn-loading');
    this.disabled = true;

    const loadingToast = toast.loading('Mise à jour en cours...');

    // Simulate API call (you should implement actual API call here)
    setTimeout(() => {
      toast.success('Dépense marquée comme payée');
      location.reload();
    }, 1000);
  });
});

// Delete with confirmation
document.querySelectorAll('.delete-expense-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette dépense ?')) {
      const expenseId = this.dataset.id;
      this.classList.add('btn-loading');
      this.disabled = true;

      const loadingToast = toast.loading('Suppression en cours...');

      // Simulate API call (you should implement actual API call here)
      setTimeout(() => {
        toast.success('Dépense supprimée');
        location.reload();
      }, 1000);
    }
  });
});

// Edit expense with modal
document.querySelectorAll('.edit-expense-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const expenseId = this.dataset.id;
    // You should fetch expense data via API here
    $('#editExpenseModal').modal('show');
  });
});

// Save edit
document.getElementById('save-expense-edit')?.addEventListener('click', function() {
  this.classList.add('btn-loading');
  this.disabled = true;

  const loadingToast = toast.loading('Sauvegarde en cours...');

  // Simulate API call (you should implement actual API call here)
  setTimeout(() => {
    toast.success('Dépense mise à jour');
    $('#editExpenseModal').modal('hide');
    location.reload();
  }, 1000);
});
</script>
