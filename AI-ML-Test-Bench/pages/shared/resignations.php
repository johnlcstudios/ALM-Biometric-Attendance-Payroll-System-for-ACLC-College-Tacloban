<div class="content-section" id="resignations">
    <h2><i class="fas fa-user-minus"></i> Resignation Management</h2>
    <div class="table-container">
        <table id="resignations-table" class="data-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Reason</th>
                    <th>Effective Date</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="6" class="text-center">Loading resignations...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
async function loadResignations() {
    try {
        const response = await fetch('backend/api.php?action=get_resignation_requests');
        const resignations = await response.json();
        
        const tbody = document.querySelector('#resignations-table tbody');
        if (resignations.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No resignation requests found</td></tr>';
            return;
        }
        
        tbody.innerHTML = resignations.map(r => `
            <tr>
                <td>${r.full_name}</td>
                <td>${r.reason}</td>
                <td>${new Date(r.effective_date).toLocaleDateString()}</td>
                <td><span class="badge badge-${r.status === 'Completed' ? 'success' : r.status === 'Processing' ? 'info' : 'warning'}">${r.status}</span></td>
                <td>${new Date(r.requested_at).toLocaleDateString()}</td>
                <td>
                    ${r.status === 'Pending' ? `
                        <button class="btn-sm btn-success" onclick="updateResignationStatus(${r.id}, 'Processing')"><i class="fas fa-check"></i></button>
                    ` : '-'}
                </td>
            </tr>
        `).join('');
    } catch (error) {
        console.error('Error loading resignations:', error);
    }
}

loadResignations();
</script>
