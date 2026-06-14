<?php
// API v2 - Enhanced version with pagination and improved features
// This file demonstrates the API versioning structure

header('Content-Type: application/json');

// Load dependencies
require_once 'db.php';
require_once 'api_helpers.php';
require_once 'audit.php';

$action = $_GET['action'] ?? '';

// Pagination helper function
function getPaginationParams() {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(10, (int)($_GET['per_page'] ?? 20)));
    $offset = ($page - 1) * $perPage;
    
    return [
        'page' => $page,
        'per_page' => $perPage,
        'offset' => $offset
    ];
}

// Enhanced response with pagination
function paginatedResponse($data, $total, $page, $perPage) {
    return [
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage),
            'has_next' => ($page * $perPage) < $total,
            'has_prev' => $page > 1
        ]
    ];
}

try {
    switch ($action) {
        case 'get_employees':
            if (!isset($_SESSION['company_id'])) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            
            $pagination = getPaginationParams();
            $search = $_GET['search'] ?? '';
            $department = $_GET['department'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $conditions = ['e.company_id = ?'];
            $params = [$_SESSION['company_id']];
            
            if ($search) {
                $conditions[] = '(e.full_name LIKE ? OR e.employee_id LIKE ? OR e.email LIKE ?)';
                $searchParam = "%$search%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
            }
            
            if ($department) {
                $conditions[] = 'e.department = ?';
                $params[] = $department;
            }
            
            if ($status) {
                $conditions[] = 'e.status = ?';
                $params[] = $status;
            }
            
            $whereClause = implode(' AND ', $conditions);
            
            // Get total count
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM employees e WHERE $whereClause");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            // Get paginated data
            $stmt = $pdo->prepare("
                SELECT e.*, u.username, u.email as user_email
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                WHERE $whereClause
                ORDER BY e.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $pagination['per_page'];
            $params[] = $pagination['offset'];
            $stmt->execute($params);
            $employees = $stmt->fetchAll();
            
            echo json_encode(paginatedResponse(
                $employees,
                $total,
                $pagination['page'],
                $pagination['per_page']
            ));
            break;
            
        case 'get_attendance':
            if (!isset($_SESSION['company_id'])) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            
            $pagination = getPaginationParams();
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $employeeId = $_GET['employee_id'] ?? '';
            
            $conditions = ['a.company_id = ?'];
            $params = [$_SESSION['company_id']];
            
            $conditions[] = 'a.log_date BETWEEN ? AND ?';
            $params[] = $startDate;
            $params[] = $endDate;
            
            if ($employeeId) {
                $conditions[] = 'a.employee_id = ?';
                $params[] = $employeeId;
            }
            
            $whereClause = implode(' AND ', $conditions);
            
            // Get total count
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance a WHERE $whereClause");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();
            
            // Get paginated data
            $stmt = $pdo->prepare("
                SELECT a.*, e.full_name, e.employee_id as emp_code
                FROM attendance a
                JOIN employees e ON a.employee_id = e.id
                WHERE $whereClause
                ORDER BY a.log_date DESC, a.check_in DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $pagination['per_page'];
            $params[] = $pagination['offset'];
            $stmt->execute($params);
            $attendance = $stmt->fetchAll();
            
            echo json_encode(paginatedResponse(
                $attendance,
                $total,
                $pagination['page'],
                $pagination['per_page']
            ));
            break;
            
        case 'get_audit_logs':
            if (!isset($_SESSION['company_id']) || ($_SESSION['role'] !== 'HR' && $_SESSION['role'] !== 'Admin')) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            
            $pagination = getPaginationParams();
            $filters = [
                'page' => $pagination['page'],
                'per_page' => $pagination['per_page'],
                'user_id' => $_GET['user_id'] ?? null,
                'action' => $_GET['action_filter'] ?? null,
                'entity_type' => $_GET['entity_type'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];
            
            $result = getAuditLogs($pdo, $_SESSION['company_id'], $filters);
            
            echo json_encode(paginatedResponse(
                $result['logs'],
                $result['total'],
                $result['page'],
                $result['per_page']
            ));
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
