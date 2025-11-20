<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-tags"></i> Mes Catégories</h1>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a>
            <span>/</span>
            <span>Mes Catégories</span>
        </div>
    </div>

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

    <!-- Catégories par défaut -->
    <section class="categories-section">
        <div class="container">
            <h3 class="section-title">
                <i class="fas fa-star"></i> Catégories par défaut
            </h3>
            <p class="section-description">Ces catégories sont disponibles pour tous les utilisateurs</p>

            <div class="categories-grid">
                <?php foreach ($defaultCategories as $category): ?>
                    <?php
                        // Définir les icônes et couleurs pour chaque catégorie par défaut
                        $categoryData = [
                            'fixe' => [
                                'icon' => 'fa-calendar-check',
                                'color' => '#3b82f6',
                                'description' => 'Dépenses régulières et prévisibles (loyer, abonnements, etc.)'
                            ],
                            'diver' => [
                                'icon' => 'fa-shopping-cart',
                                'color' => '#8b5cf6',
                                'description' => 'Dépenses diverses et occasionnelles'
                            ],
                            'epargne' => [
                                'icon' => 'fa-piggy-bank',
                                'color' => '#10b981',
                                'description' => 'Épargne et investissements'
                            ]
                        ];
                        $data = $categoryData[$category] ?? ['icon' => 'fa-tag', 'color' => '#6b7280', 'description' => ''];
                    ?>
                    <div class="category-card default-category">
                        <div class="category-icon" style="background-color: <?= $data['color'] ?>">
                            <i class="fas <?= $data['icon'] ?>"></i>
                        </div>

                        <div class="category-content">
                            <h4 class="category-title">
                                <?= ucfirst(htmlspecialchars($category)) ?>
                                <span class="badge-default">Défaut</span>
                            </h4>

                            <p class="category-description">
                                <?= htmlspecialchars($data['description']) ?>
                            </p>

                            <div class="category-meta">
                                <span class="category-info">
                                    <i class="fas fa-info-circle"></i>
                                    Catégorie système (non modifiable)
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Catégories personnalisées -->
    <section class="categories-section">
        <div class="container">
            <h3 class="section-title">
                <i class="fas fa-user-tag"></i> Mes catégories personnalisées
                <?php if ($customCategoriesCount > 0): ?>
                    <span class="count-badge"><?= $customCategoriesCount ?></span>
                <?php endif; ?>
            </h3>
            <p class="section-description">Catégories créées spécialement pour vos besoins</p>

            <div class="categories-grid">
                <?php if (empty($customCategories)): ?>
                    <div class="alert-info" role="alert">
                        <i class="fas fa-info-circle"></i>
                        Aucune catégorie personnalisée. Créez votre première catégorie en cliquant sur "Nouvelle Catégorie".
                    </div>
                <?php else: ?>
                    <?php foreach ($customCategories as $category): ?>
                        <div class="category-card custom-category" data-id="<?= $category->id ?>">
                            <div class="category-icon" style="background-color: <?= htmlspecialchars($category->color) ?>">
                                <i class="fas <?= htmlspecialchars($category->icon) ?>"></i>
                            </div>

                            <div class="category-content">
                                <h4 class="category-title">
                                    <?= htmlspecialchars($category->name) ?>
                                    <span class="badge-custom">Perso</span>
                                </h4>

                                <?php if (!empty($category->description)): ?>
                                    <p class="category-description">
                                        <?= htmlspecialchars($category->description) ?>
                                    </p>
                                <?php endif; ?>

                                <div class="category-meta">
                                    <span class="category-date">
                                        <i class="fas fa-calendar"></i>
                                        Créée le <?= date('d/m/Y', strtotime($category->created_at)) ?>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirm-delete">Supprimer</button>
            </div>
        </div>
    </div>
</div>
