<?php
// api.php - Core Backend Logic
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once 'db.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

// Helper to check for HR or Admin role
function isAdminOrHR() {
    return isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['HR', 'Admin']);
}

// Helper to check for Payroll role (includes Admin/HR)
function isPayrollOrHigher() {
    return isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['HR', 'Admin', 'Payroll']);
}

try {
    switch ($action) {
        case 'login':
            $data = json_decode(file_get_contents('php://input'), true);
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

            if (empty($username) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Username and password are required']);
                break;
            }

            $stmt = $pdo->prepare("SELECT u.*, c.name as company_name FROM users u JOIN companies c ON u.company_id = c.id WHERE u.username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['company_id'] = $user['company_id'];
                $_SESSION['role'] = trim($user['role']);
                $_SESSION['company_name'] = $user['company_name'];
                echo json_encode(['success' => true, 'role' => trim($user['role']), 'company_name' => $user['company_name']]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            }
            break;

        case 'signup':
            $data = json_decode(file_get_contents('php://input'), true);
            $company_name = $data['company_name'] ?? '';
            $username = $data['username'] ?? '';
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            if (empty($company_name) || empty($username) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                break;
            }

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO companies (name, admin_email) VALUES (?, ?)");
                $stmt->execute([$company_name, $email]);
                $company_id = $pdo->lastInsertId();

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (company_id, username, password, role, email) VALUES (?, ?, ?, 'HR', ?)");
                $stmt->execute([$company_id, $username, $hashed_password, $email]);

                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Signup failed: ' . $e->getMessage()]);
            }
            break;

        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            break;

        case 'get_employees':
            if (!isset($_SESSION['company_id'])) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT e.*, u.username, u.role FROM employees e LEFT JOIN users u ON e.user_id = u.id WHERE e.company_id = ?");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'save_employee':
            if (!isAdminOrHR()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($data['id']) && !empty($data['id'])) {
                // Update existing employee
                $stmt = $pdo->prepare("UPDATE employees SET full_name = ?, dob = ?, email = ?, position = ?, department = ?, basic_salary = ?, sss = ?, philhealth = ?, tin = ?, pagibig = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([$data['fullName'], $data['dob'], $data['email'], $data['position'], $data['department'], $data['basicSalary'], $data['sss'], $data['philhealth'], $data['tin'], $data['pagibig'], $data['id'], $_SESSION['company_id']]);
            } else {
                // Create new employee
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("SELECT id FROM employees WHERE company_id = ? ORDER BY id DESC LIMIT 1");
                    $stmt->execute([$_SESSION['company_id']]);
                    $last_id = $stmt->fetchColumn();
                    $num = $last_id ? (int)$last_id + 1 : 1;
                    $emp_id = 'EMP' . str_pad($num, 3, '0', STR_PAD_LEFT);

                    $hashed_pass = password_hash('welcome123', PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (company_id, username, password, role, email) VALUES (?, ?, ?, 'Employee', ?)");
                    $stmt->execute([$_SESSION['company_id'], strtolower(str_replace(' ', '.', $data['fullName'])), $hashed_pass, $data['email']]);
                    $user_id = $pdo->lastInsertId();

                    $stmt = $pdo->prepare("INSERT INTO employees (company_id, employee_id, full_name, dob, email, position, department, basic_salary, sss, philhealth, tin, pagibig, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['company_id'], $emp_id, $data['fullName'], $data['dob'], $data['email'], $data['position'], $data['department'], $data['basicSalary'], $data['sss'], $data['philhealth'], $data['tin'], $data['pagibig'], $user_id]);
                    $pdo->commit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    exit(json_encode(['success' => false, 'message' => 'Failed to save employee: ' . $e->getMessage()]));
                }
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_employee':
            if (!isAdminOrHR()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = $_GET['id'] ?? '';
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'save_face_descriptor':
            if (!isAdminOrHR()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['id']) || empty($data['descriptor'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                break;
            }

            // Check for duplicate face within the same company
            $stmt_faces = $pdo->prepare("SELECT id, full_name, face_descriptor FROM employees WHERE company_id = ? AND face_descriptor IS NOT NULL AND id != ?");
            $stmt_faces->execute([$_SESSION['company_id'], $data['id']]);
            $existing_faces = $stmt_faces->fetchAll();

            $new_descriptor = $data['descriptor'];
            foreach ($existing_faces as $face) {
                $enrolled_descriptor = json_decode($face['face_descriptor'], true);
                if (is_array($enrolled_descriptor) && count($enrolled_descriptor) === 128) {
                    $sum = 0;
                    for ($i = 0; $i < 128; $i++) {
                        $sum += pow((float)$new_descriptor[$i] - (float)$enrolled_descriptor[$i], 2);
                    }
                    $distance = sqrt($sum);
                    if ($distance < 0.45) { // Strict threshold for duplicates (approx 90% match)
                        echo json_encode(['success' => false, 'message' => "This face is already registered to " . $face['full_name']]);
                        return;
                    }
                }
            }

            $stmt = $pdo->prepare("UPDATE employees SET face_descriptor = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([json_encode($data['descriptor']), $data['id'], $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'reset_password':
            if (!isAdminOrHR()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $target_user_id = $_GET['user_id'] ?? '';
            
            // Security: Ensure target user belongs to the same company
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND company_id = ?");
            $stmt->execute([$target_user_id, $_SESSION['company_id']]);
            if (!$stmt->fetch()) {
                exit(json_encode(['success' => false, 'message' => 'User not found or access denied']));
            }

            $new_pass = password_hash('welcome123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$new_pass, $target_user_id, $_SESSION['company_id']]);
            echo json_encode(['success' => true, 'message' => 'Password reset to welcome123']);
            break;

        case 'get_attendance':
            if (!isset($_SESSION['company_id'])) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT a.*, e.full_name, e.employee_id as emp_code FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.company_id = ? ORDER BY a.log_date DESC, a.check_in DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'kiosk_scan':
            $data = json_decode(file_get_contents('php://input'), true);
            $company_id = $data['company_id'] ?? 1;
            $descriptor = $data['descriptor'] ?? [];

            if (empty($descriptor) || count($descriptor) !== 128) {
                 echo json_encode(['success' => false, 'message' => 'Invalid descriptor (Expected 128 floats)']);
                 break;
            }

            // Fetch all enrolled and ACTIVE faces for the company
            $stmt_faces = $pdo->prepare("SELECT id, full_name, face_descriptor FROM employees WHERE company_id = ? AND face_descriptor IS NOT NULL AND status = 'Active'");
            $stmt_faces->execute([$company_id]);
            $enrolled_faces = $stmt_faces->fetchAll();

            $best_match = null;
            $best_distance = 9.9;
            $faces_checked = 0;

            foreach ($enrolled_faces as $face) {
                $enrolled_descriptor = json_decode($face['face_descriptor'], true);
                if (is_array($enrolled_descriptor) && count($enrolled_descriptor) === 128) {
                    $faces_checked++;
                    $sum = 0;
                    for ($i = 0; $i < 128; $i++) {
                        $diff = (float)$descriptor[$i] - (float)$enrolled_descriptor[$i];
                        $sum += $diff * $diff;
                    }

                    if ($sum < $best_distance) {
                        $best_distance = $sum;
                        $best_match = $face;
                    }
                }
            }

            $final_distance = sqrt($best_match ? $best_distance : 9.9);
            $match_percentage = max(0, round(100 - ($final_distance * 25), 2));

            if ($best_match && $match_percentage >= 90) {
                $employee_id = $best_match['id'];
                $date = date('Y-m-d');
                $time = date('H:i:s');

                $stmt_emp = $pdo->prepare("SELECT employee_id, position FROM employees WHERE id = ?");
                $stmt_emp->execute([$employee_id]);
                $emp_data = $stmt_emp->fetch();

                $stmt_config = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
                $stmt_config->execute([$company_id]);
                $config = $stmt_config->fetch();

                $stmt_log = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND log_date = ?");
                $stmt_log->execute([$employee_id, $date]);
                $log = $stmt_log->fetch();

                $missed_morning = (!$log || empty($log['check_in'])) && $time > date('H:i:s', strtotime($config['work_start'] . ' + 4 hours'));
                $status = 'On-Time';
                $column = '';
                
                $work_start = $config['work_start'];
                $grace_period = $config['grace_period'] ?? 15;
                $late_time = date('H:i:s', strtotime($work_start . " + $grace_period minutes"));
                
                if ($time >= date('H:i:s', strtotime($config['work_end'] . ' - 30 minutes'))) {
                    $column = 'check_out';
                } elseif ($time <= date('H:i:s', strtotime($work_start . ' + 2 hours'))) {
                    $column = 'check_in';
                    if ($time > $late_time) $status = 'Late';
                } elseif ($time >= $config['lunch_out_start'] && $time <= $config['lunch_out_end']) {
                    $column = 'lunch_out';
                } elseif ($time >= $config['lunch_in_start'] && $time <= $config['lunch_in_end']) {
                    $column = 'lunch_in';
                } else {
                    $column = 'check_out';
                }

                // Entry point requirements
                if ($column === 'lunch_out' && (!$log || empty($log['check_in']))) {
                    echo json_encode(['success' => false, 'message' => 'MUST CHECK-IN FIRST', 'name' => $best_match['full_name']]);
                    break;
                }
                if ($column === 'check_out' && (!$log || (empty($log['check_in']) && empty($log['lunch_in'])))) {
                    echo json_encode(['success' => false, 'message' => 'MUST LOG-IN FIRST', 'name' => $best_match['full_name']]);
                    break;
                }

                if ($log && !empty($log[$column])) {
                    echo json_encode(['success' => false, 'message' => 'ALREADY LOGGED', 'name' => $best_match['full_name']]);
                    break;
                }

                if (!$log) {
                    $stmt = $pdo->prepare("INSERT INTO attendance (company_id, employee_id, log_date, $column, status) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$company_id, $employee_id, $date, $time, $status]);
                } else {
                    if ($column === 'check_out') {
                        if (empty($log['check_in']) && !empty($log['lunch_in'])) $status = 'Half-Day';
                        else if (!empty($log['check_in']) && empty($log['lunch_in'])) $status = 'Half-Day';
                        else if (empty($log['check_in'])) $status = 'Absent';
                    }
                    $stmt = $pdo->prepare("UPDATE attendance SET $column = ?, status = ? WHERE id = ?");
                    $stmt->execute([$time, $status, $log['id']]);
                }

                $stmt_stats = $pdo->prepare("SELECT 
                    (SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND check_in IS NOT NULL) as total_attendance,
                    (SELECT created_at FROM employees WHERE id = ?) as joined_date
                ");
                $stmt_stats->execute([$employee_id, $employee_id]);
                $stats = $stmt_stats->fetch();

                // Calculate Absences: Days since joining - total attendance (excluding weekends)
                $joined = new DateTime($stats['joined_date']);
                $today_dt = new DateTime();
                $interval = $joined->diff($today_dt);
                $days_diff = $interval->days;
                
                $work_days = 0;
                $temp_date = clone $joined;
                for ($i = 0; $i <= $days_diff; $i++) {
                    if ($temp_date->format('N') < 6) { // 1-5 (Mon-Fri)
                        $work_days++;
                    }
                    $temp_date->modify('+1 day');
                }
                
                $absent_count = max(0, $work_days - $stats['total_attendance']);

                echo json_encode([
                    'success' => true, 
                    'action' => $column, 
                    'time' => $time, 
                    'status' => $status, 
                    'name' => $best_match['full_name'],
                    'employee_id' => $emp_data['employee_id'],
                    'position' => $emp_data['position'],
                    'attendance_count' => $stats['total_attendance'],
                    'absent_count' => $absent_count,
                    'match_percentage' => $match_percentage,
                    'missed_morning' => $missed_morning
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No match found', 'match_percentage' => $match_percentage]);
            }
            break;

        case 'run_payroll':
            if (!isPayrollOrHigher()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $start_date = $data['start_date'];
            $end_date = $data['end_date'];
            $period = date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));

            $work_days_in_period = 0;
            $current_date = new DateTime($start_date);
            $end_date_dt = new DateTime($end_date);
            while($current_date <= $end_date_dt) {
                if ($current_date->format('N') < 6) $work_days_in_period++;
                $current_date->add(new DateInterval('P1D'));
            }

            $stmt_employees = $pdo->prepare("SELECT * FROM employees WHERE company_id = ? AND status = 'Active'");
            $stmt_employees->execute([$_SESSION['company_id']]);
            $employees = $stmt_employees->fetchAll();

            $stmt_deductions = $pdo->prepare("SELECT * FROM deductions WHERE company_id = ? AND is_active = true");
            $stmt_deductions->execute([$_SESSION['company_id']]);
            $deductions_config = $stmt_deductions->fetchAll();

            foreach ($employees as $emp) {
                $stmt_attendance = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND log_date BETWEEN ? AND ? AND check_in IS NOT NULL");
                $stmt_attendance->execute([$emp['id'], $start_date, $end_date]);
                $days_present = $stmt_attendance->fetchColumn();

                $monthly_salary = $emp['basic_salary'];
                $earned_pay = ($work_days_in_period > 0) ? ($days_present / $work_days_in_period) * $monthly_salary : 0;
                
                $total_deductions = 0;
                foreach ($deductions_config as $deduction) {
                    if ($deduction['type'] === 'percentage') {
                        $total_deductions += $earned_pay * ($deduction['value'] / 100);
                    } else {
                        $total_deductions += $deduction['value'];
                    }
                }

                $stmt_loans = $pdo->prepare("SELECT id, amount FROM loans WHERE employee_id = ? AND company_id = ? AND status = 'Approved'");
                $stmt_loans->execute([$emp['id'], $_SESSION['company_id']]);
                $approved_loans = $stmt_loans->fetchAll();
                
                foreach ($approved_loans as $loan) {
                    $total_deductions += $loan['amount'];
                    $pdo->prepare("UPDATE loans SET status = 'Paid' WHERE id = ?")->execute([$loan['id']]);
                }

                $net_pay = $earned_pay - $total_deductions;

                $stmt = $pdo->prepare("INSERT INTO payroll (company_id, employee_id, period, basic_pay, deductions, net_pay, status) VALUES (?, ?, ?, ?, ?, ?, 'Paid') ON DUPLICATE KEY UPDATE basic_pay = ?, deductions = ?, net_pay = ?, status = 'Paid'");
                $stmt->execute([$_SESSION['company_id'], $emp['id'], $period, $earned_pay, $total_deductions, $net_pay, $earned_pay, $total_deductions, $net_pay]);
            }
            echo json_encode(['success' => true, 'message' => "Payroll for $period run successfully."]);
            break;

        case 'get_payroll':
            if (!isset($_SESSION['company_id'])) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT p.*, e.full_name FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.company_id = ? ORDER BY p.created_at DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_payslip':
            if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $payslip_id = $_GET['id'] ?? '';
            $stmt = $pdo->prepare("SELECT p.*, e.full_name, e.employee_id as emp_code, e.position, e.department FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.id = ? AND p.company_id = ?");
            $stmt->execute([$payslip_id, $_SESSION['company_id']]);
            echo json_encode($stmt->fetch());
            break;

        case 'get_leave_requests':
            if (!isset($_SESSION['company_id'])) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT lr.*, e.full_name FROM leave_requests lr JOIN employees e ON lr.employee_id = e.id WHERE lr.company_id = ? ORDER BY lr.id DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_loan_requests':
            if (!isset($_SESSION['company_id'])) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT l.*, e.full_name FROM loans l JOIN employees e ON l.employee_id = e.id WHERE l.company_id = ? ORDER BY l.id DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_resignation_requests':
            if (!isset($_SESSION['company_id'])) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT r.*, e.full_name FROM resignations r JOIN employees e ON r.employee_id = e.id WHERE r.company_id = ? ORDER BY r.id DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'update_leave_status':
        case 'update_loan_status':
        case 'update_resignation_status':
            if (!isAdminOrHR()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $table = str_replace('update_', '', str_replace('_status', '', $action)) . '_requests';
            if ($action === 'update_loan_status') $table = 'loans';
            if ($action === 'update_resignation_status') $table = 'resignations';
            
            $stmt = $pdo->prepare("UPDATE $table SET status = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$data['status'], $data['id'], $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'get_deductions':
            if (!isset($_SESSION['company_id'])) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT * FROM deductions WHERE company_id = ?");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'save_deduction':
            if (!isAdminOrHR()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data['id']) && !empty($data['id'])) {
                $stmt = $pdo->prepare("UPDATE deductions SET name = ?, type = ?, value = ?, is_active = ?, is_government = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([$data['name'], $data['type'], $data['value'], $data['is_active'], $data['is_government'], $data['id'], $_SESSION['company_id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO deductions (company_id, name, type, value, is_active, is_government) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['company_id'], $data['name'], $data['type'], $data['value'], $data['is_active'], $data['is_government']]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_deduction':
            if (!isAdminOrHR()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = $_GET['id'] ?? '';
            $stmt = $pdo->prepare("DELETE FROM deductions WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'save_settings':
            if (!isAdminOrHR()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE companies SET name = ?, work_start = ?, work_end = ?, lunch_out_start = ?, lunch_out_end = ?, lunch_in_start = ?, lunch_in_end = ?, grace_period = ?, ot_percentage = ?, deduction_per_sec = ?, deduction_per_min = ?, deduction_per_hour = ? WHERE id = ?");
            $stmt->execute([
                $data['companyName'], 
                $data['workStart'], 
                $data['workEnd'], 
                $data['lunchOutStart'], 
                $data['lunchOutEnd'], 
                $data['lunchInStart'], 
                $data['lunchInEnd'], 
                $data['gracePeriod'], 
                $data['otPercentage'], 
                $data['deductionPerSec'], 
                $data['deductionPerMin'], 
                $data['deductionPerHour'], 
                $_SESSION['company_id']
            ]);
            $_SESSION['company_name'] = $data['companyName'];
            echo json_encode(['success' => true]);
            break;

        case 'get_dashboard_stats':
            if (!isset($_SESSION['company_id'])) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $cid = $_SESSION['company_id'];
            $today = date('Y-m-d');
            $stats = [];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE company_id = ?");
            $stmt->execute([$cid]);
            $stats['total_employees'] = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE company_id = ? AND log_date = ? AND check_in IS NOT NULL");
            $stmt->execute([$cid, $today]);
            $stats['present_today'] = (int)$stmt->fetchColumn();

            $stats['absent_today'] = $stats['total_employees'] - $stats['present_today'];

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE company_id = ? AND status = 'Pending'");
            $stmt->execute([$cid]);
            $stats['pending_leave'] = (int)$stmt->fetchColumn();

            echo json_encode($stats);
            break;

        case 'get_ess_data':
            if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $stmt = $pdo->prepare("SELECT e.*, u.username, u.email as user_email FROM employees e JOIN users u ON e.user_id = u.id WHERE u.id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp = $stmt->fetch();
            if (!$emp) exit(json_encode(['success' => false, 'message' => 'Profile not found']));
            
            $eid = $emp['id'];
            
            $stmt_attendance = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? ORDER BY log_date DESC LIMIT 30");
            $stmt_attendance->execute([$eid]);
            $attendance = $stmt_attendance->fetchAll();

            $stmt_payroll = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? ORDER BY created_at DESC");
            $stmt_payroll->execute([$eid]);
            $payroll = $stmt_payroll->fetchAll();

            $stmt_leave = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY id DESC");
            $stmt_leave->execute([$eid]);
            $leave = $stmt_leave->fetchAll();
            
            echo json_encode([
                'profile' => $emp,
                'attendance' => $attendance,
                'payroll' => $payroll,
                'leave' => $leave
            ]);
            break;

        case 'get_subject_loads':
            if (!isset($_SESSION['company_id'])) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT sl.*, e.full_name as faculty_name FROM subject_loads sl JOIN employees e ON sl.faculty_id = e.id WHERE sl.company_id = ?");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_subjects':
            if (!isset($_SESSION['company_id'])) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT * FROM subjects WHERE company_id = ? ORDER BY code ASC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'save_subject':
            if (!isAdminOrHR()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['code']) || empty($data['description'])) {
                echo json_encode(['success' => false, 'message' => 'Code and description are required']);
                break;
            }
            if (isset($data['id']) && !empty($data['id'])) {
                $stmt = $pdo->prepare("UPDATE subjects SET code = ?, description = ?, units = ?, hours = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([$data['code'], $data['description'], $data['units'], $data['hours'], $data['id'], $_SESSION['company_id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO subjects (company_id, code, description, units, hours) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['company_id'], $data['code'], $data['description'], $data['units'], $data['hours']]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_subject':
            if (!isAdminOrHR()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = $_GET['id'] ?? '';
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'save_subject_load':
            if (!isAdminOrHR()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO subject_loads (company_id, faculty_id, code, description, units, hours) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['company_id'], $data['faculty_id'], $data['code'], $data['description'], $data['units'], $data['hours']]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_subject_load':
            if (!isAdminOrHR()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = $_GET['id'] ?? '';
            $stmt = $pdo->prepare("DELETE FROM subject_loads WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'update_role':
            if (!isAdminOrHR()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = $_GET['id'] ?? '';
            $role = $_GET['role'] ?? 'Employee';
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("SELECT user_id FROM employees WHERE id = ? AND company_id = ?");
                $stmt->execute([$id, $_SESSION['company_id']]);
                $user_id = $stmt->fetchColumn();
                if (!$user_id) throw new Exception('User not found');
                $pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND company_id = ?")->execute([$role, $user_id, $_SESSION['company_id']]);
                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'change_password':
            $data = json_decode(file_get_contents('php://input'), true);
            $oldPass = $data['oldPass'] ?? '';
            $newPass = $data['newPass'] ?? '';
            
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($oldPass, $user['password'])) {
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $_SESSION['user_id']]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Incorrect current password']);
            }
            break;

        case 'update_leave_balance':
            if (!isAdminOrHR()) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $employee_id = $_GET['employee_id'] ?? '';
            $balance = $_GET['balance'] ?? 0;
            $stmt = $pdo->prepare("UPDATE employees SET leave_balance = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$balance, $employee_id, $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'get_payroll_batches':
            if (!isset($_SESSION['company_id'])) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT period, SUM(net_pay) as total_disbursed, COUNT(*) as staff_count, MAX(created_at) as processing_date FROM payroll WHERE company_id = ? GROUP BY period ORDER BY processing_date DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_companies':
            $stmt = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC");
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_company_info':
            $id = $_GET['company_id'] ?? 1;
            $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode($stmt->fetch());
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
}
?>