/**
 * AI-ML-Test-Bench Password Visibility Toggle
 * Provides centralized, accessible password toggling for the entire application.
 */
(function() {
    function togglePassword(toggleIcon) {
        const inputWrapper = toggleIcon.closest('.input-wrapper');
        if (!inputWrapper) return;

        const passwordInput = inputWrapper.querySelector('input[type="password"], input[type="text"]');
        if (!passwordInput) return;

        const isPassword = passwordInput.getAttribute('type') === 'password';
        passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

        // Update icon class
        toggleIcon.classList.toggle('fa-eye', !isPassword);
        toggleIcon.classList.toggle('fa-eye-slash', isPassword);

        // Update ARIA label for screen readers
        toggleIcon.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    }

    function initPasswordToggles() {
        const toggles = document.querySelectorAll('.toggle-password');
        toggles.forEach(toggle => {
            // Ensure the toggle is accessible
            if (!toggle.hasAttribute('role')) toggle.setAttribute('role', 'button');
            if (!toggle.hasAttribute('tabindex')) toggle.setAttribute('tabindex', '0');
            if (!toggle.hasAttribute('aria-label')) toggle.setAttribute('aria-label', 'Toggle password visibility');

            // Click event
            toggle.removeEventListener('click', handleToggleClick);
            toggle.addEventListener('click', handleToggleClick);

            // Keyboard event
            toggle.removeEventListener('keydown', handleToggleKeydown);
            toggle.addEventListener('keydown', handleToggleKeydown);
        });
    }

    function handleToggleClick(e) {
        togglePassword(e.currentTarget);
    }

    function handleToggleKeydown(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            togglePassword(e.currentTarget);
        }
    }

    // Expose initialization for dynamic content
    window.initPasswordToggles = initPasswordToggles;

    // Auto-initialize on load
    document.addEventListener('DOMContentLoaded', initPasswordToggles);
})();
