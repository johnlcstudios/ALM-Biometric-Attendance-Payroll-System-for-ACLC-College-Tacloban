<section id="biometrics" class="page">
    <div class="biometrics-container">
        <div class="registration-controls">
            <h3>Face Registration</h3>
            <div class="instruction-box box-type">
                <p><strong>How to Register:</strong></p>
                <ul>
                    <li>Select an employee from the list.</li>
                    <li>Ensure good lighting and remove glasses/masks.</li>
                    <li>Hold still when the indicator turns green.</li>
                </ul>
            </div>
            
            <div class="form-group">
                <label>Target Employee</label>
                <select id="regEmployeeSelect" class="form-control">
                    <option value="">Choose Employee...</option>
                </select>
            </div>

            <div class="action-buttons-group">
                <button id="startRegBtn" class="btn btn-primary btn-lg" onclick="initFaceRegistration()">
                    <i class="fas fa-camera"></i> Start Registration
                </button>
                <button id="captureBtn" class="btn btn-success btn-lg" style="display:none;" onclick="saveFaceRegistration()">
                    <i class="fas fa-user-plus"></i> Manual Capture
                </button>
            </div>
        </div>
        <div class="camera-preview">
            <video id="video" autoplay muted style="transform: scaleX(-1);"></video>
            <canvas id="overlay" style="transform: scaleX(-1);"></canvas>
            <div id="camera-placeholder">
                <div class="placeholder-content">
                    <i class="fas fa-video-slash"></i>
                    <p>Camera is currently inactive</p>
                    <small>Select an employee and click "Start Registration"</small>
                </div>
            </div>
        </div>
    </div>
</section>
