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

