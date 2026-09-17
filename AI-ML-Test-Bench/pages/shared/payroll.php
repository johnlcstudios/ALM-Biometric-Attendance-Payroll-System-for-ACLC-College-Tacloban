<section id="payroll" class="page">
    <div class="payroll-header">
        <div class="header-left">
            <h2>Payroll History</h2>
            <p>View and manage past payroll disbursements and processing runs.</p>
        </div>
<div class="header-right">

            <div class="dropdown">
                <button type="button" class="btn btn-secondary dropdown-toggle" aria-label="Print Payroll History" onclick="printPayrollHistory(); return false;">
                    <i class="fas fa-print" aria-hidden="true"></i> Print Payroll History
                </button>
            </div>
            <div class="dropdown">
                <button type="button" class="btn btn-success dropdown-toggle" aria-label="Export Payroll History" onclick="exportPayrollHistory(); return false;">
                    <i class="fas fa-download" aria-hidden="true"></i> Export Payroll History
                </button>
            </div>
            
            <!-- NEW BULK OPERATIONS BUTTONS -->
            <div class="bulk-actions">
                <button type="button" class="btn btn-warning" onclick="bulkSalaryAdjustment(1.05)" title="+5% Salary Boost" aria-label="Increase salary by 5 percent for all employees">
                    <i class="fas fa-percentage" aria-hidden="true"></i> +5% All
                </button>
                <button type="button" class="btn btn-info" onclick="bulkSalaryAdjustment(0.95)" title="-5% Salary Cut" aria-label="Decrease salary by 5 percent for all employees">
                    <i class="fas fa-minus" aria-hidden="true"></i> -5% All
                </button>
                <button type="button" class="btn btn-primary" onclick="selectAllPayrollRows()" aria-label="Select all payroll rows">
                    <i class="fas fa-check-square" aria-hidden="true"></i> Select All
                </button>
                <button type="button" class="btn btn-secondary" onclick="bulkUpdateSelected()" aria-label="Apply bulk update to selected rows">
                    <i class="fas fa-sync" aria-hidden="true"></i> Apply Bulk
                </button>
            </div>
            
            <button type="button" class="btn-process-payroll" onclick="showPayrollModal()" aria-label="Process New Payroll">
                + Process New Payroll
            </button>
        <link rel="stylesheet" href="../css/responsive-payroll.css">
    </div>
</section>

<style>
/* Local overrides for payroll page */
.bulk-actions { margin: 12px 0 !important; display: flex; gap: 12px !important; flex-wrap: wrap !important; }
.payroll-stats { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px !important; }
</style>

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
                    <th scope="col">PAYROLL</th>
                    <th scope="col">PERIOD</th>
                    <th scope="col">TOTAL DISBURSED</th>
                    <th scope="col">PROCESSING DATE</th>
                    <th scope="col">CREATED BY</th>
                    <th scope="col">STATUS</th>
                    <th scope="col">ACTION</th>
                </tr>
            </thead>
            <tbody id="payrollTableBody">
            </tbody>
        </table>
    </div>
</section>
