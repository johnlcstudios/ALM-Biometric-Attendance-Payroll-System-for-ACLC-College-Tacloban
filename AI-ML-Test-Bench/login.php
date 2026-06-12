<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? 'Employee';
    if ($_SESSION['role'] === 'HR') {
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

<!-- Font Awesome -->
<link rel="stylesheet" href="css/all.min.css" onerror="this.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'">

<!-- SweetAlert2 -->
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

body {
    height: 100vh;
    background: url('assets/bg.jpg') no-repeat center center/cover;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-card {
    width: 450px;
    padding: 40px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(25px) saturate(180%);
    -webkit-backdrop-filter: blur(25px) saturate(180%);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25), inset 0 1px 1px rgba(255, 255, 255, 0.4);
}

.logo {
    display: block;
    margin: 0 auto 15px auto;
    width: 90px;
    height: 90px;
    border-radius: 50%;
}

.login-left h2 {
    margin-bottom: 25px;
    color: #ffffff;
    text-align: center;
    font-weight: 600;
}

.form-group {
    margin-bottom: 20px;
}

.input-wrapper {
    position: relative;
}

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

.input-wrapper input:focus {
    border: 1px solid rgba(255,255,255,0.6);
    box-shadow: 0 0 10px rgba(255,255,255,0.4);
}

.input-wrapper i {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255,255,255,0.7);
}

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

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}

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

.swal2-popup.glass-modal {
    background: rgba(255, 255, 255, 0.15) !important;
    backdrop-filter: blur(25px) saturate(180%) !important;
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    border-radius: 20px !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25) !important;
    padding: 30px !important;
}

.swal2-popup.glass-modal .swal2-title,
.swal2-popup.glass-modal .swal2-html-container {
    color: #ffffff !important;
}

.swal2-popup.glass-modal .swal2-input {
    background: rgba(255, 255, 255, 0.2) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    border-radius: 20px !important;
    color: #ffffff !important;
    padding: 12px 15px !important;
}

.swal2-popup.glass-modal .swal2-confirm {
    background: linear-gradient(135deg, #4facfe, #00f2fe) !important;
    border-radius: 20px !important;
}

.swal2-container.glass-backdrop {
    background: rgba(0, 0, 0, 0.5) !important;
    backdrop-filter: blur(4px) !important;
}

.otp-display {
    font-size: 48px;
    font-weight: bold;
    letter-spacing: 10px;
    text-align: center;
    background: rgba(0,0,0,0.3);
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
    font-family: monospace;
}

.timer-text {
    font-size: 16px;
    font-weight: bold;
    color: #ffaa00;
    margin-top: 10px;
    text-align: center;
    padding: 10px;
    background: rgba(0,0,0,0.3);
    border-radius: 10px;
}

.timer-text.expired {
    color: #ff4444;
}

.resend-btn {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 20px;
    padding: 10px 16px;
    color: white;
    cursor: pointer;
    margin-top: 15px;
    width: 100%;
    text-align: center;
    font-weight: bold;
    transition: all 0.3s ease;
}

.resend-btn:hover:not(.disabled) {
    background: rgba(255,255,255,0.3);
    transform: translateY(-1px);
}

.resend-btn.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
</head>
<body>

<div class="login-card">
    <div class="login-left">
        <img src="assets/logo.jpg" alt="Logo" class="logo">
        <h2>Login</h2>
        <p><strong><h2 style="color: #fff; font-size: 16px;">Welcome back!</h2></strong><br> Please login to access your account.</p>
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
            <a href="kiosk.php" class="kiosk-btn">Launch Kiosk</a>
            <p class="signup-text">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </p>
        </form>
    </div>
</div>

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
    transition: opacity 0.8s ease-out;
">
    <div style="text-align: center; color: white;">
        <div style="width: 100px; height: 100px; margin: 0 auto 30px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px); border: 3px solid rgba(255,255,255,0.3);">
            <i class="fas fa-fingerprint" style="font-size: 50px;"></i>
        </div>
        <h1 style="font-size: 42px; margin-bottom: 10px;">ALM Biometric System</h1>
        <p style="font-size: 18px; margin-bottom: 40px;">Version 2.5.0</p>
        <div style="width: 50px; height: 50px; margin: 0 auto; border: 4px solid rgba(255,255,255,0.3); border-top: 4px solid white; border-radius: 50%; animation: spin 1s linear infinite;"></div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 700px) {
    body {
        align-items: flex-start;
        padding: 24px 0 40px;
    }
    .login-card {
        width: min(92vw, 420px);
        padding: 28px 24px;
    }
    .login-card h2,
    .login-card p {
        text-align: center;
    }
    .login-card .form-group {
        margin-bottom: 18px;
    }
    .login-btn,
    .kiosk-btn {
        padding: 14px 16px;
    }
}

@media (max-width: 520px) {
    .input-wrapper input {
        padding: 12px 16px;
    }
    .input-wrapper i {
        right: 12px;
    }
}
</style>

<script>
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
    }, 3000);
});

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
        if (role === 'HR') {
            window.location.href = 'index.php';
        } else {
            window.location.href = 'ess.php';
        }
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: result.message || 'Invalid credentials',
            customClass: { popup: 'glass-modal', container: 'glass-backdrop' },
            background: 'transparent',
            confirmButtonColor: '#4facfe'
        });
    }
};

// Forgot Password Handler with Live Timer
document.getElementById('forgotPasswordLink').addEventListener('click', async (e) => {
    e.preventDefault();
    
    let userEmployeeId = '';
    let userEmail = '';
    let userId = null;
    let resetToken = '';
    let countdownInterval = null;
    
    // STEP 1: Get Employee ID and Company Code
    const { value: formValues } = await Swal.fire({
        title: 'Reset Password',
        html: `
            <input id="swal-employee-id" type="text" class="swal2-input" placeholder="Employee ID" required>
            <input id="swal-company-code" class="swal2-input" placeholder="Company Code" required>
            <p style="margin-top: 10px; font-size: 12px; color: rgba(255,255,255,0.7);">
                ⚠️ DEMO MODE: OTP will appear on screen (Valid for 5 minutes)
            </p>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Send OTP',
        confirmButtonColor: '#4facfe',
        cancelButtonText: 'Cancel',
        customClass: { popup: 'glass-modal', container: 'glass-backdrop' },
        background: 'transparent',
        preConfirm: () => {
            const employeeId = document.getElementById('swal-employee-id').value;
            const companyCode = document.getElementById('swal-company-code').value;
            if (!employeeId || !companyCode) {
                Swal.showValidationMessage('Employee ID and Company Code are required');
                return false;
            }
            return { employeeId, companyCode };
        }
    });
    
    if (!formValues) return;
    
    // Function to generate OTP
    async function generateOTP() {
        Swal.fire({
            title: 'Generating OTP...',
            allowOutsideClick: false,
            customClass: { popup: 'glass-modal', container: 'glass-backdrop' },
            background: 'transparent',
            didOpen: () => Swal.showLoading()
        });
        
        const otpResponse = await fetch('backend/api.php?action=request_password_otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                employee_id: formValues.employeeId, 
                company_code: formValues.companyCode 
            })
        });
        
        const otpResult = await otpResponse.json();
        
        if (!otpResult.success) {
            Swal.fire({
                icon: 'error',
                title: 'Failed',
                text: otpResult.message,
                customClass: { popup: 'glass-modal', container: 'glass-backdrop' },
                background: 'transparent',
                confirmButtonColor: '#4facfe'
            });
            return null;
        }
        
        return otpResult;
    }
    
    // First OTP generation
    let otpResult = await generateOTP();
    if (!otpResult) return;
    
    userEmployeeId = formValues.employeeId;
    userEmail = otpResult.email;
    userId = otpResult.reset_id;
    let currentOtp = otpResult.test_otp;
    
    // Function to format time as MM:SS
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    
    // Function to show OTP dialog with live timer
    function showOTPDialogWithTimer(otp, email, empId) {
        let timeLeft = 300; // 5 minutes in seconds
        let canResend = false;
        
        // Clear any existing interval
        if (countdownInterval) clearInterval(countdownInterval);
        
        const htmlContent = `
            <p style="margin-bottom: 10px; color: rgba(255,255,255,0.9);">
                📧 Employee ID: <strong>${empId}</strong><br>
                📧 Email: <strong>${email}</strong>
            </p>
            <div class="otp-display">
                <strong style="font-size: 14px;">🔐 DEMO MODE - Your OTP is:</strong>
                <div style="font-size: 42px; font-weight: bold; letter-spacing: 8px; margin-top: 10px;">${otp}</div>
            </div>
            <input id="swal-otp" type="text" class="swal2-input" placeholder="Enter 6-digit OTP" maxlength="6" autocomplete="off">
            <div class="timer-text" id="live-timer">⏰ Time remaining: ${formatTime(timeLeft)}</div>
            <button class="resend-btn" id="resend-otp-btn" disabled>⏳ Resend OTP (Wait for expiry)</button>
            <p style="margin-top: 10px; font-size: 12px; color: rgba(255,255,255,0.7);">
                ℹ️ OTP expires in 5 minutes. You can request a new OTP after timer reaches 0.
            </p>
        `;
        
        Swal.fire({
            title: 'Enter OTP Code',
            html: htmlContent,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Verify OTP',
            confirmButtonColor: '#4facfe',
            cancelButtonText: 'Cancel',
            customClass: { popup: 'glass-modal', container: 'glass-backdrop' },
            background: 'transparent',
            didOpen: () => {
                const timerElement = document.getElementById('live-timer');
                const resendBtn = document.getElementById('resend-otp-btn');
                const otpInput = document.getElementById('swal-otp');
                
                // Start live countdown
                countdownInterval = setInterval(() => {
                    if (timeLeft > 0) {
                        timeLeft--;
                        if (timerElement) {
                            timerElement.innerHTML = `⏰ Time remaining: ${formatTime(timeLeft)}`;
                            timerElement.style.color = timeLeft <= 60 ? '#ff6666' : '#ffaa00';
                        }
                        
                        // Enable resend button when timer hits 0
                        if (timeLeft === 0 && resendBtn && !canResend) {
                            canResend = true;
                            resendBtn.disabled = false;
                            resendBtn.classList.remove('disabled');
                            resendBtn.innerHTML = '📧 Resend OTP (Click to get new code)';
                            if (timerElement) {
                                timerElement.innerHTML = '⏰ OTP EXPIRED! Click "Resend OTP" below.';
                                timerElement.classList.add('expired');
                            }
                        }
                    } else {
                        // Timer at 0, stop interval
                        if (countdownInterval) clearInterval(countdownInterval);
                    }
                }, 1000);
                
                // Resend button handler
                if (resendBtn) {
                    resendBtn.onclick = async () => {
                        if (!canResend && timeLeft > 0) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'OTP Still Valid',
                                text: `Please wait ${formatTime(timeLeft)} before requesting a new OTP`,
                                customClass: { popup: 'glass-modal', container: 'glass-backdrop' },
                                background: 'transparent',
                                confirmButtonColor: '#4facfe',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            return;
                        }
                        
                        // Request new OTP
                        resendBtn.disabled = true;
                        resendBtn.innerHTML = '⏳ Generating new OTP...';
                        
                        const newOtpResult = await generateOTP();
                        
                        if (newOtpResult && newOtpResult.success) {
                            currentOtp = newOtpResult.test_otp;
                            userEmail = newOtpResult.email;
                            userId = newOtpResult.reset_id;
                            
                            // Update OTP display
                            const otpDisplayDiv = document.querySelector('.otp-display div');
                            if (otpDisplayDiv) otpDisplayDiv.textContent = currentOtp;
                            
                            // Reset timer
                            timeLeft = 300;
                            canResend = false;
                            
                            if (timerElement) {
                                timerElement.innerHTML = `⏰ Time remaining: ${formatTime(timeLeft)}`;
                                timerElement.style.color = '#ffaa00';
                                timerElement.classList.remove('expired');
                            }
                            
                            resendBtn.innerHTML = '⏳ Resend OTP (Wait for expiry)';
                            resendBtn.disabled = true;
                            
                            // Clear existing interval and start new one
                            if (countdownInterval) clearInterval(countdownInterval);
                            
                            // Start new countdown
                            countdownInterval = setInterval(() => {
                                if (timeLeft > 0) {
                                    timeLeft--;
                                    if (timerElement) {
                                        timerElement.innerHTML = `⏰ Time remaining: ${formatTime(timeLeft)}`;
                                        timerElement.style.color = timeLeft <= 60 ? '#ff6666' : '#ffaa00';
                                    }
                                    
                                    if (timeLeft === 0 && resendBtn && !canResend) {
                                        canResend = true;
                                        resendBtn.disabled = false;
                                        resendBtn.classList.remove('disabled');
                                        resendBtn.innerHTML = '📧 Resend OTP (Click to get new code)';
                                        if (timerElement) {
                                            timerElement.innerHTML = '⏰ OTP EXPIRED! Click "Resend OTP" below.';
                                            timerElement.classList.add('expired');
                                        }
                                    }
                                } else {
                                    if (countdownInterval) clearInterval(countdownInterval);
                                }
                            }, 1000);
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'New OTP Generated!',
                                text: 'A new OTP has been created. Valid for 5 minutes.',
                                timer: 2000,
                                showConfirmButton: false,
                                customClass: { popup: 'glass-modal', container: 'glass-backdrop' },
                                background: 'transparent'
                            });
                        } else {
                            resendBtn.disabled = false;
                            resendBtn.innerHTML = '📧 Resend OTP';
                            Swal.fire({
                                icon: 'error',
                                title: 'Failed',
                                text: newOtpResult?.message || 'Could not generate new OTP',
                                customClass: { popup: 'glass-modal', container: 'glass-backdrop' },
                                background: 'transparent',
                                confirmButtonColor: '#4facfe'
                            });
                        }
                    };
                }
                
                // Auto-focus OTP input
                if (otpInput) otpInput.focus();
            },
            preConfirm: () => {
                const otp = document.getElementById('swal-otp').value;
                if (!otp) {
                    Swal.showValidationMessage('Please enter the OTP code');
                    return false;
                }
                if (!/^\d{6}$/.test(otp)) {
                    Swal.showValidationMessage('Please enter a valid 6-digit OTP');
                    return false;
                }
                return otp;
            },
            willClose: () => {
                if (countdownInterval) clearInterval(countdownInterval);
            }
        }).then(async (result) => {
            if (!result.isConfirmed) return;
            
            const otpCode = result.value;
            
            Swal.fire({
                title: 'Verifying OTP...',
                allowOutsideClick: false,
                customClass: { popup: 'glass-modal', container: 'glass-backdrop' },
                background: 'transparent',
                didOpen: () => Swal.showLoading()
            });
            
            const verifyResponse = await fetch('backend/api.php?action=verify_reset_otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    email: userEmail,
                    otp_code: otpCode,
                    user_id: userId
                })
            });
            
            const verifyResult = await verifyResponse.json();
            
            if (!verifyResult.success) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid OTP',
                    text: verifyResult.message,
                    customClass: { popup: 'glass-modal', container: 'glass-backdrop' },
                    background: 'transparent',
                    confirmButtonColor: '#4facfe'
                });
                return;
            }
            
            resetToken = verifyResult.reset_token;
            
            // STEP 3: Set New Password
            const { value: passwordValues } = await Swal.fire({
                title: 'Create New Password',
                html: `
                    <input id="swal-new-password" type="password" class="swal2-input" placeholder="New Password" autocomplete="new-password">
                    <input id="swal-confirm-password" type="password" class="swal2-input" placeholder="Confirm Password" autocomplete="new-password">
                    <p style="margin-top: 10px; font-size: 12px; color: rgba(255,255,255,0.7);">
                        Password must be at least 8 characters with uppercase, lowercase, and numbers
                    </p>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Reset Password',
                confirmButtonColor: '#4facfe',
                cancelButtonText: 'Cancel',
                customClass: { popup: 'glass-modal', container: 'glass-backdrop' },
                background: 'transparent',
                preConfirm: () => {
                    const newPass = document.getElementById('swal-new-password').value;
                    const confirmPass = document.getElementById('swal-confirm-password').value;
                    if (!newPass || !confirmPass) {
                        Swal.showValidationMessage('Please fill in both password fields');
                        return false;
                    }
                    if (newPass !== confirmPass) {
                        Swal.showValidationMessage('Passwords do not match');
                        return false;
                    }
                    if (newPass.length < 8) {
                        Swal.showValidationMessage('Password must be at least 8 characters');
                        return false;
                    }
                    if (!/[A-Z]/.test(newPass)) {
                        Swal.showValidationMessage('Password must contain at least one uppercase letter');
                        return false;
                    }
                    if (!/[a-z]/.test(newPass)) {
                        Swal.showValidationMessage('Password must contain at least one lowercase letter');
                        return false;
                    }
                    if (!/[0-9]/.test(newPass)) {
                        Swal.showValidationMessage('Password must contain at least one number');
                        return false;
                    }
                    return { newPass, confirmPass };
                }
            });
            
            if (!passwordValues) return;
            
            Swal.fire({
                title: 'Resetting Password...',
                allowOutsideClick: false,
                customClass: { popup: 'glass-modal', container: 'glass-backdrop' },
                background: 'transparent',
                didOpen: () => Swal.showLoading()
            });
            
            const resetResponse = await fetch('backend/api.php?action=reset_password_with_token', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    reset_token: resetToken,
                    new_password: passwordValues.newPass,
                    confirm_password: passwordValues.confirmPass
                })
            });
            
            const resetResult = await resetResponse.json();
            
            if (resetResult.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Password Reset Successful!',
                    html: 'You can now login with your new password.<br><br><strong>Redirecting to login...</strong>',
                    timer: 3000,
                    showConfirmButton: false,
                    customClass: { popup: 'glass-modal', container: 'glass-backdrop' },
                    background: 'transparent',
                    willClose: () => {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Reset Failed',
                    text: resetResult.message,
                    customClass: { popup: 'glass-modal', container: 'glass-backdrop' },
                    background: 'transparent',
                    confirmButtonColor: '#4facfe'
                });
            }
        });
    }
    
    // Show OTP dialog with live timer
    showOTPDialogWithTimer(currentOtp, userEmail, userEmployeeId);
});
</script>

<script src="js/context-menu.js?v=1.0"></script>
</body>
</html>