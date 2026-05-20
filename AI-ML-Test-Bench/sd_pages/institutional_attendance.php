<?php
/**
 * Institutional Attendance Tracking with Company Isolation
 */
require_once 'config.php';
require_once 'header.php';

// Fetch attendance data
try {
    $date_filter = $_GET['date'] ?? date('Y-m-d');
    
    // Daily attendance summary
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT e.id) as total_employees,
            COUNT(DISTINCT CASE WHEN a.check_in IS NOT NULL THEN e.id END) as present,
            COUNT(DISTINCT CASE WHEN a.check_in IS NULL THEN e.id END) as absent
        FROM employees e
        LEFT JOIN attendance a ON e.id = a.employee_id AND a.log_date = ?
        WHERE e.status = 'Active' AND e.company_id = ?
    ");
    $stmt->execute([$date_filter, $company_id]);
    $attendance_summary = $stmt->fetch();
    
    // Detailed attendance
    $stmt = $pdo->prepare("
        SELECT 
            e.employee_id,
            e.full_name,
            e.department,
            e.position,
            a.check_in,
            a.check_out,
            a.status,
            a.log_date
        FROM employees e
        LEFT JOIN attendance a ON e.id = a.employee_id AND a.log_date = ?
        WHERE e.status = 'Active' AND e.company_id = ?
        ORDER BY e.department, e.full_name
    ");
    $stmt->execute([$date_filter, $company_id]);
    $attendance_details = $stmt->fetchAll();
    
    // Recent attendance logs
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            e.full_name,
            e.department
        FROM attendance a
        LEFT JOIN employees e ON a.employee_id = e.id
        WHERE a.company_id = ?
        ORDER BY a.log_date DESC, a.check_in DESC
        LIMIT 50
    ");
    $stmt->execute([$company_id]);
    $recent_logs = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Failed to load attendance data: " . $e->getMessage();
}
?>

<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-calendar-check me-2"></i>Attendance Tracking</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row mb-4">
            <div class="col-md-4">
                <label><strong>Select Date</strong></label>
                <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>
            <div class="col-md-2" style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>View
                </button>
            </div>
        </form>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-box" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <div class="stat-label">Total Employees</div>
                    <div class="stat-value" style="font-size: 2rem; font-weight: 700; color: #1e0178;">
                        <?php echo $attendance_summary['total_employees'] ?? 0; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <div class="stat-label">Present</div>
                    <div class="stat-value" style="font-size: 2rem; font-weight: 700; color: #27ae60;">
                        <?php echo $attendance_summary['present'] ?? 0; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <div class="stat-label">Absent</div>
                    <div class="stat-value" style="font-size: 2rem; font-weight: 700; color: #dc3545;">
                        <?php echo $attendance_summary['absent'] ?? 0; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendance_details)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No attendance records found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendance_details as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['employee_id']); ?></td>
                                <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['department'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($record['position'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php echo $record['check_in'] ? date('h:i A', strtotime($record['check_in'])) : '<span class="text-muted">Not checked in</span>'; ?>
                                </td>
                                <td>
                                    <?php echo $record['check_out'] ? date('h:i A', strtotime($record['check_out'])) : '<span class="text-muted">Not checked out</span>'; ?>
                                </td>
                                <td>
                                    <?php
                                        $status = $record['check_in'] ? ($record['status'] ?? 'Present') : 'Absent';
                                        $badge_class = match(strtolower($status)) {
                                            'present' => 'bg-success',
                                            'half-day', 'late' => 'bg-warning',
                                            'absent' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
