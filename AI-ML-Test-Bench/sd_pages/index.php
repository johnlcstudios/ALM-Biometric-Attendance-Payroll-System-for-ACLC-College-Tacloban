<?php
/**
 * SD Pages - Main Dashboard Index
 */
require_once 'config.php';
require_once 'header.php';

// Fetch dashboard statistics with company isolation
try {
    // Total employees
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM employees WHERE status = 'Active' AND company_id = ?");
    $stmt->execute([$company_id]);
    $total_employees = $stmt->fetch()['total'] ?? 0;
    
    // Present today
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT employee_id) as total FROM attendance WHERE log_date = CURDATE() AND check_in IS NOT NULL AND company_id = ?");
    $stmt->execute([$company_id]);
    $present_today = $stmt->fetch()['total'] ?? 0;
    
    // Absent today
    $absent_today = max(0, $total_employees - $present_today);
    
    // Punctuality rate
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN TIME(check_in) <= '08:00:00' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0) as punctuality
        FROM attendance 
        WHERE log_date = CURDATE() AND check_in IS NOT NULL AND company_id = ?
    ");
    $stmt->execute([$company_id]);
    $punctuality = round($stmt->fetch()['punctuality'] ?? 0);
    
    // Total HR users
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'HR' AND is_active = 1 AND company_id = ?");
    $stmt->execute([$company_id]);
    $total_hr = $stmt->fetch()['total'] ?? 0;
    
    // Monthly payroll
    $stmt = $pdo->prepare("SELECT SUM(net_pay) as total FROM payroll WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND company_id = ?");
    $stmt->execute([$company_id]);
    $monthly_payroll = $stmt->fetch()['total'] ?? 0;
    
    // Department stats
    $stmt = $pdo->prepare("
        SELECT department, COUNT(*) as total 
        FROM employees 
        WHERE status = 'Active' AND department IS NOT NULL AND company_id = ?
        GROUP BY department 
        ORDER BY total DESC
        LIMIT 10
    ");
    $stmt->execute([$company_id]);
    $department_stats = $stmt->fetchAll();
    
    // Recent attendance
    $stmt = $pdo->prepare("
        SELECT a.*, e.full_name 
        FROM attendance a 
        LEFT JOIN employees e ON a.employee_id = e.id 
        WHERE a.company_id = ?
        ORDER BY a.log_date DESC, a.check_in DESC 
        LIMIT 10
    ");
    $stmt->execute([$company_id]);
    $recent_activity = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Failed to load dashboard data: " . $e->getMessage();
}
?>

<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #1e0178, #2d0a8f); color: white; padding: 20px; border-radius: 12px; text-align: center;">
            <i class="fas fa-users fa-2x"></i>
        </div>
        <div class="stat-info">
            <h3>Total Employees</h3>
            <p class="stat-value" style="font-size: 2rem; font-weight: 700; color: #1e0178;"><?php echo number_format($total_employees); ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; padding: 20px; border-radius: 12px; text-align: center;">
            <i class="fas fa-user-check fa-2x"></i>
        </div>
        <div class="stat-info">
            <h3>Present Today</h3>
            <p class="stat-value" style="font-size: 2rem; font-weight: 700; color: #27ae60;"><?php echo number_format($present_today); ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #dc3545, #e74c3c); color: white; padding: 20px; border-radius: 12px; text-align: center;">
            <i class="fas fa-user-times fa-2x"></i>
        </div>
        <div class="stat-info">
            <h3>Absent Today</h3>
            <p class="stat-value" style="font-size: 2rem; font-weight: 700; color: #dc3545;"><?php echo number_format($absent_today); ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white; padding: 20px; border-radius: 12px; text-align: center;">
            <i class="fas fa-chart-line fa-2x"></i>
        </div>
        <div class="stat-info">
            <h3>Punctuality Score</h3>
            <p class="stat-value" style="font-size: 2rem; font-weight: 700; color: #f39c12;"><?php echo $punctuality; ?>%</p>
        </div>
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-building me-2"></i>Department Overview</h5>
            </div>
            <div class="card-body">
                <canvas id="departmentChart" height="100"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <a href="assign_roles.php" class="btn btn-primary w-100">
                        <i class="fas fa-user-plus me-2"></i>Assign HR/Payroll
                    </a>
                    <a href="executive_dashboard.php" class="btn btn-secondary w-100">
                        <i class="fas fa-chart-line me-2"></i>Executive Dashboard
                    </a>
                    <a href="workforce_analytics.php" class="btn btn-info w-100" style="background: #17a2b8; border-color: #17a2b8;">
                        <i class="fas fa-users-cog me-2"></i>Workforce Analytics
                    </a>
                    <a href="institutional_attendance.php" class="btn btn-warning w-100">
                        <i class="fas fa-calendar-check me-2"></i>Attendance
                    </a>
                    <a href="budget_actual.php" class="btn btn-success w-100">
                        <i class="fas fa-wallet me-2"></i>Budget vs Actual
                    </a>
                    <a href="annual_report.php" class="btn btn-dark w-100">
                        <i class="fas fa-file-alt me-2"></i>Annual Report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5><i class="fas fa-history me-2"></i>Recent Activity</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_activity)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No recent activity</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['full_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($activity['log_date'])); ?></td>
                                <td><?php echo $activity['check_in'] ? date('h:i A', strtotime($activity['check_in'])) : 'N/A'; ?></td>
                                <td><?php echo $activity['check_out'] ? date('h:i A', strtotime($activity['check_out'])) : 'N/A'; ?></td>
                                <td>
                                    <span class="badge <?php echo $activity['status'] == 'Present' ? 'bg-success' : ($activity['status'] == 'Half-Day' ? 'bg-warning' : 'bg-danger'); ?>">
                                        <?php echo $activity['status'] ?? 'N/A'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
});
</script>

<?php require_once 'footer.php'; ?>
