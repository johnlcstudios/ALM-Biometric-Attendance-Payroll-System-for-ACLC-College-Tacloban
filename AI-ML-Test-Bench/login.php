login
<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
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
    font-family: 'Segoe UI', sans-serif;
}

body {
    height: 100vh;
    background: url('assets/bg.jpg') no-repeat center center/cover;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* CARD */
.login-card {
    width: 450px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

/* LOGO */
.logo {
    display: block;
    margin: 0 auto 10px auto;
    width: 100px;
    height: 100px;
    radius: 75%;
}

/* TITLE */
.login-left h2 {
    margin-bottom: 20px;
    color: #333;
    text-align: center;
}

/* INPUT */
.form-group {
    margin-bottom: 20px;
}

.input-wrapper {
    position: relative;
}

.input-wrapper input {
    width: 100%;
    padding: 12px 40px 12px 15px;
    border-radius: 25px;
    border: 1px solid #ccc;
    outline: none;
}

.input-wrapper input:focus {
    border-color: #2400b3;
}

.input-wrapper i {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

/* LOGIN BUTTON */
.login-btn {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 25px;
    background: #4800b3;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    margin-bottom: 10px;
}

.login-btn:hover {
    background: #1633c6;
}

/* KIOSK BUTTON */
.kiosk-btn {
    display: block;
    width: 100%;
    padding: 12px;
    border-radius: 25px;
    background: #ffffff;
    border: 1px solid #190691; 
    color: #031b57;
    font-weight: bold;
    text-align: center;
    text-decoration: none;
    transition: 0.3s;
}

.kiosk-btn:hover {
    background: #4800b3;
    color: #fff;
}

/* SIGNUP */
.signup-text {
    margin: 15px 0;
    font-size: 14px;
    text-align: center;
}

.signup-text a {
    color: #0600b3;
    text-decoration: none;
    font-weight: bold;
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
        if (result.role === 'Employee') {
            window.location.href = 'ess.php';
        } else {
            window.location.href = 'index.php';
        }
    } else {
        alert(result.message);
    }
};
</script>

</body>
</html>
