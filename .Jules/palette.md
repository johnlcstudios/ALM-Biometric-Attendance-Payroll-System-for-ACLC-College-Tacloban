## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-08-18 - [Accessible Modal Close Buttons and Escape Listener]
**Learning:** Legacy modal implementations using `<span class="close">` omit native keyboard focus and screen reader labeling. Converting close triggers to `<button type="button" class="close" aria-label="Close modal">` combined with CSS button resets (`background: transparent; border: none;`) and a global `Escape` key event listener provides full keyboard and screen reader accessibility without layout distortion.
**Action:** Always prefer semantic `<button type="button">` with `aria-label` over `<span>` or `<i>` for close buttons, and complement modals with an Escape key handler.
