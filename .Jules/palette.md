## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-06-14 - [Resilient Credential Copy-to-Clipboard Micro-UX Pattern]
**Learning:** A delightful micro-UX pattern for copying generated credentials: dynamically swap the copy button icon to a green checkmark (`fas fa-check text-success`) and update its tooltip and screen-reader `aria-label` attributes to "Copied!" for 1.5 seconds. To protect against timing bugs from rapid successive clicks, use a `data-copied` state guard. To protect against execution failures in headless testing or non-secure contexts, always implement a programmatic `<textarea>` fallback.
**Action:** Always implement local copy buttons with timing guards, explicit `type="button"` attributes, and an inline visual/auditory checkmark state feedback.
