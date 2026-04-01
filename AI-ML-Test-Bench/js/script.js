/**
 * Biometric Attendance & Payroll System - Core Logic (PHP/MySQL Version)
 */

// --- Dashboard Charts ---
let attendanceChart = null;
function initCharts() {
    const ctx = document.getElementById('attendanceChart');
    if (!ctx) return;

    if (attendanceChart) attendanceChart.destroy();

    const data = {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        datasets: [{
            label: 'Present Employees',
            data: [12, 19, 15, 17, 14],
            backgroundColor: 'rgba(30, 1, 120, 0.2)',
            borderColor: 'rgba(30, 1, 120, 1)',
            borderWidth: 2,
            tension: 0.4
        }]
    };

    attendanceChart = new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
}

// --- Subject Loads ---
function renderSubjectLoadTable() {
    const tbody = document.getElementById('subjectLoadTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = subjectLoads.map(load => {
        const faculty = employees.find(e => e.id == load.faculty_id);
        return `
            <tr>
                <td>${load.code}</td>
                <td>${load.description}</td>
                <td>${load.units}</td>
                <td>${faculty ? faculty.full_name : 'Unassigned'}</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="deleteSubjectLoad(${load.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    }).join('') || '<tr><td colspan="5" class="text-center">No subject loads assigned.</td></tr>';
}

async function deleteSubjectLoad(id) {
    if (confirm("Remove this subject load assignment?")) {
        const response = await fetch(`backend/api.php?action=delete_subject_load&id=${id}`);
        const result = await response.json();
        if (result.success) fetchData();
    }
}

// --- Biometrics ---
function populateEmployeeDropdown() {
    const select = document.getElementById('enrollEmployeeSelect');
    if (!select) return;
    select.innerHTML = '<option value="">-- Select Employee --</option>' + 
        employees.map(e => `<option value="${e.id}">${e.full_name} (${e.employee_id})</option>`).join('');
}

// --- Admin / Payroll Access Management ---
function renderPayrollOfficerAssignment() {
    const tbody = document.getElementById('payrollOfficerTableBody');
    if (!tbody) return;
    
    const officers = employees.filter(e => e.position === 'Payroll Officer');
    tbody.innerHTML = officers.map(o => `
        <tr>
            <td>${o.full_name}</td>
            <td>${o.department}</td>
            <td><span class="status-badge status-active">Active Access</span></td>
            <td>
                <button class="btn btn-danger btn-sm" onclick="revokePayrollAccess(${o.id})">Revoke Access</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="4" class="text-center">No Payroll Officers assigned.</td></tr>';
}

async function revokePayrollAccess(empId) {
    if (confirm("Are you sure you want to revoke payroll officer access for this employee?")) {
        const response = await fetch(`backend/api.php?action=revoke_payroll_access&id=${empId}`);
        const result = await response.json();
        if (result.success) fetchData();
    }
}

async function exportFacultyPayroll() {
    const { jsPDF } = window.jspdf;
    
    // Check if table is empty, if so, load latest
    let tableRows = document.querySelectorAll("#facultyPayrollTableBody tr");
    if (tableRows.length === 0 || tableRows[0].innerText.includes("No faculty payroll")) {
        await loadFacultyPayroll('latest');
        tableRows = document.querySelectorAll("#facultyPayrollTableBody tr");
    }

    if (tableRows.length === 0 || tableRows[0].innerText.includes("No faculty payroll")) {
        return alert("No payroll data available to export.");
    }

    const doc = new jsPDF('l', 'mm', 'a3'); // Using A3 for 17 columns
    const period = document.getElementById('faculty-payroll-period').innerText;
    
    doc.setFontSize(18);
    doc.text("FACULTY PAYROLL REPORT", 14, 15);
    doc.setFontSize(11);
    doc.text(`Period: ${period}`, 14, 22);

    const rows = [];
    tableRows.forEach(tr => {
        const row = [];
        tr.querySelectorAll("td").forEach(td => {
            row.push(td.innerText.replace('₱', '').trim());
        });
        rows.push(row);
    });

    doc.autoTable({
        head: [['No.', 'Name', 'Basic Pay', 'Earned for the Period', 'Load', 'Over Time', 'Differential', 'Substitution', 'Adj. (+)', 'Absences', 'Latest/UT', 'HDMF Cont.', 'HDMF Loans', 'HDMF MP2', 'Total Deduction', 'Honorarium', 'Net Pay']],
        body: rows,
        startY: 30,
        styles: { fontSize: 7, cellPadding: 1 },
        headStyles: { fillColor: [30, 1, 120] }
    });

    doc.save(`Faculty_Payroll_${period.replace(/ /g, '_')}.pdf`);
}

async function exportUtilityPayroll() {
    const { jsPDF } = window.jspdf;

    // Check if table is empty, if so, load latest
    let tableRows = document.querySelectorAll("#utilityPayrollTableBody tr");
    if (tableRows.length === 0 || tableRows[0].innerText.includes("No utility payroll")) {
        await loadUtilityPayroll('latest');
        tableRows = document.querySelectorAll("#utilityPayrollTableBody tr");
    }

    if (tableRows.length === 0 || tableRows[0].innerText.includes("No utility payroll")) {
        return alert("No payroll data available to export.");
    }

    const doc = new jsPDF('l', 'mm', 'a3');
    const period = document.getElementById('utility-payroll-period').innerText;
    
    doc.setFontSize(18);
    doc.text("UTILITY PAYROLL REPORT", 14, 15);
    doc.setFontSize(11);
    doc.text(`Period: ${period}`, 14, 22);

    const rows = [];
    tableRows.forEach(tr => {
        const row = [];
        tr.querySelectorAll("td").forEach(td => {
            row.push(td.innerText.replace('₱', '').trim());
        });
        rows.push(row);
    });

    doc.autoTable({
        head: [['No.', 'Name', 'Rate per Day', 'Earned for the Period', 'OT/ Holiday Pay', 'Adj.(+)', 'Latest/UT', 'Adj. (-)', 'HDMF Cont.', 'HDMF Loans', 'Cash Advance', 'Total Deduction', 'Net Pay', 'ATM', 'Non ATM']],
        body: rows,
        startY: 30,
        styles: { fontSize: 8, cellPadding: 2 },
        headStyles: { fillColor: [30, 1, 120] }
    });

    doc.save(`Utility_Payroll_${period.replace(/ /g, '_')}.pdf`);
}

async function printSpecializedPayroll(tableId, title) {
    const isFaculty = tableId.startsWith('faculty');
    const periodId = isFaculty ? 'faculty-payroll-period' : 'utility-payroll-period';
    const tbodyId = isFaculty ? 'facultyPayrollTableBody' : 'utilityPayrollTableBody';
    
    // Check if table is empty, if so, load latest
    let tableRows = document.querySelectorAll(`#${tbodyId} tr`);
    if (tableRows.length === 0 || tableRows[0].innerText.includes("No faculty payroll") || tableRows[0].innerText.includes("No utility payroll")) {
        if (isFaculty) await loadFacultyPayroll('latest');
        else await loadUtilityPayroll('latest');
    }

    const period = document.getElementById(periodId).innerText;
    if (period === '---') {
        return alert("No payroll data available to print.");
    }

    const printWindow = window.open('', '', 'height=800,width=1200');
    
    printWindow.document.write('<html><head><title>' + title + '</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('@page { size: landscape; margin: 10mm; }');
    printWindow.document.write('body { font-family: "Inter", sans-serif; color: #333; margin: 0; padding: 20px; }');
    printWindow.document.write('h1 { color: #1e0178; margin-bottom: 5px; font-size: 24px; text-align: center; }');
    printWindow.document.write('.period { text-align: center; color: #666; margin-bottom: 30px; font-size: 14px; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; font-size: 9px; table-layout: fixed; }');
    printWindow.document.write('th { background-color: #1e0178 !important; color: white !important; -webkit-print-color-adjust: exact; padding: 10px 5px; text-transform: uppercase; border: 1px solid #444; }');
    printWindow.document.write('td { border: 1px solid #ddd; padding: 8px 4px; text-align: center; word-wrap: break-word; }');
    printWindow.document.write('tr:nth-child(even) { background-color: #f9f9f9; }');
    printWindow.document.write('.text-success { color: #27ae60 !important; font-weight: bold; }');
    printWindow.document.write('</style></head><body>');
    printWindow.document.write('<h1>' + title + '</h1>');
    printWindow.document.write('<div class="period"><strong>Payroll Period:</strong> ' + period + '</div>');
    
    // Clone the table to avoid modifying the original UI
    const tableClone = document.getElementById(tableId).cloneNode(true);
    // Remove any action buttons or icons if they exist in the table
    tableClone.querySelectorAll('button, i, .btn').forEach(el => el.remove());
    
    printWindow.document.write(tableClone.outerHTML);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    
    // Wait for content to load before printing
    printWindow.onload = function() {
        printWindow.print();
        // Optional: printWindow.close();
    };
}

// --- Initial Data Fetching ---
let employees = [];
let payrollHistory = [];
let attendanceLogs = [];
let leaveRequests = [];
let loanRequests = [];
let resignationRequests = [];
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
    if (pageId === 'faculty_payroll') loadFacultyPayroll('latest');
    if (pageId === 'utility_payroll') loadUtilityPayroll('latest');
    if (pageId === 'subject_loads') {
        renderSubjectLoadTable();
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
    const container = document.getElementById('subjectRowsContainer');
    if (!container) return;
    const rowId = Date.now();
    const row = document.createElement('div');
    row.className = 'subject-row-item';
    row.id = `subject-${rowId}`;
    row.innerHTML = `
        <input type="text" placeholder="Subject Name/Description" class="form-control" name="subDesc[]">
        <input type="number" placeholder="Units" class="form-control text-center" name="subUnits[]" value="3">
        <button type="button" class="btn btn-danger btn-sm" onclick="removeSubjectRow('${rowId}')">
            <i class="fas fa-trash"></i>
        </button>
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
        const subDescs = Array.from(document.querySelectorAll('input[name="subDesc[]"]')).map(i => i.value);
        const subUnits = Array.from(document.querySelectorAll('input[name="subUnits[]"]')).map(i => i.value);
        data.subjects = subDescs.map((desc, i) => ({ description: desc, units: subUnits[i] }));
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
const totalSteps = 4;

function toggleSubjectStep() {
    const position = document.querySelector('select[name="position"]').value;
    const step4Indicator = document.getElementById('step4-indicator');
    if (position === 'Faculty') {
        step4Indicator.style.opacity = '1';
        step4Indicator.style.pointerEvents = 'auto';
    } else {
        step4Indicator.style.opacity = '0.3';
        step4Indicator.style.pointerEvents = 'none';
    }
}

function goEmpStep(n) {
    const steps = document.querySelectorAll('.form-step');
    const indicators = document.querySelectorAll('.stepper-item');
    const position = document.querySelector('select[name="position"]').value;
    
    // Bulletproofing: Validate current step before going forward
    if (n > 0 && !validateCurrentStep()) return;

    let nextStep = currentStep + n;
    
    // Skip Step 4 (Subjects) if not Faculty
    if (nextStep === 4 && position !== 'Faculty') {
        if (n > 0) {
            saveEmployee(); // Finalize at step 3 for non-faculty
            return;
        }
    }

    // Update visibility
    steps[currentStep - 1].classList.remove('active');
    indicators[currentStep - 1].classList.remove('active');
    if (n > 0) indicators[currentStep - 1].classList.add('completed');
    
    currentStep = nextStep;
    
    steps[currentStep - 1].classList.add('active');
    indicators[currentStep - 1].classList.add('active');
    indicators[currentStep - 1].classList.remove('completed');
    
    // Button states
    document.getElementById('prevBtn').style.display = currentStep === 1 ? 'none' : 'inline-block';
    
    const isLastStep = (position === 'Faculty' && currentStep === 4) || (position !== 'Faculty' && currentStep === 3);
    
    document.getElementById('nextBtn').style.display = isLastStep ? 'none' : 'inline-block';
    document.getElementById('saveBtn').style.display = isLastStep ? 'inline-block' : 'none';
}

function validateCurrentStep() {
    const currentStepEl = document.getElementById(`step${currentStep}`);
    const inputs = currentStepEl.querySelectorAll('input[required], select[required]');
    let isValid = true;

    inputs.forEach(input => {
        const errorMsg = input.parentElement.querySelector('.error-msg');
        
        // Check HTML5 validity
        if (!input.checkValidity()) {
            input.classList.add('border-danger');
            if (errorMsg) errorMsg.style.display = 'block';
            isValid = false;
        } else {
            input.classList.remove('border-danger');
            if (errorMsg) errorMsg.style.display = 'none';
        }
        
        // Specific Email Check
        if (input.type === 'email' && input.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                input.classList.add('border-danger');
                if (errorMsg) {
                    errorMsg.innerText = "Invalid email format.";
                    errorMsg.style.display = 'block';
                }
                isValid = false;
            }
        }
    });

    return isValid;
}

function resetEmpModal() {
    currentStep = 1;
    editingEmployeeId = null;
    document.querySelectorAll('.form-step').forEach((s, i) => s.classList.toggle('active', i === 0));
    document.querySelectorAll('.stepper-item').forEach((s, i) => {
        s.classList.toggle('active', i === 0);
        s.classList.remove('completed');
    });
    document.getElementById('employeeForm').reset();
    document.getElementById('subjectRowsContainer').innerHTML = '';
    document.getElementById('prevBtn').style.display = 'none';
    document.getElementById('nextBtn').style.display = 'inline-block';
    document.getElementById('saveBtn').style.display = 'none';
    document.getElementById('employeeModalTitle').innerText = 'Add New Employee';
    toggleSubjectStep();
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

    // Update Summary Stats
    const totalLogs = filteredLogs.length;
    const ontimeCount = filteredLogs.filter(l => l.status === 'On-Time').length;
    const lateCount = filteredLogs.filter(l => l.status === 'Late').length;
    const absentCount = filteredLogs.filter(l => l.status === 'Absent').length;

    if (document.getElementById('att-total-logs')) {
        document.getElementById('att-total-logs').innerText = totalLogs;
        document.getElementById('att-ontime-count').innerText = ontimeCount;
        document.getElementById('att-late-count').innerText = lateCount;
        document.getElementById('att-absent-count').innerText = absentCount;
    }

    tbody.innerHTML = filteredLogs.map(log => {
        const status = log.status || '---';
        const statusClass = status.toLowerCase().replace(' ', '-');
        
        // Formatted Employee Display - ensuring it's in one column
        const employeeDisplay = `
            <div class="table-emp-info">
                <div class="emp-avatar">${(log.full_name || 'E').charAt(0)}</div>
                <div class="emp-details">
                    <span class="emp-name">${log.full_name || '---'}</span>
                    <span class="emp-id">${log.emp_code || '---'}</span>
                </div>
            </div>
        `;

        return `
        <tr>
            <td>${employeeDisplay}</td>
            <td><span class="text-muted"><i class="far fa-calendar-alt"></i> ${log.log_date}</span></td>
            <td><strong>${log.check_in ? formatTime(log.check_in) : '---'}</strong></td>
            <td><span class="text-muted">${log.lunch_out ? formatTime(log.lunch_out) : '---'}</span></td>
            <td><span class="text-muted">${log.lunch_in ? formatTime(log.lunch_in) : '---'}</span></td>
            <td><strong>${log.check_out ? formatTime(log.check_out) : '---'}</strong></td>
            <td>
                <div class="status-pill-container">
                    <span class="status-badge status-${statusClass}">${status}</span>
                    ${log.late_minutes > 0 ? `<span class="late-tag">${log.late_minutes}m late</span>` : ''}
                </div>
            </td>
            <td>
                <div class="table-actions">
                    <button class="btn-icon" title="View Details" onclick="viewAttendanceDetails(${log.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon delete" title="Flag/Report" onclick="flagAttendance(${log.id})"><i class="fas fa-flag"></i></button>
                </div>
            </td>
        </tr>
    `;}).join('');
}

function viewAttendanceDetails(id) {
    // Implement detail view if needed
    console.log("Viewing attendance details for ID:", id);
}

function flagAttendance(id) {
    // Implement flagging if needed
    console.log("Flagging attendance ID:", id);
}

function exportAttendance() {
    // Implement export functionality
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4');
    
    doc.text("Daily Attendance Logs", 14, 15);
    
    const rows = attendanceLogs.map(l => [
        l.emp_code,
        l.full_name,
        l.log_date,
        l.check_in || '---',
        l.lunch_out || '---',
        l.lunch_in || '---',
        l.check_out || '---',
        l.status
    ]);

    doc.autoTable({
        head: [['ID', 'Name', 'Date', 'Check-In', 'Lunch-Out', 'Lunch-In', 'Check-Out', 'Status']],
        body: rows,
        startY: 20
    });

    doc.save(`attendance_logs_${new Date().toISOString().split('T')[0]}.pdf`);
 }
 
 // Helper to format TIME from database (HH:MM:SS) to AM/PM
 function formatTime(timeStr) {
     if (!timeStr || timeStr === '---') return '---';
     try {
         const [h, m] = timeStr.split(':');
         let hours = parseInt(h);
         const ampm = hours >= 12 ? 'PM' : 'AM';
         hours = hours % 12;
         hours = hours ? hours : 12; // the hour '0' should be '12'
         return `${hours}:${m} ${ampm}`;
     } catch (e) {
         return timeStr;
     }
 }

// --- Specialized Payroll ---
async function showRunFacultyPayroll() {
    const start = prompt("Enter Cut-off Start (YYYY-MM-DD):", new Date().toISOString().split('T')[0]);
    if (!start) return;
    const end = prompt("Enter Cut-off End (YYYY-MM-DD):", new Date().toISOString().split('T')[0]);
    if (!end) return;
    
    if (confirm(`Run Faculty Payroll for ${start} to ${end}?`)) {
        const response = await fetch('backend/api.php?action=run_specialized_payroll', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'faculty', start_date: start, end_date: end })
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            const period = `${new Date(start).toLocaleDateString('en-US')} - ${new Date(end).toLocaleDateString('en-US')}`;
            document.getElementById('faculty-payroll-period').innerText = period;
            document.getElementById('faculty-cutoff-period').innerText = period;
            loadFacultyPayroll(period);
        }
    }
}

async function showRunUtilityPayroll() {
    const start = prompt("Enter Cut-off Start (YYYY-MM-DD):", new Date().toISOString().split('T')[0]);
    if (!start) return;
    const end = prompt("Enter Cut-off End (YYYY-MM-DD):", new Date().toISOString().split('T')[0]);
    if (!end) return;
    
    if (confirm(`Run Utility Payroll for ${start} to ${end}?`)) {
        const response = await fetch('backend/api.php?action=run_specialized_payroll', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'utility', start_date: start, end_date: end })
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            const period = `${new Date(start).toLocaleDateString('en-US')} - ${new Date(end).toLocaleDateString('en-US')}`;
            document.getElementById('utility-payroll-period').innerText = period;
            document.getElementById('utility-cutoff-period').innerText = period;
            loadUtilityPayroll(period);
        }
    }
}

async function loadFacultyPayroll(period = 'latest') {
    const tbody = document.getElementById('facultyPayrollTableBody');
    if (!tbody) return;
    
    const response = await fetch(`backend/api.php?action=get_faculty_payroll&period=${period}`);
    const result = await response.json();
    const data = result.data || [];
    const actualPeriod = result.period || '---';
    
    // Update Period Display in UI
    const periodDisplay = document.getElementById('faculty-payroll-period');
    if (periodDisplay) periodDisplay.innerText = actualPeriod;

    tbody.innerHTML = data.map((p, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><strong>${p.full_name}</strong><br><small>${p.emp_code}</small></td>
            <td>₱${parseFloat(p.basic_salary).toLocaleString()}</td>
            <td>₱${parseFloat(p.basic_pay).toLocaleString()}</td>
            <td>₱5,000.00</td>
            <td>₱0.00</td>
            <td>₱0.00</td>
            <td>₱0.00</td>
            <td>₱0.00</td>
            <td>(₱0.00)</td>
            <td>(₱${parseFloat(p.deductions).toLocaleString()})</td>
            <td>₱100.00</td>
            <td>₱0.00</td>
            <td>₱0.00</td>
            <td>₱${parseFloat(p.deductions + 100).toLocaleString()}</td>
            <td>₱0.00</td>
            <td class="text-success"><strong>₱${parseFloat(p.net_pay).toLocaleString()}</strong></td>
        </tr>
    `).join('') || '<tr><td colspan="17" class="text-center">No faculty payroll records for this period.</td></tr>';
}

async function loadUtilityPayroll(period = 'latest') {
    const tbody = document.getElementById('utilityPayrollTableBody');
    if (!tbody) return;
    
    const response = await fetch(`backend/api.php?action=get_utility_payroll&period=${period}`);
    const result = await response.json();
    const data = result.data || [];
    const actualPeriod = result.period || '---';
    
    // Update Period Display in UI
    const periodDisplay = document.getElementById('utility-payroll-period');
    if (periodDisplay) periodDisplay.innerText = actualPeriod;

    tbody.innerHTML = data.map((p, index) => `
        <tr>
            <td>${index + 1}</td>
            <td><strong>${p.full_name}</strong><br><small>${p.emp_code}</small></td>
            <td>₱${(parseFloat(p.basic_salary)/22).toFixed(2)}</td>
            <td>₱${parseFloat(p.basic_pay).toLocaleString()}</td>
            <td>₱0.00</td>
            <td>₱0.00</td>
            <td>(₱${parseFloat(p.deductions).toLocaleString()})</td>
            <td>₱0.00</td>
            <td>₱100.00</td>
            <td>₱0.00</td>
            <td>₱0.00</td>
            <td>₱${parseFloat(p.deductions + 100).toLocaleString()}</td>
            <td class="text-success"><strong>₱${parseFloat(p.net_pay).toLocaleString()}</strong></td>
            <td>₱${parseFloat(p.net_pay).toLocaleString()}</td>
            <td>₱0.00</td>
        </tr>
    `).join('') || '<tr><td colspan="15" class="text-center">No utility payroll records for this period.</td></tr>';
}

// --- Payroll ---
async function runPayroll() {
    const start_date = document.getElementById('payrollStartDate').value;
    const end_date = document.getElementById('payrollEndDate').value;
    const category = document.getElementById('payrollCategorySelect').value;
    
    if (!start_date || !end_date) {
        return alert('Please select both a start and end date.');
    }

    const categoryText = category === 'all' ? 'all employees' : `${category} staff`;
    if (confirm(`Run payroll for ${categoryText} from ${start_date} to ${end_date}?`)) {
        const response = await fetch('backend/api.php?action=run_payroll', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ start_date, end_date, category })
        });
        
        const result = await response.json();
        if (result.success) {
            alert(result.message || `Payroll processed for ${start_date} to ${end_date}`);
            closeModal('runPayrollModal');
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
    openModal('runPayrollModal');
    // Set default dates to current month
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
    
    document.getElementById('payrollStartDate').value = firstDay;
    document.getElementById('payrollEndDate').value = lastDay;
    document.getElementById('payrollCategorySelect').value = 'all';
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

// --- Allowances ---
async function addAllowanceCategory() {
    const name = document.getElementById('allowanceName').value;
    const type = document.getElementById('allowanceType').value;
    const rate = document.getElementById('allowanceRate').value;
    const description = document.getElementById('allowanceDesc').value;

    if (!name || !rate) return alert("Please enter a name and rate.");

    const response = await fetch('backend/api.php?action=add_allowance_category', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, type, rate, description })
    });
    const result = await response.json();
    alert(result.message);
    if (result.success) {
        document.getElementById('allowanceName').value = '';
        document.getElementById('allowanceRate').value = '';
        document.getElementById('allowanceDesc').value = '';
        renderAllowances();
    }
}

async function assignAllowance() {
    const employee_id = document.getElementById('assignEmployeeSelect').value;
    const category_id = document.querySelector('input[name="allowanceTypeRadio"]:checked')?.value;
    const override_amount = document.getElementById('overrideAmount').value;
    const effective_date = document.getElementById('effectiveDate').value;

    if (!employee_id || !category_id) return alert("Please select an employee and an allowance category.");

    const response = await fetch('backend/api.php?action=assign_employee_allowance', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ employee_id, category_id, override_amount, effective_date })
    });
    const result = await response.json();
    alert(result.message);
    if (result.success) {
        renderAllowances();
    }
}

async function applyAllowanceToAll() {
    const category_id = document.querySelector('input[name="allowanceTypeRadio"]:checked')?.value;
    const override_amount = document.getElementById('overrideAmount').value;
    const effective_date = document.getElementById('effectiveDate').value;

    if (!category_id) return alert("Please select an allowance category first.");
    if (!confirm("Are you sure you want to apply this allowance to ALL active employees?")) return;

    const response = await fetch('backend/api.php?action=bulk_assign_allowance', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ category_id, override_amount, effective_date })
    });
    const result = await response.json();
    alert(result.message);
    if (result.success) renderAllowances();
}

async function renderAllowances() {
    // 1. Render Categories Table
    const categoriesResponse = await fetch('backend/api.php?action=get_allowance_categories');
    const categories = await categoriesResponse.json();
    const categoriesBody = document.getElementById('allowanceCategoriesBody');
    if (categoriesBody) {
        categoriesBody.innerHTML = categories.map(c => `
            <tr>
                <td>${c.name}</td>
                <td>₱${parseFloat(c.rate).toLocaleString()}</td>
                <td>${c.type}</td>
                <td>Yes</td>
                <td>
                    <button class="btn-icon delete" onclick="deleteAllowanceCategory(${c.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="text-center">No categories found.</td></tr>';
    }

    // 2. Render Assignment List for Radio Selection
    const typesList = document.getElementById('allowanceTypesList');
    if (typesList) {
        typesList.innerHTML = categories.map(c => `
            <div class="selection-item-gray">
                <input type="radio" name="allowanceTypeRadio" value="${c.id}" id="allowance_${c.id}">
                <label for="allowance_${c.id}">${c.name} (${c.type}: ${c.rate})</label>
            </div>
        `).join('') || '<p class="text-muted p-2">No categories available.</p>';
    }

    // 3. Populate Employee Dropdown
    const empSelect = document.getElementById('assignEmployeeSelect');
    if (empSelect) {
        const empResponse = await fetch('backend/api.php?action=get_employees');
        const emps = await empResponse.json();
        empSelect.innerHTML = '<option value="">Select Employee...</option>' + 
            emps.map(e => `<option value="${e.id}">${e.full_name} (${e.employee_id})</option>`).join('');
    }

    // 4. Render Breakdown Table
    const breakdownResponse = await fetch('backend/api.php?action=get_allowance_breakdown');
    const breakdown = await breakdownResponse.json();
    const breakdownBody = document.getElementById('allowanceBreakdownBody');
    if (breakdownBody) {
        breakdownBody.innerHTML = breakdown.map(b => `
            <tr>
                <td><strong>${b.full_name}</strong><br><small>${b.emp_code}</small></td>
                <td>${b.category_name}</td>
                <td>₱${parseFloat(b.override_amount || b.category_rate).toLocaleString()}</td>
                <td>${b.effective_date || '---'}</td>
                <td>
                    <button class="btn-icon delete" onclick="deleteEmployeeAllowance(${b.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="text-center">No assignments found.</td></tr>';
    }
}

async function deleteEmployeeAllowance(id) {
    if (confirm("Remove this allowance assignment?")) {
        const response = await fetch(`backend/api.php?action=delete_employee_allowance&id=${id}`);
        const result = await response.json();
        if (result.success) renderAllowances();
    }
}

async function deleteAllowanceCategory(id) {
    if (confirm("Delete this category? This will also remove assignments.")) {
        const response = await fetch(`backend/api.php?action=delete_allowance_category&id=${id}`);
        const result = await response.json();
        if (result.success) renderAllowances();
    }
}

// --- Deductions ---
async function addDeductionCategory() {
    const name = document.getElementById('deductionName').value;
    const type = document.getElementById('deductionType').value;
    const value = document.getElementById('deductionRate').value;
    const description = document.getElementById('deductionDesc').value;

    if (!name || !value) return alert("Please enter a name and rate.");

    const response = await fetch('backend/api.php?action=save_deduction', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, type, value, description, is_active: true, is_government: false })
    });
    const result = await response.json();
    if (result.success) {
        document.getElementById('deductionName').value = '';
        document.getElementById('deductionRate').value = '';
        document.getElementById('deductionDesc').value = '';
        renderDeductions();
    }
}

async function assignDeduction() {
    const employee_id = document.getElementById('assignDeductionEmployeeSelect').value;
    const deduction_id = document.querySelector('input[name="deductionTypeRadio"]:checked')?.value;
    const override_amount = document.getElementById('deductionOverrideAmount').value;
    const effective_date = document.getElementById('deductionEffectiveDate').value;

    if (!employee_id || !deduction_id) return alert("Please select an employee and a deduction category.");

    const response = await fetch('backend/api.php?action=assign_employee_deduction', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ employee_id, deduction_id, override_amount, effective_date })
    });
    const result = await response.json();
    alert(result.message);
    if (result.success) renderDeductions();
}

async function applyDeductionToAll() {
    const deduction_id = document.querySelector('input[name="deductionTypeRadio"]:checked')?.value;
    const override_amount = document.getElementById('deductionOverrideAmount').value;
    const effective_date = document.getElementById('deductionEffectiveDate').value;

    if (!deduction_id) return alert("Please select a deduction category first.");
    if (!confirm("Are you sure you want to apply this deduction to ALL active employees?")) return;

    const response = await fetch('backend/api.php?action=bulk_assign_deduction', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ deduction_id, override_amount, effective_date })
    });
    const result = await response.json();
    alert(result.message);
    if (result.success) renderDeductions();
}

async function renderDeductions() {
    // 1. Categories Table
    const categoriesResponse = await fetch('backend/api.php?action=get_deductions');
    const categories = await categoriesResponse.json();
    const categoriesBody = document.getElementById('deductionCategoriesBody');
    if (categoriesBody) {
        categoriesBody.innerHTML = categories.map(c => `
            <tr>
                <td>${c.name}</td>
                <td>₱${parseFloat(c.value).toLocaleString()}</td>
                <td>${c.type}</td>
                <td>${c.is_active ? 'Yes' : 'No'}</td>
                <td>
                    <button class="btn-icon delete" onclick="deleteDeductionCategory(${c.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="text-center">No categories found.</td></tr>';
    }

    // 2. Radio Selection List
    const typesList = document.getElementById('deductionTypesList');
    if (typesList) {
        typesList.innerHTML = categories.map(c => `
            <div class="selection-item-gray">
                <input type="radio" name="deductionTypeRadio" value="${c.id}" id="deduction_${c.id}">
                <label for="deduction_${c.id}">${c.name} (${c.type}: ${c.value})</label>
            </div>
        `).join('') || '<p class="text-muted p-2">No categories available.</p>';
    }

    // 3. Employee Dropdown
    const empSelect = document.getElementById('assignDeductionEmployeeSelect');
    if (empSelect) {
        const empResponse = await fetch('backend/api.php?action=get_employees');
        const emps = await empResponse.json();
        empSelect.innerHTML = '<option value="">Select Employee...</option>' + 
            emps.map(e => `<option value="${e.id}">${e.full_name} (${e.employee_id})</option>`).join('');
    }

    // 4. Breakdown Table
    const breakdownResponse = await fetch('backend/api.php?action=get_deduction_breakdown');
    const breakdown = await breakdownResponse.json();
    const breakdownBody = document.getElementById('deductionBreakdownBody');
    if (breakdownBody) {
        breakdownBody.innerHTML = breakdown.map(b => `
            <tr>
                <td><strong>${b.full_name}</strong><br><small>${b.emp_code}</small></td>
                <td>${b.category_name}</td>
                <td>₱${parseFloat(b.override_amount || b.category_rate).toLocaleString()}</td>
                <td>${b.effective_date || '---'}</td>
                <td>
                    <button class="btn-icon delete" onclick="deleteEmployeeDeduction(${b.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="text-center">No assignments found.</td></tr>';
    }
}

async function deleteDeductionCategory(id) {
    if (confirm("Delete this category?")) {
        const response = await fetch(`backend/api.php?action=delete_deduction&id=${id}`);
        const result = await response.json();
        if (result.success) renderDeductions();
    }
}

async function deleteEmployeeDeduction(id) {
    if (confirm("Remove this deduction assignment?")) {
        const response = await fetch(`backend/api.php?action=delete_employee_deduction&id=${id}`);
        const result = await response.json();
        if (result.success) renderDeductions();
    }
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

        let isEnrolling = false;
        let lastBox = null;
        let stabilityCounter = 0;
        const STABILITY_REQUIRED = 5; // number of stable frames before scanning
        const MOVEMENT_THRESHOLD = 20; // Slightly relaxed for better UX

        // Preview loop using requestAnimationFrame for smoother performance
        async function onPlay() {
            if (!video.srcObject || isEnrolling) return;
            
            // Match dimensions and get dimensions for scaling
            const displaySize = faceapi.matchDimensions(canvas, video, true);

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
