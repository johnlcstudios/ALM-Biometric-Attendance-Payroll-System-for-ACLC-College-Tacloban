## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-06-14 - [Async Form Submit Loading Feedback]
**Learning:** For asynchronous form submissions, disabling the submit button, updating label text (e.g. "Logging in..."), inserting an inline spinner with `aria-hidden="true"`, and restoring the initial button state on error prevents accidental double submissions while providing immediate visual feedback.
**Action:** Apply button disabling, inline spinner icons, and flex alignment to form submit buttons handling async operations.
