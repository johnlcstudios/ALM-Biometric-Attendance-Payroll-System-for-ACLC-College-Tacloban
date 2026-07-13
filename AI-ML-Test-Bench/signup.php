<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    $role = $_SESSION['role'] ?? 'Employee';
    if (in_array($role, ['Admin', 'SD', 'School Director'])) {
        header('Location: sd_dashboard.php');
    } elseif (in_array($role, ['Payroll', 'Payroll Officer'])) {
        header('Location: Payroll-Officer.php');
    } elseif ($role === 'HR') {
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
    <title>Sign Up - ALM Biometric Attendance</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Custom Context Menu Styles -->
<link rel="stylesheet" href="css/style.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            height: 100vh;
            background: #f0f2f5 url('assets/bg.jpg') no-repeat center center/cover;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* CARD */
        .login-card {
            width: 450px;
            padding: 40px;

            /* GLASS EFFECT */
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);

            border-radius: 15px;

            /* SOFT BORDER + INNER LIGHT */
            border: 1px solid rgba(255, 255, 255, 0.25);

            /* DEPTH SHADOW */
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.25),
                inset 0 1px 1px rgba(255, 255, 255, 0.36);

        }

        /* LOGO */
        .logo {
            display: block;
            margin: 0 auto 10px auto;
            width: 100px;
            height: 100px;
            border-radius: 75%;
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
            padding: 12px 40px 12px 40px;

            border-radius: 25px;

            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);

            color: #fff;
            outline: none;

            backdrop-filter: blur(10px);

        }

        .input-wrapper input:focus {
            border-color: #2400b3;
        }

        .input-wrapper i {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .input-wrapper i:not(.toggle-password) {
            left: 15px;
        }

        .input-wrapper i.toggle-password {
            right: 15px;
            cursor: pointer;
            z-index: 5;
        }

        /* BUTTON */
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

        /* TEXT */
        .signup-text {
            margin-top: 15px;
            font-size: 14px;
            text-align: center;
        }

        .signup-text a {
            color: #0600b3;
            text-decoration: none;
            font-weight: bold;
        }
        
        /* Glass Morphism Swal2 Styles */
        .swal2-popup.glass-modal {
            background: rgba(255, 255, 255, 0.15) !important;
            backdrop-filter: blur(25px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(25px) saturate(180%) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            border-radius: 20px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25), inset 0 1px 1px rgba(255, 255, 255, 0.4) !important;
        }
        .swal2-popup.glass-modal .swal2-title { color: #ffffff !important; }
        .swal2-popup.glass-modal .swal2-html-container, .swal2-popup.glass-modal .swal2-text { color: rgba(255, 255, 255, 0.9) !important; }
        .swal2-popup.glass-modal .swal2-confirm { background: linear-gradient(135deg, #4facfe, #00f2fe) !important; border-radius: 20px !important; color: #fff !important; }
        .swal2-popup.glass-modal .swal2-cancel { background: rgba(255, 255, 255, 0.15) !important; border: 1px solid rgba(255, 255, 255, 0.3) !important; color: #ffffff !important; border-radius: 20px !important; }
        .swal2-popup.glass-modal .swal2-input { background: rgba(255, 255, 255, 0.2) !important; border: 1px solid rgba(255, 255, 255, 0.3) !important; color: #ffffff !important; border-radius: 20px !important; }
        .swal2-container.glass-backdrop { background: rgba(0, 0, 0, 0.5) !important; backdrop-filter: blur(4px) !important; }
    </style>

</head>

<body>

    <div class="login-card">
        <div class="login-left">

            <!-- LOGO -->
            <img src="assets/logo.jpg" alt="Logo" class="logo">

            <h2>Sign Up</h2>

            <form id="signupForm">
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="text" name="company_name" id="company_name" placeholder="Company Name" required>
                        <i class="fas fa-building"></i>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="text" name="company_code" id="company_code" placeholder="Company Code (auto-generated)" maxlength="20">
                        <i class="fas fa-hashtag"></i>
                    </div>
                    <small style="color: #999; font-size: 12px; display: block; margin-top: 5px;">
                        Leave empty for auto-generation (e.g., ABCD-XY12) or enter custom code
                    </small>
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
                        <input type="password" name="password" id="password" class="password-field" placeholder="Password" required>
                        <i class="fas fa-eye toggle-password" data-target="password"></i>
                        <i class="fas fa-lock"></i>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" class="password-field"
                            placeholder="Confirm Password" required>
                        <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>

                <button type="submit" class="login-btn">Register</button>

                <p class="signup-text">
                    Already have an account? <a href="login.php">Login</a>
                </p>
            </form>
        </div>
    </div>

    <script src="js/password-toggle.js"></script>

    <script>
        initPasswordToggles();
        // Auto-generate company code suggestion
        document.getElementById('company_name').addEventListener('input', function() {
            const companyName = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0,4);
            if (companyName.length >= 2) {
                const randomCode = Math.random().toString(36).substr(2,4).toUpperCase();
                document.getElementById('company_code').placeholder = companyName + '-' + randomCode;
            }
        });

        document.getElementById('signupForm').onsubmit = async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);

            if (data.password !== data.confirm_password) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Passwords do not match!',
                    customClass: {
                        popup: 'glass-modal',
                        container: 'glass-backdrop'
                    },
                    background: 'transparent'
                });
                return;
            }

            const response = await fetch('backend/api.php?action=signup', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    html: `Registration successful!<br><strong>Company Code:</strong> ${result.company_code || 'Auto-generated'}<br><br><strong>Your Role:</strong> School Director<br>You will be redirected to login in <strong>3 seconds</strong>...`,
                    customClass: {
                        popup: 'glass-modal',
                        container: 'glass-backdrop'
                    },
                    background: 'transparent',
                    showConfirmButton: true,
                    confirmButtonText: 'Login Now',
                    confirmButtonColor: '#4facfe',
                    timer: 3000,
                    timerProgressBar: true,
                    allowOutsideClick: false
                }).then((willRedirect) => {
                    // Redirect to login page
                    window.location.href = 'login.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    text: result.message || "Registration failed.",
                    customClass: {
                        popup: 'glass-modal',
                        container: 'glass-backdrop'
                    },
                    background: 'transparent'
                });
            }
        };
    </script>

<script src="js/script.js"></script>
<!-- Custom Context Menu -->
<script src="js/context-menu.js?v=1.0"></script>

</body>

</html>