#!/usr/bin/env php
<?php
// Data Retention Policy Enforcement Script
// Archives or deletes old data based on retention policies
// Run monthly: 0 3 1 * * /usr/bin/php /path/to/data_retention.php

// Load environment
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

require_once __DIR__ . '/db.php';

$attendanceRetentionYears = (int)(getenv('ATTENDANCE_RETENTION_YEARS') ?: 2);
$payrollRetentionYears = (int)(getenv('PAYROLL_RETENTION_YEARS') ?: 5);

echo "Data Retention Policy Enforcement\n";
echo "==================================\n";
echo "Attendance retention: $attendanceRetentionYears years\n";
echo "Payroll retention: $payrollRetentionYears years\n\n";

try {
    $pdo->beginTransaction();
    
    // 1. Archive old attendance records
    $attendanceCutoff = date('Y-m-d', strtotime("-$attendanceRetentionYears years"));
    echo "Archiving attendance records before $attendanceCutoff...\n";
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM attendance 
        WHERE log_date < ?
    ");
    $stmt->execute([$attendanceCutoff]);
    $oldAttendanceCount = $stmt->fetchColumn();
    
    if ($oldAttendanceCount > 0) {
        // Create archive table if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS attendance_archive LIKE attendance
        ");
        
        // Move old records to archive
        $stmt = $pdo->prepare("
            INSERT INTO attendance_archive 
            SELECT * FROM attendance 
            WHERE log_date < ?
        ");
        $stmt->execute([$attendanceCutoff]);
        
        // Delete from main table
        $stmt = $pdo->prepare("
            DELETE FROM attendance 
            WHERE log_date < ?
        ");
        $stmt->execute([$attendanceCutoff]);
        
        echo "Archived $oldAttendanceCount attendance record(s)\n";
    } else {
        echo "No old attendance records to archive\n";
    }
    
    // 2. Archive old payroll records
    $payrollCutoff = date('Y-m-d', strtotime("-$payrollRetentionYears years"));
    echo "\nArchiving payroll records before $payrollCutoff...\n";
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM payroll 
        WHERE created_at < ?
    ");
    $stmt->execute([$payrollCutoff]);
    $oldPayrollCount = $stmt->fetchColumn();
    
    if ($oldPayrollCount > 0) {
        // Create archive table if not exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS payroll_archive LIKE payroll
        ");
        
        // Move old records to archive
        $stmt = $pdo->prepare("
            INSERT INTO payroll_archive 
            SELECT * FROM payroll 
            WHERE created_at < ?
        ");
        $stmt->execute([$payrollCutoff]);
        
        // Delete from main table
        $stmt = $pdo->prepare("
            DELETE FROM payroll 
            WHERE created_at < ?
        ");
        $stmt->execute([$payrollCutoff]);
        
        echo "Archived $oldPayrollCount payroll record(s)\n";
    } else {
        echo "No old payroll records to archive\n";
    }
    
    // 3. Clean up expired password reset tokens
    echo "\nCleaning expired password reset tokens...\n";
    $stmt = $pdo->exec("
        DELETE FROM password_resets 
        WHERE expires_at < NOW() OR used = TRUE
    ");
    echo "Cleaned $stmt expired token(s)\n";
    
    // 4. Clean up old login attempts
    echo "\nCleaning old login attempts...\n";
    $stmt = $pdo->exec("
        DELETE FROM login_attempts 
        WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    echo "Cleaned $stmt old login attempt(s)\n";
    
    // 5. Clean up inactive sessions
    echo "\nCleaning inactive sessions...\n";
    $stmt = $pdo->exec("
        DELETE FROM user_sessions 
        WHERE last_active < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    echo "Cleaned $stmt inactive session(s)\n";
    
    $pdo->commit();
    
    echo "\n==================================\n";
    echo "Data retention enforcement completed successfully!\n";
    
    // Log the operation
    $logEntry = date('Y-m-d H:i:s') . " - Data retention: Attendance=$oldAttendanceCount, Payroll=$oldPayrollCount\n";
    file_put_contents(__DIR__ . '/retention.log', $logEntry, FILE_APPEND);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\nERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
