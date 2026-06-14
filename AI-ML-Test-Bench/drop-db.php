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
    die("Forbidden: Local access only");
}

// Session check for web
if (!$isCli && (session_status() === PHP_SESSION_NONE)) {
    session_start();
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'HR' && $_SESSION['role'] !== 'Admin')) {
        http_response_code(403);
        die("Forbidden: Admin access only");
    }
}

require_once 'backend/notifications.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Drop Database - DANGEROUS</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7f6; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .setup-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 500px; text-align: center; }
        h2 { color: #dc3545; margin-bottom: 20px; }
        .status-item { margin: 10px 0; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 6px; }
        .btn-login { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #dc3545; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; }
        .btn-login:hover { background: #c82333; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 6px; margin: 20px 0; font-weight: bold; }
    </style>
</head>
<body>

<!-- Splash Screen -->
<div id="splashScreen" style="
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
    display: flex; justify-content: center; align-items: center; z-index: 99999;
    transition: opacity 0.8s ease-out, visibility 0.8s;
">
    <div style="text-align: center; color: white; animation: fadeInUp 1s ease-out;">
        <div style="
            width: 100px; height: 100px; margin: 0 auto 30px;
            background: rgba(255,255,255,0.2); border-radius: 50%; display: flex;
            align-items: center; justify-content: center; backdrop-filter: blur(10px);
            border: 3px solid rgba(255,255,255,0.3); animation: pulse 2s infinite;
        ">
            <i class="fas fa-exclamation-triangle" style="font-size: 50px;"></i>
        </div>
        <h1 style="font-size: 42px; margin-bottom: 10px; font-weight: 700; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">DROP DATABASE</h1>
        <p style="font-size: 18px; margin-bottom: 40px; opacity: 0.9; font-weight: 300;">ALM Biometric System v2.4.0</p>
        <div style="margin-top: 40px;">
            <div style="
                width: 50px; height: 50px; margin: 0 auto;
                border: 4px solid rgba(255,255,255,0.3); border-top: 4px solid white;
                border-radius: 50%; animation: spin 1s linear infinite;
            "></div>
            <p style="margin-top: 20px; opacity: 0.9;">Confirming destructive operation...</p>
        </div>
    </div>
</div>

<style>
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
@keyframes pulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.05); } }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<script>
window.addEventListener('load', function() {
    setTimeout(function() {
        const splash = document.getElementById('splashScreen');
        if (splash) {
            splash.style.opacity = '0';
            splash.style.visibility = 'hidden';
            setTimeout(function() { splash.style.display = 'none'; }, 800);
        }
    }, 3000);
});
</script>

<div class="setup-container">
<?php
if (isset($_POST['confirm_drop'])) {
    try {
        $dsnServer = "mysql:host=$host;charset=$charset";
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        $pdoServer = new PDO($dsnServer, $user, $pass, $options);
        
        // Check if DB exists
        $stmt = $pdoServer->query("SHOW DATABASES LIKE '$db'");
        if (!$stmt->fetch()) {
            throw new Exception("Database '$db' does not exist.");
        }
        
        // Drop the database
        $pdoServer->exec("DROP DATABASE `$db`");
        
        if ($isCli) {
            echo "Database '$db' dropped successfully.\n";
            echo "Run 'php setup-db.php' to recreate.\n";
            exit(0);
        }
        
        echo "<h2>✅ Database Dropped</h2>";
        echo "<div class='status-item'>Database: <strong>" . htmlspecialchars($db) . "</strong> has been permanently deleted.</div>";
        echo "<div class='warning'>⚠️ All data (employees, attendance, payroll) is LOST.</div>";
        echo "<p><strong>Next:</strong> <a href='setup-db.php' class='btn-login'>Recreate Database</a></p>";
        echo "<p><a href='login.php'>← Back to Login</a></p>";
        showNotification("Database dropped successfully! Recreate with setup-db.php.", "warning");
        
    } catch (Throwable $e) {
        if ($isCli) {
            fwrite(STDERR, "Drop failed: " . $e->getMessage() . "\n");
            exit(1);
        }
        http_response_code(500);
        echo "<h2>❌ Drop Failed</h2>";
        echo "<div class='status-item' style='background: #f8d7da; color: #721c24;'>" . htmlspecialchars($e->getMessage()) . "</div>";
        echo "<p><a href='javascript:history.back()'>← Go Back</a></p>";
        showNotification("Drop failed: " . $e->getMessage(), "error");
    }
} else {
    echo "<h2>⚠️ Drop Database</h2>";
    echo "<div class='warning'>This will PERMANENTLY DELETE the entire <strong>alm_biometrics</strong> database!<br>";
    echo "• All employees, attendance, payroll data will be LOST<br>";
    echo "• Backup first using phpMyAdmin or MySQL Workbench<br>";
    echo "• Only proceed if you want a fresh start</div>";
    
    echo "<form method='POST' style='margin-top: 30px;'>";
    echo "<input type='hidden' name='confirm_drop' value='1'>";
    echo "<p style='font-size: 18px; color: #dc3545; font-weight: bold;'>Type 'DROP NOW' to confirm:</p>";
    echo "<input type='text' name='confirm_text' placeholder='DROP NOW' style='width: 200px; padding: 12px; font-size: 16px; text-transform: uppercase; border: 2px solid #dc3545; border-radius: 6px; margin-bottom: 20px;' required>";
    echo "<button type='submit' class='btn-login' onclick='return confirm(\"FINAL WARNING: This cannot be undone!\")'>💥 DROP DATABASE</button>";
    echo "</form>";
    echo "<p style='margin-top: 20px;'><a href='javascript:history.back()'>← Cancel</a></p>";
}
?>
</div>
</body>
</html>
