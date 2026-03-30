/**
 * Biometric Attendance & Payroll System - Core Logic (PHP/MySQL Version)
 */

// --- Initial Data Fetching ---
let employees = [];
let payrollHistory = [];
let attendanceLogs = [];
let leaveRequests = [];
let allowanceCategories = [];
let employeeAllowances = [];
let deductionCategories = [];
let employeeDeductions = [];
let deductionsConfig = {
    gov: [],
    company: []
};
let masterSubjects = [];
let currentPage = 'dashboard';

async function fetchData() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) loadingOverlay.style.display = 'flex';

    try {
        const fetchJSON = async (url) => {
            const res = await fetch(url);
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error("Malformed JSON from " + url + ":", text);
                return null;
            }
        };

        employees = await fetchJSON('backend/api.php?action=get_employees') || [];
        attendanceLogs = await fetchJSON('backend/api.php?action=get_attendance') || [];
        payrollHistory = await fetchJSON('backend/api.php?action=get_payroll') || [];
        leaveRequests = await fetchJSON('backend/api.php?action=get_leave_requests') || [];
        loanRequests = await fetchJSON('backend/api.php?action=get_loan_requests') || [];
        resignationRequests = await fetchJSON('backend/api.php?action=get_resignation_requests') || [];
        deductionsConfig = await fetchJSON('backend/api.php?action=get_deductions') || [];
        allowanceCategories = await fetchJSON('backend/api.php?action=get_allowance_categories') || [];
        employeeAllowances = await fetchJSON('backend/api.php?action=get_employee_allowances') || [];
        deductionCategories = await fetchJSON('backend/api.php?action=get_deduction_categories') || [];
        employeeDeductions = await fetchJSON('backend/api.php?action=get_employee_deductions') || [];
        masterSubjects = await fetchJSON('backend/api.php?action=get_subjects') || [];
        subjectLoads = await fetchJSON('backend/api.php?action=get_subject_loads') || [];
        const dashboardStats = await fetchJSON('backend/api.php?action=get_dashboard_stats');
        if (dashboardStats) {
            document.getElementById('stat-total-emp').innerText = dashboardStats.total_employees;
            document.getElementById('stat-present').innerText = dashboardStats.present_today;
            document.getElementById('stat-absent').innerText = dashboardStats.absent_today;
            document.getElementById('stat-leave').innerText = dashboardStats.pending_leave;
        }
        
        // Determine initial page based on role
        let initialPage = 'dashboard';
        if (typeof USER_ROLE !== 'undefined' && USER_ROLE === 'Payroll') {
            initialPage = 'payroll';
        } else {
            initialPage = document.querySelector('.page.active')?.id || 'dashboard';
        }
        
        showPage(initialPage);
    } catch (err) {
        console.error("Error fetching data:", err);
    } finally {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
    }
}

// --- Navigation Logic ---
function showPage(pageId) {
    currentPage = pageId;
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.getAttribute('onclick')?.includes(`'${pageId}'`)) {
            btn.classList.add('active');
        }
    });

    document.querySelectorAll('.page').forEach(page => {
        page.classList.remove('active');
    });
    const targetPage = document.getElementById(pageId);
    if (targetPage) targetPage.classList.add('active');

    const titles = {
        'dashboard': 'Dashboard Overview',
        'employees': 'Employee Directory',
        'biometrics': 'Face Biometrics Enrollment',
        'attendance': 'Daily Attendance Logs',
        'payroll': 'Payroll Processing',
        'faculty_payroll': 'Faculty Payroll System',
        'utility_payroll': 'Utility Payroll System',
        'subject_loads': 'Subject Load Management',
        'assign_payroll': 'Assign Payroll Officer',
        'allowances': 'Allowances and Earnings',
        'leave': 'Leave Management',
        'deductions': 'Deductions & Allowances',
        'reports': 'System Reports',
        'settings': 'System Settings'
    };
    const titleEl = document.getElementById('current-page-title');
    if (titleEl) titleEl.innerText = titles[pageId] || 'Admin Hub';

    if (pageId === 'dashboard') initCharts();
    if (pageId === 'employees') renderEmployeeTable();
    if (pageId === 'biometrics') populateEmployeeDropdown();
    if (pageId === 'attendance') renderAttendanceTable();
    if (pageId === 'payroll') renderPayrollTable();
    if (pageId === 'faculty_payroll') renderFacultyPayroll();
    if (pageId === 'utility_payroll') renderUtilityPayroll();
    if (pageId === 'subject_loads') {
        fetchMasterSubjects();
        fetchSubjectLoads();
    }
    if (pageId === 'assign_payroll') renderPayrollOfficerAssignment();
    if (pageId === 'allowances') renderAllowances();
    if (pageId === 'leave') renderLeaveTable();
    if (pageId === 'loans') renderLoanTable();
    if (pageId === 'resignations') renderResignationTable();
    if (pageId === 'deductions') renderDeductions();
}

// --- UI Controls (Modals) ---
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'block';
    if (modalId === 'employeeModal') resetEmpModal();
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

// --- Employee Management ---
function renderEmployeeTable() {
    const tbody = document.getElementById('employeeTableBody');
    if (!tbody) return;
    tbody.innerHTML = employees.map(emp => {
        const isFaculty = (emp.position || '').toLowerCase() === 'faculty' || (emp.department || '').toLowerCase() === 'faculty' || (emp.department || '').toLowerCase() === 'education';
        const loadCount = subjectLoads.filter(load => load.faculty_id == emp.id).length;
        
        return `
        <tr id="row-${emp.id}">
            <td>${emp.employee_id}</td>
            <td>
                <div><strong>${emp.full_name}</strong></div>
                <div class="text-muted" style="font-size: 0.8rem;">Username: ${emp.username || 'N/A'}</div>
            </td>
            <td>${emp.position}</td>
            <td>${emp.department}</td>
            <td>
                ${isFaculty ? `
                    <span class="badge badge-info" style="cursor: pointer; padding: 5px 10px; border-radius: 4px; background: #3498db; color: white;" onclick="viewFacultyLoads('${emp.id}')">
                        ${loadCount} Loads
                    </span>
                ` : '<span class="text-muted">---</span>'}
            </td>
            <td><span class="status-badge status-${emp.status.toLowerCase().replace(' ', '-')}">${emp.status}</span></td>
            <td>
                <button class="btn btn-secondary btn-sm" onclick="editEmployee('${emp.id}')" title="Edit"><i class="fas fa-edit"></i></button>
                <button class="btn btn-danger btn-sm" onclick="deleteEmployee('${emp.id}')" title="Delete"><i class="fas fa-trash"></i></button>
                <button class="btn btn-warning btn-sm" onclick="resetPassword('${emp.user_id}')" title="Reset Password"><i class="fas fa-key"></i></button>
                ${isFaculty ? `<button class="btn btn-primary btn-sm" onclick="openAddLoadModal('${emp.id}')" title="Add Subject Load"><i class="fas fa-book-medical"></i></button>` : ''}
            </td>
        </tr>
    `; }).join('');
}

function openAddLoadModal(empId) {
    const emp = employees.find(e => e.id == empId);
    if (!emp) return;
    
    document.getElementById('subjectLoadForm').reset();
    document.getElementById('loadFacultyId').value = empId;
    
    // Populate subject select
    const subjectSelect = document.getElementById('loadSubjectSelect');
    if (subjectSelect) {
        subjectSelect.innerHTML = '<option value="">-- Choose Subject --</option>' + 
            masterSubjects.map(s => `<option value="${s.id}">${s.code} - ${s.description}</option>`).join('');
    }
    
    const modal = document.getElementById('addLoadModal');
    if (modal) {
        modal.style.display = 'block';
        const title = modal.querySelector('h3');
        if (title) title.innerText = `Add Subject Load for ${emp.full_name}`;
    }
}

function onLoadSubjectChange(subjectId) {
    if (!subjectId) return;
    const subject = masterSubjects.find(s => s.id == subjectId);
    if (subject) {
        document.getElementById('loadSubjectCode').value = subject.code;
        document.getElementById('loadDescription').value = subject.description;
        document.getElementById('loadUnits').value = subject.units;
        document.getElementById('loadHours').value = subject.hours;
    }
}

async function resetPassword(userId) {
    if (!userId) return alert('This employee does not have a user account.');
    if (confirm("Are you sure you want to reset this employee's password to 'welcome123'?")) {
        const response = await fetch(`backend/api.php?action=reset_password&user_id=${userId}`);
        const result = await response.json();
        if (result.success) {
            alert(result.message);
        } else {
            alert('Error: ' + (result.message || 'Failed to reset password.'));
        }
    }
}

let editingEmployeeId = null;

function editEmployee(id) {
    const emp = employees.find(e => e.id == id);
    if (!emp) return;
    
    editingEmployeeId = id;
    openModal('employeeModal');
    
    const form = document.getElementById('employeeForm');
    form.fullName.value = emp.full_name;
    form.dob.value = emp.dob || '';
    form.email.value = emp.email || '';
    form.position.value = emp.position;
    form.department.value = emp.department;
    form.basicSalary.value = emp.basic_salary;
    form.sss.value = emp.sss || '';
    form.philhealth.value = emp.philhealth || '';
    form.tin.value = emp.tin || '';
    form.pagibig.value = emp.pagibig || '';
    
    document.querySelector('#employeeModal h3').innerText = 'Edit Employee';
    document.getElementById('saveBtn').innerText = 'Update Employee';
}

async function deleteEmployee(id) {
    if (confirm(`Are you sure you want to delete this employee?`)) {
        const response = await fetch(`backend/api.php?action=delete_employee&id=${id}`);
        const result = await response.json();
        if (result.success) {
            fetchData();
        }
    }
}

function addSubjectRow() {
    const container = document.getElementById('subjectRows');
    if (!container) return;
    const rowId = Date.now();
    const row = document.createElement('div');
    row.className = 'form-row subject-row';
    row.id = `subject-${rowId}`;
    row.style.marginBottom = '10px';
    row.innerHTML = `
        <input type="text" placeholder="Subject Code" class="form-control" name="subCode[]">
        <input type="number" placeholder="Units" class="form-control" name="subUnits[]">
        <button type="button" class="btn btn-danger btn-sm" onclick="removeSubjectRow('${rowId}')"><i class="fas fa-times"></i></button>
    `;
    container.appendChild(row);
}

function removeSubjectRow(id) {
    const row = document.getElementById(`subject-${id}`);
    if (row) row.remove();
}

async function saveEmployee() {
    const form = document.getElementById('employeeForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    // Add editing ID if exists
    if (editingEmployeeId) data.id = editingEmployeeId;
    
    // Handle subject rows if Faculty
    if (data.position === 'Faculty') {
        const subCodes = Array.from(document.querySelectorAll('input[name="subCode[]"]')).map(i => i.value);
        const subUnits = Array.from(document.querySelectorAll('input[name="subUnits[]"]')).map(i => i.value);
        data.subjects = subCodes.map((code, i) => ({ code, units: subUnits[i] }));
    }

    try {
        const response = await fetch('backend/api.php?action=save_employee', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (result.success) {
            closeModal('employeeModal');
            fetchData();
            resetEmpModal();
            alert('Employee saved successfully!');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (err) {
        console.error("Save error:", err);
        alert("Failed to connect to the server.");
    }
}

// --- Multi-step Wizard Logic ---
let currentStep = 1;
function goEmpStep(n) {
    const steps = document.querySelectorAll('.form-step');
    const position = document.querySelector('select[name="position"]').value;
    
    let nextStep = currentStep + n;
    if (nextStep === 3 && position !== 'Faculty') {
        nextStep = n > 0 ? 4 : 2;
    }

    steps[currentStep - 1].classList.remove('active');
    const currentIndicator = document.getElementById(`step${currentStep}-indicator`);
    if (currentIndicator) currentIndicator.classList.remove('active');
    
    currentStep = nextStep;
    
    steps[currentStep - 1].classList.add('active');
    const nextIndicator = document.getElementById(`step${currentStep}-indicator`);
    if (nextIndicator) nextIndicator.classList.add('active');
    
    document.getElementById('prevBtn').style.display = currentStep === 1 ? 'none' : 'inline-block';
    document.getElementById('nextBtn').style.display = currentStep === 4 ? 'none' : 'inline-block';
    document.getElementById('saveBtn').style.display = currentStep === 4 ? 'inline-block' : 'none';
}

function resetEmpModal() {
    currentStep = 1;
    editingEmployeeId = null;
    document.querySelectorAll('.form-step').forEach((s, i) => s.classList.toggle('active', i === 0));
    document.querySelectorAll('.step').forEach((s, i) => s.classList.toggle('active', i === 0));
    document.getElementById('employeeForm').reset();
    document.getElementById('prevBtn').style.display = 'none';
    document.getElementById('nextBtn').style.display = 'inline-block';
    document.getElementById('saveBtn').style.display = 'none';
    document.querySelector('#employeeModal h3').innerText = 'Add New Employee';
}

// --- Attendance ---
function renderAttendanceTable() {
    const tbody = document.getElementById('attendanceTableBody');
    if (!tbody) return;
    const dateFilter = document.getElementById('attendanceDateFilter').value;
    
    let filteredLogs = attendanceLogs;
    if (dateFilter) {
        filteredLogs = attendanceLogs.filter(log => log.log_date === dateFilter);
    }

    tbody.innerHTML = filteredLogs.map(log => `
        <tr>
            <td>${log.emp_code}</td>
            <td>${log.full_name}</td>
            <td>${log.log_date}</td>
            <td>${log.check_in || '---'}</td>
            <td>${log.check_out || '---'}</td>
            <td><span class="status-badge status-${log.status.toLowerCase()}">${log.status}</span></td>
        </tr>
    `).join('');
}

// --- Payroll ---
async function runPayroll() {
    const start_date = document.getElementById('payrollStartDate').value;
    const end_date = document.getElementById('payrollEndDate').value;
    
    if (!start_date || !end_date) {
        return alert('Please select both a start and end date.');
    }

    if (confirm(`Run payroll for the period ${start_date} to ${end_date}?`)) {
        const response = await fetch('backend/api.php?action=run_payroll', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ start_date, end_date })
        });
        
        const result = await response.json();
        if (result.success) {
            alert(result.message || `Payroll processed for ${start_date} to ${end_date}`);
            fetchData();
        } else {
            alert("Error: " + (result.message || "Failed to process payroll."));
        }
    }
}

function renderPayrollTable() {
    const tbody = document.getElementById('payrollTableBody');
    if (!tbody) return;

    fetch('backend/api.php?action=get_payroll_batches')
        .then(res => res.json())
        .then(batchList => {
            // Update stats
            if (batchList.length > 0) {
                document.getElementById('stat-total-batches').innerText = batchList.length;
                const totalDisbursed = batchList.reduce((sum, b) => sum + parseFloat(b.total_disbursed), 0);
                document.getElementById('stat-total-disbursed').innerText = `₱${totalDisbursed.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
                document.getElementById('stat-last-run').innerText = batchList[0].period;
                document.getElementById('stat-last-staff-count').innerText = batchList[0].staff_count;
            }

            tbody.innerHTML = batchList.map((b, index) => `
                <tr>
                    <td><strong>BATCH-${101 + index}</strong></td>
                    <td>${b.period}</td>
                    <td>₱${parseFloat(b.total_disbursed).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td>${new Date(b.processing_date).toLocaleDateString()}</td>
                    <td>Admin</td>
                    <td><span class="status-badge status-active">Completed</span></td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick="viewBatch('${b.period}')"><i class="fas fa-eye"></i> View</button>
                    </td>
                </tr>
            `).join('');
        });
}

function showPayrollModal() {
    // This function will now open a more sophisticated modal
    // For now, we will keep the simple prompt for direct payroll run
    const start = prompt("Enter Start Date (YYYY-MM-DD):", new Date().toISOString().split('T')[0]);
    if (!start) return;
    const end = prompt("Enter End Date (YYYY-MM-DD):", new Date().toISOString().split('T')[0]);
    if (!end) return;
    
    if (confirm(`Run payroll for ${start} to ${end}?`)) {
        runPayrollDirect(start, end);
    }
}

async function runPayrollDirect(start_date, end_date) {
    const response = await fetch('backend/api.php?action=run_payroll', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ start_date, end_date })
    });
    const result = await response.json();
    alert(result.message);
    fetchData();
}

function viewBatch(period) {
    const records = payrollHistory.filter(p => p.period === period);
    if (records.length === 0) return alert("No records found for this batch.");

    let report = `Payroll Details for ${period}\n\n`;
    report += "Name".padEnd(30) + "Net Pay".padStart(15) + "\n";
    report += "-".repeat(45) + "\n";
    records.forEach(r => {
        report += `${r.full_name.padEnd(30)} ${('₱' + parseFloat(r.net_pay).toLocaleString()).padStart(15)}\n`;
    });

    alert(report);
}

// --- Leave ---
function renderLeaveTable() {
    const tbody = document.getElementById('leaveTableBody');
    if (!tbody) return;
    tbody.innerHTML = leaveRequests.map(req => `
        <tr>
            <td>${req.full_name}</td>
            <td>${req.leave_type || req.type}</td>
            <td>${req.start_date || '-'}</td>
            <td>${req.end_date || '-'}</td>
            <td>${req.reason}</td>
            <td><span class="status-badge status-${req.status.toLowerCase()}">${req.status}</span></td>
            <td>
                ${req.status === 'Pending' ? `
                    <button class="btn btn-success btn-sm" onclick="updateLeaveStatus(${req.id}, 'Approved')"><i class="fas fa-check"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="updateLeaveStatus(${req.id}, 'Rejected')"><i class="fas fa-times"></i></button>
                ` : '<span class="text-muted">Processed</span>'}
            </td>
        </tr>
    `).join('') || '<tr><td colspan="7" class="text-center">No leave requests found.</td></tr>';
}

async function updateLeaveBalance() {
    const employeeId = document.getElementById('leaveBalanceEmployeeSelect').value;
    const balance = document.getElementById('newLeaveBalance').value;

    if (!employeeId || !balance) return alert("Please select an employee and enter a balance.");

    const response = await fetch(`backend/api.php?action=update_leave_balance&employee_id=${employeeId}&balance=${balance}`);
    const result = await response.json();
    
    if (result.success) {
        alert("Leave balance updated successfully.");
        document.getElementById('newLeaveBalance').value = '';
        fetchData();
    } else {
        alert("Error: " + (result.message || "Failed to update balance."));
    }
}

async function updateLeaveStatus(id, status) {
    const response = await fetch('backend/api.php?action=update_leave_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
    });
    const result = await response.json();
    if (result.success) {
        fetchData();
    }
}

function renderLoanTable() {
    const tbody = document.getElementById('loanTableBody');
    if (!tbody) return;
    tbody.innerHTML = loanRequests.map(req => `
        <tr>
            <td>${req.full_name}</td>
            <td>₱${parseFloat(req.amount).toLocaleString()}</td>
            <td>${req.reason}</td>
            <td><span class="status-badge status-${req.status.toLowerCase()}">${req.status}</span></td>
            <td>
                ${req.status === 'Pending' ? `
                    <button class="btn btn-success btn-sm" onclick="updateLoanStatus(${req.id}, 'Approved')"><i class="fas fa-check"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="updateLoanStatus(${req.id}, 'Rejected')"><i class="fas fa-times"></i></button>
                ` : (req.status === 'Approved' ? '<span class="text-info">Awaiting Payroll</span>' : '<span class="text-muted">Processed</span>')}
            </td>
        </tr>
    `).join('');
}

async function updateLoanStatus(id, status) {
    const response = await fetch('backend/api.php?action=update_loan_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
    });
    const result = await response.json();
    if (result.success) {
        fetchData();
    }
}

function renderResignationTable() {
    const tbody = document.getElementById('resignationTableBody');
    if (!tbody) return;
    tbody.innerHTML = resignationRequests.map(req => `
        <tr>
            <td>${req.full_name}</td>
            <td>${req.effective_date}</td>
            <td>${req.reason}</td>
            <td><span class="status-badge status-${req.status.toLowerCase()}">${req.status}</span></td>
            <td>
                ${req.status === 'Pending' ? `
                    <button class="btn btn-success btn-sm" onclick="updateResignationStatus(${req.id}, 'Processing')">Process</button>
                ` : (req.status === 'Processing' ? `<button class="btn btn-success btn-sm" onclick="updateResignationStatus(${req.id}, 'Completed')">Complete</button>` : '<span class="text-muted">Processed</span>')}
            </td>
        </tr>
    `).join('');
}

async function updateResignationStatus(id, status) {
    const response = await fetch('backend/api.php?action=update_resignation_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
    });
    const result = await response.json();
    if (result.success) {
        fetchData();
    }
}

// --- Deductions ---
function renderDeductions() {
    const govList = document.getElementById('gov-deductions-list');
    const companyList = document.getElementById('company-deductions-list');
    if (!govList || !companyList) return;

    const govDeductions = deductionsConfig.filter(d => d.is_government);
    const companyDeductions = deductionsConfig.filter(d => !d.is_government);

    govList.innerHTML = govDeductions.map(d => `
        <div class="deduction-item" style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid #eee;">
            <span>${d.name} (${d.value}${d.type === 'percentage' ? '%' : ' PHP'})</span>
            <div>
                <button class="btn btn-secondary btn-sm" onclick="editDeduction(${d.id})"><i class="fas fa-edit"></i></button>
                <button class="btn btn-danger btn-sm" onclick="deleteDeduction(${d.id})"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `).join('');

    companyList.innerHTML = companyDeductions.map(d => `
        <div class="deduction-item" style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid #eee;">
            <span>${d.name} (${d.value}${d.type === 'percentage' ? '%' : ' PHP'})</span>
            <div>
                <button class="btn btn-secondary btn-sm" onclick="editDeduction(${d.id})"><i class="fas fa-edit"></i></button>
                <button class="btn btn-danger btn-sm" onclick="deleteDeduction(${d.id})"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `).join('');
}



async function deleteDeduction(id) {
    if (confirm("Are you sure you want to delete this deduction?")) {
        const response = await fetch(`backend/api.php?action=delete_deduction&id=${id}`);
        const result = await response.json();
        if (result.success) {
            fetchData();
        }
    }
}

function editDeduction(id) {
    const deduction = deductionsConfig.find(d => d.id == id);
    if (!deduction) return;

    const name = prompt("Enter new name:", deduction.name);
    if (name === null) return;
    const type = prompt("Enter new type (percentage or fixed):", deduction.type);
    if (type === null) return;
    const value = parseFloat(prompt("Enter new value:", deduction.value));
    if (isNaN(value)) return;
    const is_active = confirm("Is this deduction active?");
    const is_government = confirm("Is this a government mandated deduction?");

    saveDeduction({ ...deduction, name, type, value, is_active, is_government });
}

// --- Settings ---
async function saveSettings() {
    const form = document.getElementById('settingsForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    const response = await fetch('backend/api.php?action=save_settings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    
    const result = await response.json();
    if (result.success) {
        alert('Settings updated!');
        window.location.reload();
    }
}

async function changePassword() {
    const oldPass = document.getElementById('oldPass').value;
    const newPass = document.getElementById('newPass').value;
    const confirmPass = document.getElementById('confirmPass').value;
    
    if (newPass !== confirmPass) {
        alert("New passwords do not match!");
        return;
    }
    
    const response = await fetch('backend/api.php?action=change_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ oldPass, newPass })
    });
    
    const result = await response.json();
    if (result.success) {
        alert("Password updated successfully!");
        closeModal('passwordModal');
    } else {
        alert(result.message);
    }
}

// --- Reports ---
function generateReport(type) {
    let csvContent = "data:text/csv;charset=utf-8,";
    let filename = `Report_${type}_${new Date().toISOString().split('T')[0]}.csv`;
    
    if (type === 'attendance') {
        csvContent += "Employee ID,Name,Date,Check-In,Check-Out,Status\n";
        attendanceLogs.forEach(log => csvContent += `${log.emp_code},${log.full_name},${log.log_date},${log.check_in},${log.check_out},${log.status}\n`);
    } else if (type === 'payroll') {
        csvContent += "Employee,Period,Basic Pay,Deductions,Net Pay,Status\n";
        payrollHistory.forEach(p => csvContent += `${p.full_name},${p.period},${p.basic_pay},${p.deductions},${p.net_pay},${p.status}\n`);
    } else if (type === 'employees') {
        csvContent += "Employee ID,Full Name,Position,Department,Status\n";
        employees.forEach(emp => csvContent += `${emp.employee_id},${emp.full_name},${emp.position},${emp.department},${emp.status}\n`);
    }

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function viewFacultyLoads(empId) {
    const emp = employees.find(e => e.id == empId);
    if (!emp) return;

    const facultyLoads = subjectLoads.filter(load => load.faculty_id == empId);
    const tbody = document.getElementById('viewLoadsTableBody');
    const title = document.getElementById('viewLoadsTitle');

    if (title) title.innerText = `Subject Loads: ${emp.full_name}`;
    
    if (tbody) {
        tbody.innerHTML = facultyLoads.map(load => `
            <tr>
                <td><strong>${load.code}</strong></td>
                <td>${load.description}</td>
                <td>${load.units}</td>
                <td>${load.hours}</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="deleteSubjectLoad('${load.id}'); viewFacultyLoads('${empId}');"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="text-center">No loads assigned to this faculty.</td></tr>';
    }

    openModal('viewLoadsModal');
}

// --- Biometrics Enrollment ---
async function initFaceEnrollment() {
    const select = document.getElementById('enrollEmployeeSelect');
    const employeeId = select.value;
    if (!employeeId) return alert("Select an employee first");

    const video = document.getElementById('video');
    const captureBtn = document.getElementById('captureBtn');
    const startBtn = document.getElementById('startEnrollBtn');
    
    document.getElementById('camera-placeholder').style.display = 'none';
    
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
        video.srcObject = stream;
        
        console.log("Loading face-api models...");
        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);
        console.log("Models loaded successfully");

        captureBtn.style.display = 'inline-block';
        captureBtn.disabled = true; // Disable until blink detected
        startBtn.style.display = 'none';
        
        const canvas = document.getElementById('overlay');
        
        // Wait for video metadata to get actual resolution
        await new Promise((resolve) => {
            if (video.readyState >= 2) resolve();
            else video.onloadedmetadata = () => resolve();
        });

        const displaySize = { width: video.videoWidth || 640, height: video.videoHeight || 480 };
        faceapi.matchDimensions(canvas, displaySize);

        let isEnrolling = false;
        let lastBox = null;
        let stabilityCounter = 0;
        const STABILITY_REQUIRED = 5; // number of stable frames before scanning
        const MOVEMENT_THRESHOLD = 20; // Slightly relaxed for better UX

        // Preview loop using requestAnimationFrame for smoother performance
        async function onPlay() {
            if (!video.srcObject || isEnrolling) return;
            
            // Higher input size for better landmark precision
            const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
                .withFaceLandmarks();
            
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            if (detection) {
                const resizedDetection = faceapi.resizeResults(detection, displaySize);
                const landmarks = resizedDetection.landmarks;
                const box = resizedDetection.detection.box;

                // Stability Check: Faster than waiting for a blink
                if (lastBox) {
                    const dx = Math.abs(box.x - lastBox.x);
                    const dy = Math.abs(box.y - lastBox.y);
                    if (dx < MOVEMENT_THRESHOLD && dy < MOVEMENT_THRESHOLD) {
                        stabilityCounter++;
                    } else {
                        stabilityCounter = 0;
                    }
                }
                lastBox = box;

                // Enable manual capture after a few stable frames
                if (stabilityCounter >= 3 && captureBtn) captureBtn.disabled = false;

                // Draw feedback
                faceapi.draw.drawDetections(canvas, resizedDetection);
                faceapi.draw.drawFaceLandmarks(canvas, resizedDetection);
                
                // Mirror text drawing so it's readable on mirrored canvas
                ctx.save();
                ctx.scale(-1, 1);
                ctx.translate(-canvas.width, 0);
                
                const textX = canvas.width - (box.x + box.width / 2);
                const textY = box.y + box.height + 30;

                ctx.font = "bold 18px Inter";
                ctx.textAlign = "center";
                
                if (stabilityCounter < STABILITY_REQUIRED) {
                    ctx.fillStyle = "#f20e0eff";
                    ctx.fillText("ALIGN FACE & HOLD STILL...", textX, textY);
                } else {
                    ctx.fillStyle = "#27ae60";
                    ctx.fillText(`Stability Verified!`, textX, textY);

                    if (!isEnrolling) {
                        isEnrolling = true;
                        
                        // Get the full descriptor
                        const fullDetection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
                            .withFaceLandmarks()
                            .withFaceDescriptor();

                        if (fullDetection) {
                            const confidence = Math.round(fullDetection.detection.score * 100);
                            
                            if (confidence >= 90) {
                                // Visual feedback
                                ctx.strokeStyle = "#27ae60";
                                ctx.lineWidth = 6;
                                ctx.strokeRect(box.x, box.y, box.width, box.height); 
                                ctx.fillText("Auto-Capturing...", textX, textY + 25);

                                console.log(`Face detected and verified (${confidence}%). Auto-enrolling...`);
                                
                                setTimeout(() => {
                                    saveFaceEnrollment(fullDetection.descriptor);
                                }, 1000);
                                ctx.restore();
                                return; // Stop the loop
                            } else {
                                isEnrolling = false; // Not enough confidence, keep looking
                            }
                        } else {
                            isEnrolling = false;
                        }
                    }
                }
                ctx.restore();
             } else {
                 stabilityCounter = 0;
                 lastBox = null;
                 if (captureBtn) captureBtn.disabled = true;
             }
             
             requestAnimationFrame(onPlay);
        }
        
        onPlay();

    } catch (err) {
        console.error("Biometrics initialization error:", err);
        alert("Camera access denied or models failed to load. Check console for details.");
        document.getElementById('camera-placeholder').style.display = 'block';
    }
}

async function saveFaceEnrollment(manualDescriptor = null) {
    const employeeId = document.getElementById('enrollEmployeeSelect').value;
    const video = document.getElementById('video');
    
    let descriptor = manualDescriptor;

    if (!descriptor) {
        // Use the same detector options as the live preview
        const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });
        const detection = await faceapi.detectSingleFace(video, options).withFaceLandmarks().withFaceDescriptor();
        if (detection) {
            descriptor = detection.descriptor;
        }
    }
    
    if (descriptor) {
        const response = await fetch('backend/api.php?action=save_face_descriptor', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: employeeId, descriptor: Array.from(descriptor) })
        });
        const result = await response.json();
        if (result.success) {
            alert("Face enrolled successfully!");
            // Stop camera tracks before reload
            if (video.srcObject) {
                video.srcObject.getTracks().forEach(track => track.stop());
            }
            window.location.reload();
        } else {
            alert("Error saving biometric data: " + result.message);
        }
    } else {
        alert("No face detected! Please ensure you are visible in the camera.");
    }
}

function populateEmployeeDropdown() {
    const enrollSelect = document.getElementById('enrollEmployeeSelect');
    if (enrollSelect) {
        enrollSelect.innerHTML = '<option value="">Select Employee...</option>' + 
            employees.map(emp => `<option value="${emp.id}">${emp.full_name} (${emp.employee_id})</option>`).join('');
    }

    const assignSelect = document.getElementById('assignEmployeeSelect');
    if (assignSelect) {
        assignSelect.innerHTML = '<option value="">Select Employee...</option>' + 
            employees.map(emp => `<option value="${emp.full_name}">${emp.full_name} (${emp.employee_id})</option>`).join('');
    }

    const assignDeductionSelect = document.getElementById('assignDeductionEmployeeSelect');
    if (assignDeductionSelect) {
        assignDeductionSelect.innerHTML = '<option value="">Select Employee...</option>' + 
            employees.map(emp => `<option value="${emp.full_name}">${emp.full_name} (${emp.employee_id})</option>`).join('');
    }

    const leaveBalanceSelect = document.getElementById('leaveBalanceEmployeeSelect');
    if (leaveBalanceSelect) {
        leaveBalanceSelect.innerHTML = '<option value="">Select Employee...</option>' + 
            employees.map(emp => `<option value="${emp.id}">${emp.full_name}</option>`).join('');
    }
}

// --- Allowances ---
function renderAllowances() {
    renderAllowanceCategories();
    renderAllowanceBreakdown();
    populateEmployeeDropdown();
    
    // Populate allowance types list for assignment
    const typesList = document.getElementById('allowanceTypesList');
    if (typesList) {
        typesList.innerHTML = allowanceCategories.map(cat => `
            <div style="margin-bottom: 5px;">
                <input type="checkbox" name="assignAllowanceType" value="${cat.name}"> ${cat.name}
            </div>
        `).join('');
    }
}

function renderAllowanceCategories() {
    const tbody = document.getElementById('allowanceCategoriesBody');
    if (!tbody) return;
    tbody.innerHTML = allowanceCategories.map(cat => `
        <tr>
            <td>${cat.name}</td>
            <td>₱${cat.rate.toLocaleString()}</td>
            <td>${cat.type}</td>
            <td>${cat.recurring}</td>
            <td>
                <button class="btn btn-danger btn-sm" onclick="deleteAllowanceCategory('${cat.name}')"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function renderAllowanceBreakdown() {
    const tbody = document.getElementById('allowanceBreakdownBody');
    if (!tbody) return;
    tbody.innerHTML = employeeAllowances.map(item => `
        <tr>
            <td>${item.employee}</td>
            <td>${item.benefit}</td>
            <td>₱${item.amount.toLocaleString()}</td>
            <td>${item.date}</td>
            <td>
                <button class="btn btn-danger btn-sm" onclick="deleteEmployeeAllowance('${item.employee}', '${item.benefit}')"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function addAllowanceCategory() {
    const name = document.getElementById('allowanceName').value;
    const type = document.getElementById('allowanceType').value;
    const rate = document.getElementById('allowanceRate').value;
    const desc = document.getElementById('allowanceDesc').value;

    if (!name || !rate) return alert("Please fill in all required fields.");

    allowanceCategories.push({
        name,
        rate: parseFloat(rate),
        type,
        recurring: 'Yes'
    });

    renderAllowances();
    document.getElementById('allowanceName').value = '';
    document.getElementById('allowanceRate').value = '';
    document.getElementById('allowanceDesc').value = '';
}

function assignAllowance() {
    const employee = document.getElementById('assignEmployeeSelect').value;
    const overrideAmount = document.getElementById('overrideAmount').value;
    const date = document.getElementById('effectiveDate').value;
    
    const selectedTypes = Array.from(document.querySelectorAll('input[name="assignAllowanceType"]:checked'))
        .map(cb => cb.value);

    if (!employee || selectedTypes.length === 0 || !date) {
        return alert("Please select an employee, at least one allowance type, and an effective date.");
    }

    selectedTypes.forEach(type => {
        const category = allowanceCategories.find(c => c.name === type);
        employeeAllowances.push({
            employee,
            benefit: type,
            amount: overrideAmount ? parseFloat(overrideAmount) : category.rate,
            date
        });
    });

    renderAllowances();
    document.getElementById('overrideAmount').value = '';
    document.getElementById('effectiveDate').value = '';
    document.querySelectorAll('input[name="assignAllowanceType"]:checked').forEach(cb => cb.checked = false);
}

function deleteAllowanceCategory(name) {
    if (confirm(`Are you sure you want to delete the ${name} category?`)) {
        allowanceCategories = allowanceCategories.filter(c => c.name !== name);
        renderAllowances();
    }
}

function deleteEmployeeAllowance(employee, benefit) {
    if (confirm(`Are you sure you want to remove ${benefit} for ${employee}?`)) {
        employeeAllowances = employeeAllowances.filter(a => !(a.employee === employee && a.benefit === benefit));
        renderAllowances();
    }
}

// --- Deductions ---
function renderDeductions() {
    renderDeductionCategories();
    renderDeductionBreakdown();
    populateEmployeeDropdown();
    
    // Populate deduction types list for assignment
    const typesList = document.getElementById('deductionTypesList');
    if (typesList) {
        typesList.innerHTML = deductionCategories.map(cat => `
            <div style="margin-bottom: 5px;">
                <input type="checkbox" name="assignDeductionType" value="${cat.name}"> ${cat.name}
            </div>
        `).join('');
    }
}

function renderDeductionCategories() {
    const tbody = document.getElementById('deductionCategoriesBody');
    if (!tbody) return;
    tbody.innerHTML = deductionCategories.map(cat => `
        <tr>
            <td>${cat.name}</td>
            <td>${cat.type === 'Percentage' ? cat.rate + '%' : '₱' + cat.rate.toLocaleString()}</td>
            <td>${cat.type}</td>
            <td>${cat.recurring}</td>
            <td>
                <button class="btn btn-danger btn-sm" onclick="deleteDeductionCategory('${cat.name}')"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function renderDeductionBreakdown() {
    const tbody = document.getElementById('deductionBreakdownBody');
    if (!tbody) return;
    tbody.innerHTML = employeeDeductions.map(item => `
        <tr>
            <td>${item.employee}</td>
            <td>${item.deduction}</td>
            <td>₱${item.amount.toLocaleString()}</td>
            <td>${item.date}</td>
            <td>
                <button class="btn btn-danger btn-sm" onclick="deleteEmployeeDeduction('${item.employee}', '${item.deduction}')"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function addDeductionCategory() {
    const name = document.getElementById('deductionName').value;
    const type = document.getElementById('deductionType').value;
    const rate = document.getElementById('deductionRate').value;
    const desc = document.getElementById('deductionDesc').value;

    if (!name || !rate) return alert("Please fill in all required fields.");

    deductionCategories.push({
        name,
        rate: parseFloat(rate),
        type,
        recurring: 'Yes'
    });

    renderDeductions();
    document.getElementById('deductionName').value = '';
    document.getElementById('deductionRate').value = '';
    document.getElementById('deductionDesc').value = '';
}

function assignDeduction() {
    const employee = document.getElementById('assignDeductionEmployeeSelect').value;
    const overrideAmount = document.getElementById('deductionOverrideAmount').value;
    const date = document.getElementById('deductionEffectiveDate').value;
    
    const selectedTypes = Array.from(document.querySelectorAll('input[name="assignDeductionType"]:checked'))
        .map(cb => cb.value);

    if (!employee || selectedTypes.length === 0 || !date) {
        return alert("Please select an employee, at least one deduction type, and an effective date.");
    }

    selectedTypes.forEach(type => {
        const category = deductionCategories.find(c => c.name === type);
        let amount = overrideAmount ? parseFloat(overrideAmount) : 0;
        
        if (!overrideAmount) {
            if (category.type === 'Fixed') {
                amount = category.rate;
            } else {
                // Simplified percentage calculation for preview
                const emp = employees.find(e => e.full_name === employee);
                const salary = emp ? parseFloat(emp.basic_salary) : 20000;
                amount = salary * (category.rate / 100);
            }
        }

        employeeDeductions.push({
            employee,
            deduction: type,
            amount: amount,
            date
        });
    });

    renderDeductions();
    document.getElementById('deductionOverrideAmount').value = '';
    document.getElementById('deductionEffectiveDate').value = '';
    document.querySelectorAll('input[name="assignDeductionType"]:checked').forEach(cb => cb.checked = false);
}

function deleteDeductionCategory(name) {
    if (confirm(`Are you sure you want to delete the ${name} category?`)) {
        deductionCategories = deductionCategories.filter(c => c.name !== name);
        renderDeductions();
    }
}

function deleteEmployeeDeduction(employee, deduction) {
    if (confirm(`Are you sure you want to remove ${deduction} for ${employee}?`)) {
        employeeDeductions = employeeDeductions.filter(a => !(a.employee === employee && a.deduction === deduction));
        renderDeductions();
    }
}

// --- Faculty Payroll ---
function renderFacultyPayroll() {
    const tbody = document.getElementById('facultyPayrollTableBody');
    if (!tbody) return;

    // Filter faculty only
    const faculty = employees.filter(emp => emp.position === 'Faculty' || emp.department === 'Faculty');
    
    if (faculty.length === 0) {
        tbody.innerHTML = '<tr><td colspan="17" class="text-center">No faculty records found.</td></tr>';
        return;
    }

    // Update Header Info
    const today = new Date();
    document.getElementById('faculty-payroll-period').innerText = today.toLocaleString('default', { month: 'long', year: 'numeric' });
    document.getElementById('faculty-cutoff-period').innerText = `${today.getMonth() + 1}/01 - ${today.getMonth() + 1}/15`;

    tbody.innerHTML = faculty.map((emp, index) => {
        const basicPay = parseFloat(emp.basic_salary) || 0;
        const earned = basicPay / 2; // Semi-monthly
        const hdmfCont = emp.pagibig ? 100 : 0; // Check if Pag-IBIG exists
        const totalDeduction = hdmfCont;
        const honorarium = 0;
        const netPay = earned - totalDeduction + honorarium;

        return `
            <tr>
                <td>${index + 1}</td>
                <td style="text-align: left; padding-left: 10px;"><strong>${emp.full_name}</strong></td>
                <td>₱${basicPay.toLocaleString()}</td>
                <td>₱${earned.toLocaleString()}</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>₱${hdmfCont.toLocaleString()}</td>
                <td>0</td>
                <td>0</td>
                <td>₱${totalDeduction.toLocaleString()}</td>
                <td>₱${honorarium.toLocaleString()}</td>
                <td><strong>₱${netPay.toLocaleString()}</strong></td>
            </tr>
        `;
    }).join('');
}

function showRunFacultyPayroll() {
    const start = prompt("Enter Start Date (YYYY-MM-DD):", new Date().toISOString().split('T')[0]);
    if (!start) return;
    const end = prompt("Enter End Date (YYYY-MM-DD):", new Date().toISOString().split('T')[0]);
    if (!end) return;
    
    if (confirm(`Run Faculty Payroll for ${start} to ${end}?`)) {
        runPayrollDirect(start, end);
    }
}

function showRunUtilityPayroll() {
    const start = prompt("Enter Start Date (YYYY-MM-DD):", new Date().toISOString().split('T')[0]);
    if (!start) return;
    const end = prompt("Enter End Date (YYYY-MM-DD):", new Date().toISOString().split('T')[0]);
    if (!end) return;
    
    if (confirm(`Run Utility Payroll for ${start} to ${end}?`)) {
        runPayrollDirect(start, end);
    }
}

function renderUtilityPayroll() {
    const tbody = document.getElementById('utilityPayrollTableBody');
    if (!tbody) return;

    const utility = employees.filter(emp => ['Maintenance', 'Security', 'Janitorial'].includes(emp.department) || emp.position === 'Utility');
    
    if (utility.length === 0) {
        tbody.innerHTML = '<tr><td colspan="15" class="text-center">No utility records found.</td></tr>';
        return;
    }

    const today = new Date();
    document.getElementById('utility-payroll-period').innerText = today.toLocaleString('default', { month: 'long', year: 'numeric' });
    document.getElementById('utility-cutoff-period').innerText = `${today.getMonth() + 1}/01 - ${today.getMonth() + 1}/15`;

    tbody.innerHTML = utility.map((emp, index) => {
        const basicPay = parseFloat(emp.basic_salary) || 0;
        const ratePerDay = basicPay / 22;
        const earned = ratePerDay * 11;
        const hdmfCont = emp.pagibig ? 100 : 0; // Check if Pag-IBIG exists
        const totalDeduction = hdmfCont;
        const netPay = earned - totalDeduction;

        return `
            <tr>
                <td>${index + 1}</td>
                <td style="text-align: left; padding-left: 10px;"><strong>${emp.full_name}</strong></td>
                <td>₱${ratePerDay.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td>₱${earned.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>₱${hdmfCont.toLocaleString()}</td>
                <td>0</td>
                <td>0</td>
                <td>₱${totalDeduction.toLocaleString()}</td>
                <td><strong>₱${netPay.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                <td>₱${netPay.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td>0</td>
            </tr>
        `;
    }).join('');
}

// --- Subject Loads ---
let subjectLoads = [];
async function fetchSubjectLoads() {
    const response = await fetch('backend/api.php?action=get_subject_loads');
    const result = await response.json();
    subjectLoads = Array.isArray(result) ? result : [];
    if (currentPage === 'subject_loads') renderSubjectLoads();
    if (currentPage === 'employees') renderEmployeeTable();
}

async function fetchMasterSubjects() {
    const response = await fetch('backend/api.php?action=get_subjects');
    const result = await response.json();
    masterSubjects = Array.isArray(result) ? result : [];
    if (currentPage === 'subject_loads') renderSubjectLoads();
}

function renderSubjectLoads() {
    // Render current loads
    const loadTbody = document.getElementById('subjectLoadsTableBody');
    if (loadTbody) {
        loadTbody.innerHTML = subjectLoads.map(load => `
            <tr>
                <td>${load.faculty_name}</td>
                <td>${load.code}</td>
                <td>${load.description}</td>
                <td>${load.units}</td>
                <td>${load.hours}</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="deleteSubjectLoad('${load.id}')"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="6" class="text-center">No subject loads assigned.</td></tr>';
    }

    // Render master subjects
    const subjectTbody = document.getElementById('subjectsTableBody');
    if (subjectTbody) {
        subjectTbody.innerHTML = masterSubjects.map(s => `
            <tr>
                <td><strong>${s.code}</strong></td>
                <td>${s.description}</td>
                <td>${s.units}</td>
                <td>${s.hours}</td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick="editMasterSubject('${s.id}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="deleteMasterSubject('${s.id}')"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="text-center">No subjects created yet.</td></tr>';
    }
}

async function saveSubjectLoad() {
    const facultyId = document.getElementById('loadFacultyId').value;
    const code = document.getElementById('loadSubjectCode').value;
    const description = document.getElementById('loadDescription').value;
    const units = document.getElementById('loadUnits').value;
    const hours = document.getElementById('loadHours').value;

    if (!facultyId || !code || !units || !hours) return alert("Please fill in all required fields.");

    const response = await fetch('backend/api.php?action=save_subject_load', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ faculty_id: facultyId, code, description, units, hours })
    });

    const result = await response.json();
    if (result.success) {
        closeModal('addLoadModal');
        document.getElementById('subjectLoadForm').reset();
        fetchSubjectLoads(); // Refresh loads
    } else {
        alert("Error: " + (result.message || "Failed to save."));
    }
}

async function deleteSubjectLoad(id) {
    if (confirm("Are you sure you want to delete this subject load?")) {
        const response = await fetch(`backend/api.php?action=delete_subject_load&id=${id}`);
        const result = await response.json();
        if (result.success) {
            fetchSubjectLoads();
        } else {
            alert("Error: " + (result.message || "Failed to delete."));
        }
    }
}

// --- Master Subject CRUD ---
function editMasterSubject(id) {
    const subject = masterSubjects.find(s => s.id == id);
    if (!subject) return;
    
    document.getElementById('subjectId').value = subject.id;
    document.getElementById('subjectCode').value = subject.code;
    document.getElementById('subjectDescription').value = subject.description;
    document.getElementById('subjectUnits').value = subject.units;
    document.getElementById('subjectHours').value = subject.hours;
    
    document.getElementById('subjectModalTitle').innerText = 'Edit Subject';
    openModal('subjectModal');
}

async function saveMasterSubject() {
    const id = document.getElementById('subjectId').value;
    const code = document.getElementById('subjectCode').value;
    const description = document.getElementById('subjectDescription').value;
    const units = document.getElementById('subjectUnits').value;
    const hours = document.getElementById('subjectHours').value;

    if (!code || !description) return alert("Code and Description are required.");

    const response = await fetch('backend/api.php?action=save_subject', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, code, description, units, hours })
    });

    const result = await response.json();
    if (result.success) {
        closeModal('subjectModal');
        document.getElementById('masterSubjectForm').reset();
        document.getElementById('subjectId').value = '';
        document.getElementById('subjectModalTitle').innerText = 'Create New Subject';
        fetchMasterSubjects();
    } else {
        alert("Error: " + (result.message || "Failed to save subject."));
    }
}

async function deleteMasterSubject(id) {
    if (confirm("Are you sure you want to delete this subject? It will not remove already assigned loads.")) {
        const response = await fetch(`backend/api.php?action=delete_subject&id=${id}`);
        const result = await response.json();
        if (result.success) {
            fetchMasterSubjects();
        } else {
            alert("Error: " + (result.message || "Failed to delete."));
        }
    }
}

// --- Payroll Officer Assignment ---
function renderPayrollOfficerAssignment() {
    const select = document.getElementById('payrollOfficerSelect');
    if (select) {
        select.innerHTML = '<option value="">Choose Employee...</option>' + 
            employees.map(emp => `<option value="${emp.id}">${emp.full_name} (${emp.position})</option>`).join('');
    }
    
    const list = document.getElementById('payrollOfficersList');
    if (list) {
        const officers = employees.filter(emp => emp.role === 'Payroll');
        list.innerHTML = officers.map(off => `
            <li style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <span>${off.full_name}</span>
                <button class="btn btn-danger btn-sm" onclick="removePayrollOfficer('${off.id}')">Revoke</button>
            </li>
        `).join('') || 'No officers assigned.';
    }
}

async function assignPayrollOfficerRole() {
    const empId = document.getElementById('payrollOfficerSelect').value;
    if (!empId) return alert("Please select an employee.");
    
    const response = await fetch(`backend/api.php?action=update_role&id=${empId}&role=Payroll`);
    const result = await response.json();
    if (result.success) {
        alert("Payroll Officer assigned successfully.");
        fetchData(); // Refresh local data
    }
}

async function removePayrollOfficer(empId) {
    if (confirm("Revoke payroll officer access?")) {
        const response = await fetch(`backend/api.php?action=update_role&id=${empId}&role=Employee`);
        const result = await response.json();
        if (result.success) {
            alert("Access revoked.");
            fetchData();
        }
    }
}

// --- Charts ---
let pChart, aChart;
function initCharts() {
    const ctxP = document.getElementById('payrollChart')?.getContext('2d');
    const ctxA = document.getElementById('attendanceChart')?.getContext('2d');
    if (!ctxP || !ctxA) return;

    if (pChart) pChart.destroy();
    if (aChart) aChart.destroy();

    // Stats counting
    document.getElementById('stat-total-emp').innerText = employees.length;
    document.getElementById('stat-present').innerText = attendanceLogs.filter(l => l.log_date === new Date().toISOString().split('T')[0] && l.check_in).length;
    document.getElementById('stat-leave').innerText = leaveRequests.filter(r => r.status === 'Pending').length;

    pChart = new Chart(ctxP, {
        type: 'line',
        data: {
            labels: ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
            datasets: [{
                label: 'Expenditure (₱)',
                data: [450000, 465000, 480000, 470000, 490000, 510000],
                borderColor: '#3b4fc9',
                backgroundColor: 'rgba(59, 79, 201, 0.1)',
                fill: true,
                tension: 0.4
            }]
        }
    });

    aChart = new Chart(ctxA, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'On Leave'],
            datasets: [{
                data: [employees.length * 0.9, employees.length * 0.05, employees.length * 0.05],
                backgroundColor: ['#27ae60', '#c0392b', '#f39c12']
            }]
        }
    });
}

// --- Auth ---
async function logout() {
    if (confirm("Are you sure you want to logout?")) {
        await fetch('backend/api.php?action=logout');
        window.location.href = 'login.php';
    }
}

// Initialize on Load
window.onload = () => {
    fetchData();
        fetchMasterSubjects();
        fetchSubjectLoads();
        const dateFilter = document.getElementById('attendanceDateFilter');
    if (dateFilter) dateFilter.addEventListener('change', renderAttendanceTable);
};
