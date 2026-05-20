<?php
require_once 'db.php';
require_once 'api_helpers.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    apiError('Unauthorized: Please log in.', [], 401);
}

$action = $_GET['action'] ?? '';

$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];

// Get employee ID from user ID
$stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ? AND company_id = ?");
$stmt->execute([$user_id, $company_id]);
$employee_id = $stmt->fetchColumn();

if (!$employee_id) {
    apiError('Forbidden: Employee record not found.', [], 403);
}

switch ($action) {
    case 'apply_leave':
        $data = json_decode(file_get_contents('php://input'), true);
        $leave_type = $data['leave_type'] ?? '';
        $start_date = $data['start_date'] ?? '';
        $end_date = $data['end_date'] ?? '';
        $reason = $data['reason'] ?? '';

        if (empty($leave_type) || empty($start_date) || empty($end_date) || empty($reason)) {
            apiError('All fields are required.', [], 422);
        }

        $duration = $start_date . ' to ' . $end_date;

        $stmt = $pdo->prepare("INSERT INTO leave_requests (company_id, employee_id, type, duration, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$company_id, $employee_id, $leave_type, $duration, $reason]);
        apiSuccess();
        break;

    case 'apply_loan':
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['amount']) || empty($data['reason'])) {
            apiError('All fields are required.', [], 422);
        }
        $stmt = $pdo->prepare("INSERT INTO loans (company_id, employee_id, amount, reason) VALUES (?, ?, ?, ?)");
        $stmt->execute([$company_id, $employee_id, $data['amount'], $data['reason']]);
        apiSuccess();
        break;

    case 'apply_resignation':
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['reason']) || empty($data['effective_date'])) {
            apiError('All fields are required.', [], 422);
        }
        $stmt = $pdo->prepare("INSERT INTO resignations (company_id, employee_id, reason, effective_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$company_id, $employee_id, $data['reason'], $data['effective_date']]);
        apiSuccess();
        break;

    case 'apply_coe':
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['purpose']) || empty($data['recipient']) || empty($data['requested_date'])) {
            apiError('Purpose, recipient, and request date are required.', [], 422);
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS coe_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            employee_id INT NOT NULL,
            purpose TEXT NOT NULL,
            recipient VARCHAR(255) NOT NULL,
            requested_date DATE NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $pdo->prepare("INSERT INTO coe_requests (company_id, employee_id, purpose, recipient, requested_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$company_id, $employee_id, $data['purpose'], $data['recipient'], $data['requested_date']]);
        apiSuccess();
        break;

    case 'apply_ob':
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['purpose']) || empty($data['destination']) || empty($data['travel_date']) || empty($data['time_out']) || empty($data['time_in']) || empty($data['department_approval'])) {
            apiError('All OB request fields are required.', [], 422);
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS ob_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            employee_id INT NOT NULL,
            purpose TEXT NOT NULL,
            destination VARCHAR(255) NOT NULL,
            travel_date DATE NOT NULL,
            time_out VARCHAR(25) NOT NULL,
            time_in VARCHAR(25) NOT NULL,
            department_approval VARCHAR(50) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $stmt = $pdo->prepare("INSERT INTO ob_requests (company_id, employee_id, purpose, destination, travel_date, time_out, time_in, department_approval) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$company_id, $employee_id, $data['purpose'], $data['destination'], $data['travel_date'], $data['time_out'], $data['time_in'], $data['department_approval']]);
        apiSuccess();
        break;
}
?>