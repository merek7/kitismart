<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-cog"></i> Paramètres</h1>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a>
            <span>/</span>
            <span>Paramètres</span>
        </div>
    </div>

    <section class="content">
        <div class="container">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="row">
                <!-- Informations du profil -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user"></i> Informations du profil</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="/settings/update-profile">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                                <div class="form-group">
                                    <label for="nom">Nom</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user-circle"></i></span>
                                        </div>
                                        <input type="text" class="form-control" id="nom" name="nom"
                                               value="<?= htmlspecialchars($user->nom) ?>" required
                                               placeholder="Votre nom">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        </div>
                                        <input type="email" class="form-control email-readonly" id="email" name="email"
                                               value="<?= htmlspecialchars($user->email) ?>" readonly disabled>
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-lock"></i> L'adresse email ne peut pas être modifiée
                                    </small>
                                </div>

                                <div class="form-group mb-0">
                                    <p class="text-muted small member-since">
                                        <i class="fas fa-calendar-alt"></i>
                                        Membre depuis le <?= date('d/m/Y', strtotime($user->created_at)) ?>
                                    </p>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-save"></i> Enregistrer les modifications
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Changer le mot de passe -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0"><i class="fas fa-key"></i> Changer le mot de passe</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="/settings/update-password">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                                <div class="form-group">
                                    <label for="current_password">Mot de passe actuel</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        </div>
                                        <input type="password" class="form-control" id="current_password"
                                               name="current_password" required placeholder="••••••••">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="new_password">Nouveau mot de passe</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        </div>
                                        <input type="password" class="form-control" id="new_password"
                                               name="new_password" required minlength="8" placeholder="••••••••">
                                    </div>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Minimum 8 caractères
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-check-double"></i></span>
                                        </div>
                                        <input type="password" class="form-control" id="confirm_password"
                                               name="confirm_password" required minlength="8" placeholder="••••••••">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-warning btn-block">
                                    <i class="fas fa-key"></i> Changer le mot de passe
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Préférences d'affichage -->
            <div class="row">
                <div class="col-md-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-palette"></i> Préférences d'affichage</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6><i class="fas fa-moon"></i> Mode sombre</h6>
                                    <p class="text-muted mb-0 small">
                                        Réduisez la fatigue visuelle avec un thème sombre
                                    </p>
                                </div>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="darkModeToggle">
                                    <label class="custom-control-label" for="darkModeToggle"></label>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6><i class="fas fa-wifi"></i> Mode hors ligne (PWA)</h6>
                                    <p class="text-muted mb-0 small">
                                        Permet d'utiliser l'application sans connexion internet.
                                        <br><span id="pwa-status" class="pwa-status"></span>
                                    </p>
                                </div>
                                <div>
                                    <button type="button" id="pwa-enable-btn" class="btn btn-sm btn-outline-primary" onclick="PWAManager.enableOfflineMode()">
                                        <i class="fas fa-download"></i> Activer
                                    </button>
                                    <button type="button" id="pwa-disable-btn" class="btn btn-sm btn-outline-secondary" style="display: none;" onclick="PWAManager.disableOfflineMode()">
                                        <i class="fas fa-times"></i> Désactiver
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zone de danger -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card shadow-sm border-danger">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Zone de danger</h5>
                        </div>
                        <div class="card-body">
                            <h6>Supprimer définitivement le compte</h6>
                            <p class="text-muted">
                                Cette action est irréversible. Toutes vos données (budgets, dépenses, catégories) seront
                                définitivement supprimées.
                            </p>

                            <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteAccountModal">
                                <i class="fas fa-trash-alt"></i> Supprimer mon compte
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" role="dialog" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAccountModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirmer la suppression
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="/settings/delete-account">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Attention !</strong> Cette action est irréversible.
                    </div>

                    <p>Toutes les données suivantes seront définitivement supprimées :</p>
                    <ul>
                        <li>Tous vos budgets</li>
                        <li>Toutes vos dépenses</li>
                        <li>Toutes vos catégories</li>
                        <li>Votre historique d'activité</li>
                    </ul>

                    <div class="form-group">
                        <label for="password">Entrez votre mot de passe pour confirmer</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirmation">
                            Tapez <strong>SUPPRIMER</strong> pour confirmer
                        </label>
                        <input type="text" class="form-control" id="confirmation"
                               name="confirmation" placeholder="SUPPRIMER" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Supprimer définitivement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* ================================
   SETTINGS PAGE STYLES
   ================================ */
.card {
    border-radius: var(--radius-md);
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.card-header {
    border-bottom: none;
    padding: 1rem 1.5rem;
}

.card-header.bg-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
}

.card-header.bg-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706) !important;
}

.card-header.bg-info {
    background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
}

.card-header.bg-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
}

.card-body {
    padding: 1.5rem;
}

.alert {
    border-radius: var(--radius-md);
    margin-bottom: 20px;
}

/* Email readonly styling */
.email-readonly {
    background-color: #f8fafc !important;
    color: var(--text-secondary) !important;
    cursor: not-allowed;
}

.form-text {
    font-size: 0.8rem;
    margin-top: 0.5rem;
    display: block;
}

.form-text i {
    margin-right: 0.25rem;
}

.member-since {
    background: rgba(13, 148, 136, 0.1);
    padding: 0.75rem 1rem;
    border-radius: var(--radius-md);
    border-left: 3px solid var(--primary-color);
}

.member-since i {
    margin-right: 0.5rem;
    color: var(--primary-color);
}

.btn-block {
    width: 100%;
    margin-top: 1rem;
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border: none;
    color: white;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #d97706, #b45309);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.modal-content {
    border-radius: var(--radius-md);
}

.custom-control-input:checked ~ .custom-control-label::before {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

/* Custom Switch Styling */
.custom-switch {
    padding-left: 2.5rem;
}

.custom-switch .custom-control-label::before {
    left: -2.5rem;
    width: 2.5rem;
    border-radius: 1.25rem;
    background-color: #e5e7eb;
    border: none;
    transition: all 0.3s ease;
}

.custom-switch .custom-control-label::after {
    top: calc(0.25rem + 2px);
    left: calc(-2.5rem + 2px);
    width: calc(1.25rem - 4px);
    height: calc(1.25rem - 4px);
    background-color: white;
    border-radius: 50%;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.custom-switch .custom-control-input:checked ~ .custom-control-label::before {
    background-color: var(--primary-color);
}

.custom-switch .custom-control-input:checked ~ .custom-control-label::after {
    transform: translateX(1.25rem);
}

/* PWA Status Indicator */
.pwa-status {
    display: inline-block;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-top: 0.25rem;
}

.pwa-status.active {
    background: #d1fae5;
    color: #059669;
}

.pwa-status.inactive {
    background: #fee2e2;
    color: #dc2626;
}

[data-theme="dark"] .pwa-status.active {
    background: #064e3b;
    color: #6ee7b7;
}

[data-theme="dark"] .pwa-status.inactive {
    background: #7f1d1d;
    color: #fca5a5;
}

/* Dark mode support */
[data-theme="dark"] .card {
    background-color: var(--bg-card);
    border-color: var(--border-color);
}

[data-theme="dark"] .email-readonly {
    background-color: #374151 !important;
}

[data-theme="dark"] .member-since {
    background: rgba(20, 184, 166, 0.15);
}

/* Responsive */
@media (max-width: 768px) {
    .row {
        flex-direction: column;
    }

    .col-md-6 {
        width: 100%;
    }
}
</style>

<script>
// ================================
// Mode sombre - Toggle et persistence
// ================================
document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');

    // Charger la préférence sauvegardée
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    if (isDarkMode) {
        darkModeToggle.checked = true;
    }

    // Gérer le changement
    darkModeToggle.addEventListener('change', function() {
        const isDark = this.checked;

        // Sauvegarder la préférence
        localStorage.setItem('darkMode', isDark);

        // Appliquer le mode
        if (isDark) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }

        // Animation de transition douce
        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    });
});
</script>
