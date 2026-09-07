<?php
/**
 * Audit Logs - System Activity Tracking with Company Isolation
 */
require_once 'config.php';
require_once 'header.php';

// Fetch audit logs for current company
try {
    // Get filter parameters
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to = $_GET['date_to'] ?? date('Y-m-d');
    $user_filter = $_GET['user'] ?? '';
    
    // Build query with filters
    $query = "
        SELECT 
            al.id,
            al.action,
            al.entity_type,
            al.entity_id,
            al.details,
            al.ip_address,
            al.created_at,
            u.username,
            e.full_name
        FROM audit_log al
        LEFT JOIN users u ON al.user_id = u.id
        LEFT JOIN employees e ON u.id = e.user_id
        WHERE al.company_id = ?
        AND DATE(al.created_at) BETWEEN ? AND ?
    ";
    
    $params = [$company_id, $date_from, $date_to];
    
    if (!empty($user_filter)) {
        $query .= " AND (u.username LIKE ? OR e.full_name LIKE ?)";
        $params[] = "%$user_filter%";
        $params[] = "%$user_filter%";
    }
    
    $query .= " ORDER BY al.created_at DESC LIMIT 100";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $audit_logs = $stmt->fetchAll();
    
    // Statistics
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM audit_log 
        WHERE company_id = ? AND DATE(created_at) = CURDATE()
    ");
    $stmt->execute([$company_id]);
    $total_today = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM audit_log 
        WHERE company_id = ? 
        AND DATE(created_at) BETWEEN ? AND ?
        AND action = 'login'
        AND details LIKE '%failed%'
    ");
    $stmt->execute([$company_id, $date_from, $date_to]);
    $failed_attempts = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT user_id) as total 
        FROM audit_log 
        WHERE company_id = ? 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$company_id]);
    $active_users = $stmt->fetch()['total'] ?? 0;
    
} catch (Exception $e) {
    $error = "Failed to load audit logs: " . $e->getMessage();
}
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-history me-2" aria-hidden="true"></i>System Audit Logs</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row mb-4">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date_from"><strong>Date From</strong></label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="date_to"><strong>Date To</strong></label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="user"><strong>User</strong></label>
                    <input type="text" class="form-control" id="user" placeholder="Filter by username" name="user" value="<?php echo htmlspecialchars($user_filter); ?>">
                </div>
            </div>
            <div class="col-md-3" style="display: flex; align-items: flex-end;">
                <button class="btn btn-primary w-100" type="submit" aria-label="Filter audit logs">
                    <i class="fas fa-filter me-2" aria-hidden="true"></i>Filter
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th scope="col">Timestamp</th>
                        <th scope="col">User</th>
                        <th scope="col">Action</th>
                        <th scope="col">Module</th>
                        <th scope="col">Description</th>
                        <th scope="col">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($audit_logs)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No audit logs found for the selected criteria</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($audit_logs as $log): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['full_name'] ?? $log['username'] ?? 'System'); ?></td>
                                <td>
                                    <?php
                                        $badge_class = match(strtolower($log['action'])) {
                                            'login', 'view' => 'bg-info',
                                            'create', 'insert' => 'bg-success',
                                            'update', 'edit' => 'bg-warning',
                                            'delete', 'deactivate' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo strtoupper(htmlspecialchars($log['action'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['entity_type'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($log['details'] ?? 'N/A'); ?></td>
                                <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5><i class="fas fa-chart-bar me-2" aria-hidden="true"></i>Audit Summary</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="stat-box" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <div class="stat-label">Total Events (Today)</div>
                    <div class="stat-value" style="font-size: 2rem; font-weight: 700; color: #1e0178;"><?php echo $total_today; ?></div>
                    <small class="text-muted">System activities logged</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <div class="stat-label">Failed Login Attempts</div>
                    <div class="stat-value" style="font-size: 2rem; font-weight: 700; color: #dc3545;"><?php echo $failed_attempts; ?></div>
                    <small class="text-muted">Security incidents</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <div class="stat-label">Active Users (Last Hour)</div>
                    <div class="stat-value" style="font-size: 2rem; font-weight: 700; color: #27ae60;"><?php echo $active_users; ?></div>
                    <small class="text-muted">Currently active</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
