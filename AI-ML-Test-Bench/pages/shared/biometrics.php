<section id="biometrics" class="page">
    <div class="biometrics-container">
        <div class="enrollment-controls">
            <h3>Face Registration</h3>
            <p>Select an employee to link biometric data.</p>
            <div class="form-group">
                <label>Employee Name</label>
                <select id="enrollEmployeeSelect" class="form-control">
                    <option value="">Select Employee...</option>
                </select>
            </div>
            <button id="startEnrollBtn" class="btn btn-primary" onclick="initFaceEnrollment()">
                <i class="fas fa-camera"></i> Start Camera
            </button>
            <button id="captureBtn" class="btn btn-success" style="display:none;" onclick="saveFaceEnrollment()">
                <i class="fas fa-user-plus"></i> Capture & Save
            </button>
        </div>
        <div class="camera-preview">
            <video id="video" width="640" height="480" autoplay muted style="transform: scaleX(-1);"></video>
            <canvas id="overlay" style="transform: scaleX(-1);"></canvas>
            <div id="camera-placeholder">
                <i class="fas fa-camera-retro"></i>
                <p>Camera Preview Not Started</p>
            </div>
        </div>
    </div>
</section>
