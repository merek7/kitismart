-- Migration: Create Budget Shares Table
-- Description: Crée les tables pour le système de partage de budget
-- Date: 2025-11-21
-- Compatible: PostgreSQL

-- Table principale pour les partages de budget
CREATE TABLE IF NOT EXISTS budgetshare (
  id SERIAL PRIMARY KEY,
  budget_id INTEGER NOT NULL,
  created_by_user_id INTEGER NOT NULL,
  share_token VARCHAR(64) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  permissions JSONB NOT NULL DEFAULT '{"can_view": true, "can_add": false, "can_edit": false, "can_delete": false, "can_view_stats": true}',
  expires_at TIMESTAMP DEFAULT NULL,
  max_uses INTEGER DEFAULT NULL,
  use_count INTEGER DEFAULT 0,
  is_active SMALLINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT NULL
);

-- Index et contraintes pour budgetshare
CREATE INDEX idx_budgetshare_budget_id ON budgetshare(budget_id);
CREATE INDEX idx_budgetshare_created_by ON budgetshare(created_by_user_id);
CREATE INDEX idx_budgetshare_is_active ON budgetshare(is_active);
CREATE INDEX idx_budgetshare_expires_at ON budgetshare(expires_at);
CREATE INDEX idx_budgetshare_token ON budgetshare(share_token);

-- Table pour les logs d'accès et d'audit
CREATE TABLE IF NOT EXISTS budgetshare_log (
  id SERIAL PRIMARY KEY,
  share_id INTEGER NOT NULL,
  action VARCHAR(50) NOT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  success SMALLINT DEFAULT 1,
  error_message TEXT DEFAULT NULL,
  metadata JSONB DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index pour budgetshare_log
CREATE INDEX idx_budgetshare_log_share_id ON budgetshare_log(share_id);
CREATE INDEX idx_budgetshare_log_action ON budgetshare_log(action);
CREATE INDEX idx_budgetshare_log_created_at ON budgetshare_log(created_at);
CREATE INDEX idx_budgetshare_log_success ON budgetshare_log(success);

-- Table pour le rate limiting (protection brute-force)
CREATE TABLE IF NOT EXISTS budgetshare_rate_limit (
  id SERIAL PRIMARY KEY,
  share_token VARCHAR(64) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  attempt_count INTEGER DEFAULT 1,
  first_attempt_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_attempt_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  blocked_until TIMESTAMP DEFAULT NULL,
  UNIQUE(share_token, ip_address)
);

-- Index pour budgetshare_rate_limit
CREATE INDEX idx_budgetshare_rate_limit_blocked_until ON budgetshare_rate_limit(blocked_until);
CREATE INDEX idx_budgetshare_rate_limit_last_attempt ON budgetshare_rate_limit(last_attempt_at);
CREATE INDEX idx_budgetshare_rate_limit_cleanup ON budgetshare_rate_limit(last_attempt_at, blocked_until);

-- Note: Les contraintes de clés étrangères sont omises car RedBeanPHP ne les requiert pas
-- et peut avoir des problèmes avec elles. RedBeanPHP gère les relations de manière programmatique.
