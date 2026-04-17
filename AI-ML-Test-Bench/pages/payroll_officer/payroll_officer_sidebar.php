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
        <button class="nav-btn <?php echo $page === 'dashboard' ? 'active' : ''; ?>" data-page="dashboard" onclick="window.location.href='index.php?page=dashboard'">
            <i class="fas fa-th-large"></i> <span>Dashboard</span>
        </button>
        <button class="nav-btn <?php echo $page === 'employees' ? 'active' : ''; ?>" data-page="employees" onclick="window.location.href='index.php?page=employees'">
            <i class="fas fa-users"></i> <span>Employees</span>
        </button>
        <button class="nav-btn <?php echo $page === 'biometrics' ? 'active' : ''; ?>" data-page="biometrics" onclick="window.location.href='index.php?page=biometrics'">
            <i class="fas fa-id-card"></i> <span>Face Registration</span>
        </button>
        <button class="nav-btn <?php echo $page === 'attendance' ? 'active' : ''; ?>" data-page="attendance" onclick="window.location.href='index.php?page=attendance'">
            <i class="fas fa-calendar-alt"></i> <span>Attendance Logs</span>
        </button>
        <button class="nav-btn <?php echo $page === 'payroll' ? 'active' : ''; ?>" data-page="payroll" onclick="window.location.href='index.php?page=payroll'">
            <i class="fas fa-file-invoice-dollar"></i> <span>Payroll</span>
        </button>
        <button class="nav-btn <?php echo $page === 'faculty_payroll' ? 'active' : ''; ?>" data-page="faculty_payroll" onclick="window.location.href='index.php?page=faculty_payroll'">
            <i class="fas fa-chalkboard-teacher"></i> <span>Faculty Payroll</span>
        </button>
        <button class="nav-btn <?php echo $page === 'utility_payroll' ? 'active' : ''; ?>" data-page="utility_payroll" onclick="window.location.href='index.php?page=utility_payroll'">
            <i class="fas fa-tools"></i> <span>Utility Payroll</span>
        </button>
        <button class="nav-btn <?php echo $page === 'allowances' ? 'active' : ''; ?>" data-page="allowances" onclick="window.location.href='index.php?page=allowances'">
            <i class="fas fa-coins"></i> <span>Allowances</span>
        </button>
        <button class="nav-btn <?php echo $page === 'deductions' ? 'active' : ''; ?>" data-page="deductions" onclick="window.location.href='index.php?page=deductions'">
            <i class="fas fa-calculator"></i> <span>Deductions</span>
        </button>
        <button class="nav-btn <?php echo $page === 'leave' ? 'active' : ''; ?>" data-page="leave" onclick="window.location.href='index.php?page=leave'">
            <i class="fas fa-calendar-check"></i> <span>Leave Requests</span>
        </button>
        <button class="nav-btn <?php echo $page === 'loans' ? 'active' : ''; ?>" data-page="loans" onclick="window.location.href='index.php?page=loans'">
            <i class="fas fa-hand-holding-usd"></i> <span>Loan Requests</span>
        </button>
        <button class="nav-btn <?php echo $page === 'resignations' ? 'active' : ''; ?>" data-page="resignations" onclick="window.location.href='index.php?page=resignations'">
            <i class="fas fa-user-minus"></i> <span>Resignations</span>
        </button>
        <button class="nav-btn <?php echo $page === 'reports' ? 'active' : ''; ?>" data-page="reports" onclick="window.location.href='index.php?page=reports'">
            <i class="fas fa-chart-bar"></i> <span>Reports</span>
        </button>
        <button class="nav-btn <?php echo $page === 'settings' ? 'active' : ''; ?>" data-page="settings" onclick="window.location.href='index.php?page=settings'">
            <i class="fas fa-cog"></i> <span>Settings</span>
        </button>
    </nav>
    <div class="sidebar-footer">
        <button class="nav-btn logout" onclick="logout()">
            <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
        </button>
    </div>
</aside>
