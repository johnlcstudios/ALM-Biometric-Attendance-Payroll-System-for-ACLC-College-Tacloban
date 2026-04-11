<section id="reports" class="page">
    <div class="reports-grid">
        <div class="report-card" onclick="generateReport('attendance')">
            <i class="fas fa-file-invoice"></i>
            <h4>Attendance Summary</h4>
            <p>Generate a monthly report of attendance for all staff.</p>
        </div>
        <div class="report-card" onclick="generateReport('payroll')">
            <i class="fas fa-file-invoice-dollar"></i>
            <h4>Payroll History</h4>
            <p>Detailed breakdown of past payroll cycles and expenses.</p>
        </div>
        <div class="report-card" onclick="generateReport('employee')">
            <i class="fas fa-users"></i>
            <h4>Employee Records</h4>
            <p>Full database export of employee records and contact info.</p>
        </div>
        <div class="report-card" onclick="generateReport('leave')">
            <i class="fas fa-calendar-alt"></i>
            <h4>Leave Analysis</h4>
            <p>Trends and totals for employee leave and absences.</p>
        </div>
    </div>
</section>
