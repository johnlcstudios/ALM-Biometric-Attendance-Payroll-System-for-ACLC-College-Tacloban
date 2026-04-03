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
$stmt = $pdo->prepare("SELECT e.*, u.username, u.email as user_email FROM employees e JOIN users u ON e.user_id = u.id WHERE u.id = ?");
$stmt->execute([$user_id]);
$emp = $stmt->fetch();
$full_name = $emp['full_name'] ?? $_SESSION['full_name'] ?? 'Employee';
$emp_id = $emp['employee_id'] ?? '---';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - ALM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <style>
        :root {
            --primary: #4e73df;
            --secondary: #858796;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --dark: #5a5c69;
            --light: #f8f9fc;
            --sidebar-width: 260px;
        }

        body { font-family: 'Nunito', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f4f6f9; margin: 0; display: flex; height: 100vh; overflow: hidden; }

        /* Sidebar */
        .sidebar { width: var(--sidebar-width); background: #4e73df; background-image: linear-gradient(180deg, #4e73df 10%, #224abe 100%); color: white; display: flex; flex-direction: column; transition: all 0.3s; z-index: 100; }
        .sidebar-brand { padding: 1.5rem 1rem; text-align: center; font-size: 1.2rem; font-weight: 800; letter-spacing: 0.05rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-nav { flex: 1; padding: 1rem 0; overflow-y: auto; }
        .nav-item { padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.75rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.2s; cursor: pointer; }
        .nav-item:hover { color: #fff; background: rgba(255,255,255,0.1); }
        .nav-item.active { color: #fff; font-weight: 700; background: rgba(255,255,255,0.15); border-left: 4px solid #fff; }
        .nav-item i { width: 20px; text-align: center; }

        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .topbar { height: 70px; background: #fff; display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); z-index: 50; }
        .topbar-title { font-size: 1.2rem; font-weight: 700; color: #5a5c69; }
        .user-info { display: flex; align-items: center; gap: 1rem; color: #858796; font-size: 0.9rem; }
        .user-avatar { width: 35px; height: 35px; border-radius: 50%; background: #eef2f7; display: flex; align-items: center; justify-content: center; color: #4e73df; border: 1px solid #d1d3e2; }

        .content-area { flex: 1; padding: 2rem; overflow-y: auto; }
        .tab-content { display: none; animation: fadeIn 0.3s ease-in-out; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Dashboard Cards */
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: #fff; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); border-left: 4px solid #4e73df; }
        .stat-card.success { border-left-color: #1cc88a; }
        .stat-card.info { border-left-color: #36b9cc; }
        .stat-card.warning { border-left-color: #f6c23e; }
        .stat-title { font-size: 0.7rem; font-weight: 700; color: #4e73df; text-transform: uppercase; margin-bottom: 0.5rem; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: #5a5c69; }
        .stat-sub { font-size: 0.8rem; color: #858796; margin-top: 0.5rem; }

        /* Tables */
        .card { background: #fff; border-radius: 0.5rem; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); margin-bottom: 2rem; }
        .card-header { padding: 1rem 1.5rem; background: #f8f9fc; border-bottom: 1px solid #e3e6f0; display: flex; justify-content: space-between; align-items: center; border-radius: 0.5rem 0.5rem 0 0; }
        .card-title { margin: 0; font-size: 1rem; font-weight: 700; color: #4e73df; }
        .card-body { padding: 1.5rem; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { padding: 0.75rem; text-align: left; border-bottom: 2px solid #e3e6f0; font-size: 0.8rem; font-weight: 700; color: #4e73df; text-transform: uppercase; }
        .table td { padding: 0.75rem; border-bottom: 1px solid #e3e6f0; color: #5a5c69; font-size: 0.9rem; }
        .table tr:hover { background: #f8f9fc; }

        /* Badges */
        .badge { padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 700; }
        .badge-success { background: #1cc88a; color: #fff; }
        .badge-warning { background: #f6c23e; color: #fff; }
        .badge-danger { background: #e74a3b; color: #fff; }
        .badge-info { background: #36b9cc; color: #fff; }

        /* Buttons */
        .btn { padding: 0.5rem 1rem; border-radius: 0.35rem; border: none; font-size: 0.85rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s; }
        .btn-primary { background: #4e73df; color: #fff; }
        .btn-primary:hover { background: #2e59d9; }
        .btn-success { background: #1cc88a; color: #fff; }
        .btn-danger { background: #e74a3b; color: #fff; }
        .btn-outline { background: transparent; border: 1px solid #d1d3e2; color: #6e707e; }
        .btn-outline:hover { background: #eaecf4; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }

        /* Forms */
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 700; color: #5a5c69; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 0.75rem; border: 1px solid #d1d3e2; border-radius: 0.35rem; font-size: 0.9rem; transition: border-color 0.2s; }
        .form-control:focus { border-color: #4e73df; outline: none; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }

        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: #fff; border-radius: 0.5rem; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; }

        /* Profile Layout */
        .profile-header { display: flex; gap: 2rem; align-items: flex-start; margin-bottom: 2rem; }
        .profile-img-container { width: 150px; height: 150px; border-radius: 0.5rem; background: #eef2f7; display: flex; align-items: center; justify-content: center; font-size: 4rem; color: #d1d3e2; border: 1px solid #e3e6f0; }
        .profile-summary { flex: 1; }
        .profile-name { font-size: 1.5rem; font-weight: 700; color: #5a5c69; margin-bottom: 0.5rem; }
        .profile-role { color: #4e73df; font-weight: 700; margin-bottom: 1rem; }
        .profile-details { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .detail-item { font-size: 0.85rem; }
        .detail-label { font-weight: 700; color: #858796; }
        .detail-value { color: #5a5c69; }

        @media (max-width: 768px) {
            .sidebar { width: 0; position: absolute; }
            .sidebar.mobile-active { width: var(--sidebar-width); }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-university"></i>
            <span>ALM Portal</span>
        </div>
        <div class="sidebar-nav">
            <div class="nav-item active" onclick="switchTab('overview', this)"><i class="fas fa-fw fa-tachometer-alt"></i> Overview</div>
            <div class="nav-item" onclick="switchTab('profile', this)"><i class="fas fa-fw fa-user"></i> My Profile</div>
            <div class="nav-item" onclick="switchTab('attendance', this)"><i class="fas fa-fw fa-calendar-check"></i> Attendance</div>
            <div class="nav-item" onclick="switchTab('payroll', this)"><i class="fas fa-fw fa-money-bill-wave"></i> Payroll & Payslips</div>
            <div class="nav-item" onclick="switchTab('schedule', this)"><i class="fas fa-fw fa-clock"></i> Schedule & Loads</div>
            <div class="nav-item" onclick="switchTab('requests', this)"><i class="fas fa-fw fa-paper-plane"></i> Leave & Loans</div>
            <div class="nav-item" onclick="switchTab('settings', this)"><i class="fas fa-fw fa-cog"></i> Settings</div>
        </div>
        <div class="nav-item" onclick="logout()" style="margin-top: auto; border-top: 1px solid rgba(255,255,255,0.1);"><i class="fas fa-fw fa-sign-out-alt"></i> Logout</div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-title" id="page-title">Dashboard Overview</div>
            <div class="user-info">
                <span><?php echo $full_name; ?></span>
                <div class="user-avatar"><i class="fas fa-user"></i></div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            
            <!-- Overview Tab -->
            <div id="overview" class="tab-content active">
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <div class="stat-title">Attendance (30d)</div>
                        <div class="stat-value" id="stat-attendance">---</div>
                        <div class="stat-sub">Present Days</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-title">Last Net Pay</div>
                        <div class="stat-value" id="stat-last-pay">---</div>
                        <div class="stat-sub" id="stat-pay-period">---</div>
                    </div>
                    <div class="stat-card info">
                        <div class="stat-title">Leave Balance</div>
                        <div class="stat-value" id="stat-leave">---</div>
                        <div class="stat-sub">Available Days</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-title">Pending Requests</div>
                        <div class="stat-value" id="stat-pending">0</div>
                        <div class="stat-sub">Leave/Loan/Resign</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Announcements</h5>
                    </div>
                    <div class="card-body" id="announcements-container">
                        <div style="padding: 1rem; border-left: 4px solid #4e73df; background: #f8f9fc; margin-bottom: 1rem;">
                            <div style="font-weight: 700; color: #4e73df;">Payroll Processing Update</div>
                            <div style="font-size: 0.85rem; color: #858796; margin-bottom: 0.5rem;">Posted on <?php echo date('M d, Y'); ?></div>
                            <p style="margin: 0; font-size: 0.9rem;">The payroll for the current period is being processed. Expected disbursement is on Friday.</p>
                        </div>
                        <div style="padding: 1rem; border-left: 4px solid #1cc88a; background: #f8f9fc;">
                            <div style="font-weight: 700; color: #1cc88a;">Welcome to the New Portal!</div>
                            <div style="font-size: 0.85rem; color: #858796; margin-bottom: 0.5rem;">Posted on Mar 20, 2026</div>
                            <p style="margin: 0; font-size: 0.9rem;">We've upgraded the employee dashboard with more features. You can now view your subject loads and request resignations online.</p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody id="overview-activity-body">
                                <tr><td colspan="4" style="text-align:center;">Loading activity...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Profile Tab -->
            <div id="profile" class="tab-content">
                <div class="card">
                    <div class="card-body">
                        <div class="profile-header">
                            <div class="profile-img-container"><i class="fas fa-user"></i></div>
                            <div class="profile-summary">
                                <h2 class="profile-name" id="prof-name">---</h2>
                                <div class="profile-role" id="prof-position">---</div>
                                <div class="profile-details">
                                    <div class="detail-item"><span class="detail-label">Employee ID:</span> <span class="detail-value" id="prof-id">---</span></div>
                                    <div class="detail-item"><span class="detail-label">Department:</span> <span class="detail-value" id="prof-dept">---</span></div>
                                    <div class="detail-item"><span class="detail-label">Email:</span> <span class="detail-value" id="prof-email">---</span></div>
                                    <div class="detail-item"><span class="detail-label">Status:</span> <span class="detail-value" id="prof-status">---</span></div>
                                </div>
                            </div>
                            <button class="btn btn-primary" onclick="showEditProfile()"><i class="fas fa-edit"></i> Edit Profile</button>
                        </div>

                        <hr style="border: 0; border-top: 1px solid #e3e6f0; margin: 2rem 0;">
                        
                        <h5 class="card-title" style="margin-bottom: 1.5rem;">Government Contributions Information</h5>
                        <div class="profile-details" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                            <div class="detail-item"><span class="detail-label">SSS No:</span> <span class="detail-value" id="prof-sss">---</span></div>
                            <div class="detail-item"><span class="detail-label">TIN:</span> <span class="detail-value" id="prof-tin">---</span></div>
                            <div class="detail-item"><span class="detail-label">PhilHealth:</span> <span class="detail-value" id="prof-philhealth">---</span></div>
                            <div class="detail-item"><span class="detail-label">Pag-IBIG:</span> <span class="detail-value" id="prof-pagibig">---</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Tab -->
            <div id="attendance" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Attendance History</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
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
                            <tbody id="attendance-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payroll Tab -->
            <div id="payroll" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Payroll History</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
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
                            <tbody id="payroll-body"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Schedule Tab -->
            <div id="schedule" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Subject Loads & Schedule</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Units</th>
                                    <th>Hours</th>
                                </tr>
                            </thead>
                            <tbody id="subjects-body"></tbody>
                        </table>
                        <div id="no-subjects-msg" style="display:none; text-align:center; padding: 2rem; color: #858796;">
                            No subject loads assigned. If you are not a Faculty member, your schedule follows the company standard work hours.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requests Tab -->
            <div id="requests" class="tab-content">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                    <button class="btn btn-primary" style="justify-content: center; padding: 1rem;" onclick="showLeaveModal()">
                        <i class="fas fa-calendar-plus"></i> Apply for Leave
                    </button>
                    <button class="btn btn-success" style="justify-content: center; padding: 1rem;" onclick="showLoanModal()">
                        <i class="fas fa-hand-holding-usd"></i> Request Loan
                    </button>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="card-title">Recent Leave Requests</h5></div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr><th>Type</th><th>Duration</th><th>Status</th></tr>
                            </thead>
                            <tbody id="leaves-table-body"></tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="card-title">Recent Loan Requests</h5></div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr><th>Amount</th><th>Status</th><th>Requested Date</th></tr>
                            </thead>
                            <tbody id="loans-table-body"></tbody>
                        </table>
                    </div>
                </div>

                <div class="card" style="border-left-color: #e74a3b;">
                    <div class="card-header">
                        <h5 class="card-title" style="color: #e74a3b;">Resignation Request</h5>
                    </div>
                    <div class="card-body">
                        <p style="font-size: 0.9rem; color: #858796; margin-bottom: 1.5rem;">If you wish to resign, please submit your formal request here. All requests are subject to approval by HR.</p>
                        <div id="resignation-status-container"></div>
                        <button class="btn btn-danger btn-sm" id="btn-show-resign" onclick="showResignModal()">Submit Resignation</button>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div id="settings" class="tab-content">
                <div class="card">
                    <div class="card-header"><h5 class="card-title">Security Settings</h5></div>
                    <div class="card-body">
                        <form id="changePasswordForm" onsubmit="changePassword(event)">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" class="form-control" name="oldPass" required>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" class="form-control" name="newPass" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" class="form-control" name="confirmPass" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modals -->
    <!-- Edit Profile Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="card-header"><h5 class="card-title">Edit Profile Information</h5></div>
            <div class="card-body">
                <form id="profileForm" onsubmit="submitProfileUpdate(event)">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" class="form-control" name="email" id="edit-email" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" class="form-control" name="dob" id="edit-dob" required>
                    </div>
                    <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModals()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Leave Modal -->
    <div id="leaveModal" class="modal">
        <div class="modal-content">
            <div class="card-header"><h5 class="card-title">Apply for Leave</h5></div>
            <div class="card-body">
                <form id="leaveForm" onsubmit="submitLeave(event)">
                    <div class="form-group">
                        <label>Leave Type</label>
                        <select class="form-control" name="type" required>
                            <option value="Sick Leave">Sick Leave</option>
                            <option value="Vacation Leave">Vacation Leave</option>
                            <option value="Emergency Leave">Emergency Leave</option>
                            <option value="Maternity/Paternity">Maternity/Paternity</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Duration / Dates</label>
                        <input type="text" class="form-control" name="duration" required placeholder="e.g. March 10-12 (3 days)">
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <textarea class="form-control" name="reason" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModals()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Loan Modal -->
    <div id="loanModal" class="modal">
        <div class="modal-content">
            <div class="card-header"><h5 class="card-title">Request a Loan</h5></div>
            <div class="card-body">
                <form id="loanForm" onsubmit="submitLoan(event)">
                    <div class="form-group">
                        <label>Amount (PHP)</label>
                        <input type="number" class="form-control" name="amount" required step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Reason / Purpose</label>
                        <textarea class="form-control" name="reason" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModals()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Resignation Modal -->
    <div id="resignModal" class="modal">
        <div class="modal-content">
            <div class="card-header"><h5 class="card-title">Submit Resignation</h5></div>
            <div class="card-body">
                <form id="resignForm" onsubmit="submitResignation(event)">
                    <div class="form-group">
                        <label>Reason for Resignation</label>
                        <textarea class="form-control" name="reason" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Effective Date</label>
                        <input type="date" class="form-control" name="effective_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModals()">Cancel</button>
                        <button type="submit" class="btn btn-danger">Submit Resignation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Breakdown Modal -->
    <div id="breakdownModal" class="modal">
        <div class="modal-content">
            <div class="card-header"><h5 class="card-title">Payroll Details</h5></div>
            <div class="card-body" id="breakdown-content"></div>
            <div class="modal-footer" style="padding: 1.5rem; display: flex; justify-content: flex-end;">
                <button type="button" class="btn btn-primary" onclick="closeModals()">Close</button>
            </div>
        </div>
    </div>

    <script>
        let essData = null;

        function switchTab(tabId, el) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(t => t.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            el.classList.add('active');
            
            document.getElementById('page-title').innerText = el.innerText.trim();
        }

        async function loadESS() {
            try {
                const response = await fetch('backend/api.php?action=get_ess_data');
                essData = await response.json();
                if (!essData.profile) throw new Error("Unauthorized");

                updateDashboard();
                renderProfile();
                renderAttendance();
                renderPayroll();
                renderSubjects();
                renderRequests();

            } catch (err) {
                console.error(err);
                window.location.href = 'login.php';
            }
        }

        function updateDashboard() {
            const p = essData.profile;
            document.getElementById('stat-leave').innerText = p.leave_balance || '0';

            const att = essData.attendance || [];
            const present = att.filter(a => a.check_in).length;
            document.getElementById('stat-attendance').innerText = `${present} Days`;

            const payroll = essData.payroll || [];
            const lastP = payroll[0];
            if (lastP) {
                document.getElementById('stat-last-pay').innerText = `₱${parseFloat(lastP.net_pay).toLocaleString(undefined, {minimumFractionDigits:2})}`;
                document.getElementById('stat-pay-period').innerText = lastP.period;
            }

            const pendingLeaves = (essData.leave || []).filter(l => l.status === 'Pending').length;
            const pendingLoans = (essData.loans || []).filter(l => l.status === 'Pending').length;
            const pendingResign = (essData.resignation || []).filter(r => r.status === 'Pending').length;
            document.getElementById('stat-pending').innerText = pendingLeaves + pendingLoans + pendingResign;

            // Recent Activity
            const recentPayroll = (essData.payroll || []).slice(0, 3).map(p => ({
                type: 'Payroll', date: p.period, status: p.status, detail: `₱${parseFloat(p.net_pay).toLocaleString()}`
            }));
            const recentLeaves = (essData.leave || []).slice(0, 2).map(l => ({
                type: 'Leave', date: l.duration, status: l.status, detail: l.type
            }));
            
            const combined = [...recentPayroll, ...recentLeaves].sort((a,b) => b.id - a.id);
            const tbody = document.getElementById('overview-activity-body');
            if (combined.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No recent activity</td></tr>';
            } else {
                tbody.innerHTML = combined.map(item => `
                    <tr>
                        <td><strong>${item.type}</strong></td>
                        <td>${item.date}</td>
                        <td><span class="badge badge-${item.status.toLowerCase() === 'paid' || item.status.toLowerCase() === 'approved' ? 'success' : 'warning'}">${item.status}</span></td>
                        <td>${item.detail}</td>
                    </tr>
                `).join('');
            }
        }

        function renderProfile() {
            const p = essData.profile;
            document.getElementById('prof-name').innerText = p.full_name;
            document.getElementById('prof-position').innerText = p.position;
            document.getElementById('prof-id').innerText = p.employee_id;
            document.getElementById('prof-dept').innerText = p.department || 'General';
            document.getElementById('prof-email').innerText = p.user_email;
            document.getElementById('prof-status').innerText = p.status;
            
            document.getElementById('prof-sss').innerText = p.sss || '---';
            document.getElementById('prof-tin').innerText = p.tin || '---';
            document.getElementById('prof-philhealth').innerText = p.philhealth || '---';
            document.getElementById('prof-pagibig').innerText = p.pagibig || '---';

            // Fill edit form
            document.getElementById('edit-email').value = p.user_email;
            document.getElementById('edit-dob').value = p.dob;
        }

        function renderAttendance() {
            const tbody = document.getElementById('attendance-body');
            tbody.innerHTML = (essData.attendance || []).map(a => `
                <tr>
                    <td>${a.log_date}</td>
                    <td>${a.check_in || '--:--'}</td>
                    <td>${a.lunch_out || '--:--'}</td>
                    <td>${a.lunch_in || '--:--'}</td>
                    <td>${a.check_out || '--:--'}</td>
                    <td><span class="badge badge-${a.status === 'Late' ? 'warning' : 'success'}">${a.status || 'Present'}</span></td>
                </tr>
            `).join('') || '<tr><td colspan="6" style="text-align:center;">No attendance records</td></tr>';
        }

        function renderPayroll() {
            const tbody = document.getElementById('payroll-body');
            tbody.innerHTML = (essData.payroll || []).map(p => {
                const breakdown = p.breakdown ? JSON.parse(p.breakdown) : null;
                return `
                    <tr>
                        <td>${p.period}</td>
                        <td>₱${parseFloat(p.basic_pay).toLocaleString()}</td>
                        <td>₱${parseFloat(p.deductions).toLocaleString()}</td>
                        <td>₱${parseFloat(p.net_pay).toLocaleString()}</td>
                        <td><span class="badge badge-success">${p.status}</span></td>
                        <td>
                            <button class="btn btn-outline btn-sm" onclick="exportPayslip(${p.id})"><i class="fas fa-download"></i> PDF</button>
                            ${breakdown ? `<button class="btn btn-info btn-sm" onclick="viewBreakdown(${p.id})"><i class="fas fa-search"></i> Details</button>` : ''}
                        </td>
                    </tr>
                `;
            }).join('') || '<tr><td colspan="6" style="text-align:center;">No payroll records</td></tr>';
        }

        function viewBreakdown(id) {
            const p = essData.payroll.find(item => item.id == id);
            if (!p || !p.breakdown) return;
            const b = JSON.parse(p.breakdown);
            
            let html = `
                <div style="font-size: 0.9rem;">
                    <p><strong>Period:</strong> ${p.period}</p>
                    <table class="table" style="margin-top: 1rem;">
                        <tr style="background:#f8f9fc;"><th colspan="2">Earnings</th></tr>
                        <tr><td>Basic Salary (Period)</td><td>₱${parseFloat(p.basic_pay).toLocaleString()}</td></tr>
                        ${b.load_pay ? `<tr><td>Load Pay</td><td>₱${parseFloat(b.load_pay).toLocaleString()}</td></tr>` : ''}
                        ${b.overtime ? `<tr><td>Overtime</td><td>₱${parseFloat(b.overtime).toLocaleString()}</td></tr>` : ''}
                        ${b.honorarium ? `<tr><td>Honorarium</td><td>₱${parseFloat(b.honorarium).toLocaleString()}</td></tr>` : ''}
                        
                        <tr style="background:#f8f9fc;"><th colspan="2">Deductions</th></tr>
                        ${b.absences ? `<tr><td>Absences</td><td>₱${parseFloat(b.absences).toLocaleString()}</td></tr>` : ''}
                        ${b.late_ut ? `<tr><td>Late / Under-time</td><td>₱${parseFloat(b.late_ut).toLocaleString()}</td></tr>` : ''}
                        ${b.hdmf_cont ? `<tr><td>Pag-IBIG Contribution</td><td>₱${parseFloat(b.hdmf_cont).toLocaleString()}</td></tr>` : ''}
                        ${b.hdmf_loans ? `<tr><td>Pag-IBIG Loans</td><td>₱${parseFloat(b.hdmf_loans).toLocaleString()}</td></tr>` : ''}
                        ${b.cash_advance ? `<tr><td>Cash Advance</td><td>₱${parseFloat(b.cash_advance).toLocaleString()}</td></tr>` : ''}
                        <tr style="font-weight:700; color:#e74a3b;"><td>Total Deductions</td><td>₱${parseFloat(p.deductions).toLocaleString()}</td></tr>
                        
                        <tr style="background:#4e73df; color:#fff; font-weight:700;"><td>NET PAYOUT</td><td>₱${parseFloat(p.net_pay).toLocaleString()}</td></tr>
                    </table>
                </div>
            `;
            
            document.getElementById('breakdown-content').innerHTML = html;
            document.getElementById('breakdownModal').style.display = 'flex';
        }

        function renderSubjects() {
            const tbody = document.getElementById('subjects-body');
            const subjects = essData.subjects || [];
            if (subjects.length === 0) {
                document.getElementById('no-subjects-msg').style.display = 'block';
                tbody.innerHTML = '';
            } else {
                document.getElementById('no-subjects-msg').style.display = 'none';
                tbody.innerHTML = subjects.map(s => `
                    <tr>
                        <td>${s.code}</td>
                        <td>${s.description}</td>
                        <td>${s.units}</td>
                        <td>${s.hours}</td>
                    </tr>
                `).join('');
            }
        }

        function renderRequests() {
            // Leaves
            const leaveTbody = document.getElementById('leaves-table-body');
            leaveTbody.innerHTML = (essData.leave || []).slice(0, 5).map(l => `
                <tr>
                    <td>${l.type}</td>
                    <td>${l.duration}</td>
                    <td><span class="badge badge-${l.status.toLowerCase() === 'approved' ? 'success' : (l.status.toLowerCase() === 'pending' ? 'warning' : 'danger')}">${l.status}</span></td>
                </tr>
            `).join('') || '<tr><td colspan="3" style="text-align:center;">No leave requests</td></tr>';

            // Loans
            const loanTbody = document.getElementById('loans-table-body');
            loanTbody.innerHTML = (essData.loans || []).slice(0, 5).map(l => `
                <tr>
                    <td>₱${parseFloat(l.amount).toLocaleString()}</td>
                    <td><span class="badge badge-${l.status.toLowerCase() === 'approved' ? 'success' : (l.status.toLowerCase() === 'pending' ? 'warning' : 'danger')}">${l.status}</span></td>
                    <td>${new Date(l.requested_at).toLocaleDateString()}</td>
                </tr>
            `).join('') || '<tr><td colspan="3" style="text-align:center;">No loan requests</td></tr>';

            // Resignation
            const resContainer = document.getElementById('resignation-status-container');
            const btnResign = document.getElementById('btn-show-resign');
            if (essData.resignation && essData.resignation.length > 0) {
                const r = essData.resignation[0];
                resContainer.innerHTML = `
                    <div style="padding: 1rem; background: #f8f9fc; border-radius: 0.5rem; margin-bottom: 1rem;">
                        <strong>Current Request Status:</strong> <span class="badge badge-${r.status.toLowerCase() === 'approved' ? 'success' : 'warning'}">${r.status}</span><br>
                        <strong>Effective Date:</strong> ${r.effective_date}<br>
                        <p style="margin-top: 0.5rem; font-size: 0.85rem;">Reason: ${r.reason}</p>
                    </div>
                `;
                btnResign.style.display = 'none';
            } else {
                resContainer.innerHTML = '';
                btnResign.style.display = 'inline-flex';
            }
        }

        // Modal Controls
        function showEditProfile() { document.getElementById('profileModal').style.display = 'flex'; }
        function showLeaveModal() { document.getElementById('leaveModal').style.display = 'flex'; }
        function showLoanModal() { document.getElementById('loanModal').style.display = 'flex'; }
        function showResignModal() { document.getElementById('resignModal').style.display = 'flex'; }
        function closeModals() { 
            document.querySelectorAll('.modal').forEach(m => m.style.display = 'none'); 
        }

        async function submitProfileUpdate(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            const res = await fetch('backend/api.php?action=update_profile', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                alert('Profile updated successfully!');
                closeModals();
                loadESS();
            } else {
                alert('Error: ' + result.message);
            }
        }

        async function submitLeave(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            const res = await fetch('backend/api.php?action=apply_leave', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                alert('Leave request submitted!');
                closeModals();
                loadESS();
            } else {
                alert('Error: ' + result.message);
            }
        }

        async function submitLoan(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            const res = await fetch('backend/api.php?action=apply_loan', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                alert('Loan request submitted!');
                closeModals();
                loadESS();
            } else {
                alert('Error: ' + result.message);
            }
        }

        async function submitResignation(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            const res = await fetch('backend/api.php?action=apply_resignation', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                alert('Resignation request submitted successfully.');
                closeModals();
                loadESS();
            } else {
                alert('Error: ' + result.message);
            }
        }

        async function changePassword(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            
            if (data.newPass !== data.confirmPass) {
                alert('Passwords do not match');
                return;
            }

            const res = await fetch('backend/api.php?action=change_password', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                alert('Password updated successfully!');
                e.target.reset();
            } else {
                alert('Error: ' + result.message);
            }
        }

        async function exportPayslip(id) {
            const response = await fetch(`backend/api.php?action=get_payslip&id=${id}`);
            const payslip = await response.json();
            if (!payslip) return;

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            doc.setFontSize(22);
            doc.text('PAYSLIP', 105, 20, {align: 'center'});
            doc.setFontSize(10);
            doc.text(payslip.period, 105, 28, {align: 'center'});

            doc.autoTable({
                startY: 40,
                head: [['Description', 'Amount']],
                body: [
                    ['Employee', payslip.full_name],
                    ['Employee ID', payslip.emp_code],
                    ['Basic Salary', `PHP ${parseFloat(payslip.basic_pay).toLocaleString()}`],
                    ['Total Deductions', `PHP ${parseFloat(payslip.deductions).toLocaleString()}`],
                    ['NET PAYOUT', `PHP ${parseFloat(payslip.net_pay).toLocaleString()}`]
                ],
                theme: 'grid',
                headStyles: {fillColor: [78, 115, 223]}
            });

            doc.save(`Payslip_${payslip.emp_code}_${payslip.period.replace(/\//g, '-')}.pdf`);
        }

        async function logout() {
            await fetch('backend/api.php?action=logout');
            window.location.href = 'login.php';
        }

        loadESS();
    </script>
</body>
</html>