## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-06-15 - [WAI-ARIA Compliant Tab Navigation]
**Learning:** Legacy page tabs often lack proper semantic markings and keyboard cycle handling, leaving screen-reader and keyboard-only users blind to content changes. Upgrading custom toggle controls to a formal WAI-ARIA tabbed interface (`role="tablist"`, `role="tab"`, `role="tabpanel"`) with native JavaScript arrow-key selection unlocks deep assistive technology support.
**Action:** When designing or refactoring tabbed interfaces, always link tabs using `aria-controls` / `aria-labelledby`, toggle `aria-selected` and `tabindex` dynamically, and bind Arrow Key events to handle programmatic keyboard-focused navigation.
