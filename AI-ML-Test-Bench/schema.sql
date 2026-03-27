-- Biometric Attendance & Payroll System - Database Schema
-- Multi-tenant Architecture

CREATE DATABASE IF NOT EXISTS alm_biometrics;
USE alm_biometrics;

-- Companies Table
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    admin_email VARCHAR(255) UNIQUE NOT NULL,
    work_start TIME DEFAULT '08:00:00',
    work_end TIME DEFAULT '17:00:00',
    lunch_start TIME DEFAULT '12:00:00',
    lunch_end TIME DEFAULT '13:00:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users Table (Roles: HR, Payroll, Employee)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('HR', 'Payroll', 'Employee') NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Employees Table
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id VARCHAR(50) NOT NULL, -- e.g. EMP001
    full_name VARCHAR(255) NOT NULL,
    dob DATE,
    position VARCHAR(100),
    department VARCHAR(100),
    basic_salary DECIMAL(10, 2),
    status ENUM('Active', 'Inactive', 'On Leave') DEFAULT 'Active',
    email VARCHAR(255),
    sss VARCHAR(50),
    tin VARCHAR(50),
    philhealth VARCHAR(50),
    pagibig VARCHAR(50),
    face_descriptor JSON, -- Serialized 128-float array
    user_id INT, -- Link to users table for ESS
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(company_id, employee_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Attendance Logs
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    log_date DATE NOT NULL,
    check_in TIME,
    lunch_out TIME,
    lunch_in TIME,
    check_out TIME,
    status VARCHAR(50), -- e.g. On-Time, Late, Absent
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Payroll History
CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    period VARCHAR(50), -- e.g. 03/2026
    basic_pay DECIMAL(10, 2),
    deductions DECIMAL(10, 2),
    net_pay DECIMAL(10, 2),
    status ENUM('Pending', 'Paid') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Leave Requests
CREATE TABLE leave_requests (
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

-- Deductions Table
CREATE TABLE deductions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('percentage', 'fixed') NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    is_government BOOLEAN DEFAULT false,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Insert Demo Company
INSERT INTO companies (name, admin_email) VALUES ('ALM Tech Solutions', 'hr@almtech.com');

-- Insert Demo Users (Passwords are 'admin123')
INSERT INTO users (company_id, username, password, role, email) VALUES 
(1, 'hr_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HR', 'hr@almtech.com'),
(1, 'payroll_officer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Payroll', 'payroll@almtech.com');
