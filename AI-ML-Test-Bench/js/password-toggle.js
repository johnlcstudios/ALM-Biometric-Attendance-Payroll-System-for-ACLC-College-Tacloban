/**
 * Centralized Password Visibility Toggle
 *
 * Provides keyboard-accessible password toggling for the entire application.
 * Automatically initializes on DOMContentLoaded and provides a global hook for dynamic content.
 */

(function() {
    /**
     * Handles the toggle logic for password visibility
     * @param {Event} e - Click or Keydown event
     */
    function togglePasswordVisibility(e) {
        // Handle keyboard accessibility (Enter/Space)
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') {
            return;
        }

        if (e.type === 'keydown') {
            e.preventDefault();
        }

        const toggleIcon = e.currentTarget;
        // Find the associated password input within the same input-wrapper
        const wrapper = toggleIcon.closest('.input-wrapper');
        if (!wrapper) return;

        const passwordInput = wrapper.querySelector('input[type="password"], input[type="text"]');
        if (!passwordInput) return;

        const isPassword = passwordInput.getAttribute('type') === 'password';

        // Toggle the type attribute
        passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

        // Toggle the eye / eye-slash icons
        // The base class fa-eye is expected to be present
        if (isPassword) {
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }

        // Update ARIA label for screen readers
        const newLabel = isPassword ? 'Hide password' : 'Show password';
        toggleIcon.setAttribute('aria-label', newLabel);

        // Refocus the input for better UX after keyboard interaction
        if (e.type === 'keydown') {
            passwordInput.focus();
        }
    }

    /**
     * Initializes all password toggles on the page.
     * Can be called manually for dynamically loaded content.
     */
    window.initPasswordToggles = function() {
        const toggles = document.querySelectorAll('.toggle-password');

        toggles.forEach(toggle => {
            // Ensure accessibility attributes are present
            if (!toggle.hasAttribute('role')) toggle.setAttribute('role', 'button');
            if (!toggle.hasAttribute('tabindex')) toggle.setAttribute('tabindex', '0');
            if (!toggle.hasAttribute('aria-label')) toggle.setAttribute('aria-label', 'Toggle password visibility');

            // Remove existing named listeners to prevent double-binding
            toggle.removeEventListener('click', togglePasswordVisibility);
            toggle.removeEventListener('keydown', togglePasswordVisibility);

            // Add listeners
            toggle.addEventListener('click', togglePasswordVisibility);
            toggle.addEventListener('keydown', togglePasswordVisibility);
        });
    };

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initPasswordToggles);
    } else {
        window.initPasswordToggles();
    }
})();
