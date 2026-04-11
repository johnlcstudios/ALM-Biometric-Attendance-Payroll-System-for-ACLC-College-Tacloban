<?php
/**
 * Database Update Script
 * This file syncs your local MySQL database with the latest system requirements.
 */

require_once 'db.php';

if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow Admin or HR to run this script
if (php_sapi_name() !== 'cli' && !defined('INTERNAL_UPDATE')) {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'HR'])) {
        http_response_code(403);
        die("<h2>Access Denied</h2><p>You must be an Admin or HR to run this update script.</p>");
    }
}

$silent = defined('INTERNAL_UPDATE') && INTERNAL_UPDATE === true;

if (!$silent) {
    echo "<h2>Biometric Attendance & Payroll System - Database Updater</h2>";
    echo "<pre>";
}

try {
    // 1. Check for 'dob' column in 'employees' table
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'dob'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'dob' column to 'employees' table... ";
        $pdo->exec("ALTER TABLE employees ADD COLUMN dob DATE AFTER full_name");
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'dob' column already exists in 'employees' table.\n";
    }

    // 1.1 Update status ENUM in 'employees' table
    if (!$silent) echo "Updating status ENUM in 'employees' table... ";
    $pdo->exec("ALTER TABLE employees MODIFY COLUMN status ENUM('Active', 'Inactive', 'On Leave', 'Probationary', 'Contractual', 'Resigned') DEFAULT 'Active'");
    if (!$silent) echo "DONE\n";

    // 4. Check for 'loans' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'loans'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Creating 'loans' table... ";
        $sql = "CREATE TABLE loans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            employee_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            reason TEXT,
            status ENUM('Pending', 'Approved', 'Rejected', 'Paid') DEFAULT 'Pending',
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'loans' table already exists.\n";
    }

    // 5. Check for 'resignations' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'resignations'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Creating 'resignations' table... ";
        $sql = "CREATE TABLE resignations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            employee_id INT NOT NULL,
            reason TEXT,
            effective_date DATE,
            status ENUM('Pending', 'Processing', 'Completed') DEFAULT 'Pending',
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'resignations' table already exists.\n";
    }

    // 2. Check for 'deductions' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'deductions'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Creating 'deductions' table... ";
        $sql = "CREATE TABLE deductions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            type ENUM('percentage', 'fixed') NOT NULL,
            value DECIMAL(10, 2) NOT NULL,
            is_active BOOLEAN DEFAULT true,
            is_government BOOLEAN DEFAULT false,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'deductions' table already exists.\n";
    }

    // 3. Optional: Add default deductions for existing companies if table was just created
    $stmt = $pdo->query("SELECT COUNT(*) FROM deductions");
    if ($stmt->fetchColumn() == 0) {
        if (!$silent) echo "Inserting default deductions... ";
        $stmt_companies = $pdo->query("SELECT id FROM companies");
        $companies = $stmt_companies->fetchAll();
        
        $insert_stmt = $pdo->prepare("INSERT INTO deductions (company_id, name, type, value, is_active, is_government) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($companies as $company) {
            $cid = $company['id'];
            $insert_stmt->execute([$cid, 'SSS', 'percentage', 4.5, 1, 1]);
            $insert_stmt->execute([$cid, 'PhilHealth', 'percentage', 2.0, 1, 1]);
            $insert_stmt->execute([$cid, 'Pag-IBIG', 'fixed', 100.00, 1, 1]);
            $insert_stmt->execute([$cid, 'Health Insurance', 'fixed', 500.00, 1, 0]);
        }
        if (!$silent) echo "DONE\n";
    }

    // 6. Check for 'lunch_start', 'lunch_end' columns in 'companies' table
    $stmt = $pdo->query("SHOW COLUMNS FROM companies LIKE 'lunch_start'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'lunch_start' and 'lunch_end' columns to 'companies' table... ";
        $pdo->exec("ALTER TABLE companies ADD COLUMN lunch_start TIME DEFAULT '12:00:00', ADD COLUMN lunch_end TIME DEFAULT '13:00:00'");
        if (!$silent) echo "DONE\n";
    }

    // 7. Check for lunch out and lunch in ranges in 'companies' table
    $stmt = $pdo->query("SHOW COLUMNS FROM companies LIKE 'lunch_out_start'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding lunch out and lunch in range columns to 'companies' table... ";
        $pdo->exec("ALTER TABLE companies 
            ADD COLUMN lunch_out_start TIME DEFAULT '11:30:00', 
            ADD COLUMN lunch_out_end TIME DEFAULT '12:30:00', 
            ADD COLUMN lunch_in_start TIME DEFAULT '12:30:00', 
            ADD COLUMN lunch_in_end TIME DEFAULT '13:30:00'");
        if (!$silent) echo "DONE\n";
    }

    // 10. Add OT and dynamic deduction columns to 'companies'
    $stmt = $pdo->query("SHOW COLUMNS FROM companies LIKE 'ot_percentage'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding OT and deduction config columns to 'companies'... ";
        $pdo->exec("ALTER TABLE companies 
            ADD COLUMN ot_percentage INT DEFAULT 25,
            ADD COLUMN deduction_per_sec DECIMAL(10, 4) DEFAULT 0.0083,
            ADD COLUMN deduction_per_min DECIMAL(10, 2) DEFAULT 0.50,
            ADD COLUMN deduction_per_hour DECIMAL(10, 2) DEFAULT 30.00");
        if (!$silent) echo "DONE\n";
    }

    // 11. Add 'leave_balance' to 'employees'
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'leave_balance'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'leave_balance' to 'employees'... ";
        $pdo->exec("ALTER TABLE employees ADD COLUMN leave_balance INT DEFAULT 15");
        if (!$silent) echo "DONE\n";
    }

    // 12. Add 'late_minutes' to 'attendance'
    $stmt = $pdo->query("SHOW COLUMNS FROM attendance LIKE 'late_minutes'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'late_minutes' to 'attendance' table... ";
        $pdo->exec("ALTER TABLE attendance ADD COLUMN late_minutes INT DEFAULT 0");
        if (!$silent) echo "DONE\n";
    }

    // 12.1 Add payroll breakdown support
    $stmt = $pdo->query("SHOW COLUMNS FROM payroll LIKE 'payroll_type'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'payroll_type' to 'payroll' table... ";
        $pdo->exec("ALTER TABLE payroll ADD COLUMN payroll_type ENUM('General','Faculty','Utility') DEFAULT 'General' AFTER employee_id");
        if (!$silent) echo "DONE\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM payroll LIKE 'breakdown'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'breakdown' to 'payroll' table... ";
        $pdo->exec("ALTER TABLE payroll ADD COLUMN breakdown JSON NULL AFTER net_pay");
        if (!$silent) echo "DONE\n";
    }

    $stmt = $pdo->query("SHOW INDEX FROM payroll WHERE Key_name = 'uq_payroll_company_emp_period'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding unique key uq_payroll_company_emp_period to 'payroll'... ";
        $pdo->exec("ALTER TABLE payroll ADD UNIQUE KEY uq_payroll_company_emp_period (company_id, employee_id, period)");
        if (!$silent) echo "DONE\n";
    }

    // 13. Create 'allowance_categories' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'allowance_categories'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Creating 'allowance_categories' table... ";
        $sql = "CREATE TABLE allowance_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            type ENUM('Fixed', 'Percentage') NOT NULL,
            rate DECIMAL(10, 2) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        if (!$silent) echo "DONE\n";
    }

    // 14. Create 'employee_allowances' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'employee_allowances'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Creating 'employee_allowances' table... ";
        $sql = "CREATE TABLE employee_allowances (
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
        )";
        $pdo->exec($sql);
        if (!$silent) echo "DONE\n";
    }

    // 15. Create 'employee_deductions' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'employee_deductions'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Creating 'employee_deductions' table... ";
        $sql = "CREATE TABLE employee_deductions (
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
        )";
        $pdo->exec($sql);
        if (!$silent) echo "DONE\n";
    }

    // 16. Expand 'users' table 'role' enum
    if (!$silent) echo "Updating 'users' role enum... ";
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('HR', 'Admin', 'Payroll', 'Payroll Officer', 'Employee') DEFAULT 'Employee'");
    if (!$silent) echo "DONE\n";

    // 17. Sync Payroll Officer roles
    if (!$silent) echo "Syncing Payroll Officer roles... ";
    $pdo->exec("UPDATE users u JOIN employees e ON u.id = e.user_id SET u.role = 'Payroll Officer' WHERE e.position = 'Payroll Officer'");
    if (!$silent) echo "DONE\n";

    // 18. Relax Biometric Thresholds for better recognition
    if (!$silent) echo "Relaxing Biometric Thresholds for better recognition... ";
    $pdo->exec("UPDATE companies SET biometric_match_threshold = 0.70, biometric_ambiguity_ratio = 1.25 WHERE biometric_match_threshold = 0.60");
    if (!$silent) echo "DONE\n";

    // 19. Add buffer columns to 'companies' table
    $stmt = $pdo->query("SHOW COLUMNS FROM companies LIKE 'lunch_buffer'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'lunch_buffer' and 'checkout_buffer' to 'companies' table... ";
        $pdo->exec("ALTER TABLE companies 
            ADD COLUMN lunch_buffer INT DEFAULT 30, 
            ADD COLUMN checkout_buffer INT DEFAULT 60");
        if (!$silent) echo "DONE\n";
    }

    // 8. Create 'subjects' master table
    $stmt = $pdo->query("SHOW TABLES LIKE 'subjects'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Creating 'subjects' table... ";
        $sql = "CREATE TABLE subjects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            code VARCHAR(50) NOT NULL,
            description VARCHAR(255) NOT NULL,
            units INT DEFAULT 3,
            hours INT DEFAULT 3,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'subjects' table already exists.\n";
    }

    // 9. Create 'subject_loads' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'subject_loads'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Creating 'subject_loads' table... ";
        $sql = "CREATE TABLE subject_loads (
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
        )";
        $pdo->exec($sql);
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'subject_loads' table already exists.\n";
    }

    // 20. Create 'audit_logs' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'audit_logs'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Creating 'audit_logs' table... ";
        $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
            id          INT           AUTO_INCREMENT PRIMARY KEY,
            company_id  INT           NOT NULL,
            user_id     INT           DEFAULT NULL,
            username    VARCHAR(100)  NOT NULL DEFAULT 'system',
            action      VARCHAR(100)  NOT NULL,
            description TEXT          NOT NULL,
            target_id   INT           DEFAULT NULL,
            ip_address  VARCHAR(45)   DEFAULT NULL,
            created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id)    REFERENCES users(id)     ON DELETE SET NULL
        )");
        $pdo->exec("CREATE INDEX idx_audit_logs_company    ON audit_logs(company_id)");
        $pdo->exec("CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at)");
        $pdo->exec("CREATE INDEX idx_audit_logs_action     ON audit_logs(action)");
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'audit_logs' table already exists.\n";
    }
    
    if (!$silent) echo "\n<b>Database update completed successfully!</b>";
    if (!$silent) echo "\n<a href='index.php'>Return to Dashboard</a>";

} catch (Exception $e) {
    if (!$silent) echo "\n<span style='color:red;'>ERROR: " . $e->getMessage() . "</span>";
}

if (!$silent) echo "</pre>";
?>
