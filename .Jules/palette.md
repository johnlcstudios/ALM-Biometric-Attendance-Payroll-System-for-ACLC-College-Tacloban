## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-06-15 - [Resilient Accessible Copy Feedback Pattern]
**Learning:** For a delightful, highly accessible copy-to-clipboard experience: (1) prevent redundant/overlapping states using a `data-copied` guard, (2) guarantee support in non-secure or headless contexts via an automatic programmatic fallback (`document.execCommand`), and (3) swap icons (`fa-check text-success`) while updating `aria-label` and `title` to "Copied!" for 1.5s to provide simultaneous visual and screen-reader confirmation.
**Action:** Always structure copy-to-clipboard buttons with explicit `type="button"`, initial `aria-label`/`title` attributes, and use a dual-layer copy utility with timed success state restoration.
