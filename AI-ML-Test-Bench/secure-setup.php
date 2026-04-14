<?php
// secure-setup.php - Secure Initial Setup Script
// This script creates the first admin account with a strong password

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'backend/db.php';

// Check if setup is already completed
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Admin'");
$adminCount = $stmt->fetchColumn();

if ($adminCount > 0) {
    die("<h2>Setup Already Completed</h2><p>Admin account already exists. Please login normally.</p>");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = trim($_POST['company_name'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($companyName) || empty($adminEmail) || empty($username) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain uppercase, lowercase, and numbers';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Generate unique company code
            $companyCode = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', $companyName), 0, 4)) . '-' . substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4);
            
            // Create company
            $stmt = $pdo->prepare("INSERT INTO companies (name, admin_email, company_code) VALUES (?, ?, ?)");
            $stmt->execute([$companyName, $adminEmail, $companyCode]);
            $companyId = $pdo->lastInsertId();
            
            // Create admin user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (company_id, username, password, role, email) VALUES (?, ?, ?, 'Admin', ?)");
            $stmt->execute([$companyId, $username, $hashedPassword, $adminEmail]);
            
            $pdo->commit();
            
            $success = "Setup completed successfully!<br>
                       <strong>Company Code:</strong> $companyCode<br>
                       <strong>Username:</strong> $username<br>
                       <p>You can now <a href='login.php'>login</a> with your credentials.</p>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Setup failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initial System Setup - ALM Biometrics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }
        
        body {
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .setup-card {
            width: 500px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .password-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="setup-card">
        <h1><i class="fas fa-cog"></i> Initial Setup</h1>
        <p class="subtitle">Create your company and admin account</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text" name="company_name" required placeholder="Enter your company name">
                </div>
                
                <div class="form-group">
                    <label>Admin Email</label>
                    <input type="email" name="admin_email" required placeholder="admin@company.com">
                </div>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Choose a username">
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Create a strong password">
                    <p class="password-hint">Must be at least 8 characters with uppercase, lowercase, and numbers</p>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm your password">
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-rocket"></i> Complete Setup
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
