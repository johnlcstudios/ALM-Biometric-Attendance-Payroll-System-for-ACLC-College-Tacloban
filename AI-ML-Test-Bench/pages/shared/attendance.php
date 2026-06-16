<section id="attendance" class="page">
    <div class="attendance-summary-cards">
        <div class="att-stat-card">
            <div class="att-stat-icon blue"><i class="fas fa-users"></i></div>
            <div class="att-stat-content">
                <span class="att-stat-label">Total Logs Today</span>
                <span class="att-stat-value" id="att-total-logs">0</span>
            </div>
        </div>
        <div class="att-stat-card">
            <div class="att-stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="att-stat-content">
                <span class="att-stat-label">On-Time</span>
                <span class="att-stat-value text-success" id="att-ontime-count">0</span>
            </div>
        </div>
        <div class="att-stat-card">
            <div class="att-stat-icon orange"><i class="fas fa-clock"></i></div>
            <div class="att-stat-content">
                <span class="att-stat-label">Late Arrivals</span>
                <span class="att-stat-value text-warning" id="att-late-count">0</span>
            </div>
        </div>
        <div class="att-stat-card">
            <div class="att-stat-icon red"><i class="fas fa-exclamation-circle"></i></div>
            <div class="att-stat-content">
                <span class="att-stat-label">Absences</span>
                <span class="att-stat-value text-danger" id="att-absent-count">0</span>
            </div>
        </div>
    </div>

    <div class="table-container modern-table-wrapper">
        <div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
            <button class="btn btn-primary" onclick="addAttendance()"><i class="fas fa-plus"></i> Add Attendance</button>
        </div>
        <table id="attendanceTable" class="modern-table">
            <thead>
                <tr>
                    <th>EMPLOYEE</th>
                    <th>DATE</th>
                    <th>CHECK-IN</th>
                    <th>LUNCH-OUT</th>
                    <th>LUNCH-IN</th>
                    <th>CHECK-OUT</th>
                    <th>TOTAL HRS</th>
                    <th>STATUS</th>
                    <th>SCHEDULE</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody id="attendanceTableBody">
            </tbody>
        </table>
    </div>
</section>
