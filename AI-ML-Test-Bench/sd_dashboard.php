<?php
/**
 * SD Pages - Analytics Dashboard
 * Admin dashboard with analytics and summary features
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'backend/db.php';

// Check if user is logged in and is Admin/HR
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Only Admin/HR role can access this
$user_role = $_SESSION['role'];
if ($user_role !== 'HR') {
    header('Location: index.php');
    exit;
}

$full_name = $_SESSION['full_name'] ?? 'HR';
$company_name = $_SESSION['company_name'] ?? 'ACLC College Tacloban';
$company_id = $_SESSION['company_id'] ?? 1;

// Initialize variables with default values
$total_employees = 0;
$present_today = 0;
$absent_today = 0;
$total_hr = 0;
$total_payroll = 0;
$monthly_payroll = 0;
$recent_activity = [];
$department_stats = [];
$error = null;

// Fetch analytics data - ALL QUERIES FILTERED BY company_id
try {
    // Total employees
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM employees WHERE status = 'Active' AND company_id = ?");
    $stmt->execute([$company_id]);
    $total_employees = $stmt->fetch()['total'] ?? 0;
    
    // Present today (employees who checked in today)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT employee_id) as total FROM attendance WHERE log_date = CURDATE() AND check_in IS NOT NULL AND company_id = ?");
    $stmt->execute([$company_id]);
    $present_today = $stmt->fetch()['total'] ?? 0;
    
    // Absent today
    $absent_today = max(0, $total_employees - $present_today);
    
    // Total HR users
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'HR' AND is_active = 1 AND company_id = ?");
    $stmt->execute([$company_id]);
    $total_hr = $stmt->fetch()['total'] ?? 0;
    
    // Monthly payroll (current month)
    $stmt = $pdo->prepare("SELECT SUM(net_pay) as total FROM payroll WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND company_id = ?");
    $stmt->execute([$company_id]);
    $monthly_payroll = $stmt->fetch()['total'] ?? 0;
    
    // Recent activity
    $stmt = $pdo->prepare("
        SELECT a.*, e.full_name 
        FROM attendance a 
        LEFT JOIN employees e ON a.employee_id = e.id 
        WHERE a.company_id = ?
        ORDER BY a.log_date DESC, a.check_in DESC 
        LIMIT 5
    ");
    $stmt->execute([$company_id]);
    $recent_activity = $stmt->fetchAll();
    
    // Department stats
    $stmt = $pdo->prepare("
        SELECT department, COUNT(*) as total 
        FROM employees 
        WHERE status = 'Active' AND department IS NOT NULL AND company_id = ?
        GROUP BY department 
        ORDER BY total DESC
    ");
    $stmt->execute([$company_id]);
    $department_stats = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Failed to load analytics data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SD Dashboard - <?php echo htmlspecialchars($company_name); ?></title>
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/chart.min.js"></script>
    <script src="js/sweetalert2.all.min.js"></script>
    <style>
        .sd-dashboard {
            padding: 2rem;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        .sd-header {
            background: linear-gradient(135deg, #1e0178 0%, #2d0a8f 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(30, 1, 120, 0.3);
        }
        
        .sd-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        
        .sd-header p {
            opacity: 0.9;
            margin: 0.5rem 0 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid #1e0178;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        
        .stat-card.green { border-left-color: #27ae60; }
        .stat-card.red { border-left-color: #e71d36; }
        .stat-card.orange { border-left-color: #f39c12; }
        .stat-card.blue { border-left-color: #3498db; }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }
        
        .stat-card .stat-icon { background: linear-gradient(135deg, #1e0178, #2d0a8f); }
        .stat-card.green .stat-icon { background: linear-gradient(135deg, #27ae60, #2ecc71); }
        .stat-card.red .stat-icon { background: linear-gradient(135deg, #e71d36, #dc3545); }
        .stat-card.orange .stat-icon { background: linear-gradient(135deg, #f39c12, #e67e22); }
        .stat-card.blue .stat-icon { background: linear-gradient(135deg, #3498db, #2980b9); }
        
        .stat-label {
            font-size: 0.85rem;
            color: #7f8c8d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .analytics-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .analytics-card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e0178;
            margin: 0 0 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .action-btn {
            background: white;
            border: 2px solid #1e0178;
            color: #1e0178;
            padding: 1rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .action-btn:hover {
            background: #1e0178;
            color: white;
            transform: translateY(-2px);
        }
        
        .action-btn.primary {
            background: linear-gradient(135deg, #1e0178, #2d0a8f);
            color: white;
            border-color: #1e0178;
        }
        
        .action-btn.primary:hover {
            box-shadow: 0 4px 12px rgba(30, 1, 120, 0.4);
        }
        
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .activity-item {
            padding: 1rem 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #95a5a6;
        }
        
        .activity-desc {
            color: #2c3e50;
            font-weight: 500;
            margin: 0.25rem 0;
        }
        
        @media (max-width: 1024px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="sd-dashboard">
        <!-- Header -->
        <div class="sd-header">
            <h1><i class="fas fa-chart-line"></i> HR Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($full_name); ?> | <?php echo htmlspecialchars($company_name); ?></p>
        </div>
        
        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-label">Total Employees</div>
                <div class="stat-value"><?php echo number_format($total_employees); ?></div>
            </div>
            
            <div class="stat-card green">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-label">Present Today</div>
                <div class="stat-value" style="color: #27ae60;"><?php echo number_format($present_today); ?></div>
            </div>
            
            <div class="stat-card red">
                <div class="stat-icon"><i class="fas fa-user-times"></i></div>
                <div class="stat-label">Absent Today</div>
                <div class="stat-value" style="color: #e71d36;"><?php echo number_format($absent_today); ?></div>
            </div>
            
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                <div class="stat-label">HR Staff</div>
                <div class="stat-value" style="color: #f39c12;"><?php echo number_format($total_hr); ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                <div class="stat-label">Monthly Payroll</div>
                <div class="stat-value">₱<?php echo number_format($monthly_payroll, 2); ?></div>
            </div>
        </div>
        
        <!-- Analytics Grid -->
        <div class="analytics-grid">
            <!-- Department Overview -->
            <div class="analytics-card">
                <h3><i class="fas fa-building"></i> Department Overview</h3>
                <canvas id="departmentChart" height="100"></canvas>
            </div>
            
            <!-- Quick Actions -->
            <div class="analytics-card">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="quick-actions">
                    <a href="sd_pages/assign_roles.php" class="action-btn primary">
                        <i class="fas fa-user-plus"></i> Assign HR
                    </a>
                    <a href="sd_pages/assign_roles.php" class="action-btn primary">
                        <i class="fas fa-user-plus"></i> Assign Payroll
                    </a>
                    <a href="sd_pages/executive_dashboard.php" class="action-btn">
                        <i class="fas fa-chart-line"></i> Executive Dashboard
                    </a>
                    <a href="sd_pages/workforce_analytics.php" class="action-btn">
                        <i class="fas fa-users-cog"></i> Workforce Analytics
                    </a>
                    <a href="sd_pages/institutional_attendance.php" class="action-btn">
                        <i class="fas fa-calendar-check"></i> Attendance
                    </a>
                    <a href="sd_pages/budget_actual.php" class="action-btn">
                        <i class="fas fa-wallet"></i> Budget
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="analytics-card">
            <h3><i class="fas fa-history"></i> Recent Activity</h3>
            <ul class="activity-list">
                <?php if (empty($recent_activity)): ?>
                    <li class="activity-item">
                        <div class="activity-desc">No recent activity</div>
                    </li>
                <?php else: ?>
                    <?php foreach ($recent_activity as $activity): ?>
                        <li class="activity-item">
                            <div class="activity-desc">
                                <i class="fas fa-fingerprint"></i> 
                                <?php echo htmlspecialchars($activity['full_name'] ?? 'Unknown'); ?>
                                - Checked in at <?php echo $activity['check_in'] ? date('h:i A', strtotime($activity['check_in'])) : 'N/A'; ?>
                            </div>
                            <div class="activity-time">
                                <?php echo date('M d, Y', strtotime($activity['log_date'])); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <script>
        // Department Chart
        const deptCtx = document.getElementById('departmentChart').getContext('2d');
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($department_stats, 'department')); ?>,
                datasets: [{
                    label: 'Employees',
                    data: <?php echo json_encode(array_column($department_stats, 'total')); ?>,
                    backgroundColor: 'rgba(30, 1, 120, 0.8)',
                    borderColor: '#1e0178',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
    <script src="js/context-menu.js?v=1.0"></script>
</body>
</html>
