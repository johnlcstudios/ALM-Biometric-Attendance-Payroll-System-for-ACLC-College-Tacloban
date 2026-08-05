## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-08-05 - [WAI-ARIA Tablist Navigation in Vanilla HTML]
**Learning:** Legacy tabbed interfaces built with vanilla JS and custom CSS are completely opaque to screen readers and keyboard users without precise WAI-ARIA roles, attributes, and dynamic focus/keyboard handling. Setting `role="tablist"`, `role="tab"`, and `role="tabpanel"` is necessary, but must be paired with dynamic `tabindex` shifting (0 for active, -1 for inactive) and keyboard event listeners to enable standard arrow-key navigation with wrap-around.
**Action:** When implementing tabbed components, structure them using semantic roles, control panels with matching `aria-controls` / `aria-labelledby`, and bind custom JavaScript to handle Left/Right/Up/Down/Home/End keyboard events.
