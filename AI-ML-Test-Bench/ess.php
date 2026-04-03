<?php
require_once 'backend/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect Admin, HR, and Payroll roles
$session_role = trim($_SESSION['role'] ?? '');
if (in_array($session_role, ['Payroll', 'Payroll Officer'])) {
    header('Location: Payroll-Officer.php');
    exit;
}
if (in_array($session_role, ['Admin', 'HR'])) {
    header('Location: index.php');
    exit;
}

// Fetch employee profile on server-side
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT e.*, u.username FROM employees e JOIN users u ON e.user_id = u.id WHERE u.id = ?");
$stmt->execute([$user_id]);
$emp = $stmt->fetch();
$full_name = $emp['full_name'] ?? $_SESSION['full_name'] ?? 'Employee';
$emp_id = $emp['employee_id'] ?? '---';
$company_name = $_SESSION['company_name'] ?? 'ALM Tech Solutions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal - <?php echo $company_name; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary-color: #3b4fc9;
            --secondary-color: #6c757d;
            --success-color: #27ae60;
            --danger-color: #c0392b;
            --warning-color: #f39c12;
            --info-color: #2980b9;
            --light-bg: #f8f9fa;
            --sidebar-width: 260px;
            --header-height: 70px;
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .page { display: none; }
        .page.active { display: block; }

        .ess-dashboard { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .ess-card { background: white; border-radius: var(--border-radius); padding: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #edf2f7; transition: var(--transition); }
        .ess-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
        .ess-card-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
        .ess-card-title { font-size: 0.9rem; font-weight: 600; color: var(--secondary-color); margin-bottom: 0.5rem; }
        .ess-card-value { font-size: 1.8rem; font-weight: 700; color: #2d3748; }
        .ess-card-footer { margin-top: 1rem; font-size: 0.85rem; color: var(--secondary-color); }

        .table-container { background: white; border-radius: var(--border-radius); padding: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #edf2f7; }
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .table-header h3 { font-size: 1.2rem; font-weight: 700; margin: 0; }

        .badge { padding: 0.4rem 0.8rem; border-radius: 50px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
        .badge-on-time { background: #d4edda; color: #155724; }
        .badge-late { background: #fff3cd; color: #856404; }
        .badge-absent { background: #f8d7da; color: #721c24; }

        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #4a5568; }
        .form-control { width: 100%; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-family: inherit; font-size: 0.95rem; transition: var(--transition); }
        .form-control:focus { border-color: var(--primary-color); outline: none; box-shadow: 0 0 0 3px rgba(59, 79, 201, 0.1); }

        .profile-grid { display: grid; grid-template-columns: 280px 1fr; gap: 2rem; }
        .profile-sidebar { background: white; border-radius: var(--border-radius); padding: 2rem; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .profile-avatar { width: 150px; height: 150px; border-radius: 50%; background: #edf2f7; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: #cbd5e0; margin: 0 auto 1.5rem; border: 4px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .profile-name { font-size: 1.4rem; font-weight: 700; color: #2d3748; margin-bottom: 0.5rem; }
        .profile-role { color: var(--secondary-color); font-size: 0.95rem; margin-bottom: 1.5rem; }
        .profile-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; text-align: center; padding-top: 1.5rem; border-top: 1px solid #edf2f7; }
        .stat-item .label { display: block; font-size: 0.75rem; font-weight: 700; color: var(--secondary-color); text-transform: uppercase; margin-bottom: 0.25rem; }
        .stat-item .value { font-size: 1.1rem; font-weight: 700; color: #2d3748; }

        .profile-main { background: white; border-radius: var(--border-radius); padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .profile-section { margin-bottom: 2.5rem; }
        .profile-section-title { font-size: 1.1rem; font-weight: 700; color: #2d3748; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 2px solid #edf2f7; display: flex; align-items: center; gap: 0.75rem; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; backdrop-filter: blur(4px); }
        .modal-content { background: white; width: 500px; max-width: 90%; margin: 50px auto; border-radius: var(--border-radius); overflow: hidden; box-shadow: 0 20px 25px rgba(0,0,0,0.2); animation: modalSlideDown 0.3s ease-out; }
        @keyframes modalSlideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { padding: 1.25rem 1.5rem; background: var(--light-bg); border-bottom: 1px solid #edf2f7; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { padding: 1.25rem 1.5rem; background: var(--light-bg); border-top: 1px solid #edf2f7; display: flex; justify-content: flex-end; gap: 1rem; }

        .empty-state { text-align: center; padding: 3rem 1rem; color: var(--secondary-color); }
        .empty-state i { font-size: 3rem; color: #cbd5e0; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="assets/logo.jpg" alt="Logo" class="sidebar-logo">
                    <span>Employee Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <button class="nav-btn active" onclick="showPage('dashboard')">
                    <i class="fas fa-th-large"></i> <span>Dashboard</span>
                </button>
                <button class="nav-btn" onclick="showPage('attendance')">
                    <i class="fas fa-calendar-alt"></i> <span>Attendance</span>
                </button>
                <button class="nav-btn" onclick="showPage('payroll')">
                    <i class="fas fa-money-check-alt"></i> <span>Payroll</span>
                </button>
                <button class="nav-btn" onclick="showPage('leave')">
                    <i class="fas fa-plane"></i> <span>Leave Requests</span>
                </button>
                <button class="nav-btn" onclick="showPage('loans')">
                    <i class="fas fa-hand-holding-usd"></i> <span>Loan Requests</span>
                </button>
                <button class="nav-btn" onclick="showPage('resignation')">
                    <i class="fas fa-user-times"></i> <span>Resignation</span>
                </button>
                <button class="nav-btn" onclick="showPage('profile')">
                    <i class="fas fa-user-circle"></i> <span>My Profile</span>
                </button>
                <div class="nav-spacer"></div>
                <button class="nav-btn text-danger" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </button>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <h2 id="current-page-title">Dashboard Overview</h2>
                </div>
                <div class="header-right">
                    <div class="profile-summary">
                        <div class="profile-text">
                            <span class="name"><?php echo $full_name; ?></span>
                            <span class="role"><?php echo $emp['position']; ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-pages" id="content-pages">
                <!-- Dashboard Page -->
                <div class="page active" id="dashboard">
                    <div class="ess-dashboard">
                        <div class="ess-card">
                            <div class="ess-card-icon" style="background: rgba(39, 174, 96, 0.1); color: var(--success-color);">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="ess-card-title">Attendance (Current Month)</div>
                            <div class="ess-card-value" id="dash-attendance-count">0 Logs</div>
                            <div class="ess-card-footer" id="dash-attendance-last">Last: ---</div>
                        </div>
                        <div class="ess-card">
                            <div class="ess-card-icon" style="background: rgba(59, 79, 201, 0.1); color: var(--primary-color);">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="ess-card-title">Latest Net Payout</div>
                            <div class="ess-card-value" id="dash-last-payout">₱0.00</div>
                            <div class="ess-card-footer" id="dash-last-period">Period: ---</div>
                        </div>
                        <div class="ess-card">
                            <div class="ess-card-icon" style="background: rgba(243, 156, 18, 0.1); color: var(--warning-color);">
                                <i class="fas fa-plane-departure"></i>
                            </div>
                            <div class="ess-card-title">Leave Balance</div>
                            <div class="ess-card-value" id="dash-leave-balance"><?php echo $emp['leave_balance']; ?> Days</div>
                            <div class="ess-card-footer">Annual Allocation</div>
                        </div>
                    </div>

                    <div class="table-container">
                        <div class="table-header">
                            <h3>Recent Attendance</h3>
                            <button class="btn btn-outline-primary btn-sm" onclick="showPage('attendance')">View All</button>
                        </div>
                        <table class="payroll-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Lunch Out</th>
                                    <th>Lunch In</th>
                                    <th>Check Out</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="dash-attendance-body">
                                <!-- Dynamic -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Attendance Page -->
                <div class="page" id="attendance">
                    <div class="table-container">
                        <div class="table-header">
                            <h3>Full Attendance History</h3>
                            <div class="table-actions">
                                <input type="month" id="att-month-filter" class="form-control" onchange="renderAttendance()">
                            </div>
                        </div>
                        <table class="payroll-table">
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
                            <tbody id="att-table-body">
                                <!-- Dynamic -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payroll Page -->
                <div class="page" id="payroll">
                    <div class="table-container">
                        <div class="table-header">
                            <h3>Payroll History</h3>
                        </div>
                        <table class="payroll-table">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Basic Pay</th>
                                    <th>Deductions</th>
                                    <th>Net Pay</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="payroll-table-body">
                                <!-- Dynamic -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Leave Page -->
                <div class="page" id="leave">
                    <div class="table-container">
                        <div class="table-header">
                            <h3>My Leave Requests</h3>
                            <button class="btn btn-primary btn-sm" onclick="openModal('leaveModal')">
                                <i class="fas fa-plus"></i> Apply for Leave
                            </button>
                        </div>
                        <table class="payroll-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Duration</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="leave-table-body">
                                <!-- Dynamic -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Loans Page -->
                <div class="page" id="loans">
                    <div class="table-container">
                        <div class="table-header">
                            <h3>My Loan Requests</h3>
                            <button class="btn btn-primary btn-sm" onclick="openModal('loanModal')">
                                <i class="fas fa-plus"></i> Apply for Loan
                            </button>
                        </div>
                        <table class="payroll-table">
                            <thead>
                                <tr>
                                    <th>Amount</th>
                                    <th>Reason</th>
                                    <th>Requested At</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="loan-table-body">
                                <!-- Dynamic -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Resignation Page -->
                <div class="page" id="resignation">
                    <div class="table-container">
                        <div class="table-header">
                            <h3>Resignation Requests</h3>
                            <button class="btn btn-danger btn-sm" onclick="openModal('resignationModal')">
                                <i class="fas fa-user-times"></i> Submit Resignation
                            </button>
                        </div>
                        <table class="payroll-table">
                            <thead>
                                <tr>
                                    <th>Effective Date</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Requested At</th>
                                </tr>
                            </thead>
                            <tbody id="resignation-table-body">
                                <!-- Dynamic -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Profile Page -->
                <div class="page" id="profile">
                    <div class="profile-grid">
                        <div class="profile-sidebar">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="profile-name"><?php echo $full_name; ?></div>
                            <div class="profile-role"><?php echo $emp['position']; ?></div>
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <span class="label">Emp ID</span>
                                    <span class="value"><?php echo $emp_id; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="label">Dept</span>
                                    <span class="value"><?php echo $emp['department']; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="profile-main">
                            <form id="profileForm" onsubmit="updateProfile(event)">
                                <div class="profile-section">
                                    <div class="profile-section-title">
                                        <i class="fas fa-info-circle"></i> Basic Information
                                    </div>
                                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                        <div class="form-group">
                                            <label>Full Name</label>
                                            <input type="text" class="form-control" value="<?php echo $full_name; ?>" disabled>
                                        </div>
                                        <div class="form-group">
                                            <label>Email Address</label>
                                            <input type="email" name="email" class="form-control" value="<?php echo $emp['email']; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Position</label>
                                            <input type="text" class="form-control" value="<?php echo $emp['position']; ?>" disabled>
                                        </div>
                                        <div class="form-group">
                                            <label>Department</label>
                                            <input type="text" class="form-control" value="<?php echo $emp['department']; ?>" disabled>
                                        </div>
                                    </div>
                                </div>

                                <div class="profile-section">
                                    <div class="profile-section-title">
                                        <i class="fas fa-id-card"></i> Government Identifiers
                                    </div>
                                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                        <div class="form-group">
                                            <label>SSS Number</label>
                                            <input type="text" name="sss" class="form-control" value="<?php echo $emp['sss']; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>PhilHealth ID</label>
                                            <input type="text" name="philhealth" class="form-control" value="<?php echo $emp['philhealth']; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>TIN Number</label>
                                            <input type="text" name="tin" class="form-control" value="<?php echo $emp['tin']; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Pag-IBIG MID</label>
                                            <input type="text" name="pagibig" class="form-control" value="<?php echo $emp['pagibig']; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div style="text-align: right;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Profile Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="leaveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Apply for Leave</h3>
                <span class="close" onclick="closeModal('leaveModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="leaveForm" onsubmit="applyLeave(event)">
                    <div class="form-group">
                        <label>Leave Type</label>
                        <select name="type" class="form-control" required>
                            <option value="Sick Leave">Sick Leave</option>
                            <option value="Vacation Leave">Vacation Leave</option>
                            <option value="Emergency Leave">Emergency Leave</option>
                            <option value="Maternity/Paternity Leave">Maternity/Paternity Leave</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Duration (e.g. 1 day, 2026-05-10 to 2026-05-12)</label>
                        <input type="text" name="duration" class="form-control" placeholder="Specify dates or number of days" required>
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div style="text-align: right;">
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="loanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Apply for Loan</h3>
                <span class="close" onclick="closeModal('loanModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="loanForm" onsubmit="applyLoan(event)">
                    <div class="form-group">
                        <label>Loan Amount (₱)</label>
                        <input type="number" name="amount" class="form-control" min="100" step="100" required>
                    </div>
                    <div class="form-group">
                        <label>Reason for Loan</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div style="text-align: right;">
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="resignationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Submit Resignation</h3>
                <span class="close" onclick="closeModal('resignationModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div style="background: #fff5f5; border-left: 4px solid #c0392b; padding: 1rem; margin-bottom: 1.5rem; color: #742a2a; font-size: 0.9rem;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Resignation requests are subject to management approval and company policy.
                </div>
                <form id="resignationForm" onsubmit="submitResignation(event)">
                    <div class="form-group">
                        <label>Effective Date</label>
                        <input type="date" name="effective_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Reason for Resignation</label>
                        <textarea name="reason" class="form-control" rows="4" placeholder="Please provide a detailed reason for your resignation..." required></textarea>
                    </div>
                    <div style="text-align: right;">
                        <button type="submit" class="btn btn-danger">Submit Resignation Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Detailed Payslip Modal -->
    <div id="payslipModal" class="modal">
        <div class="modal-content large" style="width: 800px;">
            <div class="modal-header">
                <h3>Payslip Details</h3>
                <span class="close" onclick="closeModal('payslipModal')">&times;</span>
            </div>
            <div class="modal-body" id="payslipContent">
                <!-- Dynamic -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="downloadPdfBtn">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
            </div>
        </div>
    </div>

    <script>
        let essData = null;

        async function loadESS() {
            try {
                const response = await fetch('backend/api.php?action=get_ess_data');
                essData = await response.json();
                if (!essData.profile) throw new Error("Unauthorized");

                renderDashboard();
                renderAttendance();
                renderPayroll();
                renderLeave();
                renderLoans();
                renderResignation();
            } catch (error) {
                console.error("Failed to load ESS data:", error);
                window.location.href = 'login.php';
            }
        }

        function showPage(pageId) {
            document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
            
            document.getElementById(pageId).classList.add('active');
            const btn = document.querySelector(`.nav-btn[onclick*="${pageId}"]`);
            if (btn) btn.classList.add('active');

            const titles = {
                'dashboard': 'Dashboard Overview',
                'attendance': 'Attendance History',
                'payroll': 'My Payroll History',
                'leave': 'Leave Management',
                'loans': 'Loan Management',
                'profile': 'Account Profile'
            };
            document.getElementById('current-page-title').innerText = titles[pageId];
        }

        function renderResignation() {
            const resignation = essData.resignation || [];
            const tbody = document.getElementById('resignation-table-body');
            tbody.innerHTML = resignation.map(r => `
                <tr>
                    <td><strong>${new Date(r.effective_date).toLocaleDateString()}</strong></td>
                    <td>${r.reason}</td>
                    <td><span class="badge badge-${r.status.toLowerCase()}">${r.status}</span></td>
                    <td>${new Date(r.requested_at).toLocaleDateString()}</td>
                </tr>
            `).join('') || '<tr><td colspan="4" class="empty-state">No resignation requests</td></tr>';
        }

        async function submitResignation(e) {
            e.preventDefault();
            if (!confirm("Are you sure you want to submit your resignation request? This action is serious and will be reviewed by management.")) return;
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            const res = await fetch('backend/api.php?action=request_resignation', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                alert("Resignation request submitted successfully.");
                closeModal('resignationModal');
                loadESS();
            } else {
                alert("Error: " + result.message);
            }
        }

        function renderDashboard() {
            const att = essData.attendance || [];
            const pay = essData.payroll || [];
            
            document.getElementById('dash-attendance-count').innerText = `${att.length} Logs`;
            document.getElementById('dash-attendance-last').innerText = att.length > 0 ? `Last: ${att[0].log_date}` : 'Last: ---';

            const lastPay = pay[0] || { net_pay: 0, period: '---' };
            document.getElementById('dash-last-payout').innerText = `₱${parseFloat(lastPay.net_pay).toLocaleString(undefined, {minimumFractionDigits: 2})}`;
            document.getElementById('dash-last-period').innerText = `Period: ${lastPay.period}`;

            const tbody = document.getElementById('dash-attendance-body');
            tbody.innerHTML = att.slice(0, 5).map(a => `
                <tr>
                    <td>${a.log_date}</td>
                    <td>${a.check_in || '---'}</td>
                    <td>${a.lunch_out || '---'}</td>
                    <td>${a.lunch_in || '---'}</td>
                    <td>${a.check_out || '---'}</td>
                    <td><span class="badge badge-${(a.status || 'On-Time').toLowerCase().replace(' ', '-')}">${a.status || 'On-Time'}</span></td>
                </tr>
            `).join('') || '<tr><td colspan="6" class="empty-state">No attendance records</td></tr>';
        }

        function renderAttendance() {
            const att = essData.attendance || [];
            const tbody = document.getElementById('att-table-body');
            tbody.innerHTML = att.map(a => `
                <tr>
                    <td>${a.log_date}</td>
                    <td>${a.check_in || '---'}</td>
                    <td>${a.lunch_out || '---'}</td>
                    <td>${a.lunch_in || '---'}</td>
                    <td>${a.check_out || '---'}</td>
                    <td><span class="badge badge-${(a.status || 'On-Time').toLowerCase().replace(' ', '-')}">${a.status || 'On-Time'}</span></td>
                    <td>${a.late_minutes || 0}</td>
                </tr>
            `).join('') || '<tr><td colspan="7" class="empty-state">No history found</td></tr>';
        }

        function renderPayroll() {
            const pay = essData.payroll || [];
            const tbody = document.getElementById('payroll-table-body');
            tbody.innerHTML = pay.map(p => `
                <tr>
                    <td><strong>${p.period}</strong></td>
                    <td>₱${parseFloat(p.basic_pay).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td>₱${parseFloat(p.deductions).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td><strong>₱${parseFloat(p.net_pay).toLocaleString(undefined, {minimumFractionDigits: 2})}</strong></td>
                    <td><span class="badge badge-approved">${p.status}</span></td>
                    <td>
                        <button class="btn btn-outline-primary btn-sm" onclick="viewPayslip(${p.id})">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </td>
                </tr>
            `).join('') || '<tr><td colspan="6" class="empty-state">No payroll records</td></tr>';
        }

        function renderLeave() {
            const leave = essData.leave || [];
            const tbody = document.getElementById('leave-table-body');
            tbody.innerHTML = leave.map(l => `
                <tr>
                    <td>${l.type}</td>
                    <td>${l.duration}</td>
                    <td>${l.reason}</td>
                    <td><span class="badge badge-${l.status.toLowerCase()}">${l.status}</span></td>
                </tr>
            `).join('') || '<tr><td colspan="4" class="empty-state">No requests yet</td></tr>';
        }

        function renderLoans() {
            const loans = essData.loans || [];
            const tbody = document.getElementById('loan-table-body');
            tbody.innerHTML = loans.map(l => `
                <tr>
                    <td><strong>₱${parseFloat(l.amount).toLocaleString()}</strong></td>
                    <td>${l.reason}</td>
                    <td>${new Date(l.requested_at).toLocaleDateString()}</td>
                    <td><span class="badge badge-${l.status.toLowerCase()}">${l.status}</span></td>
                </tr>
            `).join('') || '<tr><td colspan="4" class="empty-state">No loan applications</td></tr>';
        }

        async function applyLeave(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            const res = await fetch('backend/api.php?action=apply_leave', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                alert("Leave request submitted successfully!");
                closeModal('leaveModal');
                loadESS();
            }
        }

        async function applyLoan(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            const res = await fetch('backend/api.php?action=apply_loan', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                alert("Loan request submitted successfully!");
                closeModal('loanModal');
                loadESS();
            }
        }

        async function updateProfile(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            const res = await fetch('backend/api.php?action=update_ess_profile', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                alert("Profile updated successfully!");
                loadESS();
            }
        }

        function viewPayslip(id) {
            const p = essData.payroll.find(x => x.id == id);
            if (!p) return;
            
            const breakdown = JSON.parse(p.breakdown || '{}');
            const content = document.getElementById('payslipContent');
            
            content.innerHTML = `
                <div style="border: 2px solid #edf2f7; padding: 2rem; border-radius: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 2px solid #edf2f7; padding-bottom: 1rem;">
                        <div>
                            <h2 style="margin: 0; color: var(--primary-color);">PAYSLIP</h2>
                            <p style="margin: 0.25rem 0; color: var(--secondary-color); font-weight: 600;">${p.period}</p>
                        </div>
                        <div style="text-align: right;">
                            <h3 style="margin: 0;">${essData.profile.full_name}</h3>
                            <p style="margin: 0.25rem 0; color: var(--secondary-color);">${essData.profile.employee_id}</p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <h4 style="border-bottom: 1px solid #edf2f7; padding-bottom: 0.5rem;">EARNINGS</h4>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Basic Pay</span>
                                <span>₱${parseFloat(p.basic_pay).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                            </div>
                            ${Object.entries(breakdown).filter(([k, v]) => !['absences', 'late_ut', 'hdmf_cont', 'hdmf_loans', 'hdmf_mp2', 'total_deduction', 'days_present', 'absent_days', 'late_minutes'].includes(k)).map(([k, v]) => `
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="text-transform: capitalize;">${k.replace('_', ' ')}</span>
                                    <span>₱${parseFloat(v || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                                </div>
                            `).join('')}
                        </div>
                        <div>
                            <h4 style="border-bottom: 1px solid #edf2f7; padding-bottom: 0.5rem;">DEDUCTIONS</h4>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: var(--danger-color);">
                                <span>Late/UT</span>
                                <span>-₱${parseFloat(breakdown.late_ut || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: var(--danger-color);">
                                <span>Absences</span>
                                <span>-₱${parseFloat(breakdown.absences || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: var(--danger-color);">
                                <span>Gov't Contributions</span>
                                <span>-₱${parseFloat(breakdown.hdmf_cont || 0).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: var(--danger-color); font-weight: 700; border-top: 1px solid #edf2f7; padding-top: 0.5rem;">
                                <span>Total Deductions</span>
                                <span>-₱${parseFloat(p.deductions).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 2rem; background: var(--light-bg); padding: 1.5rem; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 1.2rem; font-weight: 700;">NET PAYOUT</span>
                        <span style="font-size: 1.8rem; font-weight: 800; color: var(--success-color);">₱${parseFloat(p.net_pay).toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                    </div>
                </div>
            `;
            
            document.getElementById('downloadPdfBtn').onclick = () => exportPayslipPDF(p);
            openModal('payslipModal');
        }

        function exportPayslipPDF(p) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const profile = essData.profile;
            const breakdown = JSON.parse(p.breakdown || '{}');

            doc.setFontSize(22);
            doc.setTextColor(59, 79, 201);
            doc.text('OFFICIAL PAYSLIP', 105, 25, { align: 'center' });

            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text(document.title, 105, 32, { align: 'center' });

            doc.setDrawColor(200);
            doc.line(20, 40, 190, 40);

            doc.setFontSize(12);
            doc.setTextColor(0);
            doc.text(`Employee: ${profile.full_name}`, 20, 50);
            doc.text(`ID: ${profile.employee_id}`, 20, 57);
            doc.text(`Period: ${p.period}`, 190, 50, { align: 'right' });
            doc.text(`Role: ${profile.position}`, 190, 57, { align: 'right' });

            doc.autoTable({
                startY: 70,
                head: [['Description', 'Earnings', 'Deductions']],
                body: [
                    ['Basic Salary', `PHP ${parseFloat(p.basic_pay).toLocaleString()}`, ''],
                    ['Late/Undertime', '', `PHP ${parseFloat(breakdown.late_ut || 0).toLocaleString()}`],
                    ['Absences', '', `PHP ${parseFloat(breakdown.absences || 0).toLocaleString()}`],
                    ['Gov\'t Contributions', '', `PHP ${parseFloat(breakdown.hdmf_cont || 0).toLocaleString()}`],
                ],
                theme: 'striped',
                headStyles: { fillColor: [59, 79, 201] }
            });

            const finalY = doc.lastAutoTable.finalY + 10;
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text(`TOTAL NET PAYOUT: PHP ${parseFloat(p.net_pay).toLocaleString()}`, 190, finalY, { align: 'right' });

            doc.save(`Payslip_${profile.employee_id}_${p.period.replace('/', '-')}.pdf`);
        }

        function openModal(id) { document.getElementById(id).style.display = 'block'; }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
        async function logout() { await fetch('backend/api.php?action=logout'); window.location.href = 'login.php'; }

        loadESS();
    </script>
</body>
</html>