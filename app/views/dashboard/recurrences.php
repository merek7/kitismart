<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-sync-alt"></i> Dépenses Récurrentes</h1>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a>
            <span>/</span>
            <span>Dépenses Récurrentes</span>
        </div>
    </div>

    <div class="page-content">
        <!-- En-tête avec bouton ajouter -->
        <div class="recurrences-header">
            <div class="header-info">
                <p class="header-subtitle">
                    <i class="fas fa-info-circle"></i>
                    Automatisez vos dépenses régulières (loyer, abonnements, épargne...)
                </p>
            </div>
            <button type="button" class="btn btn-primary" id="add-recurrence-btn">
                <i class="fas fa-plus-circle"></i> Nouvelle Récurrence
            </button>
        </div>

        <!-- Statistiques récurrences -->
        <div class="recurrence-stats-card">
            <div class="stat-item">
                <i class="fas fa-check-circle stat-icon success"></i>
                <div class="stat-content">
                    <span class="stat-label">Récurrences actives</span>
                    <span class="stat-value" id="active-count">
                        <?= count(array_filter($recurrences, fn($r) => $r->is_active)) ?>
                    </span>
                </div>
            </div>
            <div class="stat-item">
                <i class="fas fa-pause-circle stat-icon warning"></i>
                <div class="stat-content">
                    <span class="stat-label">Récurrences en pause</span>
                    <span class="stat-value" id="inactive-count">
                        <?= count(array_filter($recurrences, fn($r) => !$r->is_active)) ?>
                    </span>
                </div>
            </div>
            <div class="stat-item">
                <i class="fas fa-coins stat-icon primary"></i>
                <div class="stat-content">
                    <span class="stat-label">Total mensuel estimé</span>
                    <span class="stat-value" id="monthly-total">
                        <?php
                        $monthlyTotal = 0;
                        foreach ($recurrences as $r) {
                            if ($r->is_active && $r->frequency === 'monthly') {
                                $monthlyTotal += $r->amount;
                            }
                        }
                        echo number_format($monthlyTotal, 2, ',', ' ');
                        ?> FCFA
                    </span>
                </div>
            </div>
        </div>

        <!-- Liste des récurrences -->
        <div class="recurrences-grid">
            <?php if (empty($recurrences)): ?>
                <div class="alert-info" role="alert">
                    <i class="fas fa-info-circle"></i>
                    Aucune récurrence configurée. Créez votre première dépense récurrente pour automatiser vos finances.
                </div>
            <?php else: ?>
                <?php foreach ($recurrences as $recurrence): ?>
                    <div class="recurrence-card <?= $recurrence->is_active ? '' : 'inactive' ?>"
                         data-id="<?= $recurrence->id ?>"
                         data-description="<?= htmlspecialchars($recurrence->description) ?>"
                         data-amount="<?= $recurrence->amount ?>"
                         data-category="<?= $recurrence->categorie_name ?>"
                         data-frequency="<?= $recurrence->frequency ?>">

                        <div class="recurrence-header">
                            <div class="recurrence-status">
                                <?php if ($recurrence->is_active): ?>
                                    <span class="status-badge badge-success">
                                        <i class="fas fa-check-circle"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge badge-warning">
                                        <i class="fas fa-pause-circle"></i> En pause
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="category-badge"><?= $recurrence->categorie_name ?></span>
                        </div>

                        <div class="recurrence-content">
                            <h3 class="recurrence-title"><?= htmlspecialchars($recurrence->description) ?></h3>

                            <div class="recurrence-details">
                                <div class="detail-item">
                                    <i class="fas fa-coins"></i>
                                    <span class="detail-value"><?= number_format($recurrence->amount, 2, ',', ' ') ?> FCFA</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-sync-alt"></i>
                                    <span class="detail-value"><?= $recurrence->frequency_label ?></span>
                                </div>
                            </div>

                            <div class="recurrence-dates">
                                <?php if ($recurrence->last_execution_date): ?>
                                    <div class="date-info">
                                        <i class="fas fa-history"></i>
                                        <span>Dernière: <?= date('d/m/Y', strtotime($recurrence->last_execution_date)) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="date-info">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Prochaine: <?= date('d/m/Y', strtotime($recurrence->next_execution_date)) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="recurrence-actions">
                            <button type="button" class="btn btn-sm btn-primary edit-recurrence-btn"
                                    data-id="<?= $recurrence->id ?>">
                                <i class="fas fa-edit"></i> Modifier
                            </button>
                            <button type="button" class="btn btn-sm <?= $recurrence->is_active ? 'btn-warning' : 'btn-success' ?> toggle-recurrence-btn"
                                    data-id="<?= $recurrence->id ?>"
                                    data-active="<?= $recurrence->is_active ?>">
                                <i class="fas fa-<?= $recurrence->is_active ? 'pause' : 'play' ?>"></i>
                                <?= $recurrence->is_active ? 'Pause' : 'Activer' ?>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-recurrence-btn"
                                    data-id="<?= $recurrence->id ?>">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modale de création/édition -->
<div id="recurrence-modal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <i class="fas fa-sync-alt modal-icon"></i>
            <h3 id="modal-title">Nouvelle Récurrence</h3>
        </div>
        <div class="modal-body">
            <form id="recurrence-form">
                <input type="hidden" id="recurrence-id">

                <div class="form-group">
                    <label for="recurrence-description">Description <span class="required">*</span></label>
                    <div class="input-with-icon">
                        <i class="fas fa-align-left"></i>
                        <input type="text" class="form-control" id="recurrence-description"
                               placeholder="Ex: Loyer, Netflix, Épargne mensuelle..." required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="recurrence-amount">Montant (FCFA) <span class="required">*</span></label>
                        <div class="input-with-icon">
                            <i class="fas fa-coins"></i>
                            <input type="number" class="form-control" id="recurrence-amount"
                                   step="0.01" min="0" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="recurrence-category">Catégorie <span class="required">*</span></label>
                        <div class="input-with-icon">
                            <i class="fas fa-tag"></i>
                            <select class="form-control" id="recurrence-category" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category ?>"><?= ucfirst($category) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="recurrence-frequency">Fréquence <span class="required">*</span></label>
                        <div class="input-with-icon">
                            <i class="fas fa-clock"></i>
                            <select class="form-control" id="recurrence-frequency" required>
                                <?php foreach ($frequencies as $freq => $label): ?>
                                    <option value="<?= $freq ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="recurrence-start-date">Date de début</label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar-alt"></i>
                            <input type="date" class="form-control" id="recurrence-start-date"
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" id="modal-cancel">
                <i class="fas fa-times"></i> Annuler
            </button>
            <button type="button" class="btn btn-primary" id="modal-confirm">
                <i class="fas fa-check"></i> Enregistrer
            </button>
        </div>
    </div>
</div>

<!-- Modale de confirmation de suppression -->
<div id="delete-modal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header modal-header-danger">
            <i class="fas fa-exclamation-triangle modal-icon"></i>
            <h3>Confirmer la suppression</h3>
        </div>
        <div class="modal-body">
            <p>Voulez-vous vraiment supprimer cette récurrence ?</p>
            <div class="modal-info modal-info-danger">
                <i class="fas fa-info-circle"></i>
                <span>Les dépenses déjà créées ne seront pas supprimées.</span>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-cancel" id="delete-modal-cancel">
                <i class="fas fa-times"></i> Annuler
            </button>
            <button type="button" class="btn btn-danger" id="delete-modal-confirm">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </div>
    </div>
</div>

<!-- Token CSRF -->
<script>
    const csrfToken = '<?= $csrfToken ?>';
</script>
