<?php
// Run this file once to add the profile_picture column
try {
    $pdo = new PDO('mysql:host=localhost;dbname=alm_biometrics', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'profile_picture'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE employees ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL AFTER face_descriptor");
        $pdo->exec("CREATE INDEX idx_employee_profile_picture ON employees(profile_picture)");
        echo "✓ Migration successful! profile_picture column added.\n";
    } else {
        echo "✓ Column 'profile_picture' already exists.\n";
    }
} catch(Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
