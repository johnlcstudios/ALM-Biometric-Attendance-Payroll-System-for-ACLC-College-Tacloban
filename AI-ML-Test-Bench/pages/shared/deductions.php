<div class="content-section" id="deductions">
    <h2><i class="fas fa-minus-circle"></i> Deduction Management</h2>
    <div class="section-header">
        <button class="btn-primary" onclick="showAddDeductionModal()">
            <i class="fas fa-plus"></i> Add Deduction
        </button>
    </div>
    <div class="table-container">
        <table id="deductions-table" class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="5" class="text-center">Loading deductions...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
async function loadDeductions() {
    try {
        const response = await fetch('backend/api.php?action=get_deductions');
        const deductions = await response.json();
        
        const tbody = document.querySelector('#deductions-table tbody');
        if (deductions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No deductions found</td></tr>';
            return;
        }
        
        tbody.innerHTML = deductions.map(d => `
            <tr>
                <td>${d.name}</td>
                <td>${d.type}</td>
                <td>${d.value}</td>
                <td><span class="badge badge-${d.is_active == 1 ? 'success' : 'danger'}">${d.is_active == 1 ? 'Active' : 'Inactive'}</span></td>
                <td>
                    <button class="btn-sm" onclick="editDeduction(${d.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-sm btn-danger" onclick="deleteDeduction(${d.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading deductions:', error);
    }
}

loadDeductions();
</script>
