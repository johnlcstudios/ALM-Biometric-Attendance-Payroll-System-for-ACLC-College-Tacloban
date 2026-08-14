## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-08-14 - [WAI-ARIA Compliant Tab Navigation Pattern]
**Learning:** Implementing custom tab components in vanilla JS require deep adherence to the WAI-ARIA tab pattern to remain fully accessible. Statically defining `role="tablist"`, `role="tab"`, and `role="tabpanel"` is not enough; the JS logic must dynamically synchronize `aria-selected` (true/false) and focusability (`tabindex="0"` vs `tabindex="-1"`) to prevent keyboard-focused users from getting trapped or lost. Keyboard listener callbacks (handling ArrowRight, ArrowLeft, ArrowDown, ArrowUp, Home, End keys) must use `e.preventDefault()` to avoid background page scrolling.
**Action:** When implementing custom tabbed widgets, combine HTML ARIA attributes with comprehensive keyboard event mapping and dynamic state synchronization (via `.setAttribute`) in JS.
