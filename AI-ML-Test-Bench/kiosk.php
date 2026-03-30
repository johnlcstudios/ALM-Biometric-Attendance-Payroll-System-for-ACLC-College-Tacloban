<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ALM Attendance Kiosk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
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
            0% { top: 0; }
            50% { top: 100%; }
            100% { top: 0; }
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
            transform: scaleX(-1); /* Mirror effect */
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

        .camera-placeholder i { font-size: 5rem; margin-bottom: 1rem; }

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
            background: rgba(255,255,255,0.1);
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
            text-shadow: 0 4px 10px rgba(0,0,0,0.2);
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
            box-shadow: 0 30px 60px rgba(0,0,0,0.4);
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
        .text-success { color: var(--primary-blue) !important; }
        .text-danger { color: var(--accent-red) !important; }

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
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        .selection-card h2 {
            color: var(--primary-blue);
            margin-bottom: 2rem;
            font-size: 2rem;
        }

        .company-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
        }

        .company-item {
            padding: 1.2rem;
            background: #f0f0f0;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            color: var(--primary-blue);
            border: 2px solid transparent;
        }

        .company-item:hover {
            background: var(--primary-blue);
            color: white;
            transform: translateY(-2px);
        }

        .change-company-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            cursor: pointer;
            z-index: 100;
            font-weight: 600;
            backdrop-filter: blur(5px);
        }

        .change-company-btn:hover {
            background: rgba(255,255,255,0.4);
        }

        /* New Responsive Design */
        @media (max-width: 1024px) {
            .kiosk-wrapper {
                flex-direction: column;
                height: auto;
            }
            .left-panel, .right-panel {
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
            </div>
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <div class="school-logo" id="company-name">ALM KIOSK</div>
            <div style="font-size: 0.7rem; opacity: 0.5;">Company ID: <span id="debug-company-id">1</span></div>
            
            <div class="clock-container">
                <div class="clock" id="clock">00:00 AM</div>
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
        const video = document.getElementById('video');
        const clockEl = document.getElementById('clock');
        const statusEl = document.getElementById('status-message');
        const displayName = document.getElementById('display-name');
        const displayRole = document.getElementById('display-role');
        const statAttendance = document.getElementById('stat-attendance');
        const statAbsent = document.getElementById('stat-absent');
        const statEmpId = document.getElementById('stat-empid');
        const cameraCircle = document.querySelector('.camera-circle');
        
        let isProcessing = false;
        let currentCompanyId = null;

        // Initialize Kiosk
        async function init() {
            const urlParams = new URLSearchParams(window.location.search);
            const companyIdFromUrl = urlParams.get('company_id');

            if (companyIdFromUrl) {
                setCompany(companyIdFromUrl);
            } else {
                showCompanySelection();
            }
        }

        async function showCompanySelection() {
            const overlay = document.getElementById('companySelectionOverlay');
            const list = document.getElementById('companyList');
            overlay.style.display = 'flex';
            
            try {
                const response = await fetch('backend/api.php?action=get_companies');
                const companies = await response.json();
                
                list.innerHTML = companies.map(c => `
                    <div class="company-item" onclick="setCompany(${c.id})">${c.name}</div>
                `).join('');
            } catch (err) {
                list.innerHTML = '<p class="text-danger">Failed to load companies.</p>';
            }
        }

        function setCompany(id) {
            currentCompanyId = id;
            document.getElementById('debug-company-id').innerText = id;
            document.getElementById('companySelectionOverlay').style.display = 'none';
            
            // Update URL without reload
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?company_id=' + id;
            window.history.pushState({path:newUrl},'',newUrl);

            // Fetch Company Name
            fetch(`backend/api.php?action=get_company_info&company_id=${id}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('company-name').innerText = data.name.toUpperCase();
                });

            // If video is not started, start it
            if (!video.srcObject) {
                startKiosk();
            }
        }

        // Update Clock
        setInterval(() => {
            const now = new Date();
            clockEl.innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }, 1000);

        async function startKiosk() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
                video.srcObject = stream;
                
                const statusLabel = document.getElementById('loading-status');
                statusLabel.innerText = "Loading AI Models...";

                console.log("Loading models...");
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/'),
                    faceapi.nets.faceLandmark68Net.loadFromUri('https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/'),
                    faceapi.nets.faceRecognitionNet.loadFromUri('https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/')
                ]);
                console.log("Models loaded.");
                statusLabel.innerText = "Ready!";
                
                setTimeout(() => {
                    document.getElementById('placeholder').style.display = 'none';
                }, 1000);

                detectFace();
            } catch (err) {
                console.error(err);
                statusEl.innerHTML = '<p class="text-danger">Camera access required.</p>';
            }
        }

        let stabilityCounter = 0;
        let lastBox = null;
        const STABILITY_REQUIRED = 5; // number of stable frames before scanning
        const MOVEMENT_THRESHOLD = 20; // Slightly relaxed for better UX
        let blinkDetected = false;
        let maxEAR = 0; // Baseline for "open eyes"

        async function detectFace() {
            const overlay = document.getElementById('overlay');
            const ctx = overlay.getContext('2d');
            // Higher input size for better landmark precision (crucial for blink detection)
            const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 });

            async function loop() {
                if (isProcessing || !currentCompanyId) {
                    ctx.clearRect(0, 0, overlay.width, overlay.height);
                    stabilityCounter = 0;
                    lastBox = null;
                    blinkDetected = false;
                    maxEAR = 0;
                    requestAnimationFrame(loop);
                    return;
                }

                // Initial detection for face presence and stability
                const detection = await faceapi.detectSingleFace(video, options).withFaceLandmarks();
                
                ctx.clearRect(0, 0, overlay.width, overlay.height);

                if (detection) {
                    const dims = faceapi.matchDimensions(overlay, video, true);
                    const resizedDetection = faceapi.resizeResults(detection, dims);
                    const landmarks = resizedDetection.landmarks;
                    const box = resizedDetection.detection.box;

                    // Liveness Check: Improved Blink Detection (Relative EAR)
                    const leftEye = landmarks.getLeftEye();
                    const rightEye = landmarks.getRightEye();
                    
                    const getEAR = (eye) => {
                        // Using precise Euclidean distance for EAR calculation
                        const v1 = Math.hypot(eye[1].x - eye[5].x, eye[1].y - eye[5].y);
                        const v2 = Math.hypot(eye[2].x - eye[4].x, eye[2].y - eye[4].y);
                        const h = Math.hypot(eye[0].x - eye[3].x, eye[0].y - eye[3].y);
                        return (v1 + v2) / (2.0 * h);
                    };

                    const currentEAR = (getEAR(leftEye) + getEAR(rightEye)) / 2;

                    // Update baseline with a sanity check (EAR shouldn't exceed 0.4 for normal eyes)
                    if (currentEAR > maxEAR && currentEAR < 0.4) {
                        maxEAR = currentEAR;
                    }

                    // Blink detection logic:
                    // 1. We need a baseline (maxEAR) of at least 0.15 (typical open eye)
                    // 2. Current EAR must drop by 30% or more compared to baseline
                    // 3. Or absolute drop below 0.18 if baseline is already established
                    if (maxEAR > 0.15 && currentEAR < maxEAR * 0.70) {
                        blinkDetected = true;
                    }

                    // Stability Check
                    if (lastBox) {
                        const dx = Math.abs(box.x - lastBox.x);
                        const dy = Math.abs(box.y - lastBox.y);
                        if (dx < MOVEMENT_THRESHOLD && dy < MOVEMENT_THRESHOLD) {
                            stabilityCounter++;
                        } else {
                            stabilityCounter = 0;
                        }
                    }
                    lastBox = box;

                    faceapi.draw.drawDetections(overlay, resizedDetection);
                    faceapi.draw.drawFaceLandmarks(overlay, resizedDetection);

                    // Mirror text drawing
                    ctx.save();
                    ctx.scale(-1, 1);
                    ctx.translate(-overlay.width, 0);
                    
                    const textX = overlay.width - (box.x + box.width / 2);
                    const textY = box.y + box.height + 30;

                    ctx.font = "bold 16px Inter";
                    ctx.textAlign = "center";
                    
                    if (stabilityCounter < STABILITY_REQUIRED) {
                        ctx.fillStyle = "#f39c12";
                        ctx.fillText("ALIGN FACE & HOLD STILL...", textX, textY);
                    } else if (!blinkDetected) {
                        ctx.fillStyle = "#f39c12";
                        ctx.fillText("STABLE - BLINK TO SCAN...", textX, textY);
                    } else {
                        ctx.fillStyle = "#27ae60";
                        ctx.fillText("LIVENESS VERIFIED - SCANNING...", textX, textY);
                        
                        if (!isProcessing) {
                            isProcessing = true;
                            cameraCircle.classList.add('scanning');
                            
                            // Get high-quality descriptor
                            const fullDetection = await faceapi.detectSingleFace(video, options)
                                .withFaceLandmarks()
                                .withFaceDescriptor();
                                
                            if (fullDetection) {
                                // Keep state for a moment to show success before resetting
                                setTimeout(() => { 
                                    stabilityCounter = 0; 
                                    lastBox = null;
                                    blinkDetected = false;
                                    maxEAR = 0;
                                }, 5000);
                                await processLog(fullDetection.descriptor);
                            } else {
                                // Detection failed at the last moment
                                isProcessing = false;
                                cameraCircle.classList.remove('scanning');
                                stabilityCounter = 0;
                            }
                        }
                    }
                    ctx.restore();
                } else {
                    stabilityCounter = 0;
                    lastBox = null;
                    // Don't reset blink immediately if face is lost for a split second
                    // but reset maxEAR to adapt to potential new person
                    maxEAR = 0; 
                }

                requestAnimationFrame(loop);
            }

            loop();
        }

        async function processLog(descriptor) {
            if (!descriptor || descriptor.length !== 128) {
                isProcessing = false;
                cameraCircle.classList.remove('scanning');
                return;
            }
            isProcessing = true;
            statusEl.innerHTML = '<p style="color: var(--primary-blue)">Verifying Identity...</p>';
            
            try {
                const response = await fetch('backend/api.php?action=kiosk_scan', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        descriptor: Array.from(descriptor), 
                        company_id: currentCompanyId
                    })
                });
                const result = await response.json();
                
                cameraCircle.classList.remove('scanning');
                
                if (result.success) {
                    displayName.innerText = result.name;
                    displayRole.innerText = result.position || "Employee Verified";
                    
                    let morningWarning = result.missed_morning ? '<br><small style="color: var(--accent-red)">⚠️ MISSED MORNING CHECK-IN</small>' : '';

                    if (result.status === 'Absent') {
                        cameraCircle.className = 'camera-circle border-danger';
                        statusEl.innerHTML = `
                            <h3 class="text-danger">CHECK-OUT (ABSENT)</h3>
                            <p style="color: var(--accent-red); font-size: 0.8rem;">No Check-in Found! Match: ${result.match_percentage}%</p>
                            ${morningWarning}
                        `;
                    } else if (result.status === 'Half-Day') {
                        cameraCircle.className = 'camera-circle border-warning';
                        statusEl.innerHTML = `
                            <h3 style="color: #f39c12">HALF-DAY SUCCESS!</h3>
                            <p style="color: #f39c12; font-size: 0.8rem;">Partial Log Recorded. Match: ${result.match_percentage}%</p>
                            ${morningWarning}
                        `;
                    } else {
                        cameraCircle.className = 'camera-circle border-success';
                        statusEl.innerHTML = `
                            <h3 class="text-success">${result.action.toUpperCase()} SUCCESS!</h3>
                            <p style="color: #27ae60; font-size: 0.8rem;">Match: ${result.match_percentage}%</p>
                            ${morningWarning}
                        `;
                    }
                    
                    statAttendance.innerText = String(result.attendance_count).padStart(2, '0');
                    statAbsent.innerText = String(result.absent_count).padStart(2, '0');
                    statEmpId.innerText = result.employee_id;

                    setTimeout(() => {
                        resetUI();
                        isProcessing = false;
                    }, 5000);
                } else if (result.message === 'ALREADY LOGGED') {
                    // Show warning for already logged
                    cameraCircle.className = 'camera-circle border-warning';
                    displayName.innerText = result.name;
                    displayRole.innerText = "Action Duplicate";
                    
                    let morningWarning = result.missed_morning ? '<br><small style="color: var(--accent-red)">⚠️ MISSED MORNING CHECK-IN</small>' : '';

                    statusEl.innerHTML = `
                        <h3 class="text-danger">ALREADY ${result.action.toUpperCase()}!</h3>
                        <p style="color: #f39c12; font-size: 0.8rem;">Match: ${result.match_percentage}%</p>
                        ${morningWarning}
                    `;
                    
                    statAttendance.innerText = String(result.attendance_count).padStart(2, '0');
                    statAbsent.innerText = String(result.absent_count).padStart(2, '0');
                    statEmpId.innerText = result.employee_id;

                    setTimeout(() => {
                        resetUI();
                        isProcessing = false;
                    }, 5000);
                } else if (result.message === 'MUST CHECK-IN FIRST' || result.message === 'MUST TIME-IN FIRST') {
                    cameraCircle.className = 'camera-circle border-danger';
                    displayName.innerText = result.name;
                    displayRole.innerText = "Entry Point Required";
                    statusEl.innerHTML = `
                        <h3 class="text-danger">${result.message}</h3>
                        <p style="color: var(--accent-red); font-size: 0.8rem;">Entry Scan Required! Match: ${result.match_percentage}%</p>
                    `;
                    
                    setTimeout(() => {
                        resetUI();
                        isProcessing = false;
                    }, 5000);
                } else {
                    cameraCircle.className = 'camera-circle border-danger';
                    const matchText = result.match_percentage > 0 ? `<br><small>Match: ${result.match_percentage}% (Required: 90%)</small>` : '';
                    statusEl.innerHTML = `<p class="text-danger">MATCH FAILED${matchText}</p>`;
                    setTimeout(() => {
                        statusEl.innerHTML = '';
                        cameraCircle.className = 'camera-circle';
                        isProcessing = false;
                    }, 3000);
                }
            } catch (err) {
                console.error(err);
                isProcessing = false;
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
</body>
</html>
