## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-07-19 - [Delightful Distraction-Free Clipboard Copy Feedback]
**Learning:** In dashboards where credentials or keys are dynamically generated, utilizing full-screen modal alerts or intrusive toast notifications for clipboard copying can interrupt the user's flow. An elegant, distraction-free alternative is a micro-interaction on the copy button itself: temporarily (e.g., 1.5 seconds) swapping the copy icon to a green checkmark (`fas fa-check text-success`) and updating the `title` and `aria-label` to "Copied!".
**Action:** Implement self-contained inline icon-swapping states for copy buttons to provide elegant, distraction-free visual feedback.
