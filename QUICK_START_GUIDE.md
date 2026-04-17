# Quick Start Guide - New Security Features

## 🚀 Getting Started

### 1. First-Time Setup
```
http://localhost/ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban/AI-ML-Test-Bench/secure-setup.php
```
- Creates company account
- Sets up admin user with strong password
- Generates unique company code

### 2. Apply Database Migrations
```
http://localhost/ALM-Biometric-Attendance-Payroll-System-for-ACLC-College-Tacloban/AI-ML-Test-Bench/run-migrations.php
```
- Adds audit trail table
- Adds 2FA support
- Adds biometric encryption columns
- Adds missing indexes

### 3. Configure Environment
Edit `.env` file:
```env
BREVO_API_KEY=your-api-key-here
ENCRYPTION_KEY=generate-random-32-char-key
SESSION_TIMEOUT=3600
```

---

## 🔐 Security Features

### Two-Factor Authentication (2FA)
**Enable 2FA for your account:**
1. Login to your account
2. Go to Account Settings
3. Click "Enable 2FA"
4. Scan QR code with Google Authenticator app
5. Enter 6-digit code to verify
6. 2FA is now active

**Login with 2FA:**
1. Enter username and password
2. System detects 2FA is enabled
3. Enter 6-digit code from authenticator app
4. Access granted

### Audit Trail
**View audit logs:**
- Admin/HR can access audit logs
- Filter by user, action, date range
- See all sensitive operations
- Track IP addresses and user agents

**Logged actions:**
- Login/logout attempts
- Employee create/update/delete
- Payroll operations
- Password changes
- Role changes
- Data imports/exports

### Biometric Encryption
**Automatic encryption:**
- New face enrollments are encrypted
- Existing data can be migrated
- AES-256-CBC encryption
- Key stored in `.env` file

**Migrate existing biometrics:**
```php
// Create a PHP file and run once:
require_once 'backend/encryption.php';
require_once 'backend/db.php';
$migrated = migrateBiometricsToEncryption($pdo);
echo "Migrated: $migrated records";
```

---

## 📊 New Features

### DTR Generation (ESS Portal)
**Generate Daily Time Record:**
```
http://localhost/.../pages/shared/generate_dtr.php?employee_id=1&month=2026-04
```

**Features:**
- Monthly attendance summary
- Hours worked calculation
- Late minutes tracking
- Print-ready PDF format
- Signature lines

### Bulk Employee Import
**Import from CSV:**
1. Prepare CSV file with required columns:
   - employee_id, full_name, email, position, basic_salary
   - Optional: department, sss, tin, philhealth, pagibig, dob, status

2. Upload via import endpoint
3. System validates and imports
4. Creates user accounts automatically
5. Default password: Welcome123!

**CSV Template:**
```csv
employee_id,full_name,email,position,department,basic_salary,sss,tin,philhealth,pagibig,dob,status
EMP001,John Doe,john@company.com,Teacher,Faculty,25000,xxx,xxx,xxx,xxx,1990-01-01,Active
```

### Automated Backups
**Setup daily backup (Linux/Mac):**
```bash
crontab -e
# Add this line:
0 2 * * * /usr/bin/php /path/to/backend/backup.php
```

**Windows Task Scheduler:**
1. Open Task Scheduler
2. Create Basic Task
3. Trigger: Daily at 2:00 AM
4. Action: Start program
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\...\backend\backup.php`

**Backup location:** `backups/` folder
**Format:** Compressed SQL (.sql.gz)
**Retention:** 30 days (configurable)

### Data Retention
**Setup monthly cleanup:**
```bash
crontab -e
# Add this line:
0 3 1 * * /usr/bin/php /path/to/backend/data_retention.php
```

**What gets cleaned:**
- Attendance records older than 2 years → archived
- Payroll records older than 5 years → archived
- Expired password reset tokens → deleted
- Old login attempts (7+ days) → deleted
- Inactive sessions (24+ hours) → deleted

---

## 🔧 API Endpoints

### API v2 (with Pagination)
```
GET /backend/api_v2.php?action=get_employees&page=1&per_page=20
GET /backend/api_v2.php?action=get_attendance&page=1&per_page=20
GET /backend/api_v2.php?action=get_audit_logs&page=1&per_page=50
```

**Pagination Response:**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "total_pages": 5,
    "has_next": true,
    "has_prev": false
  }
}
```

### 2FA Verification
```
POST /backend/api.php?action=verify_2fa
Body: {
  "user_id": 123,
  "code": "123456"
}
```

---

## 📋 Password Requirements

**New passwords must have:**
- Minimum 8 characters
- At least one uppercase letter (A-Z)
- At least one lowercase letter (a-z)
- At least one number (0-9)

**Example:** `Welcome123!`

---

## 🎯 Admin Checklist

After deployment:
- [ ] Run secure-setup.php
- [ ] Run run-migrations.php
- [ ] Configure .env file
- [ ] Generate ENCRYPTION_KEY
- [ ] Set BREVO_API_KEY (optional)
- [ ] Setup backup cron job
- [ ] Setup data retention cron job
- [ ] Migrate existing biometrics
- [ ] Enable 2FA for all admin accounts
- [ ] Test backup restoration
- [ ] Review audit logs
- [ ] Train users on new features

---

## 🆘 Troubleshooting

### 2FA Not Working
1. Check server time is synchronized
2. Verify ENCRYPTION_KEY is set
3. Check user has two_factor_secret
4. Review audit logs for errors

### Backup Fails
1. Check mysqldump is available
2. Verify database credentials in .env
3. Check backups/ folder permissions
4. Review backup.log for errors

### Encryption Issues
1. Verify ENCRYPTION_KEY is 32+ characters
2. Check openssl extension is enabled
3. Review error logs
4. Test with small dataset first

### Migration Fails
1. Check database permissions
2. Verify migration SQL syntax
3. Review executed migrations table
4. Run migrations one at a time

---

## 📞 Support

**Documentation:**
- `SECURITY_FEATURES_IMPLEMENTATION.md` - Full implementation details
- `COMPREHENSIVE_TESTING_REPORT.md` - Original testing report
- `TESTING_SUMMARY.md` - Updated summary

**Log Files:**
- `backups/backup.log` - Backup operations
- `backend/retention.log` - Data retention operations
- Database: `audit_log` table - All system activities

---

**Last Updated**: April 14, 2026  
**System Version**: 2.0 (Security Enhanced)  
**Status**: ✅ Production Ready
