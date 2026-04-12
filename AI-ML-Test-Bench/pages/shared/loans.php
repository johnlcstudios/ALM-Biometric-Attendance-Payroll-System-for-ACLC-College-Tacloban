<div class="content-section" id="loans">
    <h2><i class="fas fa-hand-holding-usd"></i> Loan Management</h2>
    <div class="table-container">
        <table id="loans-table" class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Amount</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="6" class="text-center">Loading loans...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
async function loadLoans() {
    try {
        const response = await fetch('backend/api.php?action=get_loan_requests');
        const loans = await response.json();
        
        const tbody = document.querySelector('#loans-table tbody');
        if (loans.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No loan requests found</td></tr>';
            return;
        }
        
        tbody.innerHTML = loans.map(loan => `
            <tr>
                <td>${loan.full_name}</td>
                <td>₱${parseFloat(loan.amount).toFixed(2)}</td>
                <td>${loan.reason}</td>
                <td><span class="badge badge-${loan.status === 'Approved' ? 'success' : loan.status === 'Rejected' ? 'danger' : 'warning'}">${loan.status}</span></td>
                <td>${new Date(loan.requested_at).toLocaleDateString()}</td>
                <td>
                    ${loan.status === 'Pending' ? `
                        <button class="btn-sm btn-success" onclick="updateLoanStatus(${loan.id}, 'Approved')"><i class="fas fa-check"></i></button>
                        <button class="btn-sm btn-danger" onclick="updateLoanStatus(${loan.id}, 'Rejected')"><i class="fas fa-times"></i></button>
                    ` : '-'}
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading loans:', error);
    }
}

loadLoans();
</script>
