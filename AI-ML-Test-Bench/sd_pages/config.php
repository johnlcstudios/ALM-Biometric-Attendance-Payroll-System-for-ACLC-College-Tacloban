<?php
/**
 * SD Pages Configuration - Uses Main Database Connection
 * Ensures company data isolation through session company_id
 */

// Start session if not already started
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get company_id from session for data isolation
$company_id = $_SESSION['company_id'] ?? 1;
$company_name = $_SESSION['company_name'] ?? 'ACLC College Tacloban';
$full_name = $_SESSION['full_name'] ?? 'HR';

// Include main database connection
require_once __DIR__ . '/../backend/db.php';

// Set default timezone
date_default_timezone_set('Asia/Manila');

// ======================== GLOBAL CONFIGURATION ========================

$config = [
    'institution_name' => 'ACLC College of Tacloban',
    'fiscal_year_start' => '2026-01-01',
    'default_currency' => 'PHP',
    'currency_symbol' => '₱',
    
    // Work Shifts Configuration
    'work_shifts' => [
        'morning' => [
            'name' => 'Morning Shift',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'break_duration' => '1 hour'
        ],
        'afternoon' => [
            'name' => 'Afternoon Shift',
            'start_time' => '14:00',
            'end_time' => '22:00',
            'break_duration' => '1 hour'
        ],
        'night' => [
            'name' => 'Night Shift',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'break_duration' => '1 hour'
        ]
    ],
    
    // Deduction Policies
    'deduction_policies' => [
        'sss' => [
            'name' => 'SSS Contribution',
            'rate' => '4.5%',
            'type' => 'percentage',
            'mandatory' => true
        ],
        'philhealth' => [
            'name' => 'PhilHealth Premium',
            'rate' => '4.0%',
            'type' => 'percentage',
            'mandatory' => true
        ],
        'pagibig' => [
            'name' => 'Pag-IBIG Fund',
            'rate' => '200',
            'type' => 'fixed',
            'mandatory' => true
        ],
        'tax' => [
            'name' => 'Income Tax Withholding',
            'rate' => 'variable',
            'type' => 'percentage',
            'mandatory' => true
        ]
    ],
    
    // Access Control Levels
    'access_levels' => [
        'super_admin' => ['label' => 'Super Administrator', 'level' => 10],
        'admin' => ['label' => 'Administrator', 'level' => 8],
        'hr_manager' => ['label' => 'HR Manager', 'level' => 6],
        'payroll_manager' => ['label' => 'Payroll Manager', 'level' => 6],
        'department_head' => ['label' => 'Department Head', 'level' => 4],
        'supervisor' => ['label' => 'Supervisor', 'level' => 3],
        'employee' => ['label' => 'Employee', 'level' => 1],
        'viewer' => ['label' => 'Viewer', 'level' => 0]
    ]
];

// ======================== HELPER FUNCTIONS ========================

/**
 * Get company settings from database
 */
function getCompanySettings($pdo, $company_id = 1) {
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    return $stmt->fetch();
}

/**
 * Get user by ID
 */
function getUserById($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Format currency
 */
function formatCurrency($amount, $symbol = '₱') {
    return $symbol . number_format($amount, 2);
}

/**
 * Log audit trail
 */
function logAudit($pdo, $action, $user_id, $description, $table_name = null, $record_id = null) {
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (action, user_id, description, table_name, record_id, timestamp, ip_address)
        VALUES (?, ?, ?, ?, ?, NOW(), ?)
    ");
    $stmt->execute([
        $action,
        $user_id,
        $description,
        $table_name,
        $record_id,
        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
    ]);
}

/**
 * Check if user has permission
 */
function hasPermission($pdo, $user_id, $permission) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM user_permissions 
        WHERE user_id = ? AND permission = ? AND is_active = 1
    ");
    $stmt->execute([$user_id, $permission]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get paginated results
 */
function getPaginated($pdo, $query, $params = [], $page = 1, $limit = 10) {
    $offset = ($page - 1) * $limit;
    
    // Get total count
    $countQuery = preg_replace('/^SELECT .*? FROM/i', 'SELECT COUNT(*) as total FROM', $query);
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'] ?? 0;
    
    // Get paginated data
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    return [
        'data' => $data,
        'total' => $total,
        'pages' => ceil($total / $limit),
        'current_page' => $page,
        'limit' => $limit
    ];
}

?>
