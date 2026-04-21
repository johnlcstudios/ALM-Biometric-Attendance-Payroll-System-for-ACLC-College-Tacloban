<?php
/**
 * Workforce Analytics - Employee Insights with Company Isolation
 */
require_once 'config.php';
require_once 'header.php';

// Fetch workforce analytics data
try {
    // Total employees by status
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM employees 
        WHERE company_id = ?
        GROUP BY status
    ");
    $stmt->execute([$company_id]);
    $employee_status = $stmt->fetchAll();
    
    // Employees by department
    $stmt = $pdo->prepare("
        SELECT department, COUNT(*) as count 
        FROM employees 
        WHERE company_id = ? AND department IS NOT NULL
        GROUP BY department
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute([$company_id]);
    $department_data = $stmt->fetchAll();
    
    // Employees by position
    $stmt = $pdo->prepare("
        SELECT position, COUNT(*) as count 
        FROM employees 
        WHERE company_id = ? AND position IS NOT NULL
        GROUP BY position
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute([$company_id]);
    $position_data = $stmt->fetchAll();
    
    // Average attendance rate
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN check_in IS NOT NULL THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0) as avg_attendance
        FROM attendance 
        WHERE company_id = ? 
        AND log_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$company_id]);
    $avg_attendance = round($stmt->fetch()['avg_attendance'] ?? 0, 1);
    
    // Gender distribution
    $stmt = $pdo->prepare("
        SELECT gender, COUNT(*) as count 
        FROM employees 
        WHERE company_id = ? AND gender IS NOT NULL
        GROUP BY gender
    ");
    $stmt->execute([$company_id]);
    $gender_data = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Failed to load workforce analytics: " . $e->getMessage();
}
?>

<section id="workforce-analytics">
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #1e0178, #2d0a8f); color: white; padding: 20px; border-radius: 12px; text-align: center;">
                    <i class="fas fa-users fa-2x"></i>
                </div>
                <div class="stat-info mt-3">
                    <h3>Total Workforce</h3>
                    <p class="stat-value" style="font-size: 2rem; font-weight: 700; color: #1e0178;">
                        <?php echo array_sum(array_column($employee_status, 'count')); ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71); color: white; padding: 20px; border-radius: 12px; text-align: center;">
                    <i class="fas fa-building fa-2x"></i>
                </div>
                <div class="stat-info mt-3">
                    <h3>Departments</h3>
                    <p class="stat-value" style="font-size: 2rem; font-weight: 700; color: #27ae60;">
                        <?php echo count($department_data); ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white; padding: 20px; border-radius: 12px; text-align: center;">
                    <i class="fas fa-calendar-check fa-2x"></i>
                </div>
                <div class="stat-info mt-3">
                    <h3>Avg Attendance</h3>
                    <p class="stat-value" style="font-size: 2rem; font-weight: 700; color: #f39c12;">
                        <?php echo $avg_attendance; ?>%
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #17a2b8, #138496); color: white; padding: 20px; border-radius: 12px; text-align: center;">
                    <i class="fas fa-briefcase fa-2x"></i>
                </div>
                <div class="stat-info mt-3">
                    <h3>Positions</h3>
                    <p class="stat-value" style="font-size: 2rem; font-weight: 700; color: #17a2b8;">
                        <?php echo count($position_data); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-building me-2"></i>Employees by Department</h5>
                </div>
                <div class="card-body">
                    <canvas id="departmentChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-briefcase me-2"></i>Employees by Position</h5>
                </div>
                <div class="card-body">
                    <canvas id="positionChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-venus-mars me-2"></i>Gender Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="genderChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie me-2"></i>Employee Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Department Chart
    new Chart(document.getElementById('departmentChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($department_data, 'department')); ?>,
            datasets: [{
                label: 'Employees',
                data: <?php echo json_encode(array_column($department_data, 'count')); ?>,
                backgroundColor: 'rgba(30, 1, 120, 0.8)',
                borderRadius: 8
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    // Position Chart
    new Chart(document.getElementById('positionChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($position_data, 'position')); ?>,
            datasets: [{
                label: 'Employees',
                data: <?php echo json_encode(array_column($position_data, 'count')); ?>,
                backgroundColor: 'rgba(231, 29, 54, 0.8)',
                borderRadius: 8
            }]
        },
        options: { 
            responsive: true, 
            plugins: { legend: { display: false } },
            indexAxis: 'y'
        }
    });

    // Gender Chart
    new Chart(document.getElementById('genderChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($gender_data, 'gender')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($gender_data, 'count')); ?>,
                backgroundColor: ['#1e0178', '#e71d36', '#f39c12']
            }]
        },
        options: { responsive: true }
    });

    // Status Chart
    new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_column($employee_status, 'status')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($employee_status, 'count')); ?>,
                backgroundColor: ['#27ae60', '#dc3545', '#f39c12']
            }]
        },
        options: { responsive: true }
    });
});
</script>

<?php require_once 'footer.php'; ?>
