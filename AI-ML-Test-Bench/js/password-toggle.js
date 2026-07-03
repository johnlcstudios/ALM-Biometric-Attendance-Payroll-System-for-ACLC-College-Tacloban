/**
 * Centralized Password Visibility Toggle Logic
 * Finds all elements with data-toggle="password" and attaches visibility toggle behavior.
 */
(function() {
    window.initPasswordToggles = function() {
        const toggles = document.querySelectorAll('[data-toggle="password"]:not([data-toggle-initialized])');

        toggles.forEach(toggle => {
            const targetId = toggle.getAttribute('data-target') || toggle.getAttribute('aria-controls');
            const target = targetId ? document.getElementById(targetId) : toggle.previousElementSibling;

            if (!target || (target.tagName !== 'INPUT' && !target.querySelector('input'))) return;

            const input = target.tagName === 'INPUT' ? target : target.querySelector('input');

            const toggleVisibility = () => {
                const isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');

                // Toggle icon classes
                toggle.classList.toggle('fa-eye', !isPassword);
                toggle.classList.toggle('fa-eye-slash', isPassword);

                // Update ARIA label
                const label = isPassword ? 'Hide password' : 'Show password';
                toggle.setAttribute('aria-label', label);
                if (toggle.hasAttribute('title')) {
                    toggle.setAttribute('title', label);
                }
            };

            toggle.addEventListener('click', toggleVisibility);

            // Keyboard accessibility
            toggle.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleVisibility();
                }
            });

            // Mark as initialized
            toggle.setAttribute('data-toggle-initialized', 'true');

            // Ensure necessary accessibility attributes are present
            if (!toggle.hasAttribute('role')) toggle.setAttribute('role', 'button');
            if (!toggle.hasAttribute('tabindex')) toggle.setAttribute('tabindex', '0');
            if (!toggle.hasAttribute('aria-label')) toggle.setAttribute('aria-label', 'Show password');
        });
    };

    // Initialize on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initPasswordToggles);
    } else {
        window.initPasswordToggles();
    }
})();
