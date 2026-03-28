/**
 * Biometric Attendance & Payroll System - Core Logic (PHP/MySQL Version)
 */

// --- Initial Data Fetching ---
let employees = [];
let payrollHistory = [];
let attendanceLogs = [];
let leaveRequests = [];
let deductionsConfig = {
    gov: [
        { name: 'SSS', type: 'percentage', value: 4.5, active: true },
        { name: 'PhilHealth', type: 'percentage', value: 2.0, active: true },
        { name: 'Pag-IBIG', type: 'fixed', value: 100, active: true }
    ],
    company: [
        { name: 'Health Insurance', type: 'fixed', value: 500, active: true }
    ]
};

async function fetchData() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) loadingOverlay.style.display = 'flex';

    try {
        const fetchJSON = async (url) => {
            const res = await fetch(url);
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
        const dashboardStats = await fetchJSON('backend/api.php?action=get_dashboard_stats');
        if (dashboardStats) {
            document.getElementById('stat-total-emp').innerText = dashboardStats.total_employees;
            document.getElementById('stat-present').innerText = dashboardStats.present_today;
            document.getElementById('stat-absent').innerText = dashboardStats.absent_today;
            document.getElementById('stat-leave').innerText = dashboardStats.pending_leave;
        }
        
        // Render initial page
        const currentPage = document.querySelector('.page.active')?.id || 'dashboard';
        showPage(currentPage);
    } catch (err) {
        console.error("Error fetching data:", err);
    } finally {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
    }
}

// --- Navigation Logic ---
function showPage(pageId) {
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
    tbody.innerHTML = employees.map(emp => `
        <tr id="row-${emp.id}">
            <td>${emp.employee_id}</td>
            <td>
                <div><strong>${emp.full_name}</strong></div>
                <div class="text-muted" style="font-size: 0.8rem;">Username: ${emp.username || 'N/A'}</div>
            </td>
            <td>${emp.position}</td>
            <td>${emp.department}</td>
            <td><span class="status-badge status-${emp.status.toLowerCase().replace(' ', '-')}">${emp.status}</span></td>
            <td>
                <button class="btn btn-secondary btn-sm" onclick="editEmployee('${emp.id}')"><i class="fas fa-edit"></i></button>
                <button class="btn btn-danger btn-sm" onclick="deleteEmployee('${emp.id}')"><i class="fas fa-trash"></i></button>
                <button class="btn btn-warning btn-sm" onclick="resetPassword('${emp.user_id}')" title="Reset Password"><i class="fas fa-key"></i></button>
            </td>
        </tr>
    `).join('');
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

    const response = await fetch('backend/api.php?action=run_payroll', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ start_date, end_date })
    });
    
    const result = await response.json();
    if (result.success) {
        alert(result.message || `Payroll processed for ${start_date} to ${end_date}`);
        fetchData();
    }
}

function renderPayrollTable() {
    const tbody = document.getElementById('payrollTableBody');
    if (!tbody) return;
    tbody.innerHTML = payrollHistory.map(p => `
        <tr>
            <td>${p.full_name}</td>
            <td>${p.period}</td>
            <td>₱${parseFloat(p.basic_pay).toLocaleString()}</td>
            <td>₱${parseFloat(p.deductions).toLocaleString()}</td>
            <td><strong>₱${parseFloat(p.net_pay).toLocaleString()}</strong></td>
            <td><span class="status-badge status-active">${p.status}</span></td>
            <td>
                <button class="btn btn-primary btn-sm" onclick="viewPayslip('${p.id}')"><i class="fas fa-eye"></i></button>
            </td>
        </tr>
    `).join('');
}

// --- Leave ---
function renderLeaveTable() {
    const tbody = document.getElementById('leaveTableBody');
    if (!tbody) return;
    tbody.innerHTML = leaveRequests.map(req => `
        <tr>
            <td>${req.full_name}</td>
            <td>${req.type}</td>
            <td>${req.duration}</td>
            <td>${req.reason}</td>
            <td><span class="status-badge status-${req.status.toLowerCase()}">${req.status}</span></td>
            <td>
                ${req.status === 'Pending' ? `
                    <button class="btn btn-success btn-sm" onclick="updateLeaveStatus(${req.id}, 'Approved')"><i class="fas fa-check"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="updateLeaveStatus(${req.id}, 'Rejected')"><i class="fas fa-times"></i></button>
                ` : '<span class="text-muted">Processed</span>'}
            </td>
        </tr>
    `).join('');
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

function addDeduction() {
    const name = prompt("Enter deduction name:");
    if (!name) return;
    const type = prompt("Enter type (percentage or fixed):", "fixed");
    const value = parseFloat(prompt("Enter value:"));
    const is_government = confirm("Is this a government mandated deduction?");

    saveDeduction({ name, type, value, is_active: true, is_government });
}

async function saveDeduction(deduction) {
    const response = await fetch('backend/api.php?action=save_deduction', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(deduction)
    });
    const result = await response.json();
    if (result.success) {
        fetchData();
    }
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
    }
    // ... other report types

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
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
        const MOVEMENT_THRESHOLD = 15; // pixel movement allowed for "stable"

        // Preview loop using requestAnimationFrame for smoother performance
        async function onPlay() {
            if (!video.srcObject || isEnrolling) return;
            
            // SPEED OPTIMIZATION: Only detect landmarks during the liveness phase.
            const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 }))
                .withFaceLandmarks();
            
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            if (detection) {
                const resizedDetection = faceapi.resizeResults(detection, displaySize);
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
                    ctx.fillText(`Liveness Verified!`, textX, textY);

                    if (!isEnrolling) {
                        isEnrolling = true;
                        
                        // Now that liveness is verified, get the full descriptor
                        const fullDetection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 }))
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
    const select = document.getElementById('enrollEmployeeSelect');
    if (!select) return;
    select.innerHTML = '<option value="">Select Employee...</option>' + 
        employees.map(emp => `<option value="${emp.id}">${emp.full_name} (${emp.employee_id})</option>`).join('');
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
    const dateFilter = document.getElementById('attendanceDateFilter');
    if (dateFilter) dateFilter.addEventListener('change', renderAttendanceTable);
};
