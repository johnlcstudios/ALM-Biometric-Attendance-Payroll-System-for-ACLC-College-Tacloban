<section id="biometrics" class="page">
    <div class="biometrics-container">
        <div class="enrollment-controls">
            <h3>Face Registration</h3>
            <p>Select an employee to link biometric data.</p>
            <div class="form-group">
                <label>Employee Name</label>
                <select id="enrollEmployeeSelect" class="form-control" onchange="updateBiometricStatus(this.value)">
                    <option value="">Select Employee...</option>
                </select>
            </div>
            <div id="biometricStatus">
                <!-- Status will be dynamically updated by JS -->
            </div>
            <div class="enrollment-actions">
                <button id="startEnrollBtn" class="btn btn-primary" onclick="initFaceEnrollment()">
                    <i class="fas fa-camera"></i> Start Camera
                </button>
                <button id="removeFaceBtn" class="btn btn-danger" style="display:none;" onclick="handleUnenroll()">
                    <i class="fas fa-user-minus"></i> Remove Face Data
                </button>
                <button id="captureBtn" class="btn btn-success" style="display:none;" onclick="saveFaceEnrollment()">
                    <i class="fas fa-user-plus"></i> Capture & Save
                </button>
            </div>
        </div>
        <div class="camera-preview">
            <video id="video" autoplay muted style="transform: scaleX(-1);"></video>
            <canvas id="overlay" style="transform: scaleX(-1);"></canvas>
            <div id="camera-placeholder">
                <i class="fas fa-camera-retro"></i>
                <p>Camera Preview Not Started</p>
            </div>
        </div>
    </div>
</section>
