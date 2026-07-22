## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-07-22 - [Delightful, Accessible & Resilient Clipboard Copies]
**Learning:** For critical generated credentials in legacy PHP pages, simple clipboard copy actions should be enriched with explicit keyboard/screen-reader accessibility (type="button", aria-label, title), delightful inline micro-UX feedback (momentarily swapping the icon/title/aria-label to a checkmark/"Copied!"), and a resilient fallback textarea-based execution path for non-secure contexts.
**Action:** Avoid raw `navigator.clipboard.writeText` calls on icon buttons without explicit accessibility markers, instant feedback, and a graceful fallback.
