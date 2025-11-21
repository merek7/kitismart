<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h1><i class="fas fa-cog"></i> Paramètres</h1>
                </div>
                <div class="col-md-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Paramètres</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

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
                                    <label for="nom"><i class="fas fa-user-circle"></i> Nom</label>
                                    <input type="text" class="form-control" id="nom" name="nom"
                                           value="<?= htmlspecialchars($user->nom) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?= htmlspecialchars($user->email) ?>" required>
                                </div>

                                <div class="form-group mb-0">
                                    <p class="text-muted small">
                                        <i class="fas fa-info-circle"></i>
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
                                    <label for="current_password"><i class="fas fa-lock"></i> Mot de passe actuel</label>
                                    <input type="password" class="form-control" id="current_password"
                                           name="current_password" required>
                                </div>

                                <div class="form-group">
                                    <label for="new_password"><i class="fas fa-lock"></i> Nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="new_password"
                                           name="new_password" required minlength="8">
                                    <small class="form-text text-muted">Minimum 8 caractères</small>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirmer le nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="confirm_password"
                                           name="confirm_password" required minlength="8">
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
                            <div class="d-flex justify-content-between align-items-center">
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
.card {
    border-radius: 10px;
    overflow: hidden;
}

.card-header {
    border-bottom: none;
    padding: 1rem 1.5rem;
}

.card-body {
    padding: 1.5rem;
}

.breadcrumb {
    background: transparent;
    padding: 0;
    margin-bottom: 0;
    float: right;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    padding: 0 0.5rem;
}

.alert {
    border-radius: 8px;
    margin-bottom: 20px;
}

.form-control {
    border-radius: 6px;
    border: 1px solid #ddd;
    padding: 0.75rem;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.btn {
    border-radius: 6px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
}

.btn-block {
    margin-top: 1rem;
}

.modal-content {
    border-radius: 10px;
}

.content-header h1 {
    font-size: 28px;
    font-weight: 600;
    color: #333;
}

.custom-control-input:checked ~ .custom-control-label::before {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
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
