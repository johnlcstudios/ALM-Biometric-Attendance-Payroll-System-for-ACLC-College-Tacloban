<?php
/**
 * Database Update Script
 * This file syncs your local MySQL database with the latest system requirements.
 */

require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow Admin or HR to run this script
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'HR'])) {
    http_response_code(403);
    die("<h2>Access Denied</h2><p>You must be an Admin or HR to run this update script.</p>");
}

echo "<h2>Biometric Attendance & Payroll System - Database Updater</h2>";
echo "<pre>";

try {
    // 1. Check for 'dob' column in 'employees' table
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'dob'");
    if (!$stmt->fetch()) {
        echo "Adding 'dob' column to 'employees' table... ";
        $pdo->exec("ALTER TABLE employees ADD COLUMN dob DATE AFTER full_name");
        echo "DONE\n";
    } else {
        echo "'dob' column already exists in 'employees' table.\n";
    }

    // 4. Check for 'loans' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'loans'");
    if (!$stmt->fetch()) {
        echo "Creating 'loans' table... ";
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
        echo "DONE\n";
    } else {
        echo "'loans' table already exists.\n";
    }

    // 5. Check for 'resignations' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'resignations'");
    if (!$stmt->fetch()) {
        echo "Creating 'resignations' table... ";
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
        echo "DONE\n";
    } else {
        echo "'resignations' table already exists.\n";
    }

    // 2. Check for 'deductions' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'deductions'");
    if (!$stmt->fetch()) {
        echo "Creating 'deductions' table... ";
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
        echo "DONE\n";
    } else {
        echo "'deductions' table already exists.\n";
    }

    // 3. Optional: Add default deductions for existing companies if table was just created
    $stmt = $pdo->query("SELECT COUNT(*) FROM deductions");
    if ($stmt->fetchColumn() == 0) {
        echo "Inserting default deductions... ";
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
        echo "DONE\n";
    }

    // 6. Check for 'lunch_start', 'lunch_end', 'grace_period' columns in 'companies' table
    $stmt = $pdo->query("SHOW COLUMNS FROM companies LIKE 'lunch_start'");
    if (!$stmt->fetch()) {
        echo "Adding 'lunch_start', 'lunch_end', and 'grace_period' columns to 'companies' table... ";
        $pdo->exec("ALTER TABLE companies ADD COLUMN lunch_start TIME DEFAULT '12:00:00', ADD COLUMN lunch_end TIME DEFAULT '13:00:00', ADD COLUMN grace_period INT DEFAULT 15");
        echo "DONE\n";
    }

    // 7. Check for lunch out and lunch in ranges in 'companies' table
    $stmt = $pdo->query("SHOW COLUMNS FROM companies LIKE 'lunch_out_start'");
    if (!$stmt->fetch()) {
        echo "Adding lunch out and lunch in range columns to 'companies' table... ";
        $pdo->exec("ALTER TABLE companies 
            ADD COLUMN lunch_out_start TIME DEFAULT '11:30:00', 
            ADD COLUMN lunch_out_end TIME DEFAULT '12:30:00', 
            ADD COLUMN lunch_in_start TIME DEFAULT '12:30:00', 
            ADD COLUMN lunch_in_end TIME DEFAULT '13:30:00'");
        echo "DONE\n";
    }

    // 10. Add OT and dynamic deduction columns to 'companies'
    $stmt = $pdo->query("SHOW COLUMNS FROM companies LIKE 'ot_percentage'");
    if (!$stmt->fetch()) {
        echo "Adding OT and deduction config columns to 'companies'... ";
        $pdo->exec("ALTER TABLE companies 
            ADD COLUMN ot_percentage INT DEFAULT 25,
            ADD COLUMN deduction_per_sec DECIMAL(10, 4) DEFAULT 0.0083,
            ADD COLUMN deduction_per_min DECIMAL(10, 2) DEFAULT 0.50,
            ADD COLUMN deduction_per_hour DECIMAL(10, 2) DEFAULT 30.00");
        echo "DONE\n";
    }

    // 11. Add 'leave_balance' to 'employees'
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'leave_balance'");
    if (!$stmt->fetch()) {
        echo "Adding 'leave_balance' to 'employees'... ";
        $pdo->exec("ALTER TABLE employees ADD COLUMN leave_balance INT DEFAULT 15");
        echo "DONE\n";
    }

    // 8. Create 'subjects' master table
    $stmt = $pdo->query("SHOW TABLES LIKE 'subjects'");
    if (!$stmt->fetch()) {
        echo "Creating 'subjects' table... ";
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
        echo "DONE\n";
    } else {
        echo "'subjects' table already exists.\n";
    }

    // 9. Create 'subject_loads' table
    $stmt = $pdo->query("SHOW TABLES LIKE 'subject_loads'");
    if (!$stmt->fetch()) {
        echo "Creating 'subject_loads' table... ";
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
        echo "DONE\n";
    } else {
        echo "'subject_loads' table already exists.\n";
    }

    echo "\n<b>Database update completed successfully!</b>";
    echo "\n<a href='index.php'>Return to Dashboard</a>";

} catch (Exception $e) {
    echo "\n<span style='color:red;'>ERROR: " . $e->getMessage() . "</span>";
}

echo "</pre>";
?>
