<?php
// Payroll Officer Sidebar
?>
<aside class="sidebar payroll-officer-sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img src="assets/logo.jpg" alt="Logo" class="sidebar-logo">
            <span>Payroll Officer</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <button class="nav-btn <?php echo $page === 'dashboard' ? 'active' : ''; ?>" <?php echo $page === 'dashboard' ? 'aria-current="page"' : ''; ?> data-page="dashboard" onclick="window.location.href='index.php?page=dashboard'">
            <i class="fas fa-th-large" aria-hidden="true"></i> <span>Dashboard</span>
        </button>
        <button class="nav-btn <?php echo $page === 'employees' ? 'active' : ''; ?>" <?php echo $page === 'employees' ? 'aria-current="page"' : ''; ?> data-page="employees" onclick="window.location.href='index.php?page=employees'">
            <i class="fas fa-users" aria-hidden="true"></i> <span>Employees</span>
        </button>
        <button class="nav-btn <?php echo $page === 'biometrics' ? 'active' : ''; ?>" <?php echo $page === 'biometrics' ? 'aria-current="page"' : ''; ?> data-page="biometrics" onclick="window.location.href='index.php?page=biometrics'">
            <i class="fas fa-id-card" aria-hidden="true"></i> <span>Face Registration</span>
        </button>
        <button class="nav-btn <?php echo $page === 'attendance' ? 'active' : ''; ?>" <?php echo $page === 'attendance' ? 'aria-current="page"' : ''; ?> data-page="attendance" onclick="window.location.href='index.php?page=attendance'">
            <i class="fas fa-calendar-alt" aria-hidden="true"></i> <span>Attendance Logs</span>
        </button>
        <button class="nav-btn <?php echo $page === 'payroll' ? 'active' : ''; ?>" <?php echo $page === 'payroll' ? 'aria-current="page"' : ''; ?> data-page="payroll" onclick="window.location.href='index.php?page=payroll'">
            <i class="fas fa-file-invoice-dollar" aria-hidden="true"></i> <span>Payroll</span>
        </button>
        <button class="nav-btn <?php echo $page === 'faculty_payroll' ? 'active' : ''; ?>" <?php echo $page === 'faculty_payroll' ? 'aria-current="page"' : ''; ?> data-page="faculty_payroll" onclick="window.location.href='index.php?page=faculty_payroll'">
            <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i> <span>Faculty Payroll</span>
        </button>
        <button class="nav-btn <?php echo $page === 'utility_payroll' ? 'active' : ''; ?>" <?php echo $page === 'utility_payroll' ? 'aria-current="page"' : ''; ?> data-page="utility_payroll" onclick="window.location.href='index.php?page=utility_payroll'">
            <i class="fas fa-tools" aria-hidden="true"></i> <span>Utility Payroll</span>
        </button>
        <button class="nav-btn <?php echo $page === 'allowances' ? 'active' : ''; ?>" <?php echo $page === 'allowances' ? 'aria-current="page"' : ''; ?> data-page="allowances" onclick="window.location.href='index.php?page=allowances'">
            <i class="fas fa-coins" aria-hidden="true"></i> <span>Allowances</span>
        </button>
        <button class="nav-btn <?php echo $page === 'deductions' ? 'active' : ''; ?>" <?php echo $page === 'deductions' ? 'aria-current="page"' : ''; ?> data-page="deductions" onclick="window.location.href='index.php?page=deductions'">
            <i class="fas fa-calculator" aria-hidden="true"></i> <span>Deductions</span>
        </button>
        <button class="nav-btn <?php echo $page === 'leave' ? 'active' : ''; ?>" <?php echo $page === 'leave' ? 'aria-current="page"' : ''; ?> data-page="leave" onclick="window.location.href='index.php?page=leave'">
            <i class="fas fa-calendar-check" aria-hidden="true"></i> <span>Leave Requests</span>
        </button>
        <button class="nav-btn <?php echo $page === 'loans' ? 'active' : ''; ?>" <?php echo $page === 'loans' ? 'aria-current="page"' : ''; ?> data-page="loans" onclick="window.location.href='index.php?page=loans'">
            <i class="fas fa-hand-holding-usd" aria-hidden="true"></i> <span>Cash Advance</span>
        </button>
        <button class="nav-btn <?php echo $page === 'resignations' ? 'active' : ''; ?>" <?php echo $page === 'resignations' ? 'aria-current="page"' : ''; ?> data-page="resignations" onclick="window.location.href='index.php?page=resignations'">
            <i class="fas fa-user-minus" aria-hidden="true"></i> <span>Resignations</span>
        </button>
        <button class="nav-btn <?php echo $page === 'reports' ? 'active' : ''; ?>" <?php echo $page === 'reports' ? 'aria-current="page"' : ''; ?> data-page="reports" onclick="window.location.href='index.php?page=reports'">
            <i class="fas fa-chart-bar" aria-hidden="true"></i> <span>Reports</span>
        </button>
        <button class="nav-btn <?php echo $page === 'settings' ? 'active' : ''; ?>" <?php echo $page === 'settings' ? 'aria-current="page"' : ''; ?> data-page="settings" onclick="window.location.href='index.php?page=settings'">
            <i class="fas fa-cog" aria-hidden="true"></i> <span>Settings</span>
        </button>
    </nav>
    <div class="sidebar-footer">
        <button class="nav-btn logout" onclick="logout()">
            <i class="fas fa-sign-out-alt" aria-hidden="true"></i> <span>Logout</span>
        </button>
    </div>
</aside>
