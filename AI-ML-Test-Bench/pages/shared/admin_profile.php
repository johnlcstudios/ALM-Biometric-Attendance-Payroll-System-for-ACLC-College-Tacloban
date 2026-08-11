<?php
global $pdo;
// Fetch user profile
$stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$_SESSION['user_id']]);
$user = $stmt_user->fetch();

$full_name = $_SESSION['full_name'] ?? $user['username'];
$role = $_SESSION['role'];
$email = $user['email'];
$username = $user['username'];
$phone = $user['phone'] ?? '';

// Fetch employee data
$stmt_emp = $pdo->prepare("SELECT position, work_position, department, faculty_level, hire_date, status, work_status, basic_salary, emp_code FROM employees WHERE user_id = ?");
$stmt_emp->execute([$_SESSION['user_id']]);
$emp = $stmt_emp->fetch();
?>
<section id="profile" class="page">
    <div class="profile-grid">
        <div class="profile-card">
            <div id="profile-picture-container" style="position: relative; display: inline-block; margin-bottom: 1rem;">
                <img id="profile-picture"
                     src="https://ui-avatars.com/api/?name=<?php echo urlencode($full_name); ?>&size=150&background=random"
                     alt="Profile Picture"
                     style="width:150px; height:150px; border-radius:50%; object-fit: cover; border: 4px solid #eee; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            </div>
            <h2 style="margin-bottom:0.2rem;"><?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="text-muted" style="margin-bottom:1.5rem;"><?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?></p>

            <div class="info-row"><span class="info-label">Username</span> <span class="info-value"><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="info-row"><span class="info-label">Email</span> <span class="info-value"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="info-row"><span class="info-label">Role</span> <span class="info-value"><?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="info-row"><span class="info-label">Status</span> <span class="status-tag status-approved">Active</span></div>
            <?php if ($emp): ?>
            <hr style="margin: 12px 0; border: none; border-top: 1px solid #ecf0f1;">
            <div class="info-row"><span class="info-label">Employee Type</span> <span class="info-value"><?php echo htmlspecialchars($emp['position'] ?: '---', ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="info-row"><span class="info-label">Employment Date</span> <span class="info-value"><?php echo $emp['hire_date'] ? date('F j, Y', strtotime($emp['hire_date'])) : 'Not set'; ?></span></div>
            <div class="info-row"><span class="info-label">Work Position</span> <span class="info-value"><?php echo htmlspecialchars($emp['work_position'] ?: '---', ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="info-row"><span class="info-label">Department</span> <span class="info-value"><?php echo htmlspecialchars($emp['department'] ?: '---', ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="info-row"><span class="info-label">Emp Status</span> <span class="info-value"><?php echo htmlspecialchars($emp['status'] ?: '---', ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="info-row"><span class="info-label">Work Status</span> <span class="info-value"><?php echo htmlspecialchars($emp['work_status'] ?: '---', ENT_QUOTES, 'UTF-8'); ?></span></div>
            <?php endif; ?>
        </div>

        <div class="profile-details-card">
            <div class="tab-nav">
                <button class="tab-link active" onclick="switchProfileTab('info', this)">Account Information</button>
                <?php if ($emp && $emp['position'] === 'Faculty'): ?>
                <button class="tab-link" onclick="switchProfileTab('loads', this)">Teaching Loads</button>
                <?php endif; ?>
                <button class="tab-link" onclick="switchProfileTab('security', this)">Security Settings</button>
            </div>

            <div id="profile-info" class="profile-tab-section active">
                <form id="profileForm">
                    <div class="form-row-custom">
                        <div class="form-group-custom">
                            <label>Full Name (Username)</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="form-group-custom">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <div class="form-row-custom">
                        <div class="form-group-custom">
                            <label>Role</label>
                            <input type="text" value="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>" readonly class="form-control-large-gray">
                        </div>
                    </div>
                    <?php if ($emp): ?>
                    <div class="form-row-custom">
                        <div class="form-group-custom">
                            <label>Employee Type</label>
                            <input type="text" value="<?php echo htmlspecialchars($emp['position'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" readonly class="form-control-large-gray">
                        </div>
                        <div class="form-group-custom">
                            <label>Employment Date</label>
                            <input type="date" name="hire_date" value="<?php echo htmlspecialchars($emp['hire_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div class="form-row-custom">
                        <div class="form-group-custom">
                            <label>Work Position (Academic Rank)</label>
                            <input type="text" name="work_position" value="<?php echo htmlspecialchars($emp['work_position'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. Assistant Professor">
                        </div>
                        <div class="form-group-custom">
                            <label>Work Status</label>
                            <select name="work_status" style="padding:0.75rem 1rem;border:2px solid #e0e0e0;border-radius:8px;font-size:0.95rem;background:#fafafa;">
                                <option value="">Select...</option>
                                <option value="Regular" <?php echo ($emp['work_status'] ?? '') === 'Regular' ? 'selected' : ''; ?>>Regular</option>
                                <option value="Part-time" <?php echo ($emp['work_status'] ?? '') === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                <option value="Contractual" <?php echo ($emp['work_status'] ?? '') === 'Contractual' ? 'selected' : ''; ?>>Contractual</option>
                                <option value="Probationary" <?php echo ($emp['work_status'] ?? '') === 'Probationary' ? 'selected' : ''; ?>>Probationary</option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div id="profile-msg" style="margin-bottom: 15px; display: none;"></div>
                    <button type="button" class="btn btn-primary" id="saveProfileBtn" onclick="saveProfile()">Save Changes</button>
                </form>
            </div>

            <div id="profile-security" class="profile-tab-section" style="display: none;">
                <form onsubmit="changePassword(event)">
                    <div class="form-group-custom">
                        <label>Current Password</label>
                        <div class="profile-password-wrapper">
                            <input type="password" id="oldPass" required>
                            <i class="fas fa-eye toggle-password" aria-label="Toggle password visibility"></i>
                        </div>
                    </div>
                    <div class="form-group-custom" style="margin-top: 15px;">
                        <label>New Password</label>
                        <div class="profile-password-wrapper">
                            <input type="password" id="newPass" required oninput="checkPasswordStrength()">
                            <i class="fas fa-eye toggle-password" aria-label="Toggle password visibility"></i>
                        </div>
                        <div id="password-requirements" style="margin-top: 5px; font-size: 0.8rem;">
                            <div id="req-length" class="req-item">At least 8 characters</div>
                            <div id="req-uppercase" class="req-item">One uppercase letter</div>
                            <div id="req-lowercase" class="req-item">One lowercase letter</div>
                            <div id="req-number" class="req-item">One number</div>
                            <div id="req-special" class="req-item">One special character</div>
                        </div>
                    </div>
                    <div class="form-group-custom" style="margin-top: 15px;">
                        <label>Confirm New Password</label>
                        <div class="profile-password-wrapper">
                            <input type="password" id="confirmPass" required>
                            <i class="fas fa-eye toggle-password" aria-label="Toggle password visibility"></i>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Update Password</button>
                </form>
            </div>

            <?php if ($emp && $emp['position'] === 'Faculty'): ?>
            <div id="profile-loads" class="profile-tab-section" style="display: none;">
                <h4 style="margin-bottom:16px;">My Subject Loads</h4>
                <div style="margin-bottom:16px;">
                    <button class="btn btn-primary btn-sm" onclick="showAddMyLoadForm()"><i class="fas fa-plus"></i> Add Subject Load</button>
                </div>
                <div class="table-container">
                    <table class="payroll-table">
                        <thead>
                            <tr>
                                <th>CODE</th>
                                <th>DESCRIPTION</th>
                                <th>UNITS</th>
                                <th>HOURS/WEEK</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="myLoadsTableBody">
                            <tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>

                <hr style="margin:24px 0;">
                <h4 style="margin-bottom:16px;">Subject Schedules</h4>
                <p class="text-muted" style="font-size:0.85rem;margin-bottom:12px;">Select a subject load above to manage its schedules.</p>
                <div id="myScheduleSection" style="display:none;">
                    <div class="form-row" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;margin-bottom:12px;">
                        <div class="form-group" style="flex:1;min-width:120px;">
                            <label>Day</label>
                            <select id="mySchedDay" style="padding:8px;border:1px solid #ddd;border-radius:6px;width:100%;">
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>
                        <div class="form-group" style="flex:1;min-width:100px;">
                            <label>Time Start</label>
                            <input type="time" id="mySchedTimeStart" style="padding:8px;border:1px solid #ddd;border-radius:6px;width:100%;">
                        </div>
                        <div class="form-group" style="flex:1;min-width:100px;">
                            <label>Time End</label>
                            <input type="time" id="mySchedTimeEnd" style="padding:8px;border:1px solid #ddd;border-radius:6px;width:100%;">
                        </div>
                        <div class="form-group" style="flex:1;min-width:80px;">
                            <label>Room</label>
                            <input type="text" id="mySchedRoom" placeholder="Rm. 101" style="padding:8px;border:1px solid #ddd;border-radius:6px;width:100%;">
                        </div>
                        <div class="form-group" style="min-width:80px;">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary" onclick="saveMySchedule()" style="padding:8px 16px;">Add</button>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="payroll-table">
                            <thead>
                                <tr>
                                    <th>DAY</th>
                                    <th>TIME START</th>
                                    <th>TIME END</th>
                                    <th>ROOM</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="myScheduleTableBody">
                                <tr><td colspan="5" class="text-center text-muted">Select a subject load to view schedules.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.req-item {
    color: #dc3545;
}
.req-item.valid {
    color: #28a745;
}
</style>

<script>
async function saveProfile() {
    const form = document.getElementById('profileForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    const btn = document.getElementById('saveProfileBtn');

    btn.disabled = true;
    btn.innerHTML = 'Saving...';

    try {
        const response = await fetch('backend/api.php?action=update_profile', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            showToast('Profile updated successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    } catch (err) {
        showToast('An error occurred', 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Save Changes';
    }
}

function checkPasswordStrength() {
    const password = document.getElementById('newPass').value;
    const reqs = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /\d/.test(password),
        special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
    };

    Object.keys(reqs).forEach(req => {
        const el = document.getElementById('req-' + req);
        if (reqs[req]) {
            el.classList.add('valid');
        } else {
            el.classList.remove('valid');
        }
    });
}

async function changePassword(event) {
    event.preventDefault();
    const oldPass = document.getElementById('oldPass').value;
    const newPass = document.getElementById('newPass').value;
    const confirmPass = document.getElementById('confirmPass').value;

    if (newPass !== confirmPass) {
        showToast("New passwords do not match!", 'error');
        return;
    }

    // Check if all requirements are met
    const reqs = document.querySelectorAll('.req-item');
    let allValid = true;
    reqs.forEach(req => {
        if (!req.classList.contains('valid')) {
            allValid = false;
        }
    });

    if (!allValid) {
        showToast("Password does not meet requirements!", 'error');
        return;
    }

    const response = await fetch('backend/api.php?action=change_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ oldPass, newPass })
    });

    const result = await response.json();
    if (result.success) {
        Swal.fire({
            icon: 'success',
            title: 'Password Updated',
            text: 'Your password has been changed successfully.',
            confirmButtonColor: '#1e0178'
        }).then(() => {
            document.getElementById('oldPass').value = '';
            document.getElementById('newPass').value = '';
            document.getElementById('confirmPass').value = '';
        });
    } else {
        showToast(result.message, 'error');
    }
}

// --- Faculty Teaching Loads (Self-Service) ---
let mySelectedLoadId = null;

function switchProfileTab(tab, btn) {
    document.querySelectorAll('.profile-tab-section').forEach(s => s.style.display = 'none');
    document.querySelectorAll('.tab-link').forEach(l => l.classList.remove('active'));
    document.getElementById('profile-' + tab).style.display = 'block';
    btn.classList.add('active');
    if (tab === 'loads') loadMySubjectLoads();
}

async function loadMySubjectLoads() {
    const tbody = document.getElementById('myLoadsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Loading...</td></tr>';
    try {
        const resp = await fetch('backend/api.php?action=get_my_subject_loads');
        const loads = await resp.json();
        if (!Array.isArray(loads)) throw new Error('Invalid response');
        tbody.innerHTML = loads.map(load => `
            <tr onclick="selectMyLoad(${load.id})" style="cursor:pointer;" class="load-row" data-load-id="${load.id}">
                <td><strong>${escapeHTML(load.code)}</strong></td>
                <td>${escapeHTML(load.description)}</td>
                <td>${load.units}</td>
                <td>${load.hours}</td>
                <td>
                    <button class="btn btn-sm" style="background:#667eea;color:white;" onclick="event.stopPropagation();editMySubjectLoad(${load.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="event.stopPropagation();deleteMySubjectLoad(${load.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="text-center text-muted">No subject loads yet.</td></tr>';
    } catch(e) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load.</td></tr>';
    }
}

function selectMyLoad(loadId) {
    mySelectedLoadId = loadId;
    document.querySelectorAll('#myLoadsTableBody .load-row').forEach(r => r.classList.remove('selected'));
    const row = document.querySelector(`#myLoadsTableBody .load-row[data-load-id="${loadId}"]`);
    if (row) row.classList.add('selected');
    document.getElementById('myScheduleSection').style.display = 'block';
    loadMySchedules(loadId);
}

async function loadMySchedules(loadId) {
    const tbody = document.getElementById('myScheduleTableBody');
    if (!tbody) return;
    try {
        const resp = await fetch(`backend/api.php?action=get_my_subject_schedules&subject_load_id=${loadId}`);
        const schedules = await resp.json();
        tbody.innerHTML = (Array.isArray(schedules) ? schedules : []).map(s => `
            <tr>
                <td>${s.day_of_week}</td>
                <td>${formatTime(s.time_start)}</td>
                <td>${formatTime(s.time_end)}</td>
                <td>${s.room || '---'}</td>
                <td><button class="btn btn-danger btn-sm" onclick="deleteMySchedule(${s.id})"><i class="fas fa-trash"></i></button></td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="text-center text-muted">No schedules.</td></tr>';
    } catch(e) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load.</td></tr>';
    }
}

function showAddMyLoadForm() {
    Swal.fire({
        title: 'Add Subject Load',
        html: `
            <input id="swalLoadCode" class="swal2-input" placeholder="Subject Code" required>
            <input id="swalLoadDesc" class="swal2-input" placeholder="Description" required>
            <div style="display:flex;gap:8px;">
                <input id="swalLoadUnits" class="swal2-input" type="number" placeholder="Units" value="3" style="flex:1;">
                <input id="swalLoadHours" class="swal2-input" type="number" placeholder="Hours/Week" value="3" style="flex:1;">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Save',
        preConfirm: () => {
            const code = document.getElementById('swalLoadCode').value.trim();
            const description = document.getElementById('swalLoadDesc').value.trim();
            if (!code || !description) Swal.showValidationMessage('Code and description are required');
            return { code, description, units: parseInt(document.getElementById('swalLoadUnits').value) || 3, hours: parseInt(document.getElementById('swalLoadHours').value) || 3 };
        }
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        const resp = await fetch('backend/api.php?action=save_my_subject_load', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify(result.value)
        });
        const data = await resp.json();
        if (data.success) { showToast('Added', 'success'); loadMySubjectLoads(); }
        else { showToast('Error: ' + data.message, 'error'); }
    });
}

async function editMySubjectLoad(loadId) {
    const resp = await fetch('backend/api.php?action=get_my_subject_loads');
    const loads = await resp.json();
    const load = (Array.isArray(loads) ? loads : []).find(l => l.id == loadId);
    if (!load) return;
    Swal.fire({
        title: 'Edit Subject Load',
        html: `
            <input id="swalLoadCode" class="swal2-input" value="${escapeHTML(load.code)}" required>
            <input id="swalLoadDesc" class="swal2-input" value="${escapeHTML(load.description)}" required>
            <div style="display:flex;gap:8px;">
                <input id="swalLoadUnits" class="swal2-input" type="number" value="${load.units}" style="flex:1;">
                <input id="swalLoadHours" class="swal2-input" type="number" value="${load.hours}" style="flex:1;">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Update',
        preConfirm: () => {
            const code = document.getElementById('swalLoadCode').value.trim();
            const description = document.getElementById('swalLoadDesc').value.trim();
            if (!code || !description) Swal.showValidationMessage('Required');
            return { id: loadId, code, description, units: parseInt(document.getElementById('swalLoadUnits').value) || 3, hours: parseInt(document.getElementById('swalLoadHours').value) || 3 };
        }
    }).then(async (result) => {
        if (!result.isConfirmed) return;
        const resp = await fetch('backend/api.php?action=save_my_subject_load', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify(result.value)
        });
        const data = await resp.json();
        if (data.success) { showToast('Updated', 'success'); loadMySubjectLoads(); }
        else { showToast('Error: ' + data.message, 'error'); }
    });
}

async function deleteMySubjectLoad(id) {
    const conf = await Swal.fire({ title: 'Delete?', text: 'Also removes schedules.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#db261f', confirmButtonText: 'Delete' });
    if (!conf.isConfirmed) return;
    const resp = await fetch(`backend/api.php?action=delete_my_subject_load&id=${id}`);
    const data = await resp.json();
    if (data.success) { showToast('Deleted', 'success'); if (mySelectedLoadId == id) { mySelectedLoadId = null; document.getElementById('myScheduleSection').style.display = 'none'; } loadMySubjectLoads(); }
    else { showToast('Error: ' + data.message, 'error'); }
}

async function saveMySchedule() {
    if (!mySelectedLoadId) { showToast('Select a subject load first', 'warning'); return; }
    const day = document.getElementById('mySchedDay').value;
    const timeStart = document.getElementById('mySchedTimeStart').value;
    const timeEnd = document.getElementById('mySchedTimeEnd').value;
    const room = document.getElementById('mySchedRoom').value;
    if (!timeStart || !timeEnd) { showToast('Start and end times required', 'warning'); return; }
    const resp = await fetch('backend/api.php?action=save_my_subject_schedule', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ subject_load_id: mySelectedLoadId, day_of_week: day, time_start: timeStart, time_end: timeEnd, room })
    });
    const data = await resp.json();
    if (data.success) {
        showToast('Schedule added', 'success');
        document.getElementById('mySchedTimeStart').value = '';
        document.getElementById('mySchedTimeEnd').value = '';
        document.getElementById('mySchedRoom').value = '';
        loadMySchedules(mySelectedLoadId);
    } else { showToast('Error: ' + data.message, 'error'); }
}

async function deleteMySchedule(id) {
    const conf = await Swal.fire({ title: 'Delete?', text: 'Remove this schedule?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#db261f', confirmButtonText: 'Delete' });
    if (!conf.isConfirmed) return;
    const resp = await fetch(`backend/api.php?action=delete_my_subject_schedule&id=${id}`);
    const data = await resp.json();
    if (data.success) { showToast('Deleted', 'success'); if (mySelectedLoadId) loadMySchedules(mySelectedLoadId); }
    else { showToast('Error: ' + data.message, 'error'); }
}
</script>