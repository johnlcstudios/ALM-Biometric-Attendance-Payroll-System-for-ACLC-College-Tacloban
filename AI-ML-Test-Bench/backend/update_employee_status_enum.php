<?php
/**
 * Migration Script: Update Employee Status Enumerations
 * 
 * Changes:
 * - Update employees.status enum from 'Active','Inactive','On Leave','Probationary','Contractual','Resigned' 
 *   to 'Active','Inactive','Retired','Resigned','Deceased'
 * - Add new columns for tracking extended status info
 */

require_once 'db.php';

echo "Starting Employee Status Enum Migration...\n";

try {
    $pdo->beginTransaction();
    
    // Step 1: Add temporary column to store old status values
    $pdo->exec("ALTER TABLE employees ADD COLUMN status_old VARCHAR(50) DEFAULT NULL AFTER status");
    
    // Step 2: Migrate existing status values to new system
    $statusMapping = [
        'Active' => 'Active',
        'Inactive' => 'Inactive',
        'On Leave' => 'Inactive',  // Map On Leave to Inactive
        'Probationary' => 'Inactive', // Map Probationary to Inactive
        'Contractual' => 'Inactive', // Map Contractual to Inactive (can be tracked via position)
        'Resigned' => 'Resigned' // Keep Resigned
    ];
    
    foreach ($statusMapping as $oldStatus => $newStatus) {
        $stmt = $pdo->prepare("UPDATE employees SET status_old = ? WHERE status = ?");
        $stmt->execute([$oldStatus, $oldStatus]);
    }
    
    // Step 3: Update to new enum values (MySQL requires dropping and re-adding enum)
    // First, get current enum values
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'status'");
    $columnInfo = $stmt->fetch();
    
    // Drop the enum constraint by changing to VARCHAR temporarily
    $pdo->exec("ALTER TABLE employees MODIFY status VARCHAR(50) DEFAULT 'Active'");
    
    // Add the new enum values
    $pdo->exec("ALTER TABLE employees MODIFY status ENUM('Active','Inactive','Retired','Resigned','Deceased') DEFAULT 'Active'");
    
    // Step 4: Add new status tracking columns
    $pdo->exec("ALTER TABLE employees ADD COLUMN status_effective_date DATE DEFAULT NULL AFTER status");
    $pdo->exec("ALTER TABLE employees ADD COLUMN status_notes TEXT DEFAULT NULL AFTER status_effective_date");
    
    // Step 5: Add legacy tracking column (for backward compatibility)
    $pdo->exec("ALTER TABLE employees ADD COLUMN employment_type ENUM('Regular','Probationary','Contractual') DEFAULT 'Regular' AFTER status_notes");
    
    // Commit the transaction
    $pdo->commit();
    
    echo "✓ Employee status enum migration completed successfully!\n";
    echo "\nNew status values: Active, Inactive, Retired, Resigned, Deceased\n";
    echo "Legacy status values preserved in status_old column\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
