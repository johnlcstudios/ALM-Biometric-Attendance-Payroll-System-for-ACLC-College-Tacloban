<section id="payroll" class="page">
    <div class="payroll-header">
        <div class="header-left">
            <h2>Payroll History</h2>
            <p>View and manage past payroll disbursements and processing runs.</p>
        </div>
        <div class="header-right" style="display: flex; gap: 10px;">
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <div class="dropdown-content">
                    <a href="#" onclick="printSpecializedPayroll('facultyPayrollTable', 'FACULTY PAYROLL')">Faculty Payroll</a>
                    <a href="#" onclick="printSpecializedPayroll('utilityPayrollTable', 'UTILITY PAYROLL')">Utility Payroll</a>
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-success dropdown-toggle">
                    <i class="fas fa-download"></i> Export Excel
                </button>
                <div class="dropdown-content">
                    <a href="#" onclick="exportFacultyPayroll()">Faculty Payroll</a>
                    <a href="#" onclick="exportUtilityPayroll()">Utility Payroll</a>
                </div>
            </div>
            <button class="btn-process-payroll" onclick="showPayrollModal()">
                + Process New Payroll
            </button>
        </div>
    </div>

    <div class="payroll-stats">
        <div class="payroll-stat-card">
            <label>TOTAL BATCHES</label>
            <div class="value" id="stat-total-batches">0</div>
        </div>
        <div class="payroll-stat-card">
            <label>TOTAL DISBURSED</label>
            <div class="value" id="stat-total-disbursed">₱0.00</div>
        </div>
        <div class="payroll-stat-card">
            <label>LAST RUN PERIOD</label>
            <div class="value" id="stat-last-run">---</div>
        </div>
        <div class="payroll-stat-card">
            <label>STAFF COUNT (LAST)</label>
            <div class="value" id="stat-last-staff-count">0</div>
        </div>
    </div>

    <div class="payroll-table-container">
        <table id="payrollTable" class="payroll-table">
            <thead>
                <tr>
                    <th>PAYROLL</th>
                    <th>PERIOD</th>
                    <th>TOTAL DISBURSED</th>
                    <th>PROCESSING DATE</th>
                    <th>CREATED BY</th>
                    <th>STATUS</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody id="payrollTableBody">
            </tbody>
        </table>
    </div>
</section>
