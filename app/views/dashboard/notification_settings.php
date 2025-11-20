<div class="content-wrapper">
    <div class="page-header">
        <h1><i class="fas fa-bell"></i> Paramètres de Notifications</h1>
        <div class="breadcrumb">
            <a href="/dashboard"><i class="fas fa-home"></i> Tableau de bord</a>
            <span>/</span>
            <span>Notifications</span>
        </div>
    </div>

    <div class="page-content">
        <div class="container">
            <div class="settings-header">
                <div>
                    <p class="subtitle">Configurez vos préférences d'alertes et de notifications par email</p>
                </div>
            </div>

            <!-- Résumé des paramètres actifs -->
            <div class="settings-summary">
                <div class="summary-header">
                    <i class="fas fa-info-circle"></i>
                    <span>État actuel</span>
                </div>
                <div class="summary-content">
                    <div class="summary-item <?= $settings->email_enabled ? 'active' : 'inactive' ?>">
                        <i class="fas fa-<?= $settings->email_enabled ? 'check-circle' : 'times-circle' ?>"></i>
                        <span>Notifications email: <strong><?= $settings->email_enabled ? 'Activées' : 'Désactivées' ?></strong></span>
                    </div>
                    <?php if ($settings->email_enabled): ?>
                        <div class="summary-item active">
                            <i class="fas fa-bell"></i>
                            <span>
                                <?php
                                $activeAlerts = 0;
                                if ($settings->budget_alert_80) $activeAlerts++;
                                if ($settings->budget_alert_100) $activeAlerts++;
                                if ($settings->expense_alert_enabled) $activeAlerts++;
                                if ($settings->monthly_summary) $activeAlerts++;
                                ?>
                                <strong><?= $activeAlerts ?></strong> type(s) d'alerte activé(s)
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Message de feedback -->
            <div id="feedback-message" class="alert" style="display: none;"></div>

            <div class="settings-container">
                <form id="notification-settings-form">
                    <!-- Section: Activation globale -->
                    <div class="settings-section">
                        <div class="section-header">
                            <div>
                                <h2><i class="fas fa-toggle-on"></i> Activation globale</h2>
                                <p>Activer ou désactiver toutes les notifications par email</p>
                            </div>
                        </div>
                        <div class="settings-card">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">
                                        <i class="fas fa-envelope"></i> Notifications par email
                                    </div>
                                    <div class="setting-description">
                                        Recevoir toutes les notifications par email
                                    </div>
                                </div>
                                <div class="setting-control">
                                    <label class="switch">
                                        <input type="checkbox" id="email_enabled" name="email_enabled"
                                               <?= $settings->email_enabled ? 'checked' : '' ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Alertes de budget -->
                    <div class="settings-section">
                        <div class="section-header">
                            <div>
                                <h2><i class="fas fa-chart-line"></i> Alertes de budget</h2>
                                <p>Recevez des alertes lorsque vous atteignez certains seuils de votre budget</p>
                            </div>
                        </div>
                        <div class="settings-card">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">
                                        <i class="fas fa-exclamation-triangle" style="color: #facc15;"></i> Alerte à 80%
                                    </div>
                                    <div class="setting-description">
                                        Recevoir une alerte quand 80% du budget est consommé
                                    </div>
                                </div>
                                <div class="setting-control">
                                    <label class="switch">
                                        <input type="checkbox" id="budget_alert_80" name="budget_alert_80"
                                               <?= $settings->budget_alert_80 ? 'checked' : '' ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">
                                        <i class="fas fa-exclamation-circle" style="color: #ef4444;"></i> Alerte à 100%
                                    </div>
                                    <div class="setting-description">
                                        Recevoir une alerte quand le budget est atteint ou dépassé
                                    </div>
                                </div>
                                <div class="setting-control">
                                    <label class="switch">
                                        <input type="checkbox" id="budget_alert_100" name="budget_alert_100"
                                               <?= $settings->budget_alert_100 ? 'checked' : '' ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Alertes de dépenses -->
                    <div class="settings-section">
                        <div class="section-header">
                            <div>
                                <h2><i class="fas fa-credit-card"></i> Alertes de dépenses</h2>
                                <p>Recevez une notification pour les dépenses importantes</p>
                            </div>
                        </div>
                        <div class="settings-card">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">
                                        <i class="fas fa-bell"></i> Alerte dépense importante
                                    </div>
                                    <div class="setting-description">
                                        Recevoir une alerte pour les dépenses dépassant un certain montant
                                    </div>
                                </div>
                                <div class="setting-control">
                                    <label class="switch">
                                        <input type="checkbox" id="expense_alert_enabled" name="expense_alert_enabled"
                                               <?= $settings->expense_alert_enabled ? 'checked' : '' ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="setting-item threshold-setting">
                                <div class="setting-info">
                                    <div class="setting-title">
                                        <i class="fas fa-sliders-h"></i> Seuil d'alerte
                                    </div>
                                    <div class="setting-description">
                                        Montant à partir duquel vous souhaitez être alerté
                                    </div>
                                </div>
                                <div class="setting-control">
                                    <div class="input-group threshold-input">
                                        <input type="number" class="form-control" id="expense_alert_threshold"
                                               name="expense_alert_threshold"
                                               value="<?= $settings->expense_alert_threshold ?>"
                                               min="0" step="1000">
                                        <span class="input-group-text">FCFA</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Récapitulatif mensuel -->
                    <div class="settings-section">
                        <div class="section-header">
                            <div>
                                <h2><i class="fas fa-calendar-alt"></i> Récapitulatif mensuel</h2>
                                <p>Recevez un résumé complet de vos finances chaque mois</p>
                            </div>
                        </div>
                        <div class="settings-card">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">
                                        <i class="fas fa-file-pdf"></i> Récapitulatif mensuel
                                    </div>
                                    <div class="setting-description">
                                        Recevoir un récapitulatif détaillé avec statistiques chaque mois
                                    </div>
                                </div>
                                <div class="setting-control">
                                    <label class="switch">
                                        <input type="checkbox" id="monthly_summary" name="monthly_summary"
                                               <?= $settings->monthly_summary ? 'checked' : '' ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <div class="setting-title">
                                        <i class="fas fa-calendar-day"></i> Jour d'envoi
                                    </div>
                                    <div class="setting-description">
                                        Jour du mois où vous souhaitez recevoir le récapitulatif
                                    </div>
                                </div>
                                <div class="setting-control">
                                    <select class="form-select day-select" id="summary_day" name="summary_day">
                                        <?php for($i = 1; $i <= 28; $i++): ?>
                                            <option value="<?= $i ?>" <?= $settings->summary_day == $i ? 'selected' : '' ?>>
                                                <?= $i ?><?= $i == 1 ? 'er' : '' ?> du mois
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bouton de sauvegarde -->
                    <div class="save-section">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Enregistrer les paramètres
                        </button>
                    </div>
                </form>

                <!-- Info box -->
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Note :</strong> Les notifications sont envoyées à l'adresse email associée à votre compte.
                        Assurez-vous que votre adresse email est correcte dans les paramètres de votre profil.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
