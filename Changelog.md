# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
