## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-06-15 - [Semantic Actionable Contact Information]
**Learning:** In public-facing or support forms/pages, rendering critical support details (email, phone, map location) as passive `div` or span text is a missed usability and accessibility opportunity. Transforming these passive elements into semantic `<a>` tags with descriptive `aria-label` tags and specific URI schemes (`mailto:`, `tel:`, custom maps search) allows seamless screen reader interaction, simple keyboard navigation, and one-tap action capabilities on mobile devices.
**Action:** Always render contact info, support endpoints, and addresses as semantic actionable link containers, supplemented with proper aria-labels and high-contrast `:focus-visible` focus outlines.
