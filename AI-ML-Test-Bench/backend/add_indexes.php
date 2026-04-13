<?php
// add_indexes.php - Add performance indexes to existing database
require_once 'db.php';

try {
    // Attendance indexes
    $pdo->exec("ALTER TABLE attendance ADD INDEX IF NOT EXISTS idx_attendance_company_emp_date (company_id, employee_id, log_date);");
    $pdo->exec("ALTER TABLE attendance ADD INDEX IF NOT EXISTS idx_attendance_log_date (log_date);");

    // Employees indexes
    $pdo->exec("ALTER TABLE employees ADD INDEX IF NOT EXISTS idx_employees_company_status (company_id, status);");

    // Payroll indexes
    $pdo->exec("ALTER TABLE payroll ADD INDEX IF NOT EXISTS idx_payroll_company_type_period (company_id, payroll_type, period);");

    echo "Indexes added successfully!\n";
} catch (Exception $e) {
    echo "Error adding indexes: " . $e->getMessage() . "\n";
}
?>