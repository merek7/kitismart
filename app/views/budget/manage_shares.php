<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-share-nodes"></i> Gérer les Partages</h1>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a>
            <span>/</span>
            <span>Partages</span>
        </div>
    </div>

    <div class="page-content">
        <div class="container">
            <!-- Statistiques des partages -->
            <div class="row mb-4">
                <?php
                    $totalShares = count($shares);
                    $activeShares = 0;
                    $expiredShares = 0;
                    $totalAccesses = 0;

                    foreach ($shares as $shareData) {
                        $share = $shareData['share'];
                        $totalAccesses += $share->use_count;
                        if ($shareData['is_expired'] || $shareData['is_max_uses_reached']) {
                            $expiredShares++;
                        } else {
                            $activeShares++;
                        }
                    }
                ?>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-share-alt"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-label">Total Partages</div>
                            <div class="stats-value"><?= $totalShares ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon text-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-label">Actifs</div>
                            <div class="stats-value text-success"><?= $activeShares ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon text-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-label">Expirés</div>
                            <div class="stats-value text-warning"><?= $expiredShares ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon text-primary">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-label">Total Accès</div>
                            <div class="stats-value text-primary"><?= $totalAccesses ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des partages -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list"></i> Mes Partages Actifs
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($shares)): ?>
                                <div class="no-shares">
                                    <i class="fas fa-info-circle"></i>
                                    <p>Vous n'avez pas encore créé de partage</p>
                                    <a href="/budget/create" class="btn btn-primary mt-3">
                                        <i class="fas fa-plus-circle"></i> Créer un partage
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="shares-grid">
                                    <?php foreach ($shares as $shareData): ?>
                                        <?php
                                            $share = $shareData['share'];
                                            $budget = $shareData['budget'];
                                            $permissions = $shareData['permissions'];
                                            $isExpired = $shareData['is_expired'];
                                            $isMaxUsed = $shareData['is_max_uses_reached'];
                                            $isActive = !$isExpired && !$isMaxUsed;
                                        ?>
                                        <div class="share-card <?= !$isActive ? 'share-inactive' : '' ?>"
                                             data-share-id="<?= $share->id ?>">
                                            <!-- En-tête de la carte -->
                                            <div class="share-card-header">
                                                <div class="share-budget-info">
                                                    <h4>
                                                        <i class="fas fa-wallet"></i>
                                                        <?= htmlspecialchars($budget->name ?? 'Budget') ?>
                                                    </h4>
                                                    <p class="share-date">
                                                        <i class="fas fa-calendar"></i>
                                                        Créé le <?= date('d/m/Y à H:i', strtotime($share->created_at)) ?>
                                                    </p>
                                                </div>
                                                <div class="share-status-badge">
                                                    <?php if ($isExpired): ?>
                                                        <span class="badge badge-danger">
                                                            <i class="fas fa-clock"></i> Expiré
                                                        </span>
                                                    <?php elseif ($isMaxUsed): ?>
                                                        <span class="badge badge-warning">
                                                            <i class="fas fa-stop-circle"></i> Limite atteinte
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-check-circle"></i> Actif
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Corps de la carte -->
                                            <div class="share-card-body">
                                                <!-- Statistiques d'utilisation -->
                                                <div class="share-usage">
                                                    <div class="usage-stat">
                                                        <i class="fas fa-eye"></i>
                                                        <span><?= $share->use_count ?> accès</span>
                                                        <?php if ($share->max_uses): ?>
                                                            <span class="text-muted">/ <?= $share->max_uses ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($share->expires_at): ?>
                                                        <div class="usage-stat">
                                                            <i class="fas fa-clock"></i>
                                                            <span>Expire: <?= date('d/m/Y H:i', strtotime($share->expires_at)) ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Permissions -->
                                                <div class="share-permissions-list">
                                                    <strong><i class="fas fa-key"></i> Permissions:</strong>
                                                    <div class="permissions-badges">
                                                        <?php if ($permissions['can_view']): ?>
                                                            <span class="permission-badge"><i class="fas fa-eye"></i> Voir</span>
                                                        <?php endif; ?>
                                                        <?php if ($permissions['can_add']): ?>
                                                            <span class="permission-badge"><i class="fas fa-plus"></i> Ajouter</span>
                                                        <?php endif; ?>
                                                        <?php if ($permissions['can_edit']): ?>
                                                            <span class="permission-badge"><i class="fas fa-edit"></i> Modifier</span>
                                                        <?php endif; ?>
                                                        <?php if ($permissions['can_delete']): ?>
                                                            <span class="permission-badge"><i class="fas fa-trash"></i> Supprimer</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- URL du partage -->
                                                <?php if ($isActive): ?>
                                                    <div class="share-url-container">
                                                        <label>Lien de partage:</label>
                                                        <div class="input-group">
                                                            <input type="text"
                                                                   class="form-control share-url"
                                                                   value="<?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/budget/shared/' . $share->share_token) ?>"
                                                                   readonly>
                                                            <button class="btn btn-outline-secondary copy-link-btn"
                                                                    data-url="<?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/budget/shared/' . $share->share_token) ?>">
                                                                <i class="fas fa-copy"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Actions -->
                                            <?php if ($isActive): ?>
                                                <div class="share-card-footer">
                                                    <button class="btn btn-danger revoke-share-btn"
                                                            data-share-id="<?= $share->id ?>"
                                                            data-budget-name="<?= htmlspecialchars($budget->name ?? 'Budget') ?>">
                                                        <i class="fas fa-ban"></i> Révoquer l'accès
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="csrf-token" value="<?= htmlspecialchars($csrfToken) ?>">

<link rel="stylesheet" href="/assets/css/budget/manage_shares.css">
<script src="/assets/js/budget/manage_shares.js"></script>
