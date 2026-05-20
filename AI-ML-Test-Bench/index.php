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

// Check if this is a fresh login (show splash screen only once per session)
$showSplash = !isset($_SESSION['splash_shown']);
if ($showSplash) {
    $_SESSION['splash_shown'] = true;
}

$allowed_pages = [
    'dashboard',
    'employees',
    'biometrics',
    'attendance',
    'payroll',
    'payroll_specialized',
    'allowances',
    'deductions',
    'leave',
    'loans',
    'resignations',
    'reports',
    'assign_payroll',
    'settings',
    'profile'
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
    <title>Payroll System Hub - <?php echo htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?></title>
    
    <!-- Google Fonts - Inter (with local fallback) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" onerror="this.href='css/inter-fonts.css'">
    
    <!-- Font Awesome (local with CDN fallback) -->
    <link rel="stylesheet" href="css/all.min.css" onerror="this.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'">
    
    <!-- JavaScript Libraries -->
    <script src="js/chart.min.js"></script>
    <script src="js/face-api.min.js"></script>
    <script src="js/face-api-manager.js?v=2.1"></script>
    <script src="js/jspdf.umd.min.js"></script>
    <script src="js/jspdf.plugin.autotable.min.js"></script>
    
    <!-- SweetAlert2 (local with CDN fallback) -->
    <script src="js/sweetalert2.all.min.js" onerror="this.src='https://cdn.jsdelivr.net/npm/sweetalert2@11'"></script>
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="css/style.css?v=2.4">
</head>
<body class="role-<?php echo strtolower($role); ?>">
    <?php if ($showSplash): ?>
    <!-- Splash Screen -->
    <div id="splashScreen" style="
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 99999;
        transition: opacity 0.8s ease-out, visibility 0.8s;
    ">
        <div style="
            text-align: center;
            color: white;
            animation: fadeInUp 1s ease-out;
        ">
            <!-- Logo/Icon -->
            <div style="
                width: 100px;
                height: 100px;
                margin: 0 auto 30px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                backdrop-filter: blur(10px);
                border: 3px solid rgba(255, 255, 255, 0.3);
                animation: pulse 2s infinite;
            ">
                <i class="fas fa-fingerprint" style="font-size: 50px;"></i>
            </div>
            
            <!-- System Name -->
            <h1 style="
                font-size: 42px;
                margin-bottom: 10px;
                font-weight: 700;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
                animation: slideIn 1s ease-out;
            ">ALM Biometric System</h1>
            
            <!-- Version -->
            <p style="
                font-size: 18px;
                margin-bottom: 40px;
                opacity: 0.9;
                font-weight: 300;
            ">Version 2.5.0 - Viewport Alpha</p>
            
            <!-- Credits -->
            <div style="
                background: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(10px);
                border-radius: 15px;
                padding: 25px 40px;
                border: 2px solid rgba(255, 255, 255, 0.3);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
                animation: fadeIn 1.5s ease-out;
            ">
                <p style="
                    font-size: 20px;
                    margin-bottom: 10px;
                    font-weight: 600;
                ">
                    <i class="fas fa-heart" style="color: #ff6b6b; animation: heartbeat 1.5s infinite;"></i>
                </p>
                <p style="
                    font-size: 18px;
                    margin: 0;
                    line-height: 1.6;
                    font-weight: 500;
                ">Built with STRESS from</p>
                <p style="
                    font-size: 24px;
                    margin: 10px 0;
                    font-weight: 700;
                    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
                ">BSIT 3A</p>
                <p style="
                    font-size: 16px;
                    margin: 5px 0 0;
                    opacity: 0.9;
                ">A.Y. 2025-2026 | Batch 2027</p>
            </div>
            
            <!-- Loading Indicator -->
            <div style="margin-top: 40px;">
                <div style="
                    width: 50px;
                    height: 50px;
                    margin: 0 auto;
                    border: 4px solid rgba(255, 255, 255, 0.3);
                    border-top: 4px solid white;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                "></div>
                <p style="margin-top: 20px; opacity: 0.9;">Loading dashboard...</p>
            </div>
        </div>
    </div>

    <style>
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-50px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 0 0 20px rgba(255, 255, 255, 0);
        }
    }

    @keyframes heartbeat {
        0%, 100% { transform: scale(1); }
        10%, 30% { transform: scale(1.2); }
        20%, 40% { transform: scale(1); }
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>

    <script>
    // Splash screen timeout
    window.addEventListener('load', function() {
        setTimeout(function() {
            const splash = document.getElementById('splashScreen');
            if (splash) {
                splash.style.opacity = '0';
                splash.style.visibility = 'hidden';
                setTimeout(function() {
                    splash.style.display = 'none';
                }, 800);
            }
        }, 3000); // Show for 3 seconds
    });
    </script>
    <?php endif; ?>

    <div class="app-container">
        <div id="loading-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 999999; display: flex; align-items: center; justify-content: center; pointer-events: auto;">
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
                <button class="nav-btn <?php echo $page === 'payroll_specialized' ? 'active' : ''; ?>" data-page="payroll_specialized" onclick="window.location.href='index.php?page=payroll_specialized'">
                    <i class="fas fa-file-invoice"></i> <span>Specialized Payroll</span>
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
                    <i class="fas fa-hand-holding-usd"></i> <span>Cash Advance</span>
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
                <div class="user-profile-section">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($full_name); ?>&size=40&background=random" alt="Profile" class="user-avatar">
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($full_name); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($role); ?></span>
                    </div>
                    <button class="profile-link-btn" onclick="window.location.href='index.php?page=profile'" title="My Profile">
                        <i class="fas fa-user-circle"></i>
                    </button>
                </div>
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
                    <p class="company-tag"><?php echo htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8'); ?></p>
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
        const USER_ROLE = "<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>";
    </script>
    <script src="js/script.js?v=2.4"></script>
    <script src="js/context-menu.js?v=1.0"></script>
</body>
</html>
