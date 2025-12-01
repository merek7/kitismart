-- Migration: Increase avatar column size
-- Date: 2024-11-30
-- Description: Google avatar URLs can exceed 500 characters

ALTER TABLE users ALTER COLUMN avatar TYPE VARCHAR(2048);
