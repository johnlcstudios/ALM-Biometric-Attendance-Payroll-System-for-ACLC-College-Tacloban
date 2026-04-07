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
                        <small class="text-muted">Target time for late calculation</small>
                    </div>
                    <div class="form-group">
                        <label>Time Out (Shift End)</label>
                        <input type="time" name="workEnd" value="<?php echo $company['work_end']; ?>">
                        <small class="text-muted">Target time for early out calculation</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Check In Window (Allowed Between)</label>
                        <div style="display:flex; gap:5px; align-items:center;">
                            <input type="time" name="checkInStart" value="<?php echo $company['check_in_start'] ?? '06:00'; ?>">
                            <span>to</span>
                            <input type="time" name="checkInEnd" value="<?php echo $company['check_in_end'] ?? '10:00'; ?>">
                        </div>
                        <small class="text-muted">Users can only Check In during this time</small>
                    </div>
                    <div class="form-group">
                        <label>Check Out Window (Allowed Between)</label>
                        <div style="display:flex; gap:5px; align-items:center;">
                            <input type="time" name="checkOutStart" value="<?php echo $company['check_out_start'] ?? '16:00'; ?>">
                            <span>to</span>
                            <input type="time" name="checkOutEnd" value="<?php echo $company['check_out_end'] ?? '22:00'; ?>">
                        </div>
                        <small class="text-muted">Users can only Check Out during this time</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Lunch Out Window (Allowed Between)</label>
                        <div style="display:flex; gap:5px; align-items:center;">
                            <input type="time" name="lunchOutStart" value="<?php echo $company['lunch_out_start'] ?? '11:30'; ?>">
                            <span>to</span>
                            <input type="time" name="lunchOutEnd" value="<?php echo $company['lunch_out_end'] ?? '12:30'; ?>">
                        </div>
                        <small class="text-muted">Range for Lunch Out action</small>
                    </div>
                    <div class="form-group">
                        <label>Lunch In Window (Allowed Between)</label>
                        <div style="display:flex; gap:5px; align-items:center;">
                            <input type="time" name="lunchInStart" value="<?php echo $company['lunch_in_start'] ?? '12:30'; ?>">
                            <span>to</span>
                            <input type="time" name="lunchInEnd" value="<?php echo $company['lunch_in_end'] ?? '13:30'; ?>">
                        </div>
                        <small class="text-muted">Range for Lunch In action</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Late Grace Period (Minutes)</label>
                        <input type="number" name="gracePeriod" placeholder="e.g. 15" value="<?php echo isset($company['grace_period']) ? $company['grace_period'] : ''; ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label>Overtime Percentage (%)</label>
                        <input type="number" name="otPercentage" placeholder="e.g. 25" value="<?php echo isset($company['ot_percentage']) ? $company['ot_percentage'] : ''; ?>" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Deduction Per Second (₱)</label>
                        <input type="number" step="0.0001" name="deductionPerSec" placeholder="e.g. 0.0083" value="<?php echo isset($company['deduction_per_sec']) ? $company['deduction_per_sec'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Deduction Per Minute (₱)</label>
                        <input type="number" step="0.01" name="deductionPerMin" placeholder="e.g. 0.50" value="<?php echo isset($company['deduction_per_min']) ? $company['deduction_per_min'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Deduction Per Hour (₱)</label>
                        <input type="number" step="0.01" name="deductionPerHour" placeholder="e.g. 30.00" value="<?php echo isset($company['deduction_per_hour']) ? $company['deduction_per_hour'] : ''; ?>">
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
