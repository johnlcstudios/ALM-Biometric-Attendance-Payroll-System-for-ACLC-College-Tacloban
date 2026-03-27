-- ACLC College Tacloban: Biometric Attendance & Payroll System
-- Database Schema for MySQL/PostgreSQL

-- 1. Employees Table
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    suffix VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    civil_status ENUM('Single', 'Married', 'Widowed', 'Separated'),
    contact_number VARCHAR(20),
    email VARCHAR(150) UNIQUE NOT NULL,
    home_address TEXT,
    employment_type ENUM('Full-Time Faculty', 'Part-Time Faculty', 'Admin Staff', 'Utility Staff'),
    department VARCHAR(100),
    position VARCHAR(100),
    basic_salary DECIMAL(10, 2) DEFAULT 0.00,
    date_hired DATE,
    salary_grade VARCHAR(20),
    employment_status ENUM('Permanent', 'Probationary', 'Contractual', 'Casual') DEFAULT 'Probationary',
    sss_no VARCHAR(50),
    philhealth_no VARCHAR(50),
    pagibig_no VARCHAR(50),
    tin_no VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. FaceID Biometric Data
CREATE TABLE face_biometrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    face_descriptor JSON NOT NULL, -- Stores the array of 128 float values
    captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- 3. Attendance Logs
CREATE TABLE attendance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    log_type ENUM('In', 'Out') NOT NULL,
    log_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verification_method ENUM('FaceID', 'RFID', 'Manual') DEFAULT 'FaceID',
    device_id VARCHAR(100), -- Identifier for the kiosk
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- 4. Allowance Types
CREATE TABLE allowance_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('Cash', 'Non-Cash', 'Annual', 'Computed', 'Variable'),
    default_amount DECIMAL(10, 2) DEFAULT 0.00,
    is_taxable BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE
);

-- 5. Deduction Types
CREATE TABLE deduction_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('Government', 'Company', 'Loan', 'Computed'),
    default_rate_or_amount VARCHAR(50), -- e.g., '4.5%' or '100.00'
    is_active BOOLEAN DEFAULT TRUE
);

-- 6. Employee Specific Allowances
CREATE TABLE employee_allowances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    allowance_id INT NOT NULL,
    custom_amount DECIMAL(10, 2), -- Overrides default if set
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (allowance_id) REFERENCES allowance_types(id) ON DELETE CASCADE
);

-- 7. Employee Specific Deductions
CREATE TABLE employee_deductions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    deduction_id INT NOT NULL,
    custom_amount DECIMAL(10, 2), -- Overrides default if set
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (deduction_id) REFERENCES deduction_types(id) ON DELETE CASCADE
);

-- 8. Faculty Subject Load
CREATE TABLE faculty_subject_loads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    subject_code VARCHAR(50) NOT NULL,
    subject_description VARCHAR(255),
    section VARCHAR(50),
    units INT DEFAULT 3,
    schedule VARCHAR(100),
    academic_year VARCHAR(20),
    semester ENUM('1st Semester', '2nd Semester', 'Summer'),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- 9. Payroll Runs (Batch Processing History)
CREATE TABLE payroll_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_month DATE NOT NULL, -- Store as first of month, e.g., '2026-03-01'
    run_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_employees INT,
    total_gross DECIMAL(12, 2),
    total_deductions DECIMAL(12, 2),
    total_net DECIMAL(12, 2),
    status ENUM('Pending', 'Processing', 'Completed', 'Failed') DEFAULT 'Pending'
);

-- 10. Individual Payslips
CREATE TABLE payslips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_run_id INT NOT NULL,
    employee_id INT NOT NULL,
    basic_salary DECIMAL(10, 2),
    gross_pay DECIMAL(10, 2),
    total_deductions DECIMAL(10, 2),
    net_pay DECIMAL(10, 2),
    overtime_pay DECIMAL(10, 2) DEFAULT 0.00,
    overload_pay DECIMAL(10, 2) DEFAULT 0.00,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payroll_run_id) REFERENCES payroll_runs(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- 11. Leave Requests
CREATE TABLE leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type ENUM('Sick', 'Vacation', 'Emergency', 'Maternity', 'Paternity', 'Other'),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    approved_by INT,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES employees(id) ON DELETE SET NULL
);

-- Indices for faster lookup
CREATE INDEX idx_emp_id ON employees(employee_id);
CREATE INDEX idx_att_date ON attendance_logs(log_timestamp);
CREATE INDEX idx_payroll_period ON payroll_runs(period_month);

-- Sample Data
INSERT INTO employees (employee_id, first_name, last_name, email, employment_type, department, position, basic_salary, date_hired, employment_status)
VALUES 
('EMP001', 'Maria', 'Santos', 'm.santos@aclc.edu', 'Full-Time Faculty', 'Faculty', 'Professor', 35000.00, '2023-01-15', 'Permanent'),
('EMP002', 'Juan', 'dela Cruz', 'j.delacruz@aclc.edu', 'Admin Staff', 'Admin', 'Dean', 55000.00, '2020-06-10', 'Permanent');

INSERT INTO allowance_types (name, type, default_amount, is_taxable)
VALUES 
('Rice Allowance', 'Non-Cash', 2000.00, FALSE),
('Transportation', 'Cash', 1500.00, FALSE);

INSERT INTO deduction_types (name, type, default_rate_or_amount)
VALUES 
('SSS Contribution', 'Government', '4.5%'),
('PhilHealth', 'Government', '2.0%'),
('Pag-IBIG Fund', 'Government', '100.00');
