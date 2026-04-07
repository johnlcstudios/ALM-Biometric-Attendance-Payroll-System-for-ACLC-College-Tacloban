# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0] - 2026-04-06

### Added
- **Multi-Stage Loan Workflow**:
    - Implemented a new status-driven workflow for Loans: `Pending` -> `Approved`/`Rejected` -> `Distributed` -> `Paid`.
    - Added role-based action buttons: HR/Admin handles approval, while Payroll Officer manages distribution and payment marking.
- **Enhanced Status Styling**:
    - Added dedicated CSS classes and color coding for new loan statuses (`Distributed`, `Paid`, `Approved`, `Rejected`) across the Admin, Payroll, and Employee portals.

### Enhanced
- **Payroll Officer Autonomy**:
    - Enabled Payroll Officers to update loan statuses for approved requests, allowing them to track fund distribution and repayments independently of HR.
- **Role-Based UI Logic**:
    - Refined the loan management table to dynamically show or hide action buttons based on the current user's role and the loan's lifecycle stage.
- **System Initialization**:
    - Improved `script.js` to safer handle global variables like `USER_ROLE`, preventing initialization race conditions.

### Fixed
- **Critical Dashboard Loading Error**:
    - Resolved `ReferenceError: FaceManager is not defined` that caused the Payroll Officer and Employee portals to hang on the loading screen.
    - Fixed missing `face-api.js` and `face-api-manager.js` dependencies in `Payroll-Officer.php` and `ess.php`.
- **RBAC Security**:
    - Updated `isAdminOrHR()` in `api.php` to correctly authorize Payroll Officers for status updates, ensuring backend requests are no longer blocked as "Unauthorized".

## [1.3.0] - 2026-04-04

### Added
- **Master Subject List**:
    - Created a new `subjects` table to store master subject data (Code, Description, Units, Hours).
    - Added a `subject_loads` table to manage faculty subject assignments.
    - Implemented UI for managing master subjects and assigning loads to faculty members.
- **CSRF Protection**:
    - Implemented CSRF token validation for all POST requests in `api.php` and `requests.php` to enhance system security.
- **Enhanced ESS Requests**:
    - Added backend support for employees to apply for Loans and Resignations via the Employee Self-Service (ESS) portal.
- **System Settings**:
    - Added a "Configure Loads" shortcut in the settings page for quick access to subject management.

### Changed
- **Employee Management**:
    - Updated the "Add Employee" flow to include a 4-step wizard with subject load assignment for Faculty positions.
- **Database Schema**:
    - Updated `update_db.php` to automatically create `subjects` and `subject_loads` tables if they don't exist.

### Fixed
- **Payroll Calculations**:
    - Refined deduction logic in `run_specialized_payroll` to use company-specific `deduction_per_min` rates.
- **Navigation**:
    - Ensured "Subject Load Management" is accessible for both Admin/HR and Payroll Officer roles.

## [1.2.0] - 2026-04-02

### Added
- **Kiosk Enhancements**:
    - Added Lunch Out and Lunch In support with configurable time windows.
    - Added late detection using company grace period with persisted late minutes.
    - Added server-time sync for kiosk UI clock/action window decisions.
- **Specialized Payroll**:
    - Implemented Faculty Payroll (17-column format) and Utility Payroll (15-column format) with backend calculations and UI rendering.
    - Added Print and Export (PDF) options matching the table formats.
    - Added Payroll Processing filter to run payroll per category/position or all employees.
- **Allowances & Deductions**:
    - Implemented Allowances and Deductions pages with bulk “Apply to All” assignment.
    - Added Leave Balances bulk “Apply to All” action.
- **Role-Based Access**:
    - Added Payroll Officer role with dedicated dashboard and parity access to required admin/payroll features.
- **UI Pages Refactor**:
    - Split Admin/HR and Payroll Officer sections into separate PHP page files organized by role folders.

### Changed
- **Attendance Logs UI**:
    - Rebuilt attendance logs table layout and ensured Lunch Out/In and Late minutes display correctly and consistently.
- **Employee Enrollment**:
    - Reworked “Add Employee” into a multi-step wizard with stronger input validation.
- **Employee Portal (ESS)**:
    - Updated Employee Dashboard layout to a card-based design with recent payroll activity table.

### Fixed
- **Kiosk Reliability**:
    - Fixed kiosk console errors and undefined UI fields (Attendance/Absences/Employee ID).
    - Improved face recognition stability and prevented duplicate face enrollment.
    - Improved match ambiguity handling using a ratio-based approach.
- **Exports**:
    - Fixed empty exported payroll tables by aligning backend queries and ensuring latest payroll data is loaded before export/print.
- **RBAC & Navigation**:
    - Fixed Payroll Officer redirection issues and ensured correct role navigation.

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
Last Update: 04/02/26 03:50PM ***New Updated Tasks***

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

### Testing Team — Task Backlog (Priority-Ordered)  
- **P0** Build regression test checklist (manual) for every core flow: login/roles, employee CRUD, face enrollment, kiosk scan sequence, attendance logs, payroll runs, exports, settings.  
- **P0** Validate RBAC + company isolation: try to access other company data, test Payroll Officer parity, verify “employee” cannot access admin endpoints/pages.  
- **P0** Kiosk accuracy test plan: lighting scenarios, distance, angle, glasses/mask, multiple faces, replay attempts, duplicate enrollment attempts; record FAR/FRR observations.  
- **P0** Time-window testing: work start/end + lunch windows + grace period; verify allowed/blocked actions and correct statuses (Late minutes, Lunch Out/In columns).  
- **P0** Payroll correctness test cases: fixed salary, absences, late deductions, OT %, allowances/deductions/loans; verify period grouping and “latest” retrieval.  
- **P1** Export/print verification: ensure exported tables match UI columns exactly (Faculty 17 / Utility 15), test exporting without visiting pages first, test “latest” auto-load.  
- **P1** Data integrity testing: bulk “Apply to All” (allowance/deduction/leave) with failures/retries; ensure no partial updates; verify transaction safety.  
- **P1** Cross-browser testing: Chrome/Edge + at least one other browser; camera permissions; print layout; PDF generation.  
- **P2** Performance testing: large employee list, large attendance logs, repeated kiosk scans; measure load times and UI responsiveness.  
- **P2** Security testing (basic): input fuzzing (dates/amounts), unauthorized POSTs, session expiry behavior, CSRF-like actions, rate-limit attempts for kiosk_scan.  
- **P3** Automation roadmap: identify top 10 stable flows to automate first (API tests for backend, UI smoke tests if feasible).

### Administrative Team (HR/Admin/Payroll Operations) — Task Backlog (Priority-Ordered)  
- **P0** Define official policies: work schedule, lunch windows, grace period rules, overtime policy, deduction rules, leave policies, loan policies.  
- **P0** Master data setup: departments, positions/categories, subject loads list, allowance/deduction categories, default rates.  
- **P0** Biometric enrollment SOP: enrollment station setup, lighting standard, distance guidance, duplicate-face procedure, re-enrollment triggers.  
- **P0** Kiosk operations SOP: daily start-up checklist, camera checks, handling “Not Recognized/Ambiguous”, manual correction protocol.  
- **P0** Payroll run SOP: cutoff periods, validation checklist before payout, approval workflow, who can run payroll, how to handle corrections and re-runs.  
- **P1** Data governance: naming conventions, required employee fields, ID formats, handling of inactive/terminated employees, audit log review routine.  
- **P1** Exception handling: late disputes, missed punches, lunch exceptions, leave approval rules, loan review criteria, resignations workflow.  
- **P1** Reporting requirements: what reports are needed (attendance summaries, payroll history, deductions listing), frequency, and who receives them.  
- **P2** Access management: assign/rotate Payroll Officer access, periodic permission reviews, password reset process.  
- **P2** Compliance & privacy: biometric consent process, retention policy, who can view payroll amounts, incident response procedure.  
- **P3** Training plan: onboarding for HR/Payroll Officer, quick guides for employees, refresher schedule, escalation contacts.

Definitions
**SOP: Standard Operating Procedure**

