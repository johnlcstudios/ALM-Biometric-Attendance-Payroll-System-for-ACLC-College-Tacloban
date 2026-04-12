# ALM Biometric Attendance & Payroll System for ACLC College Tacloban

## Full System Description

The **ALM Biometric Attendance & Payroll System** is a comprehensive web-based Human Resource Management (HRM) platform developed by BSIT-3A students of ACLC College Tacloban as part of their Application Lifecycle Management (ALM) course project. The system automates biometric attendance tracking using facial recognition technology and integrates it directly with intelligent payroll computation for multiple employee categories. It supports multi-tenancy, allowing multiple organizations (companies) to operate independently within the same platform.

**Technology Stack:**
- **Backend**: PHP 8+ with PDO/MySQL (XAMPP local deployment)
- **Frontend**: HTML5, CSS3 (modern glassmorphism UI), vanilla JavaScript
- **Biometrics**: face-api.js (TensorFlow.js) with local ML models for offline-capable face detection/recognition
- **Database**: MySQL with comprehensive relational schema supporting multi-tenancy
- **Deployment**: XAMPP (Apache + MySQL), browser-based (no additional setup required)
- **PDF Export**: jsPDF + AutoTable for payslip/report generation

The system runs entirely in the browser with local ML models, ensuring privacy and offline kiosk functionality. All core development occurs in the `AI-ML-Test-Bench/` folder, with `main` branch protected for production stability.

## Core Features

### 1. **Multi-Tenant Architecture**
```
Companies (isolated data per organization)
├── Users (Admin/HR/Payroll Officer/Employee)
├── Employees (biometric enrollment, profiles)
├── Attendance Logs (4-column: check-in/out, lunch in/out)
├── Payroll (3 types: General/Faculty/Utility)
├── Requests (Leave/Loans/Resignations)
└── Settings (company-specific policies)
```

### 2. **Advanced Biometric Attendance**
- **Real-time facial recognition** using face-api.js (TinyFaceDetector + FaceRecognitionNet)
- **4-stage attendance logging**: Check-In → Lunch Out → Lunch In → Check-Out
- **Time window validation** (work hours, lunch buffers, checkout grace periods)
- **Late detection** with configurable grace periods and deduction rates
- **Ambiguity detection** (prevents false positives between similar faces)
- **Standalone Kiosk Mode** (`kiosk/`) for public/shared terminals
- **Admin Face Registration** with duplicate face prevention

### 3. **Intelligent Payroll Engine**
- **3 Payroll Types**:
  | Type | Columns | Target | Calculation Logic |
  |------|---------|--------|-------------------|
  | **General** | Standard | All Staff | Pro-rated daily rate × attendance |
  | **Faculty** | 17 cols | Faculty | Load pay + honorarium - absences/lates |
  | **Utility** | 15 cols | Utility Staff | Day rate × days present - deductions |
  
- **Automated Deductions**: SSS/PhilHealth/Pag-IBIG/TIN (auto-skips if no ID provided)
- **Loan Integration**: Auto-deducts approved loans from payroll
- **Bulk Operations**: Apply allowances/deductions/leave balances to all employees

### 4. **Role-Based Access Control (RBAC)**
```
Admin/HR (Full Access)
├── Employee CRUD + Face Enrollment
├── Subject Loads Management
├── All Payroll Processing
├── All Requests Approval
└── System Settings

Payroll Officer (Restricted)
├── View Employees + Reports
├── Run All Payroll Types
├── Manage Allowances/Deductions
├── Approve Loans/Leaves (post-HR)
└── View Attendance

Employee (Self-Service/ESS)
├── Personal Attendance History
├── Payslip Download (PDF)
└── Submit Leave/Loan/Resignation Requests
```

### 5. **Employee Self-Service (ESS) Portal**
- Modern card-based dashboard with key metrics
- Personal attendance history with filtering
- Payslip history with one-click PDF export
- Leave/Loan/Resignation request submission
- Profile view (SSS/PhilHealth/TIN details)
- Password self-reset

### 6. **Kiosk Mode**
- Touch-friendly standalone attendance terminal
- Live camera feed with face detection overlay
- Real-time match feedback (success/match %/unknown)
- Statistics display (personal attendance/absences)
- Server-time synchronization
- Configurable action windows (lunch buffers, etc.)

### 7. **Reporting & Exports**
- **Dashboard Analytics**: Total/Present/Absent/Pending stats
- **PDF Generation**: Payslips, payroll summaries (17/15-col formats)
- **Bulk Operations**: Apply settings to all employees
- **Audit Trail**: All requests track status changes

## Detailed Functionality List

### **Authentication & Onboarding**
1. **Multi-tenant Signup**: Company admin creates organization + HR account
2. **Role-based Login**: Automatic redirection by role (Admin → index.php, Payroll → Payroll-Officer.php, Employee → ess.php)
3. **Password Reset**: Admin bulk-reset + employee self-service
4. **Session Security**: Regenerated session IDs, CSRF protection

### **Employee Lifecycle**
```
1. ADD Employee → Auto-generate EMP001 ID + user account
2. Face Enrollment → 128D descriptor storage + duplicate check
3. Daily Attendance → Biometric kiosk/web
4. Monthly Payroll → Auto-compute by category
5. Requests → Employee submits → HR approves → Payroll deducts
6. Resignation → Status workflow → Auto-mark 'Resigned'
```

### **Attendance Workflow**
```
1. Employee approaches kiosk/webcam
2. Face-api detects landmarks → extracts 128D descriptor  
3. Euclidean distance matching vs registered faces
4. Best match if <0.60 distance + ambiguity ratio check
5. Determine next action: CheckIn/LunchOut/LunchIn/CheckOut
6. Time window validation + buffer enforcement
7. Log status (Late/Half-Day/Absent) + late minutes calculation
```

### **Payroll Computation Examples**
```
FACULTY (17 Columns):
Basic/2 + Load Pay + OT + Diff + Sub + Adj+ + Honorarium 
- (Absences × daily rate) - (Late mins × rate/min) - HDMF/Loans/MP2 = NET PAY

UTILITY (15 Columns): 
(Daily rate × present days) + OT/Holiday + Adj+ 
- Late - Adj- - HDMF/Loans/Cash Advance = ATM Transfer
```

### **Administrative Tools**
- **Subject Master List**: Code/Description/Units/Hours CRUD
- **Bulk Assignment**: Allowances/Deductions/Leave Balances to ALL
- **Company Settings**: Timezone/Work hours/Lunch windows/Deduction rates/Biometric thresholds
- **Database Auto-Migration**: `update_db.php` syncs schema changes

## Deployment & Usage

```
1. XAMPP → Start Apache + MySQL
2. http://localhost/.../AI-ML-Test-Bench/
3. Signup → Login → Add Employees → Face Enroll → Run Payroll
4. Kiosk: kiosk/kiosk.php (public terminal)
5. Development: Edit AI-ML-Test-Bench/, git push Frontend/Backend/Biometric
```

## Database Schema (Key Tables)
```
companies (multi-tenant config)
users (RBAC roles)
employees (core profiles + face_descriptor JSON)
attendance (4 time columns + late_minutes)
payroll (3 types + JSON breakdown)
leave_requests/loans/resignations (status workflows)
deductions/allowance_categories + employee assignments
subjects/subject_loads (faculty scheduling)
```

The system represents a production-ready HRM solution with enterprise-grade features like biometric security, multi-tenancy, specialized payroll formats, and comprehensive employee self-service – all delivered through a modern, responsive web interface deployable on standard XAMPP environments.
