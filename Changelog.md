# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-03-28

### Added
- **Backend Migration (PHP & MySQL)**:
    - Transitioned from static HTML prototypes to a dynamic PHP-based backend using XAMPP/MySQL.
    - Implemented core backend logic in `api.php` for centralized data handling.
    - Added `db.php` for secure database connectivity using PDO.
- **Authentication & Role-Based Access Control (RBAC)**:
    - Created `login.php` and `signup.php` with session-based authentication.
    - Implemented roles: **HR**, **Payroll**, and **Employee** with role-specific navigation and permissions.
    - Added support for multi-tenancy with a `companies` table, allowing multiple organizations on one platform.
- **Dynamic Admin Hub**:
    - Refactored the dashboard to use live data from the database.
    - Integrated real-time statistics for Total Employees, Present Today, Absent Today, and Pending Leave requests.
    - Added dynamic sections for Employees, Biometrics, Attendance, Payroll, Leave, Loans, Resignations, and Deductions.
- **Database Management**:
    - Created `schema.sql` with a comprehensive relational structure (Companies, Users, Employees, Attendance, Payroll, Leave, Deductions).
    - Added `update_db.php` for automated database schema synchronization and updates.
- **Enhanced Employee Management**:
    - Implemented full CRUD (Create, Read, Update, Delete) operations for employees via the new API.
    - Integrated Face Biometrics enrollment directly into the employee management flow.
- **Employee Self-Service (ESS) Integration**:
    - Connected `ess.php` to the backend for real-time access to personal attendance, payslips, and profile information.

### Changed
- **System Architecture**: Moved from static JSON/Local Storage simulation to a persistent MySQL database.
- **API Flow**: All frontend actions now interact with `api.php` via fetch requests for data persistence.
- **UI Responsiveness**: Updated `style.css` and `script.js` to handle dynamic content loading and state management.

### Fixed
- Resolved issues with data persistence between sessions by implementing server-side sessions.
- Improved security by using hashed passwords and prepared statements for all database queries.

## [1.0.0] - 2026-03-21

### Added
- **Leave Management System**:
    - Added `leave_requests` table to `database.sql`.
    - Implemented admin-side leave review and employee-side leave requests in `payroll-system.html` and `ess.html`.
- **Analytics Dashboard**:
    - Integrated `Chart.js` for visual data representation.
    - Added interactive "Payroll Trends" (line chart) and "Attendance Overview" (doughnut chart) to the admin dashboard.
- **Employee Self-Service (ESS) Portal**:
    - Created `ess.html` for employees to view their own attendance, payslips, and profile.
    - Integrated "Switch to ESS Portal" link in the main payroll dashboard.
- **PDF Export Functionality**:
    - Added `jspdf` and `jspdf-autotable` libraries for browser-based PDF generation.
    - Implemented "Export to PDF" feature for payslips in both the admin and employee views.
- **Database Schema**:
    - Created `database.sql` containing the full relational schema for the system.
    - Includes tables for `employees`, `face_biometrics`, `attendance_logs`, `payroll_runs`, `payslips`, `allowances`, `deductions`, and `faculty_subject_loads`.
    - Added sample data for testing purposes.
- **FaceID Biometric Integration**:
    - Integrated `face-api.js` for browser-based face detection and recognition.
    - Added real-time face tracking and landmark detection in `kiosk/kiosk.html`.
    - Implemented a face verification flow for employee attendance (Time In/Time Out).
    - Created `kiosk/admin_enroll.html` for standalone employee face enrollment.
    - Integrated Face Enrollment directly into `payroll-system.html` sidebar for admin ease of use.
- **Local Model Assets**:
    - Downloaded and configured local copies of `face-api.min.js` and required neural network models (`tiny_face_detector`, `face_landmark_68`, `face_recognition`, `ssd_mobilenetv1`) to allow offline/local operation.
- **Enhanced UI for Kiosk**:
    - Replaced static photo placeholders with live video feeds and canvas overlays.
    - Added dynamic status badges for "Scanning", "Verified", and "Unknown Face" states.

### Changed
- **Kiosk Logic**: Updated `kiosk.html` to use `face-api.js` for employee identification instead of simulated scans.
- **Payroll System UI**: Updated sidebar navigation in `payroll-system.html` to include "Face Enrollment" and added corresponding logic in `Payroll-script.js`.
- **Project Documentation**: Updated `README.md` to reflect new biometric features and added this `Changelog.md`.

### Fixed
- Improved resource management by ensuring camera streams are stopped when navigating away from enrollment pages in the payroll system.

--- 

# To Fix
on Saturday 3/28/26 (full list will be updated on 04/02/26)

### Bioemtrics Team Kiosk
- Face Model and API Integration
- Console Errors
- **Biometric Logs**: Enhance `attendance` logs to record liveness verification stability scores.
- **Lunch Out/In**: Lunch Out and Lunch In are not fully functional.


### Frontend 
- [DONE] Redesign Payroll History to support batch-based view.
- [DONE] Implement high-fidelity Allowances and Earnings page.
- [DONE] Implement high-fidelity Deductions Configuration page.
- [DONE] Implement specialized Faculty Payroll reporting page (17 columns).
- [DONE] Implement specialized Utility Payroll reporting page (15 columns).
- [DONE] Implement high-fidelity Subject Load Management page.
- [DONE] Implement high-fidelity Assign Payroll Officer page.
- [Partial] Prepare for full Backend Integration of new specialized payroll pages.
- Console Errors cleanup.

### Backend
- **Role Management API**: Implement `update_role` endpoint to handle Admin/Payroll/Employee transitions.
- **Settings Persistence API**: Create endpoints to save and retrieve institutional policies (Shift Timings, OT %, Grace Periods).
- **Subject Load CRUD**: Full API support for assigning and managing faculty teaching loads in the database.
- **Precision Deduction Engine**: Develop backend logic to calculate late/undertime penalties using the new Per-Second/Minute/Hour rates.
- **Leave Balance Management**: Finalize and secure the API for administrative updates to employee leave credits.
- **ATM/Non-ATM Payment Logic**: Implement backend support for designating and tracking payment methods in the Utility payroll.
- **Attendance Timing Logic**: Backend validation for Lunch In/Out ranges and Shift Start/End enforcement.
- **Reporting Engine**: Develop backend logic to aggregate, filter, and export attendance and payroll data for institutional reports.
- **Specialized Payroll Logic**: Implement complex server-side calculations for faculty (differentials, substitutions) and utility (OT/Holiday pay, cash advances) payroll cycles.
- **Loan/Leave Review Logic**: Create secure backend workflows for the administrative approval/rejection of employee financial and time-off requests.
- **Faculty Payroll Persistence**: Create API actions to save and retrieve semi-monthly faculty payroll runs.
- **Utility Payroll Persistence**: Create API actions for utility-specific earnings and deductions.
- **Allowance/Deduction CRUD**: Implement full database support for allowance/deduction categories and employee assignments.
- **Batch Metadata**: Update `payroll` table or create a `payroll_batches` table to support the new batch-view aggregation.
- **Loan/Resignation Workflow**: Finalize backend logic for processing loan and resignation requests.
- **Access Control**: Access Control for Payroll, Employee, and SD are not fully functional. Login infos are usernames(can be found on Employees section of HR Dashboard) and autogenerated passwords 'welcome123'
- Full System Integration and Console Errors fix.

### Testing
- Face Model Testing
- Face Model Training
- ML Training
- Console Errors
- List Bugs
- List Missing Features
- List Missing Functions

### Administrative Team
- Updated Gantt Chart (as of 3/28/26)
