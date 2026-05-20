<?php
// api.php - Core Backend Logic
header('Content-Type: application/json');

// Biometric Constants
define('BIOMETRIC_MATCH_THRESHOLD', 0.60); // Tighter threshold for production
define('BIOMETRIC_DUPLICATE_THRESHOLD', 0.40);
define('BIOMETRIC_AMBIGUITY_RATIO', 1.30); // Higher ratio for better confidence

try {
    require_once 'db.php';
    require_once 'api_helpers.php';
    require_once 'rate_limit.php';
    require_once 'notifications.php';
    require_once 'audit.php';
    require_once 'encryption.php';
    require_once 'two_factor_auth.php';
} catch (Exception $e) {
    apiError('Database connection failed: ' . $e->getMessage(), [], 500);
}

// Whitelist of allowed actions to prevent unauthorized access
$allowedActions = [
    // Authentication
    'login', 'logout', 'forgot_password', 'reset_password', 'reset_password_with_token', 'verify_2fa',
    'request_otp', 'verify_otp',
    'signup', 'change_password',
    'request_password_otp', 'verify_reset_otp', // NEW OTP endpoints
    
    // Dashboard & ESS
    'get_dashboard_stats', 'get_ess_data',
    
    // Employee Management
    'get_employees', 'save_employee', 'delete_employee', 'reinstate_employee',
    'upload_profile_picture', 'update_employee_profile',
    
    // Attendance
    'get_attendance', 'add_attendance', 'update_attendance', 'flag_attendance',
    'check_in_out', 'biometric_check', 'kiosk_scan',
    
    // Payroll
    'run_payroll', 'get_payroll_batches', 'get_payroll_by_period', 'get_payroll',
    'get_faculty_payroll', 'get_utility_payroll',
    'run_specialized_payroll',
    'update_payroll_field', 'revoke_payroll_access',
    'get_payslip', 'get_deduction_breakdown',
    'bulk_payroll_adjustment', 'bulk_payroll_update',
    
    // Deductions
    'get_deductions', 'save_deduction', 'delete_deduction',
    'get_deduction_categories',
    'get_employee_deductions', 'assign_employee_deduction', 'delete_employee_deduction',
    'bulk_assign_deduction',
    
    // Allowances
    'get_allowances', 'add_allowance', 'update_allowance', 'delete_allowance',
    'get_allowance_categories', 'add_allowance_category', 'delete_allowance_category',
    'get_employee_allowances', 'assign_employee_allowance', 'delete_employee_allowance',
    'bulk_assign_allowance',
    
    // Subject Load Management
    'get_subjects', 'save_subject', 'delete_subject',
    'get_subject_loads', 'save_subject_load', 'delete_subject_load',
    'get_subject_schedules', 'save_subject_schedule', 'delete_subject_schedule',
    'get_my_subject_loads', 'save_my_subject_load', 'delete_my_subject_load',
    'get_my_subject_schedules', 'save_my_subject_schedule', 'delete_my_subject_schedule',
    
// Leave Management
    'get_leave_requests', 'apply_leave', 'update_leave_status',
    'approve_leave', 'reject_leave',
    'update_leave_balance', 'bulk_update_leave_balance',
    'get_leave_requests_filtered',
    
    // Loan/Cash Advance Management
    'get_loan_requests', 'apply_loan', 'update_loan_status', 'get_cash_advance_by_tracking',
    
    // Company & Settings
    'get_companies', 'add_company', 'update_company', 'get_company_info',
    'get_settings', 'save_settings',
    
    // User Management
    'get_users', 'add_user', 'update_user', 'delete_user', 'update_role',
    'assign_role', 'deactivate_user',
    
    // SD Pages
    'get_sd_analytics',
    
    // Face Recognition
    'upload_face_encoding', 'get_face_registrations', 'save_face_registration',
    'get_registered_faces',
    
    // Server Utilities
    'get_server_time',
    
    // Audit & Notifications
    'get_notifications',
    
    // NEW: Payroll Calendar & Improvements (Phase 1)
    'get_payroll_schedule', 'save_payroll_schedule', 'validate_payroll_readiness',
    'calculate_taxes', 'apply_payroll_taxes',
    
    // ESS Specific Subject Load endpoints
    'save_subject_load_ess', 'delete_subject_load_ess',
    'save_subject_schedule_ess', 'delete_subject_schedule_ess'
];

// Get and sanitize action parameter
$action = getParam('action', '', 'string');

// Validate action against whitelist
if (!empty($action) && !validateAction($action, $allowedActions)) {
    apiError('Invalid action requested', [], 400);
}

// Helper function to process specialized payroll (Faculty/Utility)
function processSpecializedPayroll($pdo, $company_id, $position, $start_date, $end_date) {
    try {
        $period = date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));
        
        // Get company deduction settings
        $stmt_company = $pdo->prepare("SELECT deduction_per_min FROM companies WHERE id = ?");
        $stmt_company->execute([$company_id]);
        $company = $stmt_company->fetch();
        $deduction_per_min = isset($company['deduction_per_min']) ? (float) $company['deduction_per_min'] : 0.50;
        
        // Get employees for this position
        $stmt_employees = $pdo->prepare("SELECT * FROM employees WHERE company_id = ? AND position = ? AND status = 'Active'");
        $stmt_employees->execute([$company_id, $position]);
        $employees = $stmt_employees->fetchAll();
        
        if (empty($employees)) {
            return ['success' => true, 'message' => "No active {$position} employees to process."];
        }
        
        $employee_ids = array_column($employees, 'id');
        
        // Pre-fetch attendance logs
        $logs_by_emp = [];
        if (!empty($employee_ids)) {
            $placeholders = implode(',', array_fill(0, count($employee_ids), '?'));
            $stmt_all_att = $pdo->prepare("SELECT * FROM attendance WHERE employee_id IN ($placeholders) AND log_date BETWEEN ? AND ?");
            $stmt_all_att->execute(array_merge($employee_ids, [$start_date, $end_date]));
            while ($row = $stmt_all_att->fetch()) {
                $logs_by_emp[$row['employee_id']][] = $row;
            }
        }
        
        // Process each employee
        foreach ($employees as $emp) {
            // Prevent payroll processing before hire date
            $hire_date = $emp['hire_date'] ?? $emp['created_at'] ?? $start_date;
            $effective_start_date = max($start_date, $hire_date);
            
            // If employee wasn't hired yet during this period, skip
            if ($effective_start_date > $end_date) {
                continue;
            }
            
            $logs = $logs_by_emp[$emp['id']] ?? [];
            
            $total_absent = 0;
            $total_late_min = 0;
            $days_present = 0;
            
            foreach ($logs as $l) {
                if ($l['status'] === 'Late')
                    $total_late_min += (int) $l['late_minutes'];
                if ($l['status'] === 'Absent')
                    $total_absent++;
                if (!empty($l['check_in']))
                    $days_present++;
            }
            
            if ($position === 'Faculty') {
                // Faculty specific calculations
                // FIXED: Removed hardcoded / 2 divisor - use actual working days
                // Calculate actual working days in the period
                $period_days = max(1, (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1);
                $working_days = max(10, min(22, $period_days)); // Clamp between 10-22 days
                
                // Basic pay is prorated based on actual working days in period
                $basic_pay = (float) $emp['basic_salary'] * ($working_days / 22); // 22 = standard monthly working days
                $load_pay = 0; // Additional load pay (0 if no subjects assigned)
                $overtime = 0;
                $differential = 0;
                $substitution = 0;
                $adj_plus = 0;
                
                // FIXED: Absence deduction based on actual daily rate
                $daily_rate = (float) $emp['basic_salary'] / 22;
                $absences_deduction = $total_absent * $daily_rate;
                
                $late_ut = $total_late_min * $deduction_per_min;
                $hdmf_cont = !empty($emp['pagibig']) ? 100 : 0;
                $hdmf_loans = 0;
                $hdmf_mp2 = 0;
                
                // Fetch employee-specific allowances
                $stmt_allowances = $pdo->prepare("SELECT ea.*, ac.name, ac.type, ac.rate FROM employee_allowances ea JOIN allowance_categories ac ON ea.category_id = ac.id WHERE ea.employee_id = ? AND ea.company_id = ? AND (ea.effective_date IS NULL OR ea.effective_date <= ?)");
                $stmt_allowances->execute([$emp['id'], $company_id, $end_date]);
                $employee_allowances = $stmt_allowances->fetchAll();
                
                // Calculate total allowances
                $total_allowances = 0;
                foreach ($employee_allowances as $allowance) {
                    $amount = $allowance['override_amount'] ?? null;
                    if ($amount === null) {
                        $amount = $allowance['type'] === 'Percentage' ? $basic_pay * ($allowance['rate'] / 100) : $allowance['rate'];
                    }
                    $total_allowances += (float)$amount;
                }
                
                // Fetch employee-specific deductions
                $stmt_emp_deductions = $pdo->prepare("SELECT ed.*, d.name, d.type, d.value FROM employee_deductions ed JOIN deductions d ON ed.deduction_id = d.id WHERE ed.employee_id = ? AND ed.company_id = ? AND (ed.effective_date IS NULL OR ed.effective_date <= ?)");
                $stmt_emp_deductions->execute([$emp['id'], $company_id, $end_date]);
                $employee_deductions = $stmt_emp_deductions->fetchAll();
                
                // Calculate employee-specific deductions
                $employee_specific_deductions = 0;
                $employee_deductions_breakdown = []; // Store individual deduction details
                foreach ($employee_deductions as $deduction) {
                    $amount = $deduction['override_amount'] ?? null;
                    if ($amount === null) {
                        $amount = $deduction['type'] === 'percentage' ? $basic_pay * ($deduction['value'] / 100) : $deduction['value'];
                    }
                    $amount = (float)$amount;
                    $employee_specific_deductions += $amount;
                    
                    // Store individual deduction details
                    $employee_deductions_breakdown[] = [
                        'name' => $deduction['name'],
                        'type' => $deduction['type'],
                        'amount' => $amount
                    ];
                }
                
                $total_deduction = $absences_deduction + $late_ut + $hdmf_cont + $hdmf_loans + $hdmf_mp2 + $employee_specific_deductions;
                $honorarium = 0;
                $net_pay = ($basic_pay + $load_pay + $overtime + $differential + $substitution + $adj_plus + $honorarium + $total_allowances) - $total_deduction;
                
                // FIXED: Validate net_pay is not negative
                if ($net_pay < 0) {
                    error_log("Warning: Negative net_pay detected for faculty employee {$emp['id']}: {$net_pay}. Setting to 0.");
                    $net_pay = 0;
                    $breakdown['negative_net_pay_warning'] = 'Net pay was negative and has been set to 0. Please review deductions.';
                }
                
                $breakdown = [
                    'load_pay' => $load_pay,
                    'overtime' => $overtime,
                    'differential' => $differential,
                    'substitution' => $substitution,
                    'adj_plus' => $adj_plus,
                    'total_allowances' => $total_allowances,
                    'absences' => $absences_deduction,
                    'late_ut' => $late_ut,
                    'hdmf_cont' => $hdmf_cont,
                    'hdmf_loans' => $hdmf_loans,
                    'hdmf_mp2' => $hdmf_mp2,
                    'employee_deductions' => $employee_specific_deductions,
                    'employee_deductions_details' => $employee_deductions_breakdown,
                    'total_deduction' => $total_deduction,
                    'honorarium' => $honorarium,
                    'days_present' => $days_present,
                    'absent_days' => $total_absent,
                    'late_minutes' => $total_late_min
                ];
                
                $stmt = $pdo->prepare("REPLACE INTO payroll (company_id, employee_id, payroll_type, period, basic_pay, deductions, net_pay, breakdown, status) 
                                 VALUES (?, ?, 'Faculty', ?, ?, ?, ?, ?, 'Paid')");
                $stmt->execute([$company_id, $emp['id'], $period, $basic_pay, $total_deduction, $net_pay, json_encode($breakdown)]);
            } else {
                // Utility specific calculations
                // FIXED: Added validation for 22-day divisor
                $working_days_in_month = 22; // Standard working days
                $rate_per_day = $emp['basic_salary'] / $working_days_in_month;
                
                // Validate rate_per_day is positive
                if ($rate_per_day <= 0) {
                    error_log("Warning: Invalid rate_per_day for employee {$emp['id']}: {$rate_per_day}");
                    $rate_per_day = 0;
                }
                
                $earned = $rate_per_day * $days_present;
                $ot_holiday = 0;
                $adj_plus = 0;
                $late_ut = $total_late_min * $deduction_per_min;
                $adj_minus = 0;
                $hdmf_cont = !empty($emp['pagibig']) ? 100 : 0;
                $hdmf_loans = 0;
                $cash_advance = 0;
                
                // Fetch employee-specific allowances
                $stmt_allowances = $pdo->prepare("SELECT ea.*, ac.name, ac.type, ac.rate FROM employee_allowances ea JOIN allowance_categories ac ON ea.category_id = ac.id WHERE ea.employee_id = ? AND ea.company_id = ? AND (ea.effective_date IS NULL OR ea.effective_date <= ?)");
                $stmt_allowances->execute([$emp['id'], $company_id, $end_date]);
                $employee_allowances = $stmt_allowances->fetchAll();
                
                // Calculate total allowances
                $total_allowances = 0;
                foreach ($employee_allowances as $allowance) {
                    $amount = $allowance['override_amount'] ?? null;
                    if ($amount === null) {
                        $amount = $allowance['type'] === 'Percentage' ? $earned * ($allowance['rate'] / 100) : $allowance['rate'];
                    }
                    $total_allowances += (float)$amount;
                }
                
                // Fetch employee-specific deductions
                $stmt_emp_deductions = $pdo->prepare("SELECT ed.*, d.name, d.type, d.value FROM employee_deductions ed JOIN deductions d ON ed.deduction_id = d.id WHERE ed.employee_id = ? AND ed.company_id = ? AND (ed.effective_date IS NULL OR ed.effective_date <= ?)");
                $stmt_emp_deductions->execute([$emp['id'], $company_id, $end_date]);
                $employee_deductions = $stmt_emp_deductions->fetchAll();
                
                // Calculate employee-specific deductions
                $employee_specific_deductions = 0;
                $employee_deductions_breakdown = []; // Store individual deduction details
                foreach ($employee_deductions as $deduction) {
                    $amount = $deduction['override_amount'] ?? null;
                    if ($amount === null) {
                        $amount = $deduction['type'] === 'percentage' ? $earned * ($deduction['value'] / 100) : $deduction['value'];
                    }
                    $amount = (float)$amount;
                    $employee_specific_deductions += $amount;
                    
                    // Store individual deduction details
                    $employee_deductions_breakdown[] = [
                        'name' => $deduction['name'],
                        'type' => $deduction['type'],
                        'amount' => $amount
                    ];
                }
                
                $total_deduction = $late_ut + $adj_minus + $hdmf_cont + $hdmf_loans + $cash_advance + $employee_specific_deductions;
                $net_pay = ($earned + $ot_holiday + $adj_plus + $total_allowances) - $total_deduction;
                
                // FIXED: Validate net_pay is not negative
                if ($net_pay < 0) {
                    error_log("Warning: Negative net_pay detected for utility employee {$emp['id']}: {$net_pay}. Setting to 0.");
                    $net_pay = 0;
                    $breakdown['negative_net_pay_warning'] = 'Net pay was negative and has been set to 0. Please review deductions.';
                }
                
                $atm = $net_pay;
                $non_atm = 0;
                
                $breakdown = [
                    'rate_per_day' => $rate_per_day,
                    'earned' => $earned,
                    'ot_holiday' => $ot_holiday,
                    'adj_plus' => $adj_plus,
                    'total_allowances' => $total_allowances,
                    'late_ut' => $late_ut,
                    'adj_minus' => $adj_minus,
                    'hdmf_cont' => $hdmf_cont,
                    'hdmf_loans' => $hdmf_loans,
                    'cash_advance' => $cash_advance,
                    'employee_deductions' => $employee_specific_deductions,
                    'employee_deductions_details' => $employee_deductions_breakdown,
                    'total_deduction' => $total_deduction,
                    'atm' => $atm,
                    'non_atm' => $non_atm,
                    'days_present' => $days_present,
                    'absent_days' => $total_absent,
                    'late_minutes' => $total_late_min
                ];
                
                $stmt = $pdo->prepare("REPLACE INTO payroll (company_id, employee_id, payroll_type, period, basic_pay, deductions, net_pay, breakdown, status) 
                                 VALUES (?, ?, 'Utility', ?, ?, ?, ?, ?, 'Paid')");
                $stmt->execute([$company_id, $emp['id'], $period, $earned, $total_deduction, $net_pay, json_encode($breakdown)]);
            }
        }
        
        $count = count($employees);
        return ['success' => true, 'message' => "{$position} payroll processed for {$count} employees."];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Error processing {$position} payroll: " . $e->getMessage()];
    }
}

// Helper to check for HR or Admin role (includes Payroll Officer for full company data access)
function isAdminOrHR()
{
    return isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['HR', 'Admin', 'Payroll', 'Payroll Officer']);
}

// Helper to check for Payroll role (includes Admin/HR/Payroll Officer)
function isPayrollOrHigher()
{
    return isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['HR', 'Admin', 'Payroll', 'Payroll Officer']);
}

// Centralized validation functions
function validateRequired($data, $fields)
{
    $errors = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
            $errors[] = "Field '$field' is required";
        }
    }
    return $errors;
}

function validateDate($date, $fieldName)
{
    if (empty($date))
        return [];
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return ["Invalid date format for '$fieldName'. Expected YYYY-MM-DD"];
    }
    return [];
}

function validateAmount($amount, $fieldName, $min = 0)
{
    if (!is_numeric($amount)) {
        return ["'$fieldName' must be a valid number"];
    }
    $num = (float) $amount;
    if ($num < $min) {
        return ["'$fieldName' must be at least $min"];
    }
    return [];
}

function validateId($id, $fieldName)
{
    if (!is_numeric($id) || (int) $id <= 0) {
        return ["'$fieldName' must be a positive integer"];
    }
    return [];
}

function validateDateRange($startDate, $endDate)
{
    $errors = [];
    $errors = array_merge($errors, validateDate($startDate, 'start_date'));
    $errors = array_merge($errors, validateDate($endDate, 'end_date'));
    if (empty($errors) && strtotime($startDate) > strtotime($endDate)) {
        $errors[] = 'Start date cannot be after end date';
    }
    return $errors;
}

try {
    switch ($action) {
        case 'login':
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            // Check rate limit
            $rateCheck = checkRateLimit($pdo, $ip_address);
            if ($rateCheck['blocked']) {
                echo json_encode(['success' => false, 'message' => $rateCheck['message']]);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

            if (empty($username) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Username and password are required']);
                break;
            }

            $stmt = $pdo->prepare("SELECT u.*, c.name as company_name, c.company_code, c.timezone as company_timezone, e.full_name as emp_full_name FROM users u JOIN companies c ON u.company_id = c.id LEFT JOIN employees e ON u.id = e.user_id WHERE u.username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Check 2FA
                if (is2FAEnabled($pdo, $user['id'])) {
                    // Return 2FA required flag
                    echo json_encode([
                        'success' => true, 
                        'require_2fa' => true,
                        'user_id' => $user['id'],
                        'message' => '2FA verification required'
                    ]);
                    break;
                }
                
                // Reset failed attempts on successful login
                resetFailedAttempts($pdo, $ip_address);
                
                session_regenerate_id(true); // Prevent session fixation
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['company_id'] = $user['company_id'];
                $_SESSION['role'] = trim($user['role']);
                $_SESSION['company_name'] = $user['company_name'];
                $_SESSION['company_code'] = $user['company_code'] ?? '';
                $_SESSION['company_timezone'] = $user['company_timezone'] ?: 'Asia/Manila';
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['emp_full_name'] ?: $user['username'];
                
                // Log successful login
                logAudit($pdo, $user['company_id'], $user['id'], AUDIT_LOGIN, 'user', $user['id']);
                
                echo json_encode(['success' => true, 'role' => trim($user['role']), 'company_name' => $user['company_name']]);
            } else {
                // Record failed attempt
                recordFailedAttempt($pdo, $ip_address, $username);
                
                // Log failed login
                if ($user) {
                    logAudit($pdo, $user['company_id'], $user['id'], AUDIT_FAILED_LOGIN, 'user', $user['id'], ['ip' => $ip_address]);
                }
                
                echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            }
            break;

        // ============ OTP PASSWORD RESET ENDPOINTS (DEMO MODE - EMPLOYEE ID) ============

case 'request_password_otp':
    $data = json_decode(file_get_contents('php://input'), true);
    $employee_id = trim($data['employee_id'] ?? '');
    $company_code = $data['company_code'] ?? '';
    
    // Validate inputs
    if (empty($employee_id) || empty($company_code)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID and Company Code are required']);
        break;
    }
    
    // Find user by EMPLOYEE ID
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, u.username, e.full_name, e.employee_id, c.name as company_name, c.id as company_id
        FROM employees e
        JOIN users u ON e.user_id = u.id
        JOIN companies c ON e.company_id = c.id
        WHERE e.employee_id = ? AND c.company_code = ?
    ");
    $stmt->execute([$employee_id, $company_code]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'No account found with this Employee ID and Company Code']);
        break;
    }
    
    // Generate 6-digit OTP
    $otp_code = sprintf("%06d", mt_rand(1, 999999));
    
    // Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_otp (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        otp_code VARCHAR(6) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $expires_at = date('Y-m-d H:i:s', time() + 300);
    
    // Delete old OTPs
    $stmt = $pdo->prepare("UPDATE password_reset_otp SET used = 1 WHERE email = ? AND used = 0");
    $stmt->execute([$user['email']]);
    
    // Insert new OTP
    $stmt = $pdo->prepare("INSERT INTO password_reset_otp (email, otp_code, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['email'], $otp_code, $expires_at]);
    
    // DEMO MODE: Return OTP directly
    echo json_encode([
        'success' => true,
        'message' => 'OTP generated (DEMO MODE)',
        'test_otp' => $otp_code,
        'reset_id' => $user['id'],
        'email' => $user['email']
    ]);
    break;
    
case 'verify_reset_otp':
    $data = json_decode(file_get_contents('php://input'), true);
    $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $otp_code = $data['otp_code'] ?? '';
    $user_id = $data['user_id'] ?? 0;
    
    if (empty($email) || empty($otp_code)) {
        echo json_encode(['success' => false, 'message' => 'Email and OTP code are required']);
        break;
    }
    
    // Verify OTP
    $stmt = $pdo->prepare("
        SELECT * FROM password_reset_otp 
        WHERE email = ? AND otp_code = ? AND used = 0 AND expires_at > NOW()
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$email, $otp_code]);
    $otp_record = $stmt->fetch();
    
    if (!$otp_record) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP code']);
        break;
    }
    
    // Mark OTP as used
    $stmt = $pdo->prepare("UPDATE password_reset_otp SET used = 1 WHERE id = ?");
    $stmt->execute([$otp_record['id']]);
    
    // Create password_resets table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Generate temporary reset token
    $reset_token = bin2hex(random_bytes(32));
    $token_expires = date('Y-m-d H:i:s', time() + 900);
    
    // Delete existing tokens
    $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0");
    $stmt->execute([$user_id]);
    
    // Insert new reset token
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $email, $reset_token, $token_expires]);
    
    echo json_encode([
        'success' => true,
        'message' => 'OTP verified',
        'reset_token' => $reset_token
    ]);
    break;
    
case 'reset_password_with_token':
    $data = json_decode(file_get_contents('php://input'), true);
    $reset_token = $data['reset_token'] ?? '';
    $new_password = $data['new_password'] ?? '';
    $confirm_password = $data['confirm_password'] ?? '';
    
    if (empty($reset_token) || empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'Reset token and new password are required']);
        break;
    }
    
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        break;
    }
    
    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
        break;
    }
    
    if (!preg_match('/[A-Z]/', $new_password) || 
        !preg_match('/[a-z]/', $new_password) || 
        !preg_match('/[0-9]/', $new_password)) {
        echo json_encode(['success' => false, 'message' => 'Password must contain uppercase, lowercase, and numbers']);
        break;
    }
    
    // Validate reset token
    $stmt = $pdo->prepare("
        SELECT * FROM password_resets 
        WHERE token = ? AND used = 0 AND expires_at > NOW()
    ");
    $stmt->execute([$reset_token]);
    $reset = $stmt->fetch();
    
    if (!$reset) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token']);
        break;
    }
    
    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ?, password_last_changed = NOW() WHERE id = ?");
    $stmt->execute([$hashed_password, $reset['user_id']]);
    
    // Mark token as used
    $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
    $stmt->execute([$reset_token]);
    
    echo json_encode(['success' => true, 'message' => 'Password has been reset successfully']);
    break;

        case 'forgot_password':
            $data = json_decode(file_get_contents('php://input'), true);
            $employee_id = $data['employee_id'] ?? '';
            $company_code = $data['company_code'] ?? '';
            
            if (empty($employee_id) || empty($company_code)) {
                echo json_encode(['success' => false, 'message' => 'Employee ID and Company Code are required']);
                break;
            }
            
            // Find employee by employee_id and company_code
            $stmt = $pdo->prepare("
                SELECT e.id, e.full_name, e.email, u.id as user_id, u.username, c.name as company_name
                FROM employees e
                JOIN users u ON e.user_id = u.id
                JOIN companies c ON e.company_id = c.id
                WHERE e.employee_id = ? AND c.company_code = ?
            ");
            $stmt->execute([$employee_id, $company_code]);
            $employee = $stmt->fetch();
            
            // Always return generic message to prevent information disclosure
            if (!$employee) {
                echo json_encode(['success' => false, 'message' => 'Invalid Employee ID or Company Code']);
                break;
            }
            
            // Generate secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
            
            // Store token
            $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$employee['user_id'], $employee['email'] ?? '', $token, $expires]);
            
            // Return token for immediate password reset (in production, this would be sent via email/SMS)
            echo json_encode([
                'success' => true, 
                'message' => 'Verification successful. You can now reset your password.',
                'reset_token' => $token,
                'employee_name' => $employee['full_name'],
                'username' => $employee['username']
            ]);
            break;

        case 'verify_2fa':
            $data = json_decode(file_get_contents('php://input'), true);
            $user_id = $data['user_id'] ?? 0;
            $code = $data['code'] ?? '';
            
            if (empty($code) || !$user_id) {
                echo json_encode(['success' => false, 'message' => 'User ID and 2FA code are required']);
                break;
            }
            
            // Get user details
            $stmt = $pdo->prepare("SELECT u.*, c.name as company_name, c.company_code, c.timezone as company_timezone, e.full_name as emp_full_name FROM users u JOIN companies c ON u.company_id = c.id LEFT JOIN employees e ON u.id = e.user_id WHERE u.id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                break;
            }
            
            if (verify2FALogin($pdo, $user_id, $code)) {
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                resetFailedAttempts($pdo, $ip_address);
                
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['company_id'] = $user['company_id'];
                $_SESSION['role'] = trim($user['role']);
                $_SESSION['company_name'] = $user['company_name'];
                $_SESSION['company_code'] = $user['company_code'] ?? '';
                $_SESSION['company_timezone'] = $user['company_timezone'] ?: 'Asia/Manila';
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['emp_full_name'] ?: $user['username'];
                
                logAudit($pdo, $user['company_id'], $user['id'], AUDIT_LOGIN, 'user', $user['id']);
                
                echo json_encode(['success' => true, 'role' => trim($user['role']), 'company_name' => $user['company_name']]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid 2FA code']);
            }
            break;

        case 'signup':
            $data = json_decode(file_get_contents('php://input'), true);
            $company_name = $data['company_name'] ?? '';
            $company_code = $data['company_code'] ?? ''; // New field from frontend
            $username = $data['username'] ?? '';
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            if (empty($company_name) || empty($username) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                break;
            }

            // BULLETPROOF company_code generation - handles edge cases
            $company_code = trim($company_code);
            
            // 1. Sanitize input if provided
            if (!empty($company_code)) {
                $company_code = strtoupper(preg_replace('/[^A-Z0-9-]/', '', $company_code));
                if (strlen($company_code) > 20) {
                    $company_code = substr($company_code, 0, 20);
                }
            }
            
            // 2. Auto-generate if still empty
            if (empty($company_code)) {
                $base = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', $company_name), 0, 4));
                if (empty($base)) $base = 'COMP'; // Safeguard
                
                $max_attempts = 50; // Prevent infinite loop
                $attempt = 0;
                
                do {
                    $attempt++;
                    $random_part = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4);
                    $company_code = $base . '-' . $random_part;
                    
                    $stmt_check = $pdo->prepare("SELECT id FROM companies WHERE company_code = ?");
                    $stmt_check->execute([$company_code]);
                    
                    if ($attempt >= $max_attempts) {
                        // Final fallback - use timestamp
                        $company_code = $base . '-' . date('His');
                        break;
                    }
                } while ($stmt_check->fetch());
            }
            
            // 3. Final validation - should never be empty now
            if (empty($company_code) || strlen($company_code) < 3) {
                throw new Exception('Failed to generate valid company code');
            }

            $pdo->beginTransaction();
            try {
                // FIXED: Include company_code in INSERT
                $stmt = $pdo->prepare("INSERT INTO companies (name, admin_email, company_code) VALUES (?, ?, ?)");
                $stmt->execute([$company_name, $email, $company_code]);
                $company_id = $pdo->lastInsertId();

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // Assign Admin/HR role (Admin) to new signups
                $stmt = $pdo->prepare("INSERT INTO users (company_id, username, password, role, email, is_active) VALUES (?, ?, ?, 'Admin', ?, 1)");
                $stmt->execute([$company_id, $username, $hashed_password, $email]);
                $user_id = $pdo->lastInsertId();

                $pdo->commit();

                // Log the user in immediately so they can access the dashboard right away
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['company_id'] = $company_id;
                $_SESSION['role'] = 'Admin';
                $_SESSION['company_name'] = $company_name;
                $_SESSION['company_code'] = $company_code;
                $_SESSION['company_timezone'] = 'Asia/Manila';
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $username;

                echo json_encode([
                    'success' => true,
                    'role' => 'Admin',
                    'company_code' => $company_code,
                    'company_name' => $company_name,
                    'message' => 'Account created successfully. Company Code: ' . $company_code
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Signup failed: ' . $e->getMessage()]);
            }
            break;

        case 'get_faculty_payroll':
            if (!isPayrollOrHigher())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $period = $_GET['period'] ?? '';

            if ($period === 'latest' || $period === 'current' || empty($period)) {
                $stmt_latest = $pdo->prepare("SELECT period FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.company_id = ? AND p.payroll_type = 'Faculty' AND e.position = 'Faculty' ORDER BY p.created_at DESC LIMIT 1");
                $stmt_latest->execute([$_SESSION['company_id']]);
                $period = $stmt_latest->fetchColumn() ?: '';
            }

            $stmt = $pdo->prepare("SELECT p.*, e.full_name, e.employee_id as emp_code, e.basic_salary 
                                 FROM payroll p 
                                 JOIN employees e ON p.employee_id = e.id 
                                 WHERE p.company_id = ? AND p.payroll_type = 'Faculty' AND e.position = 'Faculty' 
                                 AND p.period = ?");
            $stmt->execute([$_SESSION['company_id'], $period]);
            $results = $stmt->fetchAll();
            echo json_encode(['period' => $period, 'data' => $results]);
            break;

        case 'get_utility_payroll':
            if (!isPayrollOrHigher())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $period = $_GET['period'] ?? '';

            if ($period === 'latest' || $period === 'current' || empty($period)) {
                $stmt_latest = $pdo->prepare("SELECT period FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.company_id = ? AND p.payroll_type = 'Utility' AND e.position = 'Utility' ORDER BY p.created_at DESC LIMIT 1");
                $stmt_latest->execute([$_SESSION['company_id']]);
                $period = $stmt_latest->fetchColumn() ?: '';
            }

            $stmt = $pdo->prepare("SELECT p.*, e.full_name, e.employee_id as emp_code, e.basic_salary 
                                 FROM payroll p 
                                 JOIN employees e ON p.employee_id = e.id 
                                 WHERE p.company_id = ? AND p.payroll_type = 'Utility' AND e.position = 'Utility' 
                                 AND p.period = ?");
            $stmt->execute([$_SESSION['company_id'], $period]);
            $results = $stmt->fetchAll();
            echo json_encode(['period' => $period, 'data' => $results]);
            break;

        case 'update_payroll_field':
            if (!isPayrollOrHigher())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            
            $data = json_decode(file_get_contents('php://input'), true);
            $payroll_id = $data['payroll_id'] ?? null;
            $field = $data['field'] ?? null;
            $value = $data['value'] ?? null;
            
            // Validation
            if (!$payroll_id || !$field || $value === null) {
                exit(json_encode(['success' => false, 'message' => 'Missing required fields']));
            }
            
            // Whitelist allowed fields to prevent SQL injection
            $allowed_fields = [
                'load_pay', 'overtime_pay', 'differential_pay', 'substitution_pay',
                'adj_plus', 'adj_minus', 'honorarium', 'absence_deduction',
                'late_deduction', 'hdmf_contribution', 'hdmf_loans', 'hdmf_mp2',
                'ot_holiday_pay', 'cash_advance'
            ];
            
            if (!in_array($field, $allowed_fields)) {
                exit(json_encode(['success' => false, 'message' => 'Invalid field name']));
            }
            
            // Validate value is numeric
            if (!is_numeric($value)) {
                exit(json_encode(['success' => false, 'message' => 'Value must be numeric']));
            }
            
            try {
                $stmt = $pdo->prepare("UPDATE payroll SET {$field} = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([$value, $payroll_id, $_SESSION['company_id']]);
                
                if ($stmt->rowCount() > 0) {
                    // Log audit trail
                    logAudit($pdo, $_SESSION['company_id'], $_SESSION['user_id'], AUDIT_UPDATE_PAYROLL, 'payroll', $payroll_id, "Updated {$field} to {$value}");
                    
                    echo json_encode(['success' => true, 'message' => 'Field updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Record not found or no changes made']);
                }
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            break;

        case 'run_specialized_payroll':
            if (!isPayrollOrHigher())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $type = $data['type'] ?? '';
            $start_date = $data['start_date'] ?? '';
            $end_date = $data['end_date'] ?? '';

            // Centralized validation
            $errors = validateRequired($data, ['type', 'start_date', 'end_date']);
            if (!in_array($type, ['faculty', 'utility'])) {
                $errors[] = 'Invalid payroll type. Must be faculty or utility';
            }
            $errors = array_merge($errors, validateDateRange($start_date, $end_date));
            rejectInvalidPayload($errors);

            $period = date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));
            $company_id = $_SESSION['company_id'];

            $position = ($type === 'faculty') ? 'Faculty' : 'Utility';

            $stmt_company = $pdo->prepare("SELECT deduction_per_min FROM companies WHERE id = ?");
            $stmt_company->execute([$company_id]);
            $company = $stmt_company->fetch();
            $deduction_per_min = isset($company['deduction_per_min']) ? (float) $company['deduction_per_min'] : 0.50;

            $period = date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));
            $company_id = $_SESSION['company_id'];

            $position = ($type === 'faculty') ? 'Faculty' : 'Utility';

            $stmt_company = $pdo->prepare("SELECT deduction_per_min FROM companies WHERE id = ?");
            $stmt_company->execute([$company_id]);
            $company = $stmt_company->fetch();
            $deduction_per_min = isset($company['deduction_per_min']) ? (float) $company['deduction_per_min'] : 0.50;

            $stmt_employees = $pdo->prepare("SELECT * FROM employees WHERE company_id = ? AND position = ? AND status = 'Active'");
            $stmt_employees->execute([$company_id, $position]);
            $employees = $stmt_employees->fetchAll();
            $employee_ids = array_column($employees, 'id');

            // Pre-fetch all attendance logs to avoid N+1 query problem
            $logs_by_emp = [];
            if (!empty($employee_ids)) {
                $placeholders = implode(',', array_fill(0, count($employee_ids), '?'));
                $stmt_all_att = $pdo->prepare("SELECT * FROM attendance WHERE employee_id IN ($placeholders) AND log_date BETWEEN ? AND ?");
                $stmt_all_att->execute(array_merge($employee_ids, [$start_date, $end_date]));
                while ($row = $stmt_all_att->fetch()) {
                    $logs_by_emp[$row['employee_id']][] = $row;
                }
            }

            $pdo->beginTransaction();
            try {
                foreach ($employees as $emp) {
                    $logs = $logs_by_emp[$emp['id']] ?? [];

                    $total_absent = 0;
                    $total_late_min = 0;
                    $days_present = 0;
                    foreach ($logs as $l) {
                        if ($l['status'] === 'Late')
                            $total_late_min += (int) $l['late_minutes'];
                        if ($l['status'] === 'Absent')
                            $total_absent++;
                        if (!empty($l['check_in']))
                            $days_present++;
                    }

                    if ($type === 'faculty') {
                        // Faculty specific calculations (17 columns)
                        $basic_pay = (float) $emp['basic_salary'] / 2;
                        $load_pay = 0;
                        $overtime = 0;
                        $differential = 0;
                        $substitution = 0;
                        $adj_plus = 0;
                        $absences_deduction = $total_absent * (((float) $emp['basic_salary']) / 22);
                        $late_ut = $total_late_min * $deduction_per_min;
                        $hdmf_cont = !empty($emp['pagibig']) ? 100 : 0;
                        $hdmf_loans = 0;
                        $hdmf_mp2 = 0;
                        
                        // Fetch employee-specific allowances
                        $stmt_allowances = $pdo->prepare("SELECT ea.*, ac.name, ac.type, ac.rate FROM employee_allowances ea JOIN allowance_categories ac ON ea.category_id = ac.id WHERE ea.employee_id = ? AND ea.company_id = ? AND (ea.effective_date IS NULL OR ea.effective_date <= ?)");
                        $stmt_allowances->execute([$emp['id'], $company_id, $end_date]);
                        $employee_allowances = $stmt_allowances->fetchAll();
                        
                        // Calculate total allowances
                        $total_allowances = 0;
                        foreach ($employee_allowances as $allowance) {
                            $amount = $allowance['override_amount'] ?? null;
                            if ($amount === null) {
                                $amount = $allowance['type'] === 'Percentage' ? $basic_pay * ($allowance['rate'] / 100) : $allowance['rate'];
                            }
                            $total_allowances += (float)$amount;
                        }
                        
                        // Fetch employee-specific deductions
                        $stmt_emp_deductions = $pdo->prepare("SELECT ed.*, d.name, d.type, d.value FROM employee_deductions ed JOIN deductions d ON ed.deduction_id = d.id WHERE ed.employee_id = ? AND ed.company_id = ? AND (ed.effective_date IS NULL OR ed.effective_date <= ?)");
                        $stmt_emp_deductions->execute([$emp['id'], $company_id, $end_date]);
                        $employee_deductions = $stmt_emp_deductions->fetchAll();
                        
                        // Calculate employee-specific deductions
                        $employee_specific_deductions = 0;
                        foreach ($employee_deductions as $deduction) {
                            $amount = $deduction['override_amount'] ?? null;
                            if ($amount === null) {
                                $amount = $deduction['type'] === 'percentage' ? $basic_pay * ($deduction['value'] / 100) : $deduction['value'];
                            }
                            $employee_specific_deductions += (float)$amount;
                        }
                        
                        $total_deduction = $absences_deduction + $late_ut + $hdmf_cont + $hdmf_loans + $hdmf_mp2 + $employee_specific_deductions;
                        $honorarium = 0;
                        $net_pay = ($basic_pay + $load_pay + $overtime + $differential + $substitution + $adj_plus + $honorarium + $total_allowances) - $total_deduction;

                        $breakdown = [
                            'load_pay' => $load_pay,
                            'overtime' => $overtime,
                            'differential' => $differential,
                            'substitution' => $substitution,
                            'adj_plus' => $adj_plus,
                            'total_allowances' => $total_allowances,
                            'absences' => $absences_deduction,
                            'late_ut' => $late_ut,
                            'hdmf_cont' => $hdmf_cont,
                            'hdmf_loans' => $hdmf_loans,
                            'hdmf_mp2' => $hdmf_mp2,
                            'employee_deductions' => $employee_specific_deductions,
                            'total_deduction' => $total_deduction,
                            'honorarium' => $honorarium,
                            'days_present' => $days_present,
                            'absent_days' => $total_absent,
                            'late_minutes' => $total_late_min
                        ];

                        $stmt = $pdo->prepare("REPLACE INTO payroll (company_id, employee_id, payroll_type, period, basic_pay, deductions, net_pay, breakdown, status) 
                                         VALUES (?, ?, 'Faculty', ?, ?, ?, ?, ?, 'Paid')");
                        $stmt->execute([$company_id, $emp['id'], $period, $basic_pay, $total_deduction, $net_pay, json_encode($breakdown)]);
                    } else {
                        // Utility specific calculations (15 columns)
                        $rate_per_day = $emp['basic_salary'] / 22;
                        $earned = $rate_per_day * $days_present;
                        $ot_holiday = 0;
                        $adj_plus = 0;
                        $late_ut = $total_late_min * $deduction_per_min;
                        $adj_minus = 0;
                        $hdmf_cont = !empty($emp['pagibig']) ? 100 : 0;
                        $hdmf_loans = 0;
                        $cash_advance = 0;
                        
                        // Fetch employee-specific allowances
                        $stmt_allowances = $pdo->prepare("SELECT ea.*, ac.name, ac.type, ac.rate FROM employee_allowances ea JOIN allowance_categories ac ON ea.category_id = ac.id WHERE ea.employee_id = ? AND ea.company_id = ? AND (ea.effective_date IS NULL OR ea.effective_date <= ?)");
                        $stmt_allowances->execute([$emp['id'], $company_id, $end_date]);
                        $employee_allowances = $stmt_allowances->fetchAll();
                        
                        // Calculate total allowances
                        $total_allowances = 0;
                        foreach ($employee_allowances as $allowance) {
                            $amount = $allowance['override_amount'] ?? null;
                            if ($amount === null) {
                                $amount = $allowance['type'] === 'Percentage' ? $earned * ($allowance['rate'] / 100) : $allowance['rate'];
                            }
                            $total_allowances += (float)$amount;
                        }
                        
                        // Fetch employee-specific deductions
                        $stmt_emp_deductions = $pdo->prepare("SELECT ed.*, d.name, d.type, d.value FROM employee_deductions ed JOIN deductions d ON ed.deduction_id = d.id WHERE ed.employee_id = ? AND ed.company_id = ? AND (ed.effective_date IS NULL OR ed.effective_date <= ?)");
                        $stmt_emp_deductions->execute([$emp['id'], $company_id, $end_date]);
                        $employee_deductions = $stmt_emp_deductions->fetchAll();
                        
                        // Calculate employee-specific deductions
                        $employee_specific_deductions = 0;
                        foreach ($employee_deductions as $deduction) {
                            $amount = $deduction['override_amount'] ?? null;
                            if ($amount === null) {
                                $amount = $deduction['type'] === 'percentage' ? $earned * ($deduction['value'] / 100) : $deduction['value'];
                            }
                            $employee_specific_deductions += (float)$amount;
                        }
                        
                        $total_deduction = $late_ut + $adj_minus + $hdmf_cont + $hdmf_loans + $cash_advance + $employee_specific_deductions;
                        $net_pay = ($earned + $ot_holiday + $adj_plus + $total_allowances) - $total_deduction;
                        $atm = $net_pay;
                        $non_atm = 0;

                        $breakdown = [
                            'rate_per_day' => $rate_per_day,
                            'earned' => $earned,
                            'ot_holiday' => $ot_holiday,
                            'adj_plus' => $adj_plus,
                            'total_allowances' => $total_allowances,
                            'late_ut' => $late_ut,
                            'adj_minus' => $adj_minus,
                            'hdmf_cont' => $hdmf_cont,
                            'hdmf_loans' => $hdmf_loans,
                            'cash_advance' => $cash_advance,
                            'employee_deductions' => $employee_specific_deductions,
                            'total_deduction' => $total_deduction,
                            'atm' => $atm,
                            'non_atm' => $non_atm,
                            'days_present' => $days_present,
                            'absent_days' => $total_absent,
                            'late_minutes' => $total_late_min
                        ];

                        $stmt = $pdo->prepare("REPLACE INTO payroll (company_id, employee_id, payroll_type, period, basic_pay, deductions, net_pay, breakdown, status) 
                                         VALUES (?, ?, 'Utility', ?, ?, ?, ?, ?, 'Paid')");
                        $stmt->execute([$company_id, $emp['id'], $period, $earned, $total_deduction, $net_pay, json_encode($breakdown)]);
                    }
                }
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => ucfirst($type) . ' payroll processed successfully', 'period' => $period]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'get_allowance_categories':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $stmt = $pdo->prepare("SELECT * FROM allowance_categories WHERE company_id = ? ORDER BY name ASC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'add_allowance_category':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO allowance_categories (company_id, name, type, rate, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['company_id'], $data['name'], $data['type'], $data['rate'], $data['description']]);
            echo json_encode(['success' => true, 'message' => 'Category added successfully']);
            break;

        case 'get_employee_allowances':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $stmt = $pdo->prepare("SELECT ea.*, e.full_name, e.employee_id as emp_code, ac.name as category_name, ac.type as category_type, ac.rate as category_rate 
                                 FROM employee_allowances ea 
                                 JOIN employees e ON ea.employee_id = e.id 
                                 JOIN allowance_categories ac ON ea.category_id = ac.id 
                                 WHERE ea.company_id = ? ORDER BY ea.created_at DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'assign_employee_allowance':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO employee_allowances (company_id, employee_id, category_id, override_amount, effective_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['company_id'], $data['employee_id'], $data['category_id'], $data['override_amount'], $data['effective_date']]);
            echo json_encode(['success' => true, 'message' => 'Allowance assigned successfully']);
            break;

        case 'delete_allowance_category':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
            if (!$id || $id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM allowance_categories WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true, 'message' => 'Category deleted']);
            break;

        case 'delete_employee_allowance':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
            if (!$id || $id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM employee_allowances WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true, 'message' => 'Assignment deleted']);
            break;

        case 'bulk_assign_allowance':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $category_id = $data['category_id'];
            $override_amount = $data['override_amount'] ?: null;
            $effective_date = $data['effective_date'] ?: date('Y-m-d');

            $pdo->beginTransaction();
            try {
                // Efficient Batch Insert/Update
                $stmt = $pdo->prepare("INSERT INTO employee_allowances (company_id, employee_id, category_id, override_amount, effective_date) 
                                     SELECT ?, id, ?, ?, ? FROM employees WHERE company_id = ? AND status = 'Active'
                                     ON DUPLICATE KEY UPDATE override_amount = VALUES(override_amount), effective_date = VALUES(effective_date)");
                $stmt->execute([$_SESSION['company_id'], $category_id, $override_amount, $effective_date, $_SESSION['company_id']]);

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Allowance applied to all active employees']);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'get_deduction_breakdown':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $stmt = $pdo->prepare("SELECT ed.*, e.full_name, e.employee_id as emp_code, d.name as category_name, d.type as category_type, d.value as category_rate 
                                 FROM employee_deductions ed 
                                 JOIN employees e ON ed.employee_id = e.id 
                                 JOIN deductions d ON ed.deduction_id = d.id 
                                 WHERE ed.company_id = ? ORDER BY ed.created_at DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'assign_employee_deduction':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO employee_deductions (company_id, employee_id, deduction_id, override_amount, effective_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['company_id'], $data['employee_id'], $data['deduction_id'], $data['override_amount'], $data['effective_date']]);
            echo json_encode(['success' => true, 'message' => 'Deduction assigned successfully']);
            break;

        case 'delete_employee_deduction':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
            if (!$id || $id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM employee_deductions WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true, 'message' => 'Assignment deleted']);
            break;

        case 'bulk_assign_deduction':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $deduction_id = $data['deduction_id'];
            $override_amount = $data['override_amount'] ?: null;
            $effective_date = $data['effective_date'] ?: date('Y-m-d');

            $pdo->beginTransaction();
            try {
                // Efficient Batch Insert/Update
                $stmt = $pdo->prepare("INSERT INTO employee_deductions (company_id, employee_id, deduction_id, override_amount, effective_date) 
                                     SELECT ?, id, ?, ?, ? FROM employees WHERE company_id = ? AND status = 'Active'
                                     ON DUPLICATE KEY UPDATE override_amount = VALUES(override_amount), effective_date = VALUES(effective_date)");
                $stmt->execute([$_SESSION['company_id'], $deduction_id, $override_amount, $effective_date, $_SESSION['company_id']]);

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Deduction applied to all active employees']);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'revoke_payroll_access':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
            if (!$id || $id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                break;
            }
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE employees SET position = 'Staff' WHERE id = ? AND company_id = ?");
                $stmt->execute([$id, $_SESSION['company_id']]);

                $stmt_user = $pdo->prepare("UPDATE users u JOIN employees e ON u.id = e.user_id SET u.role = 'Employee' WHERE e.id = ?");
                $stmt_user->execute([$id]);

                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'get_deduction_categories':
            if (!isPayrollOrHigher())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $stmt = $pdo->prepare("SELECT * FROM deductions WHERE company_id = ?");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_employee_deductions':
            if (!isPayrollOrHigher())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $stmt = $pdo->prepare("SELECT ed.*, e.full_name, e.employee_id as emp_code, d.name as category_name FROM employee_deductions ed JOIN employees e ON ed.employee_id = e.id JOIN deductions d ON ed.deduction_id = d.id WHERE ed.company_id = ?");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'save_settings':
            if (!isAdminOrHR()) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
                break;
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $company_id = $_SESSION['company_id'];

            // Basic validation
            if (empty($data['companyName'])) {
                echo json_encode(['success' => false, 'message' => 'Company Name is required']);
                break;
            }

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE companies SET name=?, timezone=?, work_start=?, work_end=?, lunch_out_start=?, lunch_out_end=?, lunch_in_start=?, lunch_in_end=?, lunch_buffer=?, checkout_buffer=?, ot_percentage=?, deduction_per_sec=?, deduction_per_min=?, deduction_per_hour=? WHERE id=?");
                $success = $stmt->execute([
                    $data['companyName'],
                    $data['timezone'] ?: 'Asia/Manila',
                    $data['workStart'] ?: '08:00:00',
                    $data['workEnd'] ?: '17:00:00',
                    $data['lunchOutStart'] ?: '10:00:00',
                    $data['lunchOutEnd'] ?: '10:30:00',
                    $data['lunchInStart'] ?: '10:30:00',
                    $data['lunchInEnd'] ?: '11:00:00',
                    (int) ($data['lunchBuffer'] ?? 30),
                    (int) ($data['checkoutBuffer'] ?? 60),
                    (int) ($data['otPercentage'] ?? 25),
                    (float) ($data['deductionPerSec'] ?? 0.0083),
                    (float) ($data['deductionPerMin'] ?? 0.50),
                    (float) ($data['deductionPerHour'] ?? 30.00),
                    $company_id
                ]);

                if ($success) {
                    $pdo->commit();
                    $_SESSION['company_timezone'] = $data['timezone'] ?: 'Asia/Manila';
                    $_SESSION['company_name'] = $data['companyName'];
                    echo json_encode(['success' => true]);
                } else {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Database update failed']);
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'logout':
            // Clear all session data
            $_SESSION = [];

            // Delete the session cookie from the browser
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }

            session_destroy();
            echo json_encode(['success' => true]);
            break;

        case 'get_employees':
            if (!isset($_SESSION['company_id']))
                exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT e.*, u.username, u.role FROM employees e LEFT JOIN users u ON e.user_id = u.id WHERE e.company_id = ?");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'upload_profile_picture':
            if (!isset($_SESSION['user_id']))
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            
            // Check if file was uploaded
            if (!isset($_FILES['profile_picture'])) {
                echo json_encode(['success' => false, 'message' => 'No file uploaded']);
                break;
            }
            
            $file = $_FILES['profile_picture'];
            
            // Validate file
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Upload error occurred']);
                break;
            }
            
            if (!in_array($file['type'], $allowed_types)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed']);
                break;
            }
            
            if ($file['size'] > $max_size) {
                echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
                break;
            }
            
            // Create upload directory if it doesn't exist
            $upload_dir = dirname(__DIR__) . '/uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
            $upload_path = $upload_dir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Delete old profile picture if exists
                $stmt = $pdo->prepare("SELECT profile_picture FROM employees WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $old_picture = $stmt->fetchColumn();
                
                if ($old_picture && file_exists($upload_dir . basename($old_picture))) {
                    unlink($upload_dir . basename($old_picture));
                }
                
                // Update database
                $picture_url = 'uploads/profiles/' . $filename;
                $stmt = $pdo->prepare("UPDATE employees SET profile_picture = ? WHERE user_id = ?");
                $stmt->execute([$picture_url, $_SESSION['user_id']]);
                
                echo json_encode(['success' => true, 'picture_url' => $picture_url]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
            }
            break;

        case 'save_employee':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);

            // Combine firstName, lastName, and middleInitial into fullName
            $firstName = trim($data['firstName'] ?? '');
            $lastName = trim($data['lastName'] ?? '');
            $middleInitial = trim($data['middleInitial'] ?? '');
            
            if (empty($firstName) || empty($lastName)) {
                exit(json_encode(['success' => false, 'message' => 'First Name and Last Name are required']));
            }
            
            // Build full name: "FirstName MiddleInitial. LastName" or "FirstName LastName"
            $fullName = $firstName . ($middleInitial ? ' ' . strtoupper($middleInitial) . '.' : '') . ' ' . $lastName;
            $data['fullName'] = $fullName;

            // Centralized validation
            $errors = validateRequired($data, ['fullName', 'dob', 'email', 'position', 'department', 'basicSalary', 'hire_date']);
            $errors = array_merge($errors, validateDate($data['dob'], 'dob'));
            $errors = array_merge($errors, validateDate($data['hire_date'] ?? '', 'hire_date'));
            $errors = array_merge($errors, validateAmount($data['basicSalary'], 'basicSalary', 0));
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
            
            // Validate phone number if provided
            if (!empty($data['contactNo'])) {
                $phone = preg_replace('/[\s\-]/', '', $data['contactNo']);
                if (!preg_match('/^(09|\+639)\d{9}$/', $phone)) {
                    $errors[] = 'Invalid phone number format. Use: 09XXXXXXXXX or +639XXXXXXXXX';
                }
            }
            
            // Validate faculty_level for Faculty position
            if ($data['position'] === 'Faculty' && empty($data['faculty_level'])) {
                $errors[] = 'Faculty level is required for Faculty position';
            }
            
            // Validate government ID formats if provided
            if (!empty($data['sss'])) {
                $sss = preg_replace('/[\s\-]/', '', $data['sss']);
                if (!preg_match('/^\d{10,11}$/', $sss)) {
                    $errors[] = 'Invalid SSS number format';
                }
            }
            if (!empty($data['tin'])) {
                $tin = preg_replace('/[\s\-]/', '', $data['tin']);
                if (!preg_match('/^\d{9,12}$/', $tin)) {
                    $errors[] = 'Invalid TIN format';
                }
            }
            if (!empty($data['philhealth'])) {
                $philhealth = preg_replace('/[\s\-]/', '', $data['philhealth']);
                if (!preg_match('/^\d{11,12}$/', $philhealth)) {
                    $errors[] = 'Invalid PhilHealth format';
                }
            }
            if (!empty($data['pagibig'])) {
                $pagibig = preg_replace('/[\s\-]/', '', $data['pagibig']);
                if (!preg_match('/^\d{12}$/', $pagibig)) {
                    $errors[] = 'Invalid Pag-IBIG format (12 digits required)';
                }
            }
            
            rejectInvalidPayload($errors);

            // Sanitize Email
            $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

            $basic_salary = (float) $data['basicSalary'];
            $status = (isset($data['status']) && is_string($data['status']) && trim($data['status']) !== '') ? trim($data['status']) : 'Active';

            if (isset($data['id']) && !empty($data['id'])) {
                // Update existing employee
                $stmt = $pdo->prepare("UPDATE employees SET full_name = ?, dob = ?, email = ?, position = ?, work_position = ?, department = ?, faculty_level = ?, hire_date = ?, basic_salary = ?, sss = ?, philhealth = ?, tin = ?, pagibig = ?, status = ?, work_status = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([
                    trim($data['fullName']),
                    $data['dob'],
                    $email,
                    $data['position'],
                    $data['work_position'] ?? null,
                    $data['department'],
                    $data['faculty_level'] ?? null,
                    $data['hire_date'] ?? date('Y-m-d'),
                    $basic_salary,
                    trim($data['sss'] ?? ''),
                    trim($data['philhealth'] ?? ''),
                    trim($data['tin'] ?? ''),
                    trim($data['pagibig'] ?? ''),
                    $status,
                    $data['work_status'] ?? null,
                    $data['id'],
                    $_SESSION['company_id']
                ]);
            } else {
                // Create new employee
                $pdo->beginTransaction();
                try {
                    // Check if email already exists
                    $stmt_check = $pdo->prepare("SELECT id FROM employees WHERE email = ? AND company_id = ?");
                    $stmt_check->execute([$email, $_SESSION['company_id']]);
                    if ($stmt_check->fetch()) {
                        throw new Exception("Email already registered in this company.");
                    }

                    $stmt = $pdo->prepare("SELECT id FROM employees WHERE company_id = ? ORDER BY id DESC LIMIT 1");
                    $stmt->execute([$_SESSION['company_id']]);
                    $last_id = $stmt->fetchColumn();
                    $num = $last_id ? (int) $last_id + 1 : 1;
                    $emp_id = 'EMP' . str_pad($num, 3, '0', STR_PAD_LEFT);

                    // Generate secure random password
                    $random_password = bin2hex(random_bytes(6)); // 12-char hex password
                    $hashed_pass = password_hash($random_password, PASSWORD_DEFAULT);
                    $username = strtolower(str_replace(' ', '.', trim($data['fullName'])));
                    $role = ($data['position'] === 'Payroll Officer') ? 'Payroll Officer' : 'Employee';

                    $stmt = $pdo->prepare("INSERT INTO users (company_id, username, password, role, email) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['company_id'], $username, $hashed_pass, $role, $email]);
                    $user_id = $pdo->lastInsertId();

                    $stmt = $pdo->prepare("INSERT INTO employees (company_id, employee_id, full_name, dob, email, position, work_position, department, faculty_level, hire_date, basic_salary, sss, philhealth, tin, pagibig, user_id, status, work_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_SESSION['company_id'],
                        $emp_id,
                        trim($data['fullName']),
                        $data['dob'],
                        $email,
                        $data['position'],
                        $data['work_position'] ?? null,
                        $data['department'],
                        $data['faculty_level'] ?? null,
                        $data['hire_date'] ?? date('Y-m-d'),
                        $basic_salary,
                        trim($data['sss'] ?? ''),
                        trim($data['philhealth'] ?? ''),
                        trim($data['tin'] ?? ''),
                        trim($data['pagibig'] ?? ''),
                        $user_id,
                        $status,
                        $data['work_status'] ?? null
                    ]);
                    $new_emp_id = $pdo->lastInsertId();

                    // Handle subjects if provided
                    if ($data['position'] === 'Faculty' && !empty($data['subjects']) && is_array($data['subjects'])) {
                        foreach ($data['subjects'] as $sub) {
                            if (empty($sub['description']))
                                continue;
                            $stmt_sub = $pdo->prepare("INSERT INTO subject_loads (company_id, faculty_id, code, description, units) VALUES (?, ?, ?, ?, ?)");
                            $stmt_sub->execute([
                                $_SESSION['company_id'],
                                $new_emp_id,
                                'AUTO',
                                trim($sub['description']),
                                (float) $sub['units']
                            ]);
                        }
                    }

                    $pdo->commit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    exit(json_encode(['success' => false, 'message' => $e->getMessage()]));
                }
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_employee':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = $_GET['id'] ?? '';
            $errors = validateId($id, 'id');
            rejectInvalidPayload($errors);
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'get_registered_faces':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $stmt = $pdo->prepare("SELECT id, full_name, face_descriptor FROM employees WHERE company_id = ? AND face_descriptor IS NOT NULL");
            $stmt->execute([$_SESSION['company_id']]);
            $faces = $stmt->fetchAll();
            echo json_encode(['success' => true, 'faces' => $faces]);
            break;

        case 'save_face_registration':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['id']) || empty($data['descriptor'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                break;
            }

            // Check for duplicate face within the same company
            $stmt_faces = $pdo->prepare("SELECT id, full_name, face_descriptor FROM employees WHERE company_id = ? AND face_descriptor IS NOT NULL AND id != ?");
            $stmt_faces->execute([$_SESSION['company_id'], $data['id']]);
            $existing_faces = $stmt_faces->fetchAll();

            $new_descriptor = array_map('floatval', $data['descriptor']);
            foreach ($existing_faces as $face) {
                $registered_descriptor = json_decode($face['face_descriptor'], true);
                if (is_array($registered_descriptor) && count($registered_descriptor) === 128) {
                    $sum = 0;
                    for ($i = 0; $i < 128; $i++) {
                        $diff = $new_descriptor[$i] - (float) $registered_descriptor[$i];
                        $sum += $diff * $diff;
                    }
                    $distance = sqrt($sum);
                    if ($distance < BIOMETRIC_DUPLICATE_THRESHOLD) {
                        echo json_encode(['success' => false, 'message' => "This face is already registered to " . $face['full_name']]);
                        return;
                    }
                }
            }

            $stmt = $pdo->prepare("UPDATE employees SET face_descriptor = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([json_encode($new_descriptor), $data['id'], $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'reset_password':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $target_user_id = filter_var($_GET['user_id'] ?? '', FILTER_VALIDATE_INT);
            if (!$target_user_id || $target_user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
                break;
            }

            // Security: Ensure target user belongs to the same company
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND company_id = ?");
            $stmt->execute([$target_user_id, $_SESSION['company_id']]);
            if (!$stmt->fetch()) {
                exit(json_encode(['success' => false, 'message' => 'User not found or access denied']));
            }

            // Generate secure random password
            $random_password = bin2hex(random_bytes(6)); // 12-char hex password
            $new_pass = password_hash($random_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$new_pass, $target_user_id, $_SESSION['company_id']]);
            echo json_encode(['success' => true, 'message' => 'Password has been reset. New password: ' . $random_password]);
            break;

        case 'get_attendance':
            if (!isset($_SESSION['company_id']))
                exit(json_encode([]));
            $date = $_GET['date'] ?? date('Y-m-d');
            $stmt = $pdo->prepare("SELECT a.*, e.full_name, e.employee_id as emp_code, e.position FROM attendance a JOIN employees e ON a.employee_id = e.id WHERE a.company_id = ? ORDER BY a.log_date DESC, a.check_in DESC");
            $stmt->execute([$_SESSION['company_id']]);
            $attendance = $stmt->fetchAll();
            
            // Enrich with schedule data for faculty
            $day_map = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $today_day_name = $day_map[(int)date('N', strtotime($date)) - 1];
            
            foreach ($attendance as &$row) {
                $row['schedule'] = [];
                if ($row['position'] === 'Faculty') {
                    $stmt_sched = $pdo->prepare("
                        SELECT ss.day_of_week, ss.time_start, ss.time_end, ss.room, 
                               sl.description as subject_description, sl.code as subject_code
                        FROM subject_schedules ss 
                        JOIN subject_loads sl ON ss.subject_load_id = sl.id 
                        WHERE sl.faculty_id = ? AND ss.day_of_week = ? AND ss.company_id = ?
                        ORDER BY ss.time_start
                    ");
                    $stmt_sched->execute([$row['employee_id'], $today_day_name, $_SESSION['company_id']]);
                    $schedules = $stmt_sched->fetchAll();
                    
                    // Fallback: if not found for today, try matching log_date day name
                    if (empty($schedules)) {
                        $log_day_name = $day_map[(int)date('N', strtotime($row['log_date'])) - 1];
                        $stmt_sched->execute([$row['employee_id'], $log_day_name, $_SESSION['company_id']]);
                        $schedules = $stmt_sched->fetchAll();
                    }
                    $row['schedule'] = $schedules;
                    
                    // Calculate on-time based on schedule
                    if (!empty($schedules) && $row['check_in']) {
                        $first_start = $schedules[0]['time_start'];
                        if ($row['check_in'] <= $first_start) {
                            $row['schedule_ontime_in'] = true;
                        } else {
                            $row['schedule_ontime_in'] = false;
                        }
                        
                        $last_end = end($schedules)['time_end'];
                        if ($row['check_out'] && $row['check_out'] >= $last_end) {
                            $row['schedule_ontime_out'] = true;
                        } else {
                            $row['schedule_ontime_out'] = $row['check_out'] ? false : null;
                        }
                    }
                }
            }
            
            echo json_encode($attendance);
            break;

        case 'kiosk_scan':
            $data = json_decode(file_get_contents('php://input'), true);
            $company_id = $data['company_id'] ?? null;
            if (!$company_id) {
                echo json_encode(['success' => false, 'message' => 'Company ID is required for kiosk scan']);
                break;
            }
            $descriptor = $data['descriptor'] ?? [];

            if (empty($descriptor) || count($descriptor) !== 128) {
                echo json_encode(['success' => false, 'message' => 'Invalid descriptor']);
                break;
            }

            // Fetch company-specific biometric thresholds
            $stmt_config = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
            $stmt_config->execute([$company_id]);
            $config = $stmt_config->fetch();
            if (!$config) {
                echo json_encode(['success' => false, 'message' => 'Company configuration not found']);
                break;
            }

            $match_threshold = (float) ($config['biometric_match_threshold'] ?? BIOMETRIC_MATCH_THRESHOLD);
            $ambiguity_ratio_threshold = (float) ($config['biometric_ambiguity_ratio'] ?? BIOMETRIC_AMBIGUITY_RATIO);

            // Fetch active registered faces for this company
            $stmt_faces = $pdo->prepare("SELECT id, full_name, face_descriptor FROM employees WHERE company_id = ? AND face_descriptor IS NOT NULL AND status = 'Active'");
            $stmt_faces->execute([$company_id]);
            $registered_faces = $stmt_faces->fetchAll();

            $best_match = null;
            $best_distance = 999;
            $second_best_distance = 999;

            $input_desc = array_map('floatval', $descriptor);

            foreach ($registered_faces as $face) {
                $registered_desc = json_decode($face['face_descriptor'], true);
                if (is_array($registered_desc) && count($registered_desc) === 128) {
                    $sum = 0;
                    for ($i = 0; $i < 128; $i++) {
                        $diff = $input_desc[$i] - (float) $registered_desc[$i];
                        $sum += $diff * $diff;
                    }

                    $distance = sqrt($sum);
                    if ($distance < $best_distance) {
                        $second_best_distance = $best_distance;
                        $best_distance = $distance;
                        $best_match = $face;
                    } elseif ($distance < $second_best_distance) {
                        $second_best_distance = $distance;
                    }
                }
            }

            if (!$best_match || $best_distance > $match_threshold) {
                $match_percentage = $best_distance < 999 ? max(0, round(100 - ($best_distance * 100 / 0.8), 2)) : 0;
                echo json_encode(['success' => false, 'message' => 'Face not recognized. Please position yourself clearly.', 'match_percentage' => $match_percentage]);
                break;
            }

            // Ambiguity check
            if ($second_best_distance < 999) {
                $ratio = ($best_distance > 0) ? ($second_best_distance / $best_distance) : 999;
                if ($ratio <= $ambiguity_ratio_threshold) {
                    echo json_encode(['success' => false, 'message' => 'Ambiguous match detected. Multiple similar faces found. Please try again or contact HR.', 'debug_ratio' => $ratio]);
                    break;
                }
            }

            // Improved Match Percentage formula
            // 0.0 distance = 100%, 0.6 distance (threshold) = ~70%, 0.8+ = 0%
            $match_percentage = max(0, round(100 - ($best_distance * 125), 2));
            $employee_id = $best_match['id'];

            // Use provided scan time from kiosk if available, else use current server time
            $scan_time_input = $data['scan_time'] ?? null;

            // Set company-specific timezone if available
            $cid_for_tz = $company_id ?? $_SESSION['company_id'] ?? null;
            if ($cid_for_tz) {
                $stmt_tz = $pdo->prepare("SELECT timezone FROM companies WHERE id = ?");
                $stmt_tz->execute([$cid_for_tz]);
                $company_tz = $stmt_tz->fetchColumn();
                if ($company_tz)
                    date_default_timezone_set($company_tz);
            }

            if ($scan_time_input) {
                try {
                    $dt = new DateTime($scan_time_input);
                    // Ensure the timezone is correctly handled if the client sent an ISO string
                    $dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
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

            // Fetch employee data
            $stmt_emp = $pdo->prepare("SELECT id, employee_id, position, created_at FROM employees WHERE id = ?");
            $stmt_emp->execute([$employee_id]);
            $emp_data = $stmt_emp->fetch();

            // Fetch existing log for today
            $stmt_log = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND log_date = ?");
            $stmt_log->execute([$employee_id, $date]);
            $log = $stmt_log->fetch();

            // Statistics for summary card
            $stmt_stats = $pdo->prepare("SELECT COUNT(*) as total_attendance FROM attendance WHERE employee_id = ? AND check_in IS NOT NULL");
            $stmt_stats->execute([$employee_id]);
            $stats = $stmt_stats->fetch();

            // Absence calculation (Mon-Fri)
            $joined = new DateTime($emp_data['created_at']);
            $today_dt = new DateTime();
            $interval = $joined->diff($today_dt);
            $work_days = 0;
            $temp_date = clone $joined;
            for ($i = 0; $i <= $interval->days; $i++) {
                if ($temp_date->format('N') < 6)
                    $work_days++;
                $temp_date->modify('+1 day');
            }
            $absent_count = max(0, $work_days - $stats['total_attendance']);

            $common_data = [
                'name' => $best_match['full_name'],
                'employee_id' => $emp_data['employee_id'],
                'position' => $emp_data['position'],
                'attendance_count' => $stats['total_attendance'],
                'absent_count' => $absent_count,
                'match_percentage' => $match_percentage
            ];

            // Determine Action based on strict time windows
            $column = '';
            $status = $log ? ($log['status'] ?? 'On-Time') : 'On-Time';
            $late_minutes = $log ? ($log['late_minutes'] ?? 0) : 0;

            $work_start = $config['work_start'] ?: '08:00:00';
            $work_end = $config['work_end'] ?: '17:00:00';
            $lunch_out_start = $config['lunch_out_start'] ?: '10:00:00';
            $lunch_out_end = $config['lunch_out_end'] ?: '10:30:00';
            $lunch_in_start = $config['lunch_in_start'] ?: '10:30:00';
            $lunch_in_end = $config['lunch_in_end'] ?: '11:00:00';
            $lunch_buffer = $config['lunch_buffer'] ?? 30;
            $checkout_buffer = $config['checkout_buffer'] ?? 60;
            $grace_period = 15; // Standard grace period

            // Column Determination based on logical sequence and state
            if (!$log || empty($log['check_in'])) {
                $column = 'check_in';
            } elseif ($time < $work_start && $time < $lunch_out_start) {
                $column = 'check_in';
            } elseif (empty($log['lunch_out'])) {
                if ($time > $lunch_out_end) {
                    $column = 'check_out';
                } else {
                    $column = 'lunch_out';
                }
            } elseif (empty($log['lunch_in'])) {
                if ($time > $lunch_in_end) {
                    $column = 'check_out';
                } else {
                    $column = 'lunch_in';
                }
            } else {
                $column = 'check_out';
            }

            // Time Window Enforcement
            if ($column === 'lunch_out') {
                if ($time < $lunch_out_start) {
                    echo json_encode(array_merge(['success' => false, 'message' => "TOO EARLY FOR LUNCH OUT (Starts " . date('h:i A', strtotime($lunch_out_start)) . ")", 'action' => $column, 'server_time' => $time], $common_data));
                    break;
                }
                if ($time > $lunch_out_end) {
                    echo json_encode(array_merge(['success' => false, 'message' => "LUNCH OUT RANGE EXPIRED (Ended " . date('h:i A', strtotime($lunch_out_end)) . ")", 'action' => $column, 'server_time' => $time], $common_data));
                    break;
                }
            }

            if ($column === 'lunch_in') {
                if ($time < $lunch_in_start) {
                    echo json_encode(array_merge(['success' => false, 'message' => "TOO EARLY FOR LUNCH IN (Starts " . date('h:i A', strtotime($lunch_in_start)) . ")", 'action' => $column, 'server_time' => $time], $common_data));
                    break;
                }
                if ($time > $lunch_in_end) {
                    echo json_encode(array_merge(['success' => false, 'message' => "LUNCH IN RANGE EXPIRED (Ended " . date('h:i A', strtotime($lunch_in_end)) . ")", 'action' => $column, 'server_time' => $time], $common_data));
                    break;
                }
                // Buffer check
                if ($log && !empty($log['lunch_out'])) {
                    $diff_minutes = round((strtotime($time) - strtotime($log['lunch_out'])) / 60);
                    if ($diff_minutes < $lunch_buffer) {
                        echo json_encode(array_merge(['success' => false, 'message' => "LUNCH BUFFER: Wait " . ($lunch_buffer - $diff_minutes) . " more mins.", 'action' => $column, 'server_time' => $time], $common_data));
                        break;
                    }
                }
            }

            if ($column === 'check_out') {
                if ($time < $work_end) {
                    echo json_encode(array_merge(['success' => false, 'message' => "TOO EARLY FOR TIME OUT (Shift ends " . date('h:i A', strtotime($work_end)) . ")", 'action' => $column, 'server_time' => $time], $common_data));
                    break;
                }
                // Buffer check
                if ($log && !empty($log['lunch_in'])) {
                    $diff_minutes = round((strtotime($time) - strtotime($log['lunch_in'])) / 60);
                    if ($diff_minutes < $checkout_buffer) {
                        echo json_encode(array_merge(['success' => false, 'message' => "TIME OUT BUFFER: Wait " . ($checkout_buffer - $diff_minutes) . " more mins.", 'action' => $column, 'server_time' => $time], $common_data));
                        break;
                    }
                }
            }

            // Late status calculation (only for check_in)
            if ($column === 'check_in') {
                // For faculty, check against first subject load schedule
                $day_names = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                $today_day_name = $day_names[(int)date('N', strtotime($date)) - 1];
                $sched_start = null;
                
                if ($emp_data['position'] === 'Faculty') {
                    $stmt_sched = $pdo->prepare("SELECT ss.time_start, ss.time_end FROM subject_schedules ss JOIN subject_loads sl ON ss.subject_load_id = sl.id WHERE sl.faculty_id = ? AND ss.day_of_week = ? AND ss.company_id = ? ORDER BY ss.time_start LIMIT 1");
                    $stmt_sched->execute([$employee_id, $today_day_name, $company_id]);
                    $first_sched = $stmt_sched->fetch();
                    if ($first_sched) {
                        $sched_start = $first_sched['time_start'];
                    }
                }
                
                $late_ref_time = $sched_start ?: $work_start;
                $late_time = date('H:i:s', strtotime($late_ref_time . " + $grace_period minutes"));
                if ($time > $late_time) {
                    $status = 'Late';
                    $late_minutes = max(0, floor((strtotime($time) - strtotime($late_ref_time)) / 60));
                }
            }
            
            // For faculty check_out: on-time if after last subject load end
            if ($column === 'check_out' && $emp_data['position'] === 'Faculty') {
                $day_names = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                $today_day_name = $day_names[(int)date('N', strtotime($date)) - 1];
                $stmt_sched = $pdo->prepare("SELECT ss.time_end FROM subject_schedules ss JOIN subject_loads sl ON ss.subject_load_id = sl.id WHERE sl.faculty_id = ? AND ss.day_of_week = ? AND ss.company_id = ? ORDER BY ss.time_end DESC LIMIT 1");
                $stmt_sched->execute([$employee_id, $today_day_name, $company_id]);
                $last_sched = $stmt_sched->fetch();
                if ($last_sched && $time < $last_sched['time_end']) {
                    echo json_encode(array_merge(['success' => false, 'message' => "TOO EARLY FOR TIME OUT (Last subject ends " . date('h:i A', strtotime($last_sched['time_end'])) . ")", 'action' => $column, 'server_time' => $time], $common_data));
                    break;
                }
            }

            // GLOBAL VALIDATION: If the person has already clocked out, they are done for the day.
            if ($log && !empty($log['check_out'])) {
                echo json_encode(array_merge([
                    'success' => false,
                    'message' => "ALREADY CHECKED OUT FOR TODAY",
                    'action' => 'check_out',
                    'server_time' => $time
                ], $common_data));
                break;
            }

            // Validation: Prevent duplicate logs (redundant now but safe)
            if ($log && !empty($log[$column])) {
                $labels = [
                    'check_in' => 'TIME IN (CHECK IN)',
                    'lunch_out' => 'LUNCH OUT',
                    'lunch_in' => 'LUNCH IN',
                    'check_out' => 'TIME OUT (CHECK OUT)'
                ];
                $action_label = $labels[$column] ?? strtoupper($column);
                echo json_encode(array_merge([
                    'success' => false,
                    'message' => "ALREADY $action_label FOR TODAY",
                    'action' => $column,
                    'server_time' => $time
                ], $common_data));
                break;
            }

            // Logical sequence validation (redundant now but safe)
            if ($column === 'lunch_out' && (!$log || empty($log['check_in']))) {
                echo json_encode(array_merge(['success' => false, 'message' => 'MUST CHECK-IN FIRST', 'action' => $column], $common_data));
                break;
            }
            if ($column === 'lunch_in' && (!$log || empty($log['lunch_out']))) {
                echo json_encode(array_merge(['success' => false, 'message' => 'MUST LUNCH-OUT FIRST', 'action' => $column], $common_data));
                break;
            }
            if ($column === 'check_out' && (!$log || (empty($log['check_in']) && empty($log['lunch_in'])))) {
                echo json_encode(array_merge(['success' => false, 'message' => 'MUST LOG-IN FIRST', 'action' => $column], $common_data));
                break;
            }

            // Update status for Half-Day if they missed a major slot
            if ($column === 'check_out' && $log) {
                if (empty($log['check_in']) || empty($log['lunch_in'])) {
                    $status = 'Half-Day';
                }
            }

            // Save to Database
            if (!$log) {
                $stmt = $pdo->prepare("INSERT INTO attendance (company_id, employee_id, log_date, $column, status, late_minutes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$company_id, $employee_id, $date, $time, $status, $late_minutes]);
            } else {
                $stmt = $pdo->prepare("UPDATE attendance SET $column = ?, status = ?, late_minutes = ? WHERE id = ?");
                $stmt->execute([$time, $status, $late_minutes, $log['id']]);
            }

            $missed_morning = (!$log || empty($log['check_in'])) && $time > $lunch_out_start;

            $labels = [
                'check_in' => 'TIME IN (CHECK IN)',
                'lunch_out' => 'LUNCH OUT',
                'lunch_in' => 'LUNCH IN',
                'check_out' => 'TIME OUT (CHECK OUT)'
            ];
            $display_action = $labels[$column] ?? strtoupper($column);

            echo json_encode(array_merge([
                'success' => true,
                'action' => $display_action,
                'time' => date('h:i A', strtotime($time)),
                'status' => $status,
                'late_minutes' => $late_minutes,
                'missed_morning' => $missed_morning
            ], $common_data));
            break;

        case 'run_payroll':
            if (!isPayrollOrHigher())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $start_date = $data['start_date'] ?? '';
            $end_date = $data['end_date'] ?? '';
            $category = $data['category'] ?? 'all';

            // Centralized validation
            $errors = validateRequired($data, ['start_date', 'end_date']);
            $errors = array_merge($errors, validateDateRange($start_date, $end_date));
            rejectInvalidPayload($errors);

            $period = date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));

            $work_days_in_period = 0;
            $current_date = new DateTime($start_date);
            $end_date_dt = new DateTime($end_date);
            while ($current_date <= $end_date_dt) {
                if ($current_date->format('N') < 6)
                    $work_days_in_period++;
                $current_date->add(new DateInterval('P1D'));
            }

            $query = "SELECT * FROM employees WHERE company_id = ? AND status = 'Active'";
            $params = [$_SESSION['company_id']];
            if ($category !== 'all') {
                $query .= " AND position = ?";
                $params[] = $category;
            }

            $stmt_employees = $pdo->prepare($query);
            $stmt_employees->execute($params);
            $employees = $stmt_employees->fetchAll();
            $employee_ids = array_column($employees, 'id');

            // Pre-fetch all attendance counts for the period
            $attendance_counts = [];
            if (!empty($employee_ids)) {
                $placeholders = implode(',', array_fill(0, count($employee_ids), '?'));
                $stmt_all_att = $pdo->prepare("SELECT employee_id, COUNT(*) as count FROM attendance WHERE employee_id IN ($placeholders) AND log_date BETWEEN ? AND ? AND check_in IS NOT NULL GROUP BY employee_id");
                $stmt_all_att->execute(array_merge($employee_ids, [$start_date, $end_date]));
                while ($row = $stmt_all_att->fetch()) {
                    $attendance_counts[$row['employee_id']] = (int) $row['count'];
                }
            }

            $stmt_deductions = $pdo->prepare("SELECT * FROM deductions WHERE company_id = ? AND is_active = true");
            $stmt_deductions->execute([$_SESSION['company_id']]);
            $deductions_config = $stmt_deductions->fetchAll();

            $pdo->beginTransaction();
            try {
                foreach ($employees as $emp) {
                    // Prevent payroll processing before hire date
                    $hire_date = $emp['hire_date'] ?? $emp['created_at'] ?? $start_date;
                    $effective_start_date = max($start_date, $hire_date);
                    
                    // If employee wasn't hired yet during this period, skip
                    if ($effective_start_date > $end_date) {
                        continue;
                    }
                    
                    $days_present = $attendance_counts[$emp['id']] ?? 0;

                    $monthly_salary = (float) $emp['basic_salary'];
                    $earned_pay = ($work_days_in_period > 0) ? ($days_present / $work_days_in_period) * $monthly_salary : 0;

                    $total_deductions = 0;
                    foreach ($deductions_config as $deduction) {
                        $d_name = strtoupper($deduction['name']);

                        // Skip government deductions if employee doesn't have the respective ID
                        if (strpos($d_name, 'SSS') !== false && empty($emp['sss']))
                            continue;
                        if (strpos($d_name, 'PHILHEALTH') !== false && empty($emp['philhealth']))
                            continue;
                        if ((strpos($d_name, 'PAG-IBIG') !== false || strpos($d_name, 'PAGIBIG') !== false) && empty($emp['pagibig']))
                            continue;
                        if ((strpos($d_name, 'TIN') !== false || strpos($d_name, 'TAX') !== false) && empty($emp['tin']))
                            continue;

                        if ($deduction['type'] === 'percentage') {
                            $total_deductions += $earned_pay * ($deduction['value'] / 100);
                        } else {
                            $total_deductions += $deduction['value'];
                        }
                    }
                    
                    // Fetch employee-specific allowances
                    $stmt_allowances = $pdo->prepare("SELECT ea.*, ac.name, ac.type, ac.rate FROM employee_allowances ea JOIN allowance_categories ac ON ea.category_id = ac.id WHERE ea.employee_id = ? AND ea.company_id = ? AND (ea.effective_date IS NULL OR ea.effective_date <= ?)");
                    $stmt_allowances->execute([$emp['id'], $_SESSION['company_id'], $end_date]);
                    $employee_allowances = $stmt_allowances->fetchAll();
                    
                    // Calculate total allowances
                    $total_allowances = 0;
                    foreach ($employee_allowances as $allowance) {
                        $amount = $allowance['override_amount'] ?? null;
                        if ($amount === null) {
                            $amount = $allowance['type'] === 'Percentage' ? $earned_pay * ($allowance['rate'] / 100) : $allowance['rate'];
                        }
                        $total_allowances += (float)$amount;
                    }
                    
                    // Fetch employee-specific deductions
                    $stmt_emp_deductions = $pdo->prepare("SELECT ed.*, d.name, d.type, d.value FROM employee_deductions ed JOIN deductions d ON ed.deduction_id = d.id WHERE ed.employee_id = ? AND ed.company_id = ? AND (ed.effective_date IS NULL OR ed.effective_date <= ?)");
                    $stmt_emp_deductions->execute([$emp['id'], $_SESSION['company_id'], $end_date]);
                    $employee_deductions = $stmt_emp_deductions->fetchAll();
                    
                    // Calculate employee-specific deductions
                    $employee_specific_deductions = 0;
                    foreach ($employee_deductions as $deduction) {
                        $amount = $deduction['override_amount'] ?? null;
                        if ($amount === null) {
                            $amount = $deduction['type'] === 'percentage' ? $earned_pay * ($deduction['value'] / 100) : $deduction['value'];
                        }
                        $employee_specific_deductions += (float)$amount;
                    }
                    
                    // Add employee-specific deductions to total
                    $total_deductions += $employee_specific_deductions;

                    $stmt_loans = $pdo->prepare("SELECT id, amount FROM loans WHERE employee_id = ? AND company_id = ? AND status = 'Approved'");
                    $stmt_loans->execute([$emp['id'], $_SESSION['company_id']]);
                    $approved_loans = $stmt_loans->fetchAll();

                    foreach ($approved_loans as $loan) {
                        $total_deductions += $loan['amount'];
                        $pdo->prepare("UPDATE loans SET status = 'Paid' WHERE id = ?")->execute([$loan['id']]);
                    }

                    $net_pay = $earned_pay + $total_allowances - $total_deductions;

                    $stmt = $pdo->prepare("REPLACE INTO payroll (company_id, employee_id, period, basic_pay, deductions, net_pay, status, payroll_type) 
                                         VALUES (?, ?, ?, ?, ?, ?, 'Paid', 'General')");
                    $stmt->execute([$_SESSION['company_id'], $emp['id'], $period, $earned_pay, $total_deductions, $net_pay]);
                }
                
                $pdo->commit();
                
                // AUTOMATICALLY process Faculty and Utility payrolls for the same period
                $faculty_result = processSpecializedPayroll($pdo, $_SESSION['company_id'], 'Faculty', $start_date, $end_date);
                $utility_result = processSpecializedPayroll($pdo, $_SESSION['company_id'], 'Utility', $start_date, $end_date);
                
                $cat_msg = ($category === 'all') ? 'all employees' : "$category staff";
                $message = "Payroll for $cat_msg during $period run successfully.";
                
                // Add faculty/utility processing results to message
                $messages = [$message];
                if ($faculty_result['success']) {
                    $messages[] = $faculty_result['message'];
                }
                if ($utility_result['success']) {
                    $messages[] = $utility_result['message'];
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => implode(' ', $messages),
                    'faculty_processed' => $faculty_result['success'],
                    'utility_processed' => $utility_result['success']
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => "Error running payroll: " . $e->getMessage()]);
            }
            break;

        case 'get_payroll':
            if (!isset($_SESSION['company_id']))
                exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT p.*, e.full_name FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.company_id = ? ORDER BY p.created_at DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_payslip':
            if (!isset($_SESSION['user_id']))
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            
            // Support both id-based and employee_id+period-based lookups
            $payslip_id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
            $employee_id = filter_var($_GET['employee_id'] ?? '', FILTER_VALIDATE_INT);
            $period = $_GET['period'] ?? '';
            
            if ($payslip_id && $payslip_id > 0) {
                // Lookup by payslip ID
                $stmt = $pdo->prepare("SELECT p.*, e.full_name, e.employee_id as emp_code, e.position, e.department, c.name as company_name FROM payroll p JOIN employees e ON p.employee_id = e.id JOIN companies c ON p.company_id = c.id WHERE p.id = ? AND p.company_id = ?");
                $stmt->execute([$payslip_id, $_SESSION['company_id']]);
                $result = $stmt->fetch();
                if ($result) {
                    echo json_encode($result);
                } else {
                    echo json_encode(['error' => 'Payslip not found']);
                }
            } elseif ($employee_id && $employee_id > 0 && $period) {
                // Lookup by employee_id and period
                $stmt = $pdo->prepare("SELECT p.*, e.full_name, e.employee_id as emp_code, e.position, e.department, c.name as company_name FROM payroll p JOIN employees e ON p.employee_id = e.id JOIN companies c ON p.company_id = c.id WHERE p.employee_id = ? AND p.period = ? AND p.company_id = ?");
                $stmt->execute([$employee_id, $period, $_SESSION['company_id']]);
                $result = $stmt->fetch();
                if ($result) {
                    echo json_encode($result);
                } else {
                    echo json_encode(['error' => 'Payslip not found for this employee and period']);
                }
            } else {
                echo json_encode(['error' => 'Invalid parameters. Provide either id or employee_id+period']);
            }
            break;

        case 'get_leave_requests':
            if (!isset($_SESSION['company_id']))
                exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT lr.*, e.full_name FROM leave_requests lr JOIN employees e ON lr.employee_id = e.id WHERE lr.company_id = ? ORDER BY lr.id DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_loan_requests':
            if (!isset($_SESSION['company_id']))
                exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT l.*, e.full_name FROM loans l JOIN employees e ON l.employee_id = e.id WHERE l.company_id = ? ORDER BY l.id DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_resignation_requests':
            if (!isset($_SESSION['company_id']))
                exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT r.*, e.full_name FROM resignations r JOIN employees e ON r.employee_id = e.id WHERE r.company_id = ? ORDER BY r.id DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'update_leave_status':
        case 'update_loan_status':
        case 'update_resignation_status':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);

            $table = '';
            if ($action === 'update_leave_status')
                $table = 'leave_requests';
            if ($action === 'update_loan_status')
                $table = 'loans';
            if ($action === 'update_resignation_status')
                $table = 'resignations';

            if (!$table) {
                exit(json_encode(['success' => false, 'message' => 'Invalid action']));
            }

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE `$table` SET `status` = ? WHERE `id` = ? AND `company_id` = ?");
                $stmt->execute([$data['status'], $data['id'], $_SESSION['company_id']]);

                if ($action === 'update_resignation_status' && ($data['status'] === 'Approved' || $data['status'] === 'Completed')) {
                    $stmt = $pdo->prepare("SELECT `employee_id` FROM `$table` WHERE `id` = ?");
                    $stmt->execute([$data['id']]);
                    $employee_id = $stmt->fetchColumn();
                    if ($employee_id) {
                        $stmt = $pdo->prepare("UPDATE `employees` SET `status` = 'Resigned' WHERE `id` = ? AND `company_id` = ?");
                        $stmt->execute([$employee_id, $_SESSION['company_id']]);
                    }
                }

                // Send email notification for leave/loan status updates
                if ($action === 'update_leave_status' || $action === 'update_loan_status') {
                    $stmt_emp = $pdo->prepare("SELECT e.email, e.full_name FROM employees e JOIN `$table` t ON e.id = t.employee_id WHERE t.id = ?");
                    $stmt_emp->execute([$data['id']]);
                    $emp = $stmt_emp->fetch();
                    
                    if ($emp && $emp['email']) {
                        if ($action === 'update_leave_status') {
                            $stmt_type = $pdo->prepare("SELECT type FROM `$table` WHERE id = ?");
                            $stmt_type->execute([$data['id']]);
                            $leaveData = $stmt_type->fetch();
                            notifyLeaveRequest($emp['email'], $emp['full_name'], $leaveData['type'] ?? 'Leave', $data['status']);
                        } elseif ($action === 'update_loan_status') {
                            $stmt_amount = $pdo->prepare("SELECT amount FROM `$table` WHERE id = ?");
                            $stmt_amount->execute([$data['id']]);
                            $loanData = $stmt_amount->fetch();
                            notifyLoanStatus($emp['email'], $emp['full_name'], $loanData['amount'] ?? 0, $data['status']);
                        }
                    }
                }

                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $e->getMessage()]);
            }
            break;

        case 'decline_resignation':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? '';
            $reason = $data['reason'] ?? '';

            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'Resignation ID is required']);
                break;
            }

            $pdo->beginTransaction();
            try {
                // Update resignation status to Declined
                $stmt = $pdo->prepare("UPDATE resignations SET status = 'Declined', declined_by = ?, decline_reason = ?, declined_at = NOW() WHERE id = ? AND company_id = ?");
                $stmt->execute([$_SESSION['user_id'], $reason, $id, $_SESSION['company_id']]);

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Resignation declined successfully']);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Failed to decline resignation: ' . $e->getMessage()]);
            }
            break;

        case 'reinstate_employee':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? '';

            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
                break;
            }

            $pdo->beginTransaction();
            try {
                // Update employee status to Active
                $stmt = $pdo->prepare("UPDATE employees SET status = 'Active', reinstated_at = NOW(), reinstated_by = ? WHERE id = ? AND company_id = ? AND status = 'Resigned'");
                $stmt->execute([$_SESSION['user_id'], $id, $_SESSION['company_id']]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception('Employee not found or not in Resigned status');
                }
                
                // Reactivate user account
                $stmt_user = $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = (SELECT user_id FROM employees WHERE id = ?)");
                $stmt_user->execute([$id]);

                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Employee reinstated successfully']);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Failed to reinstate employee: ' . $e->getMessage()]);
            }
            break;

        case 'apply_leave':
            if (!isset($_SESSION['user_id']))
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $pdo->prepare("SELECT id, company_id FROM employees WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp = $stmt->fetch();
            if (!$emp)
                exit(json_encode(['success' => false, 'message' => 'Employee not found']));

            $stmt = $pdo->prepare("INSERT INTO leave_requests (company_id, employee_id, type, duration, reason, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([$emp['company_id'], $emp['id'], $data['type'], $data['duration'], $data['reason']]);
            echo json_encode(['success' => true]);
            break;

        case 'apply_loan':
            if (!isset($_SESSION['user_id']))
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);

            // Centralized validation
            $errors = validateRequired($data, ['amount', 'reason']);
            $errors = array_merge($errors, validateAmount($data['amount'], 'amount', 0.01));
            rejectInvalidPayload($errors);

            $stmt = $pdo->prepare("SELECT id, company_id FROM employees WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp = $stmt->fetch();
            if (!$emp)
                exit(json_encode(['success' => false, 'message' => 'Employee not found']));

            // Check if loans table exists, if not create it or fail gracefully
            try {
                $stmt = $pdo->prepare("INSERT INTO loans (company_id, employee_id, amount, reason, status) VALUES (?, ?, ?, ?, 'Pending')");
                $stmt->execute([$emp['company_id'], $emp['id'], $data['amount'], $data['reason']]);
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                // If table doesn't exist, we might need to create it. For now, return error.
                echo json_encode(['success' => false, 'message' => 'Cash advance feature not fully initialized: ' . $e->getMessage()]);
            }
            break;

        case 'get_deductions':
            if (!isset($_SESSION['company_id']))
                exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT * FROM deductions WHERE company_id = ?");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'save_deduction':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data['id']) && !empty($data['id'])) {
                $stmt = $pdo->prepare("UPDATE deductions SET name = ?, type = ?, value = ?, is_active = ?, is_government = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([$data['name'], $data['type'], $data['value'], $data['is_active'], $data['is_government'], $data['id'], $_SESSION['company_id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO deductions (company_id, name, type, value, is_active, is_government) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['company_id'], $data['name'], $data['type'], $data['value'], $data['is_active'], $data['is_government']]);
            }
            echo json_encode(['success' => true, 'message' => 'Deduction category saved successfully']);
            break;

        case 'delete_deduction':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
            if (!$id || $id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM deductions WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true, 'message' => 'Deduction category deleted']);
            break;

        case 'get_dashboard_stats':
            if (!isset($_SESSION['company_id']))
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $cid = $_SESSION['company_id'];
            $today = date('Y-m-d');
            $stats = [];

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE company_id = ?");
            $stmt->execute([$cid]);
            $stats['total_employees'] = (int) $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE company_id = ? AND log_date = ? AND check_in IS NOT NULL");
            $stmt->execute([$cid, $today]);
            $stats['present_today'] = (int) $stmt->fetchColumn();

            $stats['absent_today'] = $stats['total_employees'] - $stats['present_today'];
            
            // Attendance breakdown by status for today
            $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE company_id = ? AND log_date = ? GROUP BY status");
            $stmt->execute([$cid, $today]);
            $stats['attendance_breakdown'] = $stmt->fetchAll();
            
            // Absence by department
            $stmt = $pdo->prepare("SELECT e.department, COUNT(*) as count FROM employees e LEFT JOIN attendance a ON e.id = a.employee_id AND a.log_date = ? AND a.check_in IS NOT NULL WHERE e.company_id = ? AND e.status = 'Active' AND a.id IS NULL GROUP BY e.department");
            $stmt->execute([$today, $cid]);
            $stats['absence_by_department'] = $stmt->fetchAll();
            
            // Monthly attendance trends (last 6 months)
            $six_months_ago = date('Y-m-d', strtotime('-6 months'));
            $stmt = $pdo->prepare("SELECT DATE_FORMAT(log_date, '%Y-%m') as month, 
                                  COUNT(CASE WHEN status = 'On-Time' THEN 1 END) as on_time,
                                  COUNT(CASE WHEN status = 'Late' THEN 1 END) as late,
                                  COUNT(CASE WHEN status = 'Absent' THEN 1 END) as absent
                                  FROM attendance WHERE company_id = ? AND log_date >= ? 
                                  GROUP BY DATE_FORMAT(log_date, '%Y-%m') ORDER BY month");
            $stmt->execute([$cid, $six_months_ago]);
            $stats['monthly_trends'] = $stmt->fetchAll();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM leave_requests WHERE company_id = ? AND status = 'Pending'");
            $stmt->execute([$cid]);
            $stats['pending_leave'] = (int) $stmt->fetchColumn();

            echo json_encode($stats);
            break;

        case 'get_ess_data':
            if (!isset($_SESSION['user_id']))
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $stmt = $pdo->prepare("SELECT e.*, u.username, u.email as user_email FROM employees e JOIN users u ON e.user_id = u.id WHERE u.id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp = $stmt->fetch();
            if (!$emp)
                exit(json_encode(['success' => false, 'message' => 'Profile not found']));

            $eid = $emp['id'];

            $stmt_attendance = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? ORDER BY log_date DESC LIMIT 30");
            $stmt_attendance->execute([$eid]);
            $attendance = $stmt_attendance->fetchAll();
            
            // Count absent days
            $stmt_absent = $pdo->prepare("SELECT COUNT(*) as absent_count FROM attendance WHERE employee_id = ? AND status = 'Absent'");
            $stmt_absent->execute([$eid]);
            $absent_count = $stmt_absent->fetch()['absent_count'];

            $stmt_payroll = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ? ORDER BY created_at DESC");
            $stmt_payroll->execute([$eid]);
            $payroll = $stmt_payroll->fetchAll();

            $stmt_leave = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY id DESC");
            $stmt_leave->execute([$eid]);
            $leave = $stmt_leave->fetchAll();

            $stmt_loans = $pdo->prepare("SELECT * FROM loans WHERE employee_id = ? ORDER BY id DESC");
            $stmt_loans->execute([$eid]);
            $loans = $stmt_loans->fetchAll();

            $stmt_resignations = $pdo->prepare("SELECT * FROM resignations WHERE employee_id = ? ORDER BY id DESC");
            $stmt_resignations->execute([$eid]);
            $resignations = $stmt_resignations->fetchAll();

            echo json_encode([
                'profile' => $emp,
                'attendance' => $attendance,
                'absent_days' => (int)$absent_count,
                'payroll' => $payroll,
                'leave' => $leave,
                'loans' => $loans,
                'resignations' => $resignations
            ]);
            break;

        case 'get_subject_loads':
            if (!isset($_SESSION['company_id']))
                exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT sl.*, e.full_name as faculty_name FROM subject_loads sl JOIN employees e ON sl.faculty_id = e.id WHERE sl.company_id = ?");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_subjects':
            if (!isset($_SESSION['company_id']))
                exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT * FROM subjects WHERE company_id = ? ORDER BY code ASC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'save_subject':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
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
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
            if (!$id || $id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'save_subject_load':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO subject_loads (company_id, faculty_id, code, description, units, hours) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['company_id'], $data['faculty_id'], $data['code'], $data['description'], $data['units'], $data['hours']]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_subject_load':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
            if (!$id || $id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM subject_loads WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'get_subject_schedules':
            if (!isset($_SESSION['company_id']))
                exit(json_encode([]));
            $subject_load_id = $_GET['subject_load_id'] ?? null;
            $faculty_id = $_GET['faculty_id'] ?? null;
            $day = $_GET['day'] ?? null;
            $sql = "SELECT ss.*, sl.faculty_id, sl.description as subject_description, sl.code as subject_code, e.full_name as faculty_name 
                    FROM subject_schedules ss 
                    JOIN subject_loads sl ON ss.subject_load_id = sl.id 
                    JOIN employees e ON sl.faculty_id = e.id 
                    WHERE ss.company_id = ?";
            $params = [$_SESSION['company_id']];
            if ($subject_load_id) { $sql .= " AND ss.subject_load_id = ?"; $params[] = $subject_load_id; }
            if ($faculty_id) { $sql .= " AND sl.faculty_id = ?"; $params[] = $faculty_id; }
            if ($day) { $sql .= " AND ss.day_of_week = ?"; $params[] = $day; }
            $sql .= " ORDER BY ss.day_of_week, ss.time_start";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll());
            break;

        case 'save_subject_schedule':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['subject_load_id']) || empty($data['day_of_week']) || empty($data['time_start']) || empty($data['time_end'])) {
                echo json_encode(['success' => false, 'message' => 'Subject load, day, start time, and end time are required']);
                break;
            }
            if (isset($data['id']) && !empty($data['id'])) {
                $stmt = $pdo->prepare("UPDATE subject_schedules SET subject_load_id = ?, day_of_week = ?, time_start = ?, time_end = ?, room = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([$data['subject_load_id'], $data['day_of_week'], $data['time_start'], $data['time_end'], $data['room'] ?? null, $data['id'], $_SESSION['company_id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO subject_schedules (company_id, subject_load_id, day_of_week, time_start, time_end, room) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['company_id'], $data['subject_load_id'], $data['day_of_week'], $data['time_start'], $data['time_end'], $data['room'] ?? null]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_subject_schedule':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
            if (!$id || $id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM subject_schedules WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        // --- Faculty Self-Service: Subject Loads ---
        case 'get_my_subject_loads':
            if (!isset($_SESSION['user_id']))
                exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT e.id FROM employees WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp_id = $stmt->fetchColumn();
            if (!$emp_id) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT * FROM subject_loads WHERE faculty_id = ? ORDER BY code");
            $stmt->execute([$emp_id]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'save_my_subject_load':
            if (!isset($_SESSION['user_id']))
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp_id = $stmt->fetchColumn();
            if (!$emp_id) exit(json_encode(['success' => false, 'message' => 'Employee not found']));
            $company_id = $_SESSION['company_id'] ?? null;
            if (!$company_id) exit(json_encode(['success' => false, 'message' => 'No company']));
            if (isset($data['id']) && !empty($data['id'])) {
                $stmt = $pdo->prepare("UPDATE subject_loads SET code = ?, description = ?, units = ?, hours = ? WHERE id = ? AND faculty_id = ?");
                $stmt->execute([$data['code'], $data['description'], $data['units'], $data['hours'], $data['id'], $emp_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO subject_loads (company_id, faculty_id, code, description, units, hours) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$company_id, $emp_id, $data['code'], $data['description'], $data['units'], $data['hours']]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_my_subject_load':
            if (!isset($_SESSION['user_id']))
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
            if (!$id || $id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid ID']); break; }
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp_id = $stmt->fetchColumn();
            if (!$emp_id) exit(json_encode(['success' => false, 'message' => 'Employee not found']));
            $stmt = $pdo->prepare("DELETE FROM subject_loads WHERE id = ? AND faculty_id = ?");
            $stmt->execute([$id, $emp_id]);
            echo json_encode(['success' => true]);
            break;

        // --- Faculty Self-Service: Subject Schedules ---
        case 'get_my_subject_schedules':
            if (!isset($_SESSION['user_id']))
                exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT e.id FROM employees WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp_id = $stmt->fetchColumn();
            if (!$emp_id) exit(json_encode([]));
            $subject_load_id = $_GET['subject_load_id'] ?? null;
            if (!$subject_load_id) exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT ss.*, sl.code as subject_code, sl.description as subject_description FROM subject_schedules ss JOIN subject_loads sl ON ss.subject_load_id = sl.id WHERE ss.subject_load_id = ? AND sl.faculty_id = ? ORDER BY ss.day_of_week, ss.time_start");
            $stmt->execute([$subject_load_id, $emp_id]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'save_my_subject_schedule':
            if (!isset($_SESSION['user_id']))
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("SELECT e.id, e.company_id FROM employees e WHERE e.user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp = $stmt->fetch();
            if (!$emp) exit(json_encode(['success' => false, 'message' => 'Employee not found']));
            $emp_id = $emp['id'];
            $company_id = $emp['company_id'];
            // Verify the subject_load belongs to this faculty
            $stmt = $pdo->prepare("SELECT id FROM subject_loads WHERE id = ? AND faculty_id = ?");
            $stmt->execute([$data['subject_load_id'], $emp_id]);
            if (!$stmt->fetch()) exit(json_encode(['success' => false, 'message' => 'Subject load not found']));
            if (empty($data['day_of_week']) || empty($data['time_start']) || empty($data['time_end'])) {
                echo json_encode(['success' => false, 'message' => 'Day, start time, and end time are required']);
                break;
            }
            // Overlap check
            $stmt = $pdo->prepare("SELECT ss.id FROM subject_schedules ss JOIN subject_loads sl ON ss.subject_load_id = sl.id WHERE sl.faculty_id = ? AND ss.day_of_week = ? AND ss.time_start < ? AND ss.time_end > ? AND (? IS NULL OR ss.id != ?)");
            $existing_id = $data['id'] ?? null;
            $stmt->execute([$emp_id, $data['day_of_week'], $data['time_end'], $data['time_start'], $existing_id, $existing_id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Schedule overlaps with an existing entry on this day.']);
                break;
            }
            if (isset($data['id']) && !empty($data['id'])) {
                $stmt = $pdo->prepare("UPDATE subject_schedules SET day_of_week = ?, time_start = ?, time_end = ?, room = ? WHERE id = ? AND subject_load_id IN (SELECT id FROM subject_loads WHERE id = ? AND faculty_id = ?)");
                $stmt->execute([$data['day_of_week'], $data['time_start'], $data['time_end'], $data['room'] ?? null, $data['id'], $data['subject_load_id'], $emp_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO subject_schedules (company_id, subject_load_id, day_of_week, time_start, time_end, room) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$company_id, $data['subject_load_id'], $data['day_of_week'], $data['time_start'], $data['time_end'], $data['room'] ?? null]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_my_subject_schedule':
            if (!isset($_SESSION['user_id']))
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
            if (!$id || $id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid ID']); break; }
            $stmt = $pdo->prepare("SELECT e.id FROM employees e WHERE e.user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp_id = $stmt->fetchColumn();
            if (!$emp_id) exit(json_encode(['success' => false, 'message' => 'Employee not found']));
            $stmt = $pdo->prepare("DELETE ss FROM subject_schedules ss JOIN subject_loads sl ON ss.subject_load_id = sl.id WHERE ss.id = ? AND sl.faculty_id = ?");
            $stmt->execute([$id, $emp_id]);
            echo json_encode(['success' => true]);
            break;

        case 'update_role':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
            if (!$id || $id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                break;
            }
            $role = $_GET['role'] ?? 'Employee';
            // Validate role is a valid enum value
            $valid_roles = ['HR', 'Admin', 'Payroll', 'Payroll Officer', 'Employee'];
            if (!in_array($role, $valid_roles)) {
                echo json_encode(['success' => false, 'message' => 'Invalid role']);
                break;
            }
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("SELECT user_id FROM employees WHERE id = ? AND company_id = ?");
                $stmt->execute([$id, $_SESSION['company_id']]);
                $user_id = $stmt->fetchColumn();
                if (!$user_id)
                    throw new Exception('User not found');
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

            // Validate password strength
            if (strlen($newPass) < 8 || !preg_match('/[A-Z]/', $newPass) || !preg_match('/[a-z]/', $newPass) || !preg_match('/\d/', $newPass) || !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $newPass)) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, number, and special character.']);
                break;
            }

            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user && password_verify($oldPass, $user['password'])) {
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, password_last_changed = NOW() WHERE id = ?");
                $stmt->execute([$hashed, $_SESSION['user_id']]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Incorrect current password']);
            }
            break;

        case 'update_profile':
            $data = json_decode(file_get_contents('php://input'), true);
            $username = trim($data['username'] ?? '');
            $email = trim($data['email'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $work_position = trim($data['work_position'] ?? '') ?: null;
            $work_status = trim($data['work_status'] ?? '') ?: null;
            $hire_date = trim($data['hire_date'] ?? '') ?: null;

            if (empty($username) || empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Username and email are required']);
                break;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                break;
            }

            // Check if username is unique
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username already taken']);
                break;
            }

            // Check if email is unique
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email already in use']);
                break;
            }

            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ? WHERE id = ?");
            $success = $stmt->execute([$username, $email, $phone, $_SESSION['user_id']]);

            // Also update employee fields
            $empFields = [];
            $empParams = [];
            if ($work_position !== null) { $empFields[] = "work_position = ?"; $empParams[] = $work_position; }
            if ($work_status !== null) { $empFields[] = "work_status = ?"; $empParams[] = $work_status; }
            if ($hire_date !== null) { $empFields[] = "hire_date = ?"; $empParams[] = $hire_date; }
            if (!empty($empFields)) {
                $empParams[] = $_SESSION['user_id'];
                $empStmt = $pdo->prepare("UPDATE employees SET " . implode(', ', $empFields) . " WHERE user_id = ?");
                $empStmt->execute($empParams);
            }

            if ($success) {
                $_SESSION['full_name'] = $username;
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
            }
            break;

        case 'update_employee_profile':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate and sanitize input
            $full_name = trim($data['full_name'] ?? '');
            $email = trim($data['email'] ?? '');
            $dob = trim($data['dob'] ?? '') ?: null;
            $gender = trim($data['gender'] ?? '') ?: null;
            $contact_no = trim($data['contact_no'] ?? '') ?: null;
            $position = trim($data['position'] ?? '') ?: null;
            $work_position = trim($data['work_position'] ?? '') ?: null;
            $department = trim($data['department'] ?? '') ?: null;
            $hire_date = trim($data['hire_date'] ?? '') ?: null;
            $faculty_level = trim($data['faculty_level'] ?? '') ?: null;
            $status = trim($data['status'] ?? 'active');
            $work_status = trim($data['work_status'] ?? '') ?: null;
            
            // Keep backward compatibility with old fields
            $sss = trim($data['sss'] ?? '') ?: null;
            $philhealth = trim($data['philhealth'] ?? '') ?: null;
            $tin = trim($data['tin'] ?? '') ?: null;
            $pagibig = trim($data['pagibig'] ?? '') ?: null;

            if (!$full_name) {
                echo json_encode(['success' => false, 'message' => 'Full name is required']);
                break;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                break;
            }

            // Get employee id from user_id
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp = $stmt->fetch();
            if (!$emp) {
                echo json_encode(['success' => false, 'message' => 'Employee not found']);
                break;
            }

            $stmt = $pdo->prepare("UPDATE employees SET full_name = ?, email = ?, dob = ?, gender = ?, contact_no = ?, position = ?, work_position = ?, department = ?, hire_date = ?, faculty_level = ?, status = ?, work_status = ?, sss = ?, philhealth = ?, tin = ?, pagibig = ? WHERE user_id = ?");
            $success = $stmt->execute([$full_name, $email, $dob, $gender, $contact_no, $position, $work_position, $department, $hire_date, $faculty_level, $status, $work_status, $sss, $philhealth, $tin, $pagibig, $_SESSION['user_id']]);

            if ($success) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update employee profile']);
            }
            break;

        case 'update_leave_balance':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $employee_id = $_GET['employee_id'] ?? '';
            $balance = $_GET['balance'] ?? 0;
            $stmt = $pdo->prepare("UPDATE employees SET leave_balance = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$balance, $employee_id, $_SESSION['company_id']]);
            echo json_encode(['success' => true]);
            break;

        case 'bulk_update_leave_balance':
            if (!isAdminOrHR())
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $balance = isset($data['balance']) ? (float) $data['balance'] : null;
            if ($balance === null || $balance < 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid leave balance']);
                break;
            }

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE employees SET leave_balance = ? WHERE company_id = ? AND status = 'Active'");
                $stmt->execute([$balance, $_SESSION['company_id']]);
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Leave balance applied to all active employees']);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Failed to apply leave balance: ' . $e->getMessage()]);
            }
            break;

        case 'get_payroll_batches':
            if (!isset($_SESSION['company_id']))
                exit(json_encode([]));
            $stmt = $pdo->prepare("SELECT period, SUM(net_pay) as total_disbursed, COUNT(*) as staff_count, MAX(created_at) as processing_date FROM payroll WHERE company_id = ? GROUP BY period ORDER BY processing_date DESC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'get_payroll_by_period':
            if (!isset($_SESSION['company_id']))
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $period = $_GET['period'] ?? '';
            if (!$period) {
                echo json_encode(['success' => false, 'message' => 'Period is required']);
                break;
            }
            $stmt = $pdo->prepare("SELECT p.*, e.full_name, e.employee_id as emp_code, e.position FROM payroll p JOIN employees e ON p.employee_id = e.id WHERE p.company_id = ? AND p.period = ? ORDER BY e.full_name ASC");
            $stmt->execute([$_SESSION['company_id'], $period]);
            $records = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $records, 'period' => $period]);
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

        case 'get_payroll_schedule':
            if (!isPayrollOrHigher()) {
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            }
            
            // Create table if not exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS payroll_schedule (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_id INT NOT NULL,
                period VARCHAR(50),
                cutoff_start DATE,
                cutoff_end DATE,
                run_date DATE,
                payroll_type ENUM('general','faculty','utility','all') DEFAULT 'all',
                status ENUM('pending','ready','overdue','completed') DEFAULT 'pending',
                employee_count INT DEFAULT 0,
                est_amount DECIMAL(10,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
            )");
            
            $stmt = $pdo->prepare("SELECT * FROM payroll_schedule WHERE company_id = ? ORDER BY run_date ASC");
            $stmt->execute([$_SESSION['company_id']]);
            echo json_encode($stmt->fetchAll());
            break;

        case 'save_payroll_schedule':
            if (!isPayrollOrHigher()) {
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $errors = validateRequired($data, ['start_date', 'end_date', 'run_date']);
            $errors = array_merge($errors, validateDateRange($data['start_date'], $data['end_date']));
            rejectInvalidPayload($errors);
            
            $period = date('m/Y', strtotime($data['start_date']));
            
            // Create table if not exists (idempotent)
            $pdo->exec("CREATE TABLE IF NOT EXISTS payroll_schedule (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_id INT NOT NULL,
                period VARCHAR(50),
                cutoff_start DATE,
                cutoff_end DATE,
                run_date DATE,
                payroll_type ENUM('general','faculty','utility','all') DEFAULT 'all',
                status ENUM('pending','ready','overdue','completed') DEFAULT 'pending',
                employee_count INT DEFAULT 0,
                est_amount DECIMAL(10,2) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
            )");
            
            $stmt = $pdo->prepare("INSERT INTO payroll_schedule (company_id, period, cutoff_start, cutoff_end, run_date, payroll_type) VALUES (?, ?, ?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE cutoff_start=VALUES(cutoff_start), cutoff_end=VALUES(cutoff_end), run_date=VALUES(run_date)");
            $stmt->execute([$_SESSION['company_id'], $period, $data['start_date'], $data['end_date'], $data['run_date'], $data['type'] ?? 'all']);
            
            echo json_encode(['success' => true, 'message' => 'Schedule saved']);
            break;

        case 'validate_payroll_readiness':
            if (!isPayrollOrHigher()) {
                exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            }
            
            $schedule_id = filter_var($_GET['schedule_id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$schedule_id) {
                echo json_encode(['success' => false, 'message' => 'Schedule ID required']);
                break;
            }
            
            $stmt = $pdo->prepare("SELECT * FROM payroll_schedule WHERE id = ? AND company_id = ?");
            $stmt->execute([$schedule_id, $_SESSION['company_id']]);
            $schedule = $stmt->fetch();
            if (!$schedule) {
                echo json_encode(['success' => false, 'message' => 'Schedule not found']);
                break;
            }
            
            $today = date('Y-m-d');
            $coverage = [];
            
            // Check attendance completeness
            $end_date = $schedule['cutoff_end'];
            $stmt_coverage = $pdo->prepare("SELECT 
                e.id, e.full_name,
                COUNT(a.id) as logged_days,
                DATEDIFF(?, ?) + 1 as total_days
            FROM employees e 
            LEFT JOIN attendance a ON a.employee_id = e.id AND a.log_date BETWEEN ? AND ? AND a.check_in IS NOT NULL
            WHERE e.company_id = ? AND e.status = 'Active'
            GROUP BY e.id");
            $stmt_coverage->execute([$end_date, $schedule['cutoff_start'], $schedule['cutoff_start'], $end_date, $_SESSION['company_id']]);
            
            $ready_count = 0;
            $total_employees = 0;
            $est_amount = 0;
            
            while ($emp = $stmt_coverage->fetch()) {
                $total_employees++;
                $coverage_pct = ($emp['logged_days'] / $emp['total_days']) * 100;
                if ($coverage_pct >= 95) $ready_count++; // 95% threshold
                
                // Rough est amount based on basic_salary
                $est_amount += $emp['basic_salary'] ?? 0;
            }
            
            $status = ($ready_count / max(1, $total_employees)) >= 0.9 ? 'ready' : 'pending';
            if ($today > $schedule['run_date']) $status = 'overdue';
            
            echo json_encode([
                'success' => true,
                'status' => $status,
                'attendance_coverage' => round(($ready_count / max(1, $total_employees)) * 100, 1) . '%',
                'ready_employees' => $ready_count,
                'total_employees' => $total_employees,
                'est_disbursement' => number_format($est_amount, 2),
                'cutoff_complete' => $today <= $schedule['cutoff_end']
            ]);
            break;

        case 'get_server_time':
            $cid = $_GET['company_id'] ?? $_SESSION['company_id'] ?? null;
            if ($cid) {
                $stmt = $pdo->prepare("SELECT timezone FROM companies WHERE id = ?");
                $stmt->execute([$cid]);
                $tz = $stmt->fetchColumn();
                if ($tz)
                    date_default_timezone_set($tz);
            }
            $server_ms = (int) round(microtime(true) * 1000);
            echo json_encode([
                'server_ms' => $server_ms,
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'display_time' => date('h:i A'),
                'timezone' => date_default_timezone_get()
            ]);
            break;

        case 'calculate_taxes':
            require_once 'payroll_tax.php';
            $company_id = $_SESSION['company_id'] ?? $_GET['company_id'] ?? 1;
            $gross_pay = $_POST['gross_pay'] ?? 0;
            $employee_data = $_POST['employee_data'] ?? [];
            
            $engine = new PayrollTaxEngine($pdo, $company_id);
            $taxes = $engine->calculateGovContributions($gross_pay, $employee_data);
            echo json_encode(['success' => true, 'taxes' => $taxes]);
            break;
            
        case 'apply_payroll_taxes':
            if (!isPayrollOrHigher()) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            
            require_once 'payroll_tax.php';
            $payroll_id = $_POST['payroll_id'] ?? 0;
            if (!$payroll_id) {
                echo json_encode(['success' => false, 'message' => 'Payroll ID required']);
                break;
            }
            
            $company_id = $_SESSION['company_id'];
            $engine = new PayrollTaxEngine($pdo, $company_id);
            $success = $engine->applyTaxesToPayroll($payroll_id);
            
            echo json_encode(['success' => $success]);
            break;

        case 'bulk_payroll_adjustment':
            if (!isPayrollOrHigher()) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $payroll_ids = $data['payroll_ids'] ?? [];
            $multiplier = (float) ($data['multiplier'] ?? 1.0);
            
            if (empty($payroll_ids)) {
                echo json_encode(['success' => false, 'message' => 'No payroll IDs provided']);
                break;
            }
            
            $placeholders = implode(',', array_fill(0, count($payroll_ids), '?'));
            $stmt = $pdo->prepare("UPDATE payroll SET 
                basic_pay = basic_pay * ?,
                net_pay = net_pay * ?
                WHERE id IN ($placeholders) AND company_id = ?
            ");
            $params = array_merge([$multiplier, $multiplier], $payroll_ids, [$_SESSION['company_id']]);
            $stmt->execute($params);
            
            logAudit($pdo, $_SESSION['company_id'], $_SESSION['user_id'], 'BULK_PAYROLL_ADJUSTMENT', 'payroll', json_encode($payroll_ids), "Multiplier: $multiplier");
            
            echo json_encode([
                'success' => true,
                'message' => "Adjusted " . count($payroll_ids) . " payroll records by x$multiplier factor"
            ]);
            break;

        case 'bulk_payroll_update':
            if (!isPayrollOrHigher()) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $payroll_ids = $data['payroll_ids'] ?? [];
            $field = $data['field'] ?? '';
            $value = $data['value'] ?? null;
            
            $allowed_fields = ['basic_pay', 'net_pay', 'allowances'];
            if (empty($payroll_ids) || !in_array($field, $allowed_fields) || $value === null) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                break;
            }
            
            $placeholders = implode(',', array_fill(0, count($payroll_ids), '?'));
            $stmt = $pdo->prepare("UPDATE payroll SET `$field` = ? WHERE id IN ($placeholders) AND company_id = ?");
            $params = array_merge([$value], $payroll_ids, [$_SESSION['company_id']]);
            $stmt->execute($params);
            
            logAudit($pdo, $_SESSION['company_id'], $_SESSION['user_id'], 'BULK_PAYROLL_UPDATE', 'payroll', json_encode($payroll_ids), "$field = $value");
            
            echo json_encode([
                'success' => true,
                'message' => "Updated $field for " . count($payroll_ids) . " payroll records"
            ]);
            break;

        case 'log_spoof_attempt':
            $data = json_decode(file_get_contents('php://input'), true);
            $company_id = $data['company_id'] ?? $_SESSION['company_id'] ?? 1;
            $reason = $data['reason'] ?? 'Photo/spoof detected – access denied';
            
            // We use the audit log system to record spoof attempts
            // A dedicated spoof log table could be used, but audit_logs works for security events
            $stmt = $pdo->prepare("INSERT INTO audit_logs (company_id, user_id, action, target_table, record_id, changes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $company_id,
                $_SESSION['user_id'] ?? null,
                'SPOOF_ATTEMPT_DETECTED',
                'system_security',
                null,
                $reason
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Spoof attempt logged']);
            break;

        case 'assign_role':
            // SD/Admin can assign HR and Payroll Officer roles
            if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'SD', 'School Director'])) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $employee_id = $data['employee_id'] ?? '';
            $username = trim($data['username'] ?? '');
            $password = $data['password'] ?? '';
            $email = trim($data['email'] ?? '');
            $role = $data['role'] ?? '';
            
            // Validate required fields
            if (empty($employee_id) || empty($username) || empty($password) || empty($email) || empty($role)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                break;
            }
            
            // Validate role
            if (!in_array($role, ['HR', 'Admin', 'Payroll Officer'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid role']);
                break;
            }
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                // Add random number to username
                $username = $username . rand(10, 99);
            }
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                break;
            }
            
            try {
                $pdo->beginTransaction();
                
                // Create user account
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $company_id = $_SESSION['company_id'] ?? 1;
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (company_id, username, password, role, email, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([$company_id, $username, $hashed_password, $role, $email]);
                $user_id = $pdo->lastInsertId();
                
                // Link employee to user
                $stmt = $pdo->prepare("UPDATE employees SET user_id = ? WHERE id = ?");
                $stmt->execute([$user_id, $employee_id]);
                
                $pdo->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Role assigned successfully',
                    'user_id' => $user_id,
                    'username' => $username
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Failed to assign role: ' . $e->getMessage()]);
            }
            break;
            
        case 'deactivate_user':
            // SD/Admin can deactivate users
            if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'SD', 'School Director'])) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $user_id = $data['user_id'] ?? '';
            
            if (empty($user_id)) {
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                break;
            }
            
            try {
                // Deactivate user
                $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
                $stmt->execute([$user_id]);
                
                // Unlink from employee
                $stmt = $pdo->prepare("UPDATE employees SET user_id = NULL WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                echo json_encode(['success' => true, 'message' => 'User deactivated successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to deactivate user: ' . $e->getMessage()]);
            }
            break;
            
        case 'get_sd_analytics':
            // Get analytics data for SD dashboard
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            
            try {
                $company_id = $_SESSION['company_id'] ?? 1;
                
                // Total employees
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM employees WHERE status = 'Active' AND company_id = ?");
                $stmt->execute([$company_id]);
                $total_employees = $stmt->fetch()['total'] ?? 0;
                
                // Present today
                $stmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT a.employee_id) as total 
                    FROM attendance a
                    JOIN employees e ON a.employee_id = e.id
                    WHERE DATE(a.scan_time) = CURDATE() 
                    AND a.type = 'IN'
                    AND e.company_id = ?
                ");
                $stmt->execute([$company_id]);
                $present_today = $stmt->fetch()['total'] ?? 0;
                
                // Absent today
                $absent_today = max(0, $total_employees - $present_today);
                
                // Total HR users
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role IN ('HR', 'Admin') AND is_active = 1 AND company_id = ?");
                $stmt->execute([$company_id]);
                $total_hr = $stmt->fetch()['total'] ?? 0;
                
                // Total Payroll Officers
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'Payroll Officer' AND is_active = 1 AND company_id = ?");
                $stmt->execute([$company_id]);
                $total_payroll = $stmt->fetch()['total'] ?? 0;
                
                // Monthly payroll
                $stmt = $pdo->prepare("
                    SELECT SUM(net_pay) as total 
                    FROM payroll_records 
                    WHERE MONTH(pay_date) = MONTH(CURDATE()) 
                    AND YEAR(pay_date) = YEAR(CURDATE())
                ");
                $stmt->execute();
                $monthly_payroll = $stmt->fetch()['total'] ?? 0;
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'total_employees' => $total_employees,
                        'present_today' => $present_today,
                        'absent_today' => $absent_today,
                        'total_hr' => $total_hr,
                        'total_payroll' => $total_payroll,
                        'monthly_payroll' => $monthly_payroll
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to get analytics: ' . $e->getMessage()]);
            }
            break;

        case 'save_subject_load_ess':
            if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp_id = $stmt->fetchColumn();
            if (!$emp_id) exit(json_encode(['success' => false, 'message' => 'Employee not found']));
            
            $stmt = $pdo->prepare("INSERT INTO subject_loads (company_id, faculty_id, code, description, units, hours) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['company_id'] ?? 1, $emp_id, $data['code'], $data['description'], $data['units'], 0]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_subject_load_ess':
            if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
            if (!$id) exit(json_encode(['success' => false, 'message' => 'Invalid ID']));
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp_id = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("DELETE FROM subject_loads WHERE id = ? AND faculty_id = ?");
            $stmt->execute([$id, $emp_id]);
            echo json_encode(['success' => true]);
            break;

        case 'save_subject_schedule_ess':
            if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp_id = $stmt->fetchColumn();
            
            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM subject_loads WHERE id = ? AND faculty_id = ?");
            $stmt->execute([$data['subject_load_id'], $emp_id]);
            if (!$stmt->fetch()) exit(json_encode(['success' => false, 'message' => 'Subject load not found']));
            
            $stmt = $pdo->prepare("INSERT INTO subject_schedules (company_id, subject_load_id, day_of_week, time_start, time_end, room) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['company_id'] ?? 1, $data['subject_load_id'], $data['day_of_week'], $data['time_start'], $data['time_end'], $data['room']]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_subject_schedule_ess':
            if (!isset($_SESSION['user_id'])) exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
            $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $emp_id = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("DELETE ss FROM subject_schedules ss JOIN subject_loads sl ON ss.subject_load_id = sl.id WHERE ss.id = ? AND sl.faculty_id = ?");
            $stmt->execute([$id, $emp_id]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'API Error: ' . $e->getMessage()]);
}
?>