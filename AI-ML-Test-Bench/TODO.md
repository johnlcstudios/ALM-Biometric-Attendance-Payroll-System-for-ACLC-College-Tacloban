<<<<<<< HEAD
## TODO (ESS - Employee tab Previous/Next)

- [x] Create task tracker file
- [ ] Add Previous/Next navigation buttons on the Employee (My Profile) section
- [ ] Implement JS: switch to previous/next profile tab and update active button styles
- [ ] Ensure Next/Previous work correctly for Faculty vs non-Faculty (skip Faculty tab when not Faculty)
- [ ] Update button enabled/disabled state at boundaries
- [ ] Smoke test in browser
=======
# TODO - Authentication Screens UI Revamp

## Planned edits (phase 1)
- [x] Unify auth styling across login + password reset using the existing auth CSS (`css/login-style.css`) and/or shared components.
- [ ] Refactor `login.php` to remove excessive inline styles where feasible and use consistent button/input/error/loading states.
- [ ] Restyle the **password reset SweetAlert2 flow** so it uses the same visual theme and consistent loading/error presentation.
- [ ] Implement consistent login loading/error UX (disable button + spinner; inline error text).


- [ ] Add/update verification screen UI for 2FA:
  - [ ] Create a dedicated `verify_2fa.php` page (or reuse an existing pattern if present).
  - [ ] Redirect to it when backend returns `require_2fa: true`.
  - [ ] Call `backend/api.php?action=verify_2fa` and show loading/errors.
- [ ] Verify responsiveness (desktop + mobile) for all auth screens.
- [ ] Quick manual test matrix:
  - [ ] Successful login
  - [ ] Wrong credentials
  - [ ] Forgot password flow happy path
  - [ ] Forgot password invalid employee/company
  - [ ] 2FA required → success
  - [ ] 2FA required → wrong code
>>>>>>> 7af2081b1ee90f973fb6a50125075e3ab9385a4f

>>>>>>> 0468767b7c880faedaf5c947bc12c03e864c803e
=======
# TODO - Request & Module Lifecycle Updates

## Task Overview
- Request & Module Lifecycles: Build database tables and API endpoints to handle Leave Requests filtering, Loan Requests (integrating Cash Advances and tracking codes), and purge the obsolete "Reason for Loan Request" fields.
- Database Cleanup: Completely remove/deprecate the current Resignations module from the system and add backend enumerations for the updated Employee Statuses (Active, Inactive, Retired, Resigned, Deceased).

## Progress Tracker

### Phase 1: Employee Status Enumerations
- [x] 1.1 Update employees.status enum to include new statuses: Active, Inactive, Retired, Resigned, Deceased
- [x] 1.2 Create migration script for status changes (update_employee_status_enum.php)
- [ ] 1.3 Update API save_employee validation for new status values

### Phase 2: Leave Request Enhancements
- [x] 2.1 Add filtering columns to leave_requests table (update_leave_requests.php)
- [x] 2.2 Create filtered API endpoint for get_leave_requests
- [ ] 2.3 Update frontend leave filtering UI

### Phase 3: Loan/Cash Advance Integration
- [x] 3.1 Add tracking_code column to loans table (update_loan_cash_advance.php)
- [x] 3.2 Add loan_type column (Cash Advance / Other)
- [x] 3.3 Mark reason field as deprecated
- [ ] 3.4 Update API endpoints for loans with tracking
- [ ] 3.5 Update frontend loan display with tracking codes

### Phase 4: Resignations Module Deprecation
- [x] 4.1 Rename resignations table to _deprecated suffix (deprecate_resignations.php)
- [ ] 4.2 Remove resignation entries from API allowedActions
- [ ] 4.3 Update frontend to hide resignation section
- [x] 4.4 Update employee status change workflow (preserved 'Resigned' status)

### Phase 5: Schema Updates
- [ ] 5.1 Update schema.sql with all changes
- [ ] 5.2 Document deprecation notices
- [ ] 5.3 Create rollback scripts

## Implementation Notes

### Employee Status Enum Values (NEW)
```
'Active'    - Currently employed and working
'Inactive'  - On extended leave or suspended
'Retired'   - Retired from service
'Resigned'   - Resigned from position
'Deceased'  - Passed away
```

### Loan Tracking Code Format
```
CA-YYYYMMDD-XXXXX
Example: CA-20260420-00001
```

### API Changes Required
- Remove from allowedActions: 'update_resignation_status', 'decline_resignation'
- Add new filter params to: get_leave_requests, get_loan_requests
- Add: generate_tracking_code for loans

## Dependencies
- backend/db.php (database connection)
- backend/api.php (API endpoints)
- pages/shared/loans.php (loan frontend)
- pages/shared/resignations.php (resignation frontend)
- ess.php (employee self-service)
- sql/schema.sql (database schema)

---

## TODO (ESS - Employee tab Previous/Next)

- [x] Create task tracker file
- [ ] Add Previous/Next navigation buttons on the Employee (My Profile) section
- [ ] Implement JS: switch to previous/next profile tab and update active button styles
- [ ] Ensure Next/Previous work correctly for Faculty vs non-Faculty (skip Faculty tab when not Faculty)
- [ ] Update button enabled/disabled state at boundaries
- [ ] Smoke test in browser

---

## TODO - Authentication Screens UI Revamp

## Planned edits (phase 1)
- [x] Unify auth styling across login + password reset using the existing auth CSS (`css/login-style.css`) and/or shared components.
- [ ] Refactor `login.php` to remove excessive inline styles where feasible and use consistent button/input/error/loading states.
- [ ] Restyle the **password reset SweetAlert2 flow** so it uses the same visual theme and consistent loading/error presentation.
- [ ] Implement consistent login loading/error UX (disable button + spinner; inline error text).


- [ ] Add/update verification screen UI for 2FA:
  - [ ] Create a dedicated `verify_2fa.php` page (or reuse an existing pattern if present).
  - [ ] Redirect to it when backend returns `require_2fa: true`.
  - [ ] Call `backend/api.php?action=verify_2fa` and show loading/errors.
- [ ] Verify responsiveness (desktop + mobile) for all auth screens.
- [ ] Quick manual test matrix:
  - [ ] Successful login
  - [ ] Wrong credentials
  - [ ] Forgot password flow happy path
  - [ ] Forgot password invalid employee/company
  - [ ] 2FA required → success
  - [ ] 2FA required → wrong code
=======
<<<<<<< HEAD
## TODO (ESS - Employee tab Previous/Next)

- [x] Create task tracker file
- [ ] Add Previous/Next navigation buttons on the Employee (My Profile) section
- [ ] Implement JS: switch to previous/next profile tab and update active button styles
- [ ] Ensure Next/Previous work correctly for Faculty vs non-Faculty (skip Faculty tab when not Faculty)
- [ ] Update button enabled/disabled state at boundaries
- [ ] Smoke test in browser
=======
# TODO - Authentication Screens UI Revamp

## Planned edits (phase 1)
- [x] Unify auth styling across login + password reset using the existing auth CSS (`css/login-style.css`) and/or shared components.
- [ ] Refactor `login.php` to remove excessive inline styles where feasible and use consistent button/input/error/loading states.
- [ ] Restyle the **password reset SweetAlert2 flow** so it uses the same visual theme and consistent loading/error presentation.
- [ ] Implement consistent login loading/error UX (disable button + spinner; inline error text).


- [ ] Add/update verification screen UI for 2FA:
  - [ ] Create a dedicated `verify_2fa.php` page (or reuse an existing pattern if present).
  - [ ] Redirect to it when backend returns `require_2fa: true`.
  - [ ] Call `backend/api.php?action=verify_2fa` and show loading/errors.
- [ ] Verify responsiveness (desktop + mobile) for all auth screens.
- [ ] Quick manual test matrix:
  - [ ] Successful login
  - [ ] Wrong credentials
  - [ ] Forgot password flow happy path
  - [ ] Forgot password invalid employee/company
  - [ ] 2FA required → success
  - [ ] 2FA required → wrong code
>>>>>>> 7af2081b1ee90f973fb6a50125075e3ab9385a4f

>>>>>>> 0468767b7c880faedaf5c947bc12c03e864c803e
