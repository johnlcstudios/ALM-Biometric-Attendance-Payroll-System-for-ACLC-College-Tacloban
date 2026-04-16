<?php
// Start session BEFORE requiring db.php or any other output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'backend/db.php';

// Load database updates silently every time index.php is loaded
define('INTERNAL_UPDATE', true);
require_once 'backend/update_db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (in_array($_SESSION['role'], ['Payroll', 'Payroll Officer'])) {
    header('Location: Payroll-Officer.php');
    exit;
}

if (!in_array($_SESSION['role'], ['Admin', 'HR'])) {
    header('Location: ess.php');
    exit;
}

$role = $_SESSION['role'];
$company_name = $_SESSION['company_name'] ?? 'ALM Tech Solutions';
$full_name = $_SESSION['full_name'] ?? 'Admin User';
$allowed_pages = [
    'dashboard',
    'employees',
    'subject_loads',
    'biometrics',
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
    'assign_payroll',
    'settings'
];
$page = $_GET['page'] ?? 'dashboard';
// Sanitize page input to prevent LFI (remove directory traversal characters)
$page = str_replace(['.', '/', '\\'], '', $page);

if (!in_array($page, $allowed_pages, true)) $page = 'dashboard';
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
    <script src="js/chart.min.js"></script>
    <script src="js/face-api.min.js"></script>
    <script src="js/face-api-manager.js"></script>
    <script src="js/jspdf.umd.min.js"></script>
    <script src="js/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    <img src="assets/logo.jpg" alt="Logo" class="sidebar-logo">
                    <span>Payroll System</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <!-- Shared Dashboard -->
                <button class="nav-btn <?php echo $page === 'dashboard' ? 'active' : ''; ?>" data-page="dashboard" onclick="window.location.href='index.php?page=dashboard'">
                    <i class="fas fa-th-large"></i> <span>Dashboard</span>
                </button>
                
                <!-- Shared Role-Based Access -->
                <?php if (in_array($role, ['HR', 'Admin', 'Payroll', 'Payroll Officer'])): ?>
                <button class="nav-btn <?php echo $page === 'employees' ? 'active' : ''; ?>" data-page="employees" onclick="window.location.href='index.php?page=employees'">
                    <i class="fas fa-users"></i> <span>Employees</span>
                </button>
                <button class="nav-btn <?php echo $page === 'subject_loads' ? 'active' : ''; ?>" data-page="subject_loads" onclick="window.location.href='index.php?page=subject_loads'">
                    <i class="fas fa-book"></i> <span>Subject Loads</span>
                </button>
                <?php endif; ?>

                <!-- Role: Admin / HR / Payroll Officer -->
                <?php if (in_array($role, ['HR', 'Admin', 'Payroll Officer'])): ?>
                <button class="nav-btn <?php echo $page === 'biometrics' ? 'active' : ''; ?>" data-page="biometrics" onclick="window.location.href='index.php?page=biometrics'">
                    <i class="fas fa-id-card"></i> <span>Face Registration</span>
                </button>
                <?php endif; ?>

                <!-- Role: Payroll Officer specific or Admin -->
                <?php if (in_array($role, ['HR', 'Admin', 'Payroll', 'Payroll Officer'])): ?>
                <button class="nav-btn <?php echo $page === 'attendance' ? 'active' : ''; ?>" data-page="attendance" onclick="window.location.href='index.php?page=attendance'">
                    <i class="fas fa-calendar-alt"></i> <span>Attendance Logs</span>
                </button>
                <button class="nav-btn <?php echo $page === 'payroll' ? 'active' : ''; ?>" data-page="payroll" onclick="window.location.href='index.php?page=payroll'">
                    <i class="fas fa-file-invoice-dollar"></i> <span>Payroll</span>
                </button>
                <button class="nav-btn <?php echo $page === 'faculty_payroll' ? 'active' : ''; ?>" data-page="faculty_payroll" onclick="window.location.href='index.php?page=faculty_payroll'">
                    <i class="fas fa-chalkboard-teacher"></i> <span>Faculty Payroll</span>
                </button>
                <button class="nav-btn <?php echo $page === 'utility_payroll' ? 'active' : ''; ?>" data-page="utility_payroll" onclick="window.location.href='index.php?page=utility_payroll'">
                    <i class="fas fa-tools"></i> <span>Utility Payroll</span>
                </button>
                <button class="nav-btn <?php echo $page === 'allowances' ? 'active' : ''; ?>" data-page="allowances" onclick="window.location.href='index.php?page=allowances'">
                    <i class="fas fa-coins"></i> <span>Allowances</span>
                </button>
                <button class="nav-btn <?php echo $page === 'deductions' ? 'active' : ''; ?>" data-page="deductions" onclick="window.location.href='index.php?page=deductions'">
                    <i class="fas fa-calculator"></i> <span>Deductions</span>
                </button>
                <?php endif; ?>

                <!-- Management Shared -->
                <button class="nav-btn <?php echo $page === 'leave' ? 'active' : ''; ?>" data-page="leave" onclick="window.location.href='index.php?page=leave'">
                    <i class="fas fa-calendar-check"></i> <span>Leave Requests</span>
                </button>
                <?php if (in_array($role, ['HR', 'Admin', 'Payroll', 'Payroll Officer'])): ?>
                <button class="nav-btn <?php echo $page === 'loans' ? 'active' : ''; ?>" data-page="loans" onclick="window.location.href='index.php?page=loans'">
                    <i class="fas fa-hand-holding-usd"></i> <span>Loan Requests</span>
                </button>
                <button class="nav-btn <?php echo $page === 'resignations' ? 'active' : ''; ?>" data-page="resignations" onclick="window.location.href='index.php?page=resignations'">
                    <i class="fas fa-user-minus"></i> <span>Resignations</span>
                </button>
                <button class="nav-btn <?php echo $page === 'reports' ? 'active' : ''; ?>" data-page="reports" onclick="window.location.href='index.php?page=reports'">
                    <i class="fas fa-chart-bar"></i> <span>Reports</span>
                </button>
                <button class="nav-btn <?php echo $page === 'settings' ? 'active' : ''; ?>" data-page="settings" onclick="window.location.href='index.php?page=settings'">
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
                            <span class="name"><?php echo $full_name; ?></span>
                            <span class="role"><?php echo $role; ?> Admin</span>
                        </div>
                    </div>
                </div>
            </header>

            <div id="content-pages">
                <!-- Sections for Dashboard, Employees, etc. (re-used from index.html) -->
                <!-- The existing sections will be injected or kept based on PHP logic -->
                <?php include __DIR__ . '/pages/admin_hr/pages/' . $page . '.php'; ?>
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
