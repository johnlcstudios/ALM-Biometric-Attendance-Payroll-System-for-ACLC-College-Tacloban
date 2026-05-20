<?php
/**
 * Database Update Script
 * This file syncs your local MySQL database with the latest system requirements.
 */

require_once 'db.php';
require_once 'notifications.php';

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
    echo "<!DOCTYPE html><html><head><title>Database Update</title><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><style>body{font-family:sans-serif;padding:20px;line-height:1.6;background:#f4f7f6;} .container{background:white;padding:20px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);max-width:800px;margin:0 auto;} h2{color:#1e0178;border-bottom:2px solid #1e0178;padding-bottom:10px;} pre{background:#2d2d2d;color:#ccc;padding:15px;border-radius:5px;overflow-x:auto;font-size:14px;}</style></head><body><div class='container'>";
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

    // 1.1 Check for 'faculty_level' column in 'employees' table
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'faculty_level'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'faculty_level' column to 'employees' table... ";
        $pdo->exec("ALTER TABLE employees ADD COLUMN faculty_level ENUM('SHS', 'College', 'Both', '') DEFAULT '' AFTER position");
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'faculty_level' column already exists in 'employees' table.\n";
    }

    // 1.2 Check for 'hire_date' column in 'employees' table
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'hire_date'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'hire_date' column to 'employees' table... ";
        $pdo->exec("ALTER TABLE employees ADD COLUMN hire_date DATE AFTER faculty_level");
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'hire_date' column already exists in 'employees' table.\n";
    }

    // 1.3 Check for 'contact_no' column in 'employees' table
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'contact_no'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'contact_no' column to 'employees' table... ";
        $pdo->exec("ALTER TABLE employees ADD COLUMN contact_no VARCHAR(20) AFTER email");
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'contact_no' column already exists in 'employees' table.\n";
    }

    // 1.4 Check for 'gender' column in 'employees' table
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'gender'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'gender' column to 'employees' table... ";
        $pdo->exec("ALTER TABLE employees ADD COLUMN gender ENUM('Male', 'Female', 'Other') DEFAULT 'Male' AFTER contact_no");
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'gender' column already exists in 'employees' table.\n";
    }

    // 1.5 Check for 'profile_picture' column in 'employees' table
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'profile_picture'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'profile_picture' column to 'employees' table... ";
        $pdo->exec("ALTER TABLE employees ADD COLUMN profile_picture VARCHAR(255) AFTER gender");
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'profile_picture' column already exists in 'employees' table.\n";
    }

    // 1.6 Check for 'reinstated_at' column in 'employees' table
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'reinstated_at'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'reinstated_at' column to 'employees' table... ";
        $pdo->exec("ALTER TABLE employees ADD COLUMN reinstated_at DATETIME NULL AFTER hire_date");
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'reinstated_at' column already exists in 'employees' table.\n";
    }

    // 1.7 Check for 'reinstated_by' column in 'employees' table
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'reinstated_by'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'reinstated_by' column to 'employees' table... ";
        $pdo->exec("ALTER TABLE employees ADD COLUMN reinstated_by INT NULL AFTER reinstated_at");
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'reinstated_by' column already exists in 'employees' table.\n";
    }

    // 1.8 Check for 'is_active' column in 'users' table
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'is_active' column to 'users' table... ";
        $pdo->exec("ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER email");
        if (!$silent) echo "DONE\n";
        
        // Set all existing users as active
        if (!$silent) echo "Setting existing users as active... ";
        $pdo->exec("UPDATE users SET is_active = TRUE WHERE is_active IS NULL");
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'is_active' column already exists in 'users' table.\n";
    }

    // 1.9 Update status ENUM in 'employees' table
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

    // 20. Add 'work_position' column to 'employees' table
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'work_position'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'work_position' column to 'employees' table... ";
        $pdo->exec("ALTER TABLE employees ADD COLUMN work_position VARCHAR(100) DEFAULT NULL AFTER position");
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'work_position' column already exists in 'employees' table.\n";
    }

    // 21. Add 'work_status' column to 'employees' table
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'work_status'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Adding 'work_status' column to 'employees' table... ";
        $pdo->exec("ALTER TABLE employees ADD COLUMN work_status VARCHAR(50) DEFAULT NULL AFTER status");
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'work_status' column already exists in 'employees' table.\n";
    }

    // 22. Create 'subject_schedules' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'subject_schedules'");
    if (!$stmt->fetch()) {
        if (!$silent) echo "Creating 'subject_schedules' table... ";
        $sql = "CREATE TABLE subject_schedules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            subject_load_id INT NOT NULL,
            day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
            time_start TIME NOT NULL,
            time_end TIME NOT NULL,
            room VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_load_id) REFERENCES subject_loads(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        if (!$silent) echo "DONE\n";
    } else {
        if (!$silent) echo "'subject_schedules' table already exists.\n";
    }

    if (!$silent) {
        echo "\n<b>Database update completed successfully!</b>";
        echo "\n<a href='index.php'>Return to Dashboard</a>";
        echo "</pre></div>";
        showNotification("Database update completed successfully!", "success", "index.php");
        echo "</body></html>";
    }

} catch (Exception $e) {
    if (!$silent) {
        echo "\n<span style='color:red;'>ERROR: " . $e->getMessage() . "</span>";
        echo "</pre></div>";
        showNotification("Database update failed: " . $e->getMessage(), "error");
        echo "</body></html>";
    }
}
?>
