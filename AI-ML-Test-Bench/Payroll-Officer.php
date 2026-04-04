<?php
// Start session BEFORE requiring db.php or any other output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'backend/db.php';

// Access Control
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$session_role = trim($_SESSION['role'] ?? '');

if (!in_array($session_role, ['Payroll', 'Payroll Officer'])) {
    if (in_array($session_role, ['Admin', 'HR'])) {
        header('Location: index.php');
    } else {
        header('Location: ess.php');
    }
    exit;
}

$role = $session_role;
$company_name = $_SESSION['company_name'] ?? 'ALM Tech Solutions';
$full_name = $_SESSION['full_name'] ?? 'Payroll Officer';
$allowed_pages = [
    'dashboard',
    'employees',
    'subject_loads',
    'biometrics',
    'assign_payroll',
    'attendance',
    'payroll',
    'faculty_payroll',
    'utility_payroll',
    'allowances',
    'deductions',
    'leave',
    'loans',
    'resignations',
    'reports',
    'settings'
];
$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $allowed_pages, true)) $page = 'dashboard';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
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
                <!-- Shared Dashboard -->
                <button class="nav-btn <?php echo $page === 'dashboard' ? 'active' : ''; ?>" data-page="dashboard" onclick="window.location.href='Payroll-Officer.php?page=dashboard'">
                    <i class="fas fa-th-large"></i> <span>Dashboard</span>
                </button>
                
                <!-- Shared Role-Based Access -->
                <button class="nav-btn <?php echo $page === 'employees' ? 'active' : ''; ?>" data-page="employees" onclick="window.location.href='Payroll-Officer.php?page=employees'">
                    <i class="fas fa-users"></i> <span>Employees</span>
                </button>
                <button class="nav-btn <?php echo $page === 'subject_loads' ? 'active' : ''; ?>" data-page="subject_loads" onclick="window.location.href='Payroll-Officer.php?page=subject_loads'">
                    <i class="fas fa-book"></i> <span>Subject Loads</span>
                </button>

                <!-- Payroll Officer / Admin Access -->
                <button class="nav-btn <?php echo $page === 'biometrics' ? 'active' : ''; ?>" data-page="biometrics" onclick="window.location.href='Payroll-Officer.php?page=biometrics'">
                    <i class="fas fa-id-card"></i> <span>Face Enrollment</span>
                </button>
                <button class="nav-btn <?php echo $page === 'assign_payroll' ? 'active' : ''; ?>" data-page="assign_payroll" onclick="window.location.href='Payroll-Officer.php?page=assign_payroll'">
                    <i class="fas fa-user-shield"></i> <span>Assign Payroll Officer</span>
                </button>

                <!-- Payroll Officer Access -->
                <button class="nav-btn <?php echo $page === 'attendance' ? 'active' : ''; ?>" data-page="attendance" onclick="window.location.href='Payroll-Officer.php?page=attendance'">
                    <i class="fas fa-calendar-alt"></i> <span>Attendance Logs</span>
                </button>
                <button class="nav-btn <?php echo $page === 'payroll' ? 'active' : ''; ?>" data-page="payroll" onclick="window.location.href='Payroll-Officer.php?page=payroll'">
                    <i class="fas fa-file-invoice-dollar"></i> <span>Payroll</span>
                </button>
                <button class="nav-btn <?php echo $page === 'faculty_payroll' ? 'active' : ''; ?>" data-page="faculty_payroll" onclick="window.location.href='Payroll-Officer.php?page=faculty_payroll'">
                    <i class="fas fa-chalkboard-teacher"></i> <span>Faculty Payroll</span>
                </button>
                <button class="nav-btn <?php echo $page === 'utility_payroll' ? 'active' : ''; ?>" data-page="utility_payroll" onclick="window.location.href='Payroll-Officer.php?page=utility_payroll'">
                    <i class="fas fa-tools"></i> <span>Utility Payroll</span>
                </button>
                <button class="nav-btn <?php echo $page === 'allowances' ? 'active' : ''; ?>" data-page="allowances" onclick="window.location.href='Payroll-Officer.php?page=allowances'">
                    <i class="fas fa-coins"></i> <span>Allowances</span>
                </button>
                <button class="nav-btn <?php echo $page === 'deductions' ? 'active' : ''; ?>" data-page="deductions" onclick="window.location.href='Payroll-Officer.php?page=deductions'">
                    <i class="fas fa-calculator"></i> <span>Deductions</span>
                </button>

                <!-- Management Shared -->
                <button class="nav-btn <?php echo $page === 'leave' ? 'active' : ''; ?>" data-page="leave" onclick="window.location.href='Payroll-Officer.php?page=leave'">
                    <i class="fas fa-calendar-check"></i> <span>Leave Requests</span>
                </button>
                <button class="nav-btn <?php echo $page === 'loans' ? 'active' : ''; ?>" data-page="loans" onclick="window.location.href='Payroll-Officer.php?page=loans'">
                    <i class="fas fa-hand-holding-usd"></i> <span>Loan Requests</span>
                </button>
                <button class="nav-btn <?php echo $page === 'resignations' ? 'active' : ''; ?>" data-page="resignations" onclick="window.location.href='Payroll-Officer.php?page=resignations'">
                    <i class="fas fa-user-minus"></i> <span>Resignations</span>
                </button>
                <button class="nav-btn <?php echo $page === 'reports' ? 'active' : ''; ?>" data-page="reports" onclick="window.location.href='Payroll-Officer.php?page=reports'">
                    <i class="fas fa-chart-bar"></i> <span>Reports</span>
                </button>
                <button class="nav-btn <?php echo $page === 'settings' ? 'active' : ''; ?>" data-page="settings" onclick="window.location.href='Payroll-Officer.php?page=settings'">
                    <i class="fas fa-cog"></i> <span>Settings</span>
                </button>
            </nav>
            <div class="sidebar-footer">
                <button class="nav-btn logout" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <header class="top-bar">
                <div class="page-title">
                    <h2 id="current-page-title">Dashboard</h2>
                    <p class="company-tag"><?php echo $company_name; ?></p>
                </div>
                <div class="user-profile">
                    <div class="profile-info">
                        <div class="profile-text">
                            <span class="name"><?php echo $full_name; ?></span>
                            <span class="role"><?php echo $role; ?> Portal</span>
                        </div>
                    </div>
                </div>
            </header>

            <div id="content-pages">
                <?php include __DIR__ . '/pages/payroll_officer/pages/' . $page . '.php'; ?>
                <script>
                    (function() {
                        const el = document.getElementById('<?php echo $page; ?>');
                        if (el) el.classList.add('active');
                    })();
                </script>
            </div>
        </main>
    </div>

    <!-- Same Modals as before -->
    <?php include 'backend/modals.php'; ?>

    <script>
        const USER_ROLE = "<?php echo $role; ?>";
    </script>
    <script src="js/script.js"></script>
</body>
</html>
