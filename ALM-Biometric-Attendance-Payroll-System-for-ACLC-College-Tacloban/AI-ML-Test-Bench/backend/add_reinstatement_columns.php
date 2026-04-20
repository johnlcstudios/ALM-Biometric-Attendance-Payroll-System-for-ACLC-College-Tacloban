<?php
// add_reinstatement_columns.php - Adds missing reinstatement columns to employees table
require_once 'db.php';

echo "Adding reinstatement columns to employees table...\n";

try {
    // Check if reinstated_at column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'reinstated_at'");
    $reinstatedAtExists = $stmt->fetch();
    
    if ($reinstatedAtExists) {
        echo "✓ reinstated_at column already exists!\n";
    } else {
        // Add the reinstated_at column
        $sql = "ALTER TABLE employees ADD COLUMN reinstated_at DATETIME NULL AFTER hire_date";
        $pdo->exec($sql);
        echo "✓ reinstated_at column added successfully!\n";
    }
    
    // Check if reinstated_by column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'reinstated_by'");
    $reinstatedByExists = $stmt->fetch();
    
    if ($reinstatedByExists) {
        echo "✓ reinstated_by column already exists!\n";
    } else {
        // Add the reinstated_by column
        $sql = "ALTER TABLE employees ADD COLUMN reinstated_by INT NULL AFTER reinstated_at";
        $pdo->exec($sql);
        echo "✓ reinstated_by column added successfully!\n";
    }
    
    echo "\n✅ All reinstatement columns have been added successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
