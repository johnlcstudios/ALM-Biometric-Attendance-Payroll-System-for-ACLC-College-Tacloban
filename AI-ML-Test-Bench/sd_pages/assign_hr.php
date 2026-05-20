<?php
require_once 'config.php';
require_once 'header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-user-tie me-2"></i>HR Management - User Assignment</h5>
        <button class="btn btn-primary btn-sm" onclick="openAssignHRModal()">
            <i class="fas fa-plus me-2"></i>Assign HR Role
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Assigned Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="hr-table-body">
                    <tr>
                        <td>Jane Doe</td>
                        <td>hr.jane.doe</td>
                        <td>jane@alm.com</td>
                        <td>2026-03-15</td>
                        <td><span class="badge bg-success">Active</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" onclick="removeHRRole(1)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="stat-box">
            <div class="stat-label">Total HR Users</div>
            <div class="stat-value" id="stat-total-hr">1</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-box">
            <div class="stat-label">Recently Assigned</div>
            <div class="stat-value" id="stat-recent-hr">0</div>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="assignHRModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); flex-direction: column; align-items: center; justify-content: center; z-index: 1000;">
    <div style="background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);">
        <h5 style="margin-bottom: 20px;"><i class="fas fa-user-plus me-2"></i>Assign HR Role</h5>
        
        <div class="form-group mb-3">
            <label><strong>Select Employee</strong></label>
            <select class="form-control" id="employeeSelect">
                <option value="">-- Choose Employee --</option>
                <option value="1">Maria Santos (EMP001)</option>
                <option value="2">Juan Dela Cruz (EMP002)</option>
                <option value="3">Rosa Garcia (EMP003)</option>
            </select>
        </div>

        <div id="generatedCredentials" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
            <p style="margin-bottom: 10px;"><strong>Generated Credentials:</strong></p>
            <p style="margin: 5px 0;"><strong>Username:</strong> <code id="genUsername"></code></p>
            <p style="margin: 5px 0;"><strong>Password:</strong> <code id="genPassword"></code></p>
            <p style="margin: 10px 0; color: #dc3545; font-size: 0.9rem;"><i class="fas fa-warning me-2"></i>Save these credentials securely!</p>
        </div>

        <div style="display: flex; gap: 10px;">
            <button class="btn btn-primary flex-grow-1" id="assignBtn" onclick="processAssignHR()">Generate & Assign</button>
            <button class="btn btn-secondary flex-grow-1" onclick="closeAssignHRModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
function openAssignHRModal() {
    const modal = document.getElementById('assignHRModal');
    modal.style.display = 'flex';
    document.getElementById('generatedCredentials').style.display = 'none';
    document.getElementById('assignBtn').innerText = 'Generate & Assign';
}

function closeAssignHRModal() {
    const modal = document.getElementById('assignHRModal');
    modal.style.display = 'none';
}

function processAssignHR() {
    const select = document.getElementById('employeeSelect');
    const empId = select.value;
    if (!empId) return alert('Please select an employee');

    const empName = select.options[select.selectedIndex].text.split(' (')[0];
    const username = empName.toLowerCase().replace(' ', '.') + Math.floor(Math.random() * 90 + 10);
    const password = Math.random().toString(36).slice(-8);

    document.getElementById('genUsername').innerText = username;
    document.getElementById('genPassword').innerText = password;
    document.getElementById('generatedCredentials').style.display = 'block';
    
    const assignBtn = document.getElementById('assignBtn');
    if (assignBtn.innerText === 'Done') {
        location.reload(); // Reload after assignment
    } else {
        assignBtn.innerText = 'Done';
    }
}

function removeHRRole(id) {
    if (confirm('Are you sure you want to remove HR role from this user?')) {
        location.reload();
    }
}
</script>

<?php require_once 'footer.php'; ?>
