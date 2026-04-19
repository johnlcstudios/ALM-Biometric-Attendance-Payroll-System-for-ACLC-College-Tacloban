<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALM Attendance Kiosk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/face-api.min.js"></script>
    <script src="js/face-api-manager.js?v=2.0"></script>
    
    <!-- Custom Context Menu Styles -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary-blue: #1e0178;
            --accent-red: #db261f;
            --bg-white: #f9f9f9;
            --border-radius: 64px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-white);
            height: 100vh;
            overflow: hidden;
        }

        .kiosk-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
        }

        /* Left Side: Photo Preview */
        .left-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bg-white);
        }

        .camera-circle {
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background-color: #222;
            overflow: hidden;
            position: relative;
            border: 15px solid var(--primary-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 20px 50px rgba(30, 1, 120, 0.3);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        /* Scanning Effect */
        .scanning-line {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, transparent, #27ae60, transparent);
            box-shadow: 0 0 15px #27ae60;
            z-index: 10;
            display: none;
            animation: scan 2s linear infinite;
        }

        @keyframes scan {
            0% {
                top: 0;
            }

            50% {
                top: 100%;
            }

            100% {
                top: 0;
            }
        }

        .camera-circle.scanning .scanning-line {
            display: block;
        }

        .camera-circle.border-success {
            border-color: #27ae60;
            box-shadow: 0 20px 50px rgba(39, 174, 96, 0.4);
        }

        .camera-circle.border-warning {
            border-color: #f39c12;
            box-shadow: 0 20px 50px rgba(243, 156, 18, 0.4);
        }

        .camera-circle.border-danger {
            border-color: var(--accent-red);
            box-shadow: 0 20px 50px rgba(219, 38, 31, 0.4);
        }

        #video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
            /* Mirror effect */
        }

        #overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            transform: scaleX(-1);
        }

        .camera-placeholder {
            color: white;
            text-align: center;
            position: absolute;
            z-index: 1;
        }

        .camera-placeholder i {
            font-size: 5rem;
            margin-bottom: 1rem;
        }

        /* Face Position Guide Overlay */
        .face-guide-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 5;
            display: none;
        }

        .face-guide-overlay.active {
            display: block;
        }

        .face-guide-circle {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 280px;
            height: 350px;
            border: 3px dashed rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
            display: none;
        }

        .face-guide-circle.perfect {
            border-color: #27ae60;
            border-style: solid;
            box-shadow: 0 0 20px rgba(39, 174, 96, 0.5);
        }

        .face-guide-circle.warning {
            border-color: #f39c12;
            border-style: dashed;
        }

        @keyframes pulse {
            0%, 100% {
                transform: translate(-50%, -50%) scale(1);
            }
            50% {
                transform: translate(-50%, -50%) scale(1.02);
            }
        }

        .position-hint {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            z-index: 6;
        }

        /* Right Side: Info Panel */
        .right-panel {
            flex: 1;
            background-color: var(--primary-blue);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 3rem;
            justify-content: space-between;
            color: var(--bg-white);
        }

        .school-logo {
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 40px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--bg-white);
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .clock-container {
            text-align: center;
        }

        .clock {
            font-size: 6rem;
            font-weight: 700;
            color: var(--bg-white);
            text-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        /* Employee Summary Card */
        .summary-card {
            background-color: var(--bg-white);
            width: 100%;
            max-width: 600px;
            border-radius: var(--border-radius);
            padding: 3rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
            color: var(--primary-blue);
        }

        .summary-header {
            background-color: var(--accent-red);
            padding: 0.8rem 3rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.4rem;
            color: white;
            margin-bottom: 2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .employee-info {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .employee-name {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--primary-blue);
            margin-bottom: 0.2rem;
        }

        .employee-role {
            font-size: 1.2rem;
            color: #555;
            font-weight: 500;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.2rem;
            width: 100%;
        }

        .stat-box {
            display: flex;
            flex-direction: column;
            border: 2px solid #eee;
            border-radius: 15px;
            overflow: hidden;
        }

        .stat-label {
            background-color: #f0f0f0;
            padding: 0.6rem;
            text-align: center;
            font-weight: 700;
            font-size: 0.85rem;
            color: #666;
            text-transform: uppercase;
        }

        .stat-value {
            background-color: white;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .footer-text {
            color: rgba(249, 249, 249, 0.6);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Status Colors */
        .text-success {
            color: var(--primary-blue) !important;
        }

        .text-danger {
            color: var(--accent-red) !important;
        }

        /* Selection Overlay */
        .selection-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(30, 1, 120, 0.95);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .selection-card {
            background: white;
            padding: 3rem;
            border-radius: 32px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }

        .selection-card h2 {
            color: var(--primary-blue);
            margin-bottom: 2rem;
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .company-list {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
        }

        .company-item {
            padding: 1.4rem;
            background: #f8f9fa;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 700;
            color: var(--primary-blue);
            border: 2px solid #e9ecef;
        }

        .company-item:hover {
            background: white;
            color: var(--primary-blue);
            transform: translateY(-3px);
            border-color: var(--primary-blue);
            box-shadow: 0 10px 20px rgba(30, 1, 120, 0.1);
        }

        .change-company-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(191, 31, 31, 0.2);
            border: none;
            color: #372222;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            z-index: 100;
            font-weight: 600;
            backdrop-filter: blur(5px);
        }

        .change-company-btn:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 35px rgba(30, 1, 120, 0.3);
        }

        .change-company-btn i {
            font-size: 0.9em;
            opacity: 0.8;
        }

        .change-company-btn:hover i {
            opacity: 1;
        }

        /* New Responsive Design */
        @media (max-width: 1024px) {
            .kiosk-wrapper {
                flex-direction: column;
                height: auto;
            }

            .left-panel,
            .right-panel {
                flex: 1;
                width: 100%;
                min-height: 100vh;
                padding: 2rem;
            }

            .camera-circle {
                width: 60vmin;
                height: 60vmin;
            }

            .clock {
                font-size: 12vmin;
            }

            .summary-card {
                width: 90%;
                padding: 4vmin;
            }

            .employee-name {
                font-size: 5vmin;
            }

            .stat-value {
                font-size: 6vmin;
            }
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
        .swal2-container.glass-backdrop { background: rgba(0, 0, 0, 0.5) !important; backdrop-filter: blur(4px) !important; }
        
        .glass-toast-popup {
            background: rgba(255, 255, 255, 0.15) !important;
            backdrop-filter: blur(25px) saturate(180%) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            border-radius: 20px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25), inset 0 1px 1px rgba(255, 255, 255, 0.4) !important;
            z-index: 100001 !important;
            position: relative !important;
        }
        .glass-toast-title { color: #ffffff !important; font-weight: 600 !important; }
        .glass-toast-progress { height: 3px !important; background: linear-gradient(90deg, rgba(79, 172, 254, 0.4), rgba(79, 172, 254, 0.8)) !important; }
    </style>
</head>

<body>
    <!-- Company Selection Overlay -->
    <div id="companySelectionOverlay" class="selection-overlay" style="display: none;">
        <div class="selection-card">
            <h2>Select Company</h2>
            <div id="companyList" class="company-list">
                <p>Loading companies...</p>
            </div>
        </div>
    </div>

    <button class="change-company-btn" onclick="showCompanySelection()">
        <i class="fas fa-exchange-alt"></i> Change Company
    </button>

    <div class="kiosk-wrapper">
        <!-- Left Panel -->
        <div class="left-panel">
            <div class="camera-circle">
                <div class="scanning-line"></div>
                <div class="camera-placeholder" id="placeholder">
                    <i class="fas fa-user-circle"></i>
                    <p id="loading-status">Initializing Camera...</p>
                </div>
                <video id="video" autoplay muted></video>
                <canvas id="overlay"></canvas>
                
                <!-- Face Position Guide -->
                <div class="face-guide-overlay" id="faceGuide">
                    <div class="face-guide-circle" id="guideCircle"></div>
                    <div class="position-hint" id="positionHint">Position your face in the circle</div>
                </div>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <div class="school-logo" id="company-name">ALM KIOSK</div>
            <div style="font-size: 0.7rem; opacity: 0.5;">Company ID: <span id="debug-company-id">1</span></div>

            <div class="clock-container">
                <div class="clock" id="clock">00:00 AM</div>
                <div id="current-action-badge" style="
                    background: var(--primary-blue);
                    color: white;
                    padding: 5px 15px;
                    border-radius: 20px;
                    font-size: 0.8rem;
                    margin-top: 10px;
                    display: inline-block;
                    font-weight: bold;
                    letter-spacing: 1px;
                ">LOADING...</div>
            </div>

            <div class="summary-card">
                <div class="summary-header">Employee Summary</div>

                <div class="employee-info" id="emp-info">
                    <div class="employee-name" id="display-name">---</div>
                    <div class="employee-role" id="display-role">---</div>
                </div>

                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-label">Attendances</div>
                        <div class="stat-value" id="stat-attendance">00</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">Absences</div>
                        <div class="stat-value" id="stat-absent">00</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-label">EMP ID</div>
                        <div class="stat-value" id="stat-empid" style="font-size: 1.5rem;">---</div>
                    </div>
                </div>

                <div id="status-message" style="margin-top: 20px; font-weight: bold; text-align: center;"></div>
            </div>

            <div class="footer-text">Developed By: BSIT-3A (2027)</div>
        </div>
    </div>

    <script>
        // Prevent file drag and drop to bypass camera
        document.addEventListener('dragover', (e) => e.preventDefault());
        document.addEventListener('drop', (e) => {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Action Blocked',
                text: 'Only live camera feed is accepted – please use the built-in camera.',
                confirmButtonColor: '#1e0178'
            });
        });
        
        // Prevent any file picker dialogs
        document.addEventListener('click', (e) => {
            if (e.target && e.target.tagName === 'INPUT' && e.target.type === 'file') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Action Blocked',
                    text: 'Only live camera feed is accepted – please use the built-in camera.',
                    confirmButtonColor: '#1e0178'
                });
            }
        }, true);
        
        const video = document.getElementById('video');
        const canvas = document.getElementById('overlay');
        const clockEl = document.getElementById('clock');
        const statusEl = document.getElementById('status-message');
        const displayName = document.getElementById('display-name');
        const displayRole = document.getElementById('display-role');
        const statAttendance = document.getElementById('stat-attendance');
        const statAbsent = document.getElementById('stat-absent');
        const statEmpId = document.getElementById('stat-empid');
        const cameraCircle = document.querySelector('.camera-circle');
        const actionBadge = document.getElementById('current-action-badge');

        const faceManager = new FaceManager({
            stabilityRequired: 5,
            sampleCount: 3,
            minConfidence: 0.6
        });

        let serverTimeOffsetMs = 0;
        let serverTimezone = 'Asia/Manila';
        let currentCompanyId = null;
        let companyConfig = null;

        async function syncServerTime() {
            try {
                const companyId = currentCompanyId || new URLSearchParams(window.location.search).get('company_id');
                const url = companyId ? `backend/api.php?action=get_server_time&company_id=${companyId}` : 'backend/api.php?action=get_server_time';
                const res = await fetch(url);
                const data = await res.json();
                if (data.server_ms) serverTimeOffsetMs = data.server_ms - Date.now();
                if (data.timezone) serverTimezone = data.timezone;
            } catch (e) { console.error("Time sync error:", e); }
        }

        function getNow() { return new Date(Date.now() + serverTimeOffsetMs); }

        async function init() {
            const urlParams = new URLSearchParams(window.location.search);
            const companyIdFromUrl = urlParams.get('company_id');
            if (companyIdFromUrl) setCompany(companyIdFromUrl);
            else showCompanySelection();
        }

        async function showCompanySelection() {
            const overlay = document.getElementById('companySelectionOverlay');
            const list = document.getElementById('companyList');
            overlay.style.display = 'flex';
            try {
                const response = await fetch('backend/api.php?action=get_companies');
                const companies = await response.json();
                list.innerHTML = companies.map(c => `<div class="company-item" onclick="setCompany(${c.id})">${c.name}</div>`).join('');
            } catch (err) { list.innerHTML = '<p class="text-danger">Failed to load companies.</p>'; }
        }

        function setCompany(id) {
            currentCompanyId = id;
            document.getElementById('debug-company-id').innerText = id;
            document.getElementById('companySelectionOverlay').style.display = 'none';
            const newUrl = window.location.pathname + '?company_id=' + id;
            window.history.pushState({ path: newUrl }, '', newUrl);
            refreshConfig();
            if (!faceManager.stream) startKiosk();
        }

        function refreshConfig() {
            if (!currentCompanyId) return;
            fetch(`backend/api.php?action=get_company_info&company_id=${currentCompanyId}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.name) {
                        document.getElementById('company-name').innerText = data.name.toUpperCase();
                        companyConfig = data;
                        syncServerTime().then(() => updateCurrentAction());
                    } else {
                        console.error('Failed to load company info:', data);
                    }
                })
                .catch(err => {
                    console.error('Error fetching company info:', err);
                });
        }

        setInterval(refreshConfig, 300000);
        setInterval(() => {
            const now = getNow();
            clockEl.innerText = now.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit',
                timeZone: serverTimezone
            });
            updateCurrentAction();
        }, 1000);

        function updateCurrentAction() {
            if (!companyConfig) {
                actionBadge.innerText = "INITIALIZING...";
                actionBadge.style.background = "#7f8c8d";
                return;
            }

            const now = getNow();
            // Get time string in the server's timezone
            const timeStr = now.toLocaleTimeString('en-GB', {
                timeZone: serverTimezone,
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            let action = "TIME OUT (CHECK OUT)", color = "var(--primary-blue)";

            const workStart = companyConfig.work_start || '08:00:00';
            const workEnd = companyConfig.work_end || '17:00:00';
            const lOutS = companyConfig.lunch_out_start || '10:00:00';
            const lOutE = companyConfig.lunch_out_end || '10:30:00';
            const lInS = companyConfig.lunch_in_start || '10:30:00';
            const lInE = companyConfig.lunch_in_end || '11:00:00';

            if (timeStr < lOutS) {
                action = "TIME IN (CHECK IN)";
                color = "var(--primary-blue)";
            } else if (timeStr >= lOutS && timeStr < lOutE) {
                action = "LUNCH OUT";
                color = "#f39c12";
            } else if (timeStr >= lInS && timeStr < lInE) {
                action = "LUNCH IN";
                color = "#27ae60";
            } else {
                action = timeStr < workEnd ? "TIME OUT (EARLY)" : "TIME OUT (CHECK OUT)";
                color = timeStr < workEnd ? "#e67e22" : "var(--accent-red)";
            }

            if (faceManager.isProcessing) {
                actionBadge.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROCESSING...';
                actionBadge.style.background = "#34495e";
            } else {
                actionBadge.innerText = action;
                actionBadge.style.background = color;
            }
        }

        async function startKiosk() {
            const statusLabel = document.getElementById('loading-status');
            try {
                statusLabel.innerText = "Loading AI Models...";
                statusLabel.style.color = "#f39c12";
                
                await faceManager.loadModels();
                
                statusLabel.innerText = "AI Models Loaded ✓";
                statusLabel.style.color = "#27ae60";
                
                setTimeout(async () => {
                    statusLabel.innerText = "Starting Camera...";
                    statusLabel.style.color = "#f39c12";
                    
                    try {
                        await faceManager.startCamera(video);
                        document.getElementById('placeholder').style.display = 'none';
                        statusLabel.innerText = "System Ready";
                        statusLabel.style.color = "#27ae60";
                        detectLoop();
                    } catch (cameraErr) {
                        console.error('Camera Error:', cameraErr);
                        statusLabel.innerHTML = `<p class="text-danger">Camera Error: ${cameraErr.message}</p>`;
                        Swal.fire({
                            icon: 'error',
                            title: 'Camera Access Failed',
                            html: `<p>${cameraErr.message}</p>
                                   <p style="font-size: 0.9em; color: #666; margin-top: 10px;">
                                   Please ensure:<br>
                                   • Camera is not being used by another application<br>
                                   • Browser has camera permissions<br>
                                   • You're using HTTPS or localhost</p>`,
                            confirmButtonText: 'Try Again'
                        }).then(() => {
                            location.reload();
                        });
                    }
                }, 500);
                
            } catch (err) {
                console.error('Model Loading Error:', err);
                statusLabel.innerHTML = `<p class="text-danger">Failed to load AI models</p>`;
                
                Swal.fire({
                    icon: 'error',
                    title: 'AI Models Failed to Load',
                    html: `<p style="text-align: left; font-size: 0.9em;">
                           <strong>Possible causes:</strong><br>
                           • Model files are missing from <code>models/</code> folder<br>
                           • Web server is not configured correctly<br>
                           • Browser cannot access the model files<br><br>
                           <strong>Check browser console (F12) for detailed errors.</strong></p>`,
                    footer: `<a href="javascript:location.reload()" style="color: #667eea;">Click here to retry</a>`
                });
            }
        }

        async function detectLoop() {
            const detectorOptions = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });

            const loop = async () => {
                if (!currentCompanyId || faceManager.isProcessing) {
                    requestAnimationFrame(loop);
                    return;
                }

                // Detect face with landmarks for frontal check
                const detection = await faceapi.detectSingleFace(video, detectorOptions)
                    .withFaceLandmarks();
                
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                const faceGuide = document.getElementById('faceGuide');
                const guideCircle = document.getElementById('guideCircle');
                const positionHint = document.getElementById('positionHint');

                if (detection) {
                    // Show face guide
                    faceGuide.classList.add('active');

                    // Check if face is stable
                    const isStable = faceManager.checkStability(detection.detection.box);
                    
                    // Check if face is looking straight at camera
                    const frontalCheck = faceManager.checkFrontalFace(detection.landmarks);
                    const isFrontal = frontalCheck.isFrontal;

                    // Check if the stream is a live camera feed (not a static image)
                    const isLive = faceManager.checkLiveness(video);

                    // Active Liveness action handling
                    if (isFrontal && isLive && faceManager.livenessAction === 'none') {
                        // Assign a random liveness task when face is frontal
                        const tasks = ['blink', 'smile'];
                        const randomTask = tasks[Math.floor(Math.random() * tasks.length)];
                        faceManager.setLivenessAction(randomTask);
                    }
                    
                    const isActiveLivenessPassed = faceManager.checkActiveLiveness(detection.landmarks);

                    // Determine status message and color
                    let status, color, hint;
                    
                    if (!isLive) {
                        status = "STATIC IMAGE DETECTED";
                        hint = "Only live camera feed is accepted – please use the built-in camera.";
                        color = "#db261f"; // Red error
                        guideCircle.className = 'face-guide-circle border-danger';
                        
                        // Show error message
                        Swal.fire({
                            icon: 'error',
                            title: 'Action Blocked',
                            text: 'Only live camera feed is accepted – please use the built-in camera.',
                            confirmButtonColor: '#1e0178'
                        });
                        
                        // Stop processing temporarily to let user read the message
                        faceManager.isProcessing = true;
                        setTimeout(() => { faceManager.isProcessing = false; }, 3000);
                        
                    } else if (!isFrontal) {
                        // Face not looking straight
                        if (!frontalCheck.details.yawOk) {
                            status = "TURN FACE TO CAMERA";
                            hint = "← Turn your face to center →";
                        } else if (!frontalCheck.details.pitchOk) {
                            status = "LOOK STRAIGHT AT CAMERA";
                            hint = frontalCheck.pitch < 0.3 ? "↑ Look up slightly" : "↓ Look down slightly";
                        } else if (!frontalCheck.details.rollOk) {
                            status = "KEEP HEAD LEVEL";
                            hint = "↔ Straighten your head";
                        } else {
                            status = "FACE CAMERA DIRECTLY";
                            hint = "Position face in center";
                        }
                        color = "#f39c12"; // Orange warning
                        guideCircle.className = 'face-guide-circle warning';
                    } else if (!isStable) {
                        status = "HOLD STILL...";
                        hint = "✓ Good! Hold still...";
                        color = "#f39c12"; // Orange
                        guideCircle.className = 'face-guide-circle warning';
                    } else if (!isActiveLivenessPassed) {
                        // Waiting for liveness action
                        status = faceManager.livenessAction === 'blink' ? "PLEASE BLINK TWICE" : "PLEASE SMILE";
                        hint = faceManager.livenessAction === 'blink' ? "Blink twice to verify liveness" : "Smile to verify liveness";
                        color = "#3498db"; // Blue action
                        guideCircle.className = 'face-guide-circle border-primary';
                    } else {
                        // Frontal, stable, and liveness verified - ready to scan!
                        status = "✓ PERFECT! SCANNING...";
                        hint = "✓ Perfect! Scanning now...";
                        color = "#27ae60"; // Green
                        guideCircle.className = 'face-guide-circle perfect';
                    }

                    positionHint.textContent = hint;
                    faceManager.drawDetection(canvas, video, detection, status, color);

                    // Only scan if face is frontal, stable, AND active liveness verified
                    if (isFrontal && isStable && isActiveLivenessPassed) {
                        faceManager.isProcessing = true;
                        cameraCircle.classList.add('scanning');
                        processScan();
                    }
                } else {
                    faceManager.stabilityCounter = 0;
                    faceManager.setLivenessAction('none'); // Reset active liveness
                    cameraCircle.classList.remove('scanning');
                    faceGuide.classList.remove('active');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                }
                requestAnimationFrame(loop);
            };
            loop();
        }

        async function processScan() {
            statusEl.innerHTML = '<p style="color: var(--primary-blue)">Capturing Face Data...</p>';
            try {
                const descriptor = await faceManager.captureSamples(video);
                statusEl.innerHTML = '<p style="color: var(--primary-blue)">Verifying Identity...</p>';

                const response = await fetch('backend/api.php?action=kiosk_scan', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        descriptor,
                        company_id: currentCompanyId,
                        scan_time: getNow().toISOString()
                    })
                });

                const result = await response.json();
                cameraCircle.classList.remove('scanning');

                if (result.success) {
                    displayName.innerText = result.name;
                    displayRole.innerText = result.position;
                    statAttendance.innerText = String(result.attendance_count).padStart(2, '0');
                    statAbsent.innerText = String(result.absent_count).padStart(2, '0');
                    statEmpId.innerText = result.employee_id;
                    cameraCircle.className = `camera-circle border-${result.status === 'On-Time' ? 'success' : 'warning'}`;
                    statusEl.innerHTML = `<h3 class="text-success">${(result.action || "SCAN").toUpperCase().replace('_', ' ')} SUCCESS!</h3><p>${result.time}</p>`;
                } else {
                    cameraCircle.className = 'camera-circle border-danger';
                    statusEl.innerHTML = `<h3 class="text-danger">${result.message}</h3>`;
                }

                setTimeout(() => { resetUI(); faceManager.isProcessing = false; }, 4000);
            } catch (err) {
                console.error(err);
                statusEl.innerHTML = `<p class="text-danger">${err.message}</p>`;
                faceManager.isProcessing = false;
                cameraCircle.classList.remove('scanning');
            }
        }

        function resetUI() {
            cameraCircle.className = 'camera-circle';
            displayName.innerText = "---";
            displayRole.innerText = "---";
            statAttendance.innerText = "00";
            statAbsent.innerText = "00";
            statEmpId.innerText = "---";
            statusEl.innerHTML = '';
        }
        init();

    </script>
    
    <!-- Custom Context Menu -->
    <script src="js/context-menu.js?v=1.0"></script>
</body>

</html>