<?php
// add_user_active_column.php - Adds missing is_active column to users table
require_once 'db.php';

echo "Adding is_active column to users table...\n";

try {
    // Check if is_active column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "✓ is_active column already exists!\n";
    } else {
        // Add the is_active column
        $sql = "ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER email";
        $pdo->exec($sql);
        echo "✓ is_active column added successfully!\n";
        
        // Set all existing users as active
        $updateStmt = $pdo->exec("UPDATE users SET is_active = TRUE WHERE is_active IS NULL");
        echo "✓ Set $updateStmt existing users as active!\n";
    }
    
    echo "\n✅ User active status column has been added successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
