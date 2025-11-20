-- Migration: Create notificationsettings table
-- Date: 2025-11-20
-- Description: Table pour gérer les paramètres de notifications des utilisateurs

CREATE TABLE IF NOT EXISTS notificationsettings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    budget_alert_80 TINYINT(1) DEFAULT 1 COMMENT 'Alerte à 80% du budget',
    budget_alert_100 TINYINT(1) DEFAULT 1 COMMENT 'Alerte à 100% du budget',
    expense_alert_threshold DECIMAL(10, 2) DEFAULT 50000.00 COMMENT 'Seuil d\'alerte pour les dépenses',
    expense_alert_enabled TINYINT(1) DEFAULT 1 COMMENT 'Activer les alertes de dépenses',
    monthly_summary TINYINT(1) DEFAULT 1 COMMENT 'Récapitulatif mensuel activé',
    summary_day INT DEFAULT 1 COMMENT 'Jour du mois pour le récapitulatif (1-28)',
    email_enabled TINYINT(1) DEFAULT 1 COMMENT 'Notifications par email activées',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_settings (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes pour améliorer les performances
CREATE INDEX idx_user_id ON notificationsettings(user_id);
CREATE INDEX idx_monthly_summary ON notificationsettings(monthly_summary, email_enabled);

-- Commentaire sur la table
ALTER TABLE notificationsettings COMMENT = 'Paramètres de notifications par utilisateur';
