## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-06-15 - [Reusable WAI-ARIA Keyboard Tab Navigation]
**Learning:** In non-framework, server-side rendered pages using static tab lists (such as developer team selectors), interactive switches lack screen reader context and keyboard parity. Upgrading the tab controls to a semantic W3C-compliant `tablist`/`tab`/`tabpanel` layout with dynamic `aria-selected` toggling and standard arrow-key navigation dramatically improves assistive device accessibility.
**Action:** Implement a standard keydown listener on the tab container to capture arrow navigation (Left/Right/Up/Down), `Home`, and `End` keys, and dynamically sync visual active classes with `aria-selected` attributes.
