/**
 * Centralized Password Visibility Toggle
 * Handles accessibility (ARIA, keyboard) and ensures consistent behavior across the app.
 */
(function() {
    function toggleVisibility(e) {
        const toggle = e.currentTarget;
        const inputWrapper = toggle.closest('.input-wrapper');
        if (!inputWrapper) return;

        const input = inputWrapper.querySelector('input');
        if (!input) return;

        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');

        // Toggle icon classes (FontAwesome)
        toggle.classList.toggle('fa-eye', !isPassword);
        toggle.classList.toggle('fa-eye-slash', isPassword);

        // Update ARIA label for screen readers
        const action = isPassword ? 'Hide' : 'Show';
        toggle.setAttribute('aria-label', `${action} password`);
    }

    function handleKeydown(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleVisibility(e);
        }
    }

    window.initPasswordToggles = function() {
        const toggles = document.querySelectorAll('.toggle-password');
        toggles.forEach(toggle => {
            // Remove existing listeners to prevent double-binding
            toggle.removeEventListener('click', toggleVisibility);
            toggle.removeEventListener('keydown', handleKeydown);

            // Add listeners
            toggle.addEventListener('click', toggleVisibility);
            toggle.addEventListener('keydown', handleKeydown);

            // Ensure accessibility attributes
            if (!toggle.hasAttribute('role')) toggle.setAttribute('role', 'button');
            if (!toggle.hasAttribute('tabindex')) toggle.setAttribute('tabindex', '0');
            if (!toggle.hasAttribute('aria-label')) toggle.setAttribute('aria-label', 'Show password');
        });
    };

    // Auto-initialize on load
    document.addEventListener('DOMContentLoaded', window.initPasswordToggles);
})();
