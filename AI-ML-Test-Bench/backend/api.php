<?php
// api.php - Router only
header('Content-Type: application/json');
date_default_timezone_set('UTC');

require_once __DIR__ . '/helpers.php';

try {
    require_once __DIR__ . '/db.php';
} catch (Exception $e) {
    error_log("DB Connection Failed: " . $e->getMessage());
    sendError(500, 'Database unavailable');
}

// Load all controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/EmployeeController.php';
require_once __DIR__ . '/controllers/AttendanceController.php';
require_once __DIR__ . '/controllers/PayrollController.php';
require_once __DIR__ . '/controllers/AllowanceController.php';
require_once __DIR__ . '/controllers/LeaveController.php';
require_once __DIR__ . '/controllers/LoanController.php';
require_once __DIR__ . '/controllers/SubjectController.php';
require_once __DIR__ . '/controllers/SettingsController.php';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        // ── Auth ──────────────────────────────────────────────────────────
        case 'login':            handle_login($pdo);            break;
        case 'signup':           handle_signup($pdo);           break;
        case 'logout':           handle_logout();               break;
        case 'change_password':  handle_change_password($pdo);  break;
        case 'reset_password':   handle_reset_password($pdo);   break;

        // ── Employees ─────────────────────────────────────────────────────
        case 'get_employees':               handle_get_employees($pdo);               break;
        case 'save_employee':               handle_save_employee($pdo);               break;
        case 'delete_employee':             handle_delete_employee($pdo);             break;
        case 'get_enrolled_faces':          handle_get_enrolled_faces($pdo);          break;
        case 'save_face_descriptor':        handle_save_face_descriptor($pdo);        break;
        case 'get_employee_biometric_status': handle_get_employee_biometric_status($pdo); break;
        case 'update_role':                 handle_update_role($pdo);                 break;
        case 'update_leave_balance':        handle_update_leave_balance($pdo);        break;
        case 'bulk_update_leave_balance':   handle_bulk_update_leave_balance($pdo);   break;

        // ── Attendance ────────────────────────────────────────────────────
        case 'get_attendance':  handle_get_attendance($pdo);  break;
        case 'kiosk_scan':      handle_kiosk_scan($pdo);      break;

        // ── Payroll ───────────────────────────────────────────────────────
        case 'run_payroll':              handle_run_payroll($pdo);              break;
        case 'run_specialized_payroll':  handle_run_specialized_payroll($pdo);  break;
        case 'get_payroll':              handle_get_payroll($pdo);              break;
        case 'get_payslip':              handle_get_payslip($pdo);              break;
        case 'get_payroll_batches':      handle_get_payroll_batches($pdo);      break;
        case 'get_faculty_payroll':      handle_get_faculty_payroll($pdo);      break;
        case 'get_utility_payroll':      handle_get_utility_payroll($pdo);      break;

        // ── Allowances & Deductions ───────────────────────────────────────
        case 'get_allowance_categories':    handle_get_allowance_categories($pdo);    break;
        case 'add_allowance_category':      handle_add_allowance_category($pdo);      break;
        case 'get_employee_allowances':     handle_get_employee_allowances($pdo);     break;
        case 'assign_employee_allowance':   handle_assign_employee_allowance($pdo);   break;
        case 'delete_allowance_category':   handle_delete_allowance_category($pdo);   break;
        case 'delete_employee_allowance':   handle_delete_employee_allowance($pdo);   break;
        case 'bulk_assign_allowance':       handle_bulk_assign_allowance($pdo);       break;
        case 'get_deduction_breakdown':     handle_get_deduction_breakdown($pdo);     break;
        case 'assign_employee_deduction':   handle_assign_employee_deduction($pdo);   break;
        case 'delete_employee_deduction':   handle_delete_employee_deduction($pdo);   break;
        case 'bulk_assign_deduction':       handle_bulk_assign_deduction($pdo);       break;
        case 'get_deduction_categories':    handle_get_deduction_categories($pdo);    break;
        case 'get_employee_deductions':     handle_get_employee_deductions($pdo);     break;
        case 'get_deductions':              handle_get_deductions($pdo);              break;
        case 'save_deduction':              handle_save_deduction($pdo);              break;
        case 'delete_deduction':            handle_delete_deduction($pdo);            break;
        case 'revoke_payroll_access':       handle_revoke_payroll_access($pdo);       break;

        // ── Leave ─────────────────────────────────────────────────────────
        case 'get_leave_requests':   handle_get_leave_requests($pdo);   break;
        case 'apply_leave':          handle_apply_leave($pdo);          break;
        case 'update_leave_status':  handle_update_leave_status($pdo);  break;

        // ── Loans & Resignations ──────────────────────────────────────────
        case 'get_loan_requests':          handle_get_loan_requests($pdo);          break;
        case 'apply_loan':                 handle_apply_loan($pdo);                 break;
        case 'update_loan_status':         handle_update_loan_status($pdo);         break;
        case 'get_resignation_requests':   handle_get_resignation_requests($pdo);   break;
        case 'update_resignation_status':  handle_update_resignation_status($pdo);  break;

        // ── Subjects ──────────────────────────────────────────────────────
        case 'get_subjects':        handle_get_subjects($pdo);        break;
        case 'save_subject':        handle_save_subject($pdo);        break;
        case 'delete_subject':      handle_delete_subject($pdo);      break;
        case 'get_subject_loads':   handle_get_subject_loads($pdo);   break;
        case 'save_subject_load':   handle_save_subject_load($pdo);   break;
        case 'delete_subject_load': handle_delete_subject_load($pdo); break;

        // ── Settings & Dashboard ──────────────────────────────────────────
        case 'save_settings':       handle_save_settings($pdo);       break;
        case 'get_dashboard_stats': handle_get_dashboard_stats($pdo); break;
        case 'get_company_info':    handle_get_company_info($pdo);    break;
        case 'get_companies':       handle_get_companies($pdo);       break;
        case 'get_server_time':     handle_get_server_time($pdo);     break;
        case 'get_ess_data':        handle_get_ess_data($pdo);        break;

        default:
            sendError(404, 'Invalid action');
    }
} catch (Exception $e) {
    error_log("API Error ($action): " . $e->getMessage());
    sendError(500, 'An error occurred');
}
?>
