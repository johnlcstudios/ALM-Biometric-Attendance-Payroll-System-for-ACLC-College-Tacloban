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
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="role-payroll">
    <div class="app-container">
        <div id="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 999999; display: flex; align-items: center; justify-content: center; pointer-events: auto;">
            <i class="fas fa-spinner fa-spin" style="font-size: 50px; color: var(--primary-color);" aria-hidden="true"></i>
        </div>

        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="assets/logo.jpg" alt="Officer Portal Logo" class="sidebar-logo">
                    <span>Officer Portal</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <!-- Shared Dashboard -->
                <button type="button" class="nav-btn <?php echo $page === 'dashboard' ? 'active' : ''; ?>" <?php echo $page === 'dashboard' ? 'aria-current="page"' : ''; ?> data-page="dashboard" onclick="window.location.href='Payroll-Officer.php?page=dashboard'">
                    <i class="fas fa-th-large" aria-hidden="true"></i> <span>Dashboard</span>
                </button>
                
                <!-- Shared Role-Based Access -->
                <button type="button" class="nav-btn <?php echo $page === 'employees' ? 'active' : ''; ?>" <?php echo $page === 'employees' ? 'aria-current="page"' : ''; ?> data-page="employees" onclick="window.location.href='Payroll-Officer.php?page=employees'">
                    <i class="fas fa-users" aria-hidden="true"></i> <span>Employees</span>
                </button>

                <!-- Payroll Officer Access -->
                <button type="button" class="nav-btn <?php echo $page === 'attendance' ? 'active' : ''; ?>" <?php echo $page === 'attendance' ? 'aria-current="page"' : ''; ?> data-page="attendance" onclick="window.location.href='Payroll-Officer.php?page=attendance'">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i> <span>Attendance Logs</span>
                </button>
                <button type="button" class="nav-btn <?php echo $page === 'payroll' ? 'active' : ''; ?>" <?php echo $page === 'payroll' ? 'aria-current="page"' : ''; ?> data-page="payroll" onclick="window.location.href='Payroll-Officer.php?page=payroll'">
                    <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i> <span>Payroll</span>
                </button>
                <button type="button" class="nav-btn <?php echo $page === 'faculty_payroll' ? 'active' : ''; ?>" <?php echo $page === 'faculty_payroll' ? 'aria-current="page"' : ''; ?> data-page="faculty_payroll" onclick="window.location.href='Payroll-Officer.php?page=faculty_payroll'">
                    <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i> <span>Faculty Payroll</span>
                </button>
                <button type="button" class="nav-btn <?php echo $page === 'utility_payroll' ? 'active' : ''; ?>" <?php echo $page === 'utility_payroll' ? 'aria-current="page"' : ''; ?> data-page="utility_payroll" onclick="window.location.href='Payroll-Officer.php?page=utility_payroll'">
                    <i class="fas fa-tools" aria-hidden="true"></i> <span>Utility Payroll</span>
                </button>
                <button type="button" class="nav-btn <?php echo $page === 'allowances' ? 'active' : ''; ?>" <?php echo $page === 'allowances' ? 'aria-current="page"' : ''; ?> data-page="allowances" onclick="window.location.href='Payroll-Officer.php?page=allowances'">
                    <i class="fas fa-coins" aria-hidden="true"></i> <span>Allowances</span>
                </button>
                <button type="button" class="nav-btn <?php echo $page === 'deductions' ? 'active' : ''; ?>" <?php echo $page === 'deductions' ? 'aria-current="page"' : ''; ?> data-page="deductions" onclick="window.location.href='Payroll-Officer.php?page=deductions'">
                    <i class="fas fa-calculator" aria-hidden="true"></i> <span>Deductions</span>
                </button>

                <!-- Management Shared -->
                <button type="button" class="nav-btn <?php echo $page === 'leave' ? 'active' : ''; ?>" <?php echo $page === 'leave' ? 'aria-current="page"' : ''; ?> data-page="leave" onclick="window.location.href='Payroll-Officer.php?page=leave'">
                    <i class="fas fa-calendar-check" aria-hidden="true"></i> <span>Leave Requests</span>
                </button>
                <button type="button" class="nav-btn <?php echo $page === 'loans' ? 'active' : ''; ?>" <?php echo $page === 'loans' ? 'aria-current="page"' : ''; ?> data-page="loans" onclick="window.location.href='Payroll-Officer.php?page=loans'">
                    <i class="fas fa-hand-holding-usd" aria-hidden="true"></i> <span>Cash Advance</span>
                </button>
                <button type="button" class="nav-btn <?php echo $page === 'resignations' ? 'active' : ''; ?>" <?php echo $page === 'resignations' ? 'aria-current="page"' : ''; ?> data-page="resignations" onclick="window.location.href='Payroll-Officer.php?page=resignations'">
                    <i class="fas fa-user-minus" aria-hidden="true"></i> <span>Resignations</span>
                </button>
                <button type="button" class="nav-btn <?php echo $page === 'reports' ? 'active' : ''; ?>" <?php echo $page === 'reports' ? 'aria-current="page"' : ''; ?> data-page="reports" onclick="window.location.href='Payroll-Officer.php?page=reports'">
                    <i class="fas fa-chart-bar" aria-hidden="true"></i> <span>Reports</span>
                </button>
                <button type="button" class="nav-btn <?php echo $page === 'settings' ? 'active' : ''; ?>" <?php echo $page === 'settings' ? 'aria-current="page"' : ''; ?> data-page="settings" onclick="window.location.href='Payroll-Officer.php?page=settings'">
                    <i class="fas fa-cog" aria-hidden="true"></i> <span>Settings</span>
                </button>
                <button type="button" class="nav-btn <?php echo $page === 'profile' ? 'active' : ''; ?>" <?php echo $page === 'profile' ? 'aria-current="page"' : ''; ?> data-page="profile" onclick="window.location.href='Payroll-Officer.php?page=profile'">
                    <i class="fas fa-user" aria-hidden="true"></i> <span>My Profile</span>
                </button>
            </nav>
            <div class="sidebar-footer">
                <button type="button" class="nav-btn logout" onclick="logout()" aria-label="Logout">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i> <span>Logout</span>
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
    <script src="js/script.js"></script>
    <script src="js/context-menu.js?v=1.0"></script>
</body>
</html>
