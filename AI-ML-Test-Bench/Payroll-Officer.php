<?php
require_once 'backend/db.php';
session_start();

// Access Control
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$session_role = trim($_SESSION['role'] ?? '');

if (strcasecmp($session_role, 'Payroll') !== 0) {
    if (in_array($session_role, ['Admin', 'HR'])) {
        header('Location: index.php');
    } else {
        header('Location: ess.php');
    }
    exit;
}

$role = $session_role;
$company_name = $_SESSION['company_name'] ?? 'ALM Tech Solutions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Officer Dashboard - <?php echo $company_name; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="role-payroll">
    <div class="app-container">
        <div id="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 9999; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-spinner fa-spin" style="font-size: 50px; color: var(--primary-color);"></i>
        </div>

        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="assets/logo.jpg" alt="Logo" class="sidebar-logo">
                    <span>Officer Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <button class="nav-btn active" onclick="showPage('dashboard')">
                    <i class="fas fa-th-large"></i> <span>Dashboard</span>
                </button>
                <button class="nav-btn" onclick="showPage('employees')">
                    <i class="fas fa-users"></i> <span>View Employees</span>
                </button>
                <button class="nav-btn" onclick="showPage('subject_loads')">
                    <i class="fas fa-book"></i> <span>Subject Loads</span>
                </button>
                <button class="nav-btn" onclick="showPage('payroll')">
                    <i class="fas fa-file-invoice-dollar"></i> <span>Manage Payroll</span>
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
                <button class="nav-btn" onclick="showPage('deductions')">
                    <i class="fas fa-calculator"></i> <span>Deductions</span>
                </button>
                <button class="nav-btn" onclick="showPage('leave')">
                    <i class="fas fa-calendar-check"></i> <span>Leave Management</span>
                </button>
                <button class="nav-btn" onclick="showPage('loans')">
                    <i class="fas fa-hand-holding-usd"></i> <span>Loan Review</span>
                </button>
                <button class="nav-btn" onclick="showPage('reports')">
                    <i class="fas fa-chart-bar"></i> <span>Generate Reports</span>
                </button>
                <button class="nav-btn" onclick="showPage('settings')">
                    <i class="fas fa-cog"></i> <span>Payroll Settings</span>
                </button>
            </nav>
            <div class="sidebar-footer">
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <h1 id="current-page-title">Officer Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <img src="https://ui-avatars.com/api/?name=Payroll+Officer&background=1e0178&color=fff" alt="Avatar">
                        <span>Payroll Officer</span>
                    </div>
                </div>
            </header>

            <div class="content-body">
                <?php include 'backend/sections.php'; ?>
            </div>
        </main>
    </div>

    <?php include 'backend/modals.php'; ?>

    <script>
        const USER_ROLE = "Payroll";
    </script>
    <script src="js/script.js"></script>
</body>
</html>
