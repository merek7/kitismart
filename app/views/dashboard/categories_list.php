<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-6">
          <h1><i class="fas fa-tags"></i> Mes Catégories</h1>
        </div>
        <div class="col-md-6 d-flex justify-content-end align-items-center">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a>
            </li>
            <li class="breadcrumb-item active">Mes Catégories</li>
          </ol>
        </div>
      </div>
    </div>
  </section>

  <section class="category-summary">
    <div class="container">
      <div class="category-summary-card">
        <div class="summary-item">
          <i class="fas fa-tags icon icon-tags" aria-hidden="true"></i>
          <div class="summary-content">
            <span class="summary-label">Total des catégories</span>
            <span class="summary-value"><?= $categoriesCount ?></span>
          </div>
        </div>
        <div class="summary-item">
          <a href="/categories/create" class="btn btn-create">
            <i class="fas fa-plus"></i> Nouvelle Catégorie
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="categories-section">
    <div class="container">
      <div class="categories-grid">
        <?php if (empty($categories)): ?>
          <div class="alert-info" role="alert">
            Aucune catégorie trouvée. Créez votre première catégorie personnalisée en cliquant sur "Nouvelle Catégorie".
          </div>
        <?php else: ?>
          <?php foreach ($categories as $category): ?>
            <div class="category-card" data-id="<?= $category->id ?>">
              <div class="category-icon" style="background-color: <?= htmlspecialchars($category->color) ?>">
                <i class="fas <?= htmlspecialchars($category->icon) ?>"></i>
              </div>

              <div class="category-content">
                <h4 class="category-title"><?= htmlspecialchars($category->name) ?></h4>

                <?php if (!empty($category->description)): ?>
                  <p class="category-description">
                    <?= htmlspecialchars($category->description) ?>
                  </p>
                <?php endif; ?>

                <div class="category-meta">
                  <span class="category-date">
                    <i class="fas fa-calendar"></i>
                    <?= date('d/m/Y', strtotime($category->created_at)) ?>
                  </span>
                </div>

                <div class="category-actions">
                  <a href="/categories/<?= $category->id ?>/edit" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit"></i> Modifier
                  </a>

                  <button type="button" class="btn btn-sm btn-danger delete-category-btn" data-id="<?= $category->id ?>" data-name="<?= htmlspecialchars($category->name) ?>">
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
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
      </div>
      <div class="modal-body">
        <p>Êtes-vous sûr de vouloir supprimer la catégorie "<span id="category-name-to-delete"></span>" ?</p>
        <p class="text-muted">Cette action est irréversible.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
        <button type="button" class="btn btn-danger" id="confirm-delete">Supprimer</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
