## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [CSS Layout Stability for Absolute Icons]
**Learning:** Centralizing `.input-wrapper { position: relative; }` in global stylesheets is essential when adding absolute-positioned utility icons (like password toggles) to prevent layout breakage on pages without local style overrides.
**Action:** Verify layout stability across all pages using a shared component/class, especially when relying on absolute positioning within flex or grid layouts.
