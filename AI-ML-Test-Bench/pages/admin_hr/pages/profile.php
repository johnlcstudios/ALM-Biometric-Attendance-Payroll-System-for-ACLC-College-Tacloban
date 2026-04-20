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
$profile_picture = $user['profile_picture'] ?? '';

// Check if profile picture exists
if (!empty($profile_picture) && file_exists($profile_picture)) {
    $profile_pic_url = $profile_picture;
} else {
    $profile_pic_url = "https://ui-avatars.com/api/?name=" . urlencode($full_name) . "&size=150&background=random";
}
?>
<section id="profile" class="page">
    <div class="profile-grid">
        <div class="profile-card">
            <div id="profile-picture-container" style="position: relative; display: inline-block; margin-bottom: 1rem;">
                <img id="profile-picture"
                     src="<?php echo htmlspecialchars($profile_pic_url, ENT_QUOTES, 'UTF-8'); ?>"
                     alt="Profile Picture"
                     style="width:150px; height:150px; border-radius:50%; object-fit: cover; border: 4px solid #eee; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                <label for="profile-picture-input" style="position: absolute; bottom: 5px; right: 5px; background: #1e0178; color: white; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2); transition: all 0.3s;">
                    <i class="fas fa-camera" style="font-size: 14px;"></i>
                </label>
                <input type="file" id="profile-picture-input" accept="image/*" style="display: none;" onchange="uploadProfilePicture(event)">
            </div>
            <h2 style="margin-bottom:0.2rem;"><?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="text-muted" style="margin-bottom:1.5rem;"><?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?></p>

            <div class="info-row"><span class="info-label">Username</span> <span class="info-value"><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="info-row"><span class="info-label">Email</span> <span class="info-value"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></span></div>
            <div class="info-row"><span class="info-label">Phone</span> <span class="info-value"><?php echo htmlspecialchars($phone ?: 'Not set', ENT_QUOTES, 'UTF-8'); ?></span></div>
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
                            <input type="text" name="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" required minlength="3" maxlength="100">
                        </div>
                        <div class="form-group-custom">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <div class="form-row-custom">
                        <div class="form-group-custom">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>" placeholder="+63 XXX XXX XXXX" pattern="[0-9+\-\s()]+">
                        </div>
                        <div class="form-group-custom">
                            <label>Role</label>
                            <input type="text" value="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>" readonly class="form-control-large-gray">
                        </div>
                    </div>
                    <div id="profile-msg" style="margin-bottom: 15px; display: none;"></div>
                    <button type="button" class="btn btn-primary" id="saveProfileBtn" onclick="saveProfile()"><i class="fas fa-save"></i> Save Changes</button>
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
/* Profile Page Grid Layout */
.profile-grid {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 2rem;
    align-items: start;
}

/* Profile Card (Left Side) */
.profile-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    text-align: center;
    position: sticky;
    top: 2rem;
}

.profile-card h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 1rem 0 0.5rem;
}

.profile-card .text-muted {
    color: #7f8c8d;
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}

#profile-picture-container {
    position: relative;
    display: inline-block;
    margin-bottom: 1rem;
}

#profile-picture-container label {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
}

#profile-picture-container label:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
}

/* Info Rows */
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #ecf0f1;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #7f8c8d;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    color: #2c3e50;
    font-size: 0.95rem;
    font-weight: 500;
}

/* Status Tag */
.status-tag {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

/* Profile Details Card (Right Side) */
.profile-details-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Tab Navigation */
.tab-nav {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid #ecf0f1;
    padding-bottom: 0;
}

.tab-link {
    background: none;
    border: none;
    padding: 0.75rem 1.5rem;
    font-size: 0.95rem;
    font-weight: 600;
    color: #7f8c8d;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
}

.tab-link:hover {
    color: #667eea;
}

.tab-link.active {
    color: #667eea;
    border-bottom-color: #667eea;
}

/* Form Styling */
.form-row-custom {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group-custom {
    display: flex;
    flex-direction: column;
}

.form-group-custom label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.form-group-custom input[type="text"],
.form-group-custom input[type="email"],
.form-group-custom input[type="tel"],
.form-group-custom input[type="password"] {
    padding: 0.75rem 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #fafafa;
}

.form-group-custom input:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group-custom input[readonly] {
    background: #e8e8e8;
    cursor: not-allowed;
    color: #7f8c8d;
}

.form-control-large-gray {
    width: 100%;
    background-color: #e8e8e8;
    border: 2px solid #d0d0d0;
    color: #7f8c8d;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.95rem;
}

/* Password Requirements */
#password-requirements {
    margin-top: 0.75rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #dc3545;
}

.req-item {
    padding: 0.25rem 0;
    font-size: 0.85rem;
    color: #dc3545;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.req-item::before {
    content: '○';
    font-weight: bold;
}

.req-item.valid {
    color: #28a745;
}

.req-item.valid::before {
    content: '✓';
}

/* Button Styling */
.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.btn-primary:active:not(:disabled) {
    transform: translateY(0);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }

    .profile-card {
        position: static;
    }

    .form-row-custom {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .profile-grid {
        gap: 1rem;
    }

    .profile-card,
    .profile-details-card {
        padding: 1.5rem;
    }

    .tab-nav {
        flex-direction: column;
    }

    .tab-link {
        text-align: left;
        border-bottom: 1px solid #ecf0f1;
        border-left: 3px solid transparent;
        margin-bottom: 0;
        margin-left: 0;
    }

    .tab-link.active {
        border-bottom-color: #ecf0f1;
        border-left-color: #667eea;
        background: #f8f9fa;
    }
}

/* Animations */
.profile-tab-section {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
function switchProfileTab(tab, btn) {
    document.querySelectorAll('.profile-tab-section').forEach(section => section.style.display = 'none');
    document.querySelectorAll('.tab-link').forEach(link => link.classList.remove('active'));
    document.getElementById('profile-' + tab).style.display = 'block';
    btn.classList.add('active');
}

async function uploadProfilePicture(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('image/')) {
        showToast('Please select a valid image file', 'error');
        return;
    }

    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        showToast('Image size must be less than 5MB', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('profile_picture', file);

    try {
        const response = await fetch('backend/api.php?action=upload_profile_picture', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            showToast('Profile picture updated successfully!', 'success');
            // Update the profile picture on the page
            document.getElementById('profile-picture').src = result.picture_url + '?t=' + Date.now();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    } catch (err) {
        showToast('An error occurred while uploading', 'error');
    }
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