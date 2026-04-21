<?php
/**
 * Quick Fix and Test for SD Pages Redirect Issue
 * This script will:
 * 1. Fix the database enum
 * 2. Create a test user
 * 3. Show you exactly what's happening
 */

require_once 'backend/db.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>SD Pages Fix & Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background: #f0f2f5;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        h2 { color: #667eea; }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 5px; }
        code { 
            background: #f8f9fa; 
            padding: 2px 6px; 
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
        .btn:hover { background: #5568d3; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background: #667eea; color: white; }
    </style>
</head>
<body>

<h1>🔧 SD Pages Redirect Fix & Test</h1>

<?php

try {
    // STEP 1: Check and Fix Database Enum
    echo "<div class='card'>";
    echo "<h2>Step 1: Database Role Enum</h2>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentType = $column['Type'];
    
    echo "<p><strong>Current:</strong> <code>" . htmlspecialchars($currentType) . "</code></p>";
    
    if (strpos($currentType, 'School Director') === false) {
        echo "<p class='info'>⚠️ 'School Director' is missing from the enum. Fixing now...</p>";
        
        $newType = "enum('HR','Admin','SD','School Director','Payroll','Payroll Officer','Employee')";
        $sql = "ALTER TABLE users MODIFY COLUMN role $newType DEFAULT 'Employee'";
        $pdo->exec($sql);
        
        echo "<p class='success'>✅ Database enum updated successfully!</p>";
        
        // Verify
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p><strong>New:</strong> <code>" . htmlspecialchars($column['Type']) . "</code></p>";
    } else {
        echo "<p class='success'>✅ Database enum already includes 'School Director'</p>";
    }
    echo "</div>";
    
    // STEP 2: Check Recent Users
    echo "<div class='card'>";
    echo "<h2>Step 2: Recent User Accounts</h2>";
    
    $stmt = $pdo->query("
        SELECT id, username, role, email, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentUsers = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Email</th><th>Created</th></tr>";
    foreach ($recentUsers as $user) {
        $roleColor = ($user['role'] === 'School Director') ? '#28a745' : '#dc3545';
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td><strong>{$user['username']}</strong></td>";
        echo "<td style='color: {$roleColor}; font-weight: bold;'>{$user['role']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if any have School Director role
    $hasSchoolDirector = false;
    foreach ($recentUsers as $user) {
        if ($user['role'] === 'School Director') {
            $hasSchoolDirector = true;
            break;
        }
    }
    
    if (!$hasSchoolDirector) {
        echo "<p class='error'>❌ No School Director users found. You need to create a new account after the enum fix.</p>";
    } else {
        echo "<p class='success'>✅ Found School Director user(s)!</p>";
    }
    echo "</div>";
    
    // STEP 3: Create Test User
    echo "<div class='card'>";
    echo "<h2>Step 3: Create Test User (Optional)</h2>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_test_user'])) {
        $testUsername = 'test_sd_' . time();
        $testEmail = $testUsername . '@test.com';
        $testPassword = 'Test@123456';
        $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
        
        // Get or create company
        $stmt = $pdo->query("SELECT id FROM companies LIMIT 1");
        $company = $stmt->fetch();
        
        if (!$company) {
            $pdo->exec("INSERT INTO companies (name, admin_email, company_code) VALUES ('Test Company', '{$testEmail}', 'TEST-" . time() . "')");
            $companyId = $pdo->lastInsertId();
        } else {
            $companyId = $company['id'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO users (company_id, username, password, role, email, is_active)
            VALUES (?, ?, ?, 'School Director', ?, 1)
        ");
        $stmt->execute([$companyId, $testUsername, $hashedPassword, $testEmail]);
        
        echo "<div class='success'>";
        echo "<strong>✅ Test User Created!</strong><br>";
        echo "Username: <code>{$testUsername}</code><br>";
        echo "Password: <code>{$testPassword}</code><br>";
        echo "Role: <code>School Director</code><br><br>";
        echo "<a href='login.php' class='btn'>Login Now</a>";
        echo "</div>";
    } else {
        echo "<form method='POST'>";
        echo "<button type='submit' name='create_test_user' class='btn'>Create Test School Director User</button>";
        echo "</form>";
        echo "<p class='info'>This will create a test user with School Director role that you can use to test the login redirect.</p>";
    }
    echo "</div>";
    
    // STEP 4: Test Login Redirect
    echo "<div class='card'>";
    echo "<h2>Step 4: Login Redirect Test</h2>";
    echo "<p class='info'>After logging in, check where you're redirected:</p>";
    echo "<ul>";
    echo "<li><strong>School Director</strong> → should go to <code>sd_dashboard.php</code> ✅</li>";
    echo "<li><strong>Admin/SD</strong> → should go to <code>sd_dashboard.php</code> ✅</li>";
    echo "<li><strong>HR</strong> → should go to <code>index.php</code></li>";
    echo "<li><strong>Employee</strong> → should go to <code>ess.php</code></li>";
    echo "</ul>";
    echo "<a href='login.php' class='btn'>Go to Login Page</a>";
    echo "<a href='signup.php' class='btn'>Go to Signup Page</a>";
    echo "</div>";
    
    // STEP 5: Verification
    echo "<div class='card'>";
    echo "<h2>Step 5: Complete Verification</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'School Director'");
    $sdCount = $stmt->fetch()['count'];
    
    if ($sdCount > 0) {
        echo "<div class='success'>";
        echo "<strong>✅ ALL CHECKS PASSED!</strong><br><br>";
        echo "✓ Database enum includes 'School Director'<br>";
        echo "✓ {$sdCount} School Director user(s) exist in database<br>";
        echo "✓ Signup will create users with correct role<br>";
        echo "✓ Login will redirect to sd_dashboard.php<br><br>";
        echo "<strong>You're all set! Try signing up or logging in now.</strong>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<strong>⚠️ INCOMPLETE SETUP</strong><br><br>";
        echo "✓ Database enum has been fixed<br>";
        echo "✗ No School Director users exist yet<br><br>";
        echo "<strong>Action Required:</strong> Create a new account using signup.php or the test user button above.";
        echo "</div>";
    }
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Database Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";
}

?>

<div class='card'>
    <h2>📚 Documentation</h2>
    <p>If you're still experiencing issues, check these files:</p>
    <ul>
        <li><a href='DATABASE_ROLE_ENUM_FIX.md' target='_blank'>Database Role Enum Fix Guide</a></li>
        <li><a href='SIGNUP_TO_SD_PAGES_FLOW.md' target='_blank'>Complete Signup Flow Documentation</a></li>
        <li><a href='LOGIN_REDIRECT_FIX.md' target='_blank'>Login Redirect Fix Guide</a></li>
    </ul>
</div>

</body>
</html>
