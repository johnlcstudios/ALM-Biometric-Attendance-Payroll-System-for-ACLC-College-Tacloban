## 2026-06-14 - [Accessible Password Toggles in Legacy Forms]
**Learning:** In legacy PHP/Bootstrap environments lacking native component abstractions, password visibility toggles must be manually augmented with `role="button"`, `tabindex="0"`, and dual event listeners (click + keydown) to ensure keyboard parity with mouse users.
**Action:** Always include `tabindex="0"` and a keydown listener (Enter/Space) when transforming non-interactive elements (like `<i>` icons) into functional UI controls.

## 2026-06-14 - [Centralized Password Toggle Pattern]
**Learning:** A reusable UX pattern for this design system was discovered: using a data-attribute driven JS utility (`password-toggle.js`) that automatically initializes icons with `.toggle-password` classes. This prevents code duplication in PHP files and ensures accessibility (ARIA, roles, keyboard) is applied consistently.
**Action:** Use the `AI-ML-Test-Bench/js/password-toggle.js` utility for all new password fields instead of writing local inline scripts.

## 2026-06-14 - [Skip Link Pattern for Complex Sidebar Navigation]
**Learning:** In dashboards with extensive sidebar navigation links (15+ items), keyboard users experience heavy focus fatigue without direct main-content navigation. Adding a visually hidden `.skip-link` right after `<body>` targeting `<main id="main-content" tabindex="-1">` grants single-tab access directly to page content.
**Action:** Always place `<a href="#main-content" class="skip-link">Skip to main content</a>` immediately inside `<body>` and assign `id="main-content"` with `tabindex="-1"` on the main content element.
