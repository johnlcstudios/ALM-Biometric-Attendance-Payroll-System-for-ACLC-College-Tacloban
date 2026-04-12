<div class="content-section" id="employees">
    <h2><i class="fas fa-users"></i> Employee Management</h2>
    <div class="section-header">
        <button class="btn-primary" onclick="showAddEmployeeModal()">
            <i class="fas fa-plus"></i> Add Employee
        </button>
    </div>
    <div class="table-container">
        <table id="employees-table" class="data-table">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Full Name</th>
                    <th>Position</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="6" class="text-center">Loading employees...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
async function loadEmployees() {
    try {
        const response = await fetch('backend/api.php?action=get_employees');
        const employees = await response.json();
        
        const tbody = document.querySelector('#employees-table tbody');
        if (employees.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No employees found</td></tr>';
            return;
        }
        
        tbody.innerHTML = employees.map(emp => `
            <tr>
                <td>${emp.employee_id}</td>
                <td>${emp.full_name}</td>
                <td>${emp.position}</td>
                <td>${emp.department}</td>
                <td><span class="badge badge-${emp.status === 'Active' ? 'success' : 'warning'}">${emp.status}</span></td>
                <td>
                    <button class="btn-sm" onclick="editEmployee(${emp.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn-sm btn-danger" onclick="deleteEmployee(${emp.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading employees:', error);
    }
}

loadEmployees();
</script>
