<?php
/**
 * SD Pages - Assign HR and Payroll Officer Roles
 * Auto-generates usernames and passwords for new users
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'backend/db.php';

// Check if user is SD/Admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'SD', 'School Director'])) {
    header('Location: login.php');
    exit;
}

$full_name = $_SESSION['full_name'] ?? 'School Director';
$company_name = $_SESSION['company_name'] ?? 'ACLC College Tacloban';
$company_id = $_SESSION['company_id'] ?? 1;

// Fetch employees without user accounts
try {
    $stmt = $pdo->prepare("
        SELECT e.id, e.employee_id, e.full_name, e.email, e.position, e.department
        FROM employees e
        LEFT JOIN users u ON e.user_id = u.id
        WHERE u.id IS NULL AND e.status = 'Active'
        ORDER BY e.full_name
    ");
    $stmt->execute();
    $available_employees = $stmt->fetchAll();
    
    // Fetch current HR and Payroll Officer users
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.role, u.is_active, u.created_at, e.full_name, e.employee_id
        FROM users u
        LEFT JOIN employees e ON u.id = e.user_id
        WHERE u.role IN ('HR', 'Admin', 'Payroll Officer')
        AND u.company_id = ?
        ORDER BY u.role, u.username
    ");
    $stmt->execute([$company_id]);
    $assigned_users = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Failed to load data";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Roles - <?php echo htmlspecialchars($company_name); ?></title>
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/sweetalert2.all.min.js"></script>
    <style>
        .assign-roles-page {
            padding: 2rem;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        .page-header {
            background: linear-gradient(135deg, #1e0178 0%, #2d0a8f 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        
        .page-header p {
            opacity: 0.9;
            margin: 0.5rem 0 0;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            color: #1e0178;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: #1e0178;
            color: white;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e0178;
            margin: 0 0 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #1e0178;
        }
        
        .btn-assign {
            width: 100%;
            background: linear-gradient(135deg, #1e0178, #2d0a8f);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-assign:hover {
            box-shadow: 0 4px 12px rgba(30, 1, 120, 0.4);
            transform: translateY(-2px);
        }
        
        .credentials-box {
            background: #f8f9fa;
            border: 2px solid #1e0178;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            display: none;
        }
        
        .credentials-box.show {
            display: block;
        }
        
        .credential-item {
            margin: 0.5rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .credential-label {
            font-weight: 600;
            color: #1e0178;
        }
        
        .credential-value {
            font-family: 'Courier New', monospace;
            background: white;
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #e0e0e0;
        }
        
        .copy-btn {
            background: #1e0178;
            color: white;
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 700;
            color: #1e0178;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-hr { background: #f39c12; color: white; }
        .badge-payroll { background: #3498db; color: white; }
        .badge-active { background: #27ae60; color: white; }
        .badge-inactive { background: #95a5a6; color: white; }
        
        .action-btn {
            background: #e71d36;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: #c91a2d;
        }
        
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="assign-roles-page">
        <a href="sd_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="page-header">
            <h1><i class="fas fa-user-plus"></i> Assign HR & Payroll Officer Roles</h1>
            <p>Auto-generate usernames and passwords for new staff accounts</p>
        </div>
        
        <div class="content-grid">
            <!-- Assignment Form -->
            <div class="card">
                <h3><i class="fas fa-plus-circle"></i> Assign New Role</h3>
                <form id="assignForm">
                    <div class="form-group">
                        <label>Select Employee</label>
                        <select id="employeeSelect" required>
                            <option value="">-- Choose Employee --</option>
                            <?php foreach ($available_employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($emp['full_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($emp['email']); ?>">
                                    <?php echo htmlspecialchars($emp['employee_id'] . ' - ' . $emp['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Assign Role</label>
                        <select id="roleSelect" required>
                            <option value="">-- Select Role --</option>
                            <option value="HR">HR Staff</option>
                            <option value="Admin">HR Admin</option>
                            <option value="Payroll Officer">Payroll Officer</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-assign">
                        <i class="fas fa-magic"></i> Generate Credentials & Assign
                    </button>
                </form>
                
                <div id="credentialsBox" class="credentials-box">
                    <h4 style="margin: 0 0 1rem; color: #1e0178;">
                        <i class="fas fa-key"></i> Generated Credentials
                    </h4>
                    <div class="credential-item">
                        <span class="credential-label">Username:</span>
                        <div>
                            <span class="credential-value" id="genUsername"></span>
                            <button type="button" class="copy-btn" aria-label="Copy Username" title="Copy Username" onclick="copyToClipboard('genUsername', this)">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="credential-item">
                        <span class="credential-label">Password:</span>
                        <div>
                            <span class="credential-value" id="genPassword"></span>
                            <button type="button" class="copy-btn" aria-label="Copy Password" title="Copy Password" onclick="copyToClipboard('genPassword', this)">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <p style="color: #e71d36; font-size: 0.85rem; margin: 1rem 0 0;">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Save these credentials securely! They will not be shown again.
                    </p>
                </div>
            </div>
            
            <!-- Assigned Users Table -->
            <div class="card">
                <h3><i class="fas fa-users-cog"></i> Assigned Users</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($assigned_users)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #95a5a6;">
                                        No users assigned yet
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($assigned_users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></strong><br>
                                            <small style="color: #95a5a6;"><?php echo htmlspecialchars($user['employee_id'] ?? ''); ?></small>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($user['username']); ?></code></td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $user['role'])); ?>">
                                                <?php echo htmlspecialchars($user['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="action-btn" onclick="deactivateUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Generate username from name
        function generateUsername(fullName) {
            const name = fullName.toLowerCase().replace(/[^a-z\s]/g, '');
            const parts = name.trim().split(/\s+/);
            
            if (parts.length >= 2) {
                return parts[0] + '.' + parts[parts.length - 1];
            }
            return parts[0];
        }
        
        // Generate random password
        function generatePassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
            let password = '';
            for (let i = 0; i < 12; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return password;
        }
        
        // Copy to clipboard with micro-UX feedback and resilient fallback
        function copyToClipboard(elementId, btn) {
            const text = document.getElementById(elementId).textContent;

            // Prevent double click/overlapping timeouts
            if (btn && btn.getAttribute('data-copied') === 'true') {
                return;
            }

            const performVisualFeedback = () => {
                if (btn) {
                    btn.setAttribute('data-copied', 'true');
                    const originalIcon = btn.innerHTML;
                    const originalTitle = btn.getAttribute('title');
                    const originalAria = btn.getAttribute('aria-label');

                    btn.innerHTML = '<i class="fas fa-check text-success"></i>';
                    btn.setAttribute('title', 'Copied!');
                    btn.setAttribute('aria-label', 'Copied!');

                    setTimeout(() => {
                        btn.innerHTML = originalIcon;
                        if (originalTitle) btn.setAttribute('title', originalTitle);
                        if (originalAria) btn.setAttribute('aria-label', originalAria);
                        btn.removeAttribute('data-copied');
                    }, 1500);
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: 'Copied to clipboard',
                    timer: 1500,
                    showConfirmButton: false
                });
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(performVisualFeedback).catch(err => {
                    fallbackCopyToClipboard(text, performVisualFeedback);
                });
            } else {
                fallbackCopyToClipboard(text, performVisualFeedback);
            }
        }

        // Resilient fallback for non-secure contexts/headless testing
        function fallbackCopyToClipboard(text, callback) {
            try {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                // Avoid scrolling to bottom
                textArea.style.top = "0";
                textArea.style.left = "0";
                textArea.style.position = "fixed";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                if (successful) {
                    callback();
                } else {
                    Swal.fire('Error', 'Failed to copy text', 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Failed to copy text: ' + err.message, 'error');
            }
        }
        
        // Handle form submission
        document.getElementById('assignForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const employeeId = document.getElementById('employeeSelect').value;
            const role = document.getElementById('roleSelect').value;
            const employeeOption = document.getElementById('employeeSelect').selectedOptions[0];
            
            if (!employeeId || !role) {
                Swal.fire('Error', 'Please fill in all fields', 'error');
                return;
            }
            
            const fullName = employeeOption.dataset.name;
            const email = employeeOption.dataset.email;
            const username = generateUsername(fullName);
            const password = generatePassword();
            
            try {
                const response = await fetch('backend/api.php?action=assign_role', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        employee_id: employeeId,
                        username: username,
                        password: password,
                        email: email,
                        role: role
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('genUsername').textContent = username;
                    document.getElementById('genPassword').textContent = password;
                    document.getElementById('credentialsBox').classList.add('show');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'User credentials generated. Save them now!',
                        showConfirmButton: true
                    }).then(() => {
                        setTimeout(() => location.reload(), 1000);
                    });
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Failed to assign role', 'error');
            }
        });
        
        // Deactivate user
        async function deactivateUser(userId) {
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: 'This will deactivate the user account',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e71d36',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'Yes, remove it!'
            });
            
            if (result.isConfirmed) {
                try {
                    const response = await fetch('backend/api.php?action=deactivate_user', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: userId })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        Swal.fire('Removed!', 'User has been deactivated.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'Failed to deactivate user', 'error');
                }
            }
        }
    </script>
</body>
</html>
