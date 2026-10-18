## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-10-18 - [Semantic Modal Close Buttons with Keyboard Accessibility]
**Learning:** Converting legacy non-semantic `<span class="close">&times;</span>` modal close controls into `<button type="button" class="close" aria-label="Close modal">&times;</button>` enables screen reader focus and native keyboard interaction (Tab / Space / Enter). When paired with CSS button reset properties (`background: transparent; border: none; padding: 0;`) and `:focus-visible` outline styles, it creates an intuitive modal dismissal experience without visual regression.
**Action:** Always replace `<span class="close">` with `<button type="button" class="close" aria-label="Close modal">` and style `:focus-visible` for proper keyboard focus indication.
