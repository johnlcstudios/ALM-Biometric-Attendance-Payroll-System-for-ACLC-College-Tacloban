<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Developer Data Registry ───
$developers = [
    'cabajaan_john_laurence' => [
        'name' => 'Cabaja-an, John Laurence M.',
        'team' => 'Leadership',
        'role' => 'Chief Architect',
    ],
    'artoza_draizen_john' => [
        'name' => 'Artoza, Draizen John B.',
        'team' => 'Administrative',
        'role' => 'CA in Charge',
    ],
    'robis_brent_kristian' => [
        'name' => 'Robis, Brent Kristian',
        'team' => 'Frontend',
        'role' => 'CA in Charge',
    ],
    'guillena_jasmine' => [
        'name' => 'Guillena, Jasmine L.',
        'team' => 'Backend',
        'role' => 'CA in Charge',
    ],
    'marcellano_jane' => [
        'name' => 'Marcellano, Jane C.',
        'team' => 'Biometrics',
        'role' => 'CA in Charge',
    ],
    'santos_vannah_maie' => [
        'name' => 'Santos, Vannah Maie R.',
        'team' => 'Biometrics',
        'role' => 'CA in Charge',
    ],
    'lacambra_alfonse' => [
        'name' => 'Lacambra, Alfonse C.',
        'team' => 'Testing',
        'role' => 'CA in Charge',
    ],
    'aureo_krystel_mae' => [
        'name' => 'Aureo, Krystel Mae B.',
        'team' => 'Administrative',
        'role' => 'Architect',
    ],
    'veloso_ella_patrisha' => [
        'name' => 'Veloso, Ella Patrisha',
        'team' => 'Administrative',
        'role' => 'Architect',
    ],
    'canonce_beverly' => [
        'name' => 'Canonce, Beverly',
        'team' => 'Frontend',
        'role' => 'Engineer',
    ],
    'bactol_ferdinand' => [
        'name' => 'Bactol, Ferdinand F.',
        'team' => 'Frontend',
        'role' => 'Engineer',
    ],
    'moreno_rexxaire_justin' => [
        'name' => 'Moreno, Rexxaire Justin',
        'team' => 'Frontend',
        'role' => 'Engineer',
    ],
    'rafales_caren' => [
        'name' => 'Rafales, Caren A.',
        'team' => 'Frontend',
        'role' => 'Engineer',
    ],
    'calvo_cherose_angela' => [
        'name' => 'Calvo, Cherose Angela C.',
        'team' => 'Frontend',
        'role' => 'Engineer',
    ],
    'caalim_arianne_mae' => [
        'name' => 'Caalim, Arianne Mae',
        'team' => 'Frontend',
        'role' => 'Engineer',
    ],
    'aucilla_criselda_vhie' => [
        'name' => 'Aucilla, Criselda Vhie O.',
        'team' => 'Frontend',
        'role' => 'Engineer',
    ],
    'comandao_shiela_mae' => [
        'name' => 'Comandao, Shiela Mae B.',
        'team' => 'Backend',
        'role' => 'Engineer',
    ],
    'magsanay_christian' => [
        'name' => 'Magsanay, Christian A.',
        'team' => 'Backend',
        'role' => 'Engineer',
    ],
    'gunda_philip_justine' => [
        'name' => 'Gunda, Philip Justine',
        'team' => 'Backend',
        'role' => 'Engineer',
    ],
    'abuda_christian_kerr' => [
        'name' => 'Abuda, Christian Kerr',
        'team' => 'Backend',
        'role' => 'Engineer',
    ],
    'alkuino_michael_jose' => [
        'name' => 'Alkuino, Michael Jose P.',
        'team' => 'Backend',
        'role' => 'Engineer',
    ],
    'besa_lourence' => [
        'name' => 'Besa, Lourence',
        'team' => 'Biometrics',
        'role' => 'Engineer',
    ],
    'lorica_khen_mariel' => [
        'name' => 'Lorica, Khen Mariel M.',
        'team' => 'Biometrics',
        'role' => 'Engineer',
    ],
    'padel_ruffa_mae' => [
        'name' => 'Padel, Ruffa Mae M.',
        'team' => 'Biometrics',
        'role' => 'Engineer',
    ],
    'samar_angelyn' => [
        'name' => 'Samar, Angelyn C.',
        'team' => 'Biometrics',
        'role' => 'Engineer',
    ],
    'de_veyra_lica_yzabelle' => [
        'name' => 'De Veyra, Lica Yzabelle J.',
        'team' => 'Biometrics',
        'role' => 'Engineer',
    ],
    'ursabia_april' => [
        'name' => 'Ursabia, April',
        'team' => 'Biometrics',
        'role' => 'Engineer',
    ],
    'galo_hendrix' => [
        'name' => 'Galo, Hendrix E.',
        'team' => 'Testing',
        'role' => 'Observer',
    ],
    'tanpiengco_ciaerwin' => [
        'name' => 'Tanpiengco, Ciaerwin',
        'team' => 'Testing',
        'role' => 'Observer',
    ],
    'villero_michael_george' => [
        'name' => 'Villero, Michael George',
        'team' => 'Testing',
        'role' => 'Observer',
    ],
];


// ─── Resolve Developer ───
$id = isset($_GET['id']) ? $_GET['id'] : '';
$dev = isset($developers[$id]) ? $developers[$id] : null;

if (!$dev) {
    header('Location: about.php');
    exit;
}

// ─── Image Resolution Logic ───
$devImage = '';
$hasImage = false;
$extensions = ['jpg', 'png', 'jpeg', 'webp'];
foreach ($extensions as $ext) {
    $path = "assets/developers/{$id}.{$ext}";
    if (file_exists($path)) {
        $devImage = $path;
        $hasImage = true;
        break;
    }
}

// Team color mapping
$teamColors = [
    'Leadership' => ['hue' => '280', 'color' => '#fbbf24', 'bg' => 'rgba(251, 191, 36, 0.15)'],
    'Administrative' => ['hue' => '270', 'color' => '#a855f7', 'bg' => 'rgba(168, 85, 247, 0.15)'],
    'Frontend' => ['hue' => '200', 'color' => '#38bdf8', 'bg' => 'rgba(56, 189, 248, 0.15)'],
    'Backend' => ['hue' => '150', 'color' => '#34d399', 'bg' => 'rgba(52, 211, 153, 0.15)'],
    'Biometrics' => ['hue' => '30', 'color' => '#fb923c', 'bg' => 'rgba(251, 146, 60, 0.15)'],
    'Testing' => ['hue' => '350', 'color' => '#f87171', 'bg' => 'rgba(248, 113, 113, 0.15)'],
];
$tc = isset($teamColors[$dev['team']]) ? $teamColors[$dev['team']] : ['hue' => '220', 'color' => '#4facfe', 'bg' => 'rgba(79, 172, 254, 0.15)'];

// Team icon mapping
$teamIcons = [
    'Leadership' => 'fa-crown',
    'Administrative' => 'fa-shield-halved',
    'Frontend' => 'fa-desktop',
    'Backend' => 'fa-server',
    'Biometrics' => 'fa-fingerprint',
    'Testing' => 'fa-flask-vial',
];
$icon = isset($teamIcons[$dev['team']]) ? $teamIcons[$dev['team']] : 'fa-code';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($dev['name']); ?> - ALM Developer Profile</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@700;900&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="css/all.min.css"
        onerror="this.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'">

    <style>
        :root {
            --primary-blue: #0000FF;
            --deep-black: #0B0B0B;
            --pure-white: #FFFFFF;
            --team-color:
                <?php echo $tc['color']; ?>
            ;
            --team-bg:
                <?php echo $tc['bg']; ?>
            ;
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
            justify-content: center;
        }

        /* Ambient spatial background */
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

        /* Floating particles */
        .particle {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
            animation: particleFloat linear infinite;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            90% {
                opacity: 1;
            }

            100% {
                transform: translateY(-10vh) rotate(720deg);
                opacity: 0;
            }
        }

        /* Main Container */
        .profile-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 700px;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0;
        }

        /* Back button */
        .back-btn {
            align-self: flex-start;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: 40px;
            transition: all 0.3s ease;
            padding: 10px 0;
        }

        .back-btn:hover {
            color: var(--pure-white);
            transform: translateX(-5px);
        }

        .back-btn:focus-visible {
            outline: 2px solid var(--pure-white);
            outline-offset: 4px;
            border-radius: 4px;
        }

        .back-btn i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .back-btn:hover i {
            transform: translateX(-4px);
        }

        /* Profile Card */
        .profile-card {
            background-color: var(--deep-black);
            border: 1px solid var(--panel-border);
            border-radius: 8px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 60px 120px rgba(0, 0, 0, 0.5);
            animation: cardReveal 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes cardReveal {
            from {
                transform: translateY(60px) scale(0.95);
                opacity: 0;
            }

            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        /* Card Hero Banner */
        .card-banner {
            position: relative;
            height: 180px;
            background: linear-gradient(135deg,
                    <?php echo $tc['color']; ?>
                    22 0%,
                    var(--primary-blue) 50%,
                    <?php echo $tc['color']; ?>
                    44 100%);
            overflow: hidden;
        }

        .card-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            transform: rotate(15deg);
            animation: gridScroll 20s linear infinite;
        }

        @keyframes gridScroll {
            0% {
                transform: rotate(15deg) translate(0, 0);
            }

            100% {
                transform: rotate(15deg) translate(40px, 40px);
            }
        }

        .card-banner::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: linear-gradient(transparent, var(--deep-black));
        }

        /* Team badge on banner */
        .team-badge-banner {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 8px 16px;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color:
                <?php echo $tc['color']; ?>
            ;
            z-index: 2;
        }

        /* Sub-leadership Badge */
        .leadership-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: linear-gradient(135deg, rgba(226, 232, 240, 0.2), rgba(148, 163, 184, 0.2));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 8px 16px;
            border-radius: 100px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #f1f5f9;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            border-left: 3px solid #cbd5e1;
        }

        /* Profile image area */
        .profile-image-wrapper {
            display: flex;
            justify-content: center;
            margin-top: -80px;
            position: relative;
            z-index: 2;
        }

        .profile-image-ring {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            padding: 4px;
            background: linear-gradient(135deg,
                    <?php echo $tc['color']; ?>
                    , var(--primary-blue),
                    <?php echo $tc['color']; ?>
                );
            animation: ringRotate 6s linear infinite;
            background-size: 200% 200%;
        }

        @keyframes ringRotate {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--deep-black);
            display: block;
        }

        .profile-placeholder {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 4px solid var(--deep-black);
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-placeholder i {
            font-size: 3.5rem;
            color: rgba(255, 255, 255, 0.2);
        }

        /* Profile Content */
        .profile-content {
            padding: 30px 40px 50px;
            text-align: center;
        }

        .dev-name {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(1.8rem, 5vw, 2.6rem);
            font-weight: 900;
            text-transform: uppercase;
            line-height: 1.1;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
            animation: nameSlide 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.3s both;
        }

        @keyframes nameSlide {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Info chips */
        .info-chips {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
            animation: chipsReveal 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.5s both;
        }

        @keyframes chipsReveal {
            from {
                transform: translateY(15px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        .chip-team {
            background: var(--team-bg);
            color: var(--team-color);
            border: 1px solid
                <?php echo $tc['color']; ?>
                33;
        }

        .chip-role {
            background: rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Decorative divider */
        .divider {
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--team-color), transparent);
            margin: 0 auto 32px;
            border-radius: 2px;
            animation: dividerExpand 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.7s both;
        }

        @keyframes dividerExpand {
            from {
                width: 0;
                opacity: 0;
            }

            to {
                width: 60px;
                opacity: 1;
            }
        }

        /* Project info */
        .project-info {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            padding: 24px;
            animation: infoFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.9s both;
        }

        @keyframes infoFade {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .project-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.25em;
            color: rgba(255, 255, 255, 0.4);
            margin-bottom: 8px;
        }

        .project-name {
            font-family: 'Outfit', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.85);
        }

        .project-sub {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.45);
            margin-top: 4px;
        }

        /* Responsive */
        @media (max-width: 500px) {
            .profile-content {
                padding: 24px 20px 40px;
            }

            .card-banner {
                height: 140px;
            }

            .profile-image-ring {
                width: 130px;
                height: 130px;
            }

            .profile-image-wrapper {
                margin-top: -65px;
            }
        }
    </style>
</head>

<body>

    <div class="ambient-bg">
        <div class="wireframe-grid"></div>
    </div>

    <!-- Floating Particles -->
    <script>
        (function () {
            const colors = ['<?php echo $tc['color']; ?>', '#ffffff', '<?php echo $tc['color']; ?>'];
            for (let i = 0; i < 12; i++) {
                const p = document.createElement('div');
                p.className = 'particle';
                const size = Math.random() * 4 + 2;
                p.style.width = size + 'px';
                p.style.height = size + 'px';
                p.style.left = Math.random() * 100 + '%';
                p.style.background = colors[Math.floor(Math.random() * colors.length)];
                p.style.opacity = Math.random() * 0.3 + 0.1;
                p.style.animationDuration = (Math.random() * 15 + 10) + 's';
                p.style.animationDelay = (Math.random() * 10) + 's';
                document.body.appendChild(p);
            }
        })();
    </script>

    <div class="profile-container">
        <a href="about.php" class="back-btn" id="back-link">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            <span>Back to Team</span>
        </a>

        <div class="profile-card">
            <!-- Banner -->
            <div class="card-banner">
                <div class="team-badge-banner">
                    <i class="fas <?php echo $icon; ?>" aria-hidden="true"></i>&nbsp;
                    <?php echo htmlspecialchars($dev['team']); ?> Team
                </div>

                <?php if ($dev['role'] === 'CA in Charge'): ?>
                    <div class="leadership-badge">
                        <i class="fas fa-medal" aria-hidden="true"></i>
                        <span>Sub-Leadership</span>
                    </div>
                <?php elseif ($dev['role'] === 'Chief Architect'): ?>
                    <div class="leadership-badge" style="border-left-color: #fbbf24; color: #fbbf24;">
                        <i class="fas fa-crown" aria-hidden="true"></i>
                        <span>Primary Leadership</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Profile Image -->
            <div class="profile-image-wrapper">
                <div class="profile-image-ring">
                    <?php if ($hasImage): ?>
                        <img src="<?php echo htmlspecialchars($devImage); ?>"
                            alt="<?php echo htmlspecialchars($dev['name']); ?>" class="profile-image">
                    <?php else: ?>
                        <div class="profile-placeholder">
                            <i class="fas fa-user" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Content -->
            <div class="profile-content">
                <h1 class="dev-name"><?php echo htmlspecialchars($dev['name']); ?></h1>

                <div class="info-chips">
                    <span class="chip chip-team">
                        <i class="fas <?php echo $icon; ?>" aria-hidden="true"></i>
                        <?php echo htmlspecialchars($dev['team']); ?>
                    </span>
                    <span class="chip chip-role">
                        <i class="fas fa-id-badge" aria-hidden="true"></i>
                        <?php echo htmlspecialchars($dev['role']); ?>
                    </span>
                </div>

                <div class="divider"></div>

                <div class="project-info">
                    <div class="project-label">Project</div>
                    <div class="project-name">ALM Biometric Attendance & Payroll System</div>
                    <div class="project-sub">ACLC College Tacloban &mdash; BSIT 3A, Batch 2027</div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/context-menu.js"></script>
</body>

</html>