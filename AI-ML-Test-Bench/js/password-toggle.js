window.initPasswordToggles = function() {
    document.querySelectorAll('.toggle-password:not([data-toggle-initialized])').forEach(btn => {
        btn.setAttribute('data-toggle-initialized', 'true');
        btn.setAttribute('role', 'button');
        btn.setAttribute('tabindex', '0');
        const input = btn.parentElement.querySelector('input');
        const toggle = (e) => {
            e.preventDefault();
            const isPass = input.type === 'password';
            input.type = isPass ? 'text' : 'password';
            btn.classList.toggle('fa-eye-slash', isPass);
            btn.classList.toggle('fa-eye', !isPass);
            btn.setAttribute('aria-label', isPass ? 'Hide password' : 'Show password');
        };
        btn.addEventListener('click', toggle);
        btn.addEventListener('keydown', e => (e.key === 'Enter' || e.key === ' ') && toggle(e));
    });
};
document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', window.initPasswordToggles) : window.initPasswordToggles();
