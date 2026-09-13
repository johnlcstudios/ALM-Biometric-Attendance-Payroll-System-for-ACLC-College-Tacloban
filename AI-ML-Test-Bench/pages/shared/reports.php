<section id="reports" class="page">
    <div class="reports-grid">
        <button type="button" class="report-card" onclick="generateReport('attendance')" aria-label="Generate Attendance Summary report">
            <i class="fas fa-file-invoice" aria-hidden="true"></i>
            <h4>Attendance Summary</h4>
            <p>Generate a monthly report of attendance for all staff.</p>
        </button>
        <button type="button" class="report-card" onclick="generateReport('payroll')" aria-label="Generate Payroll History report">
            <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>
            <h4>Payroll History</h4>
            <p>Detailed breakdown of past payroll cycles and expenses.</p>
        </button>
        <button type="button" class="report-card" onclick="generateReport('employee')" aria-label="Generate Employee Records report">
            <i class="fas fa-users" aria-hidden="true"></i>
            <h4>Employee Records</h4>
            <p>Full database export of employee records and contact info.</p>
        </button>
        <button type="button" class="report-card" onclick="generateReport('leave')" aria-label="Generate Leave Analysis report">
            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
            <h4>Leave Analysis</h4>
            <p>Trends and totals for employee leave and absences.</p>
        </button>
    </div>
</section>
