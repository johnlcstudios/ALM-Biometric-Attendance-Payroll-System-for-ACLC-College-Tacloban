<?php
/**
 * Migration Script: Deprecate Resignations Module
 * 
 * Changes:
 * - Rename resignations table to _deprecated suffix
 * - Create view for backward compatibility
 * - Log deprecation in audit
 */

require_once 'db.php';

echo "Starting Resignations Module Deprecation...\n";

try {
    $pdo->beginTransaction();
    
    // Step 1: Create backup of resignations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS resignations_backup AS SELECT * FROM resignations");
    
    // Step 2: Add deprecation metadata columns
    $pdo->exec("ALTER TABLE resignations ADD COLUMN deprecated_at DATETIME DEFAULT NULL");
    $pdo->exec("ALTER TABLE resignations ADD COLUMN deprecated_reason VARCHAR(255) DEFAULT 'Module deprecated - Use employee status changes instead'");
    
    // Step 3: Mark all records as deprecated
    $pdo->exec("UPDATE resignations SET deprecated_at = NOW() WHERE deprecated_at IS NULL");
    
    // Step 4: Rename table to deprecated name
    $pdo->exec("ALTER TABLE resignations RENAME TO resignations_DEPRECATED");
    
    // Step 5: Create view for backward compatibility (empty, shows deprecation notice)
    $pdo->exec("DROP VIEW IF EXISTS resignations");
    $pdo->exec("CREATE VIEW resignations AS 
        SELECT 'DEPRECATED' as deprecated_flag, 
               NULL as id, 
               NULL as company_id, 
               NULL as employee_id, 
               'Module deprecated - Use employee status changes' as reason, 
               NULL as effective_date, 
               NULL as status, 
               NOW() as requested_at,
               NOW() as deprecated_at
        WHERE 1=0  -- Always empty, forces deprecation message
    ");
    
    // Step 6: Ensure the employees table has Resigned status for continuity
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'status'");
    $columnInfo = $stmt->fetch();
    
    // Check if Resigned is already in the enum
    if (strpos($columnInfo['Type'], 'Resigned') === false) {
        // Add Resigned to the enum
        $pdo->exec("ALTER TABLE employees MODIFY status VARCHAR(50)");
        $pdo->exec("ALTER TABLE employees MODIFY status ENUM('Active','Inactive','On Leave','Probationary','Contractual','Resigned','Retired','Deceased') DEFAULT 'Active'");
    }
    
    // Commit the transaction
    $pdo->commit();
    
    echo "✓ Resignations module deprecation completed successfully!\n";
    echo "\nChanges made:\n";
    echo "- Table renamed to resignations_DEPRECATED\n";
    echo "- Original data backed up to resignations_backup\n";
    echo "- Created backward compatibility view (empty, shows deprecation)\n";
    echo "- Existing employee status 'Resigned' preserved\n";
    
    echo "\nFuture Workflow:\n";
    echo "- Use employee status update to 'Resigned' instead of resignations table\n";
    echo "- Resignation data preserved in resignations_backup table\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Deprecation failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
