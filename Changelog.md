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

### Biometrics — Task Backlog (Priority-Ordered)

- ***P0*** Enforce duplicate-face blocking everywhere: server-side duplicate check on enrollment, block ambiguous matches at kiosk, log attempts.
- ***P0*** Improve enrollment quality: multi-sample descriptor averaging, stability checks, minimum confidence gating, “too dark/too far” guidance.
- ***P0*** Time integrity: kiosk UI time sync to server, use server time for logs/windows/cutoffs.
- ***P0*** Liveness hardening: multi-step liveness (blink + head turn + mouth), random prompts, anti-replay timing checks.
- ***P1*** Matching calibration: tune thresholds per camera/site, store configurable thresholds per company, add test mode for calibration.
- ***P1*** Multi-face handling: reject when >1 face detected, show instruction.
- ***P1*** Device handling: camera selection UI, fallback strategies, better permission error UX, auto-stop streams.
- ***P2*** Monitoring: track FAR/FRR metrics (false accept/reject), ambiguous rates, model load errors, device failures.
- ***P2*** Performance: optimize inputSize dynamically, throttle detection, reduce CPU load on weaker devices.
- ***P3*** Advanced improvements: optional higher-accuracy detector pipeline (if hardware allows), periodic re-enrollment prompts.

### Backend — Task Backlog (Priority-Ordered)

- ***P0*** Authorization/RBAC audit: ensure company scoping on every endpoint, prevent data leakage, unify role checks (Admin/HR/Payroll Officer).
- ***P0*** Data integrity & transactions: wrap bulk operations + payroll runs + “apply to all” in transactions, prevent partial writes.
- ***P0*** Payroll correctness validation: unit-test payroll formulas (absences, late, OT, allowances, deductions, loans), consistent period handling.
- ***P0*** “Latest period” + filtering consistency: standardize period keys, add “latest” fetch patterns to avoid empty exports/pages.
- ***P1*** Input validation standard: centralized validation for dates, amounts, IDs; reject invalid payloads; consistent JSON shape.
- ***P1*** Database indexing: company_id + employee_id + period + log_date indexes for attendance/payroll queries.
- ***P1*** Audit trail: who changed settings, ran payroll, enrolled face, approved/denied leave/loans; immutable logs.  
- ***P1*** Timezone standardization: set server timezone + store timestamps consistently; avoid client-time dependency.
- ***P2*** Pagination + query optimization: attendance/employees/payroll endpoints support paging/filtering/search server-side.
- ***P2*** Reliability: better error messages, retry-safe idempotent endpoints for payroll runs, rate limiting for kiosk scans.
- ***P3*** Backup/restore tooling: scheduled backups, download/export, restore verification.

### Frontend — Task Backlog (Priority-Ordered)

- ***P0*** Stability after page-splitting: guard DOM lookups, prevent null crashes, ensure each page initializes only its own widgets.
- ***P0*** Data loading lifecycle: load the right datasets per page, show spinners, disable buttons while requests run, prevent double submits.
- ***P0*** Export/Print reliability: ensure data is fetched before export, unify headers with table schema, handle “latest” automatically.
- ***P1*** UX consistency: unify cards/tables/buttons styling across Admin/HR/Payroll Officer/Employee, consistent empty states.
- ***P1*** Responsive wide tables: sticky columns, horizontal scroll UX, A3 print layouts, column grouping.
- ***P1*** Better forms: validation messages, date-range helpers, safe defaults, confirmations, undo for bulk actions.
- ***P2*** Performance: virtualized tables for large datasets, debounce search, caching (per page), reduce re-renders.
- ***P2*** Accessibility: keyboard navigation, contrast, ARIA labels, focus management in modals.
- ***P3*** Observability: frontend error boundary/log capture for crashes, user-friendly error banners.

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
