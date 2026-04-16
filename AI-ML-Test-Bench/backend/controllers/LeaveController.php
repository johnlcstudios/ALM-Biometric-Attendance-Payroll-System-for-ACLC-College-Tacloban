<?php
// controllers/LeaveController.php

function handle_get_leave_requests($pdo) {
    if (!isset($_SESSION['company_id'])) sendError(401, 'Not logged in');
    $stmt = $pdo->prepare("SELECT lr.*, e.full_name FROM leave_requests lr JOIN employees e ON lr.employee_id = e.id WHERE lr.company_id = ? ORDER BY lr.id DESC");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_apply_leave($pdo) {
    if (!isset($_SESSION['user_id'])) sendError(401, 'Unauthorized');
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("SELECT id, company_id FROM employees WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $emp = $stmt->fetch();
    if (!$emp) sendError(404, 'Employee not found');
    try {
        $pdo->prepare("INSERT INTO leave_requests (company_id, employee_id, type, duration, reason, status) VALUES (?, ?, ?, ?, ?, 'Pending')")->execute([$emp['company_id'], $emp['id'], $data['type'], $data['duration'], $data['reason']]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("apply_leave error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}

function handle_update_leave_status($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $data = json_decode(file_get_contents('php://input'), true);
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ? AND company_id = ?")->execute([$data['status'], $data['id'], $_SESSION['company_id']]);
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("update_leave_status error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}
