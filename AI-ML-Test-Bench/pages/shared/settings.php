<?php
if (!isset($_SESSION['company_id'])) {
    echo "<p>Error: Company ID not found in session.</p>";
    exit;
}

$stmt_company = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmt_company->execute([$_SESSION['company_id']]);
$company = $stmt_company->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    echo "<p>Error: Company not found.</p>";
    exit;
}
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
                        <input type="time" name="workStart" value="<?php echo $company['work_start'] ?: '08:00'; ?>">
                    </div>
                    <div class="form-group">
                        <label>Time Out (Shift End)</label>
                        <input type="time" name="workEnd" value="<?php echo $company['work_end'] ?: '17:00'; ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Lunch Out Range</label>
                        <div style="display:flex; gap:5px;">
                            <input type="time" name="lunchOutStart" value="<?php echo $company['lunch_out_start'] ?: '10:00'; ?>">
                            <span>to</span>
                            <input type="time" name="lunchOutEnd" value="<?php echo $company['lunch_out_end'] ?: '10:30'; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Lunch In Range</label>
                        <div style="display:flex; gap:5px;">
                            <input type="time" name="lunchInStart" value="<?php echo $company['lunch_in_start'] ?: '10:30'; ?>">
                            <span>to</span>
                            <input type="time" name="lunchInEnd" value="<?php echo $company['lunch_in_end'] ?: '11:00'; ?>">
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
                    <div class="form-group" style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 15px; background: #fafafa;">
                        <label style="font-weight: 700; margin-bottom: 8px; display: block;">Admin Hour (Time In/Out Setting)</label>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <label class="switch" style="position: relative; display: inline-block; width: 50px; height: 26px;">
                                <input type="checkbox" name="adminHourActive" id="adminHourActive" <?php echo ($company['admin_hour_active'] ?? 0) ? 'checked' : ''; ?> onchange="toggleAdminHour(this)">
                                <span class="slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .3s; border-radius: 26px;"><span style="position: absolute; content: ''; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%;"></span></span>
                            </label>
                            <div>
                                <span id="adminHourLabel" style="font-weight: 600; color: <?php echo ($company['admin_hour_active'] ?? 0) ? '#27ae60' : '#e74c3c'; ?>;">
                                    <?php echo ($company['admin_hour_active'] ?? 0) ? 'ACTIVE' : 'INACTIVE'; ?>
                                </span>
                                <p class="small text-muted" style="margin: 4px 0 0 0;">
                                    When <b>ACTIVE</b>: Faculty follows admin Time In/Out settings.<br>
                                    When <b>INACTIVE</b>: Faculty follows their own class schedule only.
                                </p>
                            </div>
                        </div>
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
        
        <?php if ($role === 'HR'): ?>
        <div class="settings-card">
            <h3>Admin Tools</h3>
            <div class="setting-item">
                <div>
                    <strong>Company Code</strong>
                    <p class="small text-muted">Unique identifier for your company.</p>
                </div>
                <input type="text" value="<?php echo htmlspecialchars($company['company_code'], ENT_QUOTES, 'UTF-8'); ?>" class="form-control-large-gray" readonly style="font-weight: 700; color: var(--primary-color);">
            </div>
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
            <?php if ($_SESSION['role'] === 'HR'): ?>
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
        <?php endif; ?>

        
    </div>
</section>
<script>
function toggleAdminHour(el) {
    const label = document.getElementById('adminHourLabel');
    if (el.checked) {
        label.textContent = 'ACTIVE';
        label.style.color = '#27ae60';
    } else {
        label.textContent = 'INACTIVE';
        label.style.color = '#e74c3c';
    }
    toggleAdminHourSave(el.checked);
}
async function toggleAdminHourSave(active) {
    try {
        await fetch('backend/api.php?action=toggle_admin_hour', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ active: active ? 1 : 0 })
        });
    } catch (e) { console.error(e); }
}
</script>
