<div class="content-wrapper">
        <section class="content-header fade-in-up">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <h1><i class="fas fa-wallet"></i> Nouveau Budget</h1>
                    </div>
                    <div class="col-md-6">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/dashboard" class="transition-colors"><i class="fas fa-home"></i> Tableau de bord</a></li>
                            <li class="breadcrumb-item active">Nouveau Budget</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container">
                <div class="row">
                    <div class="col-md-12 fade-in-up delay-1">
                        <div class="card hover-lift">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-plus-circle"></i> <?= $title ?? 'Créer un Budget' ?></h3>
                            </div>

                            <div id="global-message" class="message alert" style="display: none;"></div>

                            <form id="budget-form" action="/budget/create" method="POST">
                                <div class="card-body">
                                    <?php if(isset($errors)): ?>
                                        <div class="alert alert-danger alert-dismissible fade-in">
                                            <?php foreach($errors as $error): ?>
                                                <p><i class="fas fa-exclamation-circle"></i> <?= $error ?></p>
                                            <?php endforeach; ?>
                                            <button type="button" class="close" data-dismiss="alert">
                                                <span>&times;</span>
                                            </button>
                                        </div>
                                    <?php endif; ?>

                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group input-group">
                                                <label for="name">Nom du Budget</label>
                                                <i class="fas fa-tag input-group-icon"></i>
                                                <input type="text"
                                                    class="form-control transition-all"
                                                    id="name"
                                                    name="name"
                                                    placeholder="Ex: Budget vacances été 2025"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group input-group">
                                                <label for="amount">Montant Total</label>
                                                <i class="fas fa-coins input-group-icon"></i>
                                                <input type="number"
                                                    class="form-control transition-all"
                                                    id="amount"
                                                    name="amount"
                                                    step="0.01"
                                                    min="0.01"
                                                    placeholder="0.00"
                                                    required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group input-group">
                                                <label for="start_date">Date de début</label>
                                                <i class="fas fa-calendar-alt input-group-icon"></i>
                                                <input type="date"
                                                    class="form-control transition-all"
                                                    id="start_date"
                                                    name="start_date"
                                                    required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group input-group">
                                        <label for="description">Description</label>
                                        <i class="fas fa-align-left input-group-icon"></i>
                                        <textarea class="form-control transition-all"
                                                id="description"
                                                name="description"
                                                rows="4"
                                                placeholder="Décrivez l'objectif et les détails de ce budget..."></textarea>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary hover-lift transition-all">
                                        <i class="fas fa-save"></i> Créer
                                    </button>
                                    <a href="/dashboard" class="btn btn-cancel transition-all">
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

<script>
// Form submission with loading state
document.getElementById('budget-form').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;

    // Show toast notification
    const loadingToast = toast.loading('Création du budget en cours...');
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
        this.classList.remove('error', 'success');
    });
});
</script>

