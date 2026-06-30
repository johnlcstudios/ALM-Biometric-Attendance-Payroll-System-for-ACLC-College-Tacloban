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
            
            // Run migrations after successful setup
            $migrationsDir = __DIR__ . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'migrations';
            $migrationMessage = '';
            
            if (is_dir($migrationsDir)) {
                $migrationFiles = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.sql');
                sort($migrationFiles);
                
                if (!empty($migrationFiles)) {
                    // Create migration tracking table
                    try {
                        $pdo->exec("
                            CREATE TABLE IF NOT EXISTS migrations (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                filename VARCHAR(255) NOT NULL UNIQUE,
                                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                            )
                        ");
                        
                        $stmt = $pdo->query("SELECT filename FROM migrations");
                        $executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        $migrationSuccess = 0;
                        
                        foreach ($migrationFiles as $migrationFile) {
                            $filename = basename($migrationFile);
                            
                            if (in_array($filename, $executedMigrations)) {
                                continue;
                            }
                            
                            try {
                                $pdo->beginTransaction();
                                $sql = file_get_contents($migrationFile);
                                $pdo->exec($sql);
                                
                                $stmt = $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)");
                                $stmt->execute([$filename]);
                                
                                $pdo->commit();
                                $migrationSuccess++;
                            } catch (Exception $e) {
                                $pdo->rollBack();
                                // Log migration error but don't fail setup
                                error_log("Migration failed: $filename - " . $e->getMessage());
                            }
                        }
                        
                        if ($migrationSuccess > 0) {
                            $migrationMessage = "<br><strong>Database Migrations:</strong> $migrationSuccess migration(s) applied successfully.";
                        }
                    } catch (Exception $e) {
                        // Log migration error but don't fail setup
                        error_log("Migration setup failed: " . $e->getMessage());
                    }
                }
            }
            
            $success = "Setup completed successfully!<br>
                       <strong>Company Code:</strong> $companyCode<br>
                       <strong>Username:</strong> $username<br>
                       <p>You can now <a href='login.php'>login</a> with your credentials.</p>
                       $migrationMessage";
            
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

<!-- Splash Screen -->
<div id="splashScreen" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    transition: opacity 0.8s ease-out, visibility 0.8s;
">
    <div style="
        text-align: center;
        color: white;
        animation: fadeInUp 1s ease-out;
    ">
        <!-- Logo/Icon -->
        <div style="
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            border: 3px solid rgba(255, 255, 255, 0.3);
            animation: pulse 2s infinite;
        ">
            <i class="fas fa-user-shield" style="font-size: 50px;"></i>
        </div>
        
        <!-- System Name -->
        <h1 style="
            font-size: 42px;
            margin-bottom: 10px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            animation: slideIn 1s ease-out;
        ">Secure Setup</h1>
        
        <!-- Version -->
        <p style="
            font-size: 18px;
            margin-bottom: 40px;
            opacity: 0.9;
            font-weight: 300;
        ">ALM Biometric System v2.4.0</p>
        
        <!-- Credits -->
        <div style="
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px 40px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            animation: fadeIn 1.5s ease-out;
        ">
            <p style="
                font-size: 20px;
                margin-bottom: 10px;
                font-weight: 600;
            ">
                <i class="fas fa-heart" style="color: #ff6b6b; animation: heartbeat 1.5s infinite;"></i>
            </p>
            <p style="
                font-size: 18px;
                margin: 0;
                line-height: 1.6;
                font-weight: 500;
            ">Built with STRESS from</p>
            <p style="
                font-size: 24px;
                margin: 10px 0;
                font-weight: 700;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            ">BSIT 3A</p>
            <p style="
                font-size: 16px;
                margin: 5px 0 0;
                opacity: 0.9;
            ">A.Y. 2025-2026 | Batch 2027</p>
        </div>
        
        <!-- Loading Indicator -->
        <div style="margin-top: 40px;">
            <div style="
                width: 50px;
                height: 50px;
                margin: 0 auto;
                border: 4px solid rgba(255, 255, 255, 0.3);
                border-top: 4px solid white;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            "></div>
            <p style="margin-top: 20px; opacity: 0.9;">Preparing setup...</p>
        </div>
    </div>
</div>

<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 0 0 20px rgba(255, 255, 255, 0);
    }
}

@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    10%, 30% { transform: scale(1.2); }
    20%, 40% { transform: scale(1); }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
// Splash screen timeout
window.addEventListener('load', function() {
    setTimeout(function() {
        const splash = document.getElementById('splashScreen');
        if (splash) {
            splash.style.opacity = '0';
            splash.style.visibility = 'hidden';
            setTimeout(function() {
                splash.style.display = 'none';
            }, 800);
        }
    }, 3000); // Show for 3 seconds
});
</script>

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
                    <div class="input-wrapper">
                        <input type="password" name="password" class="password-field" required placeholder="Create a strong password">
                        <i class="fas fa-eye toggle-password" role="button" tabindex="0" aria-label="Toggle password visibility"></i>
                    </div>
                    <p class="password-hint">Must be at least 8 characters with uppercase, lowercase, and numbers</p>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="confirm_password" class="password-field" required placeholder="Confirm your password">
                        <i class="fas fa-eye toggle-password" role="button" tabindex="0" aria-label="Toggle password visibility"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-rocket"></i> Complete Setup
                </button>
            </form>
        <?php endif; ?>
    </div>
    <script src="js/password-toggle.js"></script>
</body>
</html>
