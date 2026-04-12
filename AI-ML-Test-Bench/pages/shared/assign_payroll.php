<div class="content-section" id="assign_payroll">
    <h2><i class="fas fa-tasks"></i> Assign Payroll</h2>
    <div class="section-header">
        <button class="btn-primary" onclick="showAssignPayrollModal()">
            <i class="fas fa-plus"></i> Assign Payroll
        </button>
    </div>
    <div class="table-container">
        <table id="assign-payroll-table" class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Period</th>
                    <th>Type</th>
                    <th>Net Pay</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="5" class="text-center">Loading payroll assignments...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
async function loadPayrollAssignments() {
    try {
        const response = await fetch('backend/api.php?action=get_payroll');
        const payroll = await response.json();
        
        const tbody = document.querySelector('#assign-payroll-table tbody');
        if (payroll.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No payroll records found</td></tr>';
            return;
        }
        
        tbody.innerHTML = payroll.map(p => `
            <tr>
                <td>${p.full_name}</td>
                <td>${p.period}</td>
                <td>${p.payroll_type}</td>
                <td>₱${parseFloat(p.net_pay).toFixed(2)}</td>
                <td><span class="badge badge-${p.status === 'Paid' ? 'success' : 'warning'}">${p.status}</span></td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading payroll:', error);
    }
}

loadPayrollAssignments();
</script>
