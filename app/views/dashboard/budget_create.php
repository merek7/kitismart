<div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <h1><i class="fas fa-wallet"></i> Nouveau Budget</h1>
                    </div>
                    <div class="col-md-6">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a></li>
                            <li class="breadcrumb-item active">Nouveau Budget</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-plus-circle"></i> <?= $title ?? 'Créer un Budget' ?></h3>
                            </div>

                            <div id="global-message" class="message"></div>

                            <form id="budget-form" action="/budget/create" method="POST">
                                <div class="card-body">
                                    <?php if(isset($errors)): ?>
                                        <div class="alert alert-danger">
                                            <?php foreach($errors as $error): ?>
                                                <p><i class="fas fa-exclamation-circle"></i> <?= $error ?></p>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Nom du Budget</label>
                                                <div class="input-group">
                                                    <i class="fas fa-tag"></i>
                                                    <input type="text" 
                                                        class="form-control" 
                                                        id="name" 
                                                        name="name" 
                                                        placeholder="Ex: Budget vacances été 2025"
                                                        required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="amount">Montant Total</label>
                                                <div class="input-group amount">
                                                    <i class="fas fa-coins"></i>
                                                    <input type="number" 
                                                        class="form-control" 
                                                        id="amount" 
                                                        name="amount" 
                                                        step="0.01" 
                                                        min="0.01"
                                                        placeholder="0.00"
                                                       
                                                        required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="start_date">Date de début</label>
                                                <div class="input-group date">
                                                    <i class="fas fa-calendar-alt"></i>
                                                    <input type="date" 
                                                        class="form-control" 
                                                        id="start_date" 
                                                        name="start_date" 
                                                        required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <div class="input-group">
                                            <i class="fas fa-align-left"></i>
                                            <textarea class="form-control" 
                                                    id="description" 
                                                    name="description" 
                                                    rows="4"
                                                    placeholder="Décrivez l'objectif et les détails de ce budget..."></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Créer</button>
                                    <a href="/dashboard" class="btn btn-cancel"><i class="fas fa-times"></i> Annuler</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

