function showPage(id) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  const map = {dashboard:0,employees:1,deductions:2,allowances:3,payroll:4,fulltime:5,adminpay:6,utilitypay:7,parttimepay:8};
  const idx = map[id];
  if(idx !== undefined) document.querySelectorAll('.nav-item')[idx].classList.add('active');
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
