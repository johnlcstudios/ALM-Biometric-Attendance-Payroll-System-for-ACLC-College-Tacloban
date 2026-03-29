<?php
// api.php - Core Backend Logic
header('Content-Type: application/json');

// Error reporting to catch errors and return as JSON
error_reporting(0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo json_encode(['success' => false, 'message' => "PHP Error: [$errno] $errstr in $errfile on line $errline"]);
    exit;
});

session_start();

try {
    require_once 'db.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            $data = json_decode(file_get_contents('php://input'), true);
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

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
            $stmt = $pdo->prepare("INSERT INTO companies (name, admin_email) VALUES (?, ?)");
            $stmt->execute([$company_name, $email]);
            $company_id = $pdo->lastInsertId();

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (company_id, username, password, role, email) VALUES (?, ?, ?, 'HR', ?)");
            $stmt->execute([$company_id, $username, $hashed_password, $email]);

            $pdo->commit();
            echo json_encode(['success' => true]);
            break;

        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            break;

        case 'get_companies':
            $stmt = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC");
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_company_info':
            $cid = $_GET['company_id'] ?? 1;
            $stmt = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
            $stmt->execute([$cid]);
            echo json_encode($stmt->fetch() ?: ['name' => 'Unknown Company']);
            break;

        case 'get_employees':
            if (!isset($_SESSION['company_id'])) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT e.*, u.username, u.role FROM employees e LEFT JOIN users u ON e.user_id = u.id WHERE e.company_id = ?");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'save_employee':
            if (!isset($_SESSION['company_id']) || $_SESSION['role'] !== 'HR') exit(json_encode(['error' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($data['id']) && !empty($data['id'])) {
                $stmt = $pdo->prepare("UPDATE employees SET full_name = ?, dob = ?, email = ?, position = ?, department = ?, basic_salary = ?, sss = ?, philhealth = ?, tin = ?, pagibig = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([$data['fullName'], $data['dob'], $data['email'], $data['position'], $data['department'], $data['basicSalary'], $data['sss'], $data['philhealth'], $data['tin'], $data['pagibig'], $data['id'], $_SESSION['company_id']]);
            } else {
                $stmt = $pdo->prepare("SELECT employee_id FROM employees WHERE company_id = ? ORDER BY id DESC LIMIT 1");
                $stmt->execute([$_SESSION['company_id']]);
                $last_emp_id = $stmt->fetchColumn();
                $num = $last_emp_id ? (int)substr($last_emp_id, 3) + 1 : 1;
                $emp_id = 'EMP' . str_pad($num, 3, '0', STR_PAD_LEFT);

                $hashed_pass = password_hash('welcome123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (company_id, username, password, role, email) VALUES (?, ?, ?, 'Employee', ?)");
                $stmt->execute([$_SESSION['company_id'], strtolower(str_replace(' ', '.', $data['fullName'])), $hashed_pass, $data['email']]);
                $user_id = $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO employees (company_id, employee_id, full_name, dob, email, position, department, basic_salary, sss, philhealth, tin, pagibig, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['company_id'], $emp_id, $data['fullName'], $data['dob'], $data['email'], $data['position'], $data['department'], $data['basicSalary'], $data['sss'], $data['philhealth'], $data['tin'], $data['pagibig'], $user_id]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_employee':
            if (!isset($_SESSION['company_id']) || $_SESSION['role'] !== 'HR') exit(json_encode(['error' => 'Unauthorized']));
            $id = $_GET['id'] ?? '';
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'save_face_descriptor':
            if (!isset($_SESSION['company_id']) || $_SESSION['role'] !== 'HR') exit(json_encode(['error' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE employees SET face_descriptor = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([json_encode($data['descriptor']), $data['id'], $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'reset_password':
            if (!isset($_SESSION['company_id']) || $_SESSION['role'] !== 'HR') exit(json_encode(['error' => 'Unauthorized']));
            $user_id = $_GET['user_id'] ?? '';
            $new_pass = password_hash('welcome123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$new_pass, $user_id, $_SESSION['company_id']]);
            echo json_encode(['success' => true, 'message' => 'Password reset to welcome123']);
            break;

        case 'get_attendance':
            if (!isset($_SESSION['company_id'])) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT a.*, e.full_name, e.employee_id as emp_code FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.company_id = ? ORDER BY a.log_date DESC, a.check_in DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'kiosk_scan':
        // Automated Kiosk Check-In/Out Logic
        $data = json_decode(file_get_contents('php://input'), true);
        $company_id = $data['company_id'] ?? 1;
        $descriptor = $data['descriptor'] ?? [];

        if (empty($descriptor) || count($descriptor) !== 128) {
             echo json_encode(['success' => false, 'message' => 'Invalid descriptor (Expected 128 floats)', 'received' => count($descriptor)]);
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
                // Calculate Euclidean distance
                $sum = 0;
                for ($i = 0; $i < 128; $i++) {
                    $sum += pow((float)$descriptor[$i] - (float)$enrolled_descriptor[$i], 2);
                }
                $distance = sqrt($sum);

                if ($distance < $best_distance) {
                    $best_distance = $distance;
                    $best_match = $face;
                }
            }
        }

        // Calculate match percentage (0.0 distance = 100%, 0.4 distance = 90%)
        // Formula: 100 - (distance * 25) -> if distance is 0.4, 100 - 10 = 90
        $match_percentage = max(0, round(100 - ($best_distance * 25), 2));

        if ($best_match && $match_percentage >= 90) {
            $matched_employee = $best_match;
            $employee_id = $matched_employee['id'];
            $date = date('Y-m-d');
            $time = date('H:i:s');

            // Fetch matched employee detailed info
            $stmt_emp = $pdo->prepare("SELECT employee_id, position FROM employees WHERE id = ?");
            $stmt_emp->execute([$employee_id]);
            $emp_data = $stmt_emp->fetch();

            // Get Company Settings
            $stmt_config = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
            $stmt_config->execute([$company_id]);
            $config = $stmt_config->fetch();

            // Check if log exists for today
            $stmt_log = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND log_date = ?");
            $stmt_log->execute([$employee_id, $date]);
            $log = $stmt_log->fetch();

            // Determine morning absence
            $missed_morning = (!$log || empty($log['check_in'])) && $time > date('H:i:s', strtotime($config['work_start'] . ' + 4 hours'));

            // Determine action and status
            $status = 'On-Time';
            $column = '';
            
            $work_start = $config['work_start'];
            $grace_period = $config['grace_period'] ?? 15;
            $late_time = date('H:i:s', strtotime($work_start . " + $grace_period minutes"));
            
            if ($time >= date('H:i:s', strtotime($config['work_end'] . ' - 30 minutes'))) {
                $column = 'check_out';
            } elseif ($time <= date('H:i:s', strtotime($work_start . ' + 2 hours'))) {
                $column = 'check_in';
                if ($time > $late_time) {
                    $status = 'Late';
                }
            } elseif ($time >= $config['lunch_out_start'] && $time <= $config['lunch_out_end']) {
                $column = 'lunch_out';
            } elseif ($time >= $config['lunch_in_start'] && $time <= $config['lunch_in_end']) {
                $column = 'lunch_in';
            } else {
                $column = 'check_out';
            }

            // ENFORCE ENTRY POINT REQUIREMENT
            // Lunch Out requires Check-in
            if ($column === 'lunch_out' && (!$log || empty($log['check_in']))) {
                echo json_encode([
                    'success' => false,
                    'message' => 'MUST CHECK-IN FIRST',
                    'action' => 'lunch_out',
                    'name' => $matched_employee['full_name'],
                    'employee_id' => $emp_data['employee_id'],
                    'position' => $emp_data['position'],
                    'match_percentage' => $match_percentage,
                    'debug' => "User {$matched_employee['full_name']} tried to lunch out without a check-in."
                ]);
                break;
            }

            // Check Out requires Check-in OR Lunch-in
            if ($column === 'check_out' && (!$log || (empty($log['check_in']) && empty($log['lunch_in'])))) {
                echo json_encode([
                    'success' => false,
                    'message' => 'MUST LOG-IN FIRST',
                    'action' => 'check_out',
                    'name' => $matched_employee['full_name'],
                    'employee_id' => $emp_data['employee_id'],
                    'position' => $emp_data['position'],
                    'match_percentage' => $match_percentage,
                    'debug' => "User {$matched_employee['full_name']} tried to check out without a check-in or lunch-in."
                ]);
                break;
            }

            // ENFORCE ONCE PER DAY LOGGING
            if ($log && !empty($log[$column])) {
                // Fetch basic stats for the matched employee
                $stmt_stats = $pdo->prepare("SELECT 
                    (SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND check_in IS NOT NULL) as total_attendance,
                    (SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND status = 'Absent') as total_absent
                ");
                $stmt_stats->execute([$employee_id, $employee_id]);
                $stats = $stmt_stats->fetch();

                echo json_encode([
                    'success' => false,
                    'message' => 'ALREADY LOGGED',
                    'action' => $column,
                    'name' => $matched_employee['full_name'],
                    'employee_id' => $emp_data['employee_id'],
                    'position' => $emp_data['position'],
                'attendance_count' => $stats['total_attendance'],
                'absent_count' => $stats['total_absent'],
                'match_percentage' => $match_percentage,
                'missed_morning' => $missed_morning,
                'debug' => "User {$matched_employee['full_name']} already has a value in $column for today."
            ]);
            break;
        }

            if (!$log) {
                $stmt = $pdo->prepare("INSERT INTO attendance (company_id, employee_id, log_date, $column, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$company_id, $employee_id, $date, $time, $status]);
            } else {
                // Determine final status if checking out
                if ($column === 'check_out') {
                    // Rule: Lunch In + Check Out without Check In = Half-Day
                    // Rule: Check In + Lunch Out without Lunch In = Half-Day
                    if (empty($log['check_in']) && !empty($log['lunch_in'])) {
                        $status = 'Half-Day';
                    } else if (!empty($log['check_in']) && empty($log['lunch_in'])) {
                        $status = 'Half-Day';
                    } else if (empty($log['check_in'])) {
                        $status = 'Absent';
                    }
                }

                $update_status = ", status = '$status'";
                $stmt = $pdo->prepare("UPDATE attendance SET $column = ? $update_status WHERE id = ?");
                $stmt->execute([$time, $log['id']]);
            }

            // Fetch basic stats for the matched employee
            $stmt_stats = $pdo->prepare("SELECT 
                (SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND check_in IS NOT NULL) as total_attendance,
                (SELECT COUNT(*) FROM attendance WHERE employee_id = ? AND status = 'Absent') as total_absent
            ");
            $stmt_stats->execute([$employee_id, $employee_id]);
            $stats = $stmt_stats->fetch();

            echo json_encode([
                'success' => true, 
                'action' => $column, 
                'time' => $time, 
                'status' => $status, 
                'name' => $matched_employee['full_name'],
                'employee_id' => $emp_data['employee_id'],
                'position' => $emp_data['position'],
                'attendance_count' => $stats['total_attendance'],
                'absent_count' => $stats['total_absent'],
                'match_percentage' => $match_percentage,
                'missed_morning' => $missed_morning,
                'debug' => "Matched: {$matched_employee['full_name']} (Dist: " . round($best_distance, 4) . "). Match: $match_percentage%. Checked $faces_checked faces for company ID $company_id."
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'No match found', 
                'match_percentage' => $match_percentage,
                'debug' => "Best match was " . round($best_distance, 4) . " (Match: $match_percentage%). Checked $faces_checked faces for company ID $company_id. Threshold: 90% (Dist: 0.4)."
            ]);
        }
        break;

        case 'run_payroll':
            if (!isset($_SESSION['company_id'])) exit(json_encode(['error' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $start_date = $data['start_date'];
            $end_date = $data['end_date'];
            $period = date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));

            $work_days_in_period = 0;
            $current_date = new DateTime($start_date);
            $end_date_dt = new DateTime($end_date);
            while($current_date <= $end_date_dt) {
                if ($current_date->format('N') < 6) { // Mon-Fri
                    $work_days_in_period++;
                }
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
                $earned_pay = ($days_present / $work_days_in_period) * $monthly_salary;
                
                $total_deductions = 0;
                foreach ($deductions_config as $deduction) {
                    if ($deduction['type'] === 'percentage') {
                        $total_deductions += $earned_pay * ($deduction['value'] / 100);
                    } else {
                        $total_deductions += $deduction['value'];
                    }
                }

                // Auto-deduct approved loans
                $stmt_loans = $pdo->prepare("SELECT id, amount FROM loans WHERE employee_id = ? AND company_id = ? AND status = 'Approved'");
                $stmt_loans->execute([$emp['id'], $_SESSION['company_id']]);
                $approved_loans = $stmt_loans->fetchAll();
                
                foreach ($approved_loans as $loan) {
                    $total_deductions += $loan['amount'];
                    // Mark loan as Paid
                    $stmt_pay_loan = $pdo->prepare("UPDATE loans SET status = 'Paid' WHERE id = ?");
                    $stmt_pay_loan->execute([$loan['id']]);
                }

                $net_pay = $earned_pay - $total_deductions;

                $stmt = $pdo->prepare("INSERT INTO payroll (company_id, employee_id, period, basic_pay, deductions, net_pay, status) VALUES (?, ?, ?, ?, ?, ?, 'Paid') ON DUPLICATE KEY UPDATE basic_pay = ?, deductions = ?, net_pay = ?, status = 'Paid'");
                $stmt->execute([$_SESSION['company_id'], $emp['id'], $period, $earned_pay, $total_deductions, $net_pay, $earned_pay, $total_deductions, $net_pay]);
            }
            echo json_encode(['success' => true, 'message' => "Payroll for $period run successfully. Work days: $work_days_in_period"]);
            break;

        case 'get_payroll':
            if (!isset($_SESSION['company_id'])) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT p.*, e.full_name FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.company_id = ? ORDER BY p.created_at DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_payslip':
            if (!isset($_SESSION['user_id'])) exit(json_encode(['error' => 'Unauthorized']));
            $payslip_id = $_GET['id'] ?? '';
            $stmt = $pdo->prepare("SELECT p.*, e.full_name, e.employee_id as emp_code, e.position, e.department FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.id = ? AND p.company_id = ?");
            $stmt->execute([$payslip_id, $_SESSION['company_id']]);
            $payslip = $stmt->fetch();
            echo json_encode($payslip);
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
            if (!isset($_SESSION['company_id'])) exit(json_encode(['error' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$data['status'], $data['id'], $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'update_loan_status':
            if (!isset($_SESSION['company_id'])) exit(json_encode(['error' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE loans SET status = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$data['status'], $data['id'], $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'update_resignation_status':
            if (!isset($_SESSION['company_id'])) exit(json_encode(['error' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE resignations SET status = ? WHERE id = ? AND company_id = ?");
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
            if (!isset($_SESSION['company_id']) || $_SESSION['role'] !== 'HR') exit(json_encode(['error' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($data['id']) && !empty($data['id'])) {
                // Update
                $stmt = $pdo->prepare("UPDATE deductions SET name = ?, type = ?, value = ?, is_active = ?, is_government = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([$data['name'], $data['type'], $data['value'], $data['is_active'], $data['is_government'], $data['id'], $_SESSION['company_id']]);
            } else {
                // Create
                $stmt = $pdo->prepare("INSERT INTO deductions (company_id, name, type, value, is_active, is_government) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['company_id'], $data['name'], $data['type'], $data['value'], $data['is_active'], $data['is_government']]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_deduction':
            if (!isset($_SESSION['company_id']) || $_SESSION['role'] !== 'HR') exit(json_encode(['error' => 'Unauthorized']));
            $id = $_GET['id'] ?? '';
            $stmt = $pdo->prepare("DELETE FROM deductions WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'save_settings':
            if (!isset($_SESSION['company_id']) || $_SESSION['role'] !== 'HR') exit(json_encode(['error' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("UPDATE companies SET name = ?, work_start = ?, work_end = ?, lunch_out_start = ?, lunch_out_end = ?, lunch_in_start = ?, lunch_in_end = ?, grace_period = ? WHERE id = ?");
            $stmt->execute([
                $data['companyName'],
                $data['workStart'],
                $data['workEnd'],
                $data['lunchOutStart'],
                $data['lunchOutEnd'],
                $data['lunchInStart'],
                $data['lunchInEnd'],
                $data['gracePeriod'],
                $_SESSION['company_id']
            ]);
            
            $_SESSION['company_name'] = $data['companyName'];
            echo json_encode(['success' => true]);
            break;

        case 'get_dashboard_stats':
            if (!isset($_SESSION['company_id'])) exit(json_encode(['error' => 'Unauthorized']));
            $company_id = $_SESSION['company_id'];
            $today = date('Y-m-d');

            $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE company_id = ?");
            $stmt_total->execute([$company_id]);
            $total_employees = $stmt_total->fetchColumn();

            $stmt_present = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE company_id = ? AND log_date = ? AND check_in IS NOT NULL");
            $stmt_present->execute([$company_id, $today]);
            $present_today = $stmt_present->fetchColumn();

            $absent_today = $total_employees - $present_today;

            $stmt_leave = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE company_id = ? AND status = 'Pending'");
            $stmt_leave->execute([$company_id]);
            $pending_leave = $stmt_leave->fetchColumn();

            echo json_encode([
                'total_employees' => $total_employees,
                'present_today' => $present_today,
                'absent_today' => $absent_today,
                'pending_leave' => $pending_leave
            ]);
            break;

        case 'get_ess_data':
            if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') exit(json_encode(['error' => 'Unauthorized']));
            
            $stmt = $pdo->prepare("SELECT e.*, u.username, u.email as user_email FROM employees e JOIN users u ON e.user_id = u.id WHERE u.id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$emp) {
                // If not found in employees, maybe they are an admin without an employee entry
                $stmt = $pdo->prepare("SELECT *, null as employee_id, email as user_email FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $emp = $stmt->fetch(PDO::FETCH_ASSOC);
                if(!$emp) exit(json_encode(['error' => 'Profile not found']));
            }

            $employee_id = $emp['id'] ?? null; // This is the employee table ID

            $attendance = [];
            if ($employee_id) {
                $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? ORDER BY log_date DESC LIMIT 30");
                $stmt->execute([$employee_id]);
                $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $payroll = [];
            if ($employee_id) {
                $stmt = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? ORDER BY created_at DESC");
                $stmt->execute([$employee_id]);
                $payroll = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $leave = [];
            if ($employee_id) {
                $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY id DESC");
                $stmt->execute([$employee_id]);
                $leave = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            echo json_encode(['profile' => $emp, 'attendance' => $attendance, 'payroll' => $payroll, 'leave' => $leave]);
            break;

        case 'update_leave_balance':
            if (!isset($_SESSION['company_id']) || !in_array($_SESSION['role'], ['Admin', 'HR', 'Payroll'])) exit(json_encode(['error' => 'Unauthorized']));
            $employee_id = $_GET['employee_id'] ?? '';
            $balance = $_GET['balance'] ?? 0;
            $stmt = $pdo->prepare("UPDATE employees SET leave_balance = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$balance, $employee_id, $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'get_subject_loads':
            if (!isset($_SESSION['company_id'])) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT sl.*, e.full_name as faculty_name FROM subject_loads sl JOIN employees e ON sl.faculty_id = e.id WHERE sl.company_id = ?");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'save_subject_load':
            if (!isset($_SESSION['company_id']) || ($_SESSION['role'] !== 'HR' && $_SESSION['role'] !== 'Admin')) exit(json_encode(['error' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO subject_loads (company_id, faculty_id, code, description, units, hours) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['company_id'], $data['faculty_id'], $data['code'], $data['description'], $data['units'], $data['hours']]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_subject_load':
            if (!isset($_SESSION['company_id']) || ($_SESSION['role'] !== 'HR' && $_SESSION['role'] !== 'Admin')) exit(json_encode(['error' => 'Unauthorized']));
            $id = $_GET['id'] ?? '';
            $stmt = $pdo->prepare("DELETE FROM subject_loads WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'update_role':
            if (!isset($_SESSION['company_id']) || ($_SESSION['role'] !== 'HR' && $_SESSION['role'] !== 'Admin')) exit(json_encode(['error' => 'Unauthorized']));
            $id = $_GET['id'] ?? ''; // Employee ID
            $role = $_GET['role'] ?? 'Employee';
            
            try {
                $pdo->beginTransaction();

                // 1. Get user_id from employee
                $stmt = $pdo->prepare("SELECT user_id FROM employees WHERE id = ? AND company_id = ?");
                $stmt->execute([$id, $_SESSION['company_id']]);
                $user_id = $stmt->fetchColumn();
                
                if (!$user_id) {
                    throw new Exception('Employee user account not found.');
                }

                // 2. Update user role
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([$role, $user_id, $_SESSION['company_id']]);

                // 3. Update employee position if assigned as Payroll
                if ($role === 'Payroll') {
                    $stmt = $pdo->prepare("UPDATE employees SET position = 'Payroll Officer' WHERE id = ? AND company_id = ?");
                    $stmt->execute([$id, $_SESSION['company_id']]);
                } else if ($role === 'Employee') {
                    // Revert to a generic position if access is revoked
                    $stmt = $pdo->prepare("UPDATE employees SET position = 'Staff' WHERE id = ? AND company_id = ?");
                    $stmt->execute([$id, $_SESSION['company_id']]);
                }

                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['error' => 'Invalid action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
}
?>
