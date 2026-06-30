/**
 * Password Visibility Toggle
 * Centralized logic for password toggling with accessibility support.
 */

(function() {
    /**
     * Initializes password toggles for the given container or the entire document.
     * @param {HTMLElement|Document} container - The element to search for toggles within.
     */
    function initPasswordToggles(container = document) {
        const toggles = container.querySelectorAll('.toggle-password');

        toggles.forEach(toggle => {
            // Prevent double-binding by checking if initialized and removing old listeners if possible
            // Since we use anonymous functions, we'll use a marker
            if (toggle.dataset.passwordToggleInitialized) return;

            const wrapper = toggle.closest('.input-wrapper');
            // Try to find the password field:
            // 1. By data-target attribute
            // 2. By looking for the first password/text input in the same wrapper
            // 3. By ID 'password'
            const targetId = toggle.getAttribute('data-target');
            let passwordField = null;

            if (targetId) {
                passwordField = document.getElementById(targetId);
            }

            if (!passwordField && wrapper) {
                passwordField = wrapper.querySelector('input[type="password"], input[type="text"]');
            }

            if (!passwordField && targetId === null) {
                passwordField = document.getElementById('password');
            }

            if (!passwordField) return;

            const handleToggle = (e) => {
                if (e) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                const isPassword = passwordField.getAttribute('type') === 'password';
                passwordField.setAttribute('type', isPassword ? 'text' : 'password');

                // Toggle icon class - handle FontAwesome
                if (toggle.classList.contains('fa-eye') || toggle.classList.contains('fa-eye-slash')) {
                    toggle.classList.toggle('fa-eye', !isPassword);
                    toggle.classList.toggle('fa-eye-slash', isPassword);
                }
            };

            // Click listener
            toggle.addEventListener('click', handleToggle);

            // Keyboard listener (Enter and Space)
            toggle.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    handleToggle();
                }
            });

            // Set accessibility attributes if missing
            if (!toggle.getAttribute('role')) toggle.setAttribute('role', 'button');
            if (!toggle.getAttribute('tabindex')) toggle.setAttribute('tabindex', '0');
            if (!toggle.getAttribute('aria-label')) toggle.setAttribute('aria-label', 'Toggle password visibility');

            toggle.dataset.passwordToggleInitialized = 'true';
        });
    }

    // Auto-initialize on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initPasswordToggles());
    } else {
        initPasswordToggles();
    }

    // Export for dynamic content
    window.initPasswordToggles = initPasswordToggles;
})();
