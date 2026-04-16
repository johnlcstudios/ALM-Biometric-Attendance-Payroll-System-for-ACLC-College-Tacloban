<?php
// controllers/AttendanceController.php

function handle_get_attendance($pdo) {
    if (!isset($_SESSION['company_id'])) sendError(401, 'Not logged in');
    $stmt = $pdo->prepare("SELECT a.id, a.log_date, a.check_in, a.lunch_out, a.lunch_in, a.check_out, a.status, a.late_minutes, e.full_name, e.employee_id as emp_code FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.company_id = ? ORDER BY a.log_date DESC, a.check_in DESC LIMIT 200");
    $stmt->execute([$_SESSION['company_id']]);
    echo json_encode($stmt->fetchAll());
}

function handle_kiosk_scan($pdo) {
    checkRateLimit('kiosk_scan');
    $data       = json_decode(file_get_contents('php://input'), true);
    $company_id = $data['company_id'] ?? null;

    if (!$company_id) {
        echo json_encode(['success' => false, 'message' => 'Company ID is required']);
        return;
    }

    $descriptor = $data['descriptor'] ?? [];
    if (empty($descriptor) || count($descriptor) !== 128) {
        echo json_encode(['success' => false, 'message' => 'Invalid descriptor']);
        return;
    }

    // Fetch company config + timezone in one query
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $config = $stmt->fetch();
    if (!$config) {
        echo json_encode(['success' => false, 'message' => 'Company not found']);
        return;
    }

    $company_tz = $config['timezone'] ?: 'UTC';
    date_default_timezone_set($company_tz);

    $match_threshold           = (float)($config['biometric_match_threshold'] ?? BIOMETRIC_MATCH_THRESHOLD);
    $ambiguity_ratio_threshold = (float)($config['biometric_ambiguity_ratio']  ?? BIOMETRIC_AMBIGUITY_RATIO);

    // Fetch enrolled faces
    $stmt = $pdo->prepare("SELECT id, full_name, face_descriptor FROM employees WHERE company_id = ? AND face_descriptor IS NOT NULL AND status = 'Active'");
    $stmt->execute([$company_id]);
    $enrolled_faces = $stmt->fetchAll();

    $best_match           = null;
    $best_distance        = 999;
    $second_best_distance = 999;
    $input_desc           = array_map('floatval', $descriptor);

    foreach ($enrolled_faces as $face) {
        $enrolled_desc = json_decode($face['face_descriptor'], true);
        if (is_array($enrolled_desc) && count($enrolled_desc) === 128) {
            $sum = 0;
            for ($i = 0; $i < 128; $i++) {
                $diff = $input_desc[$i] - (float)$enrolled_desc[$i];
                $sum += $diff * $diff;
            }
            $distance = sqrt($sum);
            if ($distance < $best_distance) {
                $second_best_distance = $best_distance;
                $best_distance        = $distance;
                $best_match           = $face;
            } elseif ($distance < $second_best_distance) {
                $second_best_distance = $distance;
            }
        }
    }

    $match_percentage = $best_distance < 999 ? max(0, round(100 - ($best_distance * 125), 2)) : 0;

    if (!$best_match || $best_distance > $match_threshold) {
        echo json_encode(['success' => false, 'message' => 'Face not recognized. Please position yourself clearly.', 'match_percentage' => $match_percentage]);
        return;
    }

    if ($second_best_distance < 999) {
        $ratio = ($best_distance > 0) ? ($second_best_distance / $best_distance) : 999;
        if ($ratio <= $ambiguity_ratio_threshold) {
            echo json_encode(['success' => false, 'message' => 'Ambiguous match. Please try again.']);
            return;
        }
    }

    $employee_id    = $best_match['id'];
    $scan_time_input = $data['scan_time'] ?? null;

    if ($scan_time_input) {
        try {
            $dt = new DateTime($scan_time_input);
            if (strpos($scan_time_input, '+') !== false || strpos($scan_time_input, 'Z') !== false) {
                $dt->setTimezone(new DateTimeZone($company_tz));
            } else {
                $dt = new DateTime($scan_time_input, new DateTimeZone($company_tz));
            }
            $date = $dt->format('Y-m-d');
            $time = $dt->format('H:i:s');
        } catch (Exception $e) {
            $date = date('Y-m-d');
            $time = date('H:i:s');
        }
    } else {
        $date = date('Y-m-d');
        $time = date('H:i:s');
    }

    $stmt = $pdo->prepare("SELECT id, employee_id, position, created_at FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $emp_data = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND log_date = ?");
    $stmt->execute([$employee_id, $date]);
    $log = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT COUNT(*) as total_attendance FROM attendance WHERE employee_id = ? AND check_in IS NOT NULL");
    $stmt->execute([$employee_id]);
    $stats = $stmt->fetch();

    // Absence calculation
    $joined    = new DateTime($emp_data['created_at']);
    $today_dt  = new DateTime();
    $days_diff = $joined->diff($today_dt)->days;
    $work_days = 0;
    $temp_date = clone $joined;
    for ($i = 0; $i <= $days_diff; $i++) {
        if ($temp_date->format('N') < 6) $work_days++;
        $temp_date->modify('+1 day');
    }
    $absent_count = max(0, $work_days - $stats['total_attendance']);

    $common_data = [
        'name'             => $best_match['full_name'],
        'employee_id'      => $emp_data['employee_id'],
        'position'         => $emp_data['position'],
        'attendance_count' => $stats['total_attendance'],
        'absent_count'     => $absent_count,
        'match_percentage' => $match_percentage
    ];

    $status       = $log ? ($log['status'] ?? 'On-Time') : 'On-Time';
    $late_minutes = $log ? ($log['late_minutes'] ?? 0) : 0;
    $column       = '';

    $work_start     = $config['work_start']     ?: '08:00:00';
    $work_end       = $config['work_end']       ?: '17:00:00';
    $lunch_out_start = $config['lunch_out_start'] ?: '10:00:00';
    $lunch_out_end  = $config['lunch_out_end']  ?: '10:30:00';
    $lunch_in_start = $config['lunch_in_start'] ?: '10:30:00';
    $lunch_in_end   = $config['lunch_in_end']   ?: '11:00:00';
    $lunch_buffer   = $config['lunch_buffer']   ?? 30;
    $checkout_buffer = $config['checkout_buffer'] ?? 60;
    $grace_period   = 15;

    // Determine column
    if (!$log || empty($log['check_in'])) {
        $column = 'check_in';
    } elseif (empty($log['lunch_out'])) {
        $column = ($time > $lunch_out_end) ? 'check_out' : 'lunch_out';
    } elseif (empty($log['lunch_in'])) {
        $column = ($time > $lunch_in_end) ? 'check_out' : 'lunch_in';
    } else {
        $column = 'check_out';
    }

    // Time window enforcement
    if ($column === 'lunch_out') {
        if ($time < $lunch_out_start) { echo json_encode(array_merge(['success' => false, 'message' => 'TOO EARLY FOR LUNCH OUT', 'action' => $column], $common_data)); return; }
        if ($time > $lunch_out_end)   { echo json_encode(array_merge(['success' => false, 'message' => 'LUNCH OUT RANGE EXPIRED',  'action' => $column], $common_data)); return; }
    }
    if ($column === 'lunch_in') {
        if ($time < $lunch_in_start) { echo json_encode(array_merge(['success' => false, 'message' => 'TOO EARLY FOR LUNCH IN', 'action' => $column], $common_data)); return; }
        if ($time > $lunch_in_end)   { echo json_encode(array_merge(['success' => false, 'message' => 'LUNCH IN RANGE EXPIRED',  'action' => $column], $common_data)); return; }
        if ($log && !empty($log['lunch_out'])) {
            $diff_minutes = round((strtotime($time) - strtotime($log['lunch_out'])) / 60);
            if ($diff_minutes < $lunch_buffer) { echo json_encode(array_merge(['success' => false, 'message' => "LUNCH BUFFER: Wait " . ($lunch_buffer - $diff_minutes) . " more mins.", 'action' => $column], $common_data)); return; }
        }
    }
    if ($column === 'check_out') {
        if ($time < $work_end) { echo json_encode(array_merge(['success' => false, 'message' => 'TOO EARLY FOR TIME OUT', 'action' => $column], $common_data)); return; }
        if ($log && !empty($log['lunch_in'])) {
            $diff_minutes = round((strtotime($time) - strtotime($log['lunch_in'])) / 60);
            if ($diff_minutes < $checkout_buffer) { echo json_encode(array_merge(['success' => false, 'message' => "TIME OUT BUFFER: Wait " . ($checkout_buffer - $diff_minutes) . " more mins.", 'action' => $column], $common_data)); return; }
        }
    }

    // Late check
    if ($column === 'check_in') {
        $late_time = date('H:i:s', strtotime($work_start . " + $grace_period minutes"));
        if ($time > $late_time) {
            $status       = 'Late';
            $late_minutes = max(0, floor((strtotime($time) - strtotime($work_start)) / 60));
        }
    }

    if ($log && !empty($log['check_out'])) { echo json_encode(array_merge(['success' => false, 'message' => 'ALREADY CHECKED OUT FOR TODAY', 'action' => 'check_out'], $common_data)); return; }
    if ($log && !empty($log[$column]))     { echo json_encode(array_merge(['success' => false, 'message' => 'ALREADY LOGGED', 'action' => $column], $common_data)); return; }

    if ($column === 'lunch_out' && (!$log || empty($log['check_in']))) { echo json_encode(array_merge(['success' => false, 'message' => 'MUST CHECK-IN FIRST',    'action' => $column], $common_data)); return; }
    if ($column === 'lunch_in'  && (!$log || empty($log['lunch_out']))) { echo json_encode(array_merge(['success' => false, 'message' => 'MUST LUNCH-OUT FIRST', 'action' => $column], $common_data)); return; }
    if ($column === 'check_out' && (!$log || (empty($log['check_in']) && empty($log['lunch_in'])))) { echo json_encode(array_merge(['success' => false, 'message' => 'MUST LOG-IN FIRST', 'action' => $column], $common_data)); return; }

    if ($column === 'check_out' && $log && (empty($log['check_in']) || empty($log['lunch_in']))) $status = 'Half-Day';

    if (!$log) {
        $pdo->prepare("INSERT INTO attendance (company_id, employee_id, log_date, $column, status, late_minutes) VALUES (?, ?, ?, ?, ?, ?)")->execute([$company_id, $employee_id, $date, $time, $status, $late_minutes]);
    } else {
        $pdo->prepare("UPDATE attendance SET $column = ?, status = ?, late_minutes = ? WHERE id = ?")->execute([$time, $status, $late_minutes, $log['id']]);
    }

    $labels = ['check_in' => 'TIME IN', 'lunch_out' => 'LUNCH OUT', 'lunch_in' => 'LUNCH IN', 'check_out' => 'TIME OUT'];
    echo json_encode(array_merge([
        'success'        => true,
        'action'         => $labels[$column] ?? strtoupper($column),
        'time'           => date('h:i A', strtotime($time)),
        'status'         => $status,
        'late_minutes'   => $late_minutes,
        'missed_morning' => (!$log || empty($log['check_in'])) && $time > $lunch_out_start
    ], $common_data));
}
