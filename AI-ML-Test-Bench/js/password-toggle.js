/**
 * Password Visibility Toggle Logic
 * Micro-UX Enhancement by Palette 🎨
 */

(function() {
    /**
     * Handle the actual visibility toggle
     */
    function handleToggle(e) {
        const toggle = e.currentTarget;
        const wrapper = toggle.closest('.input-wrapper');
        if (!wrapper) return;

        const input = wrapper.querySelector('input');
        if (!input) return;

        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');

        // Toggle icon class if it's a font-awesome icon
        if (toggle.classList.contains('fa-eye') || toggle.classList.contains('fa-eye-slash')) {
            toggle.classList.toggle('fa-eye');
            toggle.classList.toggle('fa-eye-slash');
        }
    }

    /**
     * Handle keyboard accessibility (Enter/Space)
     */
    function handleKeydown(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            handleToggle(e);
        }
    }

    /**
     * Initialize all toggles on the page
     */
    function initPasswordToggles() {
        const toggles = document.querySelectorAll('.toggle-password');

        toggles.forEach(toggle => {
            // Ensure accessibility attributes
            if (!toggle.hasAttribute('role')) toggle.setAttribute('role', 'button');
            if (!toggle.hasAttribute('tabindex')) toggle.setAttribute('tabindex', '0');
            if (!toggle.hasAttribute('aria-label')) toggle.setAttribute('aria-label', 'Toggle password visibility');

            // Cleanup existing listeners if any to prevent double-binding
            toggle.removeEventListener('click', handleToggle);
            toggle.removeEventListener('keydown', handleKeydown);

            // Attach listeners
            toggle.addEventListener('click', handleToggle);
            toggle.addEventListener('keydown', handleKeydown);
        });
    }

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPasswordToggles);
    } else {
        initPasswordToggles();
    }

    // Export to window for dynamic use (e.g. after modals load)
    window.initPasswordToggles = initPasswordToggles;
})();
