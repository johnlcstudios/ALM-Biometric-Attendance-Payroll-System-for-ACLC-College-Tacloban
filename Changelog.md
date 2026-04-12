# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.9] **Saturday Release**  - 2026-04-12

### Merge
- Merged `Frontend` branch into `main` branch.

### Added
- **Modernized Notification System**:
    - Replaced all legacy PHP `echo` alerts and native JavaScript `alert`, `confirm`, and `prompt` dialogs with a modern GUI-based notification system powered by **SweetAlert2**.
    - Implemented `backend/notifications.php` helper to handle server-side triggered alerts and toasts.
    - Standardized success, error, warning, and info messages with consistent icons and auto-dismissing toasts for non-critical feedback.
- **Frontend Integration**:
    - Merged the latest enhancements and bug fixes from the `Frontend` branch into the core system.

### Changed
- **System Initialization**:
    - Refactored `js/script.js` to integrate SweetAlert2 modals for critical user interactions, ensuring a smoother and more professional user experience.
    - Updated `setup-db.php`, `backend/db.php`, and `backend/update_db.php` to use GUI-based progress reporting.

## [1.4.5] - 2026-04-09

### Added
- **Centralized Admin Hub**:
    - Completed transition of administrative functions to the new centralized dashboard.
    - Integrated Overtime approval workflow and legacy features fully operational within the new hub.
    - Implemented a complete automated payroll calculation engine and robust PDF reporting tools.

### New To Fix Tasks 
    - Updated To Fix Tasks located at the bottom of the page. Under To Fix Section.
    - Updated 04/09/26 01:57PM

### Enhanced
- **Merged All Branches**:
    - All branches are now merged into a 'main' branch for easier management and development. 
    - Edits and works of the following branches are now in the main branch:
        - Backend
        - Biometric

- **System-Wide Stability**:
    - Performed a comprehensive backend audit to secure API data handling, parameter validation, and access control logic.
    - Made frontend biometric attendance logic fault-tolerant to gracefully handle unhandled exceptions and prevent runtime crashes.

### Fixed
- **Database Query Alignment**:
    - Resolved critical `SQLSTATE[HY093]: Invalid parameter number` errors across the backend by auditing and correcting dynamic SQL queries, matching placeholders with bound parameters.
- **Settings & UI Freezes**:
    - Fixed a bug where the Settings page would become stuck in a loading state by fixing data fetching operations and ensuring `saveSettings` logic executes.
- **Subject Load Management**:
    - Fixed broken CRUD operations and non-functional buttons for Subject Loads by implementing missing Javascript glue code (`saveMasterSubject`, `editMasterSubject`, `deleteMasterSubject`, `saveSubjectLoad`, `deleteSubjectLoad`) to securely hit existing backend endpoints.

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
Last Update: 04/09/26 01:57PM ***New Updated Tasks***

### Biometrics — Task Backlog (Priority-Ordered)
**P0** Fix face enrollment UI redundancy: remove duplicate text, clarify instructions, rename to “Face Registration”.

**P0** Ensure enrollment flow stability: prevent broken states during face capture and submission.

**P1** Improve enrollment UX: clearer feedback for successful/failed enrollment.

--- 

### Backend — Task Backlog (Priority-Ordered)
**P0** Fix dashboard data mismatch: ensure attendance logs and dashboard graphs use same data source and queries.

**P0** Fix empty report exports: ensure data is fetched before generating downloadable files.

**P0** Fix data not loading on initial page load (Payroll, Deduction, Allowance, Faculty pages): enforce automatic data fetch on endpoint call.

**P0** Fix export/print/PDF generation: ensure backend handlers return correct file output with valid data.

**P0** Fix delayed data rendering issue: eliminate dependency on trigger actions (Export/Print) before data appears.

**P1** Add pagination support: implement LIMIT/OFFSET for Employees, Payroll, Attendance, and Tables.

**P1** Standardize API responses: consistent JSON format for all modules.

**P1** Add error handling: return proper status codes and debug messages.

**P2** Optimize queries: improve performance for large datasets.

--- 

### Frontend — Task Backlog (Priority-Ordered)
**P0** Fix all non-functional buttons:
    - Attendance Logs (Action buttons)
    - Payroll (Export/Print)
    - PDF actions

**P0** Fix data rendering issues: ensure tables load data on page initialization.

**P0** Fix search functionality (Attendance Logs): restrict search to Name/ID only (exclude STATUS).

**P0** Fix async handling: ensure proper fetch/await logic and prevent race conditions.

**P0** Prevent double-trigger bugs: disable buttons during API calls.

**P1** Implement pagination UI across all tables.

**P1** Add table limits and consistent layouts.

**P1** Remove redundant text across pages.

**P1** Improve UI consistency (buttons, tables, labels).

**P2** Improve loading states (spinners, skeletons).

**P2** Enhance responsiveness for large tables.

--- 

### Testing Team — Task Backlog (Priority-Ordered)

**P0** Validate all CRUD operations:
    - Subject Load (Add/Edit/Delete)
    - Employee records

**P0** Test data loading behavior:
    - Ensure all pages load data without manual triggers

**P0** Verify export/print functionality:
    - Files contain correct and complete data

**P0** Test dashboard accuracy:
    - Compare graphs vs attendance logs

**P0** Validate button functionality across all modules

**P1** Test pagination behavior:
    - Correct page counts and data consistency

**P1** Verify search functionality (correct filtering fields)

**P1** Regression testing across all modules after fixes

**P2** Cross-browser testing (Chrome, Edge, Opera, Brave)

--- 


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
