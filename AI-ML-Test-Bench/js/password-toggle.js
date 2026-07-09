/**
 * Password Visibility Toggle Utility
 * Centralized logic for password fields across the application.
 */

(function() {
    function togglePasswordVisibility(toggle) {
        const targetId = toggle.getAttribute('data-target');
        const input = targetId ? document.getElementById(targetId) : toggle.parentElement.querySelector('input[type="password"], input[type="text"]');

        if (!input) return;

        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');

        // Toggle icon classes
        toggle.classList.toggle('fa-eye', !isPassword);
        toggle.classList.toggle('fa-eye-slash', isPassword);

        // Update ARIA label for screen readers
        const label = isPassword ? 'Hide password' : 'Show password';
        toggle.setAttribute('aria-label', label);
        if (toggle.hasAttribute('title')) {
            toggle.setAttribute('title', label);
        }
    }

    function initToggles() {
        const toggles = document.querySelectorAll('.toggle-password:not([data-toggle-initialized])');

        toggles.forEach(toggle => {
            // Set initial accessibility attributes if missing
            if (!toggle.hasAttribute('role')) toggle.setAttribute('role', 'button');
            if (!toggle.hasAttribute('tabindex')) toggle.setAttribute('tabindex', '0');
            if (!toggle.hasAttribute('aria-label')) toggle.setAttribute('aria-label', 'Show password');

            // Handle Click
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                togglePasswordVisibility(this);
            });

            // Handle Keyboard (Enter and Space)
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

    // Initialize on DOM load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initToggles);
    } else {
        initToggles();
    }

    // Export to window for dynamic use
    window.initPasswordToggles = initToggles;
})();
