# ALM Biometric System - Installer v2.4.0 Update Guide

## Overview

The ALM Biometric Attendance & Payroll System Installer has been updated to version **2.4.0 Build9** with significant improvements and new features.

---

## 🆕 What's New in Installer v2.4

### Version Information
- **Version:** 2.4.0
- **Build:** 9
- **Release Date:** April 2026
- **Credits:** Built with STRESS from BSIT 3A (A.Y. 2025-2026) Batch 2027

### New Features in v2.4

#### 1. **Faculty Level Tracking**
- SHS, College, or Both designation for faculty members
- Integrated into employee registration and payroll
- Automatic filtering by faculty level

#### 2. **Hire Date Management**
- Track employee hire dates
- Payroll protection for employees not yet hired
- Historical reporting by hire date

#### 3. **Resignation Decline Functionality**
- HR/Admin can decline resignation requests
- Add reasons for declining
- Full audit trail of decline actions

#### 4. **Employee Reinstatement**
- Reactivate resigned employees
- Automatic status change back to Active
- Reinstatement tracking (who and when)

#### 5. **Enhanced Face Enrollment**
- **Cross-device compatibility** - Works on all devices
- **Centered UI** - Camera inactive message properly centered
- **Adaptive camera constraints** - Automatic fallback for different devices
- **Retry logic** - 3 attempts for model loading
- **Better error handling** - Specific error messages

#### 6. **Separate Name Fields**
- First Name, Last Name, Middle Initial (separate inputs)
- Auto-generated full name display
- Real-time name formatting
- Better data consistency

#### 7. **Animated Splash Screen**
- Professional startup experience
- Shows only on initial login
- Session-based tracking
- Beautiful gradient design with BSIT 3A credits

#### 8. **Complete Database Schema**
- **NEW:** `complete_schema.sql` - All-in-one migration file
- Includes base schema + all migrations (001-004)
- Faster installation process
- No need to run individual migrations

#### 9. **Frontal Face Detection**
- Kiosk only scans when looking straight
- Improved accuracy for attendance
- Real-time feedback during enrollment

#### 10. **Enhanced Security**
- 2FA authentication support
- Encrypted biometric data
- Comprehensive audit trail
- Rate limiting for login attempts

---

## 📦 Installation Process

### Prerequisites
- **XAMPP** installed (Apache + MySQL running)
- **Windows** operating system
- **Web browser** (Edge or Chrome recommended)

### Installation Steps

1. **Run ALM-Installer.exe**
   - Double-click the installer executable
   - Modern UI with gradient design appears

2. **Configure Paths**
   - **Source Files:** Auto-detected (parent directory of installer)
   - **XAMPP htdocs:** Default `C:\xampp\htdocs` (browse to change)

3. **Database Configuration**
   - ✅ **Automatic Database Setup** (checked by default)
   - Creates `alm_biometrics` database
   - Runs `complete_schema.sql` (all-in-one file)
   - Falls back to schema.sql + migrations if complete_schema.sql not found
   - Applies migrations 001-004 automatically

4. **Click "Install Application"**
   - Files copied to `C:\xampp\htdocs\ALM-Biometrics`
   - Database setup runs (if enabled)
   - Desktop shortcut created
   - Success message displays all v2.4 features

5. **Launch the Application**
   - Double-click desktop shortcut "ALM Biometrics"
   - Or run `ALM-Launcher.exe` from installation directory
   - Opens in Edge/Chrome app mode (standalone window)

---

## 🗄️ Database Setup

### New Approach (v2.4)

The installer now uses **complete_schema.sql** by default:

```
AI-ML-Test-Bench/sql/
├── complete_schema.sql  ← NEW: All-in-one file (recommended)
├── schema.sql           ← Legacy base schema
└── migrations/
    ├── 001_security_improvements.sql
    ├── 002_audit_trail_and_security.sql
    ├── 002_company_code_and_password_reset.sql
    ├── 003_add_profile_picture.sql
    └── 004_alm_features_v2.4.sql
```

### Installation Logic

```
1. Check if complete_schema.sql exists
   ├─ YES → Run complete_schema.sql (faster, single file)
   └─ NO  → Run schema.sql + migrations 001-004 (legacy mode)
```

### What Gets Created

**Tables:**
- companies (with company_code)
- users (with 2FA support)
- employees (with faculty_level, hire_date, profile_picture)
- attendance
- payroll
- leave_requests
- loans
- resignations (with decline tracking)
- deductions
- allowance_categories
- employee_allowances
- employee_deductions
- subjects
- subject_loads
- password_resets
- login_attempts
- audit_log
- user_sessions
- migrations (tracking table)

---

## 🚀 Launcher Features

### ALM-Launcher.exe

The launcher has been updated to:

1. **Check XAMPP Status**
   - Detects if Apache/MySQL is running
   - Auto-starts XAMPP if not running
   - 2.5 second wait for services to boot

2. **Open in App Mode**
   - Opens in Edge/Chrome app mode (standalone window)
   - No browser UI (no address bar, tabs, etc.)
   - Feels like a native desktop application

3. **Version Display**
   - Window title: "ALM Biometrics v2.4.0 - BSIT 3A"
   - Easy to identify the application version

### Browser Priority

```
1. Microsoft Edge (if installed)
2. Google Chrome (if Edge not found)
3. Default browser (fallback)
```

---

## 📋 Features Summary

### Employee Management
- ✅ Separate name fields (First, Last, Middle Initial)
- ✅ Faculty level designation (SHS, College, Both)
- ✅ Hire date tracking
- ✅ Profile pictures
- ✅ Face enrollment (cross-device stable)
- ✅ Government ID tracking (SSS, PhilHealth, TIN, Pag-IBIG)

### Attendance & Payroll
- ✅ Biometric attendance logging
- ✅ Frontal face detection for kiosk
- ✅ Auto payroll calculation (Faculty & Utility)
- ✅ Payroll history and export
- ✅ Faculty subject load management

### Resignation Management
- ✅ Submit resignation requests
- ✅ HR can approve or decline
- ✅ Employee reinstatement
- ✅ Full audit trail

### Security & Access
- ✅ Multi-tenant architecture (companies)
- ✅ Role-based access (Admin, HR, Payroll Officer, Employee)
- ✅ 2FA authentication
- ✅ Encrypted biometric data
- ✅ Rate limiting on login
- ✅ Comprehensive audit logging

### User Experience
- ✅ Animated splash screen on login
- ✅ Modern gradient UI design
- ✅ Responsive on all devices
- ✅ Professional table displays
- ✅ Real-time notifications

---

## 🔧 Technical Details

### Installer Changes

**File:** `web-app/Installer.cs`

**Key Updates:**
- Version badge: `v2.4.0 Build9`
- Features list updated to v2.4 features
- Database setup uses `complete_schema.sql` first
- Fallback to legacy schema + migrations
- Footer shows BSIT 3A credits
- Success message lists all new features

### Launcher Changes

**File:** `web-app/Launcher.cs`

**Key Updates:**
- Window title includes version and credits
- Maintains app mode functionality
- Auto-starts XAMPP if needed

### Database Migration

**New File:** `AI-ML-Test-Bench/sql/complete_schema.sql`

**Contains:**
- Base schema (all tables)
- Migration 001 (security tables)
- Migration 002 (audit trail, 2FA, sessions)
- Migration 003 (profile pictures)
- Migration 004 (faculty level, hire date, resignation tracking)

**Benefits:**
- Single file installation (faster)
- No migration tracking needed for new installs
- All features available immediately
- Reduced complexity

---

## 🎯 Building the Executables

### Prerequisites
- **Windows SDK** or **Visual Studio**
- **C# Compiler** (csc.exe)
- **.NET Framework** 4.5 or higher

### Build Commands

**Installer:**
```batch
cd web-app
csc /target:winexe /out:ALM-Installer.exe /win32icon:ALM-Icon.ico Installer.cs
```

**Launcher:**
```batch
cd web-app
csc /target:winexe /out:ALM-Launcher.exe /win32icon:ALM-Icon.ico Launcher.cs
```

### Quick Build Script

Run `build-executable.bat` in the web-app directory:
```batch
cd web-app
build-executable.bat
```

This will compile both executables with the proper icon.

---

## 📝 Changelog Highlights

### v2.4.0 Build9 (April 2026)

**Added:**
- Faculty level tracking (SHS, College, Both)
- Hire date management
- Resignation decline functionality
- Employee reinstatement
- Separate name fields for employees
- Animated splash screen
- Complete database schema file
- Cross-device face enrollment

**Improved:**
- Face enrollment stability (retry logic, adaptive constraints)
- Camera preview UI (centered placeholders)
- Employee directory table (professional styling)
- Form validation (fixed regex patterns)
- Database installation (single file option)

**Fixed:**
- Invalid regex patterns in government ID fields
- Duplicate renderEmployeeTable function
- 422 errors on employee save
- Face enrollment crashes on different devices

---

## 💡 Tips for Users

### First Time Setup
1. Install using ALM-Installer.exe
2. Enable "Automatic Database Setup"
3. Launch from desktop shortcut
4. Complete the initial setup wizard
5. Create admin account
6. Start adding employees

### Face Enrollment Tips
- Ensure good lighting
- Remove glasses/masks
- Look straight at camera
- Hold still when indicator turns green
- Works on any device with camera

### Best Practices
- Regular database backups
- Keep XAMPP updated
- Use strong passwords
- Enable 2FA for admin accounts
- Review audit logs periodically

---

## 📞 Support

**Common Issues:**

**Q: Camera not working?**
A: Check browser permissions, close other apps using camera, try different browser.

**Q: Database setup failed?**
A: Ensure XAMPP MySQL is running, check MySQL path in installer.

**Q: Launcher doesn't open?**
A: Check if XAMPP Apache is running, verify installation path.

**Q: Face enrollment fails?**
A: Ensure good lighting, look straight, hold still, check camera quality.

---

## 🎓 Credits

**Developed by:** BSIT 3A (A.Y. 2025-2026) Batch 2027  
**Institution:** ACLC College Tacloban  
**System:** ALM Biometric Attendance & Payroll System  
**Version:** 2.4.0 Build9  

*Built with STRESS and lots of coffee ☕*

---

## 📄 License

See `LICENSE` file in the project root for full license terms.

**Copyright © 2026 ALM Biometrics System**
