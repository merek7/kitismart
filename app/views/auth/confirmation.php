<section class="confirmation-section">
    <div class="confirmation-container">
        <?php if (isset($success)): ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <h2>✓ Confirmation réussie</h2>
                    <p><?= isset($message) ? $message : 'Votre compte a été activé avec succès' ?></p>
                    <a href="/login" class="btn btn-primary">Connectez-vous</a>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <h2>⚠ Erreur</h2>
                    <p><?= isset($message) ? $message : 'Une erreur est survenue' ?></p>
                    <a href="/register" class="btn btn-secondary">Retour à l'inscription</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<style>
.confirmation-section {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background-color: #f8f9fa;
}

.confirmation-container {
    max-width: 500px;
    width: 100%;
    padding: 30px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    text-align: center;
}
</style> 