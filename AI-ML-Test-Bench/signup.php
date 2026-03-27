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
    <title>Sign Up - ALM Biometric Attendance</title>
    <link rel="stylesheet" href="login-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-card">
        <div class="login-left">
            <h2>Sign Up</h2>
            <form id="signupForm">
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="text" name="company_name" placeholder="Company Name" required>
                        <i class="fas fa-building"></i>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="text" name="username" placeholder="Admin Username" required>
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="email" name="email" placeholder="Admin Email" required>
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" placeholder="Password" required>
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <button type="submit" class="login-btn">Register</button>
                <p class="signup-text">
                    Already have an account? <a href="login.php">Login</a>
                </p>
            </form>
        </div>
        <div class="login-right">
            <div class="welcome-text">
                <h1>CREATE</h1>
                <h2>ACCOUNT</h2>
                <p>Join us and streamline your attendance tracking.</p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('signupForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);

            if (data.password !== data.confirm_password) {
                alert("Passwords do not match!");
                return;
            }

            const response = await fetch('api.php?action=signup', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            
            if (result.success) {
                alert("Registration successful! You can now login.");
                window.location.href = 'login.php';
            } else {
                alert(result.message || "Registration failed.");
            }
        };
    </script>
</body>
</html>
