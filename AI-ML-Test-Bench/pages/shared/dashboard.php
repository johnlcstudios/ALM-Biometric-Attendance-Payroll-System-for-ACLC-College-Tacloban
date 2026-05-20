<section id="dashboard" class="page active">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3>Total Employees</h3>
                <p class="stat-value" id="stat-total-emp">0</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-user-check"></i></div>
            <div class="stat-info">
                <h3>Present Today</h3>
                <p class="stat-value" id="stat-present">0</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-user-times"></i></div>
            <div class="stat-info">
                <h3>Absent</h3>
                <p class="stat-value" id="stat-absent">0</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <h3>Pending Leave</h3>
                <p class="stat-value" id="stat-leave">0</p>
            </div>
        </div>
    </div>

    <div class="charts-container">
        <div class="chart-card">
            <h3>Payroll Expenditure (Last 6 Months)</h3>
            <canvas id="payrollChart"></canvas>
        </div>
        <div class="chart-card doughnut">
            <h3>Attendance Breakdown</h3>
            <canvas id="attendanceChart"></canvas>
        </div>
        <div class="chart-card full-width" style="margin-top:0;">
            <h3>Monthly Attendance Trends</h3>
            <canvas id="monthlyTrendsChart"></canvas>
        </div>
    </div>
</section>
