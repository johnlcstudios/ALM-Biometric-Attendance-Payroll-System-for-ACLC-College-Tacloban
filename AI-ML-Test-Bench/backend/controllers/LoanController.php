<?php
// controllers/LoanController.php

function handle_get_loan_requests($pdo) {
    if (!isset($_SESSION['company_id'])) sendError(401, 'Not logged in');
    $stmt = $pdo->prepare("SELECT l.*, e.full_name FROM loans l JOIN employees e ON l.employee_id = e.id WHERE l.company_id = ? ORDER BY l.id DESC");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_apply_loan($pdo) {
    if (!isset($_SESSION['user_id'])) sendError(401, 'Unauthorized');
    $data   = json_decode(file_get_contents('php://input'), true);
    $errors = array_merge(validateRequired($data, ['amount', 'reason']), validateAmount($data['amount'] ?? '', 'amount', 0.01));
    rejectInvalidPayload($errors);

    $stmt = $pdo->prepare("SELECT id, company_id FROM employees WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $emp = $stmt->fetch();
    if (!$emp) sendError(404, 'Employee not found');

    try {
        $pdo->prepare("INSERT INTO loans (company_id, employee_id, amount, reason, status) VALUES (?, ?, ?, ?, 'Pending')")->execute([$emp['company_id'], $emp['id'], $data['amount'], $data['reason']]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("apply_loan error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}

function handle_update_loan_status($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $data = json_decode(file_get_contents('php://input'), true);
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE loans SET status = ? WHERE id = ? AND company_id = ?")->execute([$data['status'], $data['id'], $_SESSION['company_id']]);
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("update_loan_status error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}

function handle_get_resignation_requests($pdo) {
    if (!isset($_SESSION['company_id'])) sendError(401, 'Not logged in');
    $stmt = $pdo->prepare("SELECT r.*, e.full_name FROM resignations r JOIN employees e ON r.employee_id = e.id WHERE r.company_id = ? ORDER BY r.id DESC");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_update_resignation_status($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $data = json_decode(file_get_contents('php://input'), true);
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE resignations SET status = ? WHERE id = ? AND company_id = ?")->execute([$data['status'], $data['id'], $_SESSION['company_id']]);
        if ($data['status'] === 'Approved' || $data['status'] === 'Completed') {
            $stmt = $pdo->prepare("SELECT employee_id FROM resignations WHERE id = ?");
            $stmt->execute([$data['id']]);
            $employee_id = $stmt->fetchColumn();
            if ($employee_id) $pdo->prepare("UPDATE employees SET status = 'Resigned' WHERE id = ? AND company_id = ?")->execute([$employee_id, $_SESSION['company_id']]);
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("update_resignation_status error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}
