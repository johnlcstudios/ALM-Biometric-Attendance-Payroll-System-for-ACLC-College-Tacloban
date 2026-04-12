<div class="content-section" id="dashboard">
    <h2><i class="fas fa-th-large"></i> Dashboard</h2>
    <div class="dashboard-stats">
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <h3>Total Employees</h3>
            <p id="stat-total-employees">Loading...</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-user-check"></i>
            <h3>Present Today</h3>
            <p id="stat-present-today">Loading...</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-user-times"></i>
            <h3>Absent Today</h3>
            <p id="stat-absent-today">Loading...</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-clock"></i>
            <h3>Pending Leave</h3>
            <p id="stat-pending-leave">Loading...</p>
        </div>
    </div>
    
    <div class="dashboard-charts">
        <div class="chart-container">
            <h3>Attendance Overview</h3>
            <canvas id="attendanceChart"></canvas>
        </div>
        <div class="chart-container">
            <h3>Payroll Summary</h3>
            <canvas id="payrollChart"></canvas>
        </div>
    </div>
</div>

<script>
// Load dashboard stats
async function loadDashboardStats() {
    try {
        const response = await fetch('backend/api.php?action=get_dashboard_stats');
        const stats = await response.json();
        
        document.getElementById('stat-total-employees').textContent = stats.total_employees || 0;
        document.getElementById('stat-present-today').textContent = stats.present_today || 0;
        document.getElementById('stat-absent-today').textContent = stats.absent_today || 0;
        document.getElementById('stat-pending-leave').textContent = stats.pending_leave || 0;
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

loadDashboardStats();
</script>
