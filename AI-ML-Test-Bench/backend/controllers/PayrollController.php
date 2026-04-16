<?php
// controllers/PayrollController.php

function handle_run_payroll($pdo) {
    if (!isPayrollOrHigher()) sendError(403, 'Unauthorized');
    $data       = json_decode(file_get_contents('php://input'), true);
    $start_date = $data['start_date'] ?? '';
    $end_date   = $data['end_date']   ?? '';
    $category   = $data['category']   ?? 'all';

    $errors = array_merge(validateRequired($data, ['start_date', 'end_date']), validateDateRange($start_date, $end_date));
    rejectInvalidPayload($errors);

    $period              = date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));
    $work_days_in_period = 0;
    $current_date        = new DateTime($start_date);
    $end_date_dt         = new DateTime($end_date);
    while ($current_date <= $end_date_dt) {
        if ($current_date->format('N') < 6) $work_days_in_period++;
        $current_date->add(new DateInterval('P1D'));
    }

    $query  = "SELECT * FROM employees WHERE company_id = ? AND status = 'Active'";
    $params = [$_SESSION['company_id']];
    if ($category !== 'all') { $query .= " AND position = ?"; $params[] = $category; }

    $stmt_employees = $pdo->prepare($query);
    $stmt_employees->execute($params);
    $employees    = $stmt_employees->fetchAll();
    $employee_ids = array_column($employees, 'id');

    $attendance_counts = [];
    if (!empty($employee_ids)) {
        $placeholders = implode(',', array_fill(0, count($employee_ids), '?'));
        $stmt         = $pdo->prepare("SELECT employee_id, COUNT(*) as count FROM attendance WHERE employee_id IN ($placeholders) AND log_date BETWEEN ? AND ? AND check_in IS NOT NULL GROUP BY employee_id");
        $stmt->execute(array_merge($employee_ids, [$start_date, $end_date]));
        while ($row = $stmt->fetch()) $attendance_counts[$row['employee_id']] = (int)$row['count'];
    }

    // Batch fetch approved loans
    $loans_by_emp = [];
    if (!empty($employee_ids)) {
        $placeholders = implode(',', array_fill(0, count($employee_ids), '?'));
        $stmt         = $pdo->prepare("SELECT id, employee_id, amount FROM loans WHERE employee_id IN ($placeholders) AND company_id = ? AND status = 'Approved'");
        $stmt->execute(array_merge($employee_ids, [$_SESSION['company_id']]));
        while ($row = $stmt->fetch()) $loans_by_emp[$row['employee_id']][] = $row;
    }

    $stmt = $pdo->prepare("SELECT * FROM deductions WHERE company_id = ? AND is_active = true");
    $stmt->execute([$_SESSION['company_id']]);
    $deductions_config = $stmt->fetchAll();

    $pdo->beginTransaction();
    try {
        $loan_ids_to_mark_paid = [];
        foreach ($employees as $emp) {
            $days_present   = $attendance_counts[$emp['id']] ?? 0;
            $earned_pay     = ($work_days_in_period > 0) ? ($days_present / $work_days_in_period) * (float)$emp['basic_salary'] : 0;
            $total_deductions = 0;

            foreach ($deductions_config as $d) {
                $d_name = strtoupper($d['name']);
                if (strpos($d_name, 'SSS') !== false       && empty($emp['sss']))       continue;
                if (strpos($d_name, 'PHILHEALTH') !== false && empty($emp['philhealth'])) continue;
                if ((strpos($d_name, 'PAG-IBIG') !== false || strpos($d_name, 'PAGIBIG') !== false) && empty($emp['pagibig'])) continue;
                if ((strpos($d_name, 'TIN') !== false || strpos($d_name, 'TAX') !== false) && empty($emp['tin'])) continue;
                $total_deductions += ($d['type'] === 'percentage') ? $earned_pay * ($d['value'] / 100) : $d['value'];
            }

            foreach ($loans_by_emp[$emp['id']] ?? [] as $loan) {
                $total_deductions     += $loan['amount'];
                $loan_ids_to_mark_paid[] = $loan['id'];
            }

            $net_pay = $earned_pay - $total_deductions;
            $pdo->prepare("REPLACE INTO payroll (company_id, employee_id, period, basic_pay, deductions, net_pay, status, payroll_type) VALUES (?, ?, ?, ?, ?, ?, 'Paid', 'General')")->execute([$_SESSION['company_id'], $emp['id'], $period, $earned_pay, $total_deductions, $net_pay]);
        }

        // Mark loans as paid in batch
        if (!empty($loan_ids_to_mark_paid)) {
            $placeholders = implode(',', array_fill(0, count($loan_ids_to_mark_paid), '?'));
            $pdo->prepare("UPDATE loans SET status = 'Paid' WHERE id IN ($placeholders)")->execute($loan_ids_to_mark_paid);
        }

        $pdo->commit();
        $cat_msg = ($category === 'all') ? 'all employees' : "$category staff";
        echo json_encode(['success' => true, 'message' => "Payroll for $cat_msg during $period run successfully."]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("run_payroll error: " . $e->getMessage());
        sendError(500, 'An error occurred running payroll');
    }
}

function handle_run_specialized_payroll($pdo) {
    if (!isPayrollOrHigher()) sendError(403, 'Unauthorized');
    $data       = json_decode(file_get_contents('php://input'), true);
    $type       = $data['type']       ?? '';
    $start_date = $data['start_date'] ?? '';
    $end_date   = $data['end_date']   ?? '';

    $errors = validateRequired($data, ['type', 'start_date', 'end_date']);
    if (!in_array($type, ['faculty', 'utility'])) $errors[] = 'Invalid payroll type. Must be faculty or utility';
    $errors = array_merge($errors, validateDateRange($start_date, $end_date));
    rejectInvalidPayload($errors);

    $period     = date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));
    $company_id = $_SESSION['company_id'];
    $position   = ($type === 'faculty') ? 'Faculty' : 'Utility';

    $stmt = $pdo->prepare("SELECT deduction_per_min FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company          = $stmt->fetch();
    $deduction_per_min = (float)($company['deduction_per_min'] ?? 0.50);

    $stmt = $pdo->prepare("SELECT * FROM employees WHERE company_id = ? AND position = ? AND status = 'Active'");
    $stmt->execute([$company_id, $position]);
    $employees    = $stmt->fetchAll();
    $employee_ids = array_column($employees, 'id');

    $logs_by_emp = [];
    if (!empty($employee_ids)) {
        $placeholders = implode(',', array_fill(0, count($employee_ids), '?'));
        $stmt         = $pdo->prepare("SELECT employee_id, status, late_minutes, check_in FROM attendance WHERE employee_id IN ($placeholders) AND log_date BETWEEN ? AND ?");
        $stmt->execute(array_merge($employee_ids, [$start_date, $end_date]));
        while ($row = $stmt->fetch()) $logs_by_emp[$row['employee_id']][] = $row;
    }

    $pdo->beginTransaction();
    try {
        foreach ($employees as $emp) {
            $logs           = $logs_by_emp[$emp['id']] ?? [];
            $total_absent   = 0;
            $total_late_min = 0;
            $days_present   = 0;
            foreach ($logs as $l) {
                if ($l['status'] === 'Late')  $total_late_min += (int)$l['late_minutes'];
                if ($l['status'] === 'Absent') $total_absent++;
                if (!empty($l['check_in']))    $days_present++;
            }

            if ($type === 'faculty') {
                $basic_pay          = (float)$emp['basic_salary'] / 2;
                $absences_deduction = $total_absent * ((float)$emp['basic_salary'] / 22);
                $late_ut            = $total_late_min * $deduction_per_min;
                $hdmf_cont          = !empty($emp['pagibig']) ? 100 : 0;
                $total_deduction    = $absences_deduction + $late_ut + $hdmf_cont;
                $net_pay            = $basic_pay - $total_deduction;
                $breakdown = ['absences' => $absences_deduction, 'late_ut' => $late_ut, 'hdmf_cont' => $hdmf_cont, 'total_deduction' => $total_deduction, 'days_present' => $days_present, 'absent_days' => $total_absent, 'late_minutes' => $total_late_min];
                $pdo->prepare("REPLACE INTO payroll (company_id, employee_id, payroll_type, period, basic_pay, deductions, net_pay, breakdown, status) VALUES (?, ?, 'Faculty', ?, ?, ?, ?, ?, 'Paid')")->execute([$company_id, $emp['id'], $period, $basic_pay, $total_deduction, $net_pay, json_encode($breakdown)]);
            } else {
                $rate_per_day    = $emp['basic_salary'] / 22;
                $earned          = $rate_per_day * $days_present;
                $late_ut         = $total_late_min * $deduction_per_min;
                $hdmf_cont       = !empty($emp['pagibig']) ? 100 : 0;
                $total_deduction = $late_ut + $hdmf_cont;
                $net_pay         = $earned - $total_deduction;
                $breakdown = ['rate_per_day' => $rate_per_day, 'earned' => $earned, 'late_ut' => $late_ut, 'hdmf_cont' => $hdmf_cont, 'total_deduction' => $total_deduction, 'days_present' => $days_present, 'absent_days' => $total_absent, 'late_minutes' => $total_late_min];
                $pdo->prepare("REPLACE INTO payroll (company_id, employee_id, payroll_type, period, basic_pay, deductions, net_pay, breakdown, status) VALUES (?, ?, 'Utility', ?, ?, ?, ?, ?, 'Paid')")->execute([$company_id, $emp['id'], $period, $earned, $total_deduction, $net_pay, json_encode($breakdown)]);
            }
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => ucfirst($type) . ' payroll processed successfully', 'period' => $period]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("run_specialized_payroll error: " . $e->getMessage());
        sendError(500, 'An error occurred processing payroll');
    }
}

function handle_get_payroll($pdo) {
    if (!isset($_SESSION['company_id'])) sendError(401, 'Not logged in');
    $stmt = $pdo->prepare("SELECT p.id, p.period, p.basic_pay, p.deductions, p.net_pay, p.status, p.payroll_type, p.created_at, e.full_name FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.company_id = ? ORDER BY p.created_at DESC LIMIT 200");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_get_payslip($pdo) {
    if (!isset($_SESSION['user_id'])) sendError(401, 'Unauthorized');
    $payslip_id = $_GET['id'] ?? '';
    $stmt       = $pdo->prepare("SELECT p.*, e.full_name, e.employee_id as emp_code, e.position, e.department FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.id = ? AND p.company_id = ?");
    $stmt->execute([$payslip_id, $_SESSION['company_id']]);
    $payslip = $stmt->fetch();
    if (!$payslip) sendError(404, 'Payslip not found');
    echo json_encode($payslip);
}

function handle_get_payroll_batches($pdo) {
    if (!isset($_SESSION['company_id'])) sendError(401, 'Not logged in');
    $stmt = $pdo->prepare("SELECT period, SUM(net_pay) as total_disbursed, COUNT(*) as staff_count, MAX(created_at) as processing_date FROM payroll WHERE company_id = ? GROUP BY period ORDER BY processing_date DESC");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_get_faculty_payroll($pdo) {
    if (!isPayrollOrHigher()) sendError(403, 'Unauthorized');
    $period = $_GET['period'] ?? '';
    if ($period === 'latest' || $period === 'current' || empty($period)) {
        $stmt = $pdo->prepare("SELECT period FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.company_id = ? AND p.payroll_type = 'Faculty' AND e.position = 'Faculty' ORDER BY p.created_at DESC LIMIT 1");
        $stmt->execute([$_SESSION['company_id']]);
        $period = $stmt->fetchColumn() ?: '';
    }
    $stmt = $pdo->prepare("SELECT p.*, e.full_name, e.employee_id as emp_code, e.basic_salary FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.company_id = ? AND p.payroll_type = 'Faculty' AND e.position = 'Faculty' AND p.period = ?");
    $stmt->execute([$_SESSION['company_id'], $period]);
    echo json_encode(['period' => $period, 'data' => $stmt->fetchAll()]);
}

function handle_get_utility_payroll($pdo) {
    if (!isPayrollOrHigher()) sendError(403, 'Unauthorized');
    $period = $_GET['period'] ?? '';
    if ($period === 'latest' || $period === 'current' || empty($period)) {
        $stmt = $pdo->prepare("SELECT period FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.company_id = ? AND p.payroll_type = 'Utility' AND e.position = 'Utility' ORDER BY p.created_at DESC LIMIT 1");
        $stmt->execute([$_SESSION['company_id']]);
        $period = $stmt->fetchColumn() ?: '';
    }
    $stmt = $pdo->prepare("SELECT p.*, e.full_name, e.employee_id as emp_code, e.basic_salary FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.company_id = ? AND p.payroll_type = 'Utility' AND e.position = 'Utility' AND p.period = ?");
    $stmt->execute([$_SESSION['company_id'], $period]);
    echo json_encode(['period' => $period, 'data' => $stmt->fetchAll()]);
}
