<!-- Dashboard Page -->
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
    </div>
</section>

<?php if (in_array($_SESSION['role'], ['HR', 'Admin', 'Payroll'])): ?>
<!-- Employee Management Page -->
<section id="employees" class="page">
    <div class="page-header">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="employeeSearch" placeholder="Search employees..." oninput="filterTable(this, 'employeeTable')">
        </div>
        <button class="btn btn-primary" onclick="openModal('employeeModal')">
            <i class="fas fa-plus"></i> Add Employee
        </button>
    </div>
    <div class="table-container">
        <table id="employeeTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="employeeTableBody">
                <!-- Dynamic Content -->
            </tbody>
        </table>
    </div>
</section>

<!-- Face Enrollment Page -->
<section id="biometrics" class="page">
    <div class="biometrics-container">
        <div class="enrollment-controls">
            <h3>Face Registration</h3>
            <p>Select an employee to link biometric data.</p>
            <div class="form-group">
                <label>Employee Name</label>
                <select id="enrollEmployeeSelect" class="form-control">
                    <option value="">Select Employee...</option>
                </select>
            </div>
            <button id="startEnrollBtn" class="btn btn-primary" onclick="initFaceEnrollment()">
                <i class="fas fa-camera"></i> Start Camera
            </button>
            <button id="captureBtn" class="btn btn-success" style="display:none;" onclick="saveFaceEnrollment()">
                <i class="fas fa-user-plus"></i> Capture & Save
            </button>
        </div>
        <div class="camera-preview">
            <video id="video" width="640" height="480" autoplay muted></video>
            <canvas id="overlay"></canvas>
            <div id="camera-placeholder">
                <i class="fas fa-camera-retro"></i>
                <p>Camera Preview Not Started</p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Attendance Logs Page -->
<section id="attendance" class="page">
    <div class="page-header">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="attendanceSearch" placeholder="Filter attendance..." oninput="filterTable(this, 'attendanceTable')">
        </div>
        <div class="date-filter">
            <input type="date" id="attendanceDateFilter">
        </div>
    </div>
    <div class="table-container">
        <table id="attendanceTable">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Check-In</th>
                    <th>Check-Out</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="attendanceTableBody">
                <!-- Dynamic Content -->
            </tbody>
        </table>
    </div>
</section>

<!-- Payroll Page -->
<section id="payroll" class="page">
    <div class="payroll-header">
        <div class="header-left">
            <h2>Payroll History</h2>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit</p>
        </div>
        <button class="btn-process-payroll" onclick="showPayrollModal()">
            + Process New Payroll
        </button>
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
                <!-- Dynamic Content -->
            </tbody>
        </table>
    </div>
</section>

<!-- Faculty Payroll Page -->
<section id="faculty_payroll" class="page">
    <div class="payroll-header faculty-payroll-header">
        <div class="header-left">
            <h2>FACULTY PAYROLL</h2>
            <div class="payroll-info-text">
                <p><strong>Payroll Period:</strong> <span id="faculty-payroll-period">---</span></p>
                <p><strong>Cut-off Period:</strong> <span id="faculty-cutoff-period">---</span></p>
            </div>
        </div>
        <div class="header-right">
            <button class="btn-process-payroll" onclick="showRunFacultyPayroll()">
                Run Faculty Payroll
            </button>
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
                    <th>HDMF Loans</th>
                    <th>HDMF MP2</th>
                    <th>Total Deduction</th>
                    <th>Honorarium</th>
                    <th>Net Pay</th>
                </tr>
            </thead>
            <tbody id="facultyPayrollTableBody">
                <!-- Dynamic Content -->
            </tbody>
        </table>
    </div>
</section>

<!-- Utility Payroll Page -->
<section id="utility_payroll" class="page">
    <div class="payroll-header utility-payroll-header">
        <div class="header-left">
            <h2>UTILITY</h2>
            <div class="payroll-info-text">
                <p><strong>Payroll Period:</strong> <span id="utility-payroll-period">---</span></p>
                <p><strong>Cut-off Period:</strong> <span id="utility-cutoff-period">---</span></p>
            </div>
        </div>
        <div class="header-right">
            <button class="btn-process-payroll" onclick="showRunUtilityPayroll()">
                Run Utility Payroll
            </button>
        </div>
    </div>

    <div class="table-container faculty-table-container">
        <table id="utilityPayrollTable" class="payroll-table faculty-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Name</th>
                    <th>Rate per Day</th>
                    <th>Earned for the Period</th>
                    <th>OT/ Holiday Pay</th>
                    <th>Adj.(+)</th>
                    <th>Latest/UT</th>
                    <th>Adj. (-)</th>
                    <th>HDMF Cont.</th>
                    <th>HDMF Loans</th>
                    <th>Cash Advance</th>
                    <th>Total Deduction</th>
                    <th>Net Pay</th>
                    <th>ATM</th>
                    <th>Non ATM</th>
                </tr>
            </thead>
            <tbody id="utilityPayrollTableBody">
                <!-- Dynamic Content -->
            </tbody>
        </table>
    </div>
</section>

<!-- Allowances and Earnings Page -->
<section id="allowances" class="page">
    <div class="payroll-header">
        <div class="header-left">
            <h2>Allowances and Earnings</h2>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit</p>
        </div>
    </div>

    <div class="allowances-grid">
        <div class="allowances-column">
            <div class="card allowances-card">
                <h3>Allowance Categories</h3>
                <div class="table-container small-table">
                    <table id="allowanceCategoriesTable" class="payroll-table">
                        <thead>
                            <tr>
                                <th>NAME</th>
                                <th>STD. RATE</th>
                                <th>TYPE</th>
                                <th>RECURRING</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="allowanceCategoriesBody">
                            <!-- Dynamic Content -->
                        </tbody>
                    </table>
                </div>
                
                <div class="add-new-section">
                    <h4>Add New Type</h4>
                    <div class="allowance-form-row">
                        <input type="text" id="allowanceName" placeholder="Name" class="form-control-gray">
                        <select id="allowanceType" class="form-control-gray">
                            <option value="Fixed">Fixed</option>
                            <option value="Percentage">Percentage</option>
                        </select>
                        <input type="number" id="allowanceRate" placeholder="Rate" class="form-control-gray">
                    </div>
                    <div class="allowance-form-row">
                        <input type="text" id="allowanceDesc" placeholder="Description" class="form-control-gray">
                        <button class="btn-dark-purple" onclick="addAllowanceCategory()">Add Category</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="allowances-column">
            <div class="card allowances-card">
                <h3>Assign to Employee</h3>
                <div class="assign-form">
                    <div class="form-group-custom">
                        <label>Select Employee</label>
                        <select id="assignEmployeeSelect" class="form-control-large-gray">
                            <option value="">Select Employee...</option>
                            <!-- Dynamic Content -->
                        </select>
                    </div>
                    
                    <div class="form-group-custom">
                        <label>Deduction Types</label>
                        <div class="selection-box-gray" id="allowanceTypesList">
                            <!-- Dynamic List -->
                        </div>
                    </div>

                    <div class="form-row-custom">
                        <div class="form-group-custom">
                            <label>Override Amount</label>
                            <input type="number" id="overrideAmount" class="form-control-gray">
                        </div>
                        <div class="form-group-custom">
                            <label>Effective Date</label>
                            <input type="date" id="effectiveDate" class="form-control-gray">
                        </div>
                    </div>

                    <button class="btn-dark-purple btn-full" onclick="assignAllowance()">Assign Deduction</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card breakdown-card">
        <h3>Employee Allowance Breakdown</h3>
        <div class="table-container">
            <table id="allowanceBreakdownTable" class="payroll-table">
                <thead>
                    <tr>
                        <th>EMPLOYEE</th>
                        <th>BENEFIT/ALLOWANCE</th>
                        <th>AMOUNT</th>
                        <th>EFFECTIVE DATE</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="allowanceBreakdownBody">
                    <!-- Dynamic Content -->
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Leave Management Page -->
<section id="leave" class="page">
    <?php if (in_array($role, ['Admin', 'HR', 'Payroll'])): ?>
    <div class="payroll-header">
        <div class="header-left">
            <h2>Leave Request Management</h2>
            <p>Review and manage employee leave applications.</p>
        </div>
    </div>
    
    <div class="card" style="margin-bottom: 2rem;">
        <h3>Manage Leave Balances</h3>
        <div class="allowance-form-row">
            <select id="leaveBalanceEmployeeSelect" class="form-control-gray">
                <option value="">Select Employee...</option>
                <!-- Dynamic Content -->
            </select>
            <input type="number" id="newLeaveBalance" placeholder="Total Leave Days" class="form-control-gray">
            <button class="btn-dark-purple" onclick="updateLeaveBalance()">Update Balance</button>
        </div>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <table id="leaveTable">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="leaveTableBody">
                <!-- Dynamic Content -->
            </tbody>
        </table>
    </div>
</section>

<!-- Loan Management Page -->
<section id="loans" class="page">
    <div class="table-container">
        <table id="loanTable">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Amount</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="loanTableBody">
                <!-- Dynamic Content -->
            </tbody>
        </table>
    </div>
</section>

<!-- Resignation Management Page -->
<section id="resignations" class="page">
    <div class="table-container">
        <table id="resignationTable">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Effective Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="resignationTableBody">
                <!-- Dynamic Content -->
            </tbody>
        </table>
    </div>
</section>

<?php if (in_array($_SESSION['role'], ['HR', 'Admin', 'Payroll'])): ?>
<!-- Deductions Page -->
<section id="deductions" class="page">
    <div class="payroll-header">
        <div class="header-left">
            <h2>Deductions Configuration</h2>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit</p>
        </div>
    </div>

    <div class="allowances-grid">
        <div class="allowances-column">
            <div class="card allowances-card">
                <h3>Deduction Categories</h3>
                <div class="table-container small-table">
                    <table id="deductionCategoriesTable" class="payroll-table">
                        <thead>
                            <tr>
                                <th>NAME</th>
                                <th>STD. RATE</th>
                                <th>TYPE</th>
                                <th>RECURRING</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="deductionCategoriesBody">
                            <!-- Dynamic Content -->
                        </tbody>
                    </table>
                </div>
                
                <div class="add-new-section">
                    <h4>Add New Type</h4>
                    <div class="allowance-form-row">
                        <input type="text" id="deductionName" placeholder="Name" class="form-control-gray">
                        <select id="deductionType" class="form-control-gray">
                            <option value="Fixed">Fixed</option>
                            <option value="Percentage">Percentage</option>
                        </select>
                        <input type="number" id="deductionRate" placeholder="Rate" class="form-control-gray">
                    </div>
                    <div class="allowance-form-row">
                        <input type="text" id="deductionDesc" placeholder="Description" class="form-control-gray">
                        <button class="btn-dark-purple" onclick="addDeductionCategory()">Add Category</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="allowances-column">
            <div class="card allowances-card">
                <h3>Assign to Employee</h3>
                <div class="assign-form">
                    <div class="form-group-custom">
                        <label>Select Employee</label>
                        <select id="assignDeductionEmployeeSelect" class="form-control-large-gray">
                            <option value="">Select Employee...</option>
                            <!-- Dynamic Content -->
                        </select>
                    </div>
                    
                    <div class="form-group-custom">
                        <label>Deduction Types</label>
                        <div class="selection-box-gray" id="deductionTypesList">
                            <!-- Dynamic List -->
                        </div>
                    </div>

                    <div class="form-row-custom">
                        <div class="form-group-custom">
                            <label>Override Amount</label>
                            <input type="number" id="deductionOverrideAmount" class="form-control-gray">
                        </div>
                        <div class="form-group-custom">
                            <label>Effective Date</label>
                            <input type="date" id="deductionEffectiveDate" class="form-control-gray">
                        </div>
                    </div>

                    <button class="btn-dark-purple btn-full" onclick="assignDeduction()">Assign Deduction</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card breakdown-card">
        <h3>Employee Deduction Breakdown</h3>
        <div class="table-container">
            <table id="deductionBreakdownTable" class="payroll-table">
                <thead>
                    <tr>
                        <th>EMPLOYEE</th>
                        <th>DEDUCTION</th>
                        <th>AMOUNT</th>
                        <th>EFFECTIVE DATE</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="deductionBreakdownBody">
                    <!-- Dynamic Content -->
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Reports Page -->
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

<?php
// Fetch company settings
$stmt_company = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmt_company->execute([$_SESSION['company_id']]);
$company = $stmt_company->fetch();
?>
<!-- Subject Loads Page -->
<section id="subject_loads" class="page">
    <div class="payroll-header">
        <div class="header-left">
            <h2>Subject Load Management</h2>
            <p>Assign and manage teaching loads for faculty members.</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('addLoadModal')">
            <i class="fas fa-plus"></i> Add Subject Load
        </button>
    </div>
    
    <div class="card">
        <div class="table-container">
            <table class="payroll-table">
                <thead>
                    <tr>
                        <th>FACULTY NAME</th>
                        <th>SUBJECT CODE</th>
                        <th>DESCRIPTION</th>
                        <th>UNITS</th>
                        <th>HOURS/WEEK</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="subjectLoadsTableBody">
                    <!-- Dynamic Content -->
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Assign Payroll Officer Page -->
<section id="assign_payroll" class="page">
    <div class="payroll-header">
        <div class="header-left">
            <h2>Assign Payroll Officer</h2>
            <p>Grant payroll management access to selected employees.</p>
        </div>
    </div>
    
    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <h3>Set Payroll Officer Role</h3>
        <div class="form-group-custom">
            <label>Select Employee</label>
            <select id="payrollOfficerSelect" class="form-control-large-gray">
                <option value="">Choose Employee...</option>
                <!-- Dynamic Content -->
            </select>
        </div>
        <button class="btn-dark-purple btn-full" onclick="assignPayrollOfficerRole()">Assign Access</button>
        
        <div style="margin-top: 2rem;">
            <h4>Current Officers</h4>
            <ul id="payrollOfficersList" class="selection-box-gray">
                <!-- Dynamic List -->
            </ul>
        </div>
    </div>
</section>

<!-- Settings Page -->
<section id="settings" class="page">
    <div class="settings-container">
        <div class="settings-card">
            <h3>General System Settings</h3>
            <form id="settingsForm">
                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text" name="companyName" value="<?php echo $company['name']; ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Time In (Shift Start)</label>
                        <input type="time" name="workStart" value="<?php echo $company['work_start']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Time Out (Shift End)</label>
                        <input type="time" name="workEnd" value="<?php echo $company['work_end']; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Lunch Out Range</label>
                        <div style="display:flex; gap:5px;">
                            <input type="time" name="lunchOutStart" value="<?php echo $company['lunch_out_start'] ?? '11:30'; ?>">
                            <span>to</span>
                            <input type="time" name="lunchOutEnd" value="<?php echo $company['lunch_out_end'] ?? '12:30'; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Lunch In Range</label>
                        <div style="display:flex; gap:5px;">
                            <input type="time" name="lunchInStart" value="<?php echo $company['lunch_in_start'] ?? '12:30'; ?>">
                            <span>to</span>
                            <input type="time" name="lunchInEnd" value="<?php echo $company['lunch_in_end'] ?? '13:30'; ?>">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Late Grace Period (Minutes)</label>
                        <input type="number" name="gracePeriod" value="<?php echo $company['grace_period'] ?? '15'; ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label>Overtime Percentage (%)</label>
                        <input type="number" name="otPercentage" value="<?php echo $company['ot_percentage'] ?? '25'; ?>" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Deduction Per Second (₱)</label>
                        <input type="number" step="0.0001" name="deductionPerSec" value="<?php echo $company['deduction_per_sec'] ?? '0.0083'; ?>">
                    </div>
                    <div class="form-group">
                        <label>Deduction Per Minute (₱)</label>
                        <input type="number" step="0.01" name="deductionPerMin" value="<?php echo $company['deduction_per_min'] ?? '0.50'; ?>">
                    </div>
                    <div class="form-group">
                        <label>Deduction Per Hour (₱)</label>
                        <input type="number" step="0.01" name="deductionPerHour" value="<?php echo $company['deduction_per_hour'] ?? '30.00'; ?>">
                    </div>
                </div>
                <button type="button" class="btn btn-primary" onclick="saveSettings()">Save Settings</button>
            </form>
        </div>
        
        <?php if ($role === 'Admin' || $role === 'HR'): ?>
        <div class="settings-card">
            <h3>Admin Tools</h3>
            <div class="setting-item">
                <p>Assign Payroll Officer</p>
                <button class="btn btn-primary btn-sm" onclick="showPage('assign_payroll')">Manage Access</button>
            </div>
            <div class="setting-item">
                <p>Manage Subject Loads</p>
                <button class="btn btn-secondary btn-sm" onclick="showPage('subject_loads')">Configure Loads</button>
            </div>
        </div>
        <?php endif; ?>

        <div class="settings-card">
                            <h3>Backup & Security</h3>
                            <div class="setting-item">
                                <p>Attendance Station</p>
                                <a href="kiosk.php?company_id=<?php echo $_SESSION['company_id']; ?>" target="_blank" class="btn btn-primary btn-sm">
                                    Launch Kiosk <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                            <div class="setting-item">
                                <p>Database Backup</p>
                                <button class="btn btn-secondary btn-sm">Download Backup</button>
                            </div>
            <div class="setting-item">
                <p>Admin Password</p>
                <button class="btn btn-secondary btn-sm" onclick="openModal('passwordModal')">Change Password</button>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
