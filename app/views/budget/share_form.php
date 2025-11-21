<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-share-alt"></i> Partager le Budget</h1>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a>
            <span>/</span>
            <a href="/budget/create"><i class="fas fa-wallet"></i> Budget</a>
            <span>/</span>
            <span>Partager</span>
        </div>
    </div>

    <div class="page-content">
        <div class="container">
            <!-- Informations du budget -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card budget-info-card">
                        <div class="card-body">
                            <h3><i class="fas fa-wallet"></i> <?= htmlspecialchars($budget->name ?? 'Budget Actif') ?></h3>
                            <p class="budget-period">
                                Du <?= date('d/m/Y', strtotime($budget->start_date)) ?>
                                <?= $budget->end_date ? ' au ' . date('d/m/Y', strtotime($budget->end_date)) : '' ?>
                            </p>
                            <div class="budget-amounts">
                                <span class="badge badge-primary">
                                    <i class="fas fa-coins"></i> Budget initial: <?= number_format($budget->initial_amount, 0, ',', ' ') ?> FCFA
                                </span>
                                <span class="badge badge-success">
                                    <i class="fas fa-check-circle"></i> Restant: <?= number_format($budget->remaining_amount, 0, ',', ' ') ?> FCFA
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Formulaire de création de partage -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-plus-circle"></i> Créer un nouveau partage
                            </h3>
                        </div>
                        <div class="card-body">
                            <form id="share-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                                <!-- Mot de passe -->
                                <div class="form-group">
                                    <label for="password">
                                        <i class="fas fa-lock"></i> Mot de passe d'accès <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="password"
                                               class="form-control"
                                               id="password"
                                               name="password"
                                               required
                                               minlength="6"
                                               placeholder="Minimum 6 caractères">
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye" id="passwordIcon"></i>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">
                                        Partagez ce mot de passe avec la personne qui accédera au budget
                                    </small>
                                </div>

                                <!-- Permissions -->
                                <div class="form-group">
                                    <label><i class="fas fa-key"></i> Permissions</label>
                                    <div class="permissions-list">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="can_view" name="can_view" checked disabled>
                                            <label class="form-check-label" for="can_view">
                                                <i class="fas fa-eye"></i> Voir les dépenses
                                                <span class="badge badge-info">Obligatoire</span>
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="can_add" name="can_add">
                                            <label class="form-check-label" for="can_add">
                                                <i class="fas fa-plus"></i> Ajouter des dépenses
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="can_edit" name="can_edit">
                                            <label class="form-check-label" for="can_edit">
                                                <i class="fas fa-edit"></i> Modifier les dépenses
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="can_delete" name="can_delete">
                                            <label class="form-check-label" for="can_delete">
                                                <i class="fas fa-trash"></i> Supprimer des dépenses
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="can_view_stats" name="can_view_stats" checked>
                                            <label class="form-check-label" for="can_view_stats">
                                                <i class="fas fa-chart-bar"></i> Voir les statistiques
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Options avancées -->
                                <div class="form-group">
                                    <label><i class="fas fa-cog"></i> Options avancées</label>

                                    <div class="mb-3">
                                        <label for="expires_at">Date d'expiration (optionnel)</label>
                                        <input type="datetime-local"
                                               class="form-control"
                                               id="expires_at"
                                               name="expires_at">
                                        <small class="form-text text-muted">
                                            Le lien cessera de fonctionner après cette date
                                        </small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="max_uses">Nombre maximum d'utilisations (optionnel)</label>
                                        <input type="number"
                                               class="form-control"
                                               id="max_uses"
                                               name="max_uses"
                                               min="1"
                                               placeholder="Illimité">
                                        <small class="form-text text-muted">
                                            Nombre de fois où le lien peut être utilisé
                                        </small>
                                    </div>
                                </div>

                                <div id="form-message" class="message"></div>

                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-share-alt"></i> Créer le lien de partage
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Liste des partages existants -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list"></i> Partages existants
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($existingShares)): ?>
                                <div class="no-shares">
                                    <i class="fas fa-info-circle"></i>
                                    <p>Aucun partage actif pour ce budget</p>
                                </div>
                            <?php else: ?>
                                <div class="shares-list" id="shares-list">
                                    <?php foreach ($existingShares as $share): ?>
                                        <?php
                                            $isExpired = $share->expires_at && strtotime($share->expires_at) < time();
                                            $isMaxUsed = $share->max_uses && $share->use_count >= $share->max_uses;
                                            $isInactive = !$share->is_active;
                                            $permissions = json_decode($share->permissions, true);
                                        ?>
                                        <div class="share-item <?= $isExpired || $isMaxUsed || $isInactive ? 'share-disabled' : '' ?>"
                                             data-share-id="<?= $share->id ?>">
                                            <div class="share-header">
                                                <div class="share-status">
                                                    <?php if ($isInactive): ?>
                                                        <span class="badge badge-danger"><i class="fas fa-ban"></i> Révoqué</span>
                                                    <?php elseif ($isExpired): ?>
                                                        <span class="badge badge-warning"><i class="fas fa-clock"></i> Expiré</span>
                                                    <?php elseif ($isMaxUsed): ?>
                                                        <span class="badge badge-warning"><i class="fas fa-stop-circle"></i> Max atteint</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Actif</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="share-date">
                                                    Créé le <?= date('d/m/Y', strtotime($share->created_at)) ?>
                                                </div>
                                            </div>

                                            <div class="share-info">
                                                <div class="share-stats">
                                                    <span><i class="fas fa-eye"></i> <?= $share->use_count ?> accès</span>
                                                    <?php if ($share->max_uses): ?>
                                                        <span>/ <?= $share->max_uses ?> max</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($share->expires_at): ?>
                                                    <div class="share-expiry">
                                                        <i class="fas fa-clock"></i> Expire le <?= date('d/m/Y H:i', strtotime($share->expires_at)) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="share-permissions">
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

                                            <?php if ($share->is_active && !$isExpired && !$isMaxUsed): ?>
                                                <div class="share-actions">
                                                    <button class="btn btn-sm btn-secondary copy-link-btn"
                                                            data-token="<?= htmlspecialchars($share->share_token) ?>">
                                                        <i class="fas fa-copy"></i> Copier le lien
                                                    </button>
                                                    <button class="btn btn-sm btn-danger revoke-btn"
                                                            data-share-id="<?= $share->id ?>">
                                                        <i class="fas fa-ban"></i> Révoquer
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

<!-- Modal de succès avec lien -->
<div id="success-modal" class="modal-overlay">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle text-success"></i> Partage créé avec succès
                </h5>
                <button type="button" class="close-modal" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Partagez ce lien et le mot de passe avec la personne qui doit accéder au budget
                </div>
                <div class="form-group">
                    <label><strong>Lien de partage :</strong></label>
                    <div class="input-group">
                        <input type="text"
                               class="form-control"
                               id="share-url"
                               readonly>
                        <button class="btn btn-primary" id="copy-url-btn">
                            <i class="fas fa-copy"></i> Copier
                        </button>
                    </div>
                </div>
                <div class="share-instructions">
                    <p><i class="fas fa-lightbulb"></i> <strong>Instructions :</strong></p>
                    <ol>
                        <li>Copiez le lien ci-dessus</li>
                        <li>Envoyez-le à la personne concernée (par email, WhatsApp, etc.)</li>
                        <li>Communiquez-lui le mot de passe séparément</li>
                        <li>La personne pourra accéder au budget en cliquant sur le lien et en entrant le mot de passe</li>
                    </ol>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/assets/css/budget/share.css">
<script src="/assets/js/budget/share.js"></script>
