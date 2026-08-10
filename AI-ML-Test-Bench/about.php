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

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Outfit:wght@700;900&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="css/all.min.css"
        onerror="this.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'">

    <style>
        :root {
            --primary-blue: #0000FF;
            --deep-black: #0B0B0B;
            --pure-white: #FFFFFF;
            --accent-blue: #4facfe;
            --panel-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--primary-blue);
            color: var(--pure-white);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Ambient spatial background elements */
        .ambient-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .wireframe-grid {
            position: absolute;
            width: 200%;
            height: 200%;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 100px 100px;
            transform: perspective(500px) rotateX(60deg);
            bottom: -50%;
            left: -50%;
            opacity: 0.3;
        }

        main {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1200px;
            padding: 80px 20px;
            display: flex;
            flex-direction: column;
            gap: 120px;
            perspective: 2000px; /* Enable 3D perspective */
        }

        /* Hero Section */
        .hero {
            text-align: center;
            margin-bottom: 40px;
        }

        .hero h1 {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(4rem, 15vw, 10rem);
            line-height: 0.85;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: -0.05em;
            margin-bottom: 20px;
            animation: slideUp 1.2s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        .hero h1::before,
        .hero h1::after {
            content: 'BSIT3A';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.8;
        }

        .hero h1::before {
            color: #ff00ff;
            z-index: -1;
            animation: glitch 1.5s infinite;
        }

        .hero h1::after {
            color: #00ffff;
            z-index: -2;
            animation: glitch 1.5s infinite reverse;
        }

        @keyframes glitch {
            0% { transform: translate(0); }
            20% { transform: translate(-2px, 2px); }
            40% { transform: translate(-2px, -2px); }
            60% { transform: translate(2px, 2px); }
            80% { transform: translate(2px, -2px); }
            100% { transform: translate(0); }
        }

        .hero .subtitle {
            font-size: clamp(1rem, 3vw, 1.5rem);
            text-transform: uppercase;
            letter-spacing: 0.4em;
            opacity: 0.8;
            font-weight: 700;
            animation: fadeIn 2s ease-out;
        }

        /* Billboard Panels */
        .billboard {
            background-color: var(--deep-black);
            border: 1px solid var(--panel-border);
            padding: 60px;
            border-radius: 8px;
            position: relative;
            box-shadow: 0 50px 100px rgba(0, 0, 0, 0.5);
            animation: billboardFloat 1.2s cubic-bezier(0.16, 1, 0.3, 1) both;
            transition: transform 0.1s ease-out, box-shadow 0.3s ease, border-color 0.3s ease;
            transform-style: preserve-3d;
            overflow: hidden; /* For spotlight effect */
        }

        .billboard:hover {
            box-shadow: 0 80px 150px rgba(0, 0, 0, 0.7), 0 0 30px rgba(0, 0, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* Spotlight effect */
        .spotlight {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            background: radial-gradient(circle at var(--x) var(--y), rgba(255,255,255,0.08) 0%, transparent 40%);
            z-index: 5;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .billboard:hover .spotlight {
            opacity: 1;
        }

        .billboard::before {
            content: attr(data-number);
            position: absolute;
            top: -30px;
            left: 20px;
            font-family: 'Outfit', sans-serif;
            font-size: 80px;
            font-weight: 900;
            color: var(--primary-blue);
            -webkit-text-stroke: 1px rgba(255, 255, 255, 0.3);
            text-shadow: none;
            z-index: -1;
        }

        .billboard-header {
            margin-bottom: 40px;
            display: flex;
            align-items: flex-end;
            gap: 20px;
        }

        .billboard-header h2 {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(2rem, 5vw, 3.5rem);
            text-transform: uppercase;
            line-height: 1;
        }

        .billboard-content p {
            font-size: 1.2rem;
            line-height: 1.6;
            opacity: 0.9;
            max-width: 800px;
        }

        /* Developers Styling */
        .team-container {
            margin-top: 60px;
        }

        .main-team-image {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 40px;
            filter: grayscale(100%) contrast(110%) brightness(0.8);
            transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .main-team-image:hover {
            filter: grayscale(20%) brightness(1.1);
            transform: scale(1.02);
            border-color: rgba(0, 0, 255, 0.4);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        .team-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1px;
            background-color: var(--panel-border);
            border: 1px solid var(--panel-border);
            margin-bottom: 40px;
        }

        .team-btn {
            background-color: var(--deep-black);
            border: none;
            color: var(--pure-white);
            padding: 20px;
            cursor: pointer;
            text-transform: uppercase;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.1em;
            transition: all 0.3s ease;
            text-align: left;
        }

        .team-btn:hover {
            background-color: #1a1a1a;
        }

        .team-btn.active {
            background-color: var(--primary-blue);
        }

        .team-detail {
            display: none;
            opacity: 0;
            transform: translateY(20px) scale(0.98);
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .team-detail.active {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        @media (max-width: 768px) {
            .team-detail.active {
                grid-template-columns: 1fr;
            }

            .billboard {
                padding: 30px;
            }
        }

        .team-detail img {
            width: 100%;
            border-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.5s ease;
        }

        .team-detail:hover img {
            transform: translateY(-5px);
            border-color: var(--accent-blue);
        }

        .member-info h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            margin-bottom: 20px;
            color: var(--primary-blue);
            text-transform: uppercase;
        }

        .member-category {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.2em;
            margin-top: 20px;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.5);
        }

        .member-list {
            list-style: none;
        }

        .member-list li {
            padding: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-weight: 500;
        }

        .member-list li a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 8px;
            color: var(--pure-white);
            text-decoration: none;
            transition: all 0.25s ease;
            border-radius: 4px;
            cursor: pointer;
        }

        .member-list li a:hover {
            background: rgba(79, 172, 254, 0.1);
            color: var(--accent-blue);
            padding-left: 16px;
        }

        .member-list li a::after {
            content: '\f061';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 0.7rem;
            margin-left: auto;
            opacity: 0;
            transform: translateX(-5px);
            transition: all 0.25s ease;
            color: var(--accent-blue);
        }

        /* Floating particles */
        .particle {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            animation: particleFloat linear infinite;
        }

        @keyframes particleFloat {
            0% { transform: translateY(110vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-10vh) rotate(720deg); opacity: 0; }
        }

        /* Buttons */
        .back-nav {
            margin-top: 40px;
            text-align: center;
        }

        .btn-premium {
            display: inline-block;
            padding: 20px 40px;
            background-color: var(--pure-white);
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .btn-premium:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
            background-color: var(--primary-blue);
            color: var(--pure-white);
            border: 1px solid var(--pure-white);
        }

        /* Animations */
        @keyframes slideUp {
            from {
                transform: translateY(100px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes billboardFloat {
            from {
                transform: translateY(50px) scale(0.95);
                opacity: 0;
            }

            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        /* Focus styles for keyboard accessibility */
        .team-btn:focus,
        .btn-premium:focus,
        .member-list li a:focus,
        .team-detail:focus {
            outline: none;
        }

        .team-btn:focus-visible {
            outline: 3px solid var(--accent-blue);
            outline-offset: -3px;
            z-index: 10;
        }

        .btn-premium:focus-visible {
            outline: 3px solid var(--pure-white);
            outline-offset: 4px;
        }

        .member-list li a:focus-visible {
            outline: 2px solid var(--accent-blue);
            outline-offset: 2px;
            background: rgba(79, 172, 254, 0.15);
            border-radius: 4px;
        }

        .team-detail:focus-visible {
            outline: 2px dashed rgba(255, 255, 255, 0.3);
            outline-offset: 4px;
        }
    </style>
</head>

<body>

    <div class="ambient-bg">
        <div class="wireframe-grid"></div>
    </div>

    <main>
        <section class="hero">
            <div class="subtitle">ALM BIOMETRIC</div>
            <h1>BSIT3A</h1>
            <div class="subtitle">ATTENDANCE & PAYROLL SYSTEM</div>
        </section>

        <!-- System Description -->
        <section class="billboard" data-number="01">
            <div class="spotlight"></div>
            <div class="billboard-header">
                <h2>THE SYSTEM</h2>
            </div>
            <div class="billboard-content">
                <p>
                    The <strong>ALM Biometric Attendance & Payroll System</strong> is a high-fidelity integrated
                    solution engineered for ACLC College Tacloban.
                    It utilizes advanced facial recognition algorithms to provide a seamless, agentic attendance
                    experience.
                </p>
                <p style="margin-top: 20px;">
                    Beyond simple tracking, its core engine automates institutional management—calculating payroll,
                    managing deductions,
                    and processing faculty loads with industrial-grade precision.
                </p>
            </div>
        </section>

        <!-- Developers Section -->
        <section class="billboard" data-number="02">
            <div class="spotlight"></div>
            <div class="billboard-header">
                <h2>DEVELOPERS</h2>
            </div>
            <div class="billboard-content">
                <p>Developed with obsessive attention to detail by the students of <strong>BSIT 3A</strong>, Batch 2027.
                </p>

                <div class="team-container">
                    <img src="assets/BSIT3A.JPG" alt="BSIT 3A Team" class="main-team-image">

                    <div class="team-controls" role="tablist" aria-label="Developer Teams">
                        <button class="team-btn active" id="tab-team-leads" role="tab" aria-selected="true" aria-controls="team-leads" onclick="toggleTeam('team-leads', this)">01. Leads</button>
                        <button class="team-btn" id="tab-admin-team" role="tab" aria-selected="false" aria-controls="admin-team" onclick="toggleTeam('admin-team', this)">02. Admin</button>
                        <button class="team-btn" id="tab-frontend-team" role="tab" aria-selected="false" aria-controls="frontend-team" onclick="toggleTeam('frontend-team', this)">03. Front</button>
                        <button class="team-btn" id="tab-backend-team" role="tab" aria-selected="false" aria-controls="backend-team" onclick="toggleTeam('backend-team', this)">04. Back</button>
                        <button class="team-btn" id="tab-biometrics-team" role="tab" aria-selected="false" aria-controls="biometrics-team" onclick="toggleTeam('biometrics-team', this)">05. Bio</button>
                        <button class="team-btn" id="tab-testing-team" role="tab" aria-selected="false" aria-controls="testing-team" onclick="toggleTeam('testing-team', this)">06. QA</button>
                    </div>

                    <div id="team-details">
                        <!-- Team Leads -->
                        <div id="team-leads" class="team-detail active" role="tabpanel" aria-labelledby="tab-team-leads" tabindex="0">
                            <img src="assets/Team Leads.JPG" alt="Team Leads">
                            <div class="member-info">
                                <h3>Team Leads</h3>
                                <div class="member-category">Chief Architect</div>
                                <ul class="member-list">
                                    <li><a href="developer.php?id=cabajaan_john_laurence" style="color: #fbbf24; font-weight: 700;">Cabaja-an, John Laurence M.</a></li>
                                </ul>
                                <div class="member-category" style="margin-top: 15px;">CA's in Charge</div>
                                <ul class="member-list">
                                    <li><a href="developer.php?id=artoza_draizen_john">Artoza, Draizen John
                                            (Administrative)</a></li>
                                    <li><a href="developer.php?id=robis_brent_kristian">Robis, Brent Kristian
                                            (Frontend)</a></li>
                                    <li><a href="developer.php?id=guillena_jasmine">Guillena, Jasmine (Backend)</a></li>
                                    <li><a href="developer.php?id=marcellano_jane">Marcellano, Jane (Biometrics)</a>
                                    </li>
                                    <li><a href="developer.php?id=santos_vannah_maie">Santos, Vannah Maie
                                            (Biometrics)</a></li>
                                    <li><a href="developer.php?id=lacambra_alfonse">Lacambra, Alfonse (Testing)</a></li>
                                </ul>
                            </div>
                        </div>

                        <!-- Admin Team -->
                        <div id="admin-team" class="team-detail" role="tabpanel" aria-labelledby="tab-admin-team" tabindex="0">
                            <img src="assets/Administrative.JPG" alt="Administrative Team">
                            <div class="member-info">
                                <h3>Admin & Accounting</h3>
                                <div class="member-category">CA in Charge</div>
                                <ul class="member-list">
                                    <li><a href="developer.php?id=artoza_draizen_john">Artoza, Draizen John</a></li>
                                </ul>
                                <div class="member-category">Architects</div>
                                <ul class="member-list">
                                    <li><a href="developer.php?id=aureo_krystel_mae">Aureo, Krystel Mae</a></li>
                                    <li><a href="developer.php?id=veloso_ella_patrisha">Veloso, Ella Patrisha</a></li>
                                </ul>
                            </div>
                        </div>

                        <!-- Frontend Team -->
                        <div id="frontend-team" class="team-detail" role="tabpanel" aria-labelledby="tab-frontend-team" tabindex="0">
                            <img src="assets/Frontend.JPG" alt="Frontend Team">
                            <div class="member-info">
                                <h3>Frontend Dev</h3>
                                <div class="member-category">CA in Charge</div>
                                <ul class="member-list">
                                    <li><a href="developer.php?id=robis_brent_kristian">Robis, Brent Kristian</a></li>
                                </ul>
                                <div class="member-category">Engineers</div>
                                <ul class="member-list">
                                    <li><a href="developer.php?id=canonce_beverly">Canonce, Beverly</a></li>
                                    <li><a href="developer.php?id=bactol_ferdinand">Bactol, Ferdinand</a></li>
                                    <li><a href="developer.php?id=moreno_rexxaire_justin">Moreno, Rexxaire Justin</a>
                                    </li>
                                    <li><a href="developer.php?id=rafales_caren">Rafales, Caren</a></li>
                                    <li><a href="developer.php?id=calvo_cherose_angela">Calvo, Cherose Angela</a></li>
                                    <li><a href="developer.php?id=caalim_arianne_mae">Caalim, Arianne Mae</a></li>
                                    <li><a href="developer.php?id=aucilla_criselda_vhie">Aucilla, Criselda Vhie</a></li>
                                </ul>
                            </div>
                        </div>

                        <!-- Backend Team -->
                        <div id="backend-team" class="team-detail" role="tabpanel" aria-labelledby="tab-backend-team" tabindex="0">
                            <img src="assets/Backend.JPG" alt="Backend Team">
                            <div class="member-info">
                                <h3>Backend Dev</h3>
                                <div class="member-category">CA in Charge</div>
                                <ul class="member-list">
                                    <li><a href="developer.php?id=guillena_jasmine">Guillena, Jasmine</a></li>
                                </ul>
                                <div class="member-category">Engineers</div>
                                <ul class="member-list">
                                    <li><a href="developer.php?id=comandao_shiela_mae">Comandao, Shiela Mae</a></li>
                                    <li><a href="developer.php?id=magsanay_christian">Magsanay, Christian</a></li>
                                    <li><a href="developer.php?id=gunda_philip_justine">Gunda, Philip Justine</a></li>
                                    <li><a href="developer.php?id=abuda_christian_kerr">Abuda, Christian Kerr</a></li>
                                    <li><a href="developer.php?id=alkuino_michael_jose">Alkuino, Michael Jose</a></li>
                                </ul>
                            </div>
                        </div>

                        <!-- Biometrics Team -->
                        <div id="biometrics-team" class="team-detail" role="tabpanel" aria-labelledby="tab-biometrics-team" tabindex="0">
                            <img src="assets/Biometrics.JPG" alt="Biometrics Team">
                            <div class="member-info">
                                <h3>Biometrics</h3>
                                <div class="member-category">CAs in Charge</div>
                                <ul class="member-list">
                                    <li><a href="developer.php?id=marcellano_jane">Marcellano, Jane</a></li>
                                    <li><a href="developer.php?id=santos_vannah_maie">Santos, Vannah Maie</a></li>
                                </ul>
                                <div class="member-category">Engineers</div>
                                <ul class="member-list">
                                    <li><a href="developer.php?id=besa_lourence">Besa, Lourence</a></li>
                                    <li><a href="developer.php?id=lorica_khen_mariel">Lorica, Khen Mariel</a></li>
                                    <li><a href="developer.php?id=padel_ruffa_mae">Padel, Ruffa Mae</a></li>
                                    <li><a href="developer.php?id=samar_angelyn">Samar, Angelyn</a></li>
                                    <li><a href="developer.php?id=de_veyra_lica_yzabelle">De Veyra, Lica Yzabelle</a>
                                    </li>
                                    <li><a href="developer.php?id=ursabia_april">Ursabia, April</a></li>
                                </ul>
                            </div>
                        </div>

                        <!-- Testing Team -->
                        <div id="testing-team" class="team-detail" role="tabpanel" aria-labelledby="tab-testing-team" tabindex="0">
                            <img src="assets/TESTING.JPG" alt="Testing Team">
                            <div class="member-info">
                                <h3>QA & Testing</h3>
                                <div class="member-category">CA in Charge</div>
                                <ul class="member-list">
                                    <li><a href="developer.php?id=lacambra_alfonse">Lacambra, Alfonse</a></li>
                                </ul>
                                <div class="member-category">Observers</div>
                                <ul class="member-list">
                                    <li><a href="developer.php?id=galo_hendrix">Galo, Hendrix</a></li>
                                    <li><a href="developer.php?id=tanpiengco_ciaerwin">Tanpiengco, Ciaerwin</a></li>
                                    <li><a href="developer.php?id=villero_michael_george">Villero, Michael George</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="back-nav">
            <a href="index.php" class="btn-premium">Return To Dashboard</a>
        </section>
    </main>

    <script>
        function toggleTeam(teamId, btn) {
            const details = document.querySelectorAll('.team-detail');
            const buttons = document.querySelectorAll('.team-btn');

            // Toggle buttons & ARIA states
            buttons.forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');

            // Find current active
            const currentActive = document.querySelector('.team-detail.active');
            
            if (currentActive) {
                currentActive.style.opacity = '0';
                currentActive.style.transform = 'translateY(-10px) scale(0.98)';
                setTimeout(() => {
                    currentActive.classList.remove('active');
                    currentActive.style.display = 'none';
                    showTarget();
                }, 300);
            } else {
                showTarget();
            }

            function showTarget() {
                const target = document.getElementById(teamId);
                target.style.display = 'grid';
                // Trigger reflow
                target.offsetHeight;
                target.classList.add('active');
                target.style.opacity = '1';
                target.style.transform = 'translateY(0) scale(1)';
            }
        }

        // Keyboard Arrow-Key Navigation for Tabs
        document.addEventListener('DOMContentLoaded', () => {
            const tabList = document.querySelector('.team-controls');
            if (tabList) {
                const tabs = tabList.querySelectorAll('.team-btn');
                tabList.addEventListener('keydown', (e) => {
                    let index = Array.from(tabs).indexOf(document.activeElement);
                    if (index === -1) return;

                    let nextIndex = index;
                    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                        nextIndex = (index + 1) % tabs.length;
                    } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                        nextIndex = (index - 1 + tabs.length) % tabs.length;
                    } else if (e.key === 'Home') {
                        nextIndex = 0;
                    } else if (e.key === 'End') {
                        nextIndex = tabs.length - 1;
                    } else {
                        return; // Let other keys propagate
                    }

                    e.preventDefault();
                    tabs[nextIndex].focus();
                    tabs[nextIndex].click(); // Activate the tab on focus
                });
            }
        });

        // Particle generation
        (function() {
            const colors = ['#0000FF', '#ffffff', '#4facfe'];
            for (let i = 0; i < 15; i++) {
                const p = document.createElement('div');
                p.className = 'particle';
                const size = Math.random() * 4 + 2;
                p.style.width = size + 'px';
                p.style.height = size + 'px';
                p.style.left = Math.random() * 100 + '%';
                p.style.background = colors[Math.floor(Math.random() * colors.length)];
                p.style.opacity = Math.random() * 0.3 + 0.1;
                p.style.animationDuration = (Math.random() * 20 + 10) + 's';
                p.style.animationDelay = (Math.random() * 10) + 's';
                document.body.appendChild(p);
            }
        })();

        // Interactive mouse movement
        document.addEventListener('mousemove', (e) => {
            const { clientX, clientY } = e;
            const centerX = window.innerWidth / 2;
            const centerY = window.innerHeight / 2;
            
            // Grid movement
            const grid = document.querySelector('.wireframe-grid');
            if (grid) {
                const moveX = (clientX - centerX) / 60;
                const moveY = (clientY - centerY) / 60;
                grid.style.transform = `perspective(500px) rotateX(60deg) translate(${moveX}px, ${moveY}px)`;
            }

            // Billboard 3D Tilt & Spotlight
            const billboards = document.querySelectorAll('.billboard');
            billboards.forEach(card => {
                const rect = card.getBoundingClientRect();
                const x = clientX - rect.left;
                const y = clientY - rect.top;
                
                // Update Spotlight Vars
                card.style.setProperty('--x', `${x}px`);
                card.style.setProperty('--y', `${y}px`);

                // 3D Tilt
                const rotateX = (clientY - (rect.top + rect.height/2)) / -40;
                const rotateY = (clientX - (rect.left + rect.width/2)) / 40;
                card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            });
        });

        // Reset tilt on leave
        document.querySelectorAll('.billboard').forEach(card => {
            card.addEventListener('mouseleave', () => {
                card.style.transform = `rotateY(0deg) rotateX(0deg)`;
            });
        });
    </script>
    <script src="js/context-menu.js"></script>
</body>

</html>