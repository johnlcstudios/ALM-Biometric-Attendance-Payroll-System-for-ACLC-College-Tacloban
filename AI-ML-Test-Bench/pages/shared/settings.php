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
                        <label>System Timezone</label>
                        <select name="timezone" class="form-control">
                            <?php
                            $timezones = [
                                'Asia/Manila' => 'Philippines (GMT+8)',
                                'UTC' => 'UTC / GMT',
                                'Asia/Singapore' => 'Singapore (GMT+8)',
                                'Asia/Hong_Kong' => 'Hong Kong (GMT+8)',
                                'Asia/Tokyo' => 'Tokyo (GMT+9)',
                                'Australia/Sydney' => 'Sydney (GMT+11)',
                                'Europe/London' => 'London (GMT+0)',
                                'America/New_York' => 'New York (GMT-5)',
                                'America/Los_Angeles' => 'Los Angeles (GMT-8)'
                            ];
                            foreach ($timezones as $tz => $label) {
                                $selected = ($company['timezone'] === $tz) ? 'selected' : '';
                                echo "<option value=\"$tz\" $selected>$label</option>";
                            }
                            ?>
                        </select>
                    </div>
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
                            <input type="time" name="lunchOutStart" value="<?php echo $company['lunch_out_start'] ?? '10:00'; ?>">
                            <span>to</span>
                            <input type="time" name="lunchOutEnd" value="<?php echo $company['lunch_out_end'] ?? '10:30'; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Lunch In Range</label>
                        <div style="display:flex; gap:5px;">
                            <input type="time" name="lunchInStart" value="<?php echo $company['lunch_in_start'] ?? '10:30'; ?>">
                            <span>to</span>
                            <input type="time" name="lunchInEnd" value="<?php echo $company['lunch_in_end'] ?? '11:00'; ?>">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Lunch Buffer (Minutes)</label>
                        <input type="number" name="lunchBuffer" value="<?php echo $company['lunch_buffer'] ?? '30'; ?>" min="0">
                        <small class="text-muted">Min. time between Lunch Out and Lunch In</small>
                    </div>
                    <div class="form-group">
                        <label>Time Out Buffer (Minutes)</label>
                        <input type="number" name="checkoutBuffer" value="<?php echo $company['checkout_buffer'] ?? '60'; ?>" min="0">
                        <small class="text-muted">Min. time between Lunch In and Time Out</small>
                    </div>
                </div>
                <div class="form-row">
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
                <div id="settings-msg" style="margin-bottom: 15px; display: none;"></div>
                <button type="button" class="btn btn-primary" id="saveSettingsBtn" onclick="saveSettings()">
                    <i class="fas fa-save"></i> Save System Settings
                </button>
            </form>
        </div>
        
        <?php if (in_array($role, ['Admin', 'HR', 'Payroll Officer'])): ?>
        <div class="settings-card">
            <h3>Admin Tools</h3>
            <div class="setting-item">
                <!-- <div>
                    <strong>Manage Access</strong>
                    <p class="small text-muted">Assign Payroll Officers and HR roles.</p>
                </div>
                <button class="btn btn-primary btn-sm" onclick="showPage('assign_payroll')">Manage Access</button>
            </div>
            <div class="setting-item">
                <div>
                    <strong>Subject Loads</strong>
                    <p class="small text-muted">Configure academic units for Faculty.</p>
                </div>
                <button class="btn btn-secondary btn-sm" onclick="showPage('subject_loads')">Configure Loads</button>
            </div> -->
            <div class="setting-item">
                <div>
                    <strong>Company Code</strong>
                    <p class="small text-muted">Unique identifier for your company.</p>
                </div>
                </div>
                <input type="text" value="<?php echo htmlspecialchars($company['company_code'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control-large-gray" readonly style="font-weight: 700; color: var(--primary-color);">
            </div>
            <div class="settings-card">
            <h3>Backup & Security</h3>
            <div class="setting-item">
                <div>
                    <strong>Attendance Kiosk</strong>
                    <p class="small text-muted">Open the face recognition terminal.</p>
                </div>
                <a href="kiosk.php?company_id=<?php echo $_SESSION['company_id']; ?>" target="_blank" class="btn btn-primary btn-sm">
                    Launch Terminal <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
            <div class="setting-item">
                <div>
                    <strong>System Backup</strong>
                    <p class="small text-muted">Download current database state.</p>
                </div>
                <button class="btn btn-secondary btn-sm" onclick="Swal.fire({ icon: 'info', title: 'Backup Note', text: 'Backup functionality is handled via MySQL Workbench or phpMyAdmin for security.', confirmButtonColor: '#1e0178' })">Download SQL</button>
            </div>
            <?php if ($_SESSION['role'] === 'Admin'): ?>
            <div class="setting-item">
                <div>
                    <strong>Drop Database (DESTRUCTIVE)</strong>
                    <p class="small text-muted text-danger">Permanently delete entire database. Local access only.</p>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="drop-db.php" class="btn btn-danger btn-sm" onclick="return confirm('WARNING: This deletes ALL data! Continue?')">
                        <i class="fas fa-trash"></i> Web Drop
                    </a>
                    <a href="drop_database.bat" download class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-download"></i> Download .bat
                    </a>
                </div>
            </div>
            <?php endif; ?>
            <div class="setting-item">
                <div>
                    <strong>Security</strong>
                    <p class="small text-muted">Update your administrative password.</p>
                </div>
                <button class="btn btn-secondary btn-sm" onclick="openModal('passwordModal')">Change Password</button>
            </div>
        </div>
        </div>
        <?php endif; ?>

        
    </div>
</section>
