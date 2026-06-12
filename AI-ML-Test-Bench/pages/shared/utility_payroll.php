<section id="utility_payroll" class="page">
    <div class="payroll-header utility-payroll-header">
        <div class="header-left">
            <h2>UTILITY</h2>
            <div class="payroll-info-text">
                <p><strong>Payroll Period:</strong> <span id="utility-payroll-period">---</span></p>
                <p><strong>Cut-off Period:</strong> <span id="utility-cutoff-period">---</span></p>
            </div>
        </div>
        <div class="header-right" style="display: flex; gap: 10px;">
            <button class="btn btn-secondary" onclick="printSpecializedPayroll('utilityPayrollTable', 'UTILITY PAYROLL')">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="btn btn-success" onclick="exportUtilityPayroll()">
                <i class="fas fa-file-excel"></i> Export
            </button>
            <!-- <button class="btn-process-payroll" onclick="showRunUtilityPayroll()">
                Run Utility Payroll
            </button> -->
        </div>
    </div>

    <div class="table-container faculty-table-container">
        <table id="utilityPayrollTable" class="payroll-table faculty-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Rate per Day</th>
                    <th>Gross Pay</th>
                    <th>OT/ Holiday Pay</th>
                    <th>Adj.(+)</th>
                    <th>Late/UT</th>
                    <th>Adj. (-)</th>
                    <th>HDMF Cont.</th>
                    <th>HDMF Cash Advance</th>
                    <th>Cash Advance</th>
                    <th>Gov. Contributions</th>
                    <th>Total Deduction</th>
                    <th>Net Pay</th>
                    <th>ATM</th>
                    <th>Non ATM</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="utilityPayrollTableBody">
            </tbody>
        </table>
    </div>
</section>
