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
