-- Migration: Add Google OAuth support
-- Date: 2024-11-22
-- Description: Add google_id and avatar columns to users table for Google OAuth

-- Add google_id column (unique identifier from Google)
ALTER TABLE users ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(500) NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL;

-- Create index for faster lookup by google_id
CREATE INDEX IF NOT EXISTS idx_users_google_id ON users (google_id);

-- Make password nullable (for Google-only accounts)
ALTER TABLE users ALTER COLUMN password DROP NOT NULL;

-- Note: Run this migration manually or via your migration system
-- For PostgreSQL: psql -U postgres -d kiti -f migrations/005_add_google_oauth.sql
-- For MySQL: mysql -u root -p kiti < migrations/005_add_google_oauth.sql
