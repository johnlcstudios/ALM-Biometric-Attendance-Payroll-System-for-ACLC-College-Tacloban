<?php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;
$company_id = $_SESSION['company_id'] ?? 0;

// Get employee ID from user ID
$stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
$stmt->execute([$user_id]);
$employee_id = $stmt->fetchColumn();

switch ($action) {
    case 'apply_leave':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO leave_requests (company_id, employee_id, type, duration, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$company_id, $employee_id, $data['type'], $data['duration'], $data['reason']]);
        echo json_encode(['success' => true]);
        break;

    case 'apply_loan':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO loans (company_id, employee_id, amount, reason) VALUES (?, ?, ?, ?)");
        $stmt->execute([$company_id, $employee_id, $data['amount'], $data['reason']]);
        echo json_encode(['success' => true]);
        break;

    case 'apply_resignation':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO resignations (company_id, employee_id, reason, effective_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$company_id, $employee_id, $data['reason'], $data['effective_date']]);
        echo json_encode(['success' => true]);
        break;
}
?>