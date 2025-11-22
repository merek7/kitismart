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

<style>
.badge-dev {
    background: linear-gradient(135deg, #facc15, #f59e0b);
    color: #1f2937;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.info-box-primary {
    background: linear-gradient(135deg, #e0f2fe, #bae6fd);
    border-left-color: #0ea5e9;
}

.info-box-primary a {
    color: #0369a1;
    font-weight: 600;
}

.setting-control-wide {
    min-width: 300px;
}

.setting-control-wide .form-control,
.setting-control-wide .form-select {
    width: 100%;
}

.setting-actions {
    justify-content: flex-start;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--border-color, #e5e7eb);
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

.email-icon {
    font-size: 1.5rem;
    margin-right: 0.5rem;
}

.btn-preview {
    background: var(--primary-color, #0d9488);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.btn-preview:hover {
    background: var(--secondary-color, #0f766e);
    color: white;
}

.smtp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.smtp-item {
    text-align: center;
    padding: 1rem;
    background: var(--bg-secondary, #f9fafb);
    border-radius: 8px;
}

.smtp-label {
    font-size: 0.8rem;
    color: var(--text-muted, #6b7280);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.smtp-value {
    font-weight: 600;
    color: var(--text-primary, #1f2937);
    word-break: break-all;
}

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-right: 0.5rem;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Mode sombre */
[data-theme="dark"] .badge-dev {
    background: linear-gradient(135deg, #facc15, #f59e0b);
    color: #1f2937;
}

[data-theme="dark"] .info-box-primary {
    background: rgba(14, 165, 233, 0.1);
}

[data-theme="dark"] .btn-secondary {
    background: #374151;
    color: #f9fafb;
    border-color: #4b5563;
}

[data-theme="dark"] .btn-secondary:hover {
    background: #4b5563;
}

[data-theme="dark"] .smtp-item {
    background: #1f2937;
}

@media (max-width: 768px) {
    .setting-control-wide {
        min-width: 100%;
    }

    .setting-actions {
        flex-direction: column;
    }

    .setting-actions button {
        width: 100%;
        justify-content: center;
    }

    .smtp-grid {
        grid-template-columns: 1fr 1fr;
    }
}
</style>

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
