## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.
## 2026-06-30 - [Centralized Accessible Password Toggles]
**Learning:** Consolidating password visibility logic into a single modular script ('password-toggle.js') reduces redundancy and ensures consistent accessibility (ARIA labels, keyboard support) across diverse pages like login, signup, and user settings.
**Action:** Use centralized utility scripts for common UI interactions to maintain high standards of UX and accessibility throughout the application.
