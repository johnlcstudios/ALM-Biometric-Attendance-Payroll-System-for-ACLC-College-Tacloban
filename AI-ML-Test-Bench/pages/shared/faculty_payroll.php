<section id="faculty_payroll" class="page">
    <div class="payroll-header faculty-payroll-header">
        <div class="header-left">
            <h2>FACULTY PAYROLL</h2>
            <div class="payroll-info-text">
                <p><strong>Payroll Period:</strong> <span id="faculty-payroll-period">---</span></p>
                <p><strong>Cut-off Period:</strong> <span id="faculty-cutoff-period">---</span></p>
            </div>
        </div>
        <div class="header-right" style="display: flex; gap: 10px;">
            <button class="btn btn-secondary" onclick="printSpecializedPayroll('facultyPayrollTable', 'FACULTY PAYROLL')">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="btn btn-success" onclick="exportFacultyPayroll()">
                <i class="fas fa-file-excel"></i> Export
            </button>
            <!-- <button class="btn-process-payroll" onclick="showRunFacultyPayroll()">
                Run Faculty Payroll
            </button> -->
        </div>
    </div>

    <div class="table-container faculty-table-container">
        <table id="facultyPayrollTable" class="payroll-table faculty-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Basic Pay</th>
                    <th>Earned for the Period</th>
                    <th>Load</th>
                    <th>Over Time</th>
                    <th>Differential</th>
                    <th>Substitution</th>
                    <th>Adj. (+)</th>
                    <th>Absences</th>
                    <th>Latest/UT</th>
                    <th>HDMF Cont.</th>
                    <th>HDMF Cash Advance</th>
                    <th>HDMF MP2</th>
                    <th>Total Deduction</th>
                    <th>Honorarium</th>
                    <th>Net Pay</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="facultyPayrollTableBody">
            </tbody>
        </table>
    </div>
</section>
