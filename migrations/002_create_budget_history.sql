-- Migration: Create Budget History Table
-- Description: Crée la table pour l'historique des budgets archivés
-- Date: 2025-11-20

CREATE TABLE IF NOT EXISTS `budgethistory` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `original_budget_id` INT(11) UNSIGNED DEFAULT NULL,
  `month` INT(2) NOT NULL,
  `year` INT(4) NOT NULL,
  `total_budget` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_spent` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_remaining` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `expenses_count` INT(11) UNSIGNED DEFAULT 0,
  `archived_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_month_year` (`month`, `year`),
  KEY `idx_user_month_year` (`user_id`, `year`, `month`),
  KEY `idx_archived_at` (`archived_at`),
  CONSTRAINT `fk_budgethistory_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
