-- Migration: Add company_code to companies table and update password reset
USE alm_biometrics;

-- Add company_code column to companies table
ALTER TABLE companies 
ADD COLUMN IF NOT EXISTS company_code VARCHAR(20) UNIQUE NULL AFTER id;

-- Update existing companies with a unique code if they don't have one
UPDATE companies 
SET company_code = CONCAT('CC', LPAD(id, 5, '0')) 
WHERE company_code IS NULL;

-- Make company_code NOT NULL after populating
ALTER TABLE companies 
MODIFY company_code VARCHAR(20) UNIQUE NOT NULL;

-- Add index for faster lookups
ALTER TABLE companies 
ADD INDEX IF NOT EXISTS idx_company_code (company_code);

-- Password resets table for forgot password functionality (if not exists)
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Login attempts table for rate limiting (if not exists)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(100),
    attempt_count INT DEFAULT 1,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    locked_until TIMESTAMP NULL,
    INDEX idx_ip (ip_address),
    INDEX idx_locked (locked_until)
);
