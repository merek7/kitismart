<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-envelope"></i> Test des Emails</h1>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a>
            <span>/</span>
            <span>Test Emails</span>
        </div>
    </div>

    <div class="page-content">
        <div class="container">
            <div class="settings-header">
                <div>
                    <p class="subtitle">Testez et prévisualisez tous les types d'emails de l'application</p>
                </div>
                <span class="badge-dev">Mode Développement</span>
            </div>

            <!-- Info SMTP -->
            <div class="info-box info-box-primary">
                <i class="fas fa-server"></i>
                <div>
                    Les emails sont envoyés via <strong><?= htmlspecialchars($_ENV['SMTP_HOST'] ?? 'SMTP non configuré') ?></strong>.
                    Consultez <a href="https://mailtrap.io" target="_blank">Mailtrap</a> pour voir les emails de test.
                </div>
            </div>

            <!-- Message de feedback -->
            <div id="feedback-message" class="alert" style="display: none;"></div>

            <div class="settings-container">
                <!-- Section: Envoyer un email de test -->
                <div class="settings-section">
                    <div class="section-header">
                        <div>
                            <h2><i class="fas fa-paper-plane"></i> Envoyer un email de test</h2>
                            <p>Sélectionnez le type d'email et entrez une adresse de destination</p>
                        </div>
                    </div>
                    <div class="settings-card">
                        <form id="emailTestForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">
                                        <i class="fas fa-at"></i> Email destinataire
                                    </div>
                                    <div class="setting-description">
                                        L'adresse email qui recevra le test
                                    </div>
                                </div>
                                <div class="setting-control setting-control-wide">
                                    <input type="email"
                                           class="form-control"
                                           id="recipient_email"
                                           name="recipient_email"
                                           placeholder="test@exemple.com"
                                           required>
                                </div>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">
                                        <i class="fas fa-list"></i> Type d'email
                                    </div>
                                    <div class="setting-description">
                                        Le type de notification à tester
                                    </div>
                                </div>
                                <div class="setting-control setting-control-wide">
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

                            <div class="setting-item setting-actions">
                                <button type="submit" class="btn-save" id="sendBtn">
                                    <span class="spinner" style="display: none;"></span>
                                    <i class="fas fa-paper-plane"></i> Envoyer l'email de test
                                </button>
                                <button type="button" class="btn-secondary" id="previewBtn">
                                    <i class="fas fa-eye"></i> Prévisualiser
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Section: Types d'emails disponibles -->
                <div class="settings-section">
                    <div class="section-header">
                        <div>
                            <h2><i class="fas fa-mail-bulk"></i> Types d'emails disponibles</h2>
                            <p>Tous les emails que l'application peut envoyer</p>
                        </div>
                    </div>
                    <div class="settings-card">
                        <?php foreach ($emailTypes as $key => $type): ?>
                        <div class="setting-item email-type-item">
                            <div class="setting-info">
                                <div class="setting-title">
                                    <span class="email-icon"><?= $type['icon'] ?></span>
                                    <?= htmlspecialchars($type['name']) ?>
                                </div>
                                <div class="setting-description">
                                    <?= htmlspecialchars($type['description']) ?>
                                </div>
                            </div>
                            <div class="setting-control">
                                <a href="/admin/email-test/preview?type=<?= urlencode($key) ?>"
                                   target="_blank"
                                   class="btn-preview">
                                    <i class="fas fa-external-link-alt"></i> Voir
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Section: Configuration SMTP -->
                <div class="settings-section">
                    <div class="section-header">
                        <div>
                            <h2><i class="fas fa-cogs"></i> Configuration SMTP actuelle</h2>
                            <p>Paramètres de connexion au serveur de mail</p>
                        </div>
                    </div>
                    <div class="settings-card smtp-config">
                        <div class="smtp-grid">
                            <div class="smtp-item">
                                <div class="smtp-label"><i class="fas fa-server"></i> Serveur</div>
                                <div class="smtp-value"><?= htmlspecialchars($_ENV['SMTP_HOST'] ?? 'Non configuré') ?></div>
                            </div>
                            <div class="smtp-item">
                                <div class="smtp-label"><i class="fas fa-plug"></i> Port</div>
                                <div class="smtp-value"><?= htmlspecialchars($_ENV['SMTP_PORT'] ?? '2525') ?></div>
                            </div>
                            <div class="smtp-item">
                                <div class="smtp-label"><i class="fas fa-user"></i> Expéditeur</div>
                                <div class="smtp-value"><?= htmlspecialchars($_ENV['MAIL_FROM'] ?? 'Non configuré') ?></div>
                            </div>
                            <div class="smtp-item">
                                <div class="smtp-label"><i class="fas fa-lock"></i> Encryption</div>
                                <div class="smtp-value"><?= htmlspecialchars($_ENV['SMTP_ENCRYPTION'] ?? 'Aucune') ?: 'Aucune' ?></div>
                            </div>
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
    const feedbackMessage = document.getElementById('feedback-message');
    const spinner = sendBtn.querySelector('.spinner');
    const btnIcon = sendBtn.querySelector('.fa-paper-plane');

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
        spinner.style.display = 'inline-block';
        btnIcon.style.display = 'none';

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
            spinner.style.display = 'none';
            btnIcon.style.display = 'inline';
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
        feedbackMessage.className = 'alert alert-' + type;
        feedbackMessage.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'times-circle') + '"></i> ' + message;
        feedbackMessage.style.display = 'flex';
        feedbackMessage.style.alignItems = 'center';
        feedbackMessage.style.gap = '0.5rem';

        setTimeout(() => {
            feedbackMessage.style.display = 'none';
        }, 5000);
    }
});
</script>
