# Troubleshooting: "Failed to load models" Error

## Problem
When you transfer the biometric system to another PC/laptop, the Kiosk and Face Enrollment show **"Failed to load models"** error.

## Root Causes

### 1. **Relative Path Issues**
The face-api.js library tries to load model files from a `models/` directory, but the relative path might not resolve correctly on different machines depending on:
- How XAMPP is installed
- The URL structure being used
- Virtual host configuration

### 2. **Missing MIME Type Configuration**
Apache/XAMPP might not know how to serve `.shard1` and `.json` model files correctly.

### 3. **File Permission Issues**
The models folder or files might not have proper read permissions.

### 4. **Missing Model Files**
Model files weren't copied during the transfer.

---

## Solutions (Applied Automatically)

### ✅ Fix 1: Multiple Path Fallback
The system now automatically tries multiple paths:
```javascript
- Configured path
- ./models/
- models/
- /models/
- Absolute path based on current URL
```

### ✅ Fix 2: MIME Type Configuration
Created `models/.htaccess` file with proper MIME types:
```apache
AddType application/json .json
AddType application/octet-stream .shard1
```

### ✅ Fix 3: Better Error Messages
- Detailed error messages in browser console (F12)
- User-friendly alerts with troubleshooting tips
- Visual status indicators during loading

### ✅ Fix 4: Diagnostic Tool
Created `check-models.html` - a setup checker that verifies:
- Models directory accessibility
- All required model files present
- Face-api.js library loaded
- Model files readable
- Browser camera support
- Secure context (HTTPS/localhost)

---

## Manual Troubleshooting Steps

### Step 1: Verify Model Files Exist
Navigate to: `c:\xampp\htdocs\updated biometrics\main3\ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban\AI-ML-Test-Bench\models\`

Ensure these files are present:
```
✓ tiny_face_detector_model-weights_manifest.json
✓ tiny_face_detector_model-shard1
✓ ssd_mobilenetv1_model-weights_manifest.json
✓ ssd_mobilenetv1_model-shard1
✓ ssd_mobilenetv1_model-shard2
✓ face_landmark_68_model-weights_manifest.json
✓ face_landmark_68_model-shard1
✓ face_recognition_model-weights_manifest.json
✓ face_recognition_model-shard1
✓ face_recognition_model-shard2
```

**If missing:** Copy from the original system or backup.

---

### Step 2: Run the Diagnostic Tool
1. Open browser
2. Navigate to: `http://localhost/AI-ML-Test-Bench/check-models.html`
   (Adjust URL based on your setup)
3. Review the check results
4. Fix any reported issues

---

### Step 3: Check XAMPP Apache Configuration

#### Enable mod_rewrite and mod_headers
1. Open: `c:\xampp\apache\conf\httpd.conf`
2. Find and uncomment (remove `#`) these lines:
```apache
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
LoadModule expires_module modules/mod_expires.so
```
3. Restart Apache

#### Verify DocumentRoot
1. Open: `c:\xampp\apache\conf\httpd.conf`
2. Find `DocumentRoot` directive
3. Ensure it points to: `c:/xampp/htdocs`

---

### Step 4: Check Browser Console for Errors

1. Open Kiosk or Face Enrollment page
2. Press **F12** to open Developer Tools
3. Go to **Console** tab
4. Look for errors like:
   - `404 Not Found` - Model files missing or wrong path
   - `CORS error` - Need .htaccess file
   - `MIME type error` - Apache not configured correctly
   - `Network error` - Server not running or firewall blocking

---

### Step 5: Test Direct Model Access

Try accessing a model file directly in browser:
```
http://localhost/AI-ML-Test-Bench/models/tiny_face_detector_model-weights_manifest.json
```

**Expected:** You should see JSON content or download the file.

**If you get 404:** 
- Models folder is in wrong location
- Apache not configured correctly
- File permissions issue

---

### Step 6: Verify XAMPP is Running

1. Open XAMPP Control Panel
2. Ensure **Apache** is **started** (green)
3. Click "Admin" next to Apache
4. Should open: `http://localhost/dashboard/`

---

### Step 7: Check File Permissions (Windows)

1. Right-click on `models` folder
2. Select **Properties**
3. Go to **Security** tab
4. Ensure **Users** or **Everyone** has **Read** permission
5. If not, click **Edit** → Add **Read** permission

---

### Step 8: Clear Browser Cache

Sometimes old cached files cause issues:
1. Press **Ctrl + Shift + Delete**
2. Select **Cached images and files**
3. Click **Clear data**
4. Refresh page (**Ctrl + F5**)

---

### Step 9: Test on Different Browser

Try accessing the system on:
- Google Chrome (recommended)
- Mozilla Firefox
- Microsoft Edge

Some browsers have stricter security policies.

---

### Step 10: Use Localhost, Not IP Address

Camera API requires secure context:
- ✅ `http://localhost/...`
- ✅ `http://127.0.0.1/...`
- ✅ `https://your-domain.com/...`
- ❌ `http://192.168.1.100/...` (won't work for camera)

---

## Quick Fix Checklist

- [ ] XAMPP Apache is running
- [ ] All model files exist in `models/` folder
- [ ] `.htaccess` file exists in `models/` folder
- [ ] `js/face-api.min.js` file exists
- [ ] Browser console shows no 404 errors
- [ ] Can access model files directly in browser
- [ ] Using `localhost` or `127.0.0.1`
- [ ] Browser has camera permissions
- [ ] No other application is using the camera

---

## Still Not Working?

### Enable Debug Mode

Add this to kiosk.php before the script tag:
```javascript
<script>
// Enable verbose logging
localStorage.setItem('debug', 'true');
</script>
```

### Check Apache Error Logs

1. Open: `c:\xampp\apache\logs\error.log`
2. Look for recent errors
3. Search for "404", "Forbidden", or "Permission denied"

### Reinstall Face-API Models

If model files are corrupted:
1. Download fresh models from: https://github.com/justadudewhohacks/face-api.js/tree/master/weights
2. Replace all files in `models/` folder
3. Clear browser cache
4. Retry

---

## Prevention for Future Transfers

When copying the system to another PC:

1. **Copy ENTIRE folder structure** - Don't skip any files
2. **Verify models/ folder** - Check all 10 files are present
3. **Run check-models.html** - Before testing kiosk
4. **Use same XAMPP version** - Avoid compatibility issues
5. **Test on localhost first** - Before deploying to network

---

## Technical Details

### How Face-API.js Loads Models

```javascript
faceapi.nets.tinyFaceDetector.loadFromUri('models/')
```

This actually requests:
- `models/tiny_face_detector_model-weights_manifest.json`
- Then loads: `models/tiny_face_detector_model-shard1`

The manifest file tells face-api.js which shard files to load and how to parse them.

### Why Multiple Path Attempts?

Different server configurations resolve relative paths differently:
- Some need `./models/`
- Some need `/models/`
- Some need absolute URLs

The updated code tries all common patterns automatically.

---

## Success Indicators

When everything is working:
1. ✅ Console shows: `FaceManager: Models loaded successfully from: ...`
2. ✅ Kiosk shows: "AI Models Loaded ✓"
3. ✅ Camera starts automatically
4. ✅ Face detection box appears on screen
5. ✅ No errors in browser console

---

**Last Updated:** April 2026  
**System Version:** ALM Biometric Attendance & Payroll System
