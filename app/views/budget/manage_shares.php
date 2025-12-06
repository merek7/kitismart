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
                    $revokedShares = 0;
                    $totalAccesses = 0;

                    foreach ($shares as $shareData) {
                        $share = $shareData['share'];
                        $totalAccesses += (int)$share->use_count;
                        $shareIsActive = (int)$share->is_active === 1;

                        if (!$shareIsActive) {
                            $revokedShares++;
                        } elseif ($shareData['is_expired'] || $shareData['is_max_uses_reached']) {
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
                        <div class="stats-icon text-danger">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-label">Inactifs</div>
                            <div class="stats-value text-danger"><?= $expiredShares + $revokedShares ?></div>
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
                                <i class="fas fa-list"></i> Tous mes Partages
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($shares)): ?>
                                <div class="no-shares">
                                    <i class="fas fa-info-circle"></i>
                                    <p>Vous n'avez pas encore créé de partage</p>
                                    <p class="text-muted mt-2">Pour partager un budget, allez sur la page Budget et cliquez sur "Partager"</p>
                                    <a href="/budget/create" class="btn btn-primary mt-3">
                                        <i class="fas fa-wallet"></i> Aller à mes Budgets
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
                                            $shareIsActive = (int)$share->is_active === 1;
                                            $isRevoked = !$shareIsActive;
                                            $isActive = $shareIsActive && !$isExpired && !$isMaxUsed;
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
                                                    <?php if ($isRevoked): ?>
                                                        <span class="badge badge-secondary">
                                                            <i class="fas fa-ban"></i> Révoqué
                                                        </span>
                                                    <?php elseif ($isExpired): ?>
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

                                                <!-- Nom personnalisé -->
                                                <?php if (!empty($share->name)): ?>
                                                    <div class="share-custom-name">
                                                        <i class="fas fa-tag"></i> <?= htmlspecialchars($share->name) ?>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- URL du partage -->
                                                <?php if ($isActive): ?>
                                                    <?php
                                                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                                        $shareUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/budget/shared/' . $share->share_token;
                                                    ?>
                                                    <div class="share-url-container">
                                                        <label>Lien de partage:</label>
                                                        <div class="input-group">
                                                            <input type="text"
                                                                   class="form-control share-url"
                                                                   value="<?= htmlspecialchars($shareUrl) ?>"
                                                                   readonly>
                                                            <div class="share-buttons">
                                                                <button class="btn btn-outline-secondary copy-link-btn"
                                                                        data-url="<?= htmlspecialchars($shareUrl) ?>"
                                                                        title="Copier">
                                                                    <i class="fas fa-copy"></i>
                                                                    <span class="btn-label">Copier</span>
                                                                </button>
                                                                <button class="btn btn-outline-secondary qr-code-btn"
                                                                        data-share-id="<?= $share->id ?>"
                                                                        data-share-url="<?= htmlspecialchars($shareUrl) ?>"
                                                                        title="QR Code">
                                                                    <i class="fas fa-qrcode"></i>
                                                                    <span class="btn-label">QR Code</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Actions -->
                                            <div class="share-card-footer">
                                                <div class="share-actions-group">
                                                    <button class="btn btn-sm btn-outline-primary view-logs-btn"
                                                            data-share-id="<?= $share->id ?>"
                                                            title="Voir l'historique">
                                                        <i class="fas fa-history"></i>
                                                    </button>
                                                    <?php if ($isActive): ?>
                                                        <button class="btn btn-sm btn-outline-secondary edit-share-btn"
                                                                data-share-id="<?= $share->id ?>"
                                                                data-share-name="<?= htmlspecialchars($share->name ?? '') ?>"
                                                                data-permissions='<?= $share->permissions ?>'
                                                                data-expires-at="<?= $share->expires_at ?>"
                                                                data-max-uses="<?= $share->max_uses ?>"
                                                                title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-warning regenerate-password-btn"
                                                                data-share-id="<?= $share->id ?>"
                                                                title="Changer le mot de passe">
                                                            <i class="fas fa-key"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger revoke-share-btn"
                                                                data-share-id="<?= $share->id ?>"
                                                                data-budget-name="<?= htmlspecialchars($budget->name ?? 'Budget') ?>"
                                                                title="Révoquer">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Pagination -->
                                <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                                    <div class="pagination-wrapper">
                                        <nav class="pagination-nav">
                                            <?php if ($pagination['current_page'] > 1): ?>
                                                <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="pagination-btn">
                                                    <i class="fas fa-chevron-left"></i> Précédent
                                                </a>
                                            <?php endif; ?>

                                            <div class="pagination-info">
                                                Page <?= $pagination['current_page'] ?> sur <?= $pagination['total_pages'] ?>
                                                <span class="pagination-total">(<?= $pagination['total_items'] ?> partages)</span>
                                            </div>

                                            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                                <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="pagination-btn">
                                                    Suivant <i class="fas fa-chevron-right"></i>
                                                </a>
                                            <?php endif; ?>
                                        </nav>
                                    </div>
                                <?php endif; ?>

                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'édition de partage -->
<div id="edit-share-modal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Modifier le partage</h5>
                <button type="button" class="close-modal" data-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="edit-share-form">
                    <input type="hidden" id="edit-share-id" name="share_id">
                    
                    <div class="form-group">
                        <label for="edit-share-name">Nom du partage (optionnel)</label>
                        <input type="text" class="form-control" id="edit-share-name" name="name" 
                               placeholder="Ex: Partage famille, Conjoint...">
                    </div>

                    <div class="form-group">
                        <label>Permissions</label>
                        <div class="permissions-checkboxes">
                            <label class="checkbox-label">
                                <input type="checkbox" name="can_view" id="edit-can-view" checked disabled>
                                <span><i class="fas fa-eye"></i> Consulter</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="can_add" id="edit-can-add">
                                <span><i class="fas fa-plus"></i> Ajouter</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="can_edit" id="edit-can-edit">
                                <span><i class="fas fa-edit"></i> Modifier</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="can_delete" id="edit-can-delete">
                                <span><i class="fas fa-trash"></i> Supprimer</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="can_view_stats" id="edit-can-view-stats">
                                <span><i class="fas fa-chart-pie"></i> Statistiques</span>
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit-expires-at">Expiration</label>
                                <input type="datetime-local" class="form-control" id="edit-expires-at" name="expires_at">
                                <small class="form-text text-muted">Laisser vide pour pas d'expiration</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit-max-uses">Nombre max d'accès</label>
                                <input type="number" class="form-control" id="edit-max-uses" name="max_uses" min="1">
                                <small class="form-text text-muted">Laisser vide pour illimité</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="save-share-btn">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de changement de mot de passe -->
<div id="password-modal" class="modal-overlay">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key"></i> Nouveau mot de passe</h5>
                <button type="button" class="close-modal" data-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="password-form">
                    <input type="hidden" id="password-share-id">
                    <div class="form-group">
                        <label for="new-password">Nouveau mot de passe</label>
                        <input type="password" class="form-control" id="new-password" required minlength="6">
                        <small class="form-text text-muted">Minimum 6 caractères</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirmer</label>
                        <input type="password" class="form-control" id="confirm-password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-warning" id="save-password-btn">
                    <i class="fas fa-key"></i> Changer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal des logs -->
<div id="logs-modal" class="modal-overlay">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-history"></i> Historique d'activité</h5>
                <button type="button" class="close-modal" data-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <div id="logs-container">
                    <div class="text-center"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal QR Code -->
<div id="qr-modal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-qrcode"></i> QR Code du partage</h5>
                <button type="button" class="close-modal" data-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="qr-share-id">
                <input type="hidden" id="qr-share-url">
                
                <div class="qr-preview text-center">
                    <img id="qr-image" src="" alt="QR Code">
                </div>
                
                <div class="qr-options">
                    <div class="form-group">
                        <label for="qr-size">Taille</label>
                        <select id="qr-size" class="form-control">
                            <option value="200">Petite (200px)</option>
                            <option value="300" selected>Moyenne (300px)</option>
                            <option value="400">Grande (400px)</option>
                            <option value="500">Très grande (500px)</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="qr-color">Couleur QR</label>
                                <input type="color" id="qr-color" class="form-control form-control-color" value="#0d9488">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="qr-bg-color">Couleur fond</label>
                                <input type="color" id="qr-bg-color" class="form-control form-control-color" value="#ffffff">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="qr-share-url">
                    <label>Lien de partage</label>
                    <div class="input-group">
                        <input type="text" id="qr-url-display" class="form-control" readonly>
                        <button type="button" class="btn btn-outline-secondary" id="copy-share-url">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-outline-primary" id="download-qr-svg">
                    <i class="fas fa-download"></i> SVG
                </button>
                <button type="button" class="btn btn-primary" id="download-qr-png">
                    <i class="fas fa-download"></i> PNG
                </button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="csrf-token" value="<?= htmlspecialchars($csrfToken) ?>">

<link rel="stylesheet" href="<?= \App\Core\Config::asset('/assets/css/budget/manage_shares.css') ?>">
<script src="/assets/js/budget/manage_shares.js"></script>
