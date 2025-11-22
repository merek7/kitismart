<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> Modifier la Catégorie</h1>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a>
            <span>/</span>
            <a href="/categories"><i class="fas fa-tags"></i> Mes Catégories</a>
            <span>/</span>
            <span>Modifier</span>
        </div>
    </div>

    <div class="page-content">
        <div class="container">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-edit"></i> Modifier "<?= htmlspecialchars($category->name) ?>"
                            </h3>
                            <div class="card-tools">
                                <a href="/categories" class="btn btn-tool btn-action">
                                    <i class="fas fa-list"></i> Retour à la liste
                                </a>
                            </div>
                        </div>

                        <div id="global-message" class="message"></div>

                        <form id="category-edit-form" data-id="<?= $category->id ?>">
                            <div class="card-body">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                                <div class="form-group">
                                    <label for="name">Nom de la catégorie *</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($category->name) ?>" required maxlength="50">
                                    </div>
                                    <small class="form-text text-muted">Donnez un nom court et descriptif</small>
                                </div>

                                <div class="form-group">
                                    <label for="icon">Icône *</label>
                                    <div class="icon-selector">
                                        <input type="hidden" id="icon" name="icon" value="<?= htmlspecialchars($category->icon) ?>">
                                        <div class="icon-grid">
                                            <?php foreach($availableIcons as $iconClass => $iconLabel): ?>
                                                <div class="icon-option <?= $iconClass === $category->icon ? 'selected' : '' ?>" data-icon="<?= $iconClass ?>" title="<?= htmlspecialchars($iconLabel) ?>">
                                                    <i class="fas <?= $iconClass ?>"></i>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Choisissez une icône représentative</small>
                                </div>

                                <div class="form-group">
                                    <label for="color">Couleur *</label>
                                    <div class="color-selector">
                                        <input type="hidden" id="color" name="color" value="<?= htmlspecialchars($category->color) ?>">
                                        <div class="color-grid">
                                            <?php foreach($availableColors as $colorCode => $colorName): ?>
                                                <div class="color-option <?= $colorCode === $category->color ? 'selected' : '' ?>" data-color="<?= $colorCode ?>" style="background-color: <?= $colorCode ?>" title="<?= htmlspecialchars($colorName) ?>">
                                                    <i class="fas fa-check"></i>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">Sélectionnez une couleur pour identifier rapidement la catégorie</small>
                                </div>

                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                        </div>
                                        <textarea class="form-control" id="description" name="description" rows="3" maxlength="255"><?= htmlspecialchars($category->description ?? '') ?></textarea>
                                    </div>
                                    <small class="form-text text-muted">Facultatif - Ajoutez des détails sur l'utilisation de cette catégorie</small>
                                </div>

                                <div class="form-group">
                                    <div class="category-preview">
                                        <h5>Aperçu</h5>
                                        <div class="preview-card">
                                            <div class="preview-icon" id="preview-icon" style="background-color: <?= htmlspecialchars($category->color) ?>">
                                                <i class="fas <?= htmlspecialchars($category->icon) ?>"></i>
                                            </div>
                                            <div class="preview-content">
                                                <h6 id="preview-name"><?= htmlspecialchars($category->name) ?></h6>
                                                <p id="preview-description" class="text-muted"><?= htmlspecialchars($category->description ?? 'Aucune description') ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer les modifications
                                </button>
                                <a href="/categories" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
