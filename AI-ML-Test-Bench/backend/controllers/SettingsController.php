<?php
// controllers/SettingsController.php

function handle_save_settings($pdo) {
    if (!isPayrollOrHigher()) sendError(403, 'Unauthorized');
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['companyName'])) {
        echo json_encode(['success' => false, 'message' => 'Company Name is required']);
        return;
    }
    try {
        $pdo->prepare("UPDATE companies SET name=?, timezone=?, work_start=?, work_end=?, lunch_out_start=?, lunch_out_end=?, lunch_in_start=?, lunch_in_end=?, lunch_buffer=?, checkout_buffer=?, ot_percentage=?, deduction_per_sec=?, deduction_per_min=?, deduction_per_hour=? WHERE id=?")->execute([
            $data['companyName'],
            $data['timezone']        ?: 'Asia/Manila',
            $data['workStart']       ?: '08:00:00',
            $data['workEnd']         ?: '17:00:00',
            $data['lunchOutStart']   ?: '10:00:00',
            $data['lunchOutEnd']     ?: '10:30:00',
            $data['lunchInStart']    ?: '10:30:00',
            $data['lunchInEnd']      ?: '11:00:00',
            (int)($data['lunchBuffer']     ?? 30),
            (int)($data['checkoutBuffer']  ?? 60),
            (int)($data['otPercentage']    ?? 25),
            (float)($data['deductionPerSec']  ?? 0.0083),
            (float)($data['deductionPerMin']  ?? 0.50),
            (float)($data['deductionPerHour'] ?? 30.00),
            $_SESSION['company_id']
        ]);
        $_SESSION['company_name']     = $data['companyName'];
        $_SESSION['company_timezone'] = $data['timezone'] ?: 'Asia/Manila';
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("save_settings error: " . $e->getMessage());
        sendError(500, 'An error occurred');
    }
}

function handle_get_dashboard_stats($pdo) {
    if (!isset($_SESSION['company_id'])) sendError(401, 'Unauthorized');
    $cid   = $_SESSION['company_id'];
    $today = getServerTime($cid, $pdo)['date'];

    $stmt = $pdo->prepare("SELECT 
        (SELECT COUNT(*) FROM employees WHERE company_id = ?) as total_employees,
        (SELECT COUNT(*) FROM attendance WHERE company_id = ? AND log_date = ? AND check_in IS NOT NULL) as present_today,
        (SELECT COUNT(*) FROM leave_requests WHERE company_id = ? AND status = 'Pending') as pending_leave");
    $stmt->execute([$cid, $cid, $today, $cid]);
    $stats = $stmt->fetch();
    $stats['absent_today'] = $stats['total_employees'] - $stats['present_today'];
    echo json_encode($stats);
}

function handle_get_company_info($pdo) {
    if (!isset($_SESSION['company_id'])) sendError(401, 'Not logged in');
    $id   = $_GET['company_id'] ?? $_SESSION['company_id'];
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$id]);
    $company = $stmt->fetch();
    if (!$company) sendError(404, 'Company not found');
    echo json_encode($company);
}

function handle_get_companies($pdo) {
    $stmt = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC");
    echo json_encode($stmt->fetchAll());
}

function handle_get_server_time($pdo) {
    $cid = $_GET['company_id'] ?? $_SESSION['company_id'] ?? null;
    echo json_encode(getServerTime($cid, $pdo));
}

function handle_get_ess_data($pdo) {
    if (!isset($_SESSION['user_id'])) sendError(401, 'Unauthorized');
    $stmt = $pdo->prepare("SELECT e.*, u.username, u.email as user_email FROM employees e JOIN users u ON e.user_id = u.id WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $emp = $stmt->fetch();
    if (!$emp) sendError(404, 'Profile not found');

    $eid = $emp['id'];

    $stmt_att  = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? ORDER BY log_date DESC LIMIT 30");
    $stmt_att->execute([$eid]);

    $stmt_pay  = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? ORDER BY created_at DESC");
    $stmt_pay->execute([$eid]);

    $stmt_lv   = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY id DESC");
    $stmt_lv->execute([$eid]);

    $stmt_ln   = $pdo->prepare("SELECT * FROM loans WHERE employee_id = ? ORDER BY id DESC");
    $stmt_ln->execute([$eid]);

    $stmt_res  = $pdo->prepare("SELECT * FROM resignations WHERE employee_id = ? ORDER BY id DESC");
    $stmt_res->execute([$eid]);

    echo json_encode([
        'profile'      => $emp,
        'attendance'   => $stmt_att->fetchAll(),
        'payroll'      => $stmt_pay->fetchAll(),
        'leave'        => $stmt_lv->fetchAll(),
        'loans'        => $stmt_ln->fetchAll(),
        'resignations' => $stmt_res->fetchAll()
    ]);
}
