<section id="employees" class="page">
    <div class="page-header">
        
        <button class="btn btn-primary" onclick="resetEmpModal(); openModal('employeeModal')">
            <i class="fas fa-plus"></i> Add Employee
        </button>
        <button class="btn btn-success" onclick="document.getElementById('importCsvInput').click()">
            <i class="fas fa-file-csv"></i> Import CSV
        </button>
        <input type="file" id="importCsvInput" accept=".csv" style="display:none;" onchange="importEmployees(this)">
    </div>
    <div class="table-container">
        <table id="employeeTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Work Position</th>
                    <th>Department</th>
                    <th>Faculty Level</th>
                    <th>Hire Date</th>
                    <th>Status</th>
                    <th>Work Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="employeeTableBody">
            </tbody>
        </table>
    </div>
</section>
