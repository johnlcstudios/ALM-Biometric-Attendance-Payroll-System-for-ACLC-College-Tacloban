<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$db = 'alm_biometrics';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$isCli = (php_sapi_name() === 'cli');
$remote = $_SERVER['REMOTE_ADDR'] ?? 'cli';
$isLocal = $isCli || in_array($remote, ['127.0.0.1', '::1'], true);
if (!$isLocal) {
    http_response_code(403);
    die("Forbidden");
}

require_once 'backend/notifications.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .setup-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 500px; text-align: center; }
        h2 { color: #1e0178; margin-bottom: 20px; }
        .status-item { margin: 10px 0; text-align: left; padding: 10px; background: #f9f9f9; border-radius: 6px; }
        .error-list { color: #db261f; text-align: left; font-size: 0.9rem; max-height: 200px; overflow-y: auto; }
        .btn-login { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #1e0178; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; }
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
            <i class="fas fa-database" style="font-size: 50px;"></i>
        </div>
        
        <!-- System Name -->
        <h1 style="
            font-size: 42px;
            margin-bottom: 10px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            animation: slideIn 1s ease-out;
        ">Database Setup</h1>
        
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
            ">Built with love from</p>
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
            <p style="margin-top: 20px; opacity: 0.9;">Initializing database...</p>
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

<div class="setup-container">
<?php
$schemaPath = __DIR__ . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'schema.sql';
if (!file_exists($schemaPath)) {
    http_response_code(500);
    echo "schema.sql not found at: " . htmlspecialchars($schemaPath);
    exit;
}

$dsnServer = "mysql:host=$host;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

function splitSqlStatements(string $sql): array {
    $statements = [];
    $buffer = '';
    $inSingle = false;
    $inDouble = false;
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $next = ($i + 1 < $len) ? $sql[$i + 1] : '';

        if (!$inSingle && !$inDouble) {
            if ($ch === '-' && $next === '-') {
                while ($i < $len && $sql[$i] !== "\n") $i++;
                continue;
            }
            if ($ch === '#') {
                while ($i < $len && $sql[$i] !== "\n") $i++;
                continue;
            }
            if ($ch === '/' && $next === '*') {
                $i += 2;
                while ($i < $len) {
                    if ($sql[$i] === '*' && ($i + 1 < $len) && $sql[$i + 1] === '/') {
                        $i++;
                        break;
                    }
                    $i++;
                }
                continue;
            }
        }

        if ($ch === "'" && !$inDouble) {
            $escaped = ($i > 0 && $sql[$i - 1] === '\\');
            if (!$escaped) $inSingle = !$inSingle;
        } elseif ($ch === '"' && !$inSingle) {
            $escaped = ($i > 0 && $sql[$i - 1] === '\\');
            if (!$escaped) $inDouble = !$inDouble;
        }

        if ($ch === ';' && !$inSingle && !$inDouble) {
            $stmt = trim($buffer);
            if ($stmt !== '') $statements[] = $stmt;
            $buffer = '';
            continue;
        }

        $buffer .= $ch;
    }

    $stmt = trim($buffer);
    if ($stmt !== '') $statements[] = $stmt;
    return $statements;
}

try {
    $pdoServer = new PDO($dsnServer, $user, $pass, $options);
    $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $dsnDb = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsnDb, $user, $pass, $options);

    $sql = file_get_contents($schemaPath);
    $statements = splitSqlStatements($sql);

    $ran = 0;
    $errors = [];

    foreach ($statements as $statement) {
        $upper = strtoupper(ltrim($statement));
        if (str_starts_with($upper, 'CREATE DATABASE') || str_starts_with($upper, 'USE ')) {
            continue;
        }
        try {
            $pdo->exec($statement);
            $ran++;
        } catch (Throwable $e) {
            $errors[] = ['statement' => $statement, 'error' => $e->getMessage()];
        }
    }

    if ($isCli) {
        echo "Database: $db\n";
        echo "Statements executed: $ran\n";
        if ($errors) {
            echo "Errors:\n";
            foreach ($errors as $err) {
                echo "- " . $err['error'] . "\n";
            }
            exit(1);
        }
        echo "Setup completed.\n";
        exit(0);
    }

    echo "<h2>Database Setup</h2>";
    echo "<div class='status-item'><strong>Database:</strong> " . htmlspecialchars($db) . "</div>";
    echo "<div class='status-item'><strong>Statements executed:</strong> " . htmlspecialchars((string)$ran) . "</div>";

    if ($errors) {
        echo "<h3>Errors</h3>";
        echo "<div class='error-list'><ul>";
        foreach ($errors as $err) {
            echo "<li>" . htmlspecialchars($err['error']) . "</li>";
        }
        echo "</ul></div>";
        showNotification("Setup completed with errors.", "warning");
    } else {
        echo "<div class='status-item' style='color: #27ae60;'><strong>Status:</strong> Completed successfully.</div>";
        
        // Run migrations after successful schema setup
        echo "<div class='status-item' style='background: #e3f2fd;'><strong>Running Migrations...</strong></div>";
        
        $migrationsDir = __DIR__ . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'migrations';
        if (is_dir($migrationsDir)) {
            $migrationFiles = glob($migrationsDir . DIRECTORY_SEPARATOR . '*.sql');
            sort($migrationFiles);
            
            if (!empty($migrationFiles)) {
                echo "<div class='status-item'>Found " . count($migrationFiles) . " migration file(s)</div>";
                
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
                    $migrationErrors = 0;
                    
                    foreach ($migrationFiles as $migrationFile) {
                        $filename = basename($migrationFile);
                        
                        if (in_array($filename, $executedMigrations)) {
                            echo "<div class='status-item' style='color: #666;'>✓ $filename (already executed)</div>";
                            continue;
                        }
                        
                        try {
                            $pdo->beginTransaction();
                            $sql = file_get_contents($migrationFile);
                            $pdo->exec($sql);
                            
                            $stmt = $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)");
                            $stmt->execute([$filename]);
                            
                            $pdo->commit();
                            echo "<div class='status-item' style='color: #27ae60;'>✓ $filename (executed)</div>";
                            $migrationSuccess++;
                        } catch (Throwable $e) {
                            $pdo->rollBack();
                            echo "<div class='status-item' style='color: #db261f;'>✗ $filename: " . htmlspecialchars($e->getMessage()) . "</div>";
                            $migrationErrors++;
                        }
                    }
                    
                    echo "<div class='status-item' style='background: #e8f5e9;'><strong>Migrations:</strong> $migrationSuccess succeeded, $migrationErrors failed</div>";
                } catch (Throwable $e) {
                    echo "<div class='status-item' style='color: #db261f;'><strong>Migration Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        }
        
        showNotification("Database setup completed successfully!", "success");
    }

    echo "<a href='login.php' class='btn-login'>Go to Login</a>";
} catch (Throwable $e) {
    if ($isCli) {
        fwrite(STDERR, "Setup failed: " . $e->getMessage() . "\n");
        exit(1);
    }
    http_response_code(500);
    echo "<h2>Setup Failed</h2>";
    echo "<div class='error-list' style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</div>";
    showNotification("Setup failed: " . $e->getMessage(), "error");
}
?>
</div>
</body>
</html>
