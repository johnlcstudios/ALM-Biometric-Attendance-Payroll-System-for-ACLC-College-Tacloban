<div class="content-section" id="faculty_payroll">
    <h2><i class="fas fa-chalkboard-teacher"></i> Faculty Payroll</h2>
    <div class="section-header">
        <select id="faculty-payroll-period" onchange="loadFacultyPayroll()">
            <option value="latest">Latest Period</option>
        </select>
        <button class="btn-primary" onclick="showRunPayrollModal('faculty')">
            <i class="fas fa-calculator"></i> Run Payroll
        </button>
    </div>
    <div class="table-container">
        <table id="faculty-payroll-table" class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Basic Pay</th>
                    <th>Deductions</th>
                    <th>Net Pay</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="5" class="text-center">Loading faculty payroll...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
async function loadFacultyPayroll() {
    try {
        const period = document.getElementById('faculty-payroll-period').value;
        const response = await fetch(`backend/api.php?action=get_faculty_payroll&period=${period}`);
        const data = await response.json();
        
        const tbody = document.querySelector('#faculty-payroll-table tbody');
        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No payroll data found</td></tr>';
            return;
        }
        
        tbody.innerHTML = data.data.map(p => `
            <tr>
                <td>${p.full_name}</td>
                <td>₱${parseFloat(p.basic_pay).toFixed(2)}</td>
                <td>₱${parseFloat(p.deductions).toFixed(2)}</td>
                <td><strong>₱${parseFloat(p.net_pay).toFixed(2)}</strong></td>
                <td><span class="badge badge-${p.status === 'Paid' ? 'success' : 'warning'}">${p.status}</span></td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading faculty payroll:', error);
    }
}

loadFacultyPayroll();
</script>
