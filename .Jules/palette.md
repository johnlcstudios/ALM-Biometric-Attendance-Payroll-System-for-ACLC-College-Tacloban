## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-06-14 - [Inline Clipboard Copy Micro-UX Pattern]
**Learning:** In legacy AdminLTE/Bootstrap layouts, using intrusive global SweetAlert modals to notify users of a simple clipboard copy action is highly disruptive. Replacing the alert with an inline micro-interaction (dynamic icon-swap to a green checkmark, tooltips/aria-labels updating to "Copied!" for 1.5 seconds) provides delightful, screen-reader friendly, non-intrusive feedback.
**Action:** Replace jarring copy notifications with inline state-switching icons and add a `data-copied` guard attribute to prevent overlapping timers on rapid clicking.
