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
        </div>

        <div class="profile-details-card">
            <div class="tab-nav">
                <button class="tab-link active" onclick="switchProfileTab('info', this)">Account Information</button>
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
                    <div id="profile-msg" style="margin-bottom: 15px; display: none;"></div>
                    <button type="button" class="btn btn-primary" id="saveProfileBtn" onclick="saveProfile()">Save Changes</button>
                </form>
            </div>

            <div id="profile-security" class="profile-tab-section" style="display: none;">
                <form onsubmit="changePassword(event)">
                    <div class="form-group-custom">
                        <label>Current Password</label>
                        <input type="password" id="oldPass" required>
                    </div>
                    <div class="form-group-custom">
                        <label>New Password</label>
                        <input type="password" id="newPass" required oninput="checkPasswordStrength()">
                        <div id="password-requirements" style="margin-top: 5px; font-size: 0.8rem;">
                            <div id="req-length" class="req-item">At least 8 characters</div>
                            <div id="req-uppercase" class="req-item">One uppercase letter</div>
                            <div id="req-lowercase" class="req-item">One lowercase letter</div>
                            <div id="req-number" class="req-item">One number</div>
                            <div id="req-special" class="req-item">One special character</div>
                        </div>
                    </div>
                    <div class="form-group-custom">
                        <label>Confirm New Password</label>
                        <input type="password" id="confirmPass" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>
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
function switchProfileTab(tab, btn) {
    document.querySelectorAll('.profile-tab-section').forEach(section => section.style.display = 'none');
    document.querySelectorAll('.tab-link').forEach(link => link.classList.remove('active'));
    document.getElementById('profile-' + tab).style.display = 'block';
    btn.classList.add('active');
}

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
</script>