<?php
// ess.php - Employee Self-Service Portal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'backend/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect Admin, HR, and Payroll roles
$session_role = trim($_SESSION['role'] ?? '');
$is_management = in_array($session_role, ['Admin', 'HR', 'Payroll', 'Payroll Officer']);

if ($is_management) {
    if (in_array($session_role, ['Payroll', 'Payroll Officer'])) {
        header('Location: Payroll-Officer.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

// Fetch employee profile on server-side for initial load
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT e.*, u.username, u.email as user_email, c.name as company_name, c.company_code 
                     FROM employees e 
                     JOIN users u ON e.user_id = u.id 
                     JOIN companies c ON e.company_id = c.id 
                     WHERE u.id = ?");
$stmt->execute([$user_id]);
$emp = $stmt->fetch();

$full_name = $emp['full_name'] ?? $_SESSION['full_name'] ?? 'Employee';
$emp_id = $emp['employee_id'] ?? '---';
$company_name = $emp['company_name'] ?? $_SESSION['company_name'] ?? 'ALM Tech Solutions';
$company_code = $emp['company_code'] ?? $_SESSION['company_code'] ?? 'N/A';
$position = $emp['position'] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal - <?php echo htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?></title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/chart.min.js"></script>
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ESS Specific Styles */
        .ess-request-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 2rem; }
        .request-form-card { background: white; padding: 2rem; border-radius: var(--border-radius); box-shadow: var(--card-shadow); }
        .request-history-card { background: white; padding: 2rem; border-radius: var(--border-radius); box-shadow: var(--card-shadow); }
        
        .tab-nav { display: flex; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem; }
        .tab-link { background: none; border: none; padding: 0.5rem 1rem; cursor: pointer; color: var(--text-muted); font-weight: 600; position: relative; }
        .tab-link.active { color: var(--primary-color); }
        .tab-link.active::after { content: ''; position: absolute; bottom: -0.6rem; left: 0; right: 0; height: 3px; background: var(--primary-color); border-radius: 3px; }
        
        .profile-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; }
        .profile-card { background: white; padding: 2rem; border-radius: var(--border-radius); box-shadow: var(--card-shadow); text-align: center; }
        .profile-details-card { background: white; padding: 2rem; border-radius: var(--border-radius); box-shadow: var(--card-shadow); }
        
        .info-row { display: flex; justify-content: space-between; padding: 0.8rem 0; border-bottom: 1px solid #f5f5f5; }
        .info-label { color: var(--text-muted); font-weight: 500; }
        .info-value { font-weight: 600; color: var(--text-dark); }
        
        .status-tag { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-distributed { background: #cce5ff; color: #004085; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        
        .req-item {
            color: #dc3545;
        }
        .req-item.valid {
            color: #28a745;
        }
        
        /* Modal for Payslip */
        #payslipModal .modal-content { max-width: 800px; padding: 3rem; }
        .payslip-header { text-align: center; margin-bottom: 2rem; border-bottom: 2px solid var(--primary-color); padding-bottom: 1rem; }
        .payslip-body { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .payslip-section h4 { border-bottom: 1px solid #eee; padding-bottom: 0.5rem; margin-bottom: 1rem; color: var(--primary-color); }
        .payslip-footer { margin-top: 2rem; text-align: center; font-style: italic; color: var(--text-muted); font-size: 0.8rem; }
        
        /* Glass Morphism Swal2 Styles */
        .swal2-popup.glass-modal {
            background: rgba(255, 255, 255, 0.15) !important;
            backdrop-filter: blur(25px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(25px) saturate(180%) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            border-radius: 20px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25), inset 0 1px 1px rgba(255, 255, 255, 0.4) !important;
        }
        .swal2-popup.glass-modal .swal2-title { color: #ffffff !important; }
        .swal2-popup.glass-modal .swal2-html-container, .swal2-popup.glass-modal .swal2-text { color: rgba(255, 255, 255, 0.9) !important; }
        .swal2-popup.glass-modal .swal2-confirm { background: linear-gradient(135deg, #4facfe, #00f2fe) !important; border-radius: 20px !important; }
        .swal2-popup.glass-modal .swal2-cancel { background: rgba(255, 255, 255, 0.15) !important; border: 1px solid rgba(255, 255, 255, 0.3) !important; color: #ffffff !important; border-radius: 20px !important; }
        .swal2-popup.glass-modal .swal2-input { background: rgba(255, 255, 255, 0.2) !important; border: 1px solid rgba(255, 255, 255, 0.3) !important; color: #ffffff !important; border-radius: 20px !important; }
        .swal2-container.glass-backdrop { background: rgba(0, 0, 0, 0.5) !important; backdrop-filter: blur(4px) !important; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="assets/logo.jpg" alt="Logo" class="sidebar-logo" border-radius="50%">
                    <span>ALM PORTAL</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <button class="nav-btn active" onclick="showPage('dashboard', this)">
                    <i class="fas fa-th-large"></i> Dashboard
                </button>
                <button class="nav-btn" onclick="showPage('attendance', this)">
                    <i class="fas fa-calendar-check"></i> Attendance
                </button>
                <button class="nav-btn" onclick="showPage('payroll', this)">
                    <i class="fas fa-file-invoice-dollar"></i> Payroll & Payslips
                </button>
                <button class="nav-btn" onclick="showPage('requests', this)">
                    <i class="fas fa-paper-plane"></i> Requests
                </button>
                <button class="nav-btn" onclick="showPage('profile', this)">
                    <i class="fas fa-user-circle"></i> My Profile
                </button>
            </nav>
            <div class="sidebar-footer">
                <button class="nav-btn logout" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <div class="page-title">
                    <h2 id="current-page-title">Dashboard</h2>
                </div>
                <div class="user-profile">
                    <div class="profile-info">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($full_name); ?>&background=random" alt="User">
                        <div class="profile-text">
                            <span class="name"><?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="role"><?php echo htmlspecialchars($position, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Page -->
            <section id="dashboard" class="page active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-building"></i></div>
                        <div class="stat-info">
                            <h3>Company Code</h3>
                            <div class="stat-value" style="font-size: 1.2rem;"><?php echo htmlspecialchars($company_code, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-user-clock"></i></div>
                        <div class="stat-info">
                            <h3>Days Present</h3>
                            <div class="stat-value" id="stat-present">0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon red"><i class="fas fa-calendar-times"></i></div>
                        <div class="stat-info">
                            <h3>Days Absent</h3>
                            <div class="stat-value" id="stat-absent" style="color: #dc3545;">0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-umbrella-beach"></i></div>
                        <div class="stat-info">
                            <h3>Leave Balance</h3>
                            <div class="stat-value" id="stat-leave-balance">0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon red"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <h3>Late Minutes</h3>
                            <div class="stat-value" id="stat-late">0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon red"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <h3>Late Minutes</h3>
                            <div class="stat-value" id="stat-late">0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="fas fa-wallet"></i></div>
                        <div class="stat-info">
                            <h3>Last Net Pay</h3>
                            <div class="stat-value" id="stat-net-pay">₱0.00</div>
                        </div>
                    </div>
                </div>

                <div class="charts-container" style="margin-bottom: 2rem;">
                    <div class="chart-card" style="grid-column: 1 / -1;">
                        <h3>Attendance Summary</h3>
                        <div style="height: 300px; position: relative;">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="charts-container">
                    <div class="chart-card">
                        <h3>Recent Attendance</h3>
                        <div class="modern-table-wrapper">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="dashboard-attendance-body">
                                    <tr><td colspan="4" class="text-center">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3>Latest Payslip</h3>
                        <div id="latest-payslip-summary" class="text-center" style="padding: 1rem;">
                            <p class="text-muted">No payroll data available yet.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Attendance Page -->
            <section id="attendance" class="page">
                <div class="attendance-controls">
                    <div class="control-group">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="att-search" placeholder="Search date or status..." onkeyup="filterAttendance()">
                        </div>
                    </div>
                    <div class="control-group right">
                        <div class="filter-item">
                            <label><i class="fas fa-calendar"></i> From</label>
                            <input type="date" id="att-from" onchange="filterAttendance()">
                        </div>
                        <div class="filter-item">
                            <label><i class="fas fa-calendar"></i> To</label>
                            <input type="date" id="att-to" onchange="filterAttendance()">
                        </div>
                        <button class="btn btn-primary" onclick="generateDTR()" style="margin-left: 10px;">
                            <i class="fas fa-file-pdf"></i> Generate DTR
                        </button>
                    </div>
                </div>
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Lunch Out</th>
                                <th>Lunch In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                                <th>Late (Min)</th>
                            </tr>
                        </thead>
                        <tbody id="attendance-history-body">
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Payroll Page -->
            <section id="payroll" class="page">
                <div class="modern-table-wrapper">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Basic Pay</th>
                                <th>Deductions</th>
                                <th>Net Pay</th>
                                <th>Status</th>
                                <th>Date Processed</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="payroll-history-body">
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Requests Page -->
            <section id="requests" class="page">
                <div class="tab-nav">
                    <button class="tab-link active" onclick="switchRequestTab('leave', this)">Leave Requests</button>
                    <button class="tab-link" onclick="switchRequestTab('loan', this)">Cash Advance</button>
                    <button class="tab-link" onclick="switchRequestTab('resignation', this)">Resignation</button>
                </div>

                <!-- Leave Request Section -->
                <div id="request-leave" class="request-section active">
                    <div class="ess-request-grid">
                        <div class="request-form-card">
                            <h3>Apply for Leave</h3>
                            <form id="leave-form" onsubmit="submitRequest(event, 'leave')">
                                <div class="form-group-custom">
                                    <label>Leave Type</label>
                                    <select name="leave_type" class="form-control-large-gray" required>
                                        <option value="Sick Leave">Sick Leave</option>
                                        <option value="Vacation Leave">Vacation Leave</option>
                                        <option value="Emergency Leave">Emergency Leave</option>
                                        <option value="Maternity/Paternity">Maternity/Paternity</option>
                                    </select>
                                </div>
                                <div class="form-row-custom">
                                    <div class="form-group-custom">
                                        <label>Start Date</label>
                                        <input type="date" name="start_date" class="form-control-large-gray" required>
                                    </div>
                                    <div class="form-group-custom">
                                        <label>End Date</label>
                                        <input type="date" name="end_date" class="form-control-large-gray" required>
                                    </div>
                                </div>
                                <div class="form-group-custom">
                                    <label>Reason</label>
                                    <textarea name="reason" class="form-control-large-gray" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-dark-purple btn-full">Submit Request</button>
                            </form>
                        </div>
                        <div class="request-history-card">
                            <h3>Leave History</h3>
                            <div class="modern-table-wrapper" style="box-shadow: none;">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Period</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="leave-history-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loan Request Section -->
                <div id="request-loan" class="request-section" style="display: none;">
                    <div class="ess-request-grid">
                        <div class="request-form-card" style="padding: 0; background: #f8f9fa; border: 1px solid #e9ecef; overflow: hidden;">
                            <div class="printable-form-wrapper" style="background: white; padding: 40px 30px; margin: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-radius: 4px;">
                                <div style="display: flex; align-items: center; margin-bottom: 30px;">
                                    <img src="assets/logo.jpg" alt="Logo" style="width: 70px; height: 70px; margin-right: 20px; object-fit: contain;">
                                    <div style="font-weight: 800; font-size: 18px; color: #111; line-height: 1.3;">
                                        ACLC COLLEGE<br>TACLOBAN
                                    </div>
                                </div>
                                <div style="text-align: center; font-weight: 800; font-size: 20px; margin-bottom: 40px; color: #111; letter-spacing: 1px;">
                                    CASH ADVANCE FORM
                                </div>
                                <form id="loan-form" onsubmit="submitRequest(event, 'loan')">
                                    <style>
                                        .ca-row { display: flex; align-items: flex-end; margin-bottom: 20px; }
                                        .ca-label { width: 160px; font-weight: 700; font-size: 14px; color: #333; }
                                        .ca-colon { width: 25px; text-align: center; font-weight: 700; color: #333; }
                                        .ca-input { flex-grow: 1; border: none; border-bottom: 1px solid #000; background: transparent; font-family: inherit; font-size: 15px; outline: none; padding: 4px 8px; font-weight: 600; color: #111; }
                                        .ca-input[readonly] { color: #555; border-bottom: 1px dotted #888; }
                                        .ca-input:focus { border-bottom: 2px solid var(--primary-color); background: rgba(0,0,0,0.02); }
                                    </style>
                                    
                                    <div class="ca-row">
                                        <div class="ca-label">CA FORM NO.</div>
                                        <div class="ca-colon">:</div>
                                        <input type="text" class="ca-input" value="Auto-generated" readonly>
                                    </div>
                                    <div class="ca-row">
                                        <div class="ca-label">Date Filed:</div>
                                        <div class="ca-colon">:</div>
                                        <input type="text" class="ca-input" value="<?php echo date('F j, Y'); ?>" readonly>
                                    </div>
                                    <div class="ca-row">
                                        <div class="ca-label">Name of Employee</div>
                                        <div class="ca-colon">:</div>
                                        <input type="text" class="ca-input" value="<?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                    </div>
                                    <div class="ca-row">
                                        <div class="ca-label">Position</div>
                                        <div class="ca-colon">:</div>
                                        <input type="text" class="ca-input" value="<?php echo htmlspecialchars($position, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                                    </div>
                                    <div class="ca-row">
                                        <div class="ca-label">Amount Loan</div>
                                        <div class="ca-colon">:</div>
                                        <input type="number" name="amount" class="ca-input" placeholder="Enter amount (PHP)..." step="0.01" required style="border-bottom: 2px solid var(--primary-color);">
                                    </div>
                                    <div class="ca-row" style="align-items: flex-start;">
                                        <div class="ca-label" style="margin-top: 8px;">Purpose</div>
                                        <div class="ca-colon" style="margin-top: 8px;">:</div>
                                        <textarea name="reason" class="ca-input" rows="2" placeholder="Enter reason for cash advance..." required style="resize: none; border-bottom: 2px solid var(--primary-color); line-height: 1.5; padding-top: 8px;"></textarea>
                                    </div>
                                    
                                    <div style="margin-top: 50px; margin-bottom: 40px;">
                                        <div class="ca-row" style="align-items: flex-start;">
                                            <div class="ca-label" style="margin-top: 8px;">Signature</div>
                                            <div style="flex-grow: 1; margin-left: 25px; border-bottom: 1px solid #000; min-height: 25px;"></div>
                                        </div>
                                    </div>

                                    <div style="margin-top: 30px; font-size: 14px;">
                                        <div class="ca-row" style="margin-bottom: 15px;">
                                            <div class="ca-label" style="width: 200px;">Verified by</div>
                                            <div class="ca-colon">:</div>
                                            <div style="font-weight: 700; color: #333;">Accounting Clerk</div>
                                        </div>
                                        <div class="ca-row" style="margin-bottom: 15px;">
                                            <div class="ca-label" style="width: 200px;">Recommending Approval</div>
                                            <div class="ca-colon">:</div>
                                            <div style="font-weight: 700; color: #333;">School Director</div>
                                        </div>
                                        <div class="ca-row" style="margin-bottom: 15px;">
                                            <div class="ca-label" style="width: 200px;">Approved By</div>
                                            <div class="ca-colon">:</div>
                                            <div style="font-weight: 700; color: #333;">Managing Director</div>
                                        </div>
                                        <div class="ca-row" style="margin-bottom: 15px;">
                                            <div class="ca-label" style="width: 200px;">Released By</div>
                                            <div class="ca-colon">:</div>
                                            <div style="font-weight: 700; color: #333;">Cashier</div>
                                        </div>
                                    </div>

                                    <div style="margin-top: 40px; border-top: 1px dashed #ccc; padding-top: 20px; display: flex; justify-content: flex-end; gap: 15px;">
                                        <button type="button" onclick="printFilledCashAdvance()" class="btn" style="padding: 12px 30px; font-size: 16px; border-radius: 30px; font-weight: 600; text-decoration: none; color: #333; background: #e9ecef; border: 1px solid #ced4da; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); display: flex; align-items: center;">
                                            <i class="fas fa-print" style="margin-right: 8px;"></i> Print Filled Form
                                        </button>
                                        <button type="submit" class="btn btn-dark-purple" style="padding: 12px 30px; font-size: 16px; border-radius: 30px; font-weight: 600; box-shadow: 0 4px 10px rgba(103, 58, 183, 0.3);">
                                            <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Submit Request
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="request-history-card">
                            <h3>Cash Advance History</h3>
                            <div class="modern-table-wrapper" style="box-shadow: none;">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="loan-history-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resignation Section -->
                <div id="request-resignation" class="request-section" style="display: none;">
                    <div class="ess-request-grid">
                        <div class="request-form-card">
                            <h3>Submit Resignation</h3>
                            <form id="resignation-form" onsubmit="submitRequest(event, 'resignation')">
                                <div class="form-group-custom">
                                    <label>Effective Date</label>
                                    <input type="date" name="effective_date" class="form-control-large-gray" required>
                                </div>
                                <div class="form-group-custom">
                                    <label>Reason</label>
                                    <textarea name="reason" class="form-control-large-gray" rows="4" required placeholder="State your reason for leaving..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger btn-full">Submit Resignation</button>
                            </form>
                        </div>
                        <div class="request-history-card">
                            <h3>Resignation Status</h3>
                            <div id="resignation-status-container" style="padding: 1rem;">
                                <p class="text-muted">No resignation requests filed.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profile Page -->
            <section id="profile" class="page">
                <div class="profile-grid">
                    <div class="profile-card">
                        <div id="profile-picture-container" style="position: relative; display: inline-block; margin-bottom: 1rem;">
                            <img id="profile-picture" 
                                 src="<?php echo !empty($emp['profile_picture']) ? htmlspecialchars($emp['profile_picture'], ENT_QUOTES, 'UTF-8') : 'https://ui-avatars.com/api/?name=' . urlencode($full_name) . '&size=150&background=random'; ?>" 
                                 alt="Profile Picture" 
                                 style="width:150px; height:150px; border-radius:50%; object-fit: cover; border: 4px solid #eee; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            <label for="profile-picture-upload" 
                                   style="position: absolute; bottom: 5px; right: 5px; background: var(--primary-color); color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2); transition: all 0.3s ease;" 
                                   onmouseover="this.style.background='#2a02a8'; this.style.transform='scale(1.1)'" 
                                   onmouseout="this.style.background='var(--primary-color)'; this.style.transform='scale(1)'">
                                <i class="fas fa-camera" style="font-size: 14px;"></i>
                            </label>
                            <input type="file" id="profile-picture-upload" accept="image/*" style="display: none;" onchange="uploadProfilePicture(event)">
                        </div>
                        <h2 style="margin-bottom:0.2rem;"><?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="text-muted" style="margin-bottom:1.5rem;"><?php echo htmlspecialchars($position, ENT_QUOTES, 'UTF-8'); ?></p>
                        
                        <div class="info-row"><span class="info-label">Employee ID</span> <span class="info-value"><?php echo htmlspecialchars($emp_id, ENT_QUOTES, 'UTF-8'); ?></span></div>
                        <div class="info-row"><span class="info-label">Company Code</span> <span class="info-value" style="color: var(--primary-color); font-weight: 700;"><?php echo htmlspecialchars($company_code, ENT_QUOTES, 'UTF-8'); ?></span></div>
                        <div class="info-row"><span class="info-label">Department</span> <span class="info-value"><?php echo htmlspecialchars($emp['department'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span></div>
                        <div class="info-row"><span class="info-label">Employment Date</span> <span class="info-value"><?php echo htmlspecialchars($emp['hire_date'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span></div>
                        <div class="info-row"><span class="info-label">Employee Type</span> <span class="info-value"><?php echo htmlspecialchars($emp['work_status'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span></div>
                        <div class="info-row"><span class="info-label">Status</span> <span class="status-tag status-approved"><?php echo htmlspecialchars($emp['status'] ?? 'Active', ENT_QUOTES, 'UTF-8'); ?></span></div>
                    </div>
                    
                    <div class="profile-details-card">
                        <div class="tab-nav">
                            <button class="tab-link active" onclick="switchProfileTab('info', this)">Personal Information</button>
                            <?php if ($position === 'Faculty'): ?>
                            <button class="tab-link" onclick="switchProfileTab('faculty', this)">Subject Load & Schedule</button>
                            <?php endif; ?>
                            <button class="tab-link" onclick="switchProfileTab('security', this)">Security Settings</button>
                        </div>
                        
                        <div id="profile-info" class="profile-tab-section active">
                            <form id="employeeProfileForm">
                                <div class="form-row-custom">
                                    <div class="form-group-custom">
                                        <label>Employee ID</label>
                                        <input type="text" value="<?php echo htmlspecialchars($emp_id, ENT_QUOTES, 'UTF-8'); ?>" class="form-control-large-gray" readonly>
                                    </div>
                                    <div class="form-group-custom">
                                        <label>Company Code</label>
                                        <input type="text" value="<?php echo htmlspecialchars($company_code, ENT_QUOTES, 'UTF-8'); ?>" class="form-control-large-gray" readonly style="font-weight: 700; color: var(--primary-color);">
                                    </div>
                                </div>
                                <div class="form-row-custom">
                                    <div class="form-group-custom">
                                        <label>Date Hired</label>
                                        <input type="text" value="<?php echo htmlspecialchars($emp['hire_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control-large-gray" readonly>
                                    </div>
                                    <div class="form-group-custom">
                                        <label>Work Status</label>
                                        <input type="text" value="<?php echo htmlspecialchars($emp['work_status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control-large-gray" readonly>
                                    </div>
                                </div>
                                <div class="form-row-custom">
                                    <div class="form-group-custom">
                                        <label>Work Position</label>
                                        <input type="text" value="<?php echo htmlspecialchars($emp['work_position'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control-large-gray" readonly>
                                    </div>
                                    <div class="form-group-custom">
                                        <label>Department</label>
                                        <input type="text" value="<?php echo htmlspecialchars($emp['department'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control-large-gray" readonly>
                                    </div>
                                </div>
                                <div class="form-row-custom">
                                    <div class="form-group-custom">
                                        <label>Email Address</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($emp['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                    <div class="form-group-custom">
                                        <label>Date of Birth</label>
                                        <input type="date" name="dob" value="<?php echo htmlspecialchars($emp['dob'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="form-row-custom">
                                    <div class="form-group-custom">
                                        <label>SSS No.</label>
                                        <input type="text" name="sss" value="<?php echo htmlspecialchars($emp['sss'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="form-group-custom">
                                        <label>PhilHealth No.</label>
                                        <input type="text" name="philhealth" value="<?php echo htmlspecialchars($emp['philhealth'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="form-row-custom">
                                    <div class="form-group-custom">
                                        <label>TIN</label>
                                        <input type="text" name="tin" value="<?php echo htmlspecialchars($emp['tin'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="form-group-custom">
                                        <label>Pag-IBIG No.</label>
                                        <input type="text" name="pagibig" value="<?php echo htmlspecialchars($emp['pagibig'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div id="profile-msg" style="margin-bottom: 15px; display: none;"></div>
                                <button type="button" class="btn btn-primary" id="saveEmployeeProfileBtn" onclick="saveEmployeeProfile()">Save Personal Information</button>
                            </form>
                        </div>
                        
                        <?php if ($position === 'Faculty'): ?>
                        <div id="profile-faculty" class="profile-tab-section" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3>Subject Loads</h3>
                                <button class="btn btn-dark-purple" onclick="openSubjectLoadModal()">
                                    <i class="fas fa-plus"></i> Add Subject
                                </button>
                            </div>
                            <div class="modern-table-wrapper" style="margin-bottom: 2rem;">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Description</th>
                                            <th>Units</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="faculty-subjects-body">
                                        <tr><td colspan="4" class="text-center">Loading subjects...</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
                                <h3>Schedules</h3>
                                <button class="btn btn-dark-purple" onclick="openScheduleModal()">
                                    <i class="fas fa-plus"></i> Add Schedule
                                </button>
                            </div>
                            <div class="modern-table-wrapper">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Day</th>
                                            <th>Time Start</th>
                                            <th>Time End</th>
                                            <th>Room</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="faculty-schedules-body">
                                        <tr><td colspan="6" class="text-center">Loading schedules...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div id="profile-security" class="profile-tab-section" style="display: none;">
                            <form onsubmit="changePassword(event)">
                                <div class="form-group-custom">
                                    <label>Current Password</label>
                                    <div class="input-wrapper">
                                        <input type="password" id="current_password" name="current_password" class="form-control-large-gray password-field" required>
                                        <i class="fas fa-eye toggle-password" data-target="current_password" role="button" tabindex="0" aria-label="Show password" title="Show password"></i>
                                    </div>
                                </div>
                                <div class="form-group-custom">
                                    <label>New Password</label>
                                    <div class="input-wrapper">
                                        <input type="password" id="new_password" name="new_password" class="form-control-large-gray password-field" required oninput="checkPasswordStrength()">
                                        <i class="fas fa-eye toggle-password" data-target="new_password" role="button" tabindex="0" aria-label="Show password" title="Show password"></i>
                                    </div>
                                    <div id="password-requirements" style="margin-top: 5px; font-size: 0.8rem;">
                                        <div id="req-length" class="req-item">At least 8 characters</div>
                                        <div id="req-uppercase" class="req-item">One uppercase letter</div>
                                        <div id="req-lowercase" class="req-item">One lowercase letter</div>
                                        <div id="req-number" class="req-item">One number</div>
                                        <div id="req-special" class="req-item">One special character</div>
                                    </div>
                                </div>
                                <div class="form-group-custom">
                                    <label>Confirm New Password</label>
                                    <div class="input-wrapper">
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control-large-gray password-field" required>
                                        <i class="fas fa-eye toggle-password" data-target="confirm_password" role="button" tabindex="0" aria-label="Show password" title="Show password"></i>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-dark-purple">Update Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Modals -->
    <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 100001; pointer-events: none;"></div>

    <!-- Subject Load Modal (Faculty) -->
    <div id="subjectLoadModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Add Subject Load</h2>
                <span class="close" onclick="closeModal('subjectLoadModal')">&times;</span>
            </div>
            <form id="subjectLoadForm" onsubmit="saveSubjectLoad(event)">
                <div class="form-group-custom">
                    <label>Subject Code</label>
                    <input type="text" name="code" class="form-control-large-gray" required>
                </div>
                <div class="form-group-custom">
                    <label>Description</label>
                    <input type="text" name="description" class="form-control-large-gray" required>
                </div>
                <div class="form-group-custom">
                    <label>Units</label>
                    <input type="number" name="units" step="0.5" class="form-control-large-gray" required>
                </div>
                <div class="form-group-custom" style="display:none;">
                    <input type="hidden" name="faculty_id" value="<?php echo $emp['id']; ?>">
                </div>
                <button type="submit" class="btn btn-dark-purple btn-full">Save Subject</button>
            </form>
        </div>
    </div>

    <!-- Schedule Modal (Faculty) -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Add Schedule</h2>
                <span class="close" onclick="closeModal('scheduleModal')">&times;</span>
            </div>
            <form id="scheduleForm" onsubmit="saveSchedule(event)">
                <div class="form-group-custom">
                    <label>Subject</label>
                    <select name="subject_load_id" id="scheduleSubjectSelect" class="form-control-large-gray" required>
                    </select>
                </div>
                <div class="form-group-custom">
                    <label>Day of Week</label>
                    <select name="day_of_week" class="form-control-large-gray" required>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                <div class="form-row-custom">
                    <div class="form-group-custom">
                        <label>Start Time</label>
                        <input type="time" name="time_start" class="form-control-large-gray" required>
                    </div>
                    <div class="form-group-custom">
                        <label>End Time</label>
                        <input type="time" name="time_end" class="form-control-large-gray" required>
                    </div>
                </div>
                <div class="form-group-custom">
                    <label>Room (Optional)</label>
                    <input type="text" name="room" class="form-control-large-gray">
                </div>
                <button type="submit" class="btn btn-dark-purple btn-full">Save Schedule</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/face-api.min.js"></script>
    <script src="js/face-api-manager.js"></script>
    <script src="js/script.js"></script>
    <script src="js/jspdf.umd.min.js"></script>
    <script src="js/jspdf.plugin.autotable.min.js"></script>
    
    <script>
        let essData = null;

        function showPage(pageId, btn) {
            document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
            
            const pageElement = document.getElementById(pageId);
            if (pageElement) {
                pageElement.classList.add('active');
            }
            
            if (btn) {
                btn.classList.add('active');
                const title = btn.innerText.trim();
                document.getElementById('current-page-title').innerText = title;
            }
        }

        function printFilledCashAdvance() {
            const form = document.getElementById('loan-form');
            const amount = form.amount ? form.amount.value : '';
            const reason = form.reason ? form.reason.value : '';
            const today = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            const url = `print_cash_advance.php?amount=${encodeURIComponent(amount)}&reason=${encodeURIComponent(reason)}&date=${encodeURIComponent(today)}`;
            window.open(url, '_blank');
        }

        function printHistoricalCashAdvance(id) {
            const loans = essData.loans || [];
            const loan = loans.find(l => l.id == id);
            if (loan) {
                const amount = loan.amount;
                const reason = loan.reason || 'Cash Advance'; 
                const dateF = new Date(loan.requested_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                const caNo = "CA-" + String(loan.id).padStart(5, '0');
                const url = `print_cash_advance.php?amount=${encodeURIComponent(amount)}&reason=${encodeURIComponent(reason)}&date=${encodeURIComponent(dateF)}&ca_no=${encodeURIComponent(caNo)}`;
                window.open(url, '_blank');
            }
        }

        function switchRequestTab(type, btn) {
            document.querySelectorAll('.request-section').forEach(s => s.style.display = 'none');
            document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
            
            document.getElementById(`request-${type}`).style.display = 'block';
            btn.classList.add('active');
        }

        function switchProfileTab(type, btn) {
            document.querySelectorAll('.profile-tab-section').forEach(s => s.style.display = 'none');
            const links = btn.parentElement.querySelectorAll('.tab-link');
            links.forEach(l => l.classList.remove('active'));
            
            document.getElementById(`profile-${type}`).style.display = 'block';
            btn.classList.add('active');
        }

        async function loadESS() {
            try {
                const response = await fetch('backend/api.php?action=get_ess_data');
                essData = await response.json();
                if (!essData.profile) throw new Error("Failed to load profile");

                renderDashboard();
                renderAttendance();
                renderPayroll();
                renderRequests();
            } catch (err) {
                console.error(err);
                showToast("Failed to load employee data.", "error");
            }
        }

        function renderDashboard() {
            const att = essData.attendance || [];
            const payroll = essData.payroll || [];
            const leave = essData.leave || [];
            const profile = essData.profile;

            // Stats
            document.getElementById('stat-present').innerText = att.filter(a => a.check_in).length;
            document.getElementById('stat-absent').innerText = essData.absent_days || 0;
            document.getElementById('stat-leave-balance').innerText = profile.leave_balance || 0;
            document.getElementById('stat-late').innerText = att.reduce((acc, curr) => acc + (parseInt(curr.late_minutes) || 0), 0);
            
            const lastPay = payroll[0];
            if (lastPay) {
                document.getElementById('stat-net-pay').innerText = '₱' + parseFloat(lastPay.net_pay).toLocaleString(undefined, {minimumFractionDigits: 2});
                
                document.getElementById('latest-payslip-summary').innerHTML = `
                    <div style="font-size: 1.5rem; font-weight: 800; margin: 1rem 0;">₱${parseFloat(lastPay.net_pay).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
                    <div class="text-muted" style="margin-bottom: 1.5rem;">Period: ${lastPay.period}</div>
                    <button class="btn btn-dark-purple" onclick="exportPayslip(${lastPay.id})">
                        <i class="fas fa-download"></i> Download Payslip
                    </button>
                `;
            }

            // Recent Attendance
            const recentAtt = att.slice(0, 5);
            document.getElementById('dashboard-attendance-body').innerHTML = recentAtt.map(a => `
                <tr>
                    <td>${a.log_date}</td>
                    <td>${a.check_in || '---'}</td>
                    <td>${a.check_out || '---'}</td>
                    <td><span class="late-tag ${a.status === 'Late' ? 'text-danger' : 'text-success'}">${a.status}</span></td>
                </tr>
            `).join('') || '<tr><td colspan="4" class="text-center">No recent logs</td></tr>';

            // Draw Absence Chart
            drawAbsenceChart(att.filter(a => a.check_in).length, essData.absent_days || 0);
            
            // If faculty, load their specific data
            if (profile.position === 'Faculty') {
                loadFacultySubjectsAndSchedules();
            }
        }

        let absenceChartInstance = null;
        function drawAbsenceChart(presentDays, absentDays) {
            const ctx = document.getElementById('attendanceChart');
            if (!ctx) return;
            
            if (absenceChartInstance) {
                absenceChartInstance.destroy();
            }
            
            absenceChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Present Days', 'Absent Days'],
                    datasets: [{
                        data: [presentDays, absentDays],
                        backgroundColor: ['#28a745', '#dc3545'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
        
        function openSubjectLoadModal() {
            document.getElementById('subjectLoadModal').style.display = 'block';
        }
        
        function openScheduleModal() {
            document.getElementById('scheduleModal').style.display = 'block';
        }
        
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        async function loadFacultySubjectsAndSchedules() {
            try {
                // Fetch Subjects
                let res = await fetch(`backend/api.php?action=get_subject_loads&faculty_id=${essData.profile.id}`);
                const subjects = await res.json();
                
                let tbody = document.getElementById('faculty-subjects-body');
                if (tbody) {
                    tbody.innerHTML = subjects.map(s => `
                        <tr>
                            <td>${s.code}</td>
                            <td>${s.description}</td>
                            <td>${s.units}</td>
                            <td>
                                <button class="btn-icon" style="color:var(--danger-color)" onclick="deleteSubjectLoad(${s.id})"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    `).join('') || '<tr><td colspan="4" class="text-center">No subjects found</td></tr>';
                }
                
                // Populate schedule select
                let select = document.getElementById('scheduleSubjectSelect');
                if (select) {
                    select.innerHTML = subjects.map(s => `<option value="${s.id}">${s.code} - ${s.description}</option>`).join('');
                }
                
                // Fetch Schedules
                res = await fetch(`backend/api.php?action=get_subject_schedules&faculty_id=${essData.profile.id}`);
                const schedules = await res.json();
                
                let tbodySched = document.getElementById('faculty-schedules-body');
                if (tbodySched) {
                    tbodySched.innerHTML = schedules.map(s => `
                        <tr>
                            <td>${s.subject_code} - ${s.subject_description}</td>
                            <td>${s.day_of_week}</td>
                            <td>${s.time_start}</td>
                            <td>${s.time_end}</td>
                            <td>${s.room || 'N/A'}</td>
                            <td>
                                <button class="btn-icon" style="color:var(--danger-color)" onclick="deleteSchedule(${s.id})"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    `).join('') || '<tr><td colspan="6" class="text-center">No schedules found</td></tr>';
                }
                
            } catch (err) {
                console.error("Error loading faculty data", err);
            }
        }
        
        async function saveSubjectLoad(e) {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target).entries());
            try {
                const res = await fetch('backend/api.php?action=save_subject_load_ess', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    showToast('Subject saved!', 'success');
                    closeModal('subjectLoadModal');
                    e.target.reset();
                    loadFacultySubjectsAndSchedules();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('Connection error', 'error');
            }
        }
        
        async function deleteSubjectLoad(id) {
            if (!confirm('Delete this subject load? This will also delete related schedules.')) return;
            try {
                const res = await fetch(`backend/api.php?action=delete_subject_load_ess&id=${id}`);
                const result = await res.json();
                if (result.success) {
                    showToast('Deleted', 'success');
                    loadFacultySubjectsAndSchedules();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('Connection error', 'error');
            }
        }
        
        async function saveSchedule(e) {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target).entries());
            data.faculty_id = essData.profile.id;
            try {
                const res = await fetch('backend/api.php?action=save_subject_schedule_ess', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.success) {
                    showToast('Schedule saved!', 'success');
                    closeModal('scheduleModal');
                    e.target.reset();
                    loadFacultySubjectsAndSchedules();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('Connection error', 'error');
            }
        }
        
        async function deleteSchedule(id) {
            if (!confirm('Delete this schedule?')) return;
            try {
                const res = await fetch(`backend/api.php?action=delete_subject_schedule_ess&id=${id}`);
                const result = await res.json();
                if (result.success) {
                    showToast('Deleted', 'success');
                    loadFacultySubjectsAndSchedules();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (err) {
                showToast('Connection error', 'error');
            }
        }

        function renderAttendance() {
            const att = essData.attendance || [];
            document.getElementById('attendance-history-body').innerHTML = att.map(a => {
                const isAbsent = a.status === 'Absent';
                return `
                <tr style="${isAbsent ? 'background: rgba(220, 53, 69, 0.05);' : ''}">
                    <td>${a.log_date}</td>
                    <td>${isAbsent ? '<span class="text-muted">—</span>' : (a.check_in || '---')}</td>
                    <td>${isAbsent ? '<span class="text-muted">—</span>' : (a.lunch_out || '---')}</td>
                    <td>${isAbsent ? '<span class="text-muted">—</span>' : (a.lunch_in || '---')}</td>
                    <td>${isAbsent ? '<span class="text-muted">—</span>' : (a.check_out || '---')}</td>
                    <td><span class="late-tag ${a.status === 'Late' ? 'text-danger' : (a.status === 'Absent' ? 'status-tag status-rejected' : 'text-success')}">${a.status}</span></td>
                    <td>${a.late_minutes || 0}</td>
                </tr>
                `;
            }).join('') || '<tr><td colspan="7" class="text-center">No logs found</td></tr>';
        }

        function filterAttendance() {
            const search = document.getElementById('att-search').value.toLowerCase();
            const from = document.getElementById('att-from').value;
            const to = document.getElementById('att-to').value;
            
            const filtered = essData.attendance.filter(a => {
                const matchesSearch = a.log_date.includes(search) || a.status.toLowerCase().includes(search);
                const matchesFrom = !from || a.log_date >= from;
                const matchesTo = !to || a.log_date <= to;
                return matchesSearch && matchesFrom && matchesTo;
            });
            
            document.getElementById('attendance-history-body').innerHTML = filtered.map(a => `
                <tr>
                    <td>${a.log_date}</td>
                    <td>${a.check_in || '---'}</td>
                    <td>${a.lunch_out || '---'}</td>
                    <td>${a.lunch_in || '---'}</td>
                    <td>${a.check_out || '---'}</td>
                    <td><span class="late-tag ${a.status === 'Late' ? 'text-danger' : 'text-success'}">${a.status}</span></td>
                    <td>${a.late_minutes || 0}</td>
                </tr>
            `).join('') || '<tr><td colspan="7" class="text-center">No logs match filters</td></tr>';
        }

        function renderPayroll() {
            const payroll = essData.payroll || [];
            document.getElementById('payroll-history-body').innerHTML = payroll.map(p => `
                <tr>
                    <td>${p.period}</td>
                    <td>₱${parseFloat(p.basic_pay).toLocaleString()}</td>
                    <td>₱${parseFloat(p.deductions).toLocaleString()}</td>
                    <td class="text-success" style="font-weight:700">₱${parseFloat(p.net_pay).toLocaleString()}</td>
                    <td><span class="status-tag status-approved">${p.status}</span></td>
                    <td>${new Date(p.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn-icon" onclick="exportPayslip(${p.id})"><i class="fas fa-file-pdf"></i></button>
                    </td>
                </tr>
            `).join('') || '<tr><td colspan="7" class="text-center">No payroll records</td></tr>';
        }

        function renderRequests() {
            const leave = essData.leave || [];
            const loans = essData.loans || [];
            const resign = essData.resignations || [];

            document.getElementById('leave-history-body').innerHTML = leave.map(l => `
                <tr>
                    <td>${l.duration}</td>
                    <td>${l.type}</td>
                    <td><span class="status-tag status-${l.status.toLowerCase()}">${l.status}</span></td>
                </tr>
            `).join('') || '<tr><td colspan="3" class="text-center">No leave requests</td></tr>';

            document.getElementById('loan-history-body').innerHTML = loans.map(l => `
                <tr>
                    <td>${new Date(l.requested_at).toLocaleDateString()}</td>
                    <td>₱${parseFloat(l.amount).toLocaleString()}</td>
                    <td><span class="status-tag status-${l.status.toLowerCase()}">${l.status}</span></td>
                    <td style="white-space: nowrap;">
                        <button class="btn-icon" title="Print Request" onclick="printHistoricalCashAdvance(${l.id})">
                            <i class="fas fa-print"></i>
                        </button>
                    </td>
                </tr>
            `).join('') || '<tr><td colspan="4" class="text-center">No cash advance requests</td></tr>';

            const latestResign = resign[0];
            if (latestResign) {
                document.getElementById('resignation-status-container').innerHTML = `
                    <div style="background:#f8f9fa; padding:1.5rem; border-radius:10px; border-left:4px solid var(--danger-color);">
                        <h4 style="margin-bottom:0.5rem;">Status: <span class="status-tag status-${latestResign.status.toLowerCase()}">${latestResign.status}</span></h4>
                        <p><strong>Effective Date:</strong> ${latestResign.effective_date}</p>
                        <p><strong>Reason:</strong> ${latestResign.reason}</p>
                        <p class="text-muted" style="font-size:0.8rem; margin-top:1rem;">Submitted on: ${new Date(latestResign.requested_at).toLocaleString()}</p>
                    </div>
                `;
            }
        }

        async function submitRequest(e, type) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            try {
                const response = await fetch(`backend/requests.php?action=apply_${type}`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                const res = await response.json();
                if (res.success) {
                    showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} request submitted!`, "success");
                    form.reset();
                    await loadESS();
                } else {
                    showToast(res.message, "error");
                }
            } catch (err) {
                showToast("Connection failed.", "error");
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = 'Submit Request';
            }
        }

        async function changePassword(e) {
            e.preventDefault();
            const form = e.target;
            const data = Object.fromEntries(new FormData(form).entries());
            
            if (data.new_password !== data.confirm_password) {
                return showToast("Passwords do not match!", "error");
            }

            // Check if all requirements are met
            const reqs = document.querySelectorAll('.req-item');
            let allValid = true;
            reqs.forEach(req => {
                if (!req.classList.contains('valid')) {
                    allValid = false;
                }
            });

            if (!allValid) {
                return showToast("Password does not meet requirements!", "error");
            }

            try {
                const response = await fetch('backend/api.php?action=change_password', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        oldPass: data.current_password,
                        newPass: data.new_password
                    })
                });
                const res = await response.json();
                if (res.success) {
                    showToast("Password updated successfully!", "success");
                    form.reset();
                } else {
                    showToast(res.message, "error");
                }
            } catch (err) {
                showToast("Connection failed.", "error");
            }
        }

        async function uploadProfilePicture(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showToast('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.', 'error');
                return;
            }

            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                showToast('File size exceeds 5MB limit.', 'error');
                return;
            }

            // Show loading
            showToast('Uploading profile picture...', 'info');

            try {
                const formData = new FormData();
                formData.append('profile_picture', file);

                const response = await fetch('backend/api.php?action=upload_profile_picture', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Update the profile picture on the page
                    const profileImg = document.getElementById('profile-picture');
                    if (profileImg) {
                        // Add timestamp to prevent caching
                        profileImg.src = result.picture_url + '?t=' + new Date().getTime();
                    }
                    
                    // Update header image too
                    const headerImg = document.querySelector('.user-profile img');
                    if (headerImg) {
                        headerImg.src = result.picture_url + '?t=' + new Date().getTime();
                    }
                    
                    showToast('Profile picture updated successfully!', 'success');
                } else {
                    showToast(result.message || 'Failed to upload profile picture.', 'error');
                }
            } catch (err) {
                console.error('Upload error:', err);
                showToast('Failed to connect to the server.', 'error');
            }
        }

        async function exportPayslip(id) {
            const response = await fetch(`backend/api.php?action=get_payslip&id=${id}`);
            const p = await response.json();
            if (!p) return showToast("Failed to fetch payslip data.", "error");

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Header
            doc.setFillColor(30, 1, 120);
            doc.rect(0, 0, 210, 40, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(22);
            doc.text('OFFICIAL PAYSLIP', 105, 20, { align: 'center' });
            doc.setFontSize(10);
            const companyName = (essData && essData.profile && essData.profile.company_name) || 'Company';
            doc.text(companyName, 105, 30, { align: 'center' });

            // Employee Info
            doc.setTextColor(0);
            doc.setFontSize(12);
            doc.text('EMPLOYEE DETAILS', 20, 55);
            doc.line(20, 57, 190, 57);
            
            doc.setFontSize(10);
            const fullName = p.full_name || 'N/A';
            const empCode = p.emp_code || 'N/A';
            const position = p.position || 'N/A';
            const period = p.period || 'N/A';
            const createdAt = p.created_at ? new Date(p.created_at).toLocaleDateString() : 'N/A';
            
            doc.text(`Name: ${fullName}`, 20, 65);
            doc.text(`ID: ${empCode}`, 20, 72);
            doc.text(`Position: ${position}`, 20, 79);
            doc.text(`Period: ${period}`, 130, 65);
            doc.text(`Date: ${createdAt}`, 130, 72);

            // Parse breakdown data
            let breakdown = {};
            try {
                breakdown = p.breakdown ? (typeof p.breakdown === 'string' ? JSON.parse(p.breakdown) : p.breakdown) : {};
            } catch (e) {
                console.error('Error parsing breakdown:', e);
            }

            // Financials
            const basicPay = parseFloat(p.basic_pay) || 0;
            const deductions = parseFloat(p.deductions) || 0;
            const netPay = parseFloat(p.net_pay) || 0;
            
            // Build earnings and deductions arrays
            const earnings = [];
            const deductionsList = [];
            
            // Add basic pay
            earnings.push(['Basic Pay', `PHP ${basicPay.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            
            // Add allowances if present
            if (breakdown.total_allowances && parseFloat(breakdown.total_allowances) > 0) {
                earnings.push(['Total Allowances', `PHP ${parseFloat(breakdown.total_allowances).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            
            // Add faculty-specific earnings
            if (breakdown.load_pay && parseFloat(breakdown.load_pay) > 0) {
                earnings.push(['Load Pay', `PHP ${parseFloat(breakdown.load_pay).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.overtime && parseFloat(breakdown.overtime) > 0) {
                earnings.push(['Overtime', `PHP ${parseFloat(breakdown.overtime).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.differential && parseFloat(breakdown.differential) > 0) {
                earnings.push(['Differential', `PHP ${parseFloat(breakdown.differential).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.substitution && parseFloat(breakdown.substitution) > 0) {
                earnings.push(['Substitution', `PHP ${parseFloat(breakdown.substitution).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.adj_plus && parseFloat(breakdown.adj_plus) > 0) {
                earnings.push(['Adjustments (+)', `PHP ${parseFloat(breakdown.adj_plus).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.ot_holiday && parseFloat(breakdown.ot_holiday) > 0) {
                earnings.push(['OT/Holiday Pay', `PHP ${parseFloat(breakdown.ot_holiday).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.honorarium && parseFloat(breakdown.honorarium) > 0) {
                earnings.push(['Honorarium', `PHP ${parseFloat(breakdown.honorarium).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            
            // Add utility-specific earnings
            if (breakdown.earned && parseFloat(breakdown.earned) > 0 && breakdown.rate_per_day) {
                earnings.push(['Earned for the Period', `PHP ${parseFloat(breakdown.earned).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            
            // Add deductions
            if (breakdown.absences && parseFloat(breakdown.absences) > 0) {
                deductionsList.push(['Absences', `- PHP ${parseFloat(breakdown.absences).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.late_ut && parseFloat(breakdown.late_ut) > 0) {
                deductionsList.push(['Late/Undertime', `- PHP ${parseFloat(breakdown.late_ut).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.hdmf_cont && parseFloat(breakdown.hdmf_cont) > 0) {
                deductionsList.push(['HDMF (Pag-IBIG) Contribution', `- PHP ${parseFloat(breakdown.hdmf_cont).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.hdmf_loans && parseFloat(breakdown.hdmf_loans) > 0) {
                deductionsList.push(['HDMF (Pag-IBIG) Cash Advance', `- PHP ${parseFloat(breakdown.hdmf_loans).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.hdmf_mp2 && parseFloat(breakdown.hdmf_mp2) > 0) {
                deductionsList.push(['HDMF MP2', `- PHP ${parseFloat(breakdown.hdmf_mp2).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.cash_advance && parseFloat(breakdown.cash_advance) > 0) {
                deductionsList.push(['Cash Advance', `- PHP ${parseFloat(breakdown.cash_advance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.employee_deductions && parseFloat(breakdown.employee_deductions) > 0) {
                deductionsList.push(['Employee-Specific Deductions', `- PHP ${parseFloat(breakdown.employee_deductions).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.adj_minus && parseFloat(breakdown.adj_minus) > 0) {
                deductionsList.push(['Adjustments (-)', `- PHP ${parseFloat(breakdown.adj_minus).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            
            // Add total deductions if not already itemized
            if (deductionsList.length === 0) {
                deductionsList.push(['Total Deductions', `- PHP ${deductions.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            
            // Calculate totals for display
            const totalEarnings = earnings.reduce((sum, item) => {
                const amount = parseFloat(item[1].replace('PHP ', '').replace(/,/g, ''));
                return sum + (isNaN(amount) ? 0 : amount);
            }, 0);
            
            const totalDeductions = deductionsList.reduce((sum, item) => {
                const amount = parseFloat(item[1].replace('- PHP ', '').replace(/,/g, ''));
                return sum + (isNaN(amount) ? 0 : amount);
            }, 0);
            
            // Create table with earnings and deductions side by side
            const maxRows = Math.max(earnings.length, deductionsList.length);
            const tableBody = [];
            
            for (let i = 0; i < maxRows; i++) {
                const earning = earnings[i] || ['', ''];
                const deduction = deductionsList[i] || ['', ''];
                tableBody.push([earning[0], earning[1], deduction[0], deduction[1]]);
            }
            
            // Add totals row
            tableBody.push([
                'TOTAL EARNINGS', 
                `PHP ${totalEarnings.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`,
                'TOTAL DEDUCTIONS', 
                `- PHP ${totalDeductions.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`
            ]);
            
            doc.autoTable({
                startY: 90,
                head: [['Earnings', 'Amount', 'Deductions', 'Amount']],
                body: tableBody,
                theme: 'striped',
                headStyles: { fillColor: [30, 1, 120] },
                styles: { fontSize: 9 },
                columnStyles: {
                    0: { fontStyle: 'bold' },
                    2: { fontStyle: 'bold' }
                }
            });

            const netY = doc.lastAutoTable.finalY + 20;
            doc.setFillColor(232, 232, 232);
            doc.rect(20, netY - 10, 170, 20, 'F');
            doc.setFontSize(16);
            doc.setFont(undefined, 'bold');
            doc.text('NET PAY:', 30, netY + 3);
            doc.text(`PHP ${netPay.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`, 180, netY + 3, { align: 'right' });

            const safePeriod = period.replace(/[^a-zA-Z0-9]/g, '_');
            doc.save(`Payslip_${empCode}_${safePeriod}.pdf`);
        }

        async function logout() {
            await fetch('backend/api.php?action=logout');
            window.location.href = 'login.php';
        }

        function generateDTR() {
            // Get the selected date range or use current month
            const fromDate = document.getElementById('att-from').value;
            const toDate = document.getElementById('att-to').value;
            
            let monthParam;
            
            if (fromDate && toDate) {
                // Use the from date's month
                monthParam = fromDate.substring(0, 7); // YYYY-MM
            } else {
                // Use current month
                const now = new Date();
                monthParam = now.toISOString().substring(0, 7);
            }
            
            // Open DTR in new window for printing
            const dtrUrl = `pages/shared/generate_dtr.php?month=${monthParam}`;
            window.open(dtrUrl, '_blank');
            
            showToast('DTR generated! Check the new window to print.', 'success');
        }

        async function saveEmployeeProfile() {
            const form = document.getElementById('employeeProfileForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            const btn = document.getElementById('saveEmployeeProfileBtn');

            btn.disabled = true;
            btn.innerHTML = 'Saving...';

            try {
                const response = await fetch('backend/api.php?action=update_employee_profile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    showToast('Profile updated successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            } catch (err) {
                showToast('An error occurred', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Save Personal Information';
            }
        }

        function checkPasswordStrength() {
            const password = document.querySelector('input[name="new_password"]').value;
            const reqs = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
            };

            Object.keys(reqs).forEach(req => {
                const el = document.getElementById('req-' + req);
                if (reqs[req]) {
                    el.classList.add('valid');
                } else {
                    el.classList.remove('valid');
                }
            });
        }

        loadESS();
    </script>
    <script src="js/password-toggle.js"></script>
    <script src="js/context-menu.js?v=1.0"></script>
</body>
</html>
