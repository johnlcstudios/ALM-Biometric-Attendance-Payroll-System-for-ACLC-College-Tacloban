<section id="leave" class="page">
    <?php if (in_array($role, ['Admin', 'HR', 'Payroll Officer'])): ?>
        <div class="payroll-header">
            <div class="header-left">
            </div>
        </div>

        <div class="card" style="margin-bottom: 2rem;">
            <h3>Manage Leave Balances</h3>
            <div class="allowance-form-row">
                <select id="leaveBalanceEmployeeSelect" class="form-control-gray" aria-label="Select Employee">
                    <option value="">Select Employee...</option>
                </select>
                <input type="number" id="newLeaveBalance" placeholder="Total Leave Days" class="form-control-gray" aria-label="Total Leave Days">
                <button type="button" class="btn-dark-purple" onclick="updateLeaveBalance()">Update Balance</button>
                <button type="button" class="btn-secondary" onclick="applyLeaveBalanceToAll()"><i class="fas fa-users" aria-hidden="true"></i> Apply to All</button>
            </div>
        </div>
    <?php endif; ?>

    <div class="table-container">
        <table id="leaveTable">
            <thead>
                <tr>
                    <th scope="col">Employee</th>
                    <th scope="col">Type</th>
                    <th scope="col">Start Date</th>
                    <th scope="col">End Date</th>
                    <th scope="col">Reason</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody id="leaveTableBody">
            </tbody>
        </table>
    </div>
</section>