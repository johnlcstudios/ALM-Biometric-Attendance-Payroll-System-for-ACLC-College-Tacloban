function showPage(id) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  const map = {dashboard:0,employees:1,deductions:2,allowances:3,payroll:4,fulltime:5,adminpay:6,utilitypay:7,parttimepay:8, 'face-enrollment': 9, 'leave-management': 10};
  const idx = map[id];
  if(idx !== undefined) document.querySelectorAll('.nav-item')[idx].classList.add('active');

  // Handle Page Specific Initializations
  if (id === 'face-enrollment') {
    initFaceEnrollment();
  } else if (id === 'dashboard') {
    initCharts();
  } else {
    stopFaceEnrollment();
  }
}
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => { if(e.target === m) m.classList.remove('open'); });
});
function filterTable(input, tableId) {
  const q = input.value.toLowerCase();
  document.querySelectorAll('#'+tableId+' tbody tr').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
function switchTab(btn, sectionId) {
  const parent = btn.closest('.tabs');
  parent.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  const allIds = ['dedTypes','dedRecords','allowTypes','allowRecords','ptList','ptHours','ptSummary'];
  allIds.forEach(id => { const el = document.getElementById(id); if(el) el.style.display='none'; });
  document.getElementById(sectionId).style.display = '';
}
function switchPTTab(sectionId) {
  const tabs = document.querySelectorAll('#parttimepay .tab');
  const map = {ptList:0, ptHours:1, ptSummary:2};
  tabs.forEach(t => t.classList.remove('active'));
  if(tabs[map[sectionId]]) tabs[map[sectionId]].classList.add('active');
  ['ptList','ptHours','ptSummary'].forEach(id => { const el = document.getElementById(id); if(el) el.style.display='none'; });
  document.getElementById(sectionId).style.display = '';
}
function viewEmployee(name) { openModal('addEmployeeModal'); }
function setEmpType(type) { /* future: pre-select type in modal */ }

// ── Employee Modal Stepper ──────────────────────────────────────
let currentEmpStep = 1;
const TOTAL_EMP_STEPS = 4;

function goEmpStep(n) {
  if (n < 1 || n > TOTAL_EMP_STEPS) return;
  currentEmpStep = n;
  for (let i = 1; i <= TOTAL_EMP_STEPS; i++) {
    document.getElementById('empStep' + i).classList.toggle('active', i === n);
    const btn = document.getElementById('empStep' + i + 'Btn');
    btn.classList.remove('active','done');
    if (i === n) btn.classList.add('active');
    else if (i < n) btn.classList.add('done');
  }
  document.getElementById('empPrevBtn').style.display = n > 1 ? '' : 'none';
  const nextBtn = document.getElementById('empNextBtn');
  if (n === TOTAL_EMP_STEPS) {
    nextBtn.textContent = '✓ Save Employee';
    nextBtn.onclick = () => { alert('Employee saved successfully!'); closeModal('addEmployeeModal'); resetEmpModal(); };
  } else {
    nextBtn.textContent = 'Next →';
    nextBtn.onclick = () => goEmpStep(currentEmpStep + 1);
  }
  // sync preview on step 4
  if (n === 4) syncPreview();
  // toggle subload
  if (n === 3) toggleSubjectLoad();
}

function resetEmpModal() { goEmpStep(1); }

// ── Subject Load ────────────────────────────────────────────────
const REGULAR_LOAD_CAP = 21;

function toggleSubjectLoad() {
  const type = document.getElementById('empTypeSelect')?.value || '';
  const isFaculty = type.toLowerCase().includes('faculty');
  document.getElementById('noSubloadMsg').style.display = isFaculty ? 'none' : '';
  document.getElementById('subloadContent').style.display  = isFaculty ? '' : 'none';
  if (isFaculty) calcSubloadPay();
}

function addSubjectRow() {
  const container = document.getElementById('subjectRows');
  const row = document.createElement('div');
  row.className = 'subject-row';
  row.innerHTML = `
    <input type="text" placeholder="e.g. Eng 101 – Technical Writing" oninput="calcSubloadPay()">
    <input type="text" placeholder="e.g. BSIT-1A">
    <input type="number" placeholder="3" min="1" max="6" style="text-align:center;" oninput="calcSubloadPay()">
    <input type="text" placeholder="e.g. MWF 1–2PM">
    <button class="del-btn" onclick="delSubjectRow(this)">×</button>`;
  container.appendChild(row);
  calcSubloadPay();
}

function delSubjectRow(btn) {
  btn.closest('.subject-row').remove();
  calcSubloadPay();
}

function calcSubloadPay() {
  const rows = document.querySelectorAll('#subjectRows .subject-row');
  let total = 0;
  rows.forEach(row => {
    const unitInput = row.querySelectorAll('input')[2];
    total += parseInt(unitInput?.value) || 0;
  });
  const overload = Math.max(0, total - REGULAR_LOAD_CAP);
  const rate = parseFloat(document.getElementById('overloadRate')?.value) || 0;
  const pay = overload * rate;

  document.getElementById('totalUnits').textContent = total;
  document.getElementById('overloadUnits').textContent = overload;
  document.getElementById('overloadPay').value = '₱' + pay.toLocaleString('en-PH', {minimumFractionDigits:2});

  const statusEl = document.getElementById('overloadStatus');
  if (overload > 0) {
    statusEl.textContent = overload + ' overload unit' + (overload > 1 ? 's' : '');
    statusEl.className = 'overload-badge over';
  } else {
    statusEl.textContent = 'Within Regular Load';
    statusEl.className = 'overload-badge ok';
  }

  // sync preview fields
  document.getElementById('prevUnits') && (document.getElementById('prevUnits').textContent = total + ' units');
  document.getElementById('prevOverload') && (document.getElementById('prevOverload').textContent = overload + ' units');
  document.getElementById('prevOverloadPay') && (document.getElementById('prevOverloadPay').textContent = '₱' + pay.toLocaleString('en-PH',{minimumFractionDigits:2}));
}

// ── Face Enrollment ─────────────────────────────────────────────
let faceModelsLoaded = false;
let enrollStream = null;
let enrollFaceMatcher = null;
const ENROLL_MODEL_URL = 'kiosk/models/';

async function initFaceEnrollment() {
  const status = document.getElementById('enrollStatus');
  const video = document.getElementById('enrollVideo');
  const overlay = document.getElementById('enrollOverlay');
  const enrollBtn = document.getElementById('startEnrollBtn');
  const videoWrap = document.querySelector('.video-wrap');

  if (!faceModelsLoaded) {
    try {
      status.textContent = "Loading Face Models...";
      status.className = "enroll-status loading";
      await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(ENROLL_MODEL_URL),
        faceapi.nets.faceLandmark68Net.loadFromUri(ENROLL_MODEL_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(ENROLL_MODEL_URL),
        faceapi.nets.ssdMobilenetv1.loadFromUri(ENROLL_MODEL_URL)
      ]);
      faceModelsLoaded = true;
      status.textContent = "Models Ready. Initializing Camera...";
    } catch (err) {
      console.error("Error loading face models:", err);
      status.textContent = "Error loading face models.";
      status.className = "enroll-status error";
      return;
    }
  }

  // Load enrolled faces from localStorage
  const enrolledData = JSON.parse(localStorage.getItem('enrolledFaces') || '[]');
  if (enrolledData.length > 0) {
    const labeledDescriptors = enrolledData.map(data => {
      const descriptors = data.descriptors.map(d => new Float32Array(d));
      return new faceapi.LabeledFaceDescriptors(data.label, descriptors);
    });
    enrollFaceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.45);
  }

  // Start Camera
  try {
    enrollStream = await navigator.mediaDevices.getUserMedia({ video: {} });
    video.srcObject = enrollStream;
    status.textContent = "Camera Active. Select employee to enroll.";
    status.className = "enroll-status success";
    enrollBtn.disabled = false;
  } catch (err) {
    console.error("Error accessing camera:", err);
    status.textContent = "Camera access denied.";
    status.className = "enroll-status error";
  }

  // Draw landmarks loop
  video.onplay = () => {
    const displaySize = { width: video.videoWidth, height: video.videoHeight };
    faceapi.matchDimensions(overlay, displaySize);

    const intervalId = setInterval(async () => {
      if (!enrollStream) {
        clearInterval(intervalId);
        return;
      }
      const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptors();
      
      const resizedDetections = faceapi.resizeResults(detections, displaySize);
      overlay.getContext('2d').clearRect(0, 0, overlay.width, overlay.height);
      faceapi.draw.drawFaceLandmarks(overlay, resizedDetections);

      videoWrap.classList.remove('registered', 'success-border');

      if (detections.length > 0) {
        if (enrollFaceMatcher) {
          const results = detections.map(d => enrollFaceMatcher.findBestMatch(d.descriptor));
          const match = results.find(r => r.label !== 'unknown');
          
          if (match) {
            status.textContent = `Already Registered: ${match.label}`;
            status.className = "enroll-status error";
            videoWrap.classList.add('registered');
          } else {
            status.textContent = "Face detected. Ready to enroll.";
            status.className = "enroll-status success";
            videoWrap.classList.add('success-border');
          }
        } else {
          status.textContent = "Face detected. Ready to enroll.";
          status.className = "enroll-status success";
          videoWrap.classList.add('success-border');
        }
      }
    }, 200);
  };

  // Enrollment Action
  enrollBtn.onclick = async () => {
    const name = document.getElementById('enrollEmpName').value;
    if (!name) {
      alert("Please select an employee first.");
      return;
    }

    status.textContent = `Enrolling ${name}... Please stay still.`;
    status.className = "enroll-status loading";
    enrollBtn.disabled = true;

    try {
      const detection = await faceapi.detectSingleFace(video, new faceapi.SsdMobilenetv1Options())
        .withFaceLandmarks()
        .withFaceDescriptor();

      if (detection) {
        const descriptor = Array.from(detection.descriptor);
        saveFaceEnrollment(name, descriptor);
        status.textContent = `Face registered for ${name}!`;
        status.className = "enroll-status success";
      } else {
        status.textContent = "Face not detected. Please look directly at the camera.";
        status.className = "enroll-status error";
      }
    } catch (err) {
      console.error("Enrollment error:", err);
      status.textContent = "An error occurred during enrollment.";
      status.className = "enroll-status error";
    }
    enrollBtn.disabled = false;
  };
}

function stopFaceEnrollment() {
  if (enrollStream) {
    enrollStream.getTracks().forEach(track => track.stop());
    enrollStream = null;
  }
}

function saveFaceEnrollment(label, descriptor) {
  let enrolled = JSON.parse(localStorage.getItem('enrolledFaces') || '[]');
  const index = enrolled.findIndex(e => e.label === label);
  if (index !== -1) {
    enrolled[index].descriptors.push(descriptor);
  } else {
    enrolled.push({ label: label, descriptors: [descriptor] });
  }
  localStorage.setItem('enrolledFaces', JSON.stringify(enrolled));
}

// ── PDF Export ──────────────────────────────────────────────────
function exportPayslipPDF() {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();

  // Header
  doc.setFontSize(18);
  doc.setTextColor(30, 42, 110); // --navy
  doc.text("ACLC COLLEGE TACLOBAN", 105, 20, { align: "center" });
  doc.setFontSize(12);
  doc.setTextColor(100);
  doc.text("February 2026 Payroll - Employee Payslip", 105, 28, { align: "center" });

  // Employee Details Table
  doc.autoTable({
    startY: 40,
    head: [['Employee Information', '']],
    body: [
      ['Name', 'Maria Santos'],
      ['Position', 'Professor'],
      ['Period', 'Feb 1–28, 2026'],
    ],
    theme: 'grid',
    headStyles: { fillStyle: 'df', fillColor: [30, 42, 110], textColor: [255, 255, 255] },
  });

  // Earnings Table
  doc.autoTable({
    startY: doc.lastAutoTable.finalY + 10,
    head: [['Earnings', 'Amount']],
    body: [
      ['Basic Salary', 'P35,000.00'],
      ['Rice Allowance', 'P2,000.00'],
      ['Transportation', 'P1,500.00'],
      ['Medical', 'P833.00'],
      ['Special Assignment', 'P5,000.00'],
      ['GROSS PAY', 'P44,333.00'],
    ],
    theme: 'striped',
    headStyles: { fillColor: [39, 174, 96] }, // --success green
    foot: [['TOTAL EARNINGS', 'P44,333.00']],
    footStyles: { fillColor: [240, 242, 248], textColor: [30, 42, 110], fontStyle: 'bold' }
  });

  // Deductions Table
  doc.autoTable({
    startY: doc.lastAutoTable.finalY + 10,
    head: [['Deductions', 'Amount']],
    body: [
      ['SSS', '- P1,575.00'],
      ['PhilHealth', '- P700.00'],
      ['Pag-IBIG', '- P100.00'],
      ['Withholding Tax', '- P1,200.00'],
    ],
    theme: 'striped',
    headStyles: { fillColor: [192, 57, 43] }, // --red
  });

  // Final Net Pay
  const finalY = doc.lastAutoTable.finalY + 15;
  doc.setFontSize(14);
  doc.setTextColor(59, 79, 201); // --accent
  doc.text("NET PAY: P40,758.00", 200, finalY, { align: "right" });

  // Footer
  doc.setFontSize(10);
  doc.setTextColor(150);
  doc.text("Generated on: " + new Date().toLocaleString(), 14, 280);
  doc.text("This is a computer-generated document.", 105, 285, { align: "center" });

  doc.save("Payslip_Maria_Santos_Feb2026.pdf");
}

// ── Dashboard Analytics ──────────────────────────────────────────
let payrollChart = null;
let attendanceChart = null;

function initCharts() {
  const payrollCtx = document.getElementById('payrollChart')?.getContext('2d');
  const attendanceCtx = document.getElementById('attendanceChart')?.getContext('2d');

  if (payrollCtx) {
    if (payrollChart) payrollChart.destroy();
    payrollChart = new Chart(payrollCtx, {
      type: 'line',
      data: {
        labels: ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
        datasets: [{
          label: 'Total Payout (PHP)',
          data: [1182400, 1195800, 1350000, 1198200, 1204500, 1210000],
          borderColor: '#3b4fc9',
          backgroundColor: 'rgba(59, 79, 201, 0.1)',
          fill: true,
          tension: 0.4
        }]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });
  }

  if (attendanceCtx) {
    if (attendanceChart) attendanceChart.destroy();
    attendanceChart = new Chart(attendanceCtx, {
      type: 'doughnut',
      data: {
        labels: ['Present', 'Absent', 'Late', 'On Leave'],
        datasets: [{
          data: [42, 2, 3, 1],
          backgroundColor: ['#27ae60', '#c0392b', '#f39c12', '#3498db']
        }]
      },
      options: { responsive: true, maintainAspectRatio: false }
    });
  }
}

// Initialize charts on first load if on dashboard
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('dashboard').classList.contains('active')) {
    initCharts();
  }
});

function syncPreview() {
  const type = document.getElementById('empTypeSelect')?.value || '—';
  const sal  = parseFloat(document.getElementById('empBasicSalary')?.value) || 0;
  document.getElementById('prevEmpType').textContent = type;
  document.getElementById('prevBasicSal').textContent = '₱' + sal.toLocaleString('en-PH');
  calcSubloadPay();
}

// Part-time OT calculator
function calcPT() {
  const reg = parseFloat(document.getElementById('ptRegHrs').value) || 0;
  const ot  = parseFloat(document.getElementById('ptOTHrs').value)  || 0;
  const rate = parseFloat(document.getElementById('ptRate').value)   || 0;
  const ded  = parseFloat(document.getElementById('ptDeduct').value) || 0;
  const regPay = reg * rate;
  const otPay  = ot  * rate * 1.25;
  const net = regPay + otPay - ded;
  document.getElementById('ptRegPay').textContent = '₱' + regPay.toLocaleString('en-PH', {minimumFractionDigits:2});
  document.getElementById('ptOTPay').textContent  = '₱' + otPay.toLocaleString('en-PH', {minimumFractionDigits:2});
  document.getElementById('ptNetPay').textContent = '₱' + Math.max(0,net).toLocaleString('en-PH', {minimumFractionDigits:2});
}
