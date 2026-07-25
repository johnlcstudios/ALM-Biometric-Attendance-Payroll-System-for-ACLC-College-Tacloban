## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-06-15 - [Robust Credentials Clipboard Copy Feedback Pattern]
**Learning:** For critical administrative flows such as auto-generated credentials, copy buttons require dual feedback mechanisms (visual + screen-reader). Swapping the copy icon in-place to a green checkmark (`fas fa-check text-success`) and updating the `aria-label` and `title` to "Copied!" for 1.5s provides immediate delight. Additionally, employing a safeguard tracking attribute (`data-copied="true"`) prevents overlapping timer resets, and a fallback copy helper (using a temporary `<textarea>`) ensures functionality when browser permission policies block direct API clipboard access.
**Action:** Integrate in-place icon and ARIA label swapping with `data-copied` tracking guards and fallback clipboard writers for copy operations.
