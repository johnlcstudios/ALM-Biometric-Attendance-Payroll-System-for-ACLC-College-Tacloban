## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Visual Clarity with Multi-Icon Inputs]
**Learning:** When adding interactive toggles to inputs that already contain decorative icons (like FontAwesome locks), standard padding is insufficient. Text will overlap the second icon if `padding-right` isn't explicitly increased.
**Action:** Increase `padding-right` (e.g., to `70px`) for inputs with dual right-aligned icons to maintain readability and visual polish.
