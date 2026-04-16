# 📦 OFFLINE SETUP GUIDE - ALM Biometrics System

## ⚠️ Problem

Your system doesn't have internet access, so external CDN resources cannot be loaded:
- ❌ Font Awesome (icons)
- ❌ Google Fonts (Inter font)
- ❌ SweetAlert2 (popup dialogs)

This causes errors like:
```
GET https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css net::ERR_NAME_NOT_RESOLVED
Uncaught ReferenceError: Swal is not defined
```

---

## ✅ Solution

The system has been updated to use **local files** with CDN fallback. Follow these steps:

---

## 🔧 Option 1: Download Dependencies (RECOMMENDED)

### **Step 1: Connect to Internet Temporarily**

You only need internet for 2-3 minutes to download the files.

### **Step 2: Run the Download Script**

```batch
cd "c:\xampp\htdocs\updated biometrics\main3\ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban"
download-dependencies.bat
```

This will download:
- ✅ `js/sweetalert2.all.min.js` (SweetAlert2)
- ✅ `css/all.min.css` (Font Awesome CSS)
- ✅ `webfonts/*.woff2` (Font Awesome font files)

### **Step 3: Verify Files**

Check that these files exist:
```
AI-ML-Test-Bench/
├── js/
│   └── sweetalert2.all.min.js          ← Should be ~200KB
├── css/
│   └── all.min.css                      ← Should be ~100KB
└── webfonts/
    ├── fa-solid-900.woff2               ← Should be ~100KB
    ├── fa-regular-400.woff2             ← Should be ~30KB
    └── fa-brands-400.woff2              ← Should be ~90KB
```

### **Step 4: Test the Application**

Open your browser and go to:
```
http://localhost/ALM-Biometrics/
```

All icons and popups should now work! 🎉

---

## 📥 Option 2: Manual Download

If the script doesn't work, download manually:

### **1. SweetAlert2**

Download from: https://cdn.jsdelivr.net/npm/sweetalert2@11

Save as: `AI-ML-Test-Bench/js/sweetalert2.all.min.js`

### **2. Font Awesome CSS**

Download from: https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css

Save as: `AI-ML-Test-Bench/css/all.min.css`

### **3. Font Awesome Webfonts**

Create folder: `AI-ML-Test-Bench/webfonts/`

Download these files into the `webfonts/` folder:
- https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2
- https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.woff2
- https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.woff2

---

## 🌐 Option 3: Keep Using CDN (Requires Internet)

If you have internet access, the updated `index.php` will automatically:
1. Try to load local files first
2. Fall back to CDN if local files don't exist

**No action needed** - just keep your internet connection active.

---

## 🔍 How It Works

The updated `index.php` now has this structure:

```html
<!-- Font Awesome: Try local first, fallback to CDN -->
<link rel="stylesheet" href="css/all.min.css" 
      onerror="this.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'">

<!-- SweetAlert2: Try local first, fallback to CDN -->
<script src="js/sweetalert2.all.min.js" 
        onerror="this.src='https://cdn.jsdelivr.net/npm/sweetalert2@11'"></script>

<!-- Google Fonts: Try CDN first, fallback to local -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" 
      rel="stylesheet" 
      onerror="this.href='css/inter-fonts.css'">
```

**Priority Order:**
1. **Local files** (works offline)
2. **CDN** (works online if local fails)

---

## 🎨 Font Fallback

If Google Fonts is unavailable, the system will use system fonts that look similar to Inter:

```css
font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, 
             "Helvetica Neue", Arial, sans-serif;
```

This ensures the application still looks good even without internet!

---

## ❌ Troubleshooting

### **Problem: Icons not showing**

**Solution:**
1. Check if `css/all.min.css` exists
2. Check if `webfonts/` folder exists with `.woff2` files
3. Run `download-dependencies.bat` again

### **Problem: "Swal is not defined" error**

**Solution:**
1. Check if `js/sweetalert2.all.min.js` exists
2. The file should be ~200KB
3. Run `download-dependencies.bat` to download it

### **Problem: Download script fails**

**Solution:**
1. Make sure you have internet access
2. Check if PowerShell is enabled
3. Try manual download (Option 2)
4. Check your firewall/antivirus settings

---

## 📋 File Checklist

After setup, you should have these files:

```
AI-ML-Test-Bench/
├── js/
│   ├── chart.min.js                      ✅ Already exists
│   ├── face-api.min.js                   ✅ Already exists
│   ├── face-api-manager.js               ✅ Already exists
│   ├── jspdf.umd.min.js                  ✅ Already exists
│   ├── jspdf.plugin.autotable.min.js     ✅ Already exists
│   ├── script.js                         ✅ Already exists
│   └── sweetalert2.all.min.js            ⬅️ NEEDS DOWNLOAD
│
├── css/
│   ├── style.css                         ✅ Already exists
│   ├── login-style.css                   ✅ Already exists
│   ├── all.min.css                       ⬅️ NEEDS DOWNLOAD
│   └── inter-fonts.css                   ✅ Already exists (fallback)
│
└── webfonts/
    ├── fa-solid-900.woff2                ⬅️ NEEDS DOWNLOAD
    ├── fa-regular-400.woff2              ⬅️ NEEDS DOWNLOAD
    └── fa-brands-400.woff2               ⬅️ NEEDS DOWNLOAD
```

---

## 🚀 Quick Start

**Fastest way to fix the issue:**

1. **Connect to internet**
2. **Run this command:**
   ```batch
   cd "c:\xampp\htdocs\updated biometrics\main3\ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban"
   download-dependencies.bat
   ```
3. **Wait 2-3 minutes**
4. **Done!** ✅

---

## 💡 Notes

- ✅ **One-time setup:** You only need to download these files once
- ✅ **Works offline:** After download, no internet needed
- ✅ **Automatic fallback:** System tries local files first
- ✅ **No code changes needed:** Everything is already updated
- ✅ **System fonts:** If Google Fonts fails, system fonts are used

---

## 📞 Need Help?

If you're still having issues:

1. Check browser console for errors (F12 → Console tab)
2. Verify all files in the checklist exist
3. Make sure XAMPP Apache is running
4. Clear browser cache (Ctrl + Shift + Delete)

---

**Once the dependencies are downloaded, your system will work perfectly offline!** 🎉
