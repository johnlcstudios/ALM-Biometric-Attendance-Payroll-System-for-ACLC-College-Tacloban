/**
 * Centralized Password Visibility Toggle Utility
 * Handles accessibility, ARIA attributes, and dual event listeners (click + keyboard).
 */
(function() {
    function initPasswordToggles() {
        const toggles = document.querySelectorAll('.toggle-password');

        toggles.forEach(toggle => {
            if (toggle.dataset.toggleInitialized) return;
            const targetId = toggle.dataset.target || toggle.getAttribute('id');
            let input = null;
            if (targetId) {
                input = document.getElementById(targetId.replace('#', '')) ||
                        document.querySelector(targetId);
            }
            if (!input) {
                input = toggle.closest('.input-wrapper').querySelector('input');
            }

            if (!input) return;

            if (!input) return;
            const toggleVis = () => {
                const isPw = input.type === 'password';
                input.type = isPw ? 'text' : 'password';
                toggle.classList.toggle('fa-eye', !isPw);
                toggle.classList.toggle('fa-eye-slash', isPw);
                const label = isPw ? 'Hide password' : 'Show password';
                toggle.setAttribute('aria-label', label);
                toggle.title = label;
            };
            if (!toggle.getAttribute('role')) toggle.role = 'button';
            if (!toggle.getAttribute('tabindex')) toggle.tabIndex = 0;
            toggle.addEventListener('click', e => { e.preventDefault(); toggleVis(); });
            toggle.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleVis(); }
            });
            toggle.dataset.toggleInitialized = 'true';
        });
    }
    document.readyState === 'loading' ?
        document.addEventListener('DOMContentLoaded', initPasswordToggles) : initPasswordToggles();
    window.initPasswordToggles = initPasswordToggles;
})();
