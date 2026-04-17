<section id="payroll_calendar" class="page">
    <div class="payroll-header">
        <div class="header-left">
            <h2>📅 Payroll Calendar</h2>
            <p>Schedule, upcoming cut-offs, and validation status.</p>
        </div>
        <div class="header-right">
            <button class="btn btn-primary" onclick="addPayrollSchedule()">
                <i class="fas fa-plus"></i> Add Cut-off
            </button>
            <button class="btn btn-success" onclick="previewNextPayroll()">
                <i class="fas fa-play"></i> Preview Next Payroll
            </button>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="payroll-status-grid">
        <div class="status-card ready">
            <div class="status-icon">✅</div>
            <div>
                <h4 id="next-cutoff-status">Next Cut-off Ready</h4>
                <p id="next-cutoff-date">--/--/---- to --/--/----</p>
            </div>
        </div>
        <div class="status-card warning" id="validation-card">
            <div class="status-icon">⚠️</div>
            <div>
                <h4>Attendance Coverage</h4>
                <p id="attendance-coverage">85% Complete</p>
            </div>
        </div>
        <div class="status-card info">
            <div class="status-icon">📊</div>
            <div>
                <h4>Est. Disbursement</h4>
                <p id="est-disbursement">₱0.00</p>
            </div>
        </div>
    </div>

    <!-- Calendar Table -->
    <div class="table-container">
        <table id="payrollCalendarTable" class="payroll-table">
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Attendance Cut-off</th>
                    <th>Payroll Run Date</th>
                    <th>Status</th>
                    <th>Employees</th>
                    <th>Est. Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="payrollCalendarBody">
                <!-- Dynamic content -->
            </tbody>
        </table>
    </div>

    <!-- New Schedule Modal -->
    <div id="newScheduleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('newScheduleModal')">&times;</span>
            <h3>Add New Payroll Cut-off</h3>
            <form id="newScheduleForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" id="scheduleStart" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" id="scheduleEnd" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Payroll Run Date</label>
                    <input type="date" id="runDate" required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select id="scheduleType">
                        <option value="general">General Staff</option>
                        <option value="faculty">Faculty</option>
                        <option value="utility">Utility</option>
                        <option value="all">All Types</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Save Schedule</button>
            </form>
        </div>
        <link rel="stylesheet" href="../css/responsive-payroll.css">
    </div>
</section>

<style>
.payroll-status-grid { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) !important; gap: 20px !important; }
.modal-content { max-width: 90vw !important; }
@media (max-width: 480px) { .payroll-status-grid { grid-template-columns: 1fr !important; } }
</style>

<script>

// Payroll Calendar JS (Step 1.1 Implementation)
let payrollSchedules = [];

async function loadPayrollCalendar() {
    try {
        const response = await fetch('backend/api.php?action=get_payroll_schedule');
        payrollSchedules = await response.json();
        renderCalendar();
        updateStatusCards();
    } catch (e) {
        console.error('Calendar load failed:', e);
    }
}

function renderCalendar() {
    const tbody = document.getElementById('payrollCalendarBody');
    tbody.innerHTML = payrollSchedules.map((schedule, idx) => `
        <tr class="${getStatusClass(schedule.status)}">
            <td>${schedule.period}</td>
            <td>${schedule.cutoff_start} to ${schedule.cutoff_end}</td>
            <td>${schedule.run_date || '--'}</td>
            <td>
                <span class="status-badge ${getStatusClass(schedule.status)}">
                    ${getStatusText(schedule.status)}
                </span>
            </td>
            <td>${schedule.employee_count || 0}</td>
            <td>₱${formatCurrency(schedule.est_amount || 0)}</td>
            <td>
                ${schedule.status !== 'completed' ? 
                    `<button class="btn-sm btn-primary" onclick="runPayroll('${schedule.id}')">Run Now</button>` : 
                    '✓ Completed'
                }
                <button class="btn-sm btn-secondary" onclick="previewPayroll('${schedule.id}')">Preview</button>
            </td>
        </tr>
    `).join('') || '<tr><td colspan="7" class="text-center text-muted">No scheduled payrolls</td></tr>';
}

function getStatusClass(status) {
    const classes = {
        'ready': 'success',
        'pending': 'warning', 
        'overdue': 'danger',
        'completed': 'success'
    };
    return classes[status] || 'info';
}

function updateStatusCards() {
    const next = payrollSchedules.find(s => s.status === 'ready') || {};
    document.getElementById('next-cutoff-date').textContent = 
        `${next.cutoff_start || ''} to ${next.cutoff_end || ''}`;
    
    // Mock coverage - will connect to real attendance API
    document.getElementById('attendance-coverage').textContent = '92% Complete';
    document.getElementById('est-disbursement').textContent = 
        `₱${formatCurrency(next.est_amount || 0)}`;
}

function addPayrollSchedule() {
    document.getElementById('newScheduleModal').style.display = 'block';
}

document.getElementById('newScheduleForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = {
        start_date: document.getElementById('scheduleStart').value,
        end_date: document.getElementById('scheduleEnd').value,
        run_date: document.getElementById('runDate').value,
        type: document.getElementById('scheduleType').value
    };
    
    try {
        const response = await fetch('backend/api.php?action=save_payroll_schedule', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(formData)
        });
        const result = await response.json();
        
        if (result.success) {
            loadPayrollCalendar();
            closeModal('newScheduleModal');
            showToast('Payroll schedule added!', 'success');
        }
    } catch (e) {
        showToast('Failed to save schedule', 'error');
    }
});

function previewNextPayroll() {
    showToast('🧾 Generating payroll preview...', 'info');
    // Will call API for simulation
}

function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Init on load
document.addEventListener('DOMContentLoaded', loadPayrollCalendar);
</script>

<style>
.payroll-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.status-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-left: 4px solid;
    display: flex;
    gap: 1rem;
    align-items: center;
}

.status-card.ready { border-left-color: var(--success-color); }
.status-card.warning { border-left-color: var(--warning-color); }
.status-card.info { border-left-color: var(--primary-color); }

.status-icon {
    font-size: 2rem;
    min-width: 40px;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-badge.success { background: rgba(40,167,69,0.1); color: #28a745; }
.status-badge.warning { background: rgba(255,193,7,0.1); color: #ffc107; }
.status-badge.danger { background: rgba(220,53,69,0.1); color: #dc3545; }

#payrollCalendarTable th:nth-child(7),
#payrollCalendarTable td:nth-child(7) { 
    text-align: right; 
    white-space: nowrap;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}
</style>

