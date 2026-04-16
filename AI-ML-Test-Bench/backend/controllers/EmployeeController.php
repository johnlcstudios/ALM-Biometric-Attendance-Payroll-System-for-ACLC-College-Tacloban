<?php
// controllers/EmployeeController.php

function handle_get_employees($pdo) {
    if (!isset($_SESSION['company_id'])) sendError(401, 'Not logged in');
    $stmt = $pdo->prepare("SELECT e.*, u.username, u.role FROM employees e LEFT JOIN users u ON e.user_id = u.id WHERE e.company_id = ?");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_save_employee($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $data = json_decode(file_get_contents('php://input'), true);

    $errors = validateRequired($data, ['fullName', 'dob', 'email', 'position', 'department', 'basicSalary']);
    $errors = array_merge($errors, validateDate($data['dob'] ?? '', 'dob'));
    $errors = array_merge($errors, validateAmount($data['basicSalary'] ?? '', 'basicSalary', 0));
    if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    rejectInvalidPayload($errors);

    $email        = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $basic_salary = (float)$data['basicSalary'];
    $status       = (isset($data['status']) && trim($data['status']) !== '') ? trim($data['status']) : 'Active';

    if (isset($data['id']) && !empty($data['id'])) {
        $stmt = $pdo->prepare("UPDATE employees SET full_name=?, dob=?, email=?, position=?, department=?, basic_salary=?, sss=?, philhealth=?, tin=?, pagibig=?, status=? WHERE id=? AND company_id=?");
        $stmt->execute([trim($data['fullName']), $data['dob'], $email, $data['position'], $data['department'], $basic_salary, trim($data['sss'] ?? ''), trim($data['philhealth'] ?? ''), trim($data['tin'] ?? ''), trim($data['pagibig'] ?? ''), $status, $data['id'], $_SESSION['company_id']]);
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = ? AND company_id = ?");
            $stmt->execute([$email, $_SESSION['company_id']]);
            if ($stmt->fetch()) throw new Exception("Email already registered in this company.");

            $stmt    = $pdo->prepare("SELECT id FROM employees WHERE company_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$_SESSION['company_id']]);
            $last_id = $stmt->fetchColumn();
            $emp_id  = 'EMP' . str_pad($last_id ? (int)$last_id + 1 : 1, 3, '0', STR_PAD_LEFT);

            $hashed_pass = password_hash('welcome123', PASSWORD_DEFAULT);
            $username    = strtolower(str_replace(' ', '.', trim($data['fullName'])));
            $role        = ($data['position'] === 'Payroll Officer') ? 'Payroll Officer' : 'Employee';

            $stmt = $pdo->prepare("INSERT INTO users (company_id, username, password, role, email) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['company_id'], $username, $hashed_pass, $role, $email]);
            $user_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO employees (company_id, employee_id, full_name, dob, email, position, department, basic_salary, sss, philhealth, tin, pagibig, user_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['company_id'], $emp_id, trim($data['fullName']), $data['dob'], $email, $data['position'], $data['department'], $basic_salary, trim($data['sss'] ?? ''), trim($data['philhealth'] ?? ''), trim($data['tin'] ?? ''), trim($data['pagibig'] ?? ''), $user_id, $status]);
            $new_emp_id = $pdo->lastInsertId();

            if ($data['position'] === 'Faculty' && !empty($data['subjects']) && is_array($data['subjects'])) {
                foreach ($data['subjects'] as $sub) {
                    if (empty($sub['description'])) continue;
                    $stmt = $pdo->prepare("INSERT INTO subject_loads (company_id, faculty_id, code, description, units) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['company_id'], $new_emp_id, 'AUTO', trim($sub['description']), (float)$sub['units']]);
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("save_employee error: " . $e->getMessage());
            sendError(500, 'An error occurred. Please try again.');
        }
    }
    echo json_encode(['success' => true]);
}

function handle_delete_employee($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $id = $_GET['id'] ?? '';
    rejectInvalidPayload(validateId($id, 'id'));
    $pdo->prepare("DELETE FROM employees WHERE id = ? AND company_id = ?")->execute([$id, $_SESSION['company_id']]);
    echo json_encode(['success' => true]);
}

function handle_get_enrolled_faces($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $stmt = $pdo->prepare("SELECT id, full_name, face_descriptor FROM employees WHERE company_id = ? AND face_descriptor IS NOT NULL");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode(['success' => true, 'faces' => $stmt->fetchAll()]);
}

function handle_save_face_descriptor($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id']) || empty($data['descriptor'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }

    $stmt = $pdo->prepare("SELECT id, full_name, face_descriptor FROM employees WHERE company_id = ? AND face_descriptor IS NOT NULL AND id != ?");
    $stmt->execute([$_SESSION['company_id'], $data['id']]);
    $existing_faces   = $stmt->fetchAll();
    $new_descriptor   = array_map('floatval', $data['descriptor']);

    foreach ($existing_faces as $face) {
        $enrolled = json_decode($face['face_descriptor'], true);
        if (is_array($enrolled) && count($enrolled) === 128) {
            $sum = 0;
            for ($i = 0; $i < 128; $i++) {
                $diff = $new_descriptor[$i] - (float)$enrolled[$i];
                $sum += $diff * $diff;
            }
            if (sqrt($sum) < BIOMETRIC_DUPLICATE_THRESHOLD) {
                echo json_encode(['success' => false, 'message' => 'This face is already registered to ' . $face['full_name']]);
                return;
            }
        }
    }

    $pdo->prepare("UPDATE employees SET face_descriptor = ? WHERE id = ? AND company_id = ?")->execute([json_encode($new_descriptor), $data['id'], $_SESSION['company_id']]);
    echo json_encode(['success' => true]);
}

function handle_get_employee_biometric_status($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $id   = $_GET['id'] ?? '';
    $stmt = $pdo->prepare("SELECT id, (face_descriptor IS NOT NULL) as enrolled, enrolled_at FROM employees WHERE id = ? AND company_id = ?");
    $stmt->execute([$id, $_SESSION['company_id']]);
    $status = $stmt->fetch();
    if (!$status) sendError(404, 'Employee not found');
    echo json_encode(['success' => true, 'enrolled' => (bool)$status['enrolled'], 'enrolled_at' => $status['enrolled_at'] ? date('M d, Y h:i A', strtotime($status['enrolled_at'])) : 'N/A']);
}

function handle_update_role($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $id   = $_GET['id']   ?? '';
    $role = $_GET['role'] ?? 'Employee';
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM employees WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, $_SESSION['company_id']]);
        $user_id = $stmt->fetchColumn();
        if (!$user_id) throw new Exception('User not found');
        $pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND company_id = ?")->execute([$role, $user_id, $_SESSION['company_id']]);
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("update_role error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}

function handle_update_leave_balance($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $employee_id = $_GET['employee_id'] ?? '';
    $balance     = $_GET['balance']     ?? 0;
    $pdo->prepare("UPDATE employees SET leave_balance = ? WHERE id = ? AND company_id = ?")->execute([$balance, $employee_id, $_SESSION['company_id']]);
    echo json_encode(['success' => true]);
}

function handle_bulk_update_leave_balance($pdo) {
    if (!isAdminOrHR()) sendError(403, 'Unauthorized');
    $data    = json_decode(file_get_contents('php://input'), true);
    $balance = isset($data['balance']) ? (float)$data['balance'] : null;
    if ($balance === null || $balance < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid leave balance']);
        return;
    }
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE employees SET leave_balance = ? WHERE company_id = ? AND status = 'Active'")->execute([$balance, $_SESSION['company_id']]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Leave balance applied to all active employees']);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("bulk_update_leave_balance error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}
