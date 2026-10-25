## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-06-15 - [Semantic Modal Close Buttons in Legacy PHP Templates]
**Learning:** In legacy Bootstrap/PHP templates, modal close controls are often rendered as non-semantic `<span class="close">&times;</span>` elements. Replacing them with `<button type="button" class="close" aria-label="Close modal">&times;</button>` and adding button reset styles with `:focus-visible` outlines in CSS restores full screen-reader accessibility and keyboard tab order without breaking existing dialog layouts.
**Action:** Always convert `<span class="close">` modal controls to semantic `<button type="button" class="close">` elements with explicit ARIA labels and focus indicators.
