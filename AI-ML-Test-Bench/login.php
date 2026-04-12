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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
</style>

</head>
<body>

<div class="login-card">
    <div class="login-left">

        <!-- LOGO -->
        <img src="assets/logo.jpg" alt="Logo" class="logo">

        <h2>Login</h2>

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

            <p class="signup-text">
                Dont have an account? <a href="signup.php">Sign Up</a>
            </p>

            <div style="text-align: center; margin-top: 15px;">
                <a href="#" id="forgotPasswordLink" style="color: rgba(255,255,255,0.8); font-size: 14px; text-decoration: none;">
                    Forgot Password?
                </a>
            </div>

            <!-- KIOSK BUTTON -->
            <a href="kiosk.php" class="kiosk-btn">Launch Kiosk</a>

        </form>
    </div>
</div>

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
            text: result.message || 'Invalid credentials'
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
            text: result.message
        });
        return;
    }
    
    // Step 3: Ask for new password
    const { value: newPassword } = await Swal.fire({
        title: 'Set New Password',
        html: `
            <p style="margin-bottom: 10px; color: #666;">Verified: <strong>${result.employee_name}</strong></p>
            <p style="margin-bottom: 15px; color: #666; font-size: 0.9em;">Username: <strong>${result.username}</strong></p>
            <input id="swal-new-password" type="password" class="swal2-input" placeholder="New Password (min 6 characters)">
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Reset Password',
        confirmButtonColor: '#4facfe',
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
            confirmButtonColor: '#4facfe'
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Reset Failed',
            text: resetResult.message
        });
    }
});
</script>

</body>
</html>
