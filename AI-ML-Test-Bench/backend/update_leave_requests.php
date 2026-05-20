<?php
/**
 * Migration Script: Leave Request Enhancements
 * 
 * Changes:
 * - Add filtering and tracking columns to leave_requests table
 * - Add start_date, end_date columns for clearer date tracking
 * - Add approved_by for tracking approver
 * - Add leave balance tracking
 */

require_once 'db.php';

echo "Starting Leave Request Enhancement Migration...\n";

try {
    $pdo->beginTransaction();
    
    // Step 1: Add date columns (split from duration string)
    $pdo->exec("ALTER TABLE leave_requests ADD COLUMN start_date DATE DEFAULT NULL AFTER duration");
    $pdo->exec("ALTER TABLE leave_requests ADD COLUMN end_date DATE DEFAULT NULL AFTER start_date");
    
    // Step 2: Add tracking columns
    $pdo->exec("ALTER TABLE leave_requests ADD COLUMN approved_by INT(11) DEFAULT NULL AFTER status");
    $pdo->exec("ALTER TABLE leave_requests ADD COLUMN approved_at DATETIME DEFAULT NULL AFTER approved_by");
    $pdo->exec("ALTER TABLE leave_requests ADD COLUMN reviewed_notes TEXT DEFAULT NULL AFTER approved_at");
    
    // Step 3: Add leave balance tracking
    $pdo->exec("ALTER TABLE leave_requests ADD COLUMN balance_before INT(11) DEFAULT NULL AFTER reason");
    $pdo->exec("ALTER TABLE leave_requests ADD COLUMN balance_after INT(11) DEFAULT NULL AFTER balance_before");
    
    // Step 4: Add days count column
    $pdo->exec("ALTER TABLE leave_requests ADD COLUMN days_count INT(11) DEFAULT 1 AFTER end_date");
    
    // Step 5: Migrate date data from duration field if it's in YYYY-MM-DD format
    $stmt = $pdo->query("SELECT id, duration FROM leave_requests WHERE duration LIKE '%to%'");
    $leaveRequests = $stmt->fetchAll();
    
    foreach ($leaveRequests as $leave) {
        $parts = explode(' to ', $leave['duration']);
        if (count($parts) === 2) {
            $startDate = trim($parts[0]);
            $endDate = trim($parts[1]);
            
            // Calculate days between
            $daysCount = 1;
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                $start = new DateTime($startDate);
                $end = new DateTime($endDate);
                $daysCount = $end->diff($start)->days + 1;
            }
            
            $updateStmt = $pdo->prepare("UPDATE leave_requests SET start_date = ?, end_date = ?, days_count = ? WHERE id = ?");
            $updateStmt->execute([$startDate, $endDate, $daysCount, $leave['id']]);
        }
    }
    
    // Step 6: Add index for filtering
    $pdo->exec("ALTER TABLE leave_requests ADD INDEX idx_leave_start_date (start_date)");
    $pdo->exec("ALTER TABLE leave_requests ADD INDEX idx_leave_end_date (end_date)");
    $pdo->exec("ALTER TABLE leave_requests ADD INDEX idx_leave_status_date (status, start_date)");
    
    // Commit the transaction
    $pdo->commit();
    
    echo "✓ Leave Request enhancement completed successfully!\n";
    echo "\nNew columns added:\n";
    echo "- start_date, end_date (replaces duration string parsing)\n";
    echo "- approved_by, approved_at, reviewed_notes\n";
    echo "- balance_before, balance_after\n";
    echo "- days_count\n";
    echo "- Indexes for filtering performance\n";
    
    echo "\n" . count($leaveRequests) . " records migrated with date parsing\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
