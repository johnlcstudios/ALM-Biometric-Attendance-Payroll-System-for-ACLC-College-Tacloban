<?php
/**
 * SD Pages Header - Authentication & Company Isolation
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is SD/Admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Only Admin/HR role can access this
$user_role = $_SESSION['role'] ?? '';
if ($user_role !== 'HR' && $user_role !== 'Admin') {
    header('Location: ../index.php');
    exit;
}

$full_name = $_SESSION['full_name'] ?? 'HR';
$company_name = $_SESSION['company_name'] ?? 'ACLC College Tacloban';
$company_id = $_SESSION['company_id'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SD Dashboard - <?php echo htmlspecialchars($company_name); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #1e0178;
            --secondary-color: #e71d36;
            --success-color: #27ae60;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --bg-light: #f8f9fa;
            --sidebar-width: 260px;
            --header-height: 70px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            color: #333;
        }

        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* ======================== SIDEBAR ======================== */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-color) 0%, #2d0a8f 100%);
            color: white;
            padding: 0;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .sidebar-brand-icon {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .sidebar-brand h5 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }

        .sidebar-brand small {
            display: block;
            font-size: 0.65rem;
            opacity: 0.8;
            font-weight: 400;
        }

        /* Navigation */
        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-section-title {
            padding: 15px 20px 10px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 0.5px;
        }

        .nav-item {
            margin: 0;
            border: none;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            padding: 12px 20px;
            margin: 5px 10px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: white !important;
            transform: translateX(5px);
        }

        .nav-link.active {
            background-color: var(--secondary-color) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(231, 29, 54, 0.3);
        }

        .nav-submenu {
            display: none;
            padding-left: 25px;
            background-color: rgba(0, 0, 0, 0.1);
        }

        .nav-submenu.show {
            display: block;
        }

        .nav-submenu .nav-link {
            font-size: 0.9rem;
            padding: 8px 20px;
            margin: 2px 10px;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 0 10px;
        }

        .logout-btn {
            width: 100%;
            padding: 12px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: white;
        }

        /* ======================== MAIN CONTENT ======================== */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .top-header {
            height: var(--header-height);
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }

        .user-details h6 {
            margin: 0;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-details small {
            color: #999;
            display: block;
        }

        /* Content Area */
        .content-area {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        /* Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: var(--bg-light);
            border-bottom: 1px solid #e0e0e0;
            border-radius: 12px 12px 0 0;
            padding: 20px;
            font-weight: 600;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        /* Tables */
        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: var(--bg-light);
            border: none;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px;
        }

        .table tbody td {
            padding: 15px;
            border: none;
            border-bottom: 1px solid #f0f0f0;
        }

        .table tbody tr:hover {
            background-color: #f9f9f9;
        }

        /* Buttons */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 20px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #160054;
            border-color: #160054;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-secondary:hover {
            background-color: #c91a2d;
            border-color: #c91a2d;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .content-area {
                padding: 15px;
            }

            .top-header {
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <div class="sidebar-brand-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <h5><?php echo htmlspecialchars($company_name); ?></h5>
                        <small>SD Management Portal</small>
                    </div>
                </div>
            </div>

            <div class="sidebar-nav">
                <!-- Main -->
                <div class="nav-section-title">Main</div>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home"></i> <span>Dashboard</span>
                    </a>
                </li>

                <!-- Financial & Payroll Module -->
                <div class="nav-section-title">Financial & Payroll</div>
                <li class="nav-item">
                    <a class="nav-link" onclick="toggleSubmenu('payroll-submenu')">
                        <i class="fas fa-wallet"></i> <span>Payroll Management</span>
                        <i class="fas fa-chevron-right ms-auto" style="font-size: 0.7rem;"></i>
                    </a>
                    <div class="nav-submenu" id="payroll-submenu">
                        <a class="nav-link" href="sd_pages/budget_actual.php">Budget vs Actual</a>
                        <a class="nav-link" href="sd_pages/payroll_auth.php">Payroll Authorization</a>
                        <a class="nav-link" href="sd_pages/loan_oversight.php">Cash Advance Oversight</a>
                        <a class="nav-link" href="sd_pages/gov_compliance.php">Government Compliance</a>
                    </div>
                </li>

                <!-- Institutional Oversight Module -->
                <div class="nav-section-title">Institutional Oversight</div>
                <li class="nav-item">
                    <a class="nav-link" onclick="toggleSubmenu('oversight-submenu')">
                        <i class="fas fa-building"></i> <span>Institutional Management</span>
                        <i class="fas fa-chevron-right ms-auto" style="font-size: 0.7rem;"></i>
                    </a>
                    <div class="nav-submenu" id="oversight-submenu">
                        <a class="nav-link" href="sd_pages/executive_dashboard.php">Executive Dashboard</a>
                        <a class="nav-link" href="sd_pages/workforce_analytics.php">Workforce Analytics</a>
                        <a class="nav-link" href="sd_pages/institutional_attendance.php">Attendance Tracking</a>
                        <a class="nav-link" href="sd_pages/faculty_load_audit.php">Faculty Load Audit</a>
                        <a class="nav-link" href="sd_pages/assign_roles.php">HR Management</a>
                    </div>
                </li>

                <!-- Reports & Analytics Module -->
                <div class="nav-section-title">Reports & Analytics</div>
                <li class="nav-item">
                    <a class="nav-link" onclick="toggleSubmenu('reports-submenu')">
                        <i class="fas fa-file-chart-line"></i> <span>Reports & Analysis</span>
                        <i class="fas fa-chevron-right ms-auto" style="font-size: 0.7rem;"></i>
                    </a>
                    <div class="nav-submenu" id="reports-submenu">
                        <a class="nav-link" href="sd_pages/annual_report.php">Annual Report</a>
                        <a class="nav-link" href="sd_pages/accreditation.php">Accreditation</a>
                        <a class="nav-link" href="sd_pages/attrition.php">Attrition Analysis</a>
                        <a class="nav-link" href="sd_pages/cost_analysis.php">Cost Analysis</a>
                    </div>
                </li>

                <!-- Security & Governance Module -->
                <div class="nav-section-title">Security & Governance</div>
                <li class="nav-item">
                    <a class="nav-link" onclick="toggleSubmenu('security-submenu')">
                        <i class="fas fa-shield-alt"></i> <span>Security & Governance</span>
                        <i class="fas fa-chevron-right ms-auto" style="font-size: 0.7rem;"></i>
                    </a>
                    <div class="nav-submenu" id="security-submenu">
                        <a class="nav-link" href="sd_pages/access_control.php">Access Control</a>
                        <a class="nav-link" href="sd_pages/audit_log.php">Audit Logs</a>
                        <a class="nav-link" href="sd_pages/compliance.php">Compliance</a>
                        <a class="nav-link" href="sd_pages/settings.php">Settings</a>
                    </div>
                </li>
            </div>

            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Header -->
            <div class="top-header">
                <h1 class="header-title">HR Management Portal</h1>
                <div class="header-actions">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr($full_name, 0, 1)); ?></div>
                        <div class="user-details">
                            <h6><?php echo htmlspecialchars($full_name); ?></h6>
                            <small>HR</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">

<?php
/**
 * Helper function to toggle submenu
 */
function toggleSubmenu($id) {
    echo "<script>
        const submenu = document.getElementById('$id');
        submenu.classList.toggle('show');
    </script>";
}
?>
