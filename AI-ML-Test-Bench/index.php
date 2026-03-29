<?php
require_once 'backend/db.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role'];
$company_name = $_SESSION['company_name'] ?? 'ALM Tech Solutions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll System Hub - <?php echo $company_name; ?></title>
    <!-- Same head links as before -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="role-<?php echo strtolower($role); ?>">
    <div class="app-container">
        <div id="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 9999; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-spinner fa-spin" style="font-size: 50px; color: var(--primary-color);"></i>
        </div>
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="assets/logo.jpg" alt="Logo" width="90" height="90" style="width: 90px; height: 90px; border-radius: 100%;">
                    <span>Payroll System</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <!-- HR & Payroll Shared -->
                <button class="nav-btn active" onclick="showPage('dashboard')">
                    <i class="fas fa-th-large"></i> <span>Dashboard</span>
                </button>
                
                <?php if ($role === 'HR'): ?>
                <button class="nav-btn" onclick="showPage('employees')">
                    <i class="fas fa-users"></i> <span>Employees</span>
                </button>
                <button class="nav-btn" onclick="showPage('biometrics')">
                    <i class="fas fa-id-card"></i> <span>Face Enrollment</span>
                </button>
                <?php endif; ?>

                <button class="nav-btn" onclick="showPage('attendance')">
                    <i class="fas fa-calendar-alt"></i> <span>Attendance Logs</span>
                </button>
                <button class="nav-btn" onclick="showPage('payroll')">
                    <i class="fas fa-file-invoice-dollar"></i> <span>Payroll</span>
                </button>
                <button class="nav-btn" onclick="showPage('faculty_payroll')">
                    <i class="fas fa-chalkboard-teacher"></i> <span>Faculty Payroll</span>
                </button>
                <button class="nav-btn" onclick="showPage('utility_payroll')">
                    <i class="fas fa-tools"></i> <span>Utility Payroll</span>
                </button>
                <button class="nav-btn" onclick="showPage('allowances')">
                    <i class="fas fa-coins"></i> <span>Allowances</span>
                </button>
                <button class="nav-btn" onclick="showPage('leave')">
                    <i class="fas fa-calendar-check"></i> <span>Leave Requests</span>
                </button>
                <button class="nav-btn" onclick="showPage('loans')">
                    <i class="fas fa-hand-holding-usd"></i> <span>Loan Requests</span>
                </button>
                <button class="nav-btn" onclick="showPage('resignations')">
                    <i class="fas fa-user-minus"></i> <span>Resignations</span>
                </button>
                
                <?php if ($role === 'HR'): ?>
                <button class="nav-btn" onclick="showPage('deductions')">
                    <i class="fas fa-calculator"></i> <span>Deductions</span>
                </button>
                <button class="nav-btn" onclick="showPage('reports')">
                    <i class="fas fa-chart-bar"></i> <span>Reports</span>
                </button>
                <button class="nav-btn" onclick="showPage('settings')">
                    <i class="fas fa-cog"></i> <span>Settings</span>
                </button>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <button class="nav-btn logout" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Top bar and page content sections remain similar but filtered by role -->
            <header class="top-bar">
                <div class="page-title">
                    <h2 id="current-page-title">Dashboard</h2>
                    <p class="company-tag"><?php echo $company_name; ?></p>
                </div>
                <div class="user-profile">
                    <div class="profile-info">
                        <div class="profile-text">
                            <span class="name">Admin User</span>
                            <span class="role"><?php echo $role; ?> Admin</span>
                        </div>
                    </div>
                </div>
            </header>

            <div id="content-pages">
                <!-- Sections for Dashboard, Employees, etc. (re-used from index.html) -->
                <!-- The existing sections will be injected or kept based on PHP logic -->
                <?php include 'backend/sections.php'; ?>
            </div>
        </main>
    </div>

    <!-- Same Modals as before -->
    <?php include 'backend/modals.php'; ?>

    <script src="js/script.js"></script>
</body>
</html>
