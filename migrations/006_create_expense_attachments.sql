-- Migration: Ajout des pièces jointes pour les dépenses
-- Version: 1.5.0
-- Date: 2025-01-27
-- Compatible: PostgreSQL

CREATE TABLE IF NOT EXISTS expenseattachment (
    id SERIAL PRIMARY KEY,
    expense_id INTEGER NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INTEGER NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by INTEGER NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Index pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_expenseattachment_expense_id ON expenseattachment(expense_id);
CREATE INDEX IF NOT EXISTS idx_expenseattachment_uploaded_by ON expenseattachment(uploaded_by);

-- Note: Les contraintes de clés étrangères sont omises car RedBeanPHP ne les requiert pas
-- et peut avoir des problèmes avec elles. RedBeanPHP gère les relations de manière programmatique.
