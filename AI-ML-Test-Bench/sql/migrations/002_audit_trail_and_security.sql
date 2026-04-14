-- Migration: Add audit trail logging
USE alm_biometrics;

-- Audit log table for tracking sensitive operations
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50), -- employee, payroll, user, etc.
    entity_id INT,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company (company_id),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Add company_code to companies table if not exists
ALTER TABLE companies 
ADD COLUMN IF NOT EXISTS company_code VARCHAR(20) UNIQUE AFTER name;

-- Add indices for foreign keys (missing in original schema)
CREATE INDEX idx_leave_requests_employee ON leave_requests(employee_id);
CREATE INDEX idx_loans_employee ON loans(employee_id);
CREATE INDEX idx_resignations_employee ON resignations(employee_id);
CREATE INDEX idx_employee_allowances_employee ON employee_allowances(employee_id);
CREATE INDEX idx_employee_deductions_employee ON employee_deductions(employee_id);
CREATE INDEX idx_subject_loads_faculty ON subject_loads(faculty_id);

-- Add session management table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add 2FA support to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS two_factor_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(255),
ADD COLUMN IF NOT EXISTS password_last_changed TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Add encryption support for biometric data
ALTER TABLE employees 
ADD COLUMN IF NOT EXISTS face_descriptor_encrypted LONGTEXT,
ADD COLUMN IF NOT EXISTS encryption_iv VARCHAR(255);
