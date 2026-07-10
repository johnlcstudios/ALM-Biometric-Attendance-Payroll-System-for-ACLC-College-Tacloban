/**
 * Password Visibility Toggle Utility
 * Handles toggling password visibility with accessibility support.
 */

(function() {
    function togglePasswordVisibility(toggle) {
        const targetId = toggle.getAttribute('data-target');
        let input;

        if (targetId) {
            input = document.getElementById(targetId);
        } else {
            // Fallback: look for a sibling input if data-target is missing
            input = toggle.parentElement.querySelector('input');
        }

        if (!input) return;

        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');

        // Toggle icon classes
        if (toggle.classList.contains('fa-eye')) {
            toggle.classList.remove('fa-eye');
            toggle.classList.add('fa-eye-slash');
        } else if (toggle.classList.contains('fa-eye-slash')) {
            toggle.classList.remove('fa-eye-slash');
            toggle.classList.add('fa-eye');
        }

        // Update ARIA label and title
        const newLabel = isPassword ? 'Hide password' : 'Show password';
        toggle.setAttribute('aria-label', newLabel);
        if (toggle.hasAttribute('title')) {
            toggle.setAttribute('title', newLabel);
        }
    }

    function initPasswordToggles() {
        const toggles = document.querySelectorAll('.toggle-password:not([data-toggle-initialized])');

        toggles.forEach(toggle => {
            // Ensure accessibility attributes
            if (!toggle.hasAttribute('role')) toggle.setAttribute('role', 'button');
            if (!toggle.hasAttribute('tabindex')) toggle.setAttribute('tabindex', '0');
            if (!toggle.hasAttribute('aria-label')) toggle.setAttribute('aria-label', 'Show password');

            // Click event
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                togglePasswordVisibility(this);
            });

            // Keyboard event (Enter and Space)
            toggle.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    togglePasswordVisibility(this);
                }
            });

            // Mark as initialized
            toggle.setAttribute('data-toggle-initialized', 'true');
        });
    }

    // Expose to global scope for dynamic initialization
    window.initPasswordToggles = initPasswordToggles;

    // Auto-initialize on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordToggles);
    } else {
        initPasswordToggles();
    }
})();
