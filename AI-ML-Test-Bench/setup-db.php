<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'sql301.infinityfree.com';
$db = 'if0_41515464_alm_biometrics';
$user = 'if0_41515464';
$pass = 'BSIT3Aay2027';
$charset = 'utf8mb4';

$isCli = (php_sapi_name() === 'cli');
$remote = $_SERVER['REMOTE_ADDR'] ?? 'cli';
$isLocal = $isCli || in_array($remote, ['127.0.0.1', '::1'], true);
if (!$isLocal) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

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
    echo "<p><strong>Database:</strong> " . htmlspecialchars($db) . "</p>";
    echo "<p><strong>Statements executed:</strong> " . htmlspecialchars((string)$ran) . "</p>";

    if ($errors) {
        echo "<h3>Errors</h3>";
        echo "<ul>";
        foreach ($errors as $err) {
            echo "<li>" . htmlspecialchars($err['error']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p><strong>Status:</strong> Completed successfully.</p>";
    }

    echo "<p><a href=\"login.php\">Go to Login</a></p>";
} catch (Throwable $e) {
    if ($isCli) {
        fwrite(STDERR, "Setup failed: " . $e->getMessage() . "\n");
        exit(1);
    }
    http_response_code(500);
    echo "<h2>Setup failed</h2>";
    echo "<div>" . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
