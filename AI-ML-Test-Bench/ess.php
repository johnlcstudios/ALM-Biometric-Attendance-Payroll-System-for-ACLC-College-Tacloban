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
$stmt = $pdo->prepare("SELECT e.*, u.username, u.email as user_email, c.name as company_name 
                     FROM employees e 
                     JOIN users u ON e.user_id = u.id 
                     JOIN companies c ON e.company_id = c.id 
                     WHERE u.id = ?");
$stmt->execute([$user_id]);
$emp = $stmt->fetch();

$full_name = $emp['full_name'] ?? $_SESSION['full_name'] ?? 'Employee';
$emp_id = $emp['employee_id'] ?? '---';
$company_name = $emp['company_name'] ?? $_SESSION['company_name'] ?? 'ALM Tech Solutions';
$position = $emp['position'] ?? 'Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Portal - <?php echo $company_name; ?></title>
    
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
        
        /* Modal for Payslip */
        #payslipModal .modal-content { max-width: 800px; padding: 3rem; }
        .payslip-header { text-align: center; margin-bottom: 2rem; border-bottom: 2px solid var(--primary-color); padding-bottom: 1rem; }
        .payslip-body { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .payslip-section h4 { border-bottom: 1px solid #eee; padding-bottom: 0.5rem; margin-bottom: 1rem; color: var(--primary-color); }
        .payslip-footer { margin-top: 2rem; text-align: center; font-style: italic; color: var(--text-muted); font-size: 0.8rem; }
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
                            <span class="name"><?php echo $full_name; ?></span>
                            <span class="role"><?php echo $position; ?></span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Page -->
            <section id="dashboard" class="page active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-user-clock"></i></div>
                        <div class="stat-info">
                            <h3>Days Present</h3>
                            <div class="stat-value" id="stat-present">0</div>
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
                        <div class="stat-icon orange"><i class="fas fa-wallet"></i></div>
                        <div class="stat-info">
                            <h3>Last Net Pay</h3>
                            <div class="stat-value" id="stat-net-pay">₱0.00</div>
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
                    <button class="tab-link" onclick="switchRequestTab('loan', this)">Loan Requests</button>
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
                        <div class="request-form-card">
                            <h3>Apply for Loan</h3>
                            <form id="loan-form" onsubmit="submitRequest(event, 'loan')">
                                <div class="form-group-custom">
                                    <label>Amount (PHP)</label>
                                    <input type="number" name="amount" class="form-control-large-gray" placeholder="0.00" step="0.01" required>
                                </div>
                                <div class="form-group-custom">
                                    <label>Reason</label>
                                    <textarea name="reason" class="form-control-large-gray" rows="4" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-dark-purple btn-full">Submit Request</button>
                            </form>
                        </div>
                        <div class="request-history-card">
                            <h3>Loan History</h3>
                            <div class="modern-table-wrapper" style="box-shadow: none;">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
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
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($full_name); ?>&size=150&background=random" alt="Avatar" style="width:120px; border-radius:50%; margin-bottom:1rem; border: 4px solid #eee;">
                        <h2 style="margin-bottom:0.2rem;"><?php echo $full_name; ?></h2>
                        <p class="text-muted" style="margin-bottom:1.5rem;"><?php echo $position; ?></p>
                        
                        <div class="info-row"><span class="info-label">Employee ID</span> <span class="info-value"><?php echo $emp_id; ?></span></div>
                        <div class="info-row"><span class="info-label">Department</span> <span class="info-value"><?php echo $emp['department']; ?></span></div>
                        <div class="info-row"><span class="info-label">Status</span> <span class="status-tag status-approved"><?php echo $emp['status']; ?></span></div>
                    </div>
                    
                    <div class="profile-details-card">
                        <div class="tab-nav">
                            <button class="tab-link active" onclick="switchProfileTab('info', this)">Personal Information</button>
                            <button class="tab-link" onclick="switchProfileTab('security', this)">Security Settings</button>
                        </div>
                        
                        <div id="profile-info" class="profile-tab-section active">
                            <div class="form-row-custom">
                                <div class="form-group-custom">
                                    <label>Email Address</label>
                                    <input type="text" value="<?php echo $emp['email']; ?>" class="form-control-large-gray" readonly>
                                </div>
                                <div class="form-group-custom">
                                    <label>Date of Birth</label>
                                    <input type="text" value="<?php echo $emp['dob'] ?? 'N/A'; ?>" class="form-control-large-gray" readonly>
                                </div>
                            </div>
                            <div class="form-row-custom">
                                <div class="form-group-custom">
                                    <label>SSS No.</label>
                                    <input type="text" value="<?php echo $emp['sss'] ?: 'N/A'; ?>" class="form-control-large-gray" readonly>
                                </div>
                                <div class="form-group-custom">
                                    <label>PhilHealth No.</label>
                                    <input type="text" value="<?php echo $emp['philhealth'] ?: 'N/A'; ?>" class="form-control-large-gray" readonly>
                                </div>
                            </div>
                            <div class="form-row-custom">
                                <div class="form-group-custom">
                                    <label>TIN</label>
                                    <input type="text" value="<?php echo $emp['tin'] ?: 'N/A'; ?>" class="form-control-large-gray" readonly>
                                </div>
                                <div class="form-group-custom">
                                    <label>Pag-IBIG No.</label>
                                    <input type="text" value="<?php echo $emp['pagibig'] ?: 'N/A'; ?>" class="form-control-large-gray" readonly>
                                </div>
                            </div>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 1rem;">
                                <i class="fas fa-info-circle"></i> To update your personal information, please contact the HR Department.
                            </p>
                        </div>
                        
                        <div id="profile-security" class="profile-tab-section" style="display: none;">
                            <form onsubmit="changePassword(event)">
                                <div class="form-group-custom">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" class="form-control-large-gray" required>
                                </div>
                                <div class="form-group-custom">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" class="form-control-large-gray" required>
                                </div>
                                <div class="form-group-custom">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control-large-gray" required>
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
    <div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 10000;"></div>

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
            
            document.getElementById(pageId).classList.add('active');
            btn.classList.add('active');
            
            const title = btn.innerText.trim();
            document.getElementById('current-page-title').innerText = title;
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
        }

        function renderAttendance() {
            const att = essData.attendance || [];
            document.getElementById('attendance-history-body').innerHTML = att.map(a => `
                <tr>
                    <td>${a.log_date}</td>
                    <td>${a.check_in || '---'}</td>
                    <td>${a.lunch_out || '---'}</td>
                    <td>${a.lunch_in || '---'}</td>
                    <td>${a.check_out || '---'}</td>
                    <td><span class="late-tag ${a.status === 'Late' ? 'text-danger' : 'text-success'}">${a.status}</span></td>
                    <td>${a.late_minutes || 0}</td>
                </tr>
            `).join('') || '<tr><td colspan="7" class="text-center">No logs found</td></tr>';
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
                </tr>
            `).join('') || '<tr><td colspan="3" class="text-center">No loan requests</td></tr>';

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
            doc.text(essData.profile.company_name, 105, 30, { align: 'center' });

            // Employee Info
            doc.setTextColor(0);
            doc.setFontSize(12);
            doc.text('EMPLOYEE DETAILS', 20, 55);
            doc.line(20, 57, 190, 57);
            
            doc.setFontSize(10);
            doc.text(`Name: ${p.full_name}`, 20, 65);
            doc.text(`ID: ${p.emp_code}`, 20, 72);
            doc.text(`Position: ${p.position}`, 20, 79);
            doc.text(`Period: ${p.period}`, 130, 65);
            doc.text(`Date: ${new Date(p.created_at).toLocaleDateString()}`, 130, 72);

            // Financials
            doc.autoTable({
                startY: 90,
                head: [['Earnings', 'Amount', 'Deductions', 'Amount']],
                body: [
                    ['Basic Pay', `PHP ${parseFloat(p.basic_pay).toLocaleString()}`, 'Total Deductions', `PHP ${parseFloat(p.deductions).toLocaleString()}`],
                    ['', '', '', ''],
                    ['TOTAL EARNINGS', `PHP ${parseFloat(p.basic_pay).toLocaleString()}`, 'TOTAL DEDUCTIONS', `PHP ${parseFloat(p.deductions).toLocaleString()}`]
                ],
                theme: 'striped',
                headStyles: { fillColor: [30, 1, 120] }
            });

            const netY = doc.lastAutoTable.finalY + 20;
            doc.setFillColor(232, 232, 232);
            doc.rect(20, netY - 10, 170, 20, 'F');
            doc.setFontSize(16);
            doc.setFont(undefined, 'bold');
            doc.text('NET PAY:', 30, netY + 3);
            doc.text(`PHP ${parseFloat(p.net_pay).toLocaleString()}`, 180, netY + 3, { align: 'right' });

            doc.save(`Payslip_${p.emp_code}_${p.period.replace(/[\/\s]/g, '_')}.pdf`);
        }

        async function logout() {
            await fetch('backend/api.php?action=logout');
            window.location.href = 'login.php';
        }

        loadESS();
    </script>
</body>
</html>
