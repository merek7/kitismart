-- Migration: Add source_type to budget table
-- Description: Permet de taguer les budgets par source de revenu (salaire, prime, etc.)
-- Date: 2025-12-06

-- Ajouter la colonne source_type (nullable pour compatibilité)
ALTER TABLE budget ADD COLUMN IF NOT EXISTS source_type VARCHAR(50) DEFAULT NULL;

-- Index pour les requêtes par source
CREATE INDEX IF NOT EXISTS idx_budget_source_type ON budget(source_type);
CREATE INDEX IF NOT EXISTS idx_budget_user_source ON budget(user_id, source_type);

-- Commentaire sur la colonne
COMMENT ON COLUMN budget.source_type IS 'Type de source de revenu: salaire, prime, freelance, revenu_locatif, autre';
