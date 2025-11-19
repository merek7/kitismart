<div class="content-wrapper">
    <section class="content-header fade-in-up">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <h1><i class="fas fa-cog"></i> Paramètres</h1>
                </div>
                <div class="col-md-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/dashboard" class="transition-colors"><i class="fas fa-home"></i> Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Paramètres</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade-in slide-in-left" role="alert">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade-in shake" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="row">
                <!-- Informations du profil -->
                <div class="col-md-6 mb-4 fade-in-up delay-1">
                    <div class="card shadow-sm hover-lift">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user pulse"></i> Informations du profil</h5>
                        </div>
                        <div class="card-body">
                            <form id="profile-form" method="POST" action="/settings/update-profile">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                                <div class="form-group input-group">
                                    <label for="nom"><i class="fas fa-user-circle"></i> Nom</label>
                                    <i class="fas fa-user-circle input-group-icon"></i>
                                    <input type="text" class="form-control transition-all" id="nom" name="nom"
                                           value="<?= htmlspecialchars($user->nom) ?>" required>
                                </div>

                                <div class="form-group input-group">
                                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                                    <i class="fas fa-envelope input-group-icon"></i>
                                    <input type="email" class="form-control transition-all" id="email" name="email"
                                           value="<?= htmlspecialchars($user->email) ?>" required>
                                </div>

                                <div class="form-group mb-0">
                                    <p class="text-muted small">
                                        <i class="fas fa-info-circle"></i>
                                        Membre depuis le <?= date('d/m/Y', strtotime($user->created_at)) ?>
                                    </p>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block hover-lift transition-all">
                                    <i class="fas fa-save"></i> Enregistrer les modifications
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Changer le mot de passe -->
                <div class="col-md-6 mb-4 fade-in-up delay-2">
                    <div class="card shadow-sm hover-lift">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0"><i class="fas fa-key pulse"></i> Changer le mot de passe</h5>
                        </div>
                        <div class="card-body">
                            <form id="password-form" method="POST" action="/settings/update-password">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                                <div class="form-group input-group">
                                    <label for="current_password"><i class="fas fa-lock"></i> Mot de passe actuel</label>
                                    <i class="fas fa-lock input-group-icon"></i>
                                    <input type="password" class="form-control transition-all" id="current_password"
                                           name="current_password" required>
                                    <button type="button" class="password-toggle" onclick="togglePasswordField('current_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>

                                <div class="form-group input-group">
                                    <label for="new_password"><i class="fas fa-lock"></i> Nouveau mot de passe</label>
                                    <i class="fas fa-lock input-group-icon"></i>
                                    <input type="password" class="form-control transition-all" id="new_password"
                                           name="new_password" required minlength="8">
                                    <button type="button" class="password-toggle" onclick="togglePasswordField('new_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <small class="form-text text-muted">Minimum 8 caractères</small>
                                </div>

                                <div class="form-group input-group">
                                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirmer le nouveau mot de passe</label>
                                    <i class="fas fa-lock input-group-icon"></i>
                                    <input type="password" class="form-control transition-all" id="confirm_password"
                                           name="confirm_password" required minlength="8">
                                    <button type="button" class="password-toggle" onclick="togglePasswordField('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>

                                <div id="password-strength" class="password-strength" style="display: none;">
                                    <div class="strength-bar">
                                        <div class="strength-fill"></div>
                                    </div>
                                    <span class="strength-text"></span>
                                </div>

                                <button type="submit" class="btn btn-warning btn-block hover-lift transition-all">
                                    <i class="fas fa-key"></i> Changer le mot de passe
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zone de danger -->
            <div class="row">
                <div class="col-md-12 fade-in-up delay-3">
                    <div class="card shadow-sm border-danger hover-lift">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle pulse"></i> Zone de danger</h5>
                        </div>
                        <div class="card-body">
                            <h6>Supprimer définitivement le compte</h6>
                            <p class="text-muted">
                                Cette action est irréversible. Toutes vos données (budgets, dépenses, catégories) seront
                                définitivement supprimées.
                            </p>

                            <button type="button" class="btn btn-danger hover-lift transition-all" data-toggle="modal" data-target="#deleteAccountModal">
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
    <div class="modal-dialog modal-dialog-centered scale-in" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAccountModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirmer la suppression
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="delete-account-form" method="POST" action="/settings/delete-account">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <div class="alert alert-danger shake">
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

                    <div class="form-group input-group">
                        <label for="password">Entrez votre mot de passe pour confirmer</label>
                        <i class="fas fa-lock input-group-icon"></i>
                        <input type="password" class="form-control transition-all" id="password" name="password" required>
                    </div>

                    <div class="form-group input-group">
                        <label for="confirmation">
                            Tapez <strong>SUPPRIMER</strong> pour confirmer
                        </label>
                        <i class="fas fa-keyboard input-group-icon"></i>
                        <input type="text" class="form-control transition-all" id="confirmation"
                               name="confirmation" placeholder="SUPPRIMER" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary hover-lift transition-all" data-dismiss="modal">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-danger hover-lift transition-all">
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

.password-strength {
    margin-top: -10px;
    margin-bottom: 15px;
}

.strength-bar {
    height: 4px;
    background: #e0e0e0;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 5px;
}

.strength-fill {
    height: 100%;
    width: 0;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-text {
    font-size: 12px;
    font-weight: 600;
}
</style>

<script>
// Password toggle functionality
function togglePasswordField(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = event.currentTarget.querySelector('i');

    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength indicator
document.getElementById('new_password')?.addEventListener('input', function(e) {
    const password = e.target.value;
    const strengthContainer = document.getElementById('password-strength');
    const strengthFill = strengthContainer.querySelector('.strength-fill');
    const strengthText = strengthContainer.querySelector('.strength-text');

    if (password.length === 0) {
        strengthContainer.style.display = 'none';
        return;
    }

    strengthContainer.style.display = 'block';

    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;

    const percentage = (strength / 5) * 100;
    strengthFill.style.width = percentage + '%';

    if (strength <= 2) {
        strengthFill.style.backgroundColor = '#dc3545';
        strengthText.textContent = 'Faible';
        strengthText.style.color = '#dc3545';
    } else if (strength <= 3) {
        strengthFill.style.backgroundColor = '#ffc107';
        strengthText.textContent = 'Moyen';
        strengthText.style.color = '#ffc107';
    } else {
        strengthFill.style.backgroundColor = '#28a745';
        strengthText.textContent = 'Fort';
        strengthText.style.color = '#28a745';
    }
});

// Password confirmation validation
document.getElementById('confirm_password')?.addEventListener('input', function(e) {
    const password = document.getElementById('new_password').value;
    const confirm = e.target.value;

    if (confirm.length > 0) {
        if (password === confirm) {
            e.target.classList.remove('error');
            e.target.classList.add('success');
        } else {
            e.target.classList.remove('success');
            e.target.classList.add('error');
        }
    } else {
        e.target.classList.remove('success', 'error');
    }
});

// Profile form submission
document.getElementById('profile-form')?.addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;

    toast.loading('Mise à jour du profil en cours...');
});

// Password form submission with validation
document.getElementById('password-form')?.addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (newPassword !== confirmPassword) {
        e.preventDefault();
        toast.error('Les mots de passe ne correspondent pas');
        return;
    }

    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;

    toast.loading('Changement du mot de passe en cours...');
});

// Delete account form submission with validation
document.getElementById('delete-account-form')?.addEventListener('submit', function(e) {
    const confirmation = document.getElementById('confirmation').value;

    if (confirmation !== 'SUPPRIMER') {
        e.preventDefault();
        toast.error('Veuillez taper SUPPRIMER pour confirmer');
        document.getElementById('confirmation').classList.add('error');
        return;
    }

    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;

    toast.loading('Suppression du compte en cours...');
});

// Input validation feedback
document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('blur', function() {
        if (this.value && this.checkValidity()) {
            this.classList.add('success');
            this.classList.remove('error');
        } else if (this.value && !this.checkValidity()) {
            this.classList.add('error');
            this.classList.remove('success');
        }
    });

    input.addEventListener('input', function() {
        if (this.id !== 'confirm_password' && this.id !== 'new_password') {
            this.classList.remove('error', 'success');
        }
    });
});
</script>
