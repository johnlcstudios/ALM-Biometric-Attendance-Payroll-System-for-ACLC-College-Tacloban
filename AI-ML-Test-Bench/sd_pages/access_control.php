<?php
/**
 * Access Control - User Management with Company Isolation
 */
require_once 'config.php';
require_once 'header.php';

// Fetch users for current company
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.role,
            u.is_active,
            u.created_at,
            e.full_name,
            e.employee_id
        FROM users u
        LEFT JOIN employees e ON u.id = e.user_id
        WHERE u.company_id = ?
        ORDER BY u.role, u.username
    ");
    $stmt->execute([$company_id]);
    $users = $stmt->fetchAll();
    
    // Statistics
    $total_users = count($users);
    $active_users = count(array_filter($users, fn($u) => $u['is_active']));
    $inactive_users = $total_users - $active_users;
    
} catch (Exception $e) {
    $error = "Failed to load access control data: " . $e->getMessage();
}
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-key me-2"></i>Access Control Oversight</h5>
        <p class="text-muted mb-0">Monitor all users with administrative, HR, or payroll privileges within your company.</p>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-box" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value" style="font-size: 2rem; font-weight: 700; color: #1e0178;"><?php echo $total_users; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <div class="stat-label">Active Users</div>
                    <div class="stat-value" style="font-size: 2rem; font-weight: 700; color: #27ae60;"><?php echo $active_users; ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <div class="stat-label">Inactive Users</div>
                    <div class="stat-value" style="font-size: 2rem; font-weight: 700; color: #dc3545;"><?php echo $inactive_users; ?></div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No users found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php
                                        $badge_class = match($user['role']) {
                                            'Admin', 'School Director', 'SD' => 'bg-danger',
                                            'HR' => 'bg-primary',
                                            'Payroll Officer' => 'bg-success',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleUserStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 0 : 1; ?>)" aria-label="<?php echo $user['is_active'] ? 'Deactivate user ' . htmlspecialchars($user['username']) : 'Activate user ' . htmlspecialchars($user['username']); ?>" title="<?php echo $user['is_active'] ? 'Deactivate User' : 'Activate User'; ?>">
                                        <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>" aria-hidden="true"></i>
                                        <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleUserStatus(userId, newStatus) {
    const action = newStatus ? 'activate' : 'deactivate';
    if (!confirm(`Are you sure you want to ${action} this user?`)) {
        return;
    }

    fetch('../backend/api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'toggle_user_status',
            user_id: userId,
            is_active: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User status updated successfully');
            location.reload();
        } else {
            alert('Failed to update user status: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating user status');
    });
}
</script>

<?php require_once 'footer.php'; ?>
