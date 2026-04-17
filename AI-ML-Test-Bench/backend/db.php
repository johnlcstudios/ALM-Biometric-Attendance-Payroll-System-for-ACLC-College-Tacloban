<?php
// db.php - MySQL Connection (XAMPP Default)

// Ensure session starts before any output (only for non-CLI)
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone for the system
date_default_timezone_set('Asia/Manila');

// Override with company-specific timezone if available in session
if (isset($_SESSION['company_id'])) {
    // Note: We don't want to query the DB on every single inclusion of db.php 
    // for performance, but for settings consistency, it might be necessary.
    // However, most pages will fetch company info anyway.
    // A better approach is to set it when needed or cache it in session.
    if (isset($_SESSION['company_timezone'])) {
        date_default_timezone_set($_SESSION['company_timezone']);
    }
}

// Error reporting - avoid leaking details in production
error_reporting(E_ALL);
ini_set('display_errors', 0); 

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    
    // Default generic message for security
    $message = "An unexpected error occurred. Please contact support.";
    
    // Detailed errors ONLY in development (localhost)
    $is_dev = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);
    if ($is_dev) {
        $message = "PHP Error: [$errno] $errstr in $errfile on line $errline";
    }

    // Log the error internally (can be expanded to file logging)
    error_log("[$errno] $errstr in $errfile on line $errline");

    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || 
        (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        // Use SweetAlert2 if possible, otherwise fallback to styled div
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'System Error',
                        text: '" . addslashes($message) . "',
                        confirmButtonColor: '#1e0178'
                    });
                }
            });
        </script>";
        echo "<div id='error-fallback' style='background: #fee; border: 1px solid #f99; padding: 10px; margin: 10px; border-radius: 5px; color: #a33;'>$message</div>";
        echo "<script>document.addEventListener('DOMContentLoaded', function() { if(typeof Swal !== 'undefined') document.getElementById('error-fallback').style.display = 'none'; });</script>";
    }
    exit;
});

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
} else {
    // PORTABILITY: Show helpful message if .env missing
    if (php_sapi_name() !== 'cli' && !isset($_GET['noenv'])) {
        echo '<div style="background:#ffeb3b;padding:15px;border-radius:8px;margin:20px;color:#333;">';
        echo '<strong>🚀 PORTABILITY NOTICE:</strong> Create <code>.env</code> file from <code>.env.example</code><br>';
        echo 'Copy project anywhere → Update DB credentials → Ready!<br>';
        echo '<a href="?noenv=1" style="background:#4caf50;color:white;padding:8px 16px;border-radius:4px;text-decoration:none;">Continue Without .env</a>';
        echo '</div>';
        exit;
    }
}

// Database Configuration - IMPORTANT: Change these in production!
$host = getenv('DB_HOST') ?: 'localhost';
$db = getenv('DB_NAME') ?: 'alm_biometrics';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = new PDO($dsn, $user, $pass, $options);
?>
