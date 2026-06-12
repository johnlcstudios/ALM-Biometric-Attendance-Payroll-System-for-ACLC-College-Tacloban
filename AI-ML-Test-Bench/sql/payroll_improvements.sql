-- ============================================================
-- Payroll Improvements Migration
-- Adds new tables and columns for enhanced payroll processing
-- ============================================================

-- ============================================================
-- 1. holidays table
-- Stores company holidays with pay rate configuration
-- ============================================================
CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    type ENUM('Regular', 'Special', 'Non-Working') DEFAULT 'Regular',
    pay_rate DECIMAL(5,2) DEFAULT 100.00,
    is_recurring TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_holiday_per_company (company_id, date, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO holidays (company_id, name, date, type, pay_rate) VALUES
(1, 'New Year''s Day', '2026-01-01', 'Regular', 200.00),
(1, 'People Power Revolution', '2026-02-25', 'Special', 130.00),
(1, 'Araw ng Kagitingan', '2026-04-09', 'Regular', 200.00),
(1, 'Labor Day', '2026-05-01', 'Regular', 200.00),
(1, 'Independence Day', '2026-06-12', 'Regular', 200.00),
(1, 'Ninoy Aquino Day', '2026-08-21', 'Special', 130.00),
(1, 'Buwan ng Wika', '2026-08-19', 'Special', 130.00),
(1, 'All Saints'' Day', '2026-11-01', 'Special', 130.00),
(1, 'Bonifacio Day', '2026-11-30', 'Regular', 200.00),
(1, 'Christmas Day', '2026-12-25', 'Regular', 200.00),
(1, 'Rizal Day', '2026-12-30', 'Regular', '200.00'),
(1, 'Last Day of the Year', '2026-12-31', 'Special', 130.00);

-- ============================================================
-- 2. payroll_batches table
-- Groups payroll records into processing batches
-- ============================================================
CREATE TABLE IF NOT EXISTS payroll_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    batch_name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    payroll_type ENUM('General', 'Faculty', 'Utility', 'All') DEFAULT 'All',
    total_employees INT DEFAULT 0,
    total_gross DECIMAL(12,2) DEFAULT 0.00,
    total_deductions DECIMAL(12,2) DEFAULT 0.00,
    total_net DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('Draft', 'Processed', 'Approved', 'Paid', 'Reversed') DEFAULT 'Draft',
    processed_by INT DEFAULT NULL,
    processed_at TIMESTAMP NULL,
    approved_by INT DEFAULT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. Alter payroll table
-- Adds batch tracking, approval workflow, and detailed pay columns
-- ============================================================
ALTER TABLE payroll ADD COLUMN batch_id INT DEFAULT NULL AFTER company_id;
ALTER TABLE payroll ADD COLUMN approval_status ENUM('Pending', 'Approved', 'Rejected', 'Paid') DEFAULT 'Pending' AFTER status;
ALTER TABLE payroll ADD COLUMN approved_by INT DEFAULT NULL AFTER approval_status;
ALTER TABLE payroll ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by;
ALTER TABLE payroll ADD COLUMN processed_by INT DEFAULT NULL AFTER approved_at;
ALTER TABLE payroll ADD COLUMN processed_at TIMESTAMP NULL AFTER processed_by;
ALTER TABLE payroll ADD COLUMN gross_pay DECIMAL(10,2) DEFAULT NULL AFTER basic_pay;
ALTER TABLE payroll ADD COLUMN sss_employee DECIMAL(10,2) DEFAULT NULL AFTER gross_pay;
ALTER TABLE payroll ADD COLUMN sss_employer DECIMAL(10,2) DEFAULT NULL AFTER sss_employee;
ALTER TABLE payroll ADD COLUMN philhealth_employee DECIMAL(10,2) DEFAULT NULL AFTER sss_employer;
ALTER TABLE payroll ADD COLUMN philhealth_employer DECIMAL(10,2) DEFAULT NULL AFTER philhealth_employee;
ALTER TABLE payroll ADD COLUMN pagibig_employee DECIMAL(10,2) DEFAULT NULL AFTER philhealth_employer;
ALTER TABLE payroll ADD COLUMN pagibig_employer DECIMAL(10,2) DEFAULT NULL AFTER pagibig_employee;
ALTER TABLE payroll ADD COLUMN bir_tax DECIMAL(10,2) DEFAULT NULL AFTER pagibig_employer;
ALTER TABLE payroll ADD COLUMN overtime_pay DECIMAL(10,2) DEFAULT 0.00 AFTER bir_tax;
ALTER TABLE payroll ADD COLUMN holiday_pay DECIMAL(10,2) DEFAULT 0.00 AFTER overtime_pay;
ALTER TABLE payroll ADD COLUMN night_diff_pay DECIMAL(10,2) DEFAULT 0.00 AFTER holiday_pay;
ALTER TABLE payroll ADD FOREIGN KEY (batch_id) REFERENCES payroll_batches(id) ON DELETE SET NULL;
ALTER TABLE payroll ADD FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE payroll ADD FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL;

-- ============================================================
-- 4. payroll_audit_log table
-- Tracks all payroll changes for reversal and audit purposes
-- ============================================================
CREATE TABLE IF NOT EXISTS payroll_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    payroll_id INT NOT NULL,
    action ENUM('Created', 'Modified', 'Approved', 'Rejected', 'Paid', 'Reversed') NOT NULL,
    old_values JSON DEFAULT NULL,
    new_values JSON DEFAULT NULL,
    performed_by INT NOT NULL,
    reason TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (payroll_id) REFERENCES payroll(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. loan_installments table
-- Tracks loan repayment installments for employees
-- ============================================================
CREATE TABLE IF NOT EXISTS loan_installments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    loan_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    installment_amount DECIMAL(10,2) NOT NULL,
    total_installments INT NOT NULL,
    paid_installments INT DEFAULT 0,
    balance DECIMAL(10,2) NOT NULL,
    status ENUM('Active', 'Completed', 'Defaulted') DEFAULT 'Active',
    start_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. night_diff_records table
-- Tracks night differential pay records for employees
-- ============================================================
CREATE TABLE IF NOT EXISTS night_diff_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    employee_id INT NOT NULL,
    attendance_id INT NOT NULL,
    log_date DATE NOT NULL,
    nd_start TIME NOT NULL,
    nd_end TIME NOT NULL,
    nd_hours DECIMAL(5,2) NOT NULL,
    rate_per_hour DECIMAL(10,2) NOT NULL,
    nd_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
