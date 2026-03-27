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
    <link rel="stylesheet" href="login-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-card">
        <div class="login-left">
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
            </form>
        </div>
        <div class="login-right">
            <div class="welcome-text">
                <h1>WELCOME</h1>
                <h2>BACK!</h2>
                <a href="kiosk.php" class="kiosk-btn">Launch Kiosk</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const response = await fetch('api.php?action=login', {
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
