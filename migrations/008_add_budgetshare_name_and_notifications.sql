-- Migration: Add name to budgetshare and create notifications table
-- Date: 2024-12-01
-- Description: Ajoute un nom personnalisé aux partages et crée la table des notifications

-- Ajouter la colonne name à budgetshare
ALTER TABLE budgetshare ADD COLUMN IF NOT EXISTS name VARCHAR(100) DEFAULT NULL;

-- Créer la table des notifications
CREATE TABLE IF NOT EXISTS notification (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSONB DEFAULT NULL,
    is_read SMALLINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP DEFAULT NULL
);

-- Index pour les notifications
CREATE INDEX IF NOT EXISTS idx_notification_user_id ON notification(user_id);
CREATE INDEX IF NOT EXISTS idx_notification_is_read ON notification(is_read);
CREATE INDEX IF NOT EXISTS idx_notification_type ON notification(type);
CREATE INDEX IF NOT EXISTS idx_notification_created_at ON notification(created_at);
