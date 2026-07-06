/**
 * Password Visibility Toggle
 * Centralized logic for handling password visibility toggles across the application.
 * Supports accessibility (keyboard navigation, ARIA labels) and dynamic initialization.
 */

(function() {
    function initPasswordToggles() {
        const toggles = document.querySelectorAll('.toggle-password:not([data-toggle-initialized])');

        toggles.forEach(toggle => {
            toggle.setAttribute('data-toggle-initialized', 'true');

            // Ensure accessibility attributes if missing
            if (!toggle.hasAttribute('role')) toggle.setAttribute('role', 'button');
            if (!toggle.hasAttribute('tabindex')) toggle.setAttribute('tabindex', '0');
            if (!toggle.hasAttribute('aria-label')) toggle.setAttribute('aria-label', 'Show password');

            const targetId = toggle.getAttribute('data-target');
            const passwordInput = targetId ? document.getElementById(targetId) : toggle.previousElementSibling;

            if (!passwordInput || (passwordInput.tagName !== 'INPUT' && passwordInput.querySelector('input'))) {
                // If it's not directly the input, try to find the input within the same wrapper
                const input = toggle.closest('.input-wrapper')?.querySelector('input[type="password"], input[type="text"]');
                if (input) {
                    handleToggle(toggle, input);
                }
            } else if (passwordInput) {
                handleToggle(toggle, passwordInput);
            }
        });
    }

    function handleToggle(toggle, input) {
        const toggleAction = () => {
            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');

            // Toggle icons
            toggle.classList.toggle('fa-eye');
            toggle.classList.toggle('fa-eye-slash');

            // Update ARIA label
            const newLabel = isPassword ? 'Hide password' : 'Show password';
            toggle.setAttribute('aria-label', newLabel);
            if (toggle.hasAttribute('title')) toggle.setAttribute('title', newLabel);
        };

        toggle.addEventListener('click', toggleAction);

        toggle.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleAction();
            }
        });
    }

    // Expose to window for dynamic content (like modals)
    window.initPasswordToggles = initPasswordToggles;

    // Auto-init on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordToggles);
    } else {
        initPasswordToggles();
    }
})();
