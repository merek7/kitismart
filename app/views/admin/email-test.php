<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 mb-1">Test des Emails</h1>
                    <p class="text-muted mb-0">Testez et prévisualisez tous les types d'emails de l'application</p>
                </div>
                <span class="badge bg-warning text-dark">Mode Développement</span>
            </div>

            <!-- Alert -->
            <div class="alert alert-info d-flex align-items-center mb-4">
                <svg class="me-2" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                </svg>
                <div>
                    Les emails sont envoyés via <strong><?= htmlspecialchars($_ENV['SMTP_HOST'] ?? 'SMTP non configuré') ?></strong>.
                    Consultez <a href="https://mailtrap.io" target="_blank" class="alert-link">Mailtrap</a> pour voir les emails de test.
                </div>
            </div>

            <!-- Email Test Form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Envoyer un email de test</h5>
                </div>
                <div class="card-body">
                    <form id="emailTestForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="recipient_email" class="form-label">Email destinataire</label>
                                <input type="email"
                                       class="form-control"
                                       id="recipient_email"
                                       name="recipient_email"
                                       placeholder="test@exemple.com"
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label for="email_type" class="form-label">Type d'email</label>
                                <select class="form-select" id="email_type" name="email_type" required>
                                    <option value="">Sélectionner un type...</option>
                                    <?php foreach ($emailTypes as $key => $type): ?>
                                        <option value="<?= htmlspecialchars($key) ?>">
                                            <?= $type['icon'] ?> <?= htmlspecialchars($type['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="sendBtn">
                                <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                                Envoyer l'email de test
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="previewBtn">
                                Prévisualiser
                            </button>
                        </div>
                    </form>

                    <!-- Result message -->
                    <div id="resultMessage" class="mt-3 d-none"></div>
                </div>
            </div>

            <!-- Email Types List -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Types d'emails disponibles</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th></th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($emailTypes as $key => $type): ?>
                                <tr>
                                    <td class="text-center" style="width: 50px;">
                                        <span style="font-size: 1.5rem;"><?= $type['icon'] ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($type['name']) ?></strong>
                                    </td>
                                    <td class="text-muted">
                                        <?= htmlspecialchars($type['description']) ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="/admin/email-test/preview?type=<?= urlencode($key) ?>"
                                           target="_blank"
                                           class="btn btn-sm btn-outline-primary">
                                            Voir
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SMTP Configuration Info -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Configuration SMTP actuelle</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block">Serveur</small>
                            <strong><?= htmlspecialchars($_ENV['SMTP_HOST'] ?? 'Non configuré') ?></strong>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted d-block">Port</small>
                            <strong><?= htmlspecialchars($_ENV['SMTP_PORT'] ?? '2525') ?></strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Expéditeur</small>
                            <strong><?= htmlspecialchars($_ENV['MAIL_FROM'] ?? 'Non configuré') ?></strong>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted d-block">Encryption</small>
                            <strong><?= htmlspecialchars($_ENV['SMTP_ENCRYPTION'] ?? 'Aucune') ?: 'Aucune' ?></strong>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted d-block">Environnement</small>
                            <span class="badge bg-warning text-dark"><?= htmlspecialchars($_ENV['APP_ENV'] ?? 'dev') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('emailTestForm');
    const sendBtn = document.getElementById('sendBtn');
    const previewBtn = document.getElementById('previewBtn');
    const resultMessage = document.getElementById('resultMessage');
    const spinner = sendBtn.querySelector('.spinner-border');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const data = {
            csrf_token: formData.get('csrf_token'),
            recipient_email: formData.get('recipient_email'),
            email_type: formData.get('email_type')
        };

        if (!data.email_type) {
            showMessage('Veuillez sélectionner un type d\'email', 'warning');
            return;
        }

        sendBtn.disabled = true;
        spinner.classList.remove('d-none');

        try {
            const response = await fetch('/admin/email-test/send', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showMessage(result.message, 'success');
            } else {
                showMessage(result.message, 'danger');
            }
        } catch (error) {
            showMessage('Erreur de connexion: ' + error.message, 'danger');
        } finally {
            sendBtn.disabled = false;
            spinner.classList.add('d-none');
        }
    });

    previewBtn.addEventListener('click', function() {
        const emailType = document.getElementById('email_type').value;
        if (!emailType) {
            showMessage('Veuillez sélectionner un type d\'email', 'warning');
            return;
        }
        window.open('/admin/email-test/preview?type=' + encodeURIComponent(emailType), '_blank');
    });

    function showMessage(message, type) {
        resultMessage.className = 'mt-3 alert alert-' + type;
        resultMessage.textContent = message;
        resultMessage.classList.remove('d-none');

        setTimeout(() => {
            resultMessage.classList.add('d-none');
        }, 5000);
    }
});
</script>
