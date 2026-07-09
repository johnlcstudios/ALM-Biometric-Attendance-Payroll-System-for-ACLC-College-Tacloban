## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-07-09 - [Dual-Icon Input Strategy for Legacy Glass-UI]
**Learning:** When adding interactive elements (like password toggles) to inputs that already contain decorative icons (like locks), positioning and padding must be carefully coordinated to avoid visual overlap and ensure clickability. In this glass-morphic theme, decorative icons are at `right: 15px` and non-interactive.
**Action:** Position interactive toggles at `right: 45px` and increase input `padding-right` to `70px` to safely clear both icons and maintain text readability.
