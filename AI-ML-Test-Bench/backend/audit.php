<?php
// Audit trail logging helper functions

/**
 * Log an audit event
 * 
 * @param PDO $pdo Database connection
 * @param int $companyId Company ID
 * @param int|null $userId User ID (null for system actions)
 * @param string $action Action performed (e.g., 'login', 'create_employee', 'update_payroll')
 * @param string|null $entityType Type of entity affected (e.g., 'employee', 'payroll', 'user')
 * @param int|null $entityId ID of the entity affected
 * @param array|null $details Additional details as associative array
 */
function logAudit($pdo, $companyId, $userId, $action, $entityType = null, $entityId = null, $details = null) {
    try {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO audit_log (company_id, user_id, action, entity_type, entity_id, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $companyId,
            $userId,
            $action,
            $entityType,
            $entityId,
            $details ? json_encode($details) : null,
            $ipAddress,
            $userAgent
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Audit logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get audit logs with filtering and pagination
 * 
 * @param PDO $pdo Database connection
 * @param int $companyId Company ID
 * @param array $filters Filter options
 * @return array Result with logs and pagination info
 */
function getAuditLogs($pdo, $companyId, $filters = []) {
    $page = $filters['page'] ?? 1;
    $perPage = $filters['per_page'] ?? 50;
    $offset = ($page - 1) * $perPage;
    
    $conditions = ['company_id = ?'];
    $params = [$companyId];
    
    if (!empty($filters['user_id'])) {
        $conditions[] = 'user_id = ?';
        $params[] = $filters['user_id'];
    }
    
    if (!empty($filters['action'])) {
        $conditions[] = 'action = ?';
        $params[] = $filters['action'];
    }
    
    if (!empty($filters['entity_type'])) {
        $conditions[] = 'entity_type = ?';
        $params[] = $filters['entity_type'];
    }
    
    if (!empty($filters['date_from'])) {
        $conditions[] = 'created_at >= ?';
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $conditions[] = 'created_at <= ?';
        $params[] = $filters['date_to'];
    }
    
    $whereClause = implode(' AND ', $conditions);
    
    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log WHERE $whereClause");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    // Get logs
    $logsStmt = $pdo->prepare("
        SELECT al.*, u.username, u.role, e.full_name as employee_name
        FROM audit_log al
        LEFT JOIN users u ON al.user_id = u.id
        LEFT JOIN employees e ON al.entity_type = 'employee' AND al.entity_id = e.id
        WHERE $whereClause
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $perPage;
    $params[] = $offset;
    $logsStmt->execute($params);
    $logs = $logsStmt->fetchAll();
    
    return [
        'logs' => $logs,
        'total' => (int)$total,
        'page' => (int)$page,
        'per_page' => (int)$perPage,
        'total_pages' => ceil($total / $perPage)
    ];
}

/**
 * Common audit actions
 */
define('AUDIT_LOGIN', 'login');
define('AUDIT_LOGOUT', 'logout');
define('AUDIT_FAILED_LOGIN', 'failed_login');
define('AUDIT_CREATE_EMPLOYEE', 'create_employee');
define('AUDIT_UPDATE_EMPLOYEE', 'update_employee');
define('AUDIT_DELETE_EMPLOYEE', 'delete_employee');
define('AUDIT_ENROLL_BIOMETRIC', 'enroll_biometric');
define('AUDIT_RUN_PAYROLL', 'run_payroll');
define('AUDIT_UPDATE_PAYROLL', 'update_payroll');
define('AUDIT_DELETE_PAYROLL', 'delete_payroll');
define('AUDIT_APPROVE_LEAVE', 'approve_leave');
define('AUDIT_REJECT_LEAVE', 'reject_leave');
define('AUDIT_APPROVE_LOAN', 'approve_loan');
define('AUDIT_UPDATE_USER', 'update_user');
define('AUDIT_CHANGE_PASSWORD', 'change_password');
define('AUDIT_RESET_PASSWORD', 'reset_password');
define('AUDIT_CHANGE_ROLE', 'change_role');
define('AUDIT_EXPORT_DATA', 'export_data');
define('AUDIT_IMPORT_EMPLOYEES', 'import_employees');
define('AUDIT_SYSTEM_CONFIG', 'system_config');
?>
