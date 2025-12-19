-- Migration: Ajouter la table de suivi d'utilisation IA par utilisateur
-- Date: 2024-12-19

CREATE TABLE IF NOT EXISTS useraiusage (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    requests_today INTEGER DEFAULT 0,
    total_requests INTEGER DEFAULT 0,
    last_date DATE,
    last_request_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id)
);

-- Index pour les recherches rapides
CREATE INDEX IF NOT EXISTS idx_useraiusage_user_id ON useraiusage(user_id);
CREATE INDEX IF NOT EXISTS idx_useraiusage_last_date ON useraiusage(last_date);
