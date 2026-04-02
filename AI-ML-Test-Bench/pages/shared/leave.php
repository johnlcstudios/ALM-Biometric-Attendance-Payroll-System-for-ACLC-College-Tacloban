<section id="leave" class="page">
    <?php if (in_array($role, ['Admin', 'HR', 'Payroll Officer'])): ?>
    <div class="payroll-header">
        <div class="header-left">
            <h2>Leave Request Management</h2>
            <p>Review and manage employee leave applications.</p>
        </div>
    </div>
    
    <div class="card" style="margin-bottom: 2rem;">
        <h3>Manage Leave Balances</h3>
        <div class="allowance-form-row">
            <select id="leaveBalanceEmployeeSelect" class="form-control-gray">
                <option value="">Select Employee...</option>
            </select>
            <input type="number" id="newLeaveBalance" placeholder="Total Leave Days" class="form-control-gray">
            <button class="btn-dark-purple" onclick="updateLeaveBalance()">Update Balance</button>
            <button class="btn-secondary" onclick="applyLeaveBalanceToAll()"><i class="fas fa-users"></i> Apply to All</button>
        </div>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <table id="leaveTable">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="leaveTableBody">
            </tbody>
        </table>
    </div>
</section>
