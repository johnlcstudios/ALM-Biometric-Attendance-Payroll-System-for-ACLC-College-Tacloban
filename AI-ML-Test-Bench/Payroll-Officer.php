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

if ($session_role !== 'HR' && $session_role !== 'Admin' && $session_role !== 'Payroll Officer') {
    header('Location: ess.php');
    exit;
}

if ($session_role === 'Admin' || $session_role === 'HR') {
    header('Location: index.php');
    exit;
}

$role = $session_role;
$company_name = $_SESSION['company_name'] ?? 'ALM Tech Solutions';
$full_name = $_SESSION['full_name'] ?? 'Payroll Officer';
$allowed_pages = [
    'dashboard',
    'employees',
    'biometrics',
    'assign_payroll',
    'attendance',
    'payroll',
    'payroll_specialized',
    'allowances',
    'deductions',
    'leave',
    'loans',
    'resignations',
    'archived_employees',
    'reports',
    'settings',
    'profile'
];
$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $allowed_pages, true)) $page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Officer Dashboard - <?php echo htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/chart.min.js"></script>
    <script src="js/face-api.min.js"></script>
    <script src="js/face-api-manager.js"></script>
    <script src="js/jspdf.umd.min.js"></script>
    <script src="js/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="css/style.css?v=2.4">
</head>
<body class="role-payroll">
    <div class="app-container">
        <div id="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 999999; display: flex; align-items: center; justify-content: center; pointer-events: auto;">
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

                <!-- Payroll Officer Access -->
                <button class="nav-btn <?php echo $page === 'attendance' ? 'active' : ''; ?>" data-page="attendance" onclick="window.location.href='Payroll-Officer.php?page=attendance'">
                    <i class="fas fa-calendar-alt"></i> <span>Attendance Logs</span>
                </button>
                <button class="nav-btn <?php echo $page === 'payroll' ? 'active' : ''; ?>" data-page="payroll" onclick="window.location.href='Payroll-Officer.php?page=payroll'">
                    <i class="fas fa-file-invoice-dollar"></i> <span>Payroll</span>
                </button>
                <button class="nav-btn <?php echo $page === 'payroll_specialized' ? 'active' : ''; ?>" data-page="payroll_specialized" onclick="window.location.href='Payroll-Officer.php?page=payroll_specialized'">
                    <i class="fas fa-file-invoice"></i> <span>Specialized Payroll</span>
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
                    <i class="fas fa-hand-holding-usd"></i> <span>Cash Advance</span>
                </button>
                <button class="nav-btn <?php echo $page === 'resignations' ? 'active' : ''; ?>" data-page="resignations" onclick="window.location.href='Payroll-Officer.php?page=resignations'">
                    <i class="fas fa-user-minus"></i> <span>Resignations</span>
                </button>
                <button class="nav-btn <?php echo $page === 'archived_employees' ? 'active' : ''; ?>" data-page="archived_employees" onclick="window.location.href='Payroll-Officer.php?page=archived_employees'">
                    <i class="fas fa-archive"></i> <span>Archived</span>
                </button>
                <button class="nav-btn <?php echo $page === 'reports' ? 'active' : ''; ?>" data-page="reports" onclick="window.location.href='Payroll-Officer.php?page=reports'">
                    <i class="fas fa-chart-bar"></i> <span>Reports</span>
                </button>
                <button class="nav-btn <?php echo $page === 'settings' ? 'active' : ''; ?>" data-page="settings" onclick="window.location.href='Payroll-Officer.php?page=settings'">
                    <i class="fas fa-cog"></i> <span>Settings</span>
                </button>
                <button class="nav-btn <?php echo $page === 'profile' ? 'active' : ''; ?>" data-page="profile" onclick="window.location.href='Payroll-Officer.php?page=profile'">
                    <i class="fas fa-user"></i> <span>My Profile</span>
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
                    <p class="company-tag"><?php echo htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="user-profile">
                    <div class="profile-info">
                        <div class="profile-text">
                            <span class="name"><?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="role"><?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?> Portal</span>
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
        const USER_ROLE = "<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>";
    </script>
    <script src="js/script.js?v=2.5"></script>
    <script src="js/context-menu.js?v=1.0"></script>
</body>
</html>
