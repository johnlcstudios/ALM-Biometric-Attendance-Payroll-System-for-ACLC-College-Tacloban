<section id="payroll" class="page">
    <div class="payroll-header">
        <div class="header-left">
            <h2>Payroll History</h2>
            <p>View and manage past payroll disbursements and processing runs.</p>
        </div>
<div class="header-right">

            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" onclick="printPayrollHistory(); return false;">
                    <i class="fas fa-print"></i> Print Payroll History
                </button>
            </div>
            <div class="dropdown">
                <button class="btn btn-success dropdown-toggle" onclick="exportPayrollHistory(); return false;">
                    <i class="fas fa-download"></i> Export Payroll History
                </button>
            </div>

            
            <button class="btn-process-payroll" onclick="showPayrollModal()">
                + Process New Payroll
            </button>
        <link rel="stylesheet" href="../css/responsive-payroll.css">
    </div>
</section>

<style>
/* Local overrides for payroll page */
</style>



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
