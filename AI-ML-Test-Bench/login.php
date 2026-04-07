<?php
session_start();
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
        if (result.must_change_password) {
            // Store a temporary flag or just handle it in the destination page
            // For now, let's just append a query param or use localStorage
            sessionStorage.setItem('force_password_change', 'true');
        }

        const role = result.role ? result.role.trim() : 'Employee';
        if (role === 'Payroll' || role === 'Payroll Officer') {
            window.location.href = 'Payroll-Officer.php';
        } else if (role === 'Admin' || role === 'HR') {
            window.location.href = 'index.php';
        } else {
            window.location.href = 'ess.php';
        }
    } else {
        alert(result.message);
    }
};
</script>

</body>
</html>
