-- Add 2FA columns to users table for Google Authenticator
ALTER TABLE users 
ADD COLUMN two_factor_secret VARCHAR(255) NULL,
ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE;

