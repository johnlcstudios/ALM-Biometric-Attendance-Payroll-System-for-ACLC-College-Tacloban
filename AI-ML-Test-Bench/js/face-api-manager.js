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
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ 
                video: { width, height, frameRate: { ideal: 30 } } 
            });
            videoElement.srcObject = this.stream;
            return new Promise((resolve) => {
                videoElement.onloadedmetadata = () => resolve(this.stream);
            });
        } catch (err) {
            console.error("FaceManager: Camera access failed", err);
            if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
                throw new Error("Camera is already in use by another tab or application.");
            }
            throw new Error("Failed to access camera. Please check permissions.");
        }
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

    async captureSamples(videoElement, onProgress = null) {
        const samples = [];
        // Optimization: Use TinyFaceDetector for faster sampling if supported, 
        // but for descriptors we need landmarks which are best with SSD or Tiny + Landmarks.
        // We'll stick to SSD but reduce the number of samples for Kiosk if needed.
        const options = new faceapi.SsdMobilenetv1Options({ minConfidence: this.config.minConfidence });
        
        // Faster sampling: reduce delay and max attempts
        const maxAttempts = 10; 
        for (let i = 0; i < maxAttempts && samples.length < this.config.sampleCount; i++) {
            if (onProgress) onProgress(samples.length + 1, this.config.sampleCount);
            
            const detection = await faceapi.detectSingleFace(videoElement, options)
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (detection && detection.descriptor) {
                samples.push(Array.from(detection.descriptor));
            }
            // Optimization: Reduced delay for faster capture
            if (samples.length < this.config.sampleCount) {
                await new Promise(r => setTimeout(r, 50)); 
            }
        }

        if (samples.length < 1) { // Efficiency: allow single high-quality sample if needed
            throw new Error("Could not capture clear face samples. Please ensure good lighting.");
        }

        // Average descriptors
        const averaged = new Float32Array(128).fill(0);
        for (const s of samples) {
            for (let j = 0; j < 128; j++) averaged[j] += s[j];
        }
        for (let j = 0; j < 128; j++) averaged[j] /= samples.length;
        
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

    /**
     * Estimates face pose (yaw and pitch) based on landmarks
     * @param {faceapi.FaceLandmarks68} landmarks 
     * @returns {Object} { yaw, pitch, isFrontFacing }
     */
    estimatePose(landmarks) {
        const leftEye = landmarks.getLeftEye();
        const rightEye = landmarks.getRightEye();
        const nose = landmarks.getNose();
        const mouth = landmarks.getMouth();
        const jaw = landmarks.getJawOutline();

        // 1. Calculate Yaw (Left/Right rotation)
        // Ratio of distances from nose tip to jaw edges
        const noseTip = nose[6]; // Tip of the nose
        const jawLeft = jaw[0];  // Far left jaw
        const jawRight = jaw[16]; // Far right jaw

        const distLeft = Math.sqrt(Math.pow(noseTip.x - jawLeft.x, 2) + Math.pow(noseTip.y - jawLeft.y, 2));
        const distRight = Math.sqrt(Math.pow(noseTip.x - jawRight.x, 2) + Math.pow(noseTip.y - jawRight.y, 2));
        
        const yawRatio = distLeft / distRight;
        // Ideally 1.0 for front facing. < 0.6 or > 1.6 usually means side view.
        
        // 2. Calculate Pitch (Up/Down rotation)
        // Distance from nose bridge to nose tip vs bridge to chin
        const noseBridge = nose[0];
        const chin = jaw[8];
        const bridgeToTip = Math.abs(noseTip.y - noseBridge.y);
        const bridgeToChin = Math.abs(chin.y - noseBridge.y);
        const pitchRatio = bridgeToTip / bridgeToChin;
        // Ideally around 0.35-0.45. < 0.2 (looking down) or > 0.6 (looking up).

        const isFrontFacing = (yawRatio > 0.6 && yawRatio < 1.6) && (pitchRatio > 0.25 && pitchRatio < 0.55);

        return {
            yaw: yawRatio,
            pitch: pitchRatio,
            isFrontFacing: isFrontFacing
        };
    }
}

// Export for use in both modules
window.FaceManager = FaceManager;
