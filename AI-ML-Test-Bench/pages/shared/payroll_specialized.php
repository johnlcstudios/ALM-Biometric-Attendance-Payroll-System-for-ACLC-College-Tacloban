<section id="payroll_specialized" class="page">
    <div class="payroll-header faculty-payroll-header">
        <div class="header-left">
            <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                <h2 style="margin: 0;">SPECIALIZED PAYROLL</h2>
                <select id="payroll-type-select" class="payroll-type-dropdown" onchange="switchPayrollType(this.value)" style="padding: 8px 14px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; font-weight: 600; background: white; cursor: pointer;">
                    <option value="faculty">Faculty Payroll</option>
                    <option value="utility">Utility Payroll</option>
                </select>
            </div>
            <div class="payroll-info-text">
                <p><strong>Payroll Period:</strong> <span id="specialized-payroll-period">---</span></p>
                <p><strong>Cut-off Period:</strong> <span id="specialized-cutoff-period">---</span></p>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 8px;">
                <button class="btn btn-secondary" onclick="printSpecializedPayroll(currentPayrollTableId, currentPayrollTitle)">
                    <i class="fas fa-print"></i> Print
                </button>
                <button class="btn btn-success" onclick="exportCurrentPayroll()">
                    <i class="fas fa-file-excel"></i> Export
                </button>
            </div>
            <span id="faculty-payroll-period" style="display:none"></span>
            <span id="utility-payroll-period" style="display:none"></span>
        </div>
        <div class="header-right">
        </div>
    </div>

    <!-- Faculty Payroll Table -->
    <div id="faculty-payroll-section" class="table-container faculty-table-container">
        <table id="facultyPayrollTable" class="payroll-table faculty-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Basic Pay</th>
                    <th>Gross Pay</th>
                    <th>Load</th>
                    <th>Over Time</th>
                    <th>Differential</th>
                    <th>Substitution</th>
                    <th>Adj. (+)</th>
                    <th>Absences</th>
                    <th>Late/UT</th>
                    <th>HDMF Cont.</th>
                    <th>HDMF Cash Advance</th>
                    <th>HDMF MP2</th>
                    <th>Gov. Contributions</th>
                    <th>Total Deduction</th>
                    <th>Honorarium</th>
                    <th>Net Pay</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="facultyPayrollTableBody"></tbody>
        </table>
    </div>

    <!-- Utility Payroll Table -->
    <div id="utility-payroll-section" class="table-container faculty-table-container" style="display: none;">
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
            <tbody id="utilityPayrollTableBody"></tbody>
        </table>
    </div>
</section>
