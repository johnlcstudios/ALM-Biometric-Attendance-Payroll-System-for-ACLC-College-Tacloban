<?php
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in.']);
    exit;
}

$action = $_GET['action'] ?? '';

$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];

// Get employee ID from user ID
$stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ? AND company_id = ?");
$stmt->execute([$user_id, $company_id]);
$employee_id = $stmt->fetchColumn();

if (!$employee_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Employee record not found.']);
    exit;
}

switch ($action) {
    case 'apply_leave':
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['type']) || empty($data['duration']) || empty($data['reason'])) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            break;
        }
        $stmt = $pdo->prepare("INSERT INTO leave_requests (company_id, employee_id, type, duration, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$company_id, $employee_id, $data['type'], $data['duration'], $data['reason']]);
        echo json_encode(['success' => true]);
        break;

    case 'apply_loan':
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['amount']) || empty($data['reason'])) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            break;
        }
        $stmt = $pdo->prepare("INSERT INTO loans (company_id, employee_id, amount, reason) VALUES (?, ?, ?, ?)");
        $stmt->execute([$company_id, $employee_id, $data['amount'], $data['reason']]);
        echo json_encode(['success' => true]);
        break;

    case 'apply_resignation':
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['reason']) || empty($data['effective_date'])) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            break;
        }
        $stmt = $pdo->prepare("INSERT INTO resignations (company_id, employee_id, reason, effective_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$company_id, $employee_id, $data['reason'], $data['effective_date']]);
        echo json_encode(['success' => true]);
        break;
}
?>