<?php
/**
 * Settings - Company Configuration with Company Isolation
 */
require_once 'config.php';
require_once 'header.php';

// Fetch company settings
try {
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
        $company_name = $_POST['company_name'] ?? '';
        $timezone = $_POST['timezone'] ?? 'Asia/Manila';
        $work_start = $_POST['work_start'] ?? '08:00';
        $work_end = $_POST['work_end'] ?? '17:00';
        
        $stmt = $pdo->prepare("
            UPDATE companies 
            SET name = ?, timezone = ?, work_start = ?, work_end = ?
            WHERE id = ?
        ");
        $stmt->execute([$company_name, $timezone, $work_start, $work_end, $company_id]);
        
        // Update session
        $_SESSION['company_name'] = $company_name;
        
        $success = "Settings updated successfully!";
        
        // Refresh company data
        $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
        $stmt->execute([$company_id]);
        $company = $stmt->fetch();
    }
    
} catch (Exception $e) {
    $error = "Failed to load settings: " . $e->getMessage();
}
?>

<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-cog me-2"></i>Institutional Configuration</h5>
    </div>
    <div class="card-body">
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label><strong>Company Name</strong></label>
                        <input type="text" class="form-control" name="company_name" 
                               value="<?php echo htmlspecialchars($company['name'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label><strong>System Timezone</strong></label>
                        <select class="form-control" name="timezone">
                            <option value="Asia/Manila" <?php echo ($company['timezone'] ?? '') === 'Asia/Manila' ? 'selected' : ''; ?>>Philippines (GMT+8)</option>
                            <option value="UTC" <?php echo ($company['timezone'] ?? '') === 'UTC' ? 'selected' : ''; ?>>UTC / GMT</option>
                            <option value="Asia/Singapore" <?php echo ($company['timezone'] ?? '') === 'Asia/Singapore' ? 'selected' : ''; ?>>Singapore (GMT+8)</option>
                            <option value="Asia/Tokyo" <?php echo ($company['timezone'] ?? '') === 'Asia/Tokyo' ? 'selected' : ''; ?>>Tokyo (GMT+9)</option>
                            <option value="America/New_York" <?php echo ($company['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>New York (GMT-5)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label><strong>Work Start Time</strong></label>
                        <input type="time" class="form-control" name="work_start" 
                               value="<?php echo htmlspecialchars($company['work_start'] ?? '08:00'); ?>" required>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label><strong>Work End Time</strong></label>
                        <input type="time" class="form-control" name="work_end" 
                               value="<?php echo htmlspecialchars($company['work_end'] ?? '17:00'); ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label><strong>Company Code</strong></label>
                        <input type="text" class="form-control" 
                               value="<?php echo htmlspecialchars($company['company_code'] ?? 'N/A'); ?>" readonly>
                        <small class="text-muted">Company code is auto-generated and cannot be changed</small>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="form-group">
                        <label><strong>Admin Email</strong></label>
                        <input type="email" class="form-control" 
                               value="<?php echo htmlspecialchars($company['admin_email'] ?? ''); ?>" readonly>
                        <small class="text-muted">Admin email is set during registration</small>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" name="update_settings" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5><i class="fas fa-info-circle me-2"></i>Company Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 200px;">Company ID</th>
                        <td><?php echo $company['id'] ?? 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <th>Company Name</th>
                        <td><?php echo htmlspecialchars($company['name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Company Code</th>
                        <td><code><?php echo htmlspecialchars($company['company_code'] ?? 'N/A'); ?></code></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 200px;">Created Date</th>
                        <td><?php echo date('M d, Y H:i', strtotime($company['created_at'] ?? 'now')); ?></td>
                    </tr>
                    <tr>
                        <th>Timezone</th>
                        <td><?php echo htmlspecialchars($company['timezone'] ?? 'Asia/Manila'); ?></td>
                    </tr>
                    <tr>
                        <th>Work Hours</th>
                        <td><?php echo htmlspecialchars($company['work_start'] ?? '08:00'); ?> - <?php echo htmlspecialchars($company['work_end'] ?? '17:00'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
