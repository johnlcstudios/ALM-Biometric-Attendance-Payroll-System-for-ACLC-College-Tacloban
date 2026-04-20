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
        
        // Liveness properties
        this.livenessCanvas = null;
        this.livenessCtx = null;
        this.lastFrameData = null;
        this.staticFrameCount = 0;
        
        // Active Liveness state
        this.livenessAction = 'none'; // 'blink', 'smile', 'none'
        this.livenessActionCompleted = false;
        this.blinkCount = 0;
        this.isEyesClosed = false;
        
        // Anti-spoofing micro-motion tracking
        this.eyeDistanceHistory = [];
        this.isSpoofDetected = false;
    }

    setLivenessAction(action) {
        this.livenessAction = action;
        this.livenessActionCompleted = false;
        this.blinkCount = 0;
        this.isEyesClosed = false;
        
        // Differential tracking variables
        this.baselineEAR = null;
        this.baselineMouthRatio = null;
        this.framesProcessed = 0;
        this.actionFrames = 0;
        
        // Reset anti-spoofing when a new action starts
        this.eyeDistanceHistory = [];
        this.isSpoofDetected = false;
    }

    async loadModels() {
        if (this.modelsLoaded) return true;
        
        // Try multiple times with different paths
        const pathsToTry = [
            this.config.modelUrl,
            './models/',
            'models/',
            '/models/',
            window.location.origin + window.location.pathname.split('/').slice(0, -1).join('/') + '/models/'
        ];
        
        let lastError = null;
        
        for (const modelPath of pathsToTry) {
            try {
                console.log(`FaceManager: Trying to load models from: ${modelPath}`);
                
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(modelPath),
                    faceapi.nets.ssdMobilenetv1.loadFromUri(modelPath),
                    faceapi.nets.faceLandmark68Net.loadFromUri(modelPath),
                    faceapi.nets.faceRecognitionNet.loadFromUri(modelPath)
                ]);
                
                this.modelsLoaded = true;
                console.log(`FaceManager: Models loaded successfully from: ${modelPath}`);
                return true;
            } catch (err) {
                console.warn(`FaceManager: Failed to load models from ${modelPath}:`, err.message);
                lastError = err;
                // Continue to next path
            }
        }
        
        // All paths failed
        console.error("FaceManager: Model loading failed from all paths", lastError);
        throw new Error("Failed to load face recognition models. Please check browser console for details.");
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

    /**
     * Checks if the video stream is a live camera feed or a static image
     * Returns true if motion is detected, false if static (frozen image)
     */
    checkLiveness(videoElement) {
        if (!this.livenessCanvas) {
            this.livenessCanvas = document.createElement('canvas');
            // Use low resolution for fast processing
            this.livenessCanvas.width = 64;
            this.livenessCanvas.height = 48;
            this.livenessCtx = this.livenessCanvas.getContext('2d', { willReadFrequently: true });
        }

        try {
            this.livenessCtx.drawImage(videoElement, 0, 0, 64, 48);
            const imageData = this.livenessCtx.getImageData(0, 0, 64, 48);
            const currentFrame = imageData.data;
            
            let isLive = true;

            if (this.lastFrameData) {
                let diff = 0;
                let samples = 0;
                
                // Sample pixels (RGBA) to calculate average difference
                for (let i = 0; i < currentFrame.length; i += 16) {
                    diff += Math.abs(currentFrame[i] - this.lastFrameData[i]); // R
                    diff += Math.abs(currentFrame[i+1] - this.lastFrameData[i+1]); // G
                    diff += Math.abs(currentFrame[i+2] - this.lastFrameData[i+2]); // B
                    samples += 3;
                }
                
                // Average difference per color channel per sampled pixel
                const avgDiff = diff / samples;
                
                // If avgDiff is extremely low (< 1), it's likely a static image
                if (avgDiff < 1.0) {
                    this.staticFrameCount++;
                } else {
                    this.staticFrameCount = 0;
                }

                // If static for 15 consecutive frames, consider it not live (frozen image)
                if (this.staticFrameCount > 15) {
                    isLive = false;
                }
            } else {
                this.staticFrameCount = 0;
            }

            // Copy current frame to last frame for next comparison
            this.lastFrameData = new Uint8ClampedArray(currentFrame);
            
            return isLive;
        } catch (e) {
            console.error("Liveness check error:", e);
            return true; // Default to true if canvas drawing fails
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
     * Calculates Euclidean distance between two points
     */
    _getDistance(p1, p2) {
        return Math.sqrt(Math.pow(p1.x - p2.x, 2) + Math.pow(p1.y - p2.y, 2));
    }

    /**
     * Calculates Eye Aspect Ratio (EAR) for blink detection
     * Based on 6 landmarks per eye
     */
    _calculateEAR(eyePoints) {
        // Vertical distances
        const v1 = this._getDistance(eyePoints[1], eyePoints[5]);
        const v2 = this._getDistance(eyePoints[2], eyePoints[4]);
        // Horizontal distance
        const h = this._getDistance(eyePoints[0], eyePoints[3]);
        
        return (v1 + v2) / (2.0 * h);
    }

    /**
     * Passive Anti-Spoofing: Rigidity Analysis
     * A printed photo or displayed image on a phone is a perfectly flat 2D plane.
     * When it moves, the distances between landmarks scale perfectly proportionally.
     * A real 3D human face has micro-variations (jitter) in muscle tension and perspective.
     * This tracks the normalized distance between the eyes over multiple frames.
     * If the standard deviation is extremely low (too perfect), it's a rigid 2D spoof.
     */
    checkSpoofing(landmarks, boundingBox) {
        if (!landmarks || !boundingBox) return true;

        if (!this.eyeDistanceHistory) {
            this.eyeDistanceHistory = [];
        }

        const leftEye = landmarks.positions[36];
        const rightEye = landmarks.positions[45];
        
        // Distance between eyes
        const eyeDist = this._getDistance(leftEye, rightEye);
        
        // Normalize by bounding box width to account for moving closer/further from camera
        const normalizedDist = eyeDist / boundingBox.width;

        this.eyeDistanceHistory.push(normalizedDist);
        if (this.eyeDistanceHistory.length > 20) {
            this.eyeDistanceHistory.shift(); // Keep last 20 frames
        }

        // Need at least 10 frames to analyze variance
        if (this.eyeDistanceHistory.length >= 10) {
            // Calculate Standard Deviation
            const mean = this.eyeDistanceHistory.reduce((a, b) => a + b) / this.eyeDistanceHistory.length;
            const variance = this.eyeDistanceHistory.reduce((a, b) => a + Math.pow(b - mean, 2), 0) / this.eyeDistanceHistory.length;
            const stdDev = Math.sqrt(variance);

            // A standard deviation below 0.0008 is abnormally rigid (a static photo being moved around)
            // Real faces typically have a stdDev of 0.002 to 0.010 due to 3D micro-movements and neural net jitter
            if (stdDev < 0.0008) {
                this.isSpoofDetected = true;
                return false; // Spoof detected!
            } else {
                this.isSpoofDetected = false;
            }
        }
        
        return !this.isSpoofDetected;
    }

    /**
     * Checks if the user has performed the requested active liveness action (blink twice, smile, or turn head)
     */
    checkActiveLiveness(landmarks) {
        if (!landmarks || this.livenessAction === 'none' || this.livenessActionCompleted) {
            return this.livenessActionCompleted;
        }

        this.framesProcessed = (this.framesProcessed || 0) + 1;
        
        // If they take too long (e.g. 150 frames = ~5 seconds), they might be stuck in a bad baseline
        // Reset the baseline so they have another chance to start from a neutral face
        if (this.framesProcessed > 150) {
            this.baselineEAR = null;
            this.baselineMouthRatio = null;
            this.baselineYaw = null;
            this.framesProcessed = 0;
            this.actionFrames = 0;
            this.blinkCount = 0;
            this.isEyesClosed = false;
            return false;
        }

        const points = landmarks.positions;

        // Calculate Eye Aspect Ratio
        const leftEye = points.slice(36, 42);
        const rightEye = points.slice(42, 48);
        const avgEAR = (this._calculateEAR(leftEye) + this._calculateEAR(rightEye)) / 2.0;

        // Calculate Mouth Aspect Ratio
        const mouthLeft = points[48];
        const mouthRight = points[54];
        const mouthTop = points[51];
        const mouthBottom = points[57];
        
        const faceLeft = points[0];
        const faceRight = points[16];
        
        const mouthWidth = this._getDistance(mouthLeft, mouthRight);
        const mouthHeight = this._getDistance(mouthTop, mouthBottom);
        const faceWidth = this._getDistance(faceLeft, faceRight);

        const mouthRatio = mouthWidth / faceWidth;
        const mar = mouthHeight / mouthWidth;

        // Calculate Yaw
        const noseTip = points[30];
        const leftEyeCenter = points[36];
        const rightEyeCenter = points[45];
        const faceCenterX = (leftEyeCenter.x + rightEyeCenter.x) / 2;
        const eyeDistance = Math.abs(rightEyeCenter.x - leftEyeCenter.x);
        const noseOffset = noseTip.x - faceCenterX; // Positive is right turn, Negative is left turn
        const yawRatio = noseOffset / eyeDistance;

        // Phase 1: Establish a solid baseline (first 15 frames)
        if (this.framesProcessed <= 15) {
            if (this.baselineEAR === null) this.baselineEAR = avgEAR;
            else this.baselineEAR = this.baselineEAR * 0.8 + avgEAR * 0.2;
            
            if (this.baselineMouthRatio === null) this.baselineMouthRatio = mouthRatio;
            else this.baselineMouthRatio = this.baselineMouthRatio * 0.8 + mouthRatio * 0.2;
            
            if (this.baselineYaw === null) this.baselineYaw = yawRatio;
            else this.baselineYaw = this.baselineYaw * 0.8 + yawRatio * 0.2;
            
            return false;
        }

        // Phase 2: Check for a significant, dynamic change from their personal baseline
        if (this.livenessAction === 'blink') {
            // A real blink drops the EAR significantly from the open-eye baseline
            const isClosed = avgEAR < (this.baselineEAR - 0.05) && avgEAR < 0.22;
            const isOpen = avgEAR > (this.baselineEAR - 0.02);

            if (isClosed) {
                this.isEyesClosed = true;
                this.actionFrames++;
            } else if (isOpen && this.isEyesClosed) {
                // Must have been closed for at least 1 frame to be a real blink
                if (this.actionFrames >= 1) {
                    this.blinkCount++;
                    // Require 2 blinks to prove it's intentional and not a random camera jitter
                    if (this.blinkCount >= 2) {
                        this.livenessActionCompleted = true;
                    }
                }
                this.isEyesClosed = false;
                this.actionFrames = 0;
            } else if (!isClosed && !isOpen) {
                // Intermediate state, do nothing
            } else {
                this.actionFrames = 0;
            }

        } else if (this.livenessAction === 'smile') {
            // A real smile widens the mouth significantly from the neutral baseline
            const isSmiling = mouthRatio > (this.baselineMouthRatio + 0.04) && mar > 0.12;

            if (isSmiling) {
                this.actionFrames++;
                // Must hold the smile for at least 4 consecutive frames to prove it's not jitter
                if (this.actionFrames >= 4) {
                    this.livenessActionCompleted = true;
                }
            } else {
                this.actionFrames = 0;
            }
        } else if (this.livenessAction === 'turn_left' || this.livenessAction === 'turn_right') {
            // A real head turn involves significant 3D yaw change.
            // 2D photos rotated might change yaw slightly, but not to the degree of a real 3D head turn.
            const yawDelta = yawRatio - this.baselineYaw;
            
            let hasTurned = false;
            if (this.livenessAction === 'turn_left') {
                // Nose points left (negative delta)
                hasTurned = yawDelta < -0.25;
            } else {
                // Nose points right (positive delta)
                hasTurned = yawDelta > 0.25;
            }

            if (hasTurned) {
                this.actionFrames++;
                if (this.actionFrames >= 4) {
                    this.livenessActionCompleted = true;
                }
            } else {
                this.actionFrames = 0;
            }
        }

        return this.livenessActionCompleted;
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
