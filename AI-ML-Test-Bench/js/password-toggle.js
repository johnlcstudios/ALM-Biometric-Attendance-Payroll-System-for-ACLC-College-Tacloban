/**
 * Password Visibility Toggle Logic
 * Centralized utility to handle password toggling across the application.
 */

window.initPasswordToggles = function() {
    const toggles = document.querySelectorAll('.toggle-password');

    toggles.forEach(toggle => {
        // Prevent double initialization
        if (toggle.dataset.toggleInitialized) return;

        const inputId = toggle.getAttribute('data-target') || toggle.previousElementSibling?.id;
        const passwordInput = inputId ? document.getElementById(inputId) : toggle.parentElement.querySelector('input[type="password"], input[type="text"]');

        if (!passwordInput) return;

        const handleToggle = (e) => {
            if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
            if (e.type === 'keydown') e.preventDefault();

            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

            // Toggle icon classes
            toggle.classList.toggle('fa-eye');
            toggle.classList.toggle('fa-eye-slash');

            // Update ARIA label
            const newLabel = isPassword ? 'Hide password' : 'Show password';
            toggle.setAttribute('aria-label', newLabel);
            if (toggle.hasAttribute('title')) {
                toggle.setAttribute('title', newLabel);
            }
        };

        toggle.addEventListener('click', handleToggle);
        toggle.addEventListener('keydown', handleToggle);

        // Ensure accessibility attributes
        if (!toggle.hasAttribute('role')) toggle.setAttribute('role', 'button');
        if (!toggle.hasAttribute('tabindex')) toggle.setAttribute('tabindex', '0');
        if (!toggle.hasAttribute('aria-label')) toggle.setAttribute('aria-label', 'Show password');

        toggle.dataset.toggleInitialized = 'true';
    });
};

// Auto-initialize if DOM is already ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.initPasswordToggles);
} else {
    window.initPasswordToggles();
}
