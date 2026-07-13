/**
 * Centralized Password Visibility Toggle Utility
 * Handles ARIA labels, keyboard accessibility, and state switching
 */

const initPasswordToggles = () => {
    const toggles = document.querySelectorAll('.toggle-password');

    toggles.forEach(toggle => {
        // Prevent multiple initializations
        if (toggle.dataset.toggleInitialized) return;

        // Ensure accessibility attributes
        if (!toggle.hasAttribute('role')) toggle.setAttribute('role', 'button');
        if (!toggle.hasAttribute('tabindex')) toggle.setAttribute('tabindex', '0');
        if (!toggle.hasAttribute('aria-label')) toggle.setAttribute('aria-label', 'Show password');
        if (!toggle.hasAttribute('title')) toggle.setAttribute('title', 'Show password');

        const inputId = toggle.dataset.target || toggle.previousElementSibling?.id || toggle.parentElement.querySelector('input')?.id;
        const passwordInput = inputId ? document.getElementById(inputId) : toggle.parentElement.querySelector('input');

        if (!passwordInput) return;

        const toggleState = () => {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

            // Toggle icon classes (supports both FontAwesome and Bootstrap Icons)
            if (toggle.classList.contains('fa-eye')) {
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
            } else if (toggle.classList.contains('fa-eye-slash')) {
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
            } else if (toggle.classList.contains('bi-eye')) {
                toggle.classList.remove('bi-eye');
                toggle.classList.add('bi-eye-slash');
            } else if (toggle.classList.contains('bi-eye-slash')) {
                toggle.classList.remove('bi-eye-slash');
                toggle.classList.add('bi-eye');
            }

            const label = isPassword ? 'Hide password' : 'Show password';
            toggle.setAttribute('aria-label', label);
            toggle.setAttribute('title', label);
        };

        toggle.addEventListener('click', toggleState);

        toggle.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleState();
            }
        });

        toggle.dataset.toggleInitialized = 'true';
    });
};

// Initialize on DOM load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPasswordToggles);
} else {
    initPasswordToggles();
}

// Export for dynamic content (modals, etc)
window.initPasswordToggles = initPasswordToggles;
