-- Migration: Create Custom Categories Table
-- Description: Crée la table pour les catégories personnalisées des utilisateurs
-- Date: 2025-11-20

CREATE TABLE IF NOT EXISTS `customcategory` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'fa-tag',
  `color` VARCHAR(7) DEFAULT '#0d9488',
  `description` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_user_active` (`user_id`, `is_active`),
  CONSTRAINT `fk_customcategory_user` FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes pour améliorer les performances
CREATE INDEX `idx_created_at` ON `customcategory`(`created_at`);
