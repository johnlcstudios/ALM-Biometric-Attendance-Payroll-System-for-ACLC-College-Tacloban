/**
 * Biometric Attendance & Payroll System - Core Logic (PHP/MySQL Version)
 */

// --- Dashboard Charts ---
let attendanceChart = null;
let pChart, aChart;

// --- Data Stores ---
let employees = [];
let attendanceLogs = [];
let payrollHistory = [];
let leaveRequests = [];
let loanRequests = [];
let resignationRequests = [];
let masterSubjects = [];
let subjectLoads = [];
let currentPage = 'dashboard';

// --- Helper Functions ---
function escapeHTML(str) {
    if (!str || typeof str !== 'string') return str || '';
    return str.replace(/[&<>"']/g, function(m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[m];
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'block';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = (event) => {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
};

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

function filterTable(input, tableId) {
    const filter = input.value.toLowerCase();
    const rows = document.getElementById(tableId).getElementsByTagName('tr');
    for (let i = 1; i < rows.length; i++) {
        const text = rows[i].textContent.toLowerCase();
        rows[i].style.display = text.includes(filter) ? '' : 'none';
    }
}

// --- Data Fetching ---
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

        const getArray = (data) => Array.isArray(data) ? data : [];
        
        employees = getArray(await fetchJSON('backend/api.php?action=get_employees'));
        attendanceLogs = getArray(await fetchJSON('backend/api.php?action=get_attendance'));
        payrollHistory = getArray(await fetchJSON('backend/api.php?action=get_payroll'));
        leaveRequests = getArray(await fetchJSON('backend/api.php?action=get_leave_requests'));
        loanRequests = getArray(await fetchJSON('backend/api.php?action=get_loan_requests'));
        resignationRequests = getArray(await fetchJSON('backend/api.php?action=get_resignation_requests'));
        masterSubjects = getArray(await fetchJSON('backend/api.php?action=get_subjects'));
        subjectLoads = getArray(await fetchJSON('backend/api.php?action=get_subject_loads'));

        const dashboardStats = await fetchJSON('backend/api.php?action=get_dashboard_stats');
        if (dashboardStats) {
            const totalEl = document.getElementById('stat-total-emp');
            const presentEl = document.getElementById('stat-present');
            const absentEl = document.getElementById('stat-absent');
            const leaveEl = document.getElementById('stat-leave');
            if (totalEl) totalEl.innerText = dashboardStats.total_employees;
            if (presentEl) presentEl.innerText = dashboardStats.present_today;
            if (absentEl) absentEl.innerText = dashboardStats.absent_today;
            if (leaveEl) leaveEl.innerText = leaveRequests.filter(r => r.status === 'Pending').length;
        }

        const urlParams = new URLSearchParams(window.location.search);
        const initialPage = urlParams.get('page') || 'dashboard';
        showPage(initialPage);

    } catch (error) {
        console.error("Error fetching data:", error);
    } finally {
        if (loadingOverlay) {
            loadingOverlay.style.opacity = '0';
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
                loadingOverlay.style.opacity = '1';
            }, 300);
        }
    }
}

// --- Navigation ---
function showPage(pageId) {
    currentPage = pageId;
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-btn, .nav-link').forEach(l => l.classList.remove('active'));

    const activePage = document.getElementById(pageId);
    if (activePage) activePage.classList.add('active');

    const activeLink = document.querySelector(`.nav-btn[onclick*="${pageId}"], .nav-link[onclick*="${pageId}"]`);
    if (activeLink) activeLink.classList.add('active');

    // Update Page Title
    const titles = {
        'dashboard': 'Dashboard Overview',
        'employees': 'Employee Directory',
        'attendance': 'Attendance Tracking',
        'payroll': 'Payroll Processing',
        'leave': 'Leave Management',
        'loans': 'Loan Management',
        'reports': 'System Reports',
        'subject_loads': 'Subject Load Management',
        'settings': 'Company Settings'
    };
    const titleEl = document.getElementById('current-page-title');
    if (titleEl) titleEl.innerText = titles[pageId] || 'Admin Hub';

    // Render respective tables
    if (pageId === 'employees') renderEmployeeTable();
    if (pageId === 'attendance') renderAttendanceTable();
    if (pageId === 'payroll') renderPayrollTable();
    if (pageId === 'leave') renderLeaveTable();
    if (pageId === 'loans') renderLoanTable();
    if (pageId === 'subject_loads') renderMasterSubjects();
    if (pageId === 'biometrics') populateEnrollmentSelect();
    if (pageId === 'dashboard') initCharts();
}

function populateEnrollmentSelect() {
    const select = document.getElementById('enrollEmployeeSelect');
    if (!select) return;
    
    select.innerHTML = '<option value="">Select Employee...</option>' + 
        employees.map(emp => `<option value="${emp.id}">${escapeHTML(emp.full_name)} (${escapeHTML(emp.employee_id)})</option>`).join('');
}

// --- Render Functions ---
function renderEmployeeTable() {
    const tbody = document.getElementById('employeeTableBody');
    if (!tbody) return;

    tbody.innerHTML = employees.map(emp => {
        const isFaculty = (emp.position || '').toLowerCase() === 'faculty';
        const loadCount = subjectLoads.filter(load => load.faculty_id == emp.id).length;
        
        const actionHtml = isFaculty ? `
            <button class="btn btn-info btn-sm" onclick="viewFacultyLoads('${emp.id}')">
                <i class="fas fa-book"></i> View (${loadCount})
            </button>
            <button class="btn btn-primary btn-sm" onclick="addSubjectLoadModal('${emp.id}')">
                <i class="fas fa-plus"></i> Add Load
            </button>
        ` : '---';

        return `
            <tr>
                <td><strong>${escapeHTML(emp.employee_id)}</strong></td>
                <td>
                    <div class="user-info">
                        <div class="user-details">
                            <span class="name">${escapeHTML(emp.full_name)}</span>
                            <span class="email">${escapeHTML(emp.email)}</span>
                        </div>
                    </div>
                </td>
                <td>${escapeHTML(emp.position)}</td>
                <td>${escapeHTML(emp.department)}</td>
                <td>${actionHtml}</td>
                <td><span class="badge badge-${(emp.status || 'Active').toLowerCase()}">${escapeHTML(emp.status || 'Active')}</span></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-icon" title="Edit" onclick="editEmployee('${emp.id}')"><i class="fas fa-edit"></i></button>
                        <button class="btn-icon text-danger" title="Delete" onclick="deleteEmployee('${emp.id}')"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        `;
    }).join('') || '<tr><td colspan="7" class="text-center">No employees found.</td></tr>';
}

function renderMasterSubjects() {
    const subjectTbody = document.getElementById('subjectsTableBody');
    if (subjectTbody) {
        subjectTbody.innerHTML = masterSubjects.map(s => `
            <tr>
                <td><strong>${escapeHTML(s.code)}</strong></td>
                <td>${escapeHTML(s.description)}</td>
                <td>${escapeHTML(s.units)}</td>
                <td>${escapeHTML(s.hours)}</td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick="editMasterSubject('${s.id}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="deleteMasterSubject('${s.id}')"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="text-center">No subjects created yet.</td></tr>';
    }
}

// --- Reports & Export ---
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

// --- Employee Management ---
function renderEmployeeTable() {
    const tbody = document.getElementById('employeeTableBody');
    if (!tbody) return;
    tbody.innerHTML = employees.map(emp => {
        const isFaculty = (emp.position || '').toLowerCase() === 'faculty' || (emp.department || '').toLowerCase() === 'faculty' || (emp.department || '').toLowerCase() === 'education';
        const loadCount = subjectLoads.filter(load => load.faculty_id == emp.id).length;
        const statusLabel = (typeof emp.status === 'string' && emp.status.trim() !== '') ? emp.status.trim() : 'Active';
        const statusClass = statusLabel.toLowerCase().replace(' ', '-');
        
        return `
        <tr id="row-${emp.id}">
            <td>${escapeHTML(emp.employee_id)}</td>
            <td>
                <div><strong>${escapeHTML(emp.full_name)}</strong></div>
                <div class="text-muted" style="font-size: 0.8rem;">Username: ${escapeHTML(emp.username || 'N/A')}</div>
            </td>
            <td>${escapeHTML(emp.position)}</td>
            <td>${escapeHTML(emp.department)}</td>
            <td>
                ${isFaculty ? `
                    <span class="badge badge-info" style="cursor: pointer; padding: 5px 10px; border-radius: 4px; background: #3498db; color: white;" onclick="viewFacultyLoads('${emp.id}')">
                        ${loadCount} Loads
                    </span>
                ` : '<span class="text-muted">---</span>'}
            </td>
            <td><span class="status-badge status-${statusClass}">${escapeHTML(statusLabel)}</span></td>
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
                <div class="emp-avatar">${escapeHTML((log.full_name || 'E').charAt(0))}</div>
                <div class="emp-details">
                    <span class="emp-name">${escapeHTML(log.full_name || '---')}</span>
                    <span class="emp-id">${escapeHTML(log.emp_code || '---')}</span>
                </div>
            </div>
        `;

        return `
        <tr>
            <td>${employeeDisplay}</td>
            <td><span class="text-muted"><i class="far fa-calendar-alt"></i> ${escapeHTML(log.log_date)}</span></td>
            <td><strong>${log.check_in ? formatTime(log.check_in) : '---'}</strong></td>
            <td><span class="text-muted">${log.lunch_out ? formatTime(log.lunch_out) : '---'}</span></td>
            <td><span class="text-muted">${log.lunch_in ? formatTime(log.lunch_in) : '---'}</span></td>
            <td><strong>${log.check_out ? formatTime(log.check_out) : '---'}</strong></td>
            <td>
                <div class="status-pill-container">
                    <span class="status-badge status-${statusClass}">${escapeHTML(status)}</span>
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
            <td><strong>${escapeHTML(p.full_name)}</strong><br><small>${escapeHTML(p.emp_code)}</small></td>
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
            <td><strong>${escapeHTML(p.full_name)}</strong><br><small>${escapeHTML(p.emp_code)}</small></td>
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
                    <td>${escapeHTML(b.period)}</td>
                    <td>₱${parseFloat(b.total_disbursed).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td>${escapeHTML(new Date(b.processing_date).toLocaleDateString())}</td>
                    <td>Admin</td>
                    <td><span class="status-badge status-active">Completed</span></td>
                    <td>
                        <button class="btn btn-secondary btn-sm" onclick="viewBatch('${escapeHTML(b.period)}')"><i class="fas fa-eye"></i> View</button>
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
    const leaveBalanceSelect = document.getElementById('leaveBalanceEmployeeSelect');
    if (leaveBalanceSelect) {
        leaveBalanceSelect.innerHTML = '<option value="">Select Employee...</option>' + 
            employees.map(emp => `<option value="${emp.id}">${escapeHTML(emp.full_name)} (${escapeHTML(emp.employee_id)})</option>`).join('');
    }

    const tbody = document.getElementById('leaveTableBody');
    if (!tbody) return;
    tbody.innerHTML = leaveRequests.map(req => `
        <tr>
            <td>${escapeHTML(req.full_name)}</td>
            <td>${escapeHTML(req.leave_type || req.type)}</td>
            <td>${escapeHTML(req.start_date || '-')}</td>
            <td>${escapeHTML(req.end_date || '-')}</td>
            <td>${escapeHTML(req.reason)}</td>
            <td><span class="status-badge status-${req.status.toLowerCase()}">${escapeHTML(req.status)}</span></td>
            <td>
                ${req.status === 'Pending' ? `
                    <button class="btn btn-success btn-sm" onclick="updateLeaveStatus(${req.id}, 'Approved')"><i class="fas fa-check"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="updateLeaveStatus(${req.id}, 'Rejected')"><i class="fas fa-times"></i></button>
                ` : '<span class="text-muted">Processed</span>'}
            </td>
        </tr>
    `).join('') || '<tr><td colspan="7" class="text-center">No leave requests found.</td></tr>';
}

async function applyLeaveBalanceToAll() {
    const balance = document.getElementById('newLeaveBalance')?.value;
    if (balance === undefined || balance === null || balance === '') return alert("Please enter a leave balance first.");
    if (!confirm("Apply this leave balance to ALL active employees?")) return;

    const response = await fetch('backend/api.php?action=bulk_update_leave_balance', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ balance })
    });
    const result = await response.json();
    alert(result.message || (result.success ? "Leave balance applied to all employees." : "Failed to apply leave balance."));
    if (result.success) {
        document.getElementById('newLeaveBalance').value = '';
        fetchData();
    }
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
            <td>${escapeHTML(req.full_name)}</td>
            <td>₱${parseFloat(req.amount).toLocaleString()}</td>
            <td>${escapeHTML(req.reason)}</td>
            <td><span class="status-badge status-${req.status.toLowerCase()}">${escapeHTML(req.status)}</span></td>
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
            <td>${escapeHTML(req.full_name)}</td>
            <td>${escapeHTML(req.effective_date)}</td>
            <td>${escapeHTML(req.reason)}</td>
            <td><span class="status-badge status-${req.status.toLowerCase()}">${escapeHTML(req.status)}</span></td>
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
let enrolledFaceMatcher = null;
let isEnrollingBiometrics = false;

async function initFaceEnrollment() {
    const select = document.getElementById('enrollEmployeeSelect');
    const employeeId = select.value;
    if (!employeeId) return alert("Select an employee first");

    const video = document.getElementById('video');
    const captureBtn = document.getElementById('captureBtn');
    const startBtn = document.getElementById('startEnrollBtn');
    const previewContainer = document.querySelector('.camera-preview');
    
    document.getElementById('camera-placeholder').style.display = 'none';
    
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
        video.srcObject = stream;
        
        console.log("Loading face-api models...");
        // Use CDN models directly to avoid shard-loading errors from local filesystem
        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
        
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);
        console.log("Models loaded successfully");

        // Fetch existing faces to check for duplicates
        try {
            const res = await fetch('backend/api.php?action=get_enrolled_faces');
            const data = await res.json();
            if (data.success && data.faces.length > 0) {
                const labeledDescriptors = data.faces.map(f => {
                    const desc = JSON.parse(f.face_descriptor);
                    return new faceapi.LabeledFaceDescriptors(f.full_name, [new Float32Array(desc)]);
                });
                enrolledFaceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.45); // Using a strict threshold for duplicate check
            }
        } catch (e) {
            console.error("Error loading enrolled faces:", e);
        }

        captureBtn.style.display = 'inline-block';
        captureBtn.disabled = true; // Disable until blink detected
        startBtn.style.display = 'none';
        
        const canvas = document.getElementById('overlay');
        
        // Wait for video metadata to get actual resolution
        await new Promise((resolve) => {
            if (video.readyState >= 2) resolve();
            else video.onloadedmetadata = () => resolve();
        });

        isEnrollingBiometrics = false;
        let lastBox = null;
        let stabilityCounter = 0;
        let scanLineY = 0;
        let scanDirection = 1;
        const STABILITY_REQUIRED = 5; // number of stable frames before scanning
        const MOVEMENT_THRESHOLD = 20; // Slightly relaxed for better UX
        
        const getDetectorOptions = () => {
            if (faceapi.nets.ssdMobilenetv1.params) {
                return new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 });
            }
            return new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.5 });
        };
        const detectorOptions = getDetectorOptions();

        // Preview loop using requestAnimationFrame for smoother performance
        async function onPlay() {
            if (!video.srcObject) return;
            
            // Match dimensions and get dimensions for scaling
            const displaySize = faceapi.matchDimensions(canvas, video, true);

            // Draw scanning animation line
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Scanning Line Animation
            if (!isEnrollingBiometrics) {
                scanLineY += 5 * scanDirection;
                if (scanLineY >= canvas.height || scanLineY <= 0) scanDirection *= -1;
                
                ctx.beginPath();
                ctx.moveTo(0, scanLineY);
                ctx.lineTo(canvas.width, scanLineY);
                ctx.lineWidth = 2;
                ctx.strokeStyle = 'rgba(59, 79, 201, 0.5)';
                ctx.shadowBlur = 15;
                ctx.shadowColor = '#3b4fc9';
                ctx.stroke();
                ctx.shadowBlur = 0; // Reset shadow
            }

            // SKIP DETECTION IF CAPTURING
            if (isEnrollingBiometrics) {
                ctx.save();
                ctx.scale(-1, 1);
                ctx.translate(-canvas.width, 0);
                ctx.fillStyle = "#27ae60";
                ctx.font = "bold 20px Inter";
                ctx.textAlign = "center";
                ctx.fillText("CAPTURING BIOMETRICS...", canvas.width/2, canvas.height/2);
                ctx.restore();
                requestAnimationFrame(onPlay);
                return;
            }

            const detection = await faceapi.detectSingleFace(video, detectorOptions)
                .withFaceLandmarks();
            
            // Reset outline unless face detected and matched
            previewContainer.classList.remove('registered', 'success');

            if (detection) {
                const resizedDetection = faceapi.resizeResults(detection, displaySize);
                const box = resizedDetection.detection.box;

                // Check for already registered face
                let matchedName = null;
                if (enrolledFaceMatcher) {
                    const fullD = await faceapi.detectSingleFace(video, detectorOptions).withFaceLandmarks().withFaceDescriptor();
                    if (fullD) {
                        const match = enrolledFaceMatcher.findBestMatch(fullD.descriptor);
                        if (match.label !== 'unknown') {
                            matchedName = match.label;
                            previewContainer.classList.add('registered');
                        }
                    }
                }

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
                
                if (matchedName) {
                    ctx.fillStyle = "#db261f";
                    ctx.fillText(`ALREADY REGISTERED: ${matchedName}`, textX, textY);
                    
                    const selectedEmp = select.options[select.selectedIndex].text;
                    if (matchedName === selectedEmp.split(' (')[0]) {
                        ctx.fillStyle = "#27ae60";
                        ctx.fillText(`This is ${matchedName}!`, textX, textY + 25);
                    }
                } else if (stabilityCounter < STABILITY_REQUIRED) {
                    ctx.fillStyle = "#f20e0eff";
                    ctx.fillText("SCANNING... HOLD STILL", textX, textY);
                } else {
                    ctx.fillStyle = "#27ae60";
                    ctx.fillText(`Stability Verified!`, textX, textY);
                    previewContainer.classList.add('success');

                    if (!isEnrollingBiometrics) {
                        // Get the full descriptor
                        const fullDetection = await faceapi.detectSingleFace(video, detectorOptions)
                            .withFaceLandmarks()
                            .withFaceDescriptor();

                        if (fullDetection) {
                            const confidence = Math.round(fullDetection.detection.score * 100);
                            
                            if (confidence >= 90) {
                                isEnrollingBiometrics = true; // Set immediately to prevent multiple triggers
                                
                                // Visual feedback
                                ctx.strokeStyle = "#27ae60";
                                ctx.lineWidth = 6;
                                ctx.strokeRect(box.x, box.y, box.width, box.height); 
                                ctx.fillText("Auto-Capturing...", textX, textY + 25);

                                console.log(`Face detected and verified (${confidence}%). Auto-enrolling...`);
                                
                                setTimeout(() => {
                                    saveFaceEnrollment(fullDetection.descriptor);
                                }, 1000);
                            }
                        }
                    }
                }
                ctx.restore();
            } else {
                 stabilityCounter = 0;
                 lastBox = null;
                 if (captureBtn) captureBtn.disabled = true;
                 
                 // Show "Scanning" status when no face
                 ctx.save();
                 ctx.scale(-1, 1);
                 ctx.translate(-canvas.width, 0);
                 ctx.fillStyle = "#3b4fc9";
                 ctx.font = "16px Inter";
                 ctx.textAlign = "center";
                 ctx.fillText("POSITION FACE IN CIRCLE", canvas.width/2, canvas.height - 30);
                 ctx.restore();
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
    const captureBtn = document.getElementById('captureBtn');
    
    if (!employeeId) return alert("Please select an employee first");
    
    // Stop the preview loop from detecting faces
    isEnrollingBiometrics = true;

    if (captureBtn) {
        captureBtn.disabled = true;
        captureBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Capturing...';
    }

    const getOptions = () => {
        if (faceapi.nets.ssdMobilenetv1.params) {
            return new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 });
        }
        return new faceapi.TinyFaceDetectorOptions({ inputSize: 416, scoreThreshold: 0.5 });
    };
    const options = getOptions();
    const samples = [];
    
    // If we have a high-confidence descriptor from auto-capture, use it as first sample
    if (manualDescriptor && manualDescriptor.length === 128) {
        samples.push(Array.from(manualDescriptor));
    }

    // Try to collect more samples for a robust average
    console.log("Collecting biometric samples...");
    try {
        for (let i = 0; i < 15 && samples.length < 5; i++) {
            const det = await faceapi.detectSingleFace(video, options).withFaceLandmarks().withFaceDescriptor();
            if (det && det.descriptor && det.descriptor.length === 128 && det.detection?.score >= 0.75) {
                samples.push(Array.from(det.descriptor));
                console.log(`Sample ${samples.length}/5 collected`);
            }
            await new Promise(r => setTimeout(r, 100));
        }

        // We need at least 1 very good sample
        if (samples.length === 0) {
            alert("Could not detect a clear face. Please ensure good lighting and face the camera.");
            if (captureBtn) {
                captureBtn.disabled = false;
                captureBtn.innerHTML = '<i class="fas fa-user-plus"></i> Capture & Save';
            }
            isEnrollingBiometrics = false;
            return;
        }

        // Average the descriptors
        const averaged = new Array(128).fill(0);
        for (const s of samples) {
            for (let i = 0; i < 128; i++) averaged[i] += s[i];
        }
        for (let i = 0; i < 128; i++) averaged[i] /= samples.length;

        console.log("Saving biometric data to server...");
        const response = await fetch('backend/api.php?action=save_face_descriptor', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: employeeId, descriptor: averaged })
        });
        
        const result = await response.json();
        if (result.success) {
            alert("Face enrolled successfully!");
            if (video.srcObject) {
                video.srcObject.getTracks().forEach(track => track.stop());
            }
            window.location.reload();
        } else {
            alert("Error saving biometric data: " + result.message);
            if (captureBtn) {
                captureBtn.disabled = false;
                captureBtn.innerHTML = '<i class="fas fa-user-plus"></i> Capture & Save';
            }
            isEnrollingBiometrics = false;
        }
    } catch (err) {
        console.error("Capture Error:", err);
        alert("An error occurred during capture. Please try again.");
        isEnrollingBiometrics = false;
        if (captureBtn) {
            captureBtn.disabled = false;
            captureBtn.innerHTML = '<i class="fas fa-user-plus"></i> Capture & Save';
        }
    }
}

// --- Charts ---
function initCharts() {
    const ctxP = document.getElementById('payrollChart')?.getContext('2d');
    const ctxA = document.getElementById('attendanceChart')?.getContext('2d');
    if (!ctxP || !ctxA) return;

    if (pChart) pChart.destroy();
    if (aChart) aChart.destroy();

    const parseMySqlDateTime = (value) => {
        if (!value) return null;
        const iso = String(value).includes('T') ? String(value) : String(value).replace(' ', 'T');
        const d = new Date(iso);
        if (isNaN(d.getTime())) return null;
        return d;
    };

    const monthLabel = (d) => d.toLocaleString('default', { month: 'short' });
    const monthKey = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    const now = new Date();
    const last6 = [];
    for (let i = 5; i >= 0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        last6.push({ key: monthKey(d), label: monthLabel(d) });
    }

    const sums = Object.fromEntries(last6.map(m => [m.key, 0]));
    for (const p of payrollHistory) {
        const d = parseMySqlDateTime(p.created_at);
        if (!d) continue;
        const key = monthKey(d);
        if (key in sums) sums[key] += parseFloat(p.net_pay || 0);
    }

    const todayStr = new Date().toISOString().split('T')[0];
    const presentIds = new Set(attendanceLogs.filter(l => l.log_date === todayStr && l.check_in).map(l => String(l.employee_id)));
    const onLeaveCount = employees.filter(e => (e.status || '').toLowerCase() === 'on leave').length;
    const activeCount = employees.filter(e => (e.status || '').toLowerCase() !== 'inactive').length;
    const presentCount = presentIds.size;
    const absentCount = Math.max(activeCount - onLeaveCount - presentCount, 0);

    const totalEl = document.getElementById('stat-total-emp');
    const presentEl = document.getElementById('stat-present');
    const absentEl = document.getElementById('stat-absent');
    const leaveEl = document.getElementById('stat-leave');
    if (totalEl) totalEl.innerText = String(employees.length);
    if (presentEl) presentEl.innerText = String(presentCount);
    if (absentEl) absentEl.innerText = String(absentCount);
    if (leaveEl) leaveEl.innerText = String(leaveRequests.filter(r => r.status === 'Pending').length);

    pChart = new Chart(ctxP, {
        type: 'line',
        data: {
            labels: last6.map(m => m.label),
            datasets: [{
                label: 'Expenditure (₱)',
                data: last6.map(m => Math.round(sums[m.key])),
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
                data: [presentCount, absentCount, onLeaveCount],
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
    const dateFilter = document.getElementById('attendanceDateFilter');
    if (dateFilter) dateFilter.addEventListener('change', renderAttendanceTable);
};
