-- ============================================================================
-- ALM Biometric Attendance & Payroll System - Complete Database Schema
-- Version: 2.4.0 - Build9
-- Includes: Base schema + All migrations (001-004)
-- ============================================================================

-- Create database
CREATE DATABASE IF NOT EXISTS alm_biometrics;
USE alm_biometrics;

-- ============================================================================
-- BASE SCHEMA
-- ============================================================================

-- Companies Table
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    company_code VARCHAR(20) UNIQUE NOT NULL,
    admin_email VARCHAR(255) UNIQUE NOT NULL,
    timezone VARCHAR(100) DEFAULT 'Asia/Manila',
    work_start TIME DEFAULT '08:00:00',
    work_end TIME DEFAULT '17:00:00',
    lunch_start TIME DEFAULT '10:00:00',
    lunch_end TIME DEFAULT '11:00:00',
    lunch_out_start TIME DEFAULT '10:00:00',
    lunch_out_end TIME DEFAULT '10:30:00',
    lunch_in_start TIME DEFAULT '10:30:00',
    lunch_in_end TIME DEFAULT '11:00:00',
    lunch_buffer INT DEFAULT 30,
    checkout_buffer INT DEFAULT 60,
    ot_percentage INT DEFAULT 25,
    deduction_per_sec DECIMAL(10, 4) DEFAULT 0.0083,
    deduction_per_min DECIMAL(10, 2) DEFAULT 0.50,
    deduction_per_hour DECIMAL(10, 2) DEFAULT 30.00,
    biometric_match_threshold DECIMAL(10, 2) DEFAULT 0.60,
    biometric_duplicate_threshold DECIMAL(10, 2) DEFAULT 0.38,
    biometric_ambiguity_ratio DECIMAL(10, 2) DEFAULT 1.05,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_company_code (company_code)
);

-- Users Table (Roles: HR, Payroll, Employee)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('HR', 'Admin', 'Payroll', 'Payroll Officer', 'Employee') DEFAULT 'Employee',
    email VARCHAR(255) NOT NULL,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255),
    password_last_changed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Employees Table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id VARCHAR(50) NOT NULL, -- e.g. EMP001
    full_name VARCHAR(255) NOT NULL,
    dob DATE,
    position VARCHAR(100),
    department VARCHAR(100),
    faculty_level ENUM('SHS', 'College', 'Both') NULL,
    basic_salary DECIMAL(10, 2),
    status ENUM('Active', 'Inactive', 'On Leave', 'Probationary', 'Contractual', 'Resigned') DEFAULT 'Active',
    hire_date DATE NULL,
    reinstated_at DATETIME NULL,
    reinstated_by INT NULL,
    email VARCHAR(255),
    sss VARCHAR(50),
    tin VARCHAR(50),
    philhealth VARCHAR(50),
    pagibig VARCHAR(50),
    leave_balance INT DEFAULT 15,
    face_descriptor JSON, -- Serialized 128-float array
    face_descriptor_encrypted LONGTEXT,
    encryption_iv VARCHAR(255),
    profile_picture VARCHAR(255) DEFAULT NULL,
    user_id INT, -- Link to users table for ESS
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(company_id, employee_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_hire_date (hire_date)
);

-- Attendance Logs
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    log_date DATE NOT NULL,
    check_in TIME,
    lunch_out TIME,
    lunch_in TIME,
    check_out TIME,
    status VARCHAR(50), -- e.g. On-Time, Late, Absent
    late_minutes INT DEFAULT 0,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);
CREATE INDEX idx_attendance_date ON attendance(log_date);
CREATE INDEX idx_attendance_emp_date ON attendance(employee_id, log_date);
CREATE INDEX idx_attendance_company ON attendance(company_id);
CREATE INDEX idx_attendance_company_emp_date ON attendance(company_id, employee_id, log_date);

-- Payroll History
CREATE TABLE IF NOT EXISTS payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    payroll_type ENUM('General', 'Faculty', 'Utility') DEFAULT 'General',
    period VARCHAR(50), -- e.g. 03/2026
    basic_pay DECIMAL(10, 2),
    deductions DECIMAL(10, 2),
    net_pay DECIMAL(10, 2),
    breakdown JSON,
    status ENUM('Pending', 'Paid') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payroll_company_emp_period (company_id, employee_id, period),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);
CREATE INDEX idx_payroll_period ON payroll(period);
CREATE INDEX idx_payroll_company_period ON payroll(company_id, period);
CREATE INDEX idx_payroll_company_type_period ON payroll(company_id, payroll_type, period);

-- Leave Requests
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    type VARCHAR(100),
    duration VARCHAR(50),
    reason TEXT,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_leave_requests_employee (employee_id)
);

-- Loans Table
CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Approved', 'Rejected', 'Paid') DEFAULT 'Pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_loans_employee (employee_id)
);

-- Resignations Table
CREATE TABLE IF NOT EXISTS resignations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    reason TEXT,
    effective_date DATE,
    status ENUM('Pending', 'Approved', 'Processing', 'Completed', 'Declined') DEFAULT 'Pending',
    declined_by INT NULL,
    decline_reason TEXT NULL,
    declined_at DATETIME NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_resignations_employee (employee_id)
);

-- Deductions Table
CREATE TABLE IF NOT EXISTS deductions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('percentage', 'fixed') NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    is_government BOOLEAN DEFAULT false,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Allowance Categories Table
CREATE TABLE IF NOT EXISTS allowance_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('Fixed', 'Percentage') NOT NULL,
    rate DECIMAL(10, 2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Employee Allowances Table
CREATE TABLE IF NOT EXISTS employee_allowances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    category_id INT NOT NULL,
    override_amount DECIMAL(10, 2),
    effective_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES allowance_categories(id) ON DELETE CASCADE,
    INDEX idx_employee_allowances_employee (employee_id)
);

-- Employee Deductions Table
CREATE TABLE IF NOT EXISTS employee_deductions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    deduction_id INT NOT NULL,
    override_amount DECIMAL(10, 2),
    effective_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (deduction_id) REFERENCES deductions(id) ON DELETE CASCADE,
    INDEX idx_employee_deductions_employee (employee_id)
);

-- Subjects Master Table
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    code VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL,
    units INT DEFAULT 3,
    hours INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Subject Loads Table (Assigned to Faculty)
CREATE TABLE IF NOT EXISTS subject_loads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    faculty_id INT NOT NULL,
    code VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL,
    units INT DEFAULT 3,
    hours INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (faculty_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_subject_loads_faculty (faculty_id)
);

-- ============================================================================
-- MIGRATION 001: Security Improvements
-- ============================================================================

-- Password resets table for forgot password functionality
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

-- Login attempts table for rate limiting
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

-- ============================================================================
-- MIGRATION 002: Audit Trail and Security Enhancements
-- ============================================================================

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

-- Session management table
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

-- Migration tracking table
CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- MIGRATION 003: Profile Picture Support
-- ============================================================================
-- Already included in base employees table (profile_picture column)

-- ============================================================================
-- MIGRATION 004: ALM Features v2.4
-- ============================================================================
-- Already included in base schema:
-- - faculty_level column in employees table
-- - hire_date column in employees table
-- - reinstated_at and reinstated_by columns in employees table
-- - declined_by, decline_reason, declined_at in resignations table
-- - Updated resignations status ENUM to include 'Declined'
-- - Added 'Approved' status to resignations

-- ============================================================================
-- IMPORTANT NOTES
-- ============================================================================
-- 1. Demo accounts have been removed for security.
-- 2. Use setup-db.php or secure-setup.php to create initial admin account.
-- 3. Never use default passwords in production.
-- 4. This file includes all migrations - no need to run individual migration files.
-- 5. Version: 2.4.0 - Build9
-- ============================================================================
