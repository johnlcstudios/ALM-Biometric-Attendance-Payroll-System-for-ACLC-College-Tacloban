/**
 * Custom Context Menu System
 * Provides page-specific right-click menus throughout the application
 */

/// Context menu configuration
const contextMenuConfig = {
    global: {
        title: 'System Options',
        items: [
            { icon: 'fas fa-sync-alt', label: 'Refresh', action: () => location.reload() },
            { icon: 'fas fa-info-circle', label: 'System Info', action: () => window.location.href = 'about.php' },
            { icon: 'fas fa-question-circle', label: 'Help and Support', action: () => window.location.href = 'contact-us.php' }
        ]
    }
};

// Global variables
let contextMenuElement = null;
let currentPageContext = 'dashboard';

/**
 * Initialize the custom context menu system
 */
function initContextMenu() {
    // Remove existing context menu if any
    removeContextMenu();
    
    // Create context menu element
    contextMenuElement = document.createElement('div');
    contextMenuElement.id = 'custom-context-menu';
    contextMenuElement.className = 'custom-context-menu';
    document.body.appendChild(contextMenuElement);
    
    // Add event listener for context menu
    document.addEventListener('contextmenu', handleContextMenu);
    
    // Close context menu on click outside
    document.addEventListener('click', closeContextMenu);
    
    // Close context menu on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeContextMenu();
        }
    });
    
    console.log('Custom context menu initialized');
}

/**
 * Handle right-click event
 */
function handleContextMenu(e) {
    // Prevent default context menu
    e.preventDefault();
    
    // Use global menu configuration
    const config = contextMenuConfig.global;
    
    // Build and show context menu
    buildContextMenu(config);
    showContextMenu(e.pageX, e.pageY);
}

/**
 * Get current page context identifier
 */
function getCurrentPageContext() {
    const pathname = window.location.pathname;
    
    // Check for authentication pages
    if (pathname.includes('login.php')) {
        return 'login';
    }
    
    if (pathname.includes('signup.php')) {
        return 'signup';
    }
    
    // Check for kiosk
    if (pathname.includes('kiosk.php')) {
        return 'kiosk';
    }
    
    // Check for ESS portal
    if (pathname.includes('ess.php')) {
        const activePage = document.querySelector('.page.active');
        if (activePage) {
            return 'ess-' + activePage.id;
        }
        return 'ess-dashboard';
    }
    
    // Default to Admin/HR pages
    const activePage = document.querySelector('.page.active');
    if (activePage) {
        return activePage.id;
    }
    
    return 'dashboard';
}

/**
 * Build context menu HTML
 */
function buildContextMenu(config) {
    if (!contextMenuElement) return;
    
    let html = `
        <div class="context-menu-header">
            <i class="fas fa-bars"></i>
            <span>${config.title}</span>
        </div>
        <div class="context-menu-items">
    `;
    
    config.items.forEach((item, index) => {
        if (item.divider) {
            html += '<div class="context-menu-divider"></div>';
        } else {
            html += `
                <div class="context-menu-item" data-index="${index}">
                    <i class="${item.icon}"></i>
                    <span>${item.label}</span>
                </div>
            `;
        }
    });
    
    html += '</div>';
    contextMenuElement.innerHTML = html;
    
    // Add click handlers to menu items
    const items = contextMenuElement.querySelectorAll('.context-menu-item');
    items.forEach((itemElement, index) => {
        const configItem = config.items.find(i => !i.divider && config.items.indexOf(i) === index);
        if (configItem && configItem.action) {
            itemElement.addEventListener('click', (e) => {
                e.stopPropagation();
                closeContextMenu();
                configItem.action();
            });
        }
    });
}

/**
 * Show context menu at specified position
 */
function showContextMenu(x, y) {
    if (!contextMenuElement) return;
    
    // Show menu to get dimensions
    contextMenuElement.style.display = 'block';
    contextMenuElement.style.opacity = '0';
    
    const menuWidth = contextMenuElement.offsetWidth;
    const menuHeight = contextMenuElement.offsetHeight;
    
    // Adjust position to keep menu within viewport
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    
    let adjustedX = x;
    let adjustedY = y;
    
    if (x + menuWidth > viewportWidth) {
        adjustedX = viewportWidth - menuWidth - 10;
    }
    
    if (y + menuHeight > viewportHeight) {
        adjustedY = viewportHeight - menuHeight - 10;
    }
    
    // Ensure minimum position
    adjustedX = Math.max(10, adjustedX);
    adjustedY = Math.max(10, adjustedY);
    
    // Position and show menu
    contextMenuElement.style.left = adjustedX + 'px';
    contextMenuElement.style.top = adjustedY + 'px';
    contextMenuElement.style.opacity = '1';
}

/**
 * Close context menu
 */
function closeContextMenu() {
    if (contextMenuElement) {
        contextMenuElement.style.opacity = '0';
        setTimeout(() => {
            contextMenuElement.style.display = 'none';
        }, 150);
    }
}

/**
 * Remove context menu from DOM
 */
function removeContextMenu() {
    const existing = document.getElementById('custom-context-menu');
    if (existing) {
        existing.remove();
    }
}

/**
 * Update context menu when page changes
 */
function updateContextMenu() {
    closeContextMenu();
    // Menu will be rebuilt on next right-click
}

// Helper functions for context menu actions
function navigateTo(page) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('page', page);
    window.location.href = window.location.pathname + '?' + urlParams.toString();
}

function navigateToPO(page) {
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('page', page);
    window.location.href = window.location.pathname + '?' + urlParams.toString();
}

function showESSPage(pageId) {
    if (typeof showPage === 'function') {
        const btn = document.querySelector(`.nav-btn[onclick*="${pageId}"]`);
        showPage(pageId, btn);
    }
}

function showHelp() {
    window.location.href = 'contact-us.php';
}

function showForgotPasswordHelp() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Forgot Password?',
            html: '<p>Please contact your system administrator or HR department to reset your password.</p><p><strong>Email:</strong> admin@alm.edu</p><p><strong>Phone:</strong> (053) 123-4567</p>',
            icon: 'info',
            confirmButtonText: 'Close'
        });
    }
}

function showPasswordRequirements() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Password Requirements',
            html: '<ul style="text-align: left; line-height: 2;">' +
                  '<li>Minimum 8 characters</li>' +
                  '<li>At least one uppercase letter</li>' +
                  '<li>At least one lowercase letter</li>' +
                  '<li>At least one number</li>' +
                  '<li>At least one special character</li>' +
                  '</ul>',
            icon: 'info',
            confirmButtonText: 'Got it!'
        });
    }
}

function showKioskStatus() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Kiosk Status',
            html: '<p><strong>Status:</strong> <span style="color: #27ae60;">● Online</span></p>' +
                  '<p><strong>Mode:</strong> Attendance Tracking</p>' +
                  '<p><strong>Camera:</strong> Active</p>' +
                  '<p><strong>Last Sync:</strong> ' + new Date().toLocaleString() + '</p>',
            icon: 'info',
            confirmButtonText: 'Close'
        });
    }
}

function openAddEmployeeModal() {
    const modal = document.getElementById('employeeModal');
    if (modal) {
        // Reset form and show modal
        const form = document.getElementById('employeeForm');
        if (form) form.reset();
        
        // Reset to step 1
        document.querySelectorAll('.form-step').forEach(step => step.classList.remove('active'));
        document.getElementById('step1').classList.add('active');
        
        // Update stepper
        document.querySelectorAll('.stepper-item').forEach(item => item.classList.remove('active'));
        document.getElementById('step1-indicator').classList.add('active');
        
        // Show/hide buttons
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const saveBtn = document.getElementById('saveBtn');

        if (nextBtn) nextBtn.style.display = 'inline-block';
        if (saveBtn) saveBtn.style.display = 'none';
        
        // Update title
        document.getElementById('employeeModalTitle').textContent = 'Add New Employee';
        
        modal.style.display = 'flex';
    } else {
        showToast('Employee modal not found', 'error');
    }
}

function exportEmployees() {
    if (typeof employees !== 'undefined' && employees.length > 0) {
        // Create CSV content
        let csv = 'Employee ID,Full Name,Position,Department,Email,Contact No,Status\n';
        employees.forEach(emp => {
            csv += `"${emp.employee_id || ''}","${emp.full_name || ''}","${emp.position || ''}","${emp.department || ''}","${emp.email || ''}","${emp.contact_no || ''}","${emp.status || ''}"\n`;
        });
        
        // Download CSV
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `employees_export_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
        showToast('Employee list exported successfully', 'success');
    } else {
        showToast('No employee data available to export', 'warning');
    }
}

function openImportModal() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.csv,.xlsx,.xls';
    input.onchange = async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Import Employees?',
                text: `Import ${file.name}? This will add new employees to the system.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, import',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    showToast('Import feature requires backend processing', 'info');
                    // TODO: Implement actual file upload to backend
                }
            });
        }
    };
    input.click();
}

function toggleAdvancedSearch() {
    const searchBox = document.getElementById('employeeSearch');
    if (searchBox) {
        searchBox.focus();
    }
}

function showFilterOptions() {
    showToast('Filter options coming soon', 'info');
}

function openManualAttendanceModal() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Manual Attendance Entry',
            html: `
                <form id="manualAttForm" style="text-align: left;">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Employee</label>
                        <select id="manualAttEmployee" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                            <option value="">Select Employee...</option>
                            ${(typeof employees !== 'undefined' ? employees.map(e => `<option value="${e.id}">${e.full_name} (${e.employee_id})</option>`).join('') : '')}
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Date</label>
                        <input type="date" id="manualAttDate" value="${new Date().toISOString().split('T')[0]}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">Check In Time</label>
                        <input type="time" id="manualAttCheckIn" value="08:00" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Submit Entry',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const empId = document.getElementById('manualAttEmployee').value;
                const date = document.getElementById('manualAttDate').value;
                const checkIn = document.getElementById('manualAttCheckIn').value;
                
                if (!empId || !date || !checkIn) {
                    Swal.showValidationMessage('All fields are required');
                    return false;
                }
                
                return { empId, date, checkIn };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Manual attendance entry submitted', 'success');
                // TODO: Send to backend API
            }
        });
    }
}

function contextGenerateDTR() {
    if (typeof window.generateDTR === 'function') {
        window.generateDTR();
    } else {
        showToast('DTR generation not available', 'warning');
    }
}

function exportAttendance() {
    if (typeof attendanceLogs !== 'undefined' && attendanceLogs.length > 0) {
        let csv = 'Date,Employee ID,Employee Name,Check In,Lunch Out,Lunch In,Check Out,Status,Late Minutes\n';
        attendanceLogs.forEach(log => {
            csv += `"${log.log_date || ''}","${log.employee_id || ''}","${log.full_name || ''}","${log.check_in || ''}","${log.lunch_out || ''}","${log.lunch_in || ''}","${log.check_out || ''}","${log.status || ''}","${log.late_minutes || 0}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `attendance_export_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
        showToast('Attendance exported successfully', 'success');
    } else {
        showToast('No attendance data available to export', 'warning');
    }
}

function showAttendanceHistory() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Attendance Edit History',
            html: '<p>View all manual edits and corrections made to attendance records.</p>',
            icon: 'info',
            confirmButtonText: 'Close'
        });
    }
}

function openFixLogsModal() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Fix Missing Logs',
            html: `
                <p>This will identify and fix attendance records with missing check-in or check-out times.</p>
                <p style="color: #e74c3c; font-size: 0.9rem; margin-top: 10px;"><i class="fas fa-exclamation-triangle"></i> This action requires admin approval.</p>
            `,
            showCancelButton: true,
            confirmButtonText: 'Run Fix',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Fix logs feature requires backend processing', 'info');
            }
        });
    }
}

function calculatePayroll() {
    const modal = document.getElementById('runPayrollModal');
    if (modal) {
        // Set default dates (current month)
        const today = new Date();
        const startDate = new Date(today.getFullYear(), today.getMonth(), 1);
        const endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        
        const startInput = document.getElementById('payrollStartDate');
        const endInput = document.getElementById('payrollEndDate');
        
        if (startInput) startInput.value = startDate.toISOString().split('T')[0];
        if (endInput) endInput.value = endDate.toISOString().split('T')[0];
        
        modal.style.display = 'flex';
    } else {
        showToast('Payroll modal not found', 'error');
    }
}

function processPayroll() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Process Payroll?',
            text: 'This will calculate and process payroll for all employees in the selected period.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, process it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Payroll processing started...', 'info');
                // TODO: Call backend API to process payroll
            }
        });
    }
}

function exportAllPayslips() {
    if (typeof payrollHistory !== 'undefined' && payrollHistory.length > 0) {
        let csv = 'Period,Employee ID,Employee Name,Basic Pay,Deductions,Net Pay,Status\n';
        payrollHistory.forEach(p => {
            csv += `"${p.period || ''}","${p.emp_code || ''}","${p.full_name || ''}","${p.basic_pay || 0}","${p.deductions || 0}","${p.net_pay || 0}","${p.status || ''}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `payroll_export_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
        showToast('Payroll data exported successfully', 'success');
    } else {
        showToast('No payroll data available to export', 'warning');
    }
}

function showPayrollHistory() {
    navigateTo('payroll');
}

function showPayrollSettings() {
    navigateTo('settings');
}

function calculateFacultyPayroll() {
    navigateTo('payroll_specialized');
}

function exportFacultyPayslips() {
    if (typeof payrollHistory !== 'undefined' && payrollHistory.length > 0) {
        const facultyPayroll = payrollHistory.filter(p => p.category === 'Faculty');
        if (facultyPayroll.length === 0) {
            showToast('No faculty payroll data available', 'warning');
            return;
        }
        
        let csv = 'Period,Employee ID,Name,Basic Pay,Deductions,Net Pay\n';
        facultyPayroll.forEach(p => {
            csv += `"${p.period || ''}","${p.emp_code || ''}","${p.full_name || ''}","${p.basic_pay || 0}","${p.deductions || 0}","${p.net_pay || 0}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `faculty_payroll_export_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
        showToast('Faculty payroll exported successfully', 'success');
    } else {
        showToast('No payroll data available', 'warning');
    }
}

function recalculateFacultyPayroll() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Recalculate Faculty Payroll?',
            text: 'This will recalculate all faculty payroll based on current subject loads and rates.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, recalculate',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Recalculating faculty payroll...', 'info');
                // TODO: Call backend API
            }
        });
    }
}

function calculateUtilityPayroll() {
    navigateTo('payroll_specialized');
}

function exportUtilityPayslips() {
    if (typeof payrollHistory !== 'undefined' && payrollHistory.length > 0) {
        const utilityPayroll = payrollHistory.filter(p => p.category === 'Utility');
        if (utilityPayroll.length === 0) {
            showToast('No utility payroll data available', 'warning');
            return;
        }
        
        let csv = 'Period,Employee ID,Name,Basic Pay,Deductions,Net Pay\n';
        utilityPayroll.forEach(p => {
            csv += `"${p.period || ''}","${p.emp_code || ''}","${p.full_name || ''}","${p.basic_pay || 0}","${p.deductions || 0}","${p.net_pay || 0}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `utility_payroll_export_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
        showToast('Utility payroll exported successfully', 'success');
    } else {
        showToast('No payroll data available', 'warning');
    }
}

function recalculateUtilityPayroll() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Recalculate Utility Payroll?',
            text: 'This will recalculate all utility staff payroll.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, recalculate',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Recalculating utility payroll...', 'info');
                // TODO: Call backend API
            }
        });
    }
}

function openAddAllowanceModal() {
    navigateTo('allowances');
}

function enableAllowanceEditing() {
    const editBtns = document.querySelectorAll('.allowance-edit-btn');
    editBtns.forEach(btn => btn.style.display = 'inline-block');
    showToast('Click on any allowance amount to edit', 'success');
}

function exportAllowances() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Export Allowances',
            text: 'This will export all allowance records to CSV.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Export',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Allowances export initiated', 'info');
                // TODO: Fetch and export allowances data
            }
        });
    }
}

function openAddDeductionModal() {
    navigateTo('deductions');
}

function enableDeductionEditing() {
    const editBtns = document.querySelectorAll('.deduction-edit-btn');
    editBtns.forEach(btn => btn.style.display = 'inline-block');
    showToast('Click on any deduction amount to edit', 'success');
}

function exportDeductions() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Export Deductions',
            text: 'This will export all deduction records to CSV.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Export',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Deductions export initiated', 'info');
                // TODO: Fetch and export deductions data
            }
        });
    }
}

function showLeaveCalendar() {
    navigateTo('leave');
}

function bulkApproveLeaves() {
    if (typeof leaveRequests !== 'undefined' && leaveRequests.length > 0) {
        const pendingLeaves = leaveRequests.filter(l => l.status === 'Pending');
        if (pendingLeaves.length === 0) {
            showToast('No pending leave requests to approve', 'warning');
            return;
        }
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: `Approve ${pendingLeaves.length} Leave Requests?`,
                text: 'This will approve all pending leave requests.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, approve all',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    showToast('Bulk approval initiated', 'info');
                    // TODO: Call backend API to bulk approve
                }
            });
        }
    } else {
        showToast('No leave requests available', 'warning');
    }
}

function bulkRejectLeaves() {
    if (typeof leaveRequests !== 'undefined' && leaveRequests.length > 0) {
        const pendingLeaves = leaveRequests.filter(l => l.status === 'Pending');
        if (pendingLeaves.length === 0) {
            showToast('No pending leave requests to reject', 'warning');
            return;
        }
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: `Reject ${pendingLeaves.length} Leave Requests?`,
                text: 'This will reject all pending leave requests.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, reject all',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    showToast('Bulk rejection initiated', 'info');
                    // TODO: Call backend API to bulk reject
                }
            });
        }
    } else {
        showToast('No leave requests available', 'warning');
    }
}

function exportLeaveRecords() {
    if (typeof leaveRequests !== 'undefined' && leaveRequests.length > 0) {
        let csv = 'Employee,Type,Start Date,End Date,Status,Reason\n';
        leaveRequests.forEach(l => {
            csv += `"${l.employee_name || ''}","${l.type || ''}","${l.start_date || ''}","${l.end_date || ''}","${l.status || ''}","${l.reason || ''}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `leave_records_export_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
        showToast('Leave records exported successfully', 'success');
    } else {
        showToast('No leave records available', 'warning');
    }
}

function openCreateLoanModal() {
    navigateTo('loans');
}

function approveSelectedLoans() {
    if (typeof loanRequests !== 'undefined' && loanRequests.length > 0) {
        const pendingLoans = loanRequests.filter(l => l.status === 'Pending');
        if (pendingLoans.length === 0) {
            showToast('No pending cash advance requests to approve', 'warning');
            return;
        }
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: `Approve ${pendingLoans.length} Cash Advance Requests?`,
                text: 'This will approve all pending cash advance requests.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, approve all',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    showToast('Bulk cash advance approval initiated', 'info');
                    // TODO: Call backend API
                }
            });
        }
    } else {
        showToast('No cash advance requests available', 'warning');
    }
}

function exportLoanRecords() {
    if (typeof loanRequests !== 'undefined' && loanRequests.length > 0) {
        let csv = 'Employee,Amount,Status,Requested Date,Reason\n';
        loanRequests.forEach(l => {
            csv += `"${l.employee_name || ''}","${l.amount || 0}","${l.status || ''}","${l.requested_at || ''}","${l.reason || ''}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `loan_records_export_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
        showToast('Cash advance records exported successfully', 'success');
    } else {
        showToast('No cash advance records available', 'warning');
    }
}

function viewResignationDetails() {
    navigateTo('resignations');
}

function processResignation() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Process Resignation',
            text: 'This will process the resignation and update employee status.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Process',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Resignation processing initiated', 'info');
                // TODO: Call backend API
            }
        });
    }
}

function exportResignationRecords() {
    if (typeof resignationRequests !== 'undefined' && resignationRequests.length > 0) {
        let csv = 'Employee,Effective Date,Status,Reason,Requested Date\n';
        resignationRequests.forEach(r => {
            csv += `"${r.employee_name || ''}","${r.effective_date || ''}","${r.status || ''}","${r.reason || ''}","${r.requested_at || ''}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `resignation_records_export_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
        showToast('Resignation records exported successfully', 'success');
    } else {
        showToast('No resignation records available', 'warning');
    }
}

function generatePDFReport() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Generate PDF Report',
            text: 'This will generate a comprehensive PDF report of the current data.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Generate',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('PDF report generation started', 'info');
                // TODO: Implement PDF generation
            }
        });
    }
}

function exportToExcel() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Export to Excel',
            text: 'This will export the current data to Excel format.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Export',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Excel export started', 'info');
                // TODO: Implement Excel export
            }
        });
    }
}

function showAdvancedAnalytics() {
    navigateTo('reports');
}

function printReport() {
    window.print();
}

function startRegistration() {
    const startBtn = document.getElementById('startRegBtn');
    if (startBtn) {
        startBtn.click();
    }
}

function retrainFaceModel() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Retrain Face Recognition Model?',
            text: 'This will retrain the AI model with all registered face data. This may take a few minutes.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, retrain',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Face model retraining started...', 'info');
                // TODO: Call backend API to retrain model
            }
        });
    }
}

function clearFaceData() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Clear Face Data?',
            text: 'This will remove all registered face data. This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, clear it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Face data cleared', 'success');
                // TODO: Call backend API to clear face data
            }
        });
    }
}

function exportFaceData() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Export Face Registration Data',
            text: 'This will export a list of all employees with registered face data.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Export',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                if (typeof employees !== 'undefined' && employees.length > 0) {
                    const withFaces = employees.filter(e => e.has_face_data);
                    let csv = 'Employee ID,Name,Position,Has Face Data\n';
                    withFaces.forEach(e => {
                        csv += `"${e.employee_id || ''}","${e.full_name || ''}","${e.position || ''}","Yes"\n`;
                    });
                    
                    const blob = new Blob([csv], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `face_registration_export_${new Date().toISOString().split('T')[0]}.csv`;
                    a.click();
                    window.URL.revokeObjectURL(url);
                    showToast('Face data exported successfully', 'success');
                } else {
                    showToast('No employee data available', 'warning');
                }
            }
        });
    }
}

function openAssignSubjectModal() {
    const modal = document.getElementById('addLoadModal');
    if (modal) {
        modal.style.display = 'flex';
    } else {
        navigateTo('subject_loads');
    }
}

function exportSubjectLoads() {
    if (typeof subjectLoads !== 'undefined' && subjectLoads.length > 0) {
        let csv = 'Faculty,Subject Code,Description,Units,Hours/Week\n';
        subjectLoads.forEach(sl => {
            csv += `"${sl.faculty_name || ''}","${sl.subject_code || ''}","${sl.description || ''}","${sl.units || 0}","${sl.hours || 0}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `subject_loads_export_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
        showToast('Subject loads exported successfully', 'success');
    } else {
        showToast('No subject load data available', 'warning');
    }
}

function recalculateHours() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Recalculate Teaching Hours?',
            text: 'This will recalculate all teaching hours based on current subject loads.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Recalculate',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Recalculating hours...', 'info');
                // TODO: Call backend API
            }
        });
    }
}

function openAssignEmployeesModal() {
    navigateTo('assign_payroll');
}

function exportPayrollAssignments() {
    if (typeof employees !== 'undefined' && employees.length > 0) {
        let csv = 'Employee ID,Name,Position,Department,Payroll Category\n';
        employees.forEach(e => {
            csv += `"${e.employee_id || ''}","${e.full_name || ''}","${e.position || ''}","${e.department || ''}","${e.payroll_category || ''}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `payroll_assignments_export_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
        showToast('Payroll assignments exported successfully', 'success');
    } else {
        showToast('No employee data available', 'warning');
    }
}

function resetSettings() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Reset Settings?',
            text: 'This will reset all settings to their default values. This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, reset',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Settings reset to defaults', 'success');
                // TODO: Call backend API to reset settings
            }
        });
    }
}

function backupDatabase() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Backup Database?',
            text: 'This will create a complete backup of the system database.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Start Backup',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                showToast('Database backup started...', 'info');
                // TODO: Call backend API to backup database
                // The backend will handle creating a .sql dump file and triggering download
            }
        });
    }
}

function showSystemInfo() {
    window.location.href = 'about.php';
}

function focusAttendanceFilter() {
    const filter = document.getElementById('att-from');
    if (filter) {
        filter.focus();
    }
}

function downloadLatestPayslip() {
    showToast('Downloading latest payslip...', 'info');
}

function printPayslip() {
    window.print();
}

function contextSwitchRequestTab(type, btn) {
    if (typeof window.switchRequestTab === 'function') {
        window.switchRequestTab(type, btn);
    }
}

function triggerPhotoUpload() {
    const upload = document.getElementById('profile-picture-upload');
    if (upload) {
        upload.click();
    }
}

function contextSwitchProfileTab(type, btn) {
    if (typeof window.switchProfileTab === 'function') {
        window.switchProfileTab(type, btn);
    }
}

function focusEmployeeSearch() {
    const search = document.getElementById('employeeSearch');
    if (search) {
        search.focus();
    }
}

function exportEmployeeList() {
    exportEmployees();
}

function exportPayslips() {
    exportAllPayslips();
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initContextMenu);
} else {
    initContextMenu();
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { initContextMenu, updateContextMenu, contextMenuConfig };
}
