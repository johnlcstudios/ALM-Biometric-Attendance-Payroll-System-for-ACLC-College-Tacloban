/**
 * Password Visibility Toggle Utility
 * Handles toggling password input visibility and updating icons.
 */

(function() {
    function toggleHandler(e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') {
            return;
        }

        if (e.type === 'keydown') {
            e.preventDefault();
        }

        const toggle = e.currentTarget;
        const inputWrapper = toggle.closest('.input-wrapper') || toggle.parentElement;
        const passwordInput = inputWrapper.querySelector('input[type="password"], input[type="text"]');

        if (passwordInput) {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

            // Toggle eye icons
            toggle.classList.toggle('fa-eye', !isPassword);
            toggle.classList.toggle('fa-eye-slash', isPassword);

            // Update aria-label for accessibility
            const label = isPassword ? 'Hide password' : 'Show password';
            toggle.setAttribute('aria-label', label);
        }
    }

    /**
     * Initializes all password toggles on the page.
     * Can be called manually for dynamically added content.
     */
    window.initPasswordToggles = function() {
        const toggles = document.querySelectorAll('.toggle-password');
        toggles.forEach(toggle => {
            // Remove existing listeners to prevent double-binding
            toggle.removeEventListener('click', toggleHandler);
            toggle.removeEventListener('keydown', toggleHandler);

            // Add listeners
            toggle.addEventListener('click', toggleHandler);
            toggle.addEventListener('keydown', toggleHandler);

            // Ensure basic accessibility attributes
            if (!toggle.hasAttribute('role')) toggle.setAttribute('role', 'button');
            if (!toggle.hasAttribute('tabindex')) toggle.setAttribute('tabindex', '0');
            if (!toggle.hasAttribute('aria-label')) toggle.setAttribute('aria-label', 'Toggle password visibility');
        });
    };

    // Auto-initialize on DOM load
    document.addEventListener('DOMContentLoaded', window.initPasswordToggles);
})();
