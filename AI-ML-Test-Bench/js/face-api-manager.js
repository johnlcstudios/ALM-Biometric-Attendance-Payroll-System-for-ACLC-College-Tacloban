/**
 * FaceManager - A robust utility for Face Recognition using face-api.js
 * Redesigned for production-ready registration and kiosk scanning.
 */
class FaceManager {
    constructor(config = {}) {
        this.config = {
            modelUrl: config.modelUrl || 'models/',
            minConfidence: config.minConfidence || 0.5,
            stabilityThreshold: config.stabilityThreshold || 15,
            stabilityRequired: config.stabilityRequired || 6,
            sampleCount: config.sampleCount || 5,
            ...config
        };
        
        this.stream = null;
        this.modelsLoaded = false;
        this.isProcessing = false;
        this.stabilityCounter = 0;
        this.lastBox = null;
    }

    async loadModels() {
        if (this.modelsLoaded) return true;
        try {
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(this.config.modelUrl),
                faceapi.nets.ssdMobilenetv1.loadFromUri(this.config.modelUrl),
                faceapi.nets.faceLandmark68Net.loadFromUri(this.config.modelUrl),
                faceapi.nets.faceRecognitionNet.loadFromUri(this.config.modelUrl)
            ]);
            this.modelsLoaded = true;
            return true;
        } catch (err) {
            console.error("FaceManager: Model loading failed", err);
            throw new Error("Failed to load face recognition models.");
        }
    }

    async startCamera(videoElement, width = 640, height = 480) {
        this.stopCamera(); // Ensure clean start
        
        // Try different camera constraints for better cross-device compatibility
        const constraints = [
            // First try: Ideal settings
            {
                video: { 
                    width: { ideal: width }, 
                    height: { ideal: height },
                    frameRate: { ideal: 30, min: 15 },
                    facingMode: 'user'  // Front camera for mobile
                }
            },
            // Second try: Minimal constraints
            {
                video: { 
                    width: { min: 320, ideal: width }, 
                    height: { min: 240, ideal: height },
                    facingMode: 'user'
                }
            },
            // Third try: Any available camera
            {
                video: true
            }
        ];
        
        let lastError = null;
        
        for (const constraint of constraints) {
            try {
                this.stream = await navigator.mediaDevices.getUserMedia(constraint);
                
                // Verify stream is active
                if (!this.stream.active || this.stream.getTracks().length === 0) {
                    throw new Error("Camera stream is not active");
                }
                
                videoElement.srcObject = this.stream;
                
                return new Promise((resolve, reject) => {
                    const timeout = setTimeout(() => {
                        reject(new Error("Camera initialization timeout"));
                    }, 10000);
                    
                    videoElement.onloadedmetadata = () => {
                        clearTimeout(timeout);
                        
                        // Verify video dimensions are valid
                        if (videoElement.videoWidth === 0 || videoElement.videoHeight === 0) {
                            reject(new Error("Invalid video dimensions"));
                            return;
                        }
                        
                        resolve(this.stream);
                    };
                    
                    videoElement.onerror = (err) => {
                        clearTimeout(timeout);
                        reject(new Error("Video element error: " + err.message));
                    };
                });
            } catch (err) {
                console.warn(`Camera constraint set failed:`, err);
                lastError = err;
                
                // Clean up failed stream
                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                    this.stream = null;
                }
            }
        }
        
        // All attempts failed
        console.error("FaceManager: Camera access failed", lastError);
        if (lastError && (lastError.name === 'NotReadableError' || lastError.name === 'TrackStartError')) {
            throw new Error("Camera is already in use by another tab or application. Please close other apps using the camera.");
        } else if (lastError && lastError.name === 'NotAllowedError') {
            throw new Error("Camera permission denied. Please allow camera access in your browser settings.");
        } else if (lastError && lastError.name === 'NotFoundError') {
            throw new Error("No camera found on this device.");
        }
        throw new Error("Failed to access camera. Please check permissions and ensure no other app is using the camera.");
    }

    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
    }

    checkStability(box) {
        if (!this.lastBox) {
            this.lastBox = box;
            this.stabilityCounter = 0;
            return false;
        }

        const dx = Math.abs(box.x - this.lastBox.x);
        const dy = Math.abs(box.y - this.lastBox.y);
        const dw = Math.abs(box.width - this.lastBox.width);
        const dh = Math.abs(box.height - this.lastBox.height);

        if (dx < this.config.stabilityThreshold && 
            dy < this.config.stabilityThreshold &&
            dw < this.config.stabilityThreshold &&
            dh < this.config.stabilityThreshold) {
            this.stabilityCounter++;
        } else {
            this.stabilityCounter = 0;
        }

        this.lastBox = box;
        return this.stabilityCounter >= this.config.stabilityRequired;
    }

    /**
     * Check if face is looking straight at camera using facial landmarks
     * Returns true if face is frontal (within acceptable angle range)
     */
    checkFrontalFace(landmarks) {
        if (!landmarks) return false;

        const points = landmarks.positions;
        
        // Get key facial landmarks
        const noseTip = points[30]; // Nose tip
        const leftEye = points[36]; // Left eye center
        const rightEye = points[45]; // Right eye center
        const leftMouth = points[48]; // Left mouth corner
        const rightMouth = points[54]; // Right mouth corner
        const chin = points[8]; // Chin bottom
        const forehead = points[27]; // Between eyes

        // Calculate face center
        const faceCenterX = (leftEye.x + rightEye.x) / 2;
        const faceCenterY = (forehead.y + chin.y) / 2;

        // Check horizontal alignment (yaw)
        // Nose should be approximately centered between eyes
        const eyeDistance = Math.abs(rightEye.x - leftEye.x);
        const noseOffset = Math.abs(noseTip.x - faceCenterX);
        const yawRatio = noseOffset / eyeDistance;

        // Check vertical alignment (pitch)
        // Nose should be below eyes and above mouth
        const eyeY = (leftEye.y + rightEye.y) / 2;
        const mouthY = (leftMouth.y + rightMouth.y) / 2;
        const noseVerticalPosition = (noseTip.y - eyeY) / (mouthY - eyeY);

        // Check eye level (roll)
        const eyeLevelDiff = Math.abs(leftEye.y - rightEye.y);
        const eyeLevelRatio = eyeLevelDiff / eyeDistance;

        // Check face symmetry (should be looking straight)
        const leftEyeToNose = Math.abs(noseTip.x - leftEye.x);
        const rightEyeToNose = Math.abs(rightEye.x - noseTip.x);
        const symmetryRatio = Math.min(leftEyeToNose, rightEyeToNose) / Math.max(leftEyeToNose, rightEyeToNose);

        // Thresholds for frontal face detection
        const YAW_THRESHOLD = 0.25; // Nose offset ratio (lower = stricter)
        const PITCH_MIN = 0.3; // Nose vertical position min
        const PITCH_MAX = 0.7; // Nose vertical position max
        const ROLL_THRESHOLD = 0.1; // Eye level difference ratio
        const SYMMETRY_THRESHOLD = 0.7; // Face symmetry ratio (higher = stricter)

        // All checks must pass for frontal face
        const isFrontalYaw = yawRatio < YAW_THRESHOLD;
        const isFrontalPitch = noseVerticalPosition >= PITCH_MIN && noseVerticalPosition <= PITCH_MAX;
        const isFrontalRoll = eyeLevelRatio < ROLL_THRESHOLD;
        const isFrontalSymmetry = symmetryRatio > SYMMETRY_THRESHOLD;

        return {
            isFrontal: isFrontalYaw && isFrontalPitch && isFrontalRoll && isFrontalSymmetry,
            yaw: yawRatio,
            pitch: noseVerticalPosition,
            roll: eyeLevelRatio,
            symmetry: symmetryRatio,
            details: {
                yawOk: isFrontalYaw,
                pitchOk: isFrontalPitch,
                rollOk: isFrontalRoll,
                symmetryOk: isFrontalSymmetry
            }
        };
    }

    async captureSamples(videoElement, onProgress = null) {
        const samples = [];
        const qualityScores = [];
        
        // Use TinyFaceDetector for faster detection (3x faster than SSD)
        const options = new faceapi.TinyFaceDetectorOptions({ 
            inputSize: 320,  // Higher resolution for better accuracy
            scoreThreshold: 0.6  // Higher confidence threshold
        });
        
        // Maximum attempts increased for better sample quality
        const maxAttempts = 20;
        
        for (let i = 0; i < maxAttempts && samples.length < this.config.sampleCount; i++) {
            if (onProgress) onProgress(samples.length + 1, this.config.sampleCount);
            
            const detection = await faceapi.detectSingleFace(videoElement, options)
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (detection && detection.descriptor) {
                // Check if face is frontal for better quality
                const frontalCheck = this.checkFrontalFace(detection.landmarks);
                
                // Only accept frontal faces with good quality
                if (frontalCheck.isFrontal) {
                    // Calculate quality score based on frontal metrics
                    const qualityScore = (
                        (1 - frontalCheck.yaw) * 0.3 +  // Lower yaw is better
                        (frontalCheck.symmetry) * 0.3 +  // Higher symmetry is better
                        (1 - frontalCheck.roll) * 0.2 +  // Lower roll is better
                        0.2  // Base score
                    );
                    
                    samples.push(Array.from(detection.descriptor));
                    qualityScores.push(qualityScore);
                }
            }
            
            // Reduced delay: 50ms instead of 100ms (2x faster)
            // But still enough for slight face movement variation
            await new Promise(r => setTimeout(r, 50));
        }

        if (samples.length < 3) {
            throw new Error("Could not capture enough clear face samples. Please look straight at the camera and ensure good lighting.");
        }

        // Weighted average: higher quality samples contribute more
        const averaged = new Float32Array(128).fill(0);
        let totalWeight = 0;
        
        for (let i = 0; i < samples.length; i++) {
            const weight = qualityScores[i];
            totalWeight += weight;
            
            for (let j = 0; j < 128; j++) {
                averaged[j] += samples[i][j] * weight;
            }
        }
        
        // Normalize by total weight
        for (let j = 0; j < 128; j++) {
            averaged[j] /= totalWeight;
        }
        
        return Array.from(averaged);
    }

    drawDetection(canvas, videoElement, detection, statusText = "", color = "#3b4fc9") {
        const displaySize = { width: videoElement.videoWidth, height: videoElement.videoHeight };
        faceapi.matchDimensions(canvas, displaySize);
        const resized = faceapi.resizeResults(detection, displaySize);
        
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw landmarks with custom style
        faceapi.draw.drawFaceLandmarks(canvas, resized);
        
        // Custom status text overlay
        if (statusText) {
            const { x, y, width, height } = resized.detection.box;
            ctx.fillStyle = color;
            ctx.font = "bold 18px Inter, sans-serif";
            ctx.textAlign = "center";
            // Flip text if video is mirrored
            ctx.save();
            ctx.scale(-1, 1);
            ctx.fillText(statusText, -(x + width / 2), y + height + 30);
            ctx.restore();
        }
    }
}

// Export for use in both modules
window.FaceManager = FaceManager;
