## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-15 - [Icon Spacing in Dual-Icon Inputs]
**Learning:** In this design system, standard inputs use decorative icons at `right: 15px`. Interactive toggles must be positioned at `right: 45px` to avoid overlap, and the input itself requires `padding-right: 70px` to prevent text from sliding under the icons.
**Action:** Apply the `.password-field` class and use the standardized `right: 45px` positioning for all password visibility toggles.
