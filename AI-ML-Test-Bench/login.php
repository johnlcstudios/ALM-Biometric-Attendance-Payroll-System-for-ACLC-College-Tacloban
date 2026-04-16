<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? 'Employee';
    if (in_array($role, ['Payroll', 'Payroll Officer'])) {
        header('Location: Payroll-Officer.php');
    } elseif ($role === 'Admin' || $role === 'HR') {
        header('Location: index.php');
    } else {
        header('Location: ess.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - ALM Biometric Attendance</title>

<!-- Font Awesome (local with CDN fallback) -->
<link rel="stylesheet" href="css/all.min.css" onerror="this.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'">

<!-- SweetAlert2 (local with CDN fallback) -->
<script src="js/sweetalert2.all.min.js" onerror="this.src='https://cdn.jsdelivr.net/npm/sweetalert2@11'"></script>

<!-- Custom Context Menu Styles -->
<link rel="stylesheet" href="css/style.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* BODY */
body {
    height: 100vh;
    background: url('assets/bg.jpg') no-repeat center center/cover;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* GLASS CARD */
.login-card {
    width: 450px;
    padding: 40px;

    /* GLASS EFFECT */
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(25px) saturate(180%);
    -webkit-backdrop-filter: blur(25px) saturate(180%);

    border-radius: 20px;

    /* SOFT BORDER + INNER LIGHT */
    border: 1px solid rgba(255, 255, 255, 0.25);

    /* DEPTH SHADOW */
    box-shadow:
        0 8px 32px rgba(0, 0, 0, 0.25),
        inset 0 1px 1px rgba(255, 255, 255, 0.4);
}

/* LOGO */
.logo {
    display: block;
    margin: 0 auto 15px auto;
    width: 90px;
    height: 90px;
    border-radius: 50%;
}

/* TITLE */
.login-left h2 {
    margin-bottom: 25px;
    color: #ffffff;
    text-align: center;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* INPUT */
.form-group {
    margin-bottom: 20px;
}

.input-wrapper {
    position: relative;
}

/* GLASS INPUT */
.input-wrapper input {
    width: 100%;
    padding: 12px 40px 12px 15px;

    border-radius: 20px;

    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255,255,255,0.3);

    color: #fff;
    outline: none;

    backdrop-filter: blur(10px);
}

.input-wrapper input::placeholder {
    color: rgba(255,255,255,0.7);
}

/* FOCUS GLOW */
.input-wrapper input:focus {
    border: 1px solid rgba(255,255,255,0.6);
    box-shadow: 0 0 10px rgba(255,255,255,0.4);
}

/* ICON */
.input-wrapper i {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255,255,255,0.7);
}

/* LOGIN BUTTON (macOS style gradient) */
.login-btn {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 20px;

    background: linear-gradient(135deg, #4facfe, #00f2fe);
    color: #fff;

    font-weight: 600;
    cursor: pointer;

    transition: all 0.3s ease;
}

/* BUTTON HOVER */
.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}

/* KIOSK BUTTON (secondary glass button) */
.kiosk-btn {
    display: block;
    width: 100%;
    padding: 12px;

    border-radius: 20px;

    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.3);

    color: #fff;
    font-weight: 600;
    text-align: center;
    text-decoration: none;

    backdrop-filter: blur(10px);
    transition: 0.3s;
}

.kiosk-btn:hover {
    background: rgba(255,255,255,0.3);
}

/* SIGNUP */
.signup-text {
    margin: 15px 0;
    font-size: 14px;
    text-align: center;
    color: rgba(255,255,255,0.8);
}

.signup-text a {
    color: #ffffff;
    font-weight: 600;
    text-decoration: underline;
}

/* Glass Morphism Swal2 Modal Styles for Login Page */
.swal2-popup.glass-modal {
    background: rgba(255, 255, 255, 0.15) !important;
    backdrop-filter: blur(25px) saturate(180%) !important;
    -webkit-backdrop-filter: blur(25px) saturate(180%) !important;
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    border-radius: 20px !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25), inset 0 1px 1px rgba(255, 255, 255, 0.4) !important;
    padding: 30px !important;
}

.swal2-popup.glass-modal .swal2-title {
    color: #ffffff !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
    font-size: 24px !important;
    font-weight: 700 !important;
    letter-spacing: 0.5px !important;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) !important;
}

.swal2-popup.glass-modal .swal2-html-container,
.swal2-popup.glass-modal .swal2-text {
    color: rgba(255, 255, 255, 0.9) !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
    font-size: 15px !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15) !important;
}

.swal2-popup.glass-modal .swal2-icon {
    border-width: 3px !important;
    margin: 0 auto 20px !important;
}

.swal2-popup.glass-modal .swal2-icon.swal2-success {
    border-color: rgba(39, 174, 96, 0.8) !important;
    color: #27ae60 !important;
}

.swal2-popup.glass-modal .swal2-icon.swal2-error {
    border-color: rgba(219, 38, 31, 0.8) !important;
    color: #db261f !important;
}

.swal2-popup.glass-modal .swal2-icon.swal2-warning {
    border-color: rgba(243, 156, 18, 0.8) !important;
    color: #f39c12 !important;
}

.swal2-popup.glass-modal .swal2-icon.swal2-info {
    border-color: rgba(79, 172, 254, 0.8) !important;
    color: #4facfe !important;
}

.swal2-popup.glass-modal .swal2-icon.swal2-question {
    border-color: rgba(108, 117, 125, 0.8) !important;
    color: #6c757d !important;
}

.swal2-popup.glass-modal .swal2-confirm,
.swal2-popup.glass-modal .swal2-cancel {
    border-radius: 20px !important;
    padding: 12px 24px !important;
    font-weight: 600 !important;
    font-size: 14px !important;
    letter-spacing: 0.3px !important;
    transition: all 0.3s ease !important;
    border: none !important;
}

.swal2-popup.glass-modal .swal2-confirm {
    background: linear-gradient(135deg, #4facfe, #00f2fe) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3) !important;
}

.swal2-popup.glass-modal .swal2-confirm:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 20px rgba(79, 172, 254, 0.4) !important;
}

.swal2-popup.glass-modal .swal2-cancel {
    background: rgba(255, 255, 255, 0.15) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    color: #ffffff !important;
    backdrop-filter: blur(10px) !important;
}

.swal2-popup.glass-modal .swal2-cancel:hover {
    background: rgba(255, 255, 255, 0.25) !important;
}

.swal2-popup.glass-modal .swal2-input,
.swal2-popup.glass-modal .swal2-textarea,
.swal2-popup.glass-modal .swal2-select {
    background: rgba(255, 255, 255, 0.2) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    border-radius: 20px !important;
    color: #ffffff !important;
    backdrop-filter: blur(10px) !important;
    padding: 12px 15px !important;
}

.swal2-popup.glass-modal .swal2-input:focus,
.swal2-popup.glass-modal .swal2-textarea:focus,
.swal2-popup.glass-modal .swal2-select:focus {
    border: 1px solid rgba(255, 255, 255, 0.6) !important;
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.4) !important;
    outline: none !important;
}

.swal2-popup.glass-modal .swal2-input::placeholder,
.swal2-popup.glass-modal .swal2-textarea::placeholder {
    color: rgba(255, 255, 255, 0.7) !important;
}

.swal2-popup.glass-modal .swal2-validation-message {
    background: rgba(219, 38, 31, 0.15) !important;
    border: 1px solid rgba(219, 38, 31, 0.3) !important;
    color: #ffffff !important;
    border-radius: 12px !important;
}

.swal2-popup.glass-modal .swal2-loader {
    border-color: rgba(79, 172, 254, 0.3) !important;
    border-top-color: #4facfe !important;
}

/* Glass modal backdrop */
.swal2-container.glass-backdrop {
    background: rgba(0, 0, 0, 0.5) !important;
    backdrop-filter: blur(4px) !important;
}

/* Glass Toast Styles */
.glass-toast-popup {
    background: rgba(255, 255, 255, 0.15) !important;
    backdrop-filter: blur(25px) saturate(180%) !important;
    -webkit-backdrop-filter: blur(25px) saturate(180%) !important;
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    border-radius: 20px !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25), inset 0 1px 1px rgba(255, 255, 255, 0.4) !important;
    padding: 16px 20px !important;
    min-width: 320px !important;
    max-width: 400px !important;
}

.glass-toast-title {
    color: #ffffff !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    letter-spacing: 0.3px !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2) !important;
}

.glass-toast-progress {
    height: 3px !important;
    background: linear-gradient(90deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.6)) !important;
    border-radius: 2px !important;
}
</style>

</head>
<body>

<div class="login-card">
    <div class="login-left">

        <!-- LOGO -->
        <img src="assets/logo.jpg" alt="Logo" class="logo">

        <h2>Login</h2>
        <p>Welcome back! Please login to access your account.</p>
        <p>This is an EXPERIMENTAL BRANCH. Use at your own risk.</p>

        <form id="loginForm">
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="text" name="username" placeholder="Username" required>
                    <i class="fas fa-user"></i>
                </div>
            </div>

            <div class="form-group">
                <div class="input-wrapper">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class="fas fa-lock"></i>
                </div>
            </div>

            <button type="submit" class="login-btn">Login</button>

            <div style="text-align: center; margin-top: 15px;">
                <a href="#" id="forgotPasswordLink" style="color: rgba(255,255,255,0.8); font-size: 14px; text-decoration: none;">
                    Forgot Password?
                </a>
            </div>
            <br>

            <!-- KIOSK BUTTON -->
            <a href="kiosk.php" class="kiosk-btn">Launch Kiosk</a>

            <p class="signup-text">
                Dont have an account? <a href="signup.php">Sign Up</a>
            </p>

        </form>
    </div>
</div>

<!-- Splash Screen -->
<div id="splashScreen" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    transition: opacity 0.8s ease-out, visibility 0.8s;
">
    <div style="
        text-align: center;
        color: white;
        animation: fadeInUp 1s ease-out;
    ">
        <!-- Logo/Icon -->
        <div style="
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            border: 3px solid rgba(255, 255, 255, 0.3);
            animation: pulse 2s infinite;
        ">
            <i class="fas fa-fingerprint" style="font-size: 50px;"></i>
        </div>
        
        <!-- System Name -->
        <h1 style="
            font-size: 42px;
            margin-bottom: 10px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            animation: slideIn 1s ease-out;
        ">ALM Biometric System</h1>
        
        <!-- Version -->
        <p style="
            font-size: 18px;
            margin-bottom: 40px;
            opacity: 0.9;
            font-weight: 300;
        ">Version 2.4.0 - Build9</p>
        
        <!-- Credits -->
        <div style="
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px 40px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            animation: fadeIn 1.5s ease-out;
        ">
            <p style="
                font-size: 20px;
                margin-bottom: 10px;
                font-weight: 600;
            ">
                <i class="fas fa-heart" style="color: #ff6b6b; animation: heartbeat 1.5s infinite;"></i>
            </p>
            <p style="
                font-size: 18px;
                margin: 0;
                line-height: 1.6;
                font-weight: 500;
            ">Built with STRESS from</p>
            <p style="
                font-size: 24px;
                margin: 10px 0;
                font-weight: 700;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            ">BSIT 3A</p>
            <p style="
                font-size: 16px;
                margin: 5px 0 0;
                opacity: 0.9;
            ">A.Y. 2025-2026 | Batch 2027</p>
        </div>
        
        <!-- Loading Indicator -->
        <div style="margin-top: 40px;">
            <div style="
                width: 50px;
                height: 50px;
                margin: 0 auto;
                border: 4px solid rgba(255, 255, 255, 0.3);
                border-top: 4px solid white;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            "></div>
        </div>
    </div>
</div>

<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 0 0 20px rgba(255, 255, 255, 0);
    }
}

@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    10%, 30% { transform: scale(1.2); }
    20%, 40% { transform: scale(1); }
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
// Splash screen timeout
window.addEventListener('load', function() {
    setTimeout(function() {
        const splash = document.getElementById('splashScreen');
        if (splash) {
            splash.style.opacity = '0';
            splash.style.visibility = 'hidden';
            setTimeout(function() {
                splash.style.display = 'none';
            }, 800);
        }
    }, 3000); // Show for 3 seconds
});
</script>

<script>
document.getElementById('loginForm').onsubmit = async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);

    const response = await fetch('backend/api.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(Object.fromEntries(formData))
    });

    const result = await response.json();

    if (result.success) {
        const role = result.role ? result.role.trim() : 'Employee';
        if (role === 'Payroll' || role === 'Payroll Officer') {
            window.location.href = 'Payroll-Officer.php';
        } else if (role === 'Admin' || role === 'HR') {
            window.location.href = 'index.php';
        } else {
            window.location.href = 'ess.php';
        }
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: result.message || 'Invalid credentials',
            customClass: {
                popup: 'glass-modal',
                container: 'glass-backdrop'
            },
            background: 'transparent',
            confirmButtonColor: '#4facfe'
        });
    }
};

// Forgot Password Handler
document.getElementById('forgotPasswordLink').addEventListener('click', async (e) => {
    e.preventDefault();
    
    // Step 1: Ask for Employee ID and Company Code
    const { value: formValues } = await Swal.fire({
        title: 'Reset Password',
        html: `
            <input id="swal-employee-id" class="swal2-input" placeholder="Employee ID (e.g., EMP001)">
            <input id="swal-company-code" class="swal2-input" placeholder="Company Code">
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Verify',
        confirmButtonColor: '#4facfe',
        customClass: {
            popup: 'glass-modal',
            container: 'glass-backdrop'
        },
        background: 'transparent',
        preConfirm: () => {
            const employeeId = document.getElementById('swal-employee-id').value;
            const companyCode = document.getElementById('swal-company-code').value;
            
            if (!employeeId || !companyCode) {
                Swal.showValidationMessage('Both Employee ID and Company Code are required');
                return false;
            }
            
            return { employeeId, companyCode };
        }
    });
    
    if (!formValues) return;
    
    // Step 2: Verify credentials
    Swal.fire({
        title: 'Verifying...',
        allowOutsideClick: false,
        customClass: {
            popup: 'glass-modal',
            container: 'glass-backdrop'
        },
        background: 'transparent',
        didOpen: () => Swal.showLoading()
    });
    
    const response = await fetch('backend/api.php?action=forgot_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            employee_id: formValues.employeeId, 
            company_code: formValues.companyCode 
        })
    });
    
    const result = await response.json();
    
    if (!result.success) {
        Swal.fire({
            icon: 'error',
            title: 'Verification Failed',
            text: result.message,
            customClass: {
                popup: 'glass-modal',
                container: 'glass-backdrop'
            },
            background: 'transparent',
            confirmButtonColor: '#4facfe'
        });
        return;
    }
    
    // Step 3: Ask for new password
    const { value: newPassword } = await Swal.fire({
        title: 'Set New Password',
        html: `
            <p style="margin-bottom: 10px; color: rgba(255,255,255,0.9);">Verified: <strong>${result.employee_name}</strong></p>
            <p style="margin-bottom: 15px; color: rgba(255,255,255,0.9); font-size: 0.9em;">Username: <strong>${result.username}</strong></p>
            <input id="swal-new-password" type="password" class="swal2-input" placeholder="New Password (min 6 characters)">
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Reset Password',
        confirmButtonColor: '#4facfe',
        customClass: {
            popup: 'glass-modal',
            container: 'glass-backdrop'
        },
        background: 'transparent',
        preConfirm: () => {
            const password = document.getElementById('swal-new-password').value;
            
            if (!password) {
                Swal.showValidationMessage('Password is required');
                return false;
            }
            
            if (password.length < 6) {
                Swal.showValidationMessage('Password must be at least 6 characters');
                return false;
            }
            
            return password;
        }
    });
    
    if (!newPassword) return;
    
    // Step 4: Reset password
    Swal.fire({
        title: 'Resetting Password...',
        allowOutsideClick: false,
        customClass: {
            popup: 'glass-modal',
            container: 'glass-backdrop'
        },
        background: 'transparent',
        didOpen: () => Swal.showLoading()
    });
    
    const resetResponse = await fetch('backend/api.php?action=reset_password_with_token', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            token: result.reset_token,
            password: newPassword 
        })
    });
    
    const resetResult = await resetResponse.json();
    
    if (resetResult.success) {
        Swal.fire({
            icon: 'success',
            title: 'Password Reset Successful',
            text: 'You can now login with your new password.',
            customClass: {
                popup: 'glass-modal',
                container: 'glass-backdrop'
            },
            background: 'transparent',
            confirmButtonColor: '#4facfe'
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Reset Failed',
            text: resetResult.message,
            customClass: {
                popup: 'glass-modal',
                container: 'glass-backdrop'
            },
            background: 'transparent',
            confirmButtonColor: '#4facfe'
        });
    }
});
</script>

<!-- Custom Context Menu -->
<script src="js/context-menu.js?v=1.0"></script>

</body>
</html>
