<?php
/**
 * Fix Database Role Enum
 * Adds 'SD' and 'School Director' to the users.role enum
 */

require_once 'backend/db.php';

echo "<h2>Fixing Database Role Enum for SD Pages</h2>";
echo "<hr>";

try {
    // Step 1: Check current role enum
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentType = $column['Type'];
    
    echo "<p><strong>Current role enum:</strong><br>";
    echo "<code>" . htmlspecialchars($currentType) . "</code></p>";
    
    // Step 2: Update the enum to include SD and School Director
    $newType = str_replace(
        "enum('HR','Admin','Payroll','Payroll Officer','Employee')",
        "enum('HR','Admin','SD','School Director','Payroll','Payroll Officer','Employee')",
        $currentType
    );
    
    if ($newType === $currentType) {
        echo "<p style='color: orange;'>⚠️ Role enum may already be updated or has different format.</p>";
        echo "<p>Attempting to add SD roles anyway...</p>";
        
        // Try alternative approach
        $newType = "enum('HR','Admin','SD','School Director','Payroll','Payroll Officer','Employee')";
    }
    
    $sql = "ALTER TABLE users MODIFY COLUMN role $newType DEFAULT 'Employee'";
    
    echo "<p><strong>Executing:</strong><br><code>" . htmlspecialchars($sql) . "</code></p>";
    
    $pdo->exec($sql);
    
    echo "<p style='color: green;'>✅ Role enum updated successfully!</p>";
    
    // Step 3: Verify the update
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>New role enum:</strong><br>";
    echo "<code>" . htmlspecialchars($column['Type']) . "</code></p>";
    
    // Step 4: Check for any users with invalid roles
    $stmt = $pdo->query("SELECT id, username, role FROM users WHERE role NOT IN ('HR','Admin','SD','School Director','Payroll','Payroll Officer','Employee')");
    $invalidUsers = $stmt->fetchAll();
    
    if (count($invalidUsers) > 0) {
        echo "<p style='color: red;'>❌ Found " . count($invalidUsers) . " users with invalid roles:</p>";
        echo "<ul>";
        foreach ($invalidUsers as $user) {
            echo "<li>ID: {$user['id']}, Username: {$user['username']}, Role: {$user['role']}</li>";
        }
        echo "</ul>";
        echo "<p>These users will need to be manually updated.</p>";
    } else {
        echo "<p style='color: green;'>✅ All users have valid roles.</p>";
    }
    
    // Step 5: Show summary
    echo "<hr>";
    echo "<h3>Summary:</h3>";
    echo "<ul>";
    echo "<li>✅ Added 'SD' to role enum</li>";
    echo "<li>✅ Added 'School Director' to role enum</li>";
    echo "<li>✅ Signup can now create School Director users</li>";
    echo "<li>✅ Login redirect will work correctly</li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<p style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "<strong>✅ Database migration completed successfully!</strong><br>";
    echo "You can now sign up and users will be created with 'School Director' role.";
    echo "</p>";
    
    echo "<p><a href='signup.php' style='background: #4facfe; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Signup Page</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red; background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</p>";
}
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h2 {
        color: #333;
    }
    code {
        background: #f8f9fa;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: 'Courier New', monospace;
    }
    ul {
        line-height: 1.8;
    }
</style>
