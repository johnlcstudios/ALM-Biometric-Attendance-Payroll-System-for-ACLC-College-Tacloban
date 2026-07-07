/**
 * Password Visibility Toggle
 * Handles toggling password visibility for fields with the .toggle-password class.
 * Centralized for reuse across login, signup, and profile pages.
 */
(function() {
    function initPasswordToggles() {
        const toggles = document.querySelectorAll('.toggle-password:not([data-toggle-initialized])');

        toggles.forEach(toggle => {
            toggle.setAttribute('data-toggle-initialized', 'true');

            // Ensure accessibility
            if (!toggle.getAttribute('role')) toggle.setAttribute('role', 'button');
            if (!toggle.getAttribute('tabindex')) toggle.setAttribute('tabindex', '0');

            // Set initial ARIA label if missing
            if (!toggle.getAttribute('aria-label')) {
                toggle.setAttribute('aria-label', 'Show password');
            }

            // Find the associated password field
            // 1. Via data-target attribute
            // 2. Via previous sibling (common pattern)
            // 3. Within the same .input-wrapper
            const targetId = toggle.getAttribute('data-target');
            let passwordField;

            if (targetId) {
                passwordField = document.getElementById(targetId);
            } else {
                const wrapper = toggle.closest('.input-wrapper');
                if (wrapper) {
                    passwordField = wrapper.querySelector('input[type="password"], input[type="text"]');
                } else {
                    passwordField = toggle.previousElementSibling;
                    if (passwordField && passwordField.tagName !== 'INPUT') {
                        passwordField = toggle.parentElement.querySelector('input');
                    }
                }
            }

            if (!passwordField) return;

            const toggleAction = () => {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);

                // Toggle icon classes (supporting both fa-eye and fa-eye-slash)
                if (toggle.classList.contains('fa-eye')) {
                    toggle.classList.remove('fa-eye');
                    toggle.classList.add('fa-eye-slash');
                } else if (toggle.classList.contains('fa-eye-slash')) {
                    toggle.classList.remove('fa-eye-slash');
                    toggle.classList.add('fa-eye');
                } else {
                    // Fallback if neither is present initially
                    toggle.classList.toggle('fa-eye-slash');
                }

                // Update ARIA label and title
                const isVisible = type === 'text';
                const label = isVisible ? 'Hide password' : 'Show password';
                toggle.setAttribute('aria-label', label);
                toggle.setAttribute('title', label);
            };

            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                toggleAction();
            });

            toggle.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleAction();
                }
            });
        });
    }

    // Expose to window for dynamic content (like SweetAlert modals)
    window.initPasswordToggles = initPasswordToggles;

    // Initialize on DOM load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordToggles);
    } else {
        initPasswordToggles();
    }
})();
