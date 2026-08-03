<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us - ALM Biometric Attendance</title>

<!-- Font Awesome -->
<link rel="stylesheet" href="css/all.min.css" onerror="this.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'">

<!-- Custom Styles -->
<link rel="stylesheet" href="css/style.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

body {
    min-height: 100vh;
    background: url('assets/bg.jpg') no-repeat center center/cover fixed;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.contact-card {
    width: 100%;
    max-width: 600px;
    padding: 40px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(25px) saturate(180%);
    -webkit-backdrop-filter: blur(25px) saturate(180%);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25), inset 0 1px 1px rgba(255, 255, 255, 0.4);
    color: #fff;
    animation: fadeInUp 0.8s ease-out;
}

.contact-header {
    text-align: center;
    margin-bottom: 30px;
}

.logo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    margin-bottom: 15px;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.contact-header h1 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 20px;
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 15px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

a.info-item {
    text-decoration: none;
    color: inherit;
}

.info-item:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateX(10px);
}

a.info-item:focus-visible {
    background: rgba(255, 255, 255, 0.25);
    border-color: #4facfe;
    box-shadow: 0 0 15px rgba(79, 172, 254, 0.5);
    transform: translateX(10px);
    outline: 2px solid #4facfe;
    outline-offset: 2px;
}

.info-item i {
    font-size: 24px;
    color: #4facfe;
    width: 30px;
    text-align: center;
}

.info-content h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 5px;
    color: rgba(255, 255, 255, 0.8);
}

.info-content p {
    font-size: 18px;
    font-weight: 500;
}

.back-btn {
    display: inline-block;
    padding: 12px 30px;
    background: linear-gradient(135deg, #4facfe, #00f2fe);
    color: #fff;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
    margin-top: 30px;
}

.back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.back-btn:focus-visible {
    outline: 2px solid #fff;
    outline-offset: 3px;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
</head>
<body>

<div class="contact-card">
    <div class="contact-header">
        <img src="assets/logo.jpg" alt="Logo" class="logo">
        <h1>Help & Support</h1>
        <p>We are here to help you</p>
    </div>

    <div class="contact-info">
        <a href="mailto:support@alm.edu" class="info-item" aria-label="Send email to support@alm.edu" title="Send email to support@alm.edu">
            <i class="fas fa-envelope"></i>
            <div class="info-content">
                <h3>Email Support</h3>
                <p>support@alm.edu</p>
            </div>
        </a>

        <a href="tel:0531234567" class="info-item" aria-label="Call phone (053) 123-4567" title="Call phone (053) 123-4567">
            <i class="fas fa-phone-alt"></i>
            <div class="info-content">
                <h3>Phone</h3>
                <p>(053) 123-4567</p>
            </div>
        </a>

        <a href="https://maps.google.com/?q=ACLC+College+Tacloban" target="_blank" rel="noopener noreferrer" class="info-item" aria-label="View ACLC College Tacloban Campus on Google Maps" title="View ACLC College Tacloban Campus on Google Maps">
            <i class="fas fa-map-marker-alt"></i>
            <div class="info-content">
                <h3>Address</h3>
                <p>ACLC College Tacloban Campus</p>
            </div>
        </a>

        <div class="info-item" aria-label="Working Hours: Monday to Friday, 8:00 AM to 5:00 PM">
            <i class="fas fa-clock"></i>
            <div class="info-content">
                <h3>Working Hours</h3>
                <p>Mon - Fri, 8:00 AM - 5:00 PM</p>
            </div>
        </div>
    </div>

    <div style="text-align: center;">
        <a href="index.php" class="back-btn">Back to Home</a>
    </div>
</div>

<script src="js/context-menu.js"></script>
</body>
</html>
