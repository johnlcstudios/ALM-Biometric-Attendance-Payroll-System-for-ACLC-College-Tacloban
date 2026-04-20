<?php
// add_faculty_level_column.php - Adds missing faculty_level column to employees table
require_once 'db.php';

echo "Adding faculty_level column to employees table...\n";

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'faculty_level'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "✓ faculty_level column already exists!\n";
    } else {
        // Add the faculty_level column after the position column
        $sql = "ALTER TABLE employees ADD COLUMN faculty_level ENUM('SHS', 'College', 'Both', '') DEFAULT '' AFTER position";
        $pdo->exec($sql);
        echo "✓ faculty_level column added successfully!\n";
    }
    
    // Check if hire_date column exists (it's also used in the code)
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'hire_date'");
    $hireDateExists = $stmt->fetch();
    
    if (!$hireDateExists) {
        echo "Adding hire_date column...\n";
        $sql = "ALTER TABLE employees ADD COLUMN hire_date DATE AFTER faculty_level";
        $pdo->exec($sql);
        echo "✓ hire_date column added successfully!\n";
    } else {
        echo "✓ hire_date column already exists!\n";
    }
    
    // Check if contact_no column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'contact_no'");
    $contactNoExists = $stmt->fetch();
    
    if (!$contactNoExists) {
        echo "Adding contact_no column...\n";
        $sql = "ALTER TABLE employees ADD COLUMN contact_no VARCHAR(20) AFTER email";
        $pdo->exec($sql);
        echo "✓ contact_no column added successfully!\n";
    } else {
        echo "✓ contact_no column already exists!\n";
    }
    
    // Check if gender column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'gender'");
    $genderExists = $stmt->fetch();
    
    if (!$genderExists) {
        echo "Adding gender column...\n";
        $sql = "ALTER TABLE employees ADD COLUMN gender ENUM('Male', 'Female', 'Other') DEFAULT 'Male' AFTER contact_no";
        $pdo->exec($sql);
        echo "✓ gender column added successfully!\n";
    } else {
        echo "✓ gender column already exists!\n";
    }
    
    // Check if profile_picture column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM employees LIKE 'profile_picture'");
    $profilePicExists = $stmt->fetch();
    
    if (!$profilePicExists) {
        echo "Adding profile_picture column...\n";
        $sql = "ALTER TABLE employees ADD COLUMN profile_picture VARCHAR(255) AFTER gender";
        $pdo->exec($sql);
        echo "✓ profile_picture column added successfully!\n";
    } else {
        echo "✓ profile_picture column already exists!\n";
    }
    
    echo "\n✅ All missing columns have been added successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
