## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-06-14 - [Accessible Custom Context Menu Navigation]
**Learning:** Custom context menus rendered via DOM insertion require explicit WAI-ARIA roles (`role="menu"`, `role="menuitem"`, `role="separator"`), auto-focusing the first item upon opening, and handling ArrowUp/ArrowDown key cycling to provide a native context menu experience for screen reader and keyboard users.
**Action:** When creating overlay menus, always set WAI-ARIA semantics, auto-focus the first item on open, implement arrow key cycling, and attach Enter/Space listeners on menuitems.
