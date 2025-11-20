<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h1><i class="fas fa-tag"></i> Nouvelle Catégorie</h1>
                </div>
                <div class="col-md-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a></li>
                        <li class="breadcrumb-item"><a href="/categories"><i class="fas fa-tags"></i> Mes Catégories</a></li>
                        <li class="breadcrumb-item active">Nouvelle Catégorie</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-plus-circle"></i> Créer une catégorie personnalisée
                            </h3>
                            <div class="card-tools">
                                <a href="/categories" class="btn btn-tool btn-action">
                                    <i class="fas fa-list"></i> Retour à la liste
                                </a>
                            </div>
                        </div>

                        <div id="global-message" class="message"></div>

                        <form id="category-form" action="/categories/create" method="POST">
                            <div class="card-body">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                                <div class="form-group">
                                    <label for="name">Nom de la catégorie *</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="name" name="name" placeholder="Ex: Restaurant, Sport, Abonnements..." required maxlength="50">
                                    </div>
                                    <small class="form-text text-muted">Donnez un nom court et descriptif</small>
                                </div>

                                <div class="form-group">
                                    <label for="icon">Icône *</label>
                                    <div class="icon-selector">
                                        <input type="hidden" id="icon" name="icon" value="fa-tag">
                                        <div class="icon-grid">
                                            <?php foreach($availableIcons as $iconClass => $iconLabel): ?>
                                                <div class="icon-option <?= $iconClass === 'fa-tag' ? 'selected' : '' ?>" data-icon="<?= $iconClass ?>" title="<?= htmlspecialchars($iconLabel) ?>">
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
                                        <input type="hidden" id="color" name="color" value="#0d9488">
                                        <div class="color-grid">
                                            <?php foreach($availableColors as $colorCode => $colorName): ?>
                                                <div class="color-option <?= $colorCode === '#0d9488' ? 'selected' : '' ?>" data-color="<?= $colorCode ?>" style="background-color: <?= $colorCode ?>" title="<?= htmlspecialchars($colorName) ?>">
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
                                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Description optionnelle de la catégorie..." maxlength="255"></textarea>
                                    </div>
                                    <small class="form-text text-muted">Facultatif - Ajoutez des détails sur l'utilisation de cette catégorie</small>
                                </div>

                                <div class="form-group">
                                    <div class="category-preview">
                                        <h5>Aperçu</h5>
                                        <div class="preview-card">
                                            <div class="preview-icon" id="preview-icon">
                                                <i class="fas fa-tag"></i>
                                            </div>
                                            <div class="preview-content">
                                                <h6 id="preview-name">Nom de la catégorie</h6>
                                                <p id="preview-description" class="text-muted">Description</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Créer la catégorie
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
    </section>
</div>
