<?php
// db.php - MySQL Connection (XAMPP Default)

// Error reporting to catch errors and return as JSON
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors directly, let the handler catch them
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json')) {
        echo json_encode(['success' => false, 'message' => "PHP Error: [$errno] $errstr in $errfile on line $errline"]);
    } else {
        echo "<div style='background: #fee; border: 1px solid #f99; padding: 10px; margin: 10px;'>PHP Error: [$errno] $errstr in $errfile on line $errline</div>";
    }
    exit;
});

$host = 'localhost';
$db   = 'alm_biometrics';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = new PDO($dsn, $user, $pass, $options);
?>
