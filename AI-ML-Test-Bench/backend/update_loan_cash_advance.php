<?php
/**
 * Migration Script: Loan/Cash Advance Enhancements
 * 
 * Changes:
 * - Add tracking_code column (format: CA-YYYYMMDD-XXXXX)
 * - Add loan_type column (Cash Advance, Salary Loan, Emergency Loan, Other)
 * - Mark reason field as deprecated (use loan_type instead)
 * - Add additional loan tracking fields
 */

require_once 'db.php';

echo "Starting Loan/Cash Advance Enhancement Migration...\n";

try {
    $pdo->beginTransaction();
    
    // Step 1: Add tracking_code column
    $pdo->exec("ALTER TABLE loans ADD COLUMN tracking_code VARCHAR(20) DEFAULT NULL AFTER employee_id");
    
    // Step 2: Add loan_type column
    $pdo->exec("ALTER TABLE loans ADD COLUMN loan_type ENUM('Cash Advance','Salary Loan','Emergency Loan','Other') DEFAULT 'Cash Advance' AFTER tracking_code");
    
    // Step 3: Add approved_by column for tracking approver
    $pdo->exec("ALTER TABLE loans ADD COLUMN reviewed_by INT(11) DEFAULT NULL AFTER status");
    $pdo->exec("ALTER TABLE loans ADD COLUMN reviewed_at DATETIME DEFAULT NULL AFTER reviewed_by");
    
    // Step 4: Add amount_requested and amount_approved for partial approvals
    $pdo->exec("ALTER TABLE loans ADD COLUMN amount_requested DECIMAL(10,2) DEFAULT NULL AFTER amount");
    $pdo->exec("ALTER TABLE loans ADD COLUMN amount_approved DECIMAL(10,2) DEFAULT NULL AFTER amount_requested");
    
    // Step 5: Add payment terms columns
    $pdo->exec("ALTER TABLE loans ADD COLUMN terms_months INT(11) DEFAULT 1 AFTER amount_approved");
    $pdo->exec("ALTER TABLE loans ADD COLUMN monthly_deduction DECIMAL(10,2) DEFAULT NULL AFTER terms_months");
    
    // Step 6: Add reason_old to mark old reason column as deprecated
    $pdo->exec("ALTER TABLE loans ADD COLUMN reason_deprecated TEXT DEFAULT NULL COMMENT 'DEPRECATED: Use loan_type for categorization' AFTER reason");
    
    // Step 7: Add created tracking
    $pdo->exec("ALTER TABLE loans MODIFY requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    
    // Step 8: Generate tracking codes for existing records
    $stmt = $pdo->query("SELECT id, requested_at FROM loans WHERE tracking_code IS NULL");
    $loans = $stmt->fetchAll();
    
    foreach ($loans as $loan) {
        $date = date('Ymd', strtotime($loan['requested_at']));
        $trackingCode = 'CA-' . $date . '-' . str_pad($loan['id'], 5, '0', STR_PAD_LEFT);
        
        $updateStmt = $pdo->prepare("UPDATE loans SET tracking_code = ? WHERE id = ?");
        $updateStmt->execute([$trackingCode, $loan['id']]);
    }
    
    // Set default loan_type based on reason content for legacy data
    $pdo->exec("UPDATE loans SET loan_type = 'Cash Advance' WHERE loan_type IS NULL");
    
    // Commit the transaction
    $pdo->commit();
    
    echo "✓ Loan/Cash Advance enhancement completed successfully!\n";
    echo "\nNew columns added:\n";
    echo "- tracking_code (format: CA-YYYYMMDD-XXXXX)\n";
    echo "- loan_type (Cash Advance, Salary Loan, Emergency Loan, Other)\n";
    echo "- reviewed_by, reviewed_at\n";
    echo "- amount_requested, amount_approved\n";
    echo "- terms_months, monthly_deduction\n";
    echo "- reason_deprecated (marked for deprecation)\n";
    
    // Generate tracking codes for existing records
    echo "\n" . count($loans) . " existing records updated with tracking codes\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
