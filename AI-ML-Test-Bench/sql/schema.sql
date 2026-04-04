-- Biometric Attendance & Payroll System - Database Schema
-- Multi-tenant Architecture

CREATE DATABASE IF NOT EXISTS alm_biometrics;
USE alm_biometrics;

-- Companies Table
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    admin_email VARCHAR(255) UNIQUE NOT NULL,
    work_start TIME DEFAULT '08:00:00',
    work_end TIME DEFAULT '17:00:00',
    lunch_start TIME DEFAULT '12:00:00',
    lunch_end TIME DEFAULT '13:00:00',
    lunch_out_start TIME DEFAULT '11:30:00',
    lunch_out_end TIME DEFAULT '12:30:00',
    lunch_in_start TIME DEFAULT '12:30:00',
    lunch_in_end TIME DEFAULT '13:30:00',
    ot_percentage INT DEFAULT 25,
    deduction_per_sec DECIMAL(10, 4) DEFAULT 0.0083,
    deduction_per_min DECIMAL(10, 2) DEFAULT 0.50,
    deduction_per_hour DECIMAL(10, 2) DEFAULT 30.00,
    biometric_match_threshold DECIMAL(10, 2) DEFAULT 0.60,
    biometric_duplicate_threshold DECIMAL(10, 2) DEFAULT 0.38,
    biometric_ambiguity_ratio DECIMAL(10, 2) DEFAULT 1.05,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users Table (Roles: HR, Payroll, Employee)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('HR', 'Admin', 'Payroll', 'Payroll Officer', 'Employee') DEFAULT 'Employee',
    email VARCHAR(255) NOT NULL,
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
    basic_salary DECIMAL(10, 2),
    status ENUM('Active', 'Inactive', 'On Leave', 'Probationary', 'Contractual', 'Resigned') DEFAULT 'Active',
    email VARCHAR(255),
    sss VARCHAR(50),
    tin VARCHAR(50),
    philhealth VARCHAR(50),
    pagibig VARCHAR(50),
    leave_balance INT DEFAULT 15,
    face_descriptor JSON, -- Serialized 128-float array
    user_id INT, -- Link to users table for ESS
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(company_id, employee_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
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
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Approved', 'Rejected', 'Paid') DEFAULT 'Pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS resignations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    reason TEXT,
    effective_date DATE,
    status ENUM('Pending', 'Processing', 'Completed') DEFAULT 'Pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
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
    FOREIGN KEY (category_id) REFERENCES allowance_categories(id) ON DELETE CASCADE
);

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
    FOREIGN KEY (deduction_id) REFERENCES deductions(id) ON DELETE CASCADE
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
    FOREIGN KEY (faculty_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Insert Demo Company
INSERT IGNORE INTO companies (id, name, admin_email) VALUES (1, 'ALM Tech Solutions', 'hr@almtech.com');

INSERT IGNORE INTO users (company_id, username, password, role, email) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'admin@almtech.com'),
(1, 'hr_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HR', 'hr@almtech.com'),
(1, 'payroll_officer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Payroll Officer', 'payroll@almtech.com');
