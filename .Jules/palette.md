## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-06-14 - [Single-Icon Password Field Alignment Pattern]
**Learning:** In layout designs where password fields do not contain decorative front-end icons (like a lock icon), placing the visibility toggle at `right: 45px` creates an awkward gap on the right. Overriding it cleanly to `right: 15px` using a dedicated `.profile-password-wrapper` class ensures alignment stability while avoiding repetitive inline styling across modular view files.
**Action:** Use `.profile-password-wrapper` for any password fields that do not feature decorative icons, and declare its overrides inside `style.css` rather than using inline styling attributes.
