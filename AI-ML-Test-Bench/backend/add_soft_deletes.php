<?php
/**
 * Add soft delete columns to all tables
 * This script adds is_deleted column to prevent permanent data loss
 * Run this once to migrate the database
 */

require_once 'db.php';

echo "🔄 Adding soft delete columns to tables...\n\n";

$tables = [
    'employees' => 'employee records',
    'allowance_categories' => 'allowance categories',
    'employee_allowances' => 'employee allowances',
    'deductions' => 'deduction categories',
    'employee_deductions' => 'employee deductions',
    'subjects' => 'subject records',
    'subject_loads' => 'subject loads',
    'leave_requests' => 'leave requests',
    'companies' => 'company records',
    'users' => 'user accounts'
];

$success = 0;
$errors = 0;

foreach ($tables as $table => $description) {
    try {
        // Check if column already exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE 'is_deleted'");
        $stmt->execute();
        $columnExists = $stmt->fetch();
        
        if ($columnExists) {
            echo "✅ {$table}: is_deleted column already exists\n";
            $success++;
            continue;
        }
        
        // Add is_deleted column
        $sql = "ALTER TABLE {$table} ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER updated_at";
        $pdo->exec($sql);
        
        echo "✅ {$table}: Added is_deleted column ({$description})\n";
        $success++;
        
    } catch (PDOException $e) {
        echo "❌ {$table}: Error - " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n📊 Migration Summary:\n";
echo "   ✅ Success: {$success} tables\n";
echo "   ❌ Errors: {$errors} tables\n";
echo "   📝 Total: " . ($success + $errors) . " tables\n\n";

if ($errors === 0) {
    echo "🎉 Soft delete migration completed successfully!\n";
    echo "💡 Note: You should now update DELETE queries to use soft deletes.\n";
} else {
    echo "⚠️  Migration completed with errors. Please check the issues above.\n";
}

echo "\n📋 Next Steps:\n";
echo "1. Update all DELETE queries to: UPDATE table SET is_deleted = 1 WHERE ...\n";
echo "2. Update all SELECT queries to include: WHERE is_deleted = 0\n";
echo "3. Test thoroughly to ensure no data is accidentally hidden\n";
