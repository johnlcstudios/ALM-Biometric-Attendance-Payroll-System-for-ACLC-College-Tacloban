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
    return str.replace(/[&<>"']/g, function (m) {
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

function showToast(message, type = 'info') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    Toast.fire({
        icon: type,
        title: message
    });
}

// Add keyframes for animations if not in CSS
if (!document.getElementById('toast-styles')) {
    const style = document.createElement('style');
    style.id = 'toast-styles';
    style.innerHTML = `
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
    `;
    document.head.appendChild(style);
}

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

async function fetchData(specificPage = null) {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) loadingOverlay.style.display = 'flex';

    const urlParams = new URLSearchParams(window.location.search);
    const activePageId = document.querySelector('.page.active')?.id;
    const page = specificPage || urlParams.get('page') || activePageId || 'dashboard';

    try {
        const getArray = (data) => Array.isArray(data) ? data : [];

        // Always fetch employees as they are used globally
        if (employees.length === 0 || page === 'employees') {
            employees = getArray(await fetchJSON('backend/api.php?action=get_employees'));
        }

        // Conditional fetching based on page
        if (page === 'dashboard') {
            const dashboardStats = await fetchJSON('backend/api.php?action=get_dashboard_stats');
            if (dashboardStats) {
                const totalEl = document.getElementById('stat-total-emp');
                const presentEl = document.getElementById('stat-present');
                const absentEl = document.getElementById('stat-absent');
                const leaveEl = document.getElementById('stat-leave');
                if (totalEl) totalEl.innerText = dashboardStats.total_employees;
                if (presentEl) presentEl.innerText = dashboardStats.present_today;
                if (absentEl) absentEl.innerText = dashboardStats.absent_today;

                // Still need leave requests for the count
                leaveRequests = getArray(await fetchJSON('backend/api.php?action=get_leave_requests'));
                if (leaveEl) leaveEl.innerText = leaveRequests.filter(r => r.status === 'Pending').length;
            }
        } else if (page === 'attendance') {
            attendanceLogs = getArray(await fetchJSON('backend/api.php?action=get_attendance'));
        } else if (page === 'payroll' || page === 'faculty_payroll' || page === 'utility_payroll') {
            payrollHistory = getArray(await fetchJSON('backend/api.php?action=get_payroll'));
        } else if (page === 'leave') {
            leaveRequests = getArray(await fetchJSON('backend/api.php?action=get_leave_requests'));
        } else if (page === 'loans') {
            loanRequests = getArray(await fetchJSON('backend/api.php?action=get_loan_requests'));
        } else if (page === 'resignations') {
            resignationRequests = getArray(await fetchJSON('backend/api.php?action=get_resignation_requests'));
        } else if (page === 'subject_loads' || page === 'employees') {
            masterSubjects = getArray(await fetchJSON('backend/api.php?action=get_subjects'));
            subjectLoads = getArray(await fetchJSON('backend/api.php?action=get_subject_loads'));
        } else if (page === 'allowances' || page === 'deductions') {
            // Already handled by their respective render functions called in showPage
        }

        await showPage(page);

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
function stopRegistrationCamera() {
    if (window.faceManager) {
        faceManager.stopCamera();
        faceManager.isProcessing = false;
        faceManager.registrationActive = false;
    }

    const video = document.getElementById('video');
    const canvas = document.getElementById('overlay');
    if (video) video.srcObject = null;

    // Clear the canvas explicitly
    if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    const placeholder = document.getElementById('camera-placeholder');
    if (placeholder) {
        placeholder.style.display = 'flex';
        // Reset placeholder text to default state
        const p = placeholder.querySelector('p');
        const small = placeholder.querySelector('small');
        const icon = placeholder.querySelector('i');
        if (p) p.innerText = "Camera is currently inactive";
        if (small) small.innerText = 'Select an employee and click "Start Registration"';
        if (icon) icon.className = "fas fa-video-slash";
    }

    const startBtn = document.getElementById('startRegBtn');
    if (startBtn) {
        startBtn.style.display = 'inline-block';
        startBtn.innerHTML = '<i class="fas fa-camera"></i> Start Registration';
        startBtn.disabled = false;
    }

    const captureBtn = document.getElementById('captureBtn');
    if (captureBtn) {
        captureBtn.style.display = 'none';
        captureBtn.disabled = false;
        captureBtn.innerHTML = '<i class="fas fa-user-plus"></i> Manual Capture';
    }
}

async function showPage(pageId) {
    if (currentPage === 'biometrics' && pageId !== 'biometrics') {
        stopRegistrationCamera();
    }
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
        'faculty_payroll': 'Faculty Payroll',
        'utility_payroll': 'Utility Payroll',
        'allowances': 'Allowances & Benefits',
        'deductions': 'Deductions Management',
        'leave': 'Leave Management',
        'loans': 'Loan Management',
        'resignations': 'Resignations',
        'reports': 'System Reports',
        'subject_loads': 'Subject Load Management',
        'settings': 'Company Settings',
        'biometrics': 'Face Registration'
    };
    const titleEl = document.getElementById('current-page-title');
    if (titleEl) titleEl.innerText = titles[pageId] || 'Admin Hub';

    // Render respective tables
    if (pageId === 'employees') renderEmployeeTable();
    if (pageId === 'attendance') renderAttendanceTable();
    if (pageId === 'payroll') renderPayrollTable();
    if (pageId === 'faculty_payroll') await loadFacultyPayroll('latest');
    if (pageId === 'utility_payroll') await loadUtilityPayroll('latest');
    if (pageId === 'allowances') await renderAllowances();
    if (pageId === 'deductions') await renderDeductions();
    if (pageId === 'leave') renderLeaveTable();
    if (pageId === 'loans') renderLoanTable();
    if (pageId === 'resignations') renderResignationTable();
    if (pageId === 'subject_loads') renderMasterSubjects();
    if (pageId === 'biometrics') populateRegistrationSelect();
    if (pageId === 'dashboard') initCharts();
}

function populateRegistrationSelect() {
    const select = document.getElementById('regEmployeeSelect');
    if (!select) return;

    select.innerHTML = '<option value="">Choose Employee...</option>' +
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

// Payroll History Print/Export Functions (NEW - Fixes broken buttons)
async function printPayrollHistory() {
    const tableRows = document.querySelectorAll("#payrollTableBody tr");
    if (tableRows.length === 0 || tableRows[0].textContent.includes("No data")) {
        showToast("No payroll history data available to print.", 'error');
        return;
    }

    const totalBatchesEl = document.getElementById('stat-total-batches');
    const totalDisbursedEl = document.getElementById('stat-total-disbursed');
    const lastRunEl = document.getElementById('stat-last-run');

    const printWindow = window.open('', '', 'height=800,width=1200');

    printWindow.document.write('<html><head><title>Payroll History Report</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('@page { size: landscape; margin: 10mm; }');
    printWindow.document.write('body { font-family: "Inter", sans-serif; color: #333; margin: 0; padding: 20px; }');
    printWindow.document.write('h1 { color: #1e0178; margin-bottom: 5px; font-size: 24px; text-align: center; }');
    printWindow.document.write('.stats { display: flex; gap: 20px; margin-bottom: 30px; }');
    printWindow.document.write('.stat { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; flex: 1; }');
    printWindow.document.write('.stat-label { font-size: 12px; color: #666; }');
    printWindow.document.write('.stat-value { font-size: 18px; font-weight: bold; color: #1e0178; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; font-size: 11px; }');
    printWindow.document.write('th { background-color: #1e0178 !important; color: white !important; -webkit-print-color-adjust: exact; padding: 12px 8px; border: 1px solid #444; }');
    printWindow.document.write('td { border: 1px solid #ddd; padding: 10px 8px; }');
    printWindow.document.write('tr:nth-child(even) { background-color: #f9f9f9; }');
    printWindow.document.write('.status-active { color: #27ae60; font-weight: bold; }');
    printWindow.document.write('</style></head><body>');

    printWindow.document.write('<h1>PAYROLL HISTORY REPORT</h1>');

    // Print Stats Summary
    printWindow.document.write('<div class="stats">');
    printWindow.document.write(`<div class="stat"><div class="stat-label">Total Batches</div><div class="stat-value">${totalBatchesEl?.textContent || '0'}</div></div>`);
    printWindow.document.write(`<div class="stat"><div class="stat-label">Total Disbursed</div><div class="stat-value">${totalDisbursedEl?.textContent || '₱0.00'}</div></div>`);
    printWindow.document.write(`<div class="stat"><div class="stat-label">Last Run</div><div class="stat-value">${lastRunEl?.textContent || '---'}</div></div>`);
    printWindow.document.write('</div>');

    // Clone table
    const table = document.getElementById('payrollTable').cloneNode(true);
    table.querySelectorAll('button, .btn').forEach(el => el.remove());
    printWindow.document.write(table.outerHTML);

    printWindow.document.write('</body></html>');
    printWindow.document.close();

    printWindow.onload = () => {
        printWindow.print();
    };

    showToast('Print preview opened. Use browser print dialog to print/save PDF.', 'success');
}

async function exportPayrollHistory() {
    const { jsPDF } = window.jspdf;

    const tableRows = document.querySelectorAll("#payrollTableBody tr");
    if (tableRows.length === 0 || tableRows[0].textContent.includes("No data")) {
        showToast("No payroll history data available to export.", 'error');
        return;
    }

    const doc = new jsPDF('l', 'mm', 'a4');
    const totalBatchesEl = document.getElementById('stat-total-batches');
    const totalDisbursedEl = document.getElementById('stat-total-disbursed');
    const lastRunEl = document.getElementById('stat-last-run');

    // Header
    doc.setFontSize(18);
    doc.text('PAYROLL HISTORY SUMMARY', 14, 15);
    doc.setFontSize(12);
    doc.text(`Generated: ${new Date().toLocaleDateString('en-PH')} ${new Date().toLocaleTimeString('en-PH')}`, 14, 25);

    // Stats
    doc.setFontSize(10);
    doc.text(`Total Batches: ${totalBatchesEl?.textContent || '0'} | Total Disbursed: ${totalDisbursedEl?.textContent || '₱0.00'} | Last Run: ${lastRunEl?.textContent || '---'}`, 14, 35);

    // Table data
    const rows = [];
    tableRows.forEach(tr => {
        const cells = Array.from(tr.querySelectorAll('td'));
        if (cells.length >= 6) {
            rows.push([
                cells[0].textContent.trim(),
                cells[1].textContent.trim(),
                cells[2].textContent.replace('₱', '').trim(),
                cells[3].textContent.trim(),
                cells[4].textContent.trim(),
                cells[5].textContent.trim()
            ]);
        }
    });

    doc.autoTable({
        head: [['PAYROLL BATCH', 'PERIOD', 'TOTAL DISBURSED', 'PROCESSING DATE', 'CREATED BY', 'STATUS']],
        body: rows,
        startY: 45,
        styles: { fontSize: 9, cellPadding: 3, halign: 'center' },
        headStyles: {
            fillColor: [30, 1, 120],
            textColor: 255,
            fontStyle: 'bold',
            halign: 'center'
        },
        columnStyles: { 2: { halign: 'right' } }, // Right-align amounts
        margin: { top: 45 }
    });

    doc.save(`Payroll_History_${new Date().toISOString().split('T')[0]}.pdf`);
    showToast('Payroll history exported as PDF!', 'success');
}// --- Reports & Export ---
async function exportFacultyPayroll() {
    const { jsPDF } = window.jspdf;

    // P0 Fix: Always explicitly fetch fresh data before reading the table
    await loadFacultyPayroll('latest');

    let tableRows = document.querySelectorAll("#facultyPayrollTableBody tr");

    if (tableRows.length === 0 || tableRows[0].innerText.includes("No faculty payroll")) {
        return showToast("No payroll data available to export.", 'error');
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

    // P0 Fix: Always explicitly fetch fresh data before reading the table
    await loadUtilityPayroll('latest');

    let tableRows = document.querySelectorAll("#utilityPayrollTableBody tr");

    if (tableRows.length === 0 || tableRows[0].innerText.includes("No utility payroll")) {
        return showToast("No payroll data available to export.", 'error');
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
        return showToast("No payroll data available to print.", 'error');
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
    printWindow.onload = function () {
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
    `;
    }).join('');
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
    if (!userId) return showToast('This employee does not have a user account.', 'error');
    
    const confirmResult = await Swal.fire({
        title: 'Reset Password?',
        text: "Are you sure you want to reset this employee's password to 'welcome123'?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1e0178',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, reset it!'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch(`backend/api.php?action=reset_password&user_id=${userId}`);
        const result = await response.json();
        if (result.success) {
            showToast(result.message || 'Password reset successful!', 'success');
        } else {
            showToast('Error: ' + (result.message || 'Failed to reset password.'), 'error');
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
    const confirmResult = await Swal.fire({
        title: 'Delete Employee?',
        text: "Are you sure you want to delete this employee? This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, delete!'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch(`backend/api.php?action=delete_employee&id=${id}`);
        const result = await response.json();
        if (result.success) {
            showToast('Employee deleted successfully.', 'success');
            fetchData();
        } else {
            showToast(result.message || 'Failed to delete employee.', 'error');
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

    const saveBtn = document.getElementById('saveBtn');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
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
            await fetchData();
            resetEmpModal();
            showToast('Employee saved successfully!', 'success');
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    } catch (err) {
        console.error("Save error:", err);
        showToast("Failed to connect to the server.", 'error');
    } finally {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-check-circle"></i> Save Employee';
        }
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
    `;
    }).join('');
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
    const { value: formValues } = await Swal.fire({
        title: 'Run Faculty Payroll',
        html:
            '<label>Cut-off Start</label><input id="swal-input1" class="swal2-input" type="date" value="' + new Date().toISOString().split('T')[0] + '">' +
            '<label>Cut-off End</label><input id="swal-input2" class="swal2-input" type="date" value="' + new Date().toISOString().split('T')[0] + '">',
        focusConfirm: false,
        preConfirm: () => {
            return [
                document.getElementById('swal-input1').value,
                document.getElementById('swal-input2').value
            ]
        }
    });

    if (formValues) {
        const [start, end] = formValues;
        const result = await Swal.fire({
            title: 'Confirm Action',
            text: `Run Faculty Payroll for ${start} to ${end}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, run it!'
        });

        if (result.isConfirmed) {
            const response = await fetch('backend/api.php?action=run_specialized_payroll', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'faculty', start_date: start, end_date: end })
            });
            const resultData = await response.json();
            showToast(resultData.message, resultData.success ? 'success' : 'error');
            if (resultData.success) {
                const period = `${new Date(start).toLocaleDateString('en-US')} - ${new Date(end).toLocaleDateString('en-US')}`;
                document.getElementById('faculty-payroll-period').innerText = period;
                document.getElementById('faculty-cutoff-period').innerText = period;
                loadFacultyPayroll(period);
            }
        }
    }
}

async function showRunUtilityPayroll() {
    const { value: formValues } = await Swal.fire({
        title: 'Run Utility Payroll',
        html:
            '<label>Cut-off Start</label><input id="swal-input1" class="swal2-input" type="date" value="' + new Date().toISOString().split('T')[0] + '">' +
            '<label>Cut-off End</label><input id="swal-input2" class="swal2-input" type="date" value="' + new Date().toISOString().split('T')[0] + '">',
        focusConfirm: false,
        preConfirm: () => {
            return [
                document.getElementById('swal-input1').value,
                document.getElementById('swal-input2').value
            ]
        }
    });

    if (formValues) {
        const [start, end] = formValues;
        const result = await Swal.fire({
            title: 'Confirm Action',
            text: `Run Utility Payroll for ${start} to ${end}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, run it!'
        });

        if (result.isConfirmed) {
            const response = await fetch('backend/api.php?action=run_specialized_payroll', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'utility', start_date: start, end_date: end })
            });
            const resultData = await response.json();
            showToast(resultData.message, resultData.success ? 'success' : 'error');
            if (resultData.success) {
                const period = `${new Date(start).toLocaleDateString('en-US')} - ${new Date(end).toLocaleDateString('en-US')}`;
                document.getElementById('utility-payroll-period').innerText = period;
                document.getElementById('utility-cutoff-period').innerText = period;
                loadUtilityPayroll(period);
            }
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
            <td>₱${(parseFloat(p.basic_salary) / 22).toFixed(2)}</td>
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
        return showToast('Please select both a start and end date.', 'error');
    }

    const runBtn = document.querySelector('button[onclick="runPayroll()"]');
    const categoryText = category === 'all' ? 'all employees' : `${category} staff`;

    const confirmResult = await Swal.fire({
        title: 'Confirm Action',
        text: `Run payroll for ${categoryText} from ${start_date} to ${end_date}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e0178',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, run it!'
    });

    if (confirmResult.isConfirmed) {
        if (runBtn) {
            runBtn.disabled = true;
            runBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        }

        try {
            const response = await fetch('backend/api.php?action=run_payroll', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ start_date, end_date, category })
            });

            const result = await response.json();
            if (result.success) {
                showToast(result.message || `Payroll processed for ${start_date} to ${end_date}`, 'success');
                closeModal('runPayrollModal');
                await fetchData();
            } else {
                showToast("Error: " + (result.message || "Failed to process payroll."), 'error');
            }
        } catch (err) {
            console.error("Payroll error:", err);
            showToast("Failed to connect to the server.", 'error');
        } finally {
            if (runBtn) {
                runBtn.disabled = false;
                runBtn.innerHTML = '<i class="fas fa-play"></i> Run Payroll';
            }
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
                document.getElementById('stat-total-disbursed').innerText = `₱${totalDisbursed.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
                document.getElementById('stat-last-run').innerText = batchList[0].period;
                document.getElementById('stat-last-staff-count').innerText = batchList[0].staff_count;
            }

            tbody.innerHTML = batchList.map((b, index) => `
                <tr>
                    <td><strong>BATCH-${101 + index}</strong></td>
                    <td>${escapeHTML(b.period)}</td>
                    <td>₱${parseFloat(b.total_disbursed).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
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
    showToast(result.message, result.success ? 'success' : 'error');
    fetchData();
}

function viewBatch(period) {
    const records = payrollHistory.filter(p => p.period === period);
    if (records.length === 0) return showToast("No records found for this batch.", 'error');

    let report = `<div style="text-align: left; font-family: monospace;">
        <p><strong>Payroll Details for ${period}</strong></p>
        <hr>
        <div style="display: flex; justify-content: space-between;">
            <span><strong>Name</strong></span>
            <span><strong>Net Pay</strong></span>
        </div>
        <hr>`;
    
    records.forEach(r => {
        report += `<div style="display: flex; justify-content: space-between;">
            <span>${escapeHTML(r.full_name)}</span>
            <span>₱${parseFloat(r.net_pay).toLocaleString()}</span>
        </div>`;
    });
    report += `</div>`;

    Swal.fire({
        title: 'Batch Report',
        html: report,
        confirmButtonColor: '#1e0178'
    });
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
            <td>${escapeHTML(req.type)}</td>
            <td>${escapeHTML(req.duration || '-')}</td>
            <td>${escapeHTML(req.reason)}</td>
            <td><span class="status-badge status-${req.status.toLowerCase()}">${escapeHTML(req.status)}</span></td>
            <td>
                ${req.status === 'Pending' ? `
                    <button class="btn btn-success btn-sm" onclick="updateLeaveStatus(${req.id}, 'Approved')"><i class="fas fa-check"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="updateLeaveStatus(${req.id}, 'Rejected')"><i class="fas fa-times"></i></button>
                ` : '<span class="text-muted">Processed</span>'}
            </td>
        </tr>
    `).join('') || '<tr><td colspan="6" class="text-center">No leave requests found.</td></tr>';
}

async function applyLeaveBalanceToAll() {
    const balanceInput = document.getElementById('newLeaveBalance');
    const balance = balanceInput?.value;
    if (balance === undefined || balance === null || balance === '') return showToast("Please enter a leave balance first.", 'error');
    
    const result = await Swal.fire({
        title: 'Confirm Action',
        text: "Apply this leave balance to ALL active employees?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, apply it!'
    });

    if (result.isConfirmed) {
        const response = await fetch('backend/api.php?action=bulk_update_leave_balance', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ balance })
        });
        const resultData = await response.json();
        showToast(resultData.message || (resultData.success ? "Leave balance applied to all employees." : "Failed to apply leave balance."), resultData.success ? 'success' : 'error');
        if (resultData.success) {
            if (balanceInput) balanceInput.value = '';
            fetchData();
        }
    }
}

async function updateLeaveBalance() {
    const employeeId = document.getElementById('leaveBalanceEmployeeSelect').value;
    const balanceInput = document.getElementById('newLeaveBalance');
    const balance = balanceInput.value;

    if (!employeeId || !balance) return showToast("Please select an employee and enter a balance.", 'error');

    const response = await fetch(`backend/api.php?action=update_leave_balance&employee_id=${employeeId}&balance=${balance}`);
    const result = await response.json();

    if (result.success) {
        showToast("Leave balance updated successfully.", 'success');
        if (balanceInput) balanceInput.value = '';
        fetchData();
    } else {
        showToast("Error: " + (result.message || "Failed to update balance."), 'error');
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
        showToast(`Leave request ${status.toLowerCase()} successfully.`, 'success');
        fetchData();
    } else {
        showToast(result.message || "Failed to update status.", 'error');
    }
}

function renderLoanTable() {
    const tbody = document.getElementById('loanTableBody');
    if (!tbody) return;
    tbody.innerHTML = loanRequests.map(req => {
        let actionButtons = '';
        const role = USER_ROLE.toLowerCase();

        if (req.status === 'Pending') {
            // HR and Admin can Approve/Reject
            if (role === 'admin' || role === 'hr') {
                actionButtons = `
                    <button class="btn btn-success btn-sm" onclick="updateLoanStatus(${req.id}, 'Approved')" title="Approve"><i class="fas fa-check"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="updateLoanStatus(${req.id}, 'Rejected')" title="Reject"><i class="fas fa-times"></i></button>
                `;
            } else {
                actionButtons = '<span class="text-muted">Awaiting HR Approval</span>';
            }
        } else if (req.status === 'Approved') {
            // Payroll can Distribute
            if (role === 'payroll' || role === 'payroll officer' || role === 'admin') {
                actionButtons = `
                    <button class="btn btn-primary btn-sm" onclick="updateLoanStatus(${req.id}, 'Distributed')" title="Mark as Distributed"><i class="fas fa-hand-holding-usd"></i> Distribute</button>
                `;
            } else {
                actionButtons = '<span class="text-info">Awaiting Distribution</span>';
            }
        } else if (req.status === 'Distributed') {
            // Payroll can mark as Paid
            if (role === 'payroll' || role === 'payroll officer' || role === 'admin') {
                actionButtons = `
                    <button class="btn btn-success btn-sm" onclick="updateLoanStatus(${req.id}, 'Paid')" title="Mark as Paid"><i class="fas fa-money-bill-wave"></i> Mark Paid</button>
                `;
            } else {
                actionButtons = '<span class="text-primary">Distributed</span>';
            }
        } else {
            actionButtons = `<span class="text-muted">${req.status}</span>`;
        }

        return `
            <tr>
                <td>${escapeHTML(req.full_name)}</td>
                <td>₱${parseFloat(req.amount).toLocaleString()}</td>
                <td>${escapeHTML(req.reason)}</td>
                <td><span class="status-badge status-${req.status.toLowerCase()}">${escapeHTML(req.status)}</span></td>
                <td>${actionButtons}</td>
            </tr>
        `;
    }).join('');
}

async function updateLoanStatus(id, status) {
    const response = await fetch('backend/api.php?action=update_loan_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
    });
    const result = await response.json();
    if (result.success) {
        showToast(`Loan status updated to ${status}.`, 'success');
        fetchData();
    } else {
        showToast(result.message || "Failed to update status.", 'error');
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
        showToast(`Resignation ${status.toLowerCase()} successfully.`, 'success');
        fetchData();
    } else {
        showToast(result.message || "Failed to update status.", 'error');
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
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, delete it!'
    });

    if (result.isConfirmed) {
        const response = await fetch(`backend/api.php?action=delete_deduction&id=${id}`);
        const resultData = await response.json();
        if (resultData.success) {
            showToast("Deduction deleted successfully.", 'success');
            fetchData();
        } else {
            showToast(resultData.message || "Failed to delete deduction.", 'error');
        }
    }
}

async function editDeduction(id) {
    const deduction = deductionsConfig.find(d => d.id == id);
    if (!deduction) return;

    const { value: formValues } = await Swal.fire({
        title: 'Edit Deduction',
        html:
            `<label>Name</label><input id="swal-input1" class="swal2-input" value="${deduction.name}">` +
            `<label>Type</label><select id="swal-input2" class="swal2-input">
                <option value="percentage" ${deduction.type === 'percentage' ? 'selected' : ''}>Percentage</option>
                <option value="fixed" ${deduction.type === 'fixed' ? 'selected' : ''}>Fixed</option>
            </select>` +
            `<label>Value</label><input id="swal-input3" class="swal2-input" type="number" step="0.01" value="${deduction.value}">` +
            `<div style="margin-top: 10px;">
                <input id="swal-input4" type="checkbox" ${deduction.is_active ? 'checked' : ''}> <label for="swal-input4">Active</label>
            </div>` +
            `<div style="margin-top: 5px;">
                <input id="swal-input5" type="checkbox" ${deduction.is_government ? 'checked' : ''}> <label for="swal-input5">Government Mandated</label>
            </div>`,
        focusConfirm: false,
        preConfirm: () => {
            return {
                name: document.getElementById('swal-input1').value,
                type: document.getElementById('swal-input2').value,
                value: parseFloat(document.getElementById('swal-input3').value),
                is_active: document.getElementById('swal-input4').checked ? 1 : 0,
                is_government: document.getElementById('swal-input5').checked ? 1 : 0
            }
        }
    });

    if (formValues) {
        saveDeduction({ ...deduction, ...formValues });
    }
}

// --- Allowances ---
async function addAllowanceCategory() {
    const name = document.getElementById('allowanceName').value;
    const type = document.getElementById('allowanceType').value;
    const rate = document.getElementById('allowanceRate').value;
    const description = document.getElementById('allowanceDesc').value;

    if (!name || !rate) return showToast("Please enter a name and rate.", 'error');

    const response = await fetch('backend/api.php?action=add_allowance_category', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, type, rate, description })
    });
    const result = await response.json();
    showToast(result.message, result.success ? 'success' : 'error');
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

    if (!employee_id || !category_id) return showToast("Please select an employee and an allowance category.", 'error');

    const response = await fetch('backend/api.php?action=assign_employee_allowance', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ employee_id, category_id, override_amount, effective_date })
    });
    const result = await response.json();
    showToast(result.message, result.success ? 'success' : 'error');
    if (result.success) {
        renderAllowances();
    }
}

async function applyAllowanceToAll() {
    const category_id = document.querySelector('input[name="allowanceTypeRadio"]:checked')?.value;
    const override_amount = document.getElementById('overrideAmount').value;
    const effective_date = document.getElementById('effectiveDate').value;

    if (!category_id) return showToast("Please select an allowance category first.", 'error');
    
    const confirmResult = await Swal.fire({
        title: 'Confirm Action',
        text: "Are you sure you want to apply this allowance to ALL active employees?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, apply it!'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch('backend/api.php?action=bulk_assign_allowance', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ category_id, override_amount, effective_date })
        });
        const result = await response.json();
        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) renderAllowances();
    }
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
    const confirmResult = await Swal.fire({
        title: 'Confirm Action',
        text: "Remove this allowance assignment?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, remove it!'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch(`backend/api.php?action=delete_employee_allowance&id=${id}`);
        const result = await response.json();
        if (result.success) {
            showToast("Allowance removed successfully.", 'success');
            renderAllowances();
        } else {
            showToast(result.message || "Failed to remove allowance.", 'error');
        }
    }
}

async function deleteAllowanceCategory(id) {
    const confirmResult = await Swal.fire({
        title: 'Confirm Action',
        text: "Delete this category? This will also remove assignments.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, delete it!'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch(`backend/api.php?action=delete_allowance_category&id=${id}`);
        const result = await response.json();
        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) renderAllowances();
    }
}

// --- Deductions ---
async function addDeductionCategory() {
    const name = document.getElementById('deductionName').value;
    const type = document.getElementById('deductionType').value;
    const value = document.getElementById('deductionRate').value;
    const description = document.getElementById('deductionDesc').value;

    if (!name || !value) return showToast("Please enter a name and rate.", 'error');

    const response = await fetch('backend/api.php?action=save_deduction', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, type, value, description, is_active: true, is_government: false })
    });
    const result = await response.json();
    showToast(result.message, result.success ? 'success' : 'error');
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

    if (!employee_id || !deduction_id) return showToast("Please select an employee and a deduction category.", 'error');

    const response = await fetch('backend/api.php?action=assign_employee_deduction', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ employee_id, deduction_id, override_amount, effective_date })
    });
    const result = await response.json();
    showToast(result.message, result.success ? 'success' : 'error');
    if (result.success) renderDeductions();
}

async function applyDeductionToAll() {
    const deduction_id = document.querySelector('input[name="deductionTypeRadio"]:checked')?.value;
    const override_amount = document.getElementById('deductionOverrideAmount').value;
    const effective_date = document.getElementById('deductionEffectiveDate').value;

    if (!deduction_id) return showToast("Please select a deduction category first.", 'error');
    
    const confirmResult = await Swal.fire({
        title: 'Confirm Action',
        text: "Are you sure you want to apply this deduction to ALL active employees?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, apply it!'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch('backend/api.php?action=bulk_assign_deduction', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ deduction_id, override_amount, effective_date })
        });
        const result = await response.json();
        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) renderDeductions();
    }
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
    const confirmResult = await Swal.fire({
        title: 'Confirm Action',
        text: "Delete this category?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, delete it!'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch(`backend/api.php?action=delete_deduction&id=${id}`);
        const result = await response.json();
        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) renderDeductions();
    }
}

async function deleteEmployeeDeduction(id) {
    const confirmResult = await Swal.fire({
        title: 'Confirm Action',
        text: "Remove this deduction assignment?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, remove it!'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch(`backend/api.php?action=delete_employee_deduction&id=${id}`);
        const result = await response.json();
        if (result.success) {
            showToast("Deduction removed successfully.", 'success');
            renderDeductions();
        } else {
            showToast(result.message || "Failed to remove deduction.", 'error');
        }
    }
}

// --- Settings ---
async function saveSettings() {
    const form = document.getElementById('settingsForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    const btn = document.getElementById('saveSettingsBtn');

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }

    try {
        const response = await fetch('backend/api.php?action=save_settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Settings saved successfully! Updating UI...',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            showToast("Error: " + result.message, 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Save System Settings';
            }
        }
    } catch (err) {
        console.error(err);
        showToast("An error occurred while saving settings.", 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save System Settings';
        }
    }
}

async function changePassword() {
    const oldPass = document.getElementById('oldPass').value;
    const newPass = document.getElementById('newPass').value;
    const confirmPass = document.getElementById('confirmPass').value;

    if (newPass !== confirmPass) {
        showToast("New passwords do not match!", 'error');
        return;
    }

    const response = await fetch('backend/api.php?action=change_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ oldPass, newPass })
    });

    const result = await response.json();
    if (result.success) {
        Swal.fire({
            icon: 'success',
            title: 'Password Updated',
            text: 'Your password has been changed successfully.',
            confirmButtonColor: '#1e0178'
        }).then(() => {
            closeModal('passwordModal');
        });
    } else {
        showToast(result.message, 'error');
    }
}

// --- Reports ---
async function generateReport(type) {
    let csvContent = "data:text/csv;charset=utf-8,";
    let filename = `Report_${type}_${new Date().toISOString().split('T')[0]}.csv`;

    // P0 Fix: Fetch data before generating the report if the store is empty
    if (type === 'attendance') {
        if (attendanceLogs.length === 0) {
            await fetchData('attendance');
        }
        if (attendanceLogs.length === 0) {
            return showToast("No attendance data available to export.", 'error');
        }
        csvContent += "Employee ID,Name,Date,Check-In,Check-Out,Status\n";
        attendanceLogs.forEach(log => csvContent += `${log.emp_code},${log.full_name},${log.log_date},${log.check_in},${log.check_out},${log.status}\n`);
    } else if (type === 'payroll') {
        if (payrollHistory.length === 0) {
            await fetchData('payroll');
        }
        if (payrollHistory.length === 0) {
            return showToast("No payroll data available to export.", 'error');
        }
        csvContent += "Employee,Period,Basic Pay,Deductions,Net Pay,Status\n";
        payrollHistory.forEach(p => csvContent += `${p.full_name},${p.period},${p.basic_pay},${p.deductions},${p.net_pay},${p.status}\n`);
    } else if (type === 'employees') {
        if (employees.length === 0) {
            await fetchData('employees');
        }
        if (employees.length === 0) {
            return showToast("No employee data available to export.", 'error');
        }
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

async function deleteSubjectLoad(id) {
    const confirmResult = await Swal.fire({
        title: 'Delete Load?',
        text: 'Are you sure you want to delete this subject load?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, delete it!'
    });

    if (confirmResult.isConfirmed) {
        try {
            const response = await fetch(`backend/api.php?action=delete_subject_load&id=${id}`);
            const result = await response.json();
            if (result.success) {
                showToast('Subject load deleted successfully', 'success');
                await fetchData('employees'); // Refresh since it can affect modals
            } else {
                showToast('Error: ' + result.message, 'error');
            }
        } catch (error) {
            showToast('Failed to connect to the server.', 'error');
        }
    }
}

async function saveSubjectLoad() {
    const form = document.getElementById('subjectLoadForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = {
        faculty_id: document.getElementById('loadFacultyId').value,
        code: document.getElementById('loadSubjectCode').value,
        description: document.getElementById('loadDescription').value,
        units: document.getElementById('loadUnits').value,
        hours: document.getElementById('loadHours').value
    };

    try {
        const response = await fetch('backend/api.php?action=save_subject_load', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            closeModal('addLoadModal');
            document.getElementById('subjectLoadForm').reset();
            showToast('Subject load saved successfully!', 'success');
            fetchData('employees');
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    } catch (err) {
        showToast('Failed to connect to the server.', 'error');
    }
}

function openAddSubjectModal() {
    document.getElementById('masterSubjectForm').reset();
    document.getElementById('subjectId').value = '';
    document.getElementById('subjectModalTitle').innerText = 'Create New Subject';
    openModal('subjectModal');
}

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

async function deleteMasterSubject(id) {
    const confirmResult = await Swal.fire({
        title: 'Delete Subject?',
        text: 'Are you sure you want to delete this subject?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, delete it!'
    });

    if (confirmResult.isConfirmed) {
        try {
            const response = await fetch(`backend/api.php?action=delete_subject&id=${id}`);
            const result = await response.json();
            if (result.success) {
                showToast('Subject deleted successfully', 'success');
                fetchData('subject_loads');
            } else {
                showToast('Error: ' + result.message, 'error');
            }
        } catch (error) {
            showToast('Failed to connect to the server.', 'error');
        }
    }
}

async function saveMasterSubject() {
    const form = document.getElementById('masterSubjectForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = {
        id: document.getElementById('subjectId').value,
        code: document.getElementById('subjectCode').value,
        description: document.getElementById('subjectDescription').value,
        units: document.getElementById('subjectUnits').value,
        hours: document.getElementById('subjectHours').value
    };

    try {
        const response = await fetch('backend/api.php?action=save_subject', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            closeModal('subjectModal');
            document.getElementById('masterSubjectForm').reset();
            document.getElementById('subjectId').value = '';
            document.getElementById('subjectModalTitle').innerText = 'Create New Subject';
            showToast('Subject saved successfully!', 'success');
            fetchData('subject_loads');
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    } catch (err) {
        showToast('Failed to connect to the server.', 'error');
    }
}

// --- Biometrics Enrollment ---
let registeredFaceMatcher = null;
const faceManager = new FaceManager({
    stabilityRequired: 8,
    sampleCount: 5,
    stabilityThreshold: 12
});

async function initFaceRegistration() {
    const select = document.getElementById('regEmployeeSelect');
    const employeeId = select.value;
    if (!employeeId) return showToast("Please select an employee before starting registration.", "error");

    const video = document.getElementById('video');
    const canvas = document.getElementById('overlay');
    const captureBtn = document.getElementById('captureBtn');
    const startBtn = document.getElementById('startRegBtn');
    const placeholder = document.getElementById('camera-placeholder');
    const placeholderText = placeholder.querySelector('p');

    // Reset UI state before starting
    if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    placeholder.style.display = 'flex';
    placeholderText.innerText = "Initializing AI Models...";

    try {
        await faceManager.loadModels();
        placeholderText.innerText = "Starting Camera...";

        await faceManager.startCamera(video);
        placeholder.style.display = 'none';

        startBtn.style.display = 'none';
        captureBtn.style.display = 'inline-block';
        captureBtn.disabled = true;

        faceManager.registrationActive = true;
        faceManager.isProcessing = false;

        const detectorOptions = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });

        const loop = async () => {
            if (!faceManager.stream || !faceManager.registrationActive) return;

            if (!faceManager.isProcessing) {
                const detection = await faceapi.detectSingleFace(video, detectorOptions).withFaceLandmarks();
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                if (detection) {
                    const isStable = faceManager.checkStability(detection.detection.box);
                    const status = isStable ? "STABLE! AUTO-CAPTURING..." : "HOLD STILL...";
                    const color = isStable ? "#27ae60" : "#f39c12";

                    faceManager.drawDetection(canvas, video, detection, status, color);

                    if (isStable) {
                        faceManager.isProcessing = true;
                        setTimeout(() => saveFaceRegistration(), 300);
                    } else {
                        captureBtn.disabled = false;
                    }
                } else {
                    faceManager.stabilityCounter = 0;
                    captureBtn.disabled = true;
                }
            }
            requestAnimationFrame(loop);
        };
        loop();

    } catch (err) {
        console.error("Registration Error:", err);
        showToast(err.message, "error");
        stopRegistrationCamera();
    }
}

async function saveFaceRegistration() {
    const employeeId = document.getElementById('regEmployeeSelect').value;
    const video = document.getElementById('video');
    const captureBtn = document.getElementById('captureBtn');
    const canvas = document.getElementById('overlay');
    const ctx = canvas.getContext('2d');

    if (!employeeId) return;
    faceManager.isProcessing = true;

    if (captureBtn) {
        captureBtn.disabled = true;
        captureBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    }

    try {
        const averagedDescriptor = await faceManager.captureSamples(video, (current, total) => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = "#27ae60";
            ctx.font = "bold 24px Inter, sans-serif";
            ctx.textAlign = "center";

            // Un-mirror the text since the canvas is mirrored via CSS
            ctx.save();
            ctx.scale(-1, 1);
            ctx.fillText(`CAPTURING SAMPLE ${current}/${total}...`, -canvas.width / 2, canvas.height / 2);
            ctx.restore();
        });

        // Clear canvas after capture loop finishes
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (captureBtn) captureBtn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Saving Data...';

        const response = await fetch('backend/api.php?action=save_face_registration', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: employeeId, descriptor: averagedDescriptor })
        });

        const result = await response.json();
        if (result.success) {
            showToast("Registration Complete! Face data saved securely.", "success");
            setTimeout(() => {
                stopRegistrationCamera();
                // Optionally reload or just reset the select
                document.getElementById('regEmployeeSelect').value = '';
            }, 1500);
        } else {
            throw new Error(result.message);
        }
    } catch (err) {
        showToast("Registration Failed: " + err.message, "error");
        faceManager.isProcessing = false;
        if (captureBtn) {
            captureBtn.disabled = false;
            captureBtn.innerHTML = '<i class="fas fa-user-plus"></i> Retry Registration';
        }
    }
}

// Consolidating stop camera logic above, removing duplicate here
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
    const confirmResult = await Swal.fire({
        title: 'Logout?',
        text: "Are you sure you want to logout?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e0178',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, logout'
    });

    if (confirmResult.isConfirmed) {
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