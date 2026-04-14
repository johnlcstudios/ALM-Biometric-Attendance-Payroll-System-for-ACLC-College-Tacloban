<?php
// Migration Runner - Applies all pending database migrations
// Access: http://localhost/ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban/AI-ML-Test-Bench/run-migrations.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow admin to run migrations
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    die("<h2>Unauthorized</h2><p>Only administrators can run database migrations.</p>");
}

require_once 'backend/db.php';

$migrationsDir = __DIR__ . '/sql/migrations/';
$migrations = glob($migrationsDir . '*.sql');

if (empty($migrations)) {
    die("<h2>No Migrations Found</h2><p>No migration files found in sql/migrations/ directory.</p>");
}

sort($migrations);

// Create migration tracking table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Get already executed migrations
$stmt = $pdo->query("SELECT filename FROM migrations");
$executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

$pendingMigrations = array_diff($migrations, array_map(function($f) use ($migrationsDir) {
    return $migrationsDir . basename($f);
}, $executedMigrations));

if (isset($_GET['run']) && !empty($pendingMigrations)) {
    echo "<h2>Running Migrations</h2>";
    echo "<pre>";
    
    $success = 0;
    $failed = 0;
    
    foreach ($pendingMigrations as $migration) {
        $filename = basename($migration);
        echo "Running: $filename... ";
        
        try {
            $pdo->beginTransaction();
            
            $sql = file_get_contents($migration);
            $pdo->exec($sql);
            
            $stmt = $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)");
            $stmt->execute([$filename]);
            
            $pdo->commit();
            echo "✓ SUCCESS\n";
            $success++;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "✗ FAILED: " . $e->getMessage() . "\n";
            $failed++;
        }
    }
    
    echo "\n========================================\n";
    echo "Migrations completed: $success succeeded, $failed failed\n";
    echo "</pre>";
    
    echo "<p><a href='index.php'>Return to Dashboard</a></p>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migrations - ALM Biometrics</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }
        
        body {
            padding: 40px;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .migration-list {
            margin: 20px 0;
        }
        
        .migration-item {
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
        }
        
        .migration-item.pending {
            border-left-color: #ff9800;
            background: #fff3e0;
        }
        
        .migration-item.executed {
            border-left-color: #4caf50;
            background: #e8f5e9;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #4facfe;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin-top: 20px;
        }
        
        .btn:hover {
            background: #00f2fe;
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .status {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Database Migrations</h1>
        <p>Manage and apply database schema updates</p>
        
        <div class="migration-list">
            <?php foreach ($migrations as $migration): ?>
                <?php 
                $filename = basename($migration);
                $isExecuted = in_array($filename, $executedMigrations);
                $executedAt = null;
                
                if ($isExecuted) {
                    $stmt = $pdo->prepare("SELECT executed_at FROM migrations WHERE filename = ?");
                    $stmt->execute([$filename]);
                    $executedAt = $stmt->fetchColumn();
                }
                ?>
                <div class="migration-item <?php echo $isExecuted ? 'executed' : 'pending'; ?>">
                    <strong><?php echo htmlspecialchars($filename); ?></strong>
                    <div class="status">
                        <?php if ($isExecuted): ?>
                            ✓ Executed on <?php echo date('Y-m-d H:i:s', strtotime($executedAt)); ?>
                        <?php else: ?>
                            ⏳ Pending
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (!empty($pendingMigrations)): ?>
            <p><strong><?php echo count($pendingMigrations); ?> migration(s) pending</strong></p>
            <a href="?run=1" class="btn" onclick="return confirm('Are you sure you want to run pending migrations?')">
                Run Pending Migrations
            </a>
        <?php else: ?>
            <p><strong>✓ All migrations are up to date</strong></p>
            <a href="index.php" class="btn">Return to Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>
