<?php
// db.php - MySQL Connection (XAMPP Default)

// Ensure session starts before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
        echo "<div style='background: #fee; border: 1px solid #f99; padding: 10px; margin: 10px; border-radius: 5px; color: #a33;'>$message</div>";
    }
    exit;
});

// Database Configuration - IMPORTANT: Change these in production!
$host = 'localhost';
$db   = 'alm_biometrics';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = new PDO($dsn, $user, $pass, $options);
?>
