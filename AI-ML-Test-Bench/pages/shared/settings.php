<?php
$stmt_company = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmt_company->execute([$_SESSION['company_id']]);
$company = $stmt_company->fetch();
?>
<section id="settings" class="page">
    <div class="settings-container">
        <div class="settings-card">
            <h3>General System Settings</h3>
            <form id="settingsForm">
                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text" name="companyName" value="<?php echo $company['name']; ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Time In (Shift Start)</label>
                        <input type="time" name="workStart" value="<?php echo $company['work_start']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Time Out (Shift End)</label>
                        <input type="time" name="workEnd" value="<?php echo $company['work_end']; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Lunch Out Range</label>
                        <div style="display:flex; gap:5px;">
                            <input type="time" name="lunchOutStart" value="<?php echo $company['lunch_out_start'] ?? '11:30'; ?>">
                            <span>to</span>
                            <input type="time" name="lunchOutEnd" value="<?php echo $company['lunch_out_end'] ?? '12:30'; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Lunch In Range</label>
                        <div style="display:flex; gap:5px;">
                            <input type="time" name="lunchInStart" value="<?php echo $company['lunch_in_start'] ?? '12:30'; ?>">
                            <span>to</span>
                            <input type="time" name="lunchInEnd" value="<?php echo $company['lunch_in_end'] ?? '13:30'; ?>">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Late Grace Period (Minutes)</label>
                        <input type="number" name="gracePeriod" value="<?php echo $company['grace_period'] ?? '15'; ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label>Overtime Percentage (%)</label>
                        <input type="number" name="otPercentage" value="<?php echo $company['ot_percentage'] ?? '25'; ?>" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Deduction Per Second (₱)</label>
                        <input type="number" step="0.0001" name="deductionPerSec" value="<?php echo $company['deduction_per_sec'] ?? '0.0083'; ?>">
                    </div>
                    <div class="form-group">
                        <label>Deduction Per Minute (₱)</label>
                        <input type="number" step="0.01" name="deductionPerMin" value="<?php echo $company['deduction_per_min'] ?? '0.50'; ?>">
                    </div>
                    <div class="form-group">
                        <label>Deduction Per Hour (₱)</label>
                        <input type="number" step="0.01" name="deductionPerHour" value="<?php echo $company['deduction_per_hour'] ?? '30.00'; ?>">
                    </div>
                </div>
                <button type="button" class="btn btn-primary" onclick="saveSettings()">Save Settings</button>
            </form>
        </div>
        
        <?php if (in_array($role, ['Admin', 'HR', 'Payroll Officer'])): ?>
        <div class="settings-card">
            <h3>Admin Tools</h3>
            <div class="setting-item">
                <p>Assign Payroll Officer</p>
                <button class="btn btn-primary btn-sm" onclick="showPage('assign_payroll')">Manage Access</button>
            </div>
            <div class="setting-item">
                <p>Manage Subject Loads</p>
                <button class="btn btn-secondary btn-sm" onclick="showPage('subject_loads')">Configure Loads</button>
            </div>
        </div>
        <?php endif; ?>

        <div class="settings-card">
            <h3>Backup & Security</h3>
            <div class="setting-item">
                <p>Attendance Station</p>
                <a href="kiosk.php?company_id=<?php echo $_SESSION['company_id']; ?>" target="_blank" class="btn btn-primary btn-sm">
                    Launch Kiosk <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
            <div class="setting-item">
                <p>Database Backup</p>
                <button class="btn btn-secondary btn-sm">Download Backup</button>
            </div>
            <div class="setting-item">
                <p>Admin Password</p>
                <button class="btn btn-secondary btn-sm" onclick="openModal('passwordModal')">Change Password</button>
            </div>
        </div>
    </div>
</section>
