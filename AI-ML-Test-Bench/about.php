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
<title>About - ALM Biometric Attendance</title>

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

.about-card {
    width: 100%;
    max-width: 800px;
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

.about-header {
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

.about-header h1 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 10px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.section {
    margin-bottom: 30px;
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 15px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.section h2 {
    font-size: 20px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #4facfe;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.section p {
    line-height: 1.6;
    font-size: 16px;
    opacity: 0.9;
}

.developer-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.dev-item {
    background: rgba(255, 255, 255, 0.05);
    padding: 10px 15px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    gap: 10px;
}

.dev-item i {
    color: #00f2fe;
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
    margin-top: 20px;
}

.back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.main-team-image {
    width: 100%;
    margin-bottom: 25px;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.team-img {
    width: 100%;
    display: block;
    transition: transform 0.5s ease;
}

.main-img:hover {
    transform: scale(1.02);
}

.team-toggles {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 25px;
    justify-content: center;
}

.team-btn {
    padding: 10px 18px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #fff;
    border-radius: 20px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(5px);
}

.team-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.team-btn.active {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
    border-color: transparent;
    box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
}

.team-section {
    display: none;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 15px;
    padding: 20px;
    margin-top: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    animation: fadeIn 0.5s ease-out;
}

.team-section.active {
    display: block;
}

.team-section h3 {
    font-size: 20px;
    margin-bottom: 15px;
    color: #00f2fe;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 10px;
}

.group-img {
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.member-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.member-category {
    font-weight: 700;
    font-size: 15px;
    color: rgba(255, 255, 255, 0.7);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.member-list ul {
    list-style: none;
    padding-left: 5px;
}

.member-list li {
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.05);
    margin-bottom: 5px;
    border-radius: 8px;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.member-list li::before {
    content: '\f105';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    color: #4facfe;
    font-size: 12px;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
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

<div class="about-card">
    <div class="about-header">
        <img src="assets/logo.jpg" alt="Logo" class="logo">
        <h1>About System</h1>
    </div>

    <div class="section">
        <h2><i class="fas fa-info-circle"></i> System Description</h2>
        <p>
            The <strong>ALM Biometric Attendance & Payroll System</strong> is an advanced, integrated solution designed specifically for ACLC College Tacloban. 
            It leverages cutting-edge facial recognition technology to provide secure, contactless attendance tracking for both faculty and staff.
        </p>
        <p style="margin-top: 10px;">
            Beyond attendance, the system automates complex payroll processes, including salary calculations, tax deductions, allowances, and loans, 
            ensuring accuracy and efficiency in institutional management.
        </p>
    </div>

    <div class="section">
        <h2><i class="fas fa-code"></i> Developers Section</h2>
        <p style="margin-bottom: 20px;">Developed with passion and dedication by the students of <strong>BSIT 3A</strong>, Batch 2027.</p>
        
        <div class="main-team-image">
            <img src="assets/BSIT3A.JPG" alt="BSIT 3A Team" class="team-img main-img">
        </div>

        <div class="team-toggles">
            <button class="team-btn" onclick="toggleTeam('team-leads')">Team Leads</button>
            <button class="team-btn" onclick="toggleTeam('admin-team')">Administrative</button>
            <button class="team-btn" onclick="toggleTeam('frontend-team')">Frontend</button>
            <button class="team-btn" onclick="toggleTeam('backend-team')">Backend</button>
            <button class="team-btn" onclick="toggleTeam('biometrics-team')">Biometrics</button>
            <button class="team-btn" onclick="toggleTeam('testing-team')">Testing</button>
        </div>

        <div id="team-details-container">
            <!-- Team Leads -->
            <div id="team-leads" class="team-section">
                <h3><i class="fas fa-crown"></i> Team Leads</h3>
                <img src="assets/Team Leads.JPG" alt="Team Leads" class="team-img group-img">
                <div class="member-list">
                    <div class="member-category">CAs in Charge:</div>
                    <ul>
                        <li>Artoza, Draizen John (Administrative)</li>
                        <li>Robis, Brent Kristian (Frontend)</li>
                        <li>Guillena, Jasmine (Backend)</li>
                        <li>Marcellano, Jane (Biometrics)</li>
                        <li>Santos, Vannah Maie (Biometrics)</li>
                        <li>Lacambra, Alfonse (Testing)</li>
                    </ul>
                </div>
            </div>

            <!-- Admin Team -->
            <div id="admin-team" class="team-section">
                <h3><i class="fas fa-file-invoice-dollar"></i> Administrative & Accounting Team</h3>
                <img src="assets/Administrative.JPG" alt="Administrative Team" class="team-img group-img">
                <div class="member-list">
                    <div class="member-category">CA in Charge:</div>
                    <ul><li>Artoza, Draizen John</li></ul>
                    <div class="member-category">Team Members:</div>
                    <ul>
                        <li>Aureo, Krystel Mae</li>
                        <li>Veloso, Ella Patrisha</li>
                    </ul>
                </div>
            </div>

            <!-- Frontend Team -->
            <div id="frontend-team" class="team-section">
                <h3><i class="fas fa-paint-brush"></i> Front End Development Team</h3>
                <img src="assets/Frontend.JPG" alt="Frontend Team" class="team-img group-img">
                <div class="member-list">
                    <div class="member-category">CA in Charge:</div>
                    <ul><li>Robis, Brent Kristian</li></ul>
                    <div class="member-category">Team Members:</div>
                    <ul>
                        <li>Canonce, Beverly</li>
                        <li>Bactol, Ferdinand</li>
                        <li>Rafales, Caren</li>
                        <li>Calvo, Cherose Angela</li>
                        <li>Galo, Hendrix</li>
                        <li>Caalim, Arianne Mae</li>
                        <li>Aucilla, Criselda Vhie</li>
                    </ul>
                </div>
            </div>

            <!-- Backend Team -->
            <div id="backend-team" class="team-section">
                <h3><i class="fas fa-server"></i> Back End Development Team</h3>
                <img src="assets/Backend.JPG" alt="Backend Team" class="team-img group-img">
                <div class="member-list">
                    <div class="member-category">CA in Charge:</div>
                    <ul><li>Guillena, Jasmine</li></ul>
                    <div class="member-category">Team Members:</div>
                    <ul>
                        <li>Comandao, Shiela Mae</li>
                        <li>Magsanay, Christian</li>
                        <li>Gunda, Philip Justine</li>
                        <li>Abuda, Christian Kerr</li>
                        <li>Moreno, Rexxaire Justin</li>
                        <li>Alkuino, Michael Jose</li>
                    </ul>
                </div>
            </div>

            <!-- Biometrics Team -->
            <div id="biometrics-team" class="team-section">
                <h3><i class="fas fa-fingerprint"></i> Biometrics Integration Team</h3>
                <img src="assets/Biometrics.JPG" alt="Biometrics Team" class="team-img group-img">
                <div class="member-list">
                    <div class="member-category">CA in Charge:</div>
                    <ul>
                        <li>Marcellano, Jane</li>
                        <li>Santos, Vannah Maie</li>
                    </ul>
                    <div class="member-category">Team Members:</div>
                    <ul>
                        <li>Besa, Lourence</li>
                        <li>Lorica, Khen Mariel</li>
                        <li>Padel, Ruffa Mae</li>
                        <li>Samar, Angelyn</li>
                        <li>De Veyra, Lica Yzabelle</li>
                    </ul>
                </div>
            </div>

            <!-- Testing Team -->
            <div id="testing-team" class="team-section">
                <h3><i class="fas fa-vial"></i> Testing / QA Team</h3>
                <img src="assets/TESTING.JPG" alt="Testing Team" class="team-img group-img">
                <div class="member-list">
                    <div class="member-category">CA in Charge:</div>
                    <ul><li>Lacambra, Alfonse</li></ul>
                    <div class="member-category">Team Members:</div>
                    <ul>
                        <li>Ursabia, April</li>
                        <li>Tanpiengco, Ciaerwin</li>
                        <li>Villero, Michael George</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: center;">
        <a href="index.php" class="back-btn">Back to Dashboard</a>
    </div>
</div>

<script>
function toggleTeam(teamId) {
    const sections = document.querySelectorAll('.team-section');
    const buttons = document.querySelectorAll('.team-btn');
    const targetSection = document.getElementById(teamId);
    
    const isCurrentlyVisible = targetSection.classList.contains('active');
    
    // Hide all first
    sections.forEach(s => s.classList.remove('active'));
    buttons.forEach(b => b.classList.remove('active'));
    
    if (!isCurrentlyVisible) {
        targetSection.classList.add('active');
        // Find the button that was clicked and add active class
        event.currentTarget.classList.add('active');
        
        // Scroll to the active section smoothly
        targetSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}
</script>

<script src="js/context-menu.js"></script>
</body>
</html>
