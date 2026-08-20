## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-06-14 - [Dynamic CSS Positioning for Dual vs Standalone Input Icons]
**Learning:** In `.input-wrapper` components without pre-existing icons, `.toggle-password` positioned at `right: 45px` creates noticeable empty whitespace on the right edge. Leveraging CSS `:has(i:not(.toggle-password))` allows defaulting `.toggle-password` to `right: 15px` while automatically shifting it to `right: 45px` when paired with other decorative icons (such as lock icons).
**Action:** Use `.input-wrapper:has(i:not(.toggle-password)) .toggle-password` for contextual icon offsets in CSS.
