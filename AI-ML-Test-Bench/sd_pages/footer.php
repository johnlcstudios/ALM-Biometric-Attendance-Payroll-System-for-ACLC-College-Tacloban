            </div>
        </div>
    </div>

    <footer style="background-color: #f8f9fa; border-top: 1px solid #e0e0e0; padding: 20px 30px; text-align: center; color: #666; font-size: 0.9rem; margin-left: 260px;">
        <p style="margin: 0;">
            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($company_name ?? 'ACLC College of Tacloban'); ?> | School Director Management Portal | 
            <a href="#" style="color: #1e0178; text-decoration: none;">Privacy Policy</a> | 
            <a href="#" style="color: #1e0178; text-decoration: none;">Terms of Service</a>
        </p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /**
         * Toggle submenu visibility and ARIA expanded state
         */
        function toggleSubmenu(id, triggerElement) {
            if (typeof event !== 'undefined' && event) {
                event.preventDefault();
            }
            const submenu = document.getElementById(id);
            if (!submenu) return;

            const isShow = submenu.classList.contains('show');

            // Close all submenus and reset aria-expanded
            document.querySelectorAll('.nav-submenu').forEach(menu => {
                menu.classList.remove('show');
            });
            document.querySelectorAll('.sidebar .nav-link[aria-controls]').forEach(link => {
                link.setAttribute('aria-expanded', 'false');
            });

            // Open clicked submenu if it wasn't open
            if (!isShow) {
                submenu.classList.add('show');
                if (triggerElement) {
                    triggerElement.setAttribute('aria-expanded', 'true');
                }
            }
        }

        /**
         * Handle keyboard navigation for submenu triggers
         */
        function handleSubmenuKey(event, id, triggerElement) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleSubmenu(id, triggerElement);
            }
        }

        /**
         * Format currency
         */
        function formatCurrency(value) {
            return new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP'
            }).format(value);
        }

        /**
         * Format date
         */
        function formatDate(date) {
            return new Date(date).toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        /**
         * Show loading spinner
         */
        function showLoading(selector) {
            const element = document.querySelector(selector);
            if (element) {
                element.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
            }
        }

        /**
         * Show error message
         */
        function showError(message, selector) {
            const element = document.querySelector(selector);
            if (element) {
                element.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
            }
        }

        /**
         * Show success message
         */
        function showSuccess(message, selector) {
            const element = document.querySelector(selector);
            if (element) {
                element.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>`;
            }
        }

        /**
         * Fetch JSON data
         */
        async function fetchData(url, options = {}) {
            try {
                const response = await fetch(url, options);
                if (!response.ok) throw new Error('Network response was not ok');
                return await response.json();
            } catch (error) {
                console.error('Fetch error:', error);
                return null;
            }
        }

        /**
         * Set active navigation link & expand parent submenu if active
         */
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            document.querySelectorAll('.nav-link').forEach(link => {
                const href = link.getAttribute('href');
                if (href && href !== '#' && href.endsWith(currentPage)) {
                    link.classList.add('active');
                    const parentSubmenu = link.closest('.nav-submenu');
                    if (parentSubmenu) {
                        parentSubmenu.classList.add('show');
                        const trigger = document.querySelector(`.sidebar .nav-link[aria-controls="${parentSubmenu.id}"]`);
                        if (trigger) {
                            trigger.setAttribute('aria-expanded', 'true');
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
