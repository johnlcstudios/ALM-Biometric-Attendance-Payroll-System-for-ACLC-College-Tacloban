<?php
// controllers/AllowanceController.php

function handle_get_allowance_categories($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $stmt = $pdo->prepare("SELECT * FROM allowance_categories WHERE company_id = ? ORDER BY name ASC");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_add_allowance_category($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $data = json_decode(file_get_contents('php://input'), true);
    try {
        $pdo->prepare("INSERT INTO allowance_categories (company_id, name, type, rate, description) VALUES (?, ?, ?, ?, ?)")->execute([$_SESSION['company_id'], $data['name'], $data['type'], $data['rate'], $data['description']]);
        echo json_encode(['success' => true, 'message' => 'Category added successfully']);
    } catch (Exception $e) {
        error_log("add_allowance_category error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}

function handle_get_employee_allowances($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $stmt = $pdo->prepare("SELECT ea.*, e.full_name, e.employee_id as emp_code, ac.name as category_name, ac.type as category_type, ac.rate as category_rate FROM employee_allowances ea JOIN employees e ON ea.employee_id = e.id JOIN allowance_categories ac ON ea.category_id = ac.id WHERE ea.company_id = ? ORDER BY ea.created_at DESC");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_assign_employee_allowance($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $data = json_decode(file_get_contents('php://input'), true);
    try {
        $pdo->prepare("INSERT INTO employee_allowances (company_id, employee_id, category_id, override_amount, effective_date) VALUES (?, ?, ?, ?, ?)")->execute([$_SESSION['company_id'], $data['employee_id'], $data['category_id'], $data['override_amount'], $data['effective_date']]);
        echo json_encode(['success' => true, 'message' => 'Allowance assigned successfully']);
    } catch (Exception $e) {
        error_log("assign_employee_allowance error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}

function handle_delete_allowance_category($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $id = $_GET['id'] ?? '';
    $pdo->prepare("DELETE FROM allowance_categories WHERE id = ? AND company_id = ?")->execute([$id, $_SESSION['company_id']]);
    echo json_encode(['success' => true, 'message' => 'Category deleted']);
}

function handle_delete_employee_allowance($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $id = $_GET['id'] ?? '';
    $pdo->prepare("DELETE FROM employee_allowances WHERE id = ? AND company_id = ?")->execute([$id, $_SESSION['company_id']]);
    echo json_encode(['success' => true, 'message' => 'Assignment deleted']);
}

function handle_bulk_assign_allowance($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $data            = json_decode(file_get_contents('php://input'), true);
    $category_id     = $data['category_id'];
    $override_amount = $data['override_amount'] ?: null;
    $effective_date  = $data['effective_date'] ?: getServerTime($_SESSION['company_id'], $pdo)['date'];
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO employee_allowances (company_id, employee_id, category_id, override_amount, effective_date) SELECT ?, id, ?, ?, ? FROM employees WHERE company_id = ? AND status = 'Active' ON DUPLICATE KEY UPDATE override_amount = VALUES(override_amount), effective_date = VALUES(effective_date)")->execute([$_SESSION['company_id'], $category_id, $override_amount, $effective_date, $_SESSION['company_id']]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Allowance applied to all active employees']);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("bulk_assign_allowance error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}

function handle_get_deduction_breakdown($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $stmt = $pdo->prepare("SELECT ed.*, e.full_name, e.employee_id as emp_code, d.name as category_name, d.type as category_type, d.value as category_rate FROM employee_deductions ed JOIN employees e ON ed.employee_id = e.id JOIN deductions d ON ed.deduction_id = d.id WHERE ed.company_id = ? ORDER BY ed.created_at DESC");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_assign_employee_deduction($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $data = json_decode(file_get_contents('php://input'), true);
    try {
        $pdo->prepare("INSERT INTO employee_deductions (company_id, employee_id, deduction_id, override_amount, effective_date) VALUES (?, ?, ?, ?, ?)")->execute([$_SESSION['company_id'], $data['employee_id'], $data['deduction_id'], $data['override_amount'], $data['effective_date']]);
        echo json_encode(['success' => true, 'message' => 'Deduction assigned successfully']);
    } catch (Exception $e) {
        error_log("assign_employee_deduction error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}

function handle_delete_employee_deduction($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $id = $_GET['id'] ?? '';
    $pdo->prepare("DELETE FROM employee_deductions WHERE id = ? AND company_id = ?")->execute([$id, $_SESSION['company_id']]);
    echo json_encode(['success' => true, 'message' => 'Assignment deleted']);
}

function handle_bulk_assign_deduction($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $data            = json_decode(file_get_contents('php://input'), true);
    $deduction_id    = $data['deduction_id'];
    $override_amount = $data['override_amount'] ?: null;
    $effective_date  = $data['effective_date'] ?: getServerTime($_SESSION['company_id'], $pdo)['date'];
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO employee_deductions (company_id, employee_id, deduction_id, override_amount, effective_date) SELECT ?, id, ?, ?, ? FROM employees WHERE company_id = ? AND status = 'Active' ON DUPLICATE KEY UPDATE override_amount = VALUES(override_amount), effective_date = VALUES(effective_date)")->execute([$_SESSION['company_id'], $deduction_id, $override_amount, $effective_date, $_SESSION['company_id']]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Deduction applied to all active employees']);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("bulk_assign_deduction error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}

function handle_get_deduction_categories($pdo) {
    if (!isPayrollOrHigher()) sendError(403, 'Unauthorized');
    $stmt = $pdo->prepare("SELECT * FROM deductions WHERE company_id = ?");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_get_employee_deductions($pdo) {
    if (!isPayrollOrHigher()) sendError(403, 'Unauthorized');
    $stmt = $pdo->prepare("SELECT ed.*, e.full_name, e.employee_id as emp_code, d.name as category_name FROM employee_deductions ed JOIN employees e ON ed.employee_id = e.id JOIN deductions d ON ed.deduction_id = d.id WHERE ed.company_id = ?");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_get_deductions($pdo) {
    if (!isset($_SESSION['company_id'])) sendError(401, 'Not logged in');
    $stmt = $pdo->prepare("SELECT * FROM deductions WHERE company_id = ?");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_save_deduction($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $data = json_decode(file_get_contents('php://input'), true);
    try {
        if (isset($data['id']) && !empty($data['id'])) {
            $pdo->prepare("UPDATE deductions SET name=?, type=?, value=?, is_active=?, is_government=? WHERE id=? AND company_id=?")->execute([$data['name'], $data['type'], $data['value'], $data['is_active'], $data['is_government'], $data['id'], $_SESSION['company_id']]);
        } else {
            $pdo->prepare("INSERT INTO deductions (company_id, name, type, value, is_active, is_government) VALUES (?, ?, ?, ?, ?, ?)")->execute([$_SESSION['company_id'], $data['name'], $data['type'], $data['value'], $data['is_active'], $data['is_government']]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("save_deduction error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}

function handle_delete_deduction($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $id = $_GET['id'] ?? '';
    $pdo->prepare("DELETE FROM deductions WHERE id = ? AND company_id = ?")->execute([$id, $_SESSION['company_id']]);
    echo json_encode(['success' => true]);
}

function handle_revoke_payroll_access($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $id = $_GET['id'] ?? '';
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE employees SET position = 'Staff' WHERE id = ? AND company_id = ?")->execute([$id, $_SESSION['company_id']]);
        $pdo->prepare("UPDATE users u JOIN employees e ON u.id = e.user_id SET u.role = 'Employee' WHERE e.id = ?")->execute([$id]);
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("revoke_payroll_access error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}
