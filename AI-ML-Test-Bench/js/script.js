/**
 * Biometric Attendance & Payroll System - Core Logic (PHP/MySQL Version)
 */

// --- Dashboard Charts ---
let attendanceChart = null;
let pChart, aChart, mChart;

// --- Data Stores ---
let employees = [];
let attendanceLogs = [];
let payrollHistory = [];
let leaveRequests = [];
let loanRequests = [];
let resignationRequests = [];
let masterSubjects = [];
let subjectLoads = [];
let currentPage = 'dashboard';
let currentPayrollType = 'faculty';
let currentPayrollTableId = 'facultyPayrollTable';
let currentPayrollTitle = 'FACULTY PAYROLL';

// --- Helper Functions ---
function escapeHTML(str) {
    if (!str || typeof str !== 'string') return str || '';
    return str.replace(/[&<>"']/g, function (m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        }[m];
    });
}

// --- Validation Functions ---
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    if (!phone || phone.trim() === '') return true; // Optional field
    const re = /^(09|\+639)\d{9}$/; // Philippine format
    return re.test(phone.replace(/\s|-/g, ''));
}

function validateSalary(salary) {
    const num = parseFloat(salary);
    return !isNaN(num) && num >= 0 && num < 10000000;
}

function validateDate(date) {
    if (!date) return false;
    const d = new Date(date);
    return d instanceof Date && !isNaN(d);
}

function validateRequired(value) {
    return value && typeof value === 'string' && value.trim() !== '';
}

function validateGovernmentID(id, type) {
    if (!id || id.trim() === '') return true; // Optional
    // Basic format validation for Philippine government IDs
    const cleaned = id.replace(/\s|-/g, '');
    if (type === 'sss' && !/^\d{10,11}$/.test(cleaned)) return false;
    if (type === 'tin' && !/^\d{9,12}$/.test(cleaned)) return false;
    if (type === 'philhealth' && !/^\d{11,12}$/.test(cleaned)) return false;
    if (type === 'pagibig' && !/^\d{12}$/.test(cleaned)) return false;
    return true;
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'block';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = (event) => {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
};

function showToast(message, type = 'info') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: {
            popup: 'glass-toast-popup',
            title: 'glass-toast-title',
            timerProgressBar: 'glass-toast-progress',
            container: 'swal2-toast-container'
        },
        didOpen: (toast) => {
            toast.style.pointerEvents = 'auto';
        }
    });

    Toast.fire({
        icon: type,
        title: message
    });
}

// ==========================================
// TABLE PAGINATION, SEARCH & FILTER SYSTEM
// ==========================================

// Global table state management
const TableState = {
    employees: {
        currentPage: 1,
        rowsPerPage: 10,
        searchTerm: '',
        filters: {},
        filteredData: []
    },
    attendance: {
        currentPage: 1,
        rowsPerPage: 15,
        searchTerm: '',
        filters: {},
        filteredData: []
    },
    payroll: {
        currentPage: 1,
        rowsPerPage: 10,
        searchTerm: '',
        filters: {},
        filteredData: []
    },
    facultyPayroll: {
        currentPage: 1,
        rowsPerPage: 10,
        searchTerm: '',
        filters: {},
        filteredData: []
    },
    utilityPayroll: {
        currentPage: 1,
        rowsPerPage: 10,
        searchTerm: '',
        filters: {},
        filteredData: []
    }
};

// Initialize table with pagination, search, and filters
function initializeTable(tableName, options = {}) {
    const state = TableState[tableName];
    if (!state) return;
    
    state.rowsPerPage = options.rowsPerPage || state.rowsPerPage;
    
    // Create controls container if it doesn't exist
    const tableSection = document.getElementById(tableName === 'employees' ? 'employees' : 
                                                  tableName === 'attendance' ? 'attendance' : 
                                                  tableName === 'payroll' ? 'payroll' :
                                                  tableName === 'facultyPayroll' ? 'faculty_payroll' : 'utility_payroll');
    
    if (!tableSection) return;
    
    // Add controls before table
    let controlsContainer = tableSection.querySelector('.table-controls');
    if (!controlsContainer) {
        controlsContainer = document.createElement('div');
        controlsContainer.className = 'table-controls';
        controlsContainer.innerHTML = generateTableControlsHTML(tableName, options);
        
        const tableWrapper = tableSection.querySelector('.table-container') || 
                            tableSection.querySelector('.payroll-table-container') ||
                            tableSection.querySelector('.modern-table-wrapper') ||
                            tableSection.querySelector('table');
        
        if (tableWrapper) {
            tableWrapper.parentNode.insertBefore(controlsContainer, tableWrapper);
        }
    }
    
    // Add pagination after table
    let paginationContainer = tableSection.querySelector('.table-pagination');
    if (!paginationContainer) {
        paginationContainer = document.createElement('div');
        paginationContainer.className = 'table-pagination';
        paginationContainer.id = `${tableName}-pagination`;
        
        const tableWrapper = tableSection.querySelector('.table-container') || 
                            tableSection.querySelector('.payroll-table-container') ||
                            tableSection.querySelector('.modern-table-wrapper') ||
                            tableSection.querySelector('table');
        
        if (tableWrapper) {
            tableWrapper.parentNode.insertBefore(paginationContainer, tableWrapper.nextSibling);
        }
    }
    
    // Attach event listeners
    attachTableEventListeners(tableName, options);
}

// Generate HTML for table controls
function generateTableControlsHTML(tableName, options) {
    const showSearch = options.showSearch !== false;
    const showFilters = options.showFilters !== false;
    const showRowsPerPage = options.showRowsPerPage !== false;
    
    let html = '<div class="table-controls-left">';
    
    // Search box
    if (showSearch) {
        html += `
            <div class="table-search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="${tableName}-search" placeholder="Search by name or employee ID..." oninput="handleTableSearch('${tableName}', this.value)">
            </div>
        `;
    }
    
    // Filters
    if (showFilters && options.filters) {
        if (options.useMotherFilter) {
            html += generateMotherFilterHTML(tableName, options.filters);
        } else {
            html += '<div class="table-filters">';
            options.filters.forEach(filter => {
                html += generateFilterHTML(tableName, filter);
            });
            html += '</div>';
        }
    }
    
    html += '</div>';
    
    // Right side - Rows per page
    if (showRowsPerPage) {
        html += `
            <div class="table-controls-right">
                <div class="rows-per-page">
                    <label>Rows per page:</label>
                    <select id="${tableName}-rows" onchange="handleRowsPerPageChange('${tableName}', this.value)">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>
        `;
    }
    
    return html;
}

// Generate individual filter HTML
function generateFilterHTML(tableName, filter) {
    switch(filter.type) {
        case 'select':
            return `
                <div class="table-filter-item">
                    <select id="${tableName}-filter-${filter.id}" onchange="handleTableFilter('${tableName}', '${filter.id}', this.value)">
                        <option value="">${filter.label}</option>
                        ${filter.options.map(opt => `<option value="${opt.value}">${opt.label}</option>`).join('')}
                    </select>
                </div>
            `;
        case 'date':
            return `
                <div class="table-filter-item">
                    <label>${filter.label}</label>
                    <input type="date" id="${tableName}-filter-${filter.id}" onchange="handleTableFilter('${tableName}', '${filter.id}', this.value)">
                </div>
            `;
        default:
            return '';
    }
}

// Generate combined mother filter HTML with accordion checkboxes
function generateMotherFilterHTML(tableName, filters) {
    let html = `<div class="mother-filter-wrap">
        <button class="mother-filter-btn" onclick="toggleFilterPanel('${tableName}')" type="button">
            <i class="fas fa-sliders-h"></i> Customize Filter
        </button>
        <div class="mother-filter-panel" id="${tableName}-filter-panel">
            <div class="mother-filter-panel-inner">`;
    filters.forEach(filter => {
        if (filter.type === 'select' && filter.options) {
            html += `<div class="filter-group">
                <div class="filter-group-title" onclick="toggleFilterGroup(this)">
                    <span>${filter.label}</span>
                    <i class="fas fa-chevron-down filter-group-arrow"></i>
                </div>
                <div class="filter-group-body">`;
            filter.options.forEach(opt => {
                html += `<label class="filter-check-label">
                    <input type="checkbox" data-filter-id="${filter.id}" value="${opt.value}">
                    <span>${opt.label}</span>
                </label>`;
            });
            html += `</div></div>`;
        }
    });
    html += `</div>
            <div class="mother-filter-actions">
                <button class="filter-btn filter-btn-find" onclick="applyCustomFilters('${tableName}')" type="button">Find</button>
                <button class="filter-btn filter-btn-clear" onclick="clearCustomFilters('${tableName}')" type="button">Clear</button>
            </div>
        </div>
    </div>`;
    return html;
}

// Toggle accordion section open/closed
function toggleFilterGroup(titleEl) {
    const body = titleEl.nextElementSibling;
    const arrow = titleEl.querySelector('.filter-group-arrow');
    if (body) {
        body.classList.toggle('open');
        if (arrow) arrow.classList.toggle('open');
    }
}

// Attach event listeners to table controls
function attachTableEventListeners(tableName, options) {
    // Set initial rows per page value
    const rowsSelect = document.getElementById(`${tableName}-rows`);
    if (rowsSelect) {
        rowsSelect.value = TableState[tableName].rowsPerPage;
    }
}

// Handle search input
function handleTableSearch(tableName, searchTerm) {
    TableState[tableName].searchTerm = searchTerm.toLowerCase();
    TableState[tableName].currentPage = 1; // Reset to first page on search
    refreshTable(tableName);
}

// Handle filter change
function handleTableFilter(tableName, filterId, value) {
    TableState[tableName].filters[filterId] = value;
    TableState[tableName].currentPage = 1; // Reset to first page on filter change
    refreshTable(tableName);
}

// Toggle filter panel visibility
function toggleFilterPanel(tableName) {
    const panel = document.getElementById(`${tableName}-filter-panel`);
    if (panel) panel.classList.toggle('open');
}

// Apply selected checkboxes as filters
function applyCustomFilters(tableName) {
    const panel = document.getElementById(`${tableName}-filter-panel`);
    if (!panel) return;

    const checked = panel.querySelectorAll('input[type="checkbox"]:checked');
    const filters = {};
    checked.forEach(cb => {
        const id = cb.dataset.filterId;
        if (!filters[id]) filters[id] = [];
        filters[id].push(cb.value);
    });

    const allIds = [...new Set([...panel.querySelectorAll('input[type="checkbox"]')].map(cb => cb.dataset.filterId))];
    const state = TableState[tableName];
    allIds.forEach(id => { state.filters[id] = filters[id] || []; });

    state.currentPage = 1;
    panel.classList.remove('open');
    refreshTable(tableName);
}

// Clear all checkboxes and filters
function clearCustomFilters(tableName) {
    const panel = document.getElementById(`${tableName}-filter-panel`);
    if (!panel) return;

    panel.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);

    const allIds = [...new Set([...panel.querySelectorAll('input[type="checkbox"]')].map(cb => cb.dataset.filterId))];
    const state = TableState[tableName];
    allIds.forEach(id => { state.filters[id] = []; });

    state.currentPage = 1;
    panel.classList.remove('open');
    refreshTable(tableName);
}

// Handle rows per page change
function handleRowsPerPageChange(tableName, value) {
    TableState[tableName].rowsPerPage = parseInt(value);
    TableState[tableName].currentPage = 1; // Reset to first page
    refreshTable(tableName);
}

// Apply search and filters to data
function applyTableFilters(tableName, data, searchFields = ['full_name', 'employee_id', 'emp_code']) {
    const state = TableState[tableName];
    let filtered = data;
    
    // Apply search
    if (state.searchTerm) {
        filtered = filtered.filter(item => {
            return searchFields.some(field => {
                const value = item[field];
                return value && value.toString().toLowerCase().includes(state.searchTerm);
            });
        });
    }
    
    // Apply filters
    Object.keys(state.filters).forEach(filterId => {
        const filterValue = state.filters[filterId];
        if (Array.isArray(filterValue)) {
            if (filterValue.length > 0) {
                filtered = filtered.filter(item => {
                    if (filterId === 'position') return filterValue.includes(item.position);
                    if (filterId === 'department') return filterValue.includes(item.department);
                    if (filterId === 'status') return filterValue.includes(item.status);
                    return true;
                });
            }
        } else if (filterValue) {
            filtered = filtered.filter(item => {
                if (filterId === 'position') return item.position === filterValue;
                if (filterId === 'department') return item.department === filterValue;
                if (filterId === 'status') return item.status === filterValue;
                if (filterId === 'date') return item.log_date === filterValue;
                if (filterId === 'dateFrom') return item.log_date >= filterValue;
                if (filterId === 'dateTo') return item.log_date <= filterValue;
                if (filterId === 'period') return item.period === filterValue;
                return true;
            });
        }
    });
    
    state.filteredData = filtered;
    return filtered;
}

// Get paginated data
function getPaginatedData(tableName, filteredData) {
    const state = TableState[tableName];
    const start = (state.currentPage - 1) * state.rowsPerPage;
    const end = start + state.rowsPerPage;
    return filteredData.slice(start, end);
}

// Render pagination controls
function renderPagination(tableName, totalItems) {
    const state = TableState[tableName];
    const totalPages = Math.ceil(totalItems / state.rowsPerPage);
    const paginationContainer = document.getElementById(`${tableName}-pagination`);
    
    if (!paginationContainer) return;
    
    const startItem = totalItems === 0 ? 0 : (state.currentPage - 1) * state.rowsPerPage + 1;
    const endItem = Math.min(state.currentPage * state.rowsPerPage, totalItems);
    
    let html = `
        <div class="pagination-info">
            Showing <strong>${startItem}-${endItem}</strong> of <strong>${totalItems}</strong> records
        </div>
        <div class="pagination-controls">
            <button class="pagination-btn" onclick="changePage('${tableName}', 1)" ${state.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-angle-double-left"></i> First
            </button>
            <button class="pagination-btn" onclick="changePage('${tableName}', ${state.currentPage - 1})" ${state.currentPage === 1 ? 'disabled' : ''}>
                <i class="fas fa-angle-left"></i> Prev
            </button>
    `;
    
    // Page numbers
    html += '<div class="pagination-numbers">';
    const maxVisiblePages = 5;
    let startPage = Math.max(1, state.currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage < maxVisiblePages - 1) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="page-number ${i === state.currentPage ? 'active' : ''}" onclick="changePage('${tableName}', ${i})">${i}</button>`;
    }
    html += '</div>';
    
    html += `
            <button class="pagination-btn" onclick="changePage('${tableName}', ${state.currentPage + 1})" ${state.currentPage === totalPages || totalPages === 0 ? 'disabled' : ''}>
                Next <i class="fas fa-angle-right"></i>
            </button>
            <button class="pagination-btn" onclick="changePage('${tableName}', ${totalPages})" ${state.currentPage === totalPages || totalPages === 0 ? 'disabled' : ''}>
                Last <i class="fas fa-angle-double-right"></i>
            </button>
        </div>
    `;
    
    paginationContainer.innerHTML = html;
}

// Change page
function changePage(tableName, page) {
    const state = TableState[tableName];
    const filteredData = state.filteredData;
    const totalPages = Math.ceil(filteredData.length / state.rowsPerPage);
    
    if (page < 1 || page > totalPages) return;
    
    state.currentPage = page;
    refreshTable(tableName);
}

// Refresh table display
function refreshTable(tableName) {
    switch(tableName) {
        case 'employees':
            renderEmployeeTable();
            break;
        case 'attendance':
            renderAttendanceTable();
            break;
        case 'payroll':
            renderPayrollTable();
            break;
        case 'facultyPayroll':
            loadFacultyPayroll('latest');
            break;
        case 'utilityPayroll':
            loadUtilityPayroll('latest');
            break;
        case 'specializedPayroll':
            loadSpecializedPayroll(currentPayrollType || 'faculty', 'latest');
            break;
    }
}

// ==========================================
// END TABLE PAGINATION SYSTEM
// ==========================================

// Helper function for glass morphism modal dialogs
function showGlassModal(options = {}) {
    const defaultOptions = {
        customClass: {
            popup: 'glass-modal',
            container: 'glass-backdrop',
            backdrop: 'swal2-backdrop'
        },
        background: 'transparent',
        backdrop: 'rgba(0,0,0,0.6)',
        showClass: {
            popup: 'swal2-show'
        },
        hideClass: {
            popup: 'swal2-hide'
        }
    };  
    const mergedOptions = { ...defaultOptions, ...options };
    return Swal.fire(mergedOptions);
}

// Add keyframes for animations if not in CSS
if (!document.getElementById('toast-styles')) {
    const style = document.createElement('style');
    style.id = 'toast-styles';
    style.innerHTML = `
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
        
        /* Glass Morphism Toast Notifications */
        .glass-toast-popup {
            background: rgba(255, 255, 255, 0.15) !important;
            backdrop-filter: blur(25px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(25px) saturate(180%) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            border-radius: 20px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25), inset 0 1px 1px rgba(255, 255, 255, 0.4) !important;
            padding: 16px 20px !important;
            min-width: 320px !important;
            max-width: 400px !important;
            z-index: 100001 !important;
            position: relative !important;
        }
        
        .glass-toast-popup.swal2-show {
            animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        
        /* Toast container - highest z-index */
        .swal2-container.swal2-top-end,
        .swal2-container.swal2-top-right {
            z-index: 100001 !important;
            pointer-events: none !important;
        }
        
        .swal2-container.swal2-top-end > .swal2-popup,
        .swal2-container.swal2-top-right > .swal2-popup {
            pointer-events: auto !important;
        }
        
        .glass-toast-popup.swal2-icon-success {
            background: rgba(39, 174, 96, 0.15) !important;
            border: 1px solid rgba(39, 174, 96, 0.3) !important;
        }
        
        .glass-toast-popup.swal2-icon-error,
        .glass-toast-popup.swal2-icon-warning {
            background: rgba(219, 38, 31, 0.15) !important;
            border: 1px solid rgba(219, 38, 31, 0.3) !important;
        }
        
        .glass-toast-popup.swal2-icon-info {
            background: rgba(30, 1, 120, 0.15) !important;
            border: 1px solid rgba(30, 1, 120, 0.3) !important;
        }
        
        .glass-toast-title {
            color: #ffffff !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            letter-spacing: 1px !important;
            text-shadow: 0 0 4px rgba(0, 0, 0, 0.9), 0 0 4px rgba(0, 0, 0, 0.9) !important;
        }
        
        .swal2-glass-toast .swal2-icon {
            margin: 0 !important;
            width: 24px !important;
            height: 24px !important;
            font-size: 18px !important;
            border-width: 2px !important;
        }
        
        .swal2-glass-toast .swal2-icon.swal2-success {
            border-color: rgba(39, 174, 96, 0.8) !important;
            color: #27ae60 !important;
        }
        
        .swal2-glass-toast .swal2-icon.swal2-error {
            border-color: rgba(219, 38, 31, 0.8) !important;
            color: #db261f !important;
        }
        
        .swal2-glass-toast .swal2-icon.swal2-warning {
            border-color: rgba(243, 156, 18, 0.8) !important;
            color: #f39c12 !important;
        }
        
        .swal2-glass-toast .swal2-icon.swal2-info {
            border-color: rgba(30, 1, 120, 0.8) !important;
            color: #1e0178 !important;
        }
        
        .glass-toast-progress {
            height: 3px !important;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.6)) !important;
            border-radius: 2px !important;
        }
        
        .glass-toast-popup.swal2-icon-success .glass-toast-progress {
            background: linear-gradient(90deg, rgba(39, 174, 96, 0.4), rgba(39, 174, 96, 0.8)) !important;
        }
        
        .glass-toast-popup.swal2-icon-error .glass-toast-progress,
        .glass-toast-popup.swal2-icon-warning .glass-toast-progress {
            background: linear-gradient(90deg, rgba(219, 38, 31, 0.4), rgba(219, 38, 31, 0.8)) !important;
        }
        
        .glass-toast-popup.swal2-icon-info .glass-toast-progress {
            background: linear-gradient(90deg, rgba(30, 1, 120, 0.4), rgba(30, 1, 120, 0.8)) !important;
        }
        
        /* Remove default SweetAlert2 background */
        .swal2-container.swal2-top-end {
            background: transparent !important;
        }
        
        .swal2-glass-toast {
            background: transparent !important;
            box-shadow: none !important;
        }
        
        /* Glass Morphism Modal Popups */
        .swal2-popup.glass-modal {
            background: rgba(255, 255, 255, 0.15) !important;
            backdrop-filter: blur(25px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(25px) saturate(180%) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            border-radius: 20px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25), inset 0 1px 1px rgba(255, 255, 255, 0.4) !important;
            padding: 30px !important;
        }
        
        .swal2-popup.glass-modal .swal2-title {
            color: #ffffff !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
            font-size: 24px !important;
            font-weight: 700 !important;
            letter-spacing: 0.5px !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2) !important;
        }
        
        .swal2-popup.glass-modal .swal2-html-container,
        .swal2-popup.glass-modal .swal2-text {
            color: rgba(255, 255, 255, 0.9) !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
            font-size: 15px !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15) !important;
        }
        
        .swal2-popup.glass-modal .swal2-icon {
            border-width: 3px !important;
            margin: 0 auto 20px !important;
        }
        
        .swal2-popup.glass-modal .swal2-icon.swal2-success {
            border-color: rgba(39, 174, 96, 0.8) !important;
            color: #27ae60 !important;
        }
        
        .swal2-popup.glass-modal .swal2-icon.swal2-error {
            border-color: rgba(219, 38, 31, 0.8) !important;
            color: #db261f !important;
        }
        
        .swal2-popup.glass-modal .swal2-icon.swal2-warning {
            border-color: rgba(243, 156, 18, 0.8) !important;
            color: #f39c12 !important;
        }
        
        .swal2-popup.glass-modal .swal2-icon.swal2-info {
            border-color: rgba(30, 1, 120, 0.8) !important;
            color: #1e0178 !important;
        }
        
        .swal2-popup.glass-modal .swal2-icon.swal2-question {
            border-color: rgba(108, 117, 125, 0.8) !important;
            color: #6c757d !important;
        }
        
        .swal2-popup.glass-modal .swal2-confirm,
        .swal2-popup.glass-modal .swal2-cancel {
            border-radius: 20px !important;
            padding: 12px 24px !important;
            font-weight: 600 !important;
            font-size: 14px !important;
            letter-spacing: 0.3px !important;
            transition: all 0.3s ease !important;
            border: none !important;
        }
        
        .swal2-popup.glass-modal .swal2-confirm {
            background: linear-gradient(135deg, #4facfe, #00f2fe) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3) !important;
        }
        
        .swal2-popup.glass-modal .swal2-confirm:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px rgba(79, 172, 254, 0.4) !important;
        }
        
        .swal2-popup.glass-modal .swal2-cancel {
            background: rgba(255, 255, 255, 0.15) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            color: #ffffff !important;
            backdrop-filter: blur(10px) !important;
        }
        
        .swal2-popup.glass-modal .swal2-cancel:hover {
            background: rgba(255, 255, 255, 0.25) !important;
        }
        
        .swal2-popup.glass-modal .swal2-input,
        .swal2-popup.glass-modal .swal2-textarea,
        .swal2-popup.glass-modal .swal2-select {
            background: rgba(255, 255, 255, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 20px !important;
            color: #ffffff !important;
            backdrop-filter: blur(10px) !important;
            padding: 12px 15px !important;
        }
        
        .swal2-popup.glass-modal .swal2-input:focus,
        .swal2-popup.glass-modal .swal2-textarea:focus,
        .swal2-popup.glass-modal .swal2-select:focus {
            border: 1px solid rgba(255, 255, 255, 0.6) !important;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.4) !important;
            outline: none !important;
        }
        
        .swal2-popup.glass-modal .swal2-input::placeholder,
        .swal2-popup.glass-modal .swal2-textarea::placeholder {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        
        .swal2-popup.glass-modal .swal2-validation-message {
            background: rgba(219, 38, 31, 0.15) !important;
            border: 1px solid rgba(219, 38, 31, 0.3) !important;
            color: #ffffff !important;
            border-radius: 12px !important;
        }
        
        .swal2-popup.glass-modal .swal2-loader {
            border-color: rgba(30, 1, 120, 0.3) !important;
            border-top-color: #1e0178 !important;
        }
        
        /* Glass modal backdrop */
        .swal2-container.glass-backdrop {
            background: rgba(0, 0, 0, 0.5) !important;
            backdrop-filter: blur(4px) !important;
        }
    `;
    document.head.appendChild(style);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

function filterTable(input, tableId) {
    const filter = input.value.toLowerCase();
    const rows = document.getElementById(tableId).getElementsByTagName('tr');
    for (let i = 1; i < rows.length; i++) {
        const text = rows[i].textContent.toLowerCase();
        rows[i].style.display = text.includes(filter) ? '' : 'none';
    }
}

// --- Data Fetching ---
const fetchJSON = async (url) => {
    const res = await fetch(url);
    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
    const text = await res.text();
    try {
        return JSON.parse(text);
    } catch (e) {
        console.error("Malformed JSON from " + url + ":", text);
        return null;
    }
};

async function fetchData(specificPage = null) {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) loadingOverlay.style.display = 'flex';

    const urlParams = new URLSearchParams(window.location.search);
    const page = specificPage || urlParams.get('page') || 'dashboard';

    try {
        const getArray = (data) => Array.isArray(data) ? data : [];

        // Always fetch employees as they are used globally
        if (employees.length === 0 || page === 'employees') {
            employees = getArray(await fetchJSON('backend/api.php?action=get_employees'));
        }

        // Conditional fetching based on page
        if (page === 'dashboard') {
            // Fetch all data needed for dashboard with real attendance logs
            const dashboardStats = await fetchJSON('backend/api.php?action=get_dashboard_stats');
            if (dashboardStats) {
                const totalEl = document.getElementById('stat-total-emp');
                const presentEl = document.getElementById('stat-present');
                const absentEl = document.getElementById('stat-absent');
                const leaveEl = document.getElementById('stat-leave');
                if (totalEl) totalEl.innerText = dashboardStats.total_employees;
                if (presentEl) presentEl.innerText = dashboardStats.present_today;
                if (absentEl) absentEl.innerText = dashboardStats.absent_today;

                // Still need leave requests for the count
                leaveRequests = getArray(await fetchJSON('backend/api.php?action=get_leave_requests'));
                if (leaveEl) leaveEl.innerText = leaveRequests.filter(r => r.status === 'Pending').length;
            }
            
            // Fetch attendance logs for charts (last 30 days for better data)
            attendanceLogs = getArray(await fetchJSON('backend/api.php?action=get_attendance'));
            
            // Fetch payroll history for payroll chart
            payrollHistory = getArray(await fetchJSON('backend/api.php?action=get_payroll'));
        } else if (page === 'attendance') {
            attendanceLogs = getArray(await fetchJSON('backend/api.php?action=get_attendance'));
        } else if (page === 'payroll' || page === 'payroll_specialized') {
            payrollHistory = getArray(await fetchJSON('backend/api.php?action=get_payroll'));
        } else if (page === 'leave') {
            leaveRequests = getArray(await fetchJSON('backend/api.php?action=get_leave_requests'));
        } else if (page === 'loans') {
            loanRequests = getArray(await fetchJSON('backend/api.php?action=get_loan_requests'));
        } else if (page === 'resignations') {
            resignationRequests = getArray(await fetchJSON('backend/api.php?action=get_resignation_requests'));
        } else if (page === 'subject_loads' || page === 'employees') {
            masterSubjects = getArray(await fetchJSON('backend/api.php?action=get_subjects'));
            subjectLoads = getArray(await fetchJSON('backend/api.php?action=get_subject_loads'));
        } else if (page === 'allowances' || page === 'deductions') {
            // Already handled by their respective render functions called in showPage
        }

        showPage(page);

    } catch (error) {
        console.error("Error fetching data:", error);
    } finally {
        if (loadingOverlay) {
            loadingOverlay.style.opacity = '0';
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
                loadingOverlay.style.opacity = '1';
            }, 300);
        }
    }
}

// --- Navigation ---
function stopRegistrationCamera() {
    if (window.faceManager) {
        faceManager.stopCamera();
        faceManager.isProcessing = false;
        faceManager.registrationActive = false;
    }

    const video = document.getElementById('video');
    const canvas = document.getElementById('overlay');
    if (video) video.srcObject = null;

    // Clear the canvas explicitly
    if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    const placeholder = document.getElementById('camera-placeholder');
    if (placeholder) {
        placeholder.style.display = 'flex';
        // Reset placeholder text to default state
        const p = placeholder.querySelector('p');
        const small = placeholder.querySelector('small');
        const icon = placeholder.querySelector('i');
        if (p) p.innerText = "Camera is currently inactive";
        if (small) small.innerText = 'Select an employee and click "Start Registration"';
        if (icon) icon.className = "fas fa-video-slash";
    }

    const startBtn = document.getElementById('startRegBtn');
    if (startBtn) {
        startBtn.style.display = 'inline-block';
        startBtn.innerHTML = '<i class="fas fa-camera"></i> Start Registration';
        startBtn.disabled = false;
    }

    const captureBtn = document.getElementById('captureBtn');
    if (captureBtn) {
        captureBtn.style.display = 'none';
        captureBtn.disabled = false;
        captureBtn.innerHTML = '<i class="fas fa-user-plus"></i> Manual Capture';
    }
}

function showPage(pageId) {
    if (currentPage === 'biometrics' && pageId !== 'biometrics') {
        stopRegistrationCamera();
    }
    currentPage = pageId;
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-btn, .nav-link').forEach(l => l.classList.remove('active'));

    const activePage = document.getElementById(pageId);
    if (activePage) activePage.classList.add('active');

    const activeLink = document.querySelector(`.nav-btn[onclick*="${pageId}"], .nav-link[onclick*="${pageId}"]`);
    if (activeLink) activeLink.classList.add('active');

    // Update Page Title
    const titles = {
        'dashboard': 'Dashboard Overview',
        'employees': 'Employee Directory',
        'attendance': 'Attendance Tracking',
        'payroll': 'Payroll Processing',
        'payroll_specialized': 'Specialized Payroll',
        'allowances': 'Allowances & Benefits',
        'deductions': 'Deductions Management',
        'leave': 'Leave Management',
        'loans': 'Cash Advance',
        'resignations': 'Resignations',
        'reports': 'System Reports',
        'subject_loads': 'Subject Load Management',
        'settings': 'Company Settings',
        'biometrics': 'Face Registration'
    };
    const titleEl = document.getElementById('current-page-title');
    if (titleEl) titleEl.innerText = titles[pageId] || 'Admin Hub';

    // Render respective tables
    if (pageId === 'employees') renderEmployeeTable();
    if (pageId === 'attendance') renderAttendanceTable();
    if (pageId === 'payroll') renderPayrollTable();
    if (pageId === 'payroll_specialized') loadSpecializedPayroll('faculty', 'latest');
    if (pageId === 'allowances') renderAllowances();
    if (pageId === 'deductions') renderDeductions();
    if (pageId === 'leave') renderLeaveTable();
    if (pageId === 'loans') renderLoanTable();
    if (pageId === 'resignations') renderResignationTable();
    if (pageId === 'subject_loads') renderMasterSubjects();
    if (pageId === 'biometrics') populateRegistrationSelect();
    if (pageId === 'dashboard') initCharts();
}

function populateRegistrationSelect() {
    const select = document.getElementById('regEmployeeSelect');
    if (!select) return;

    select.innerHTML = '<option value="">Choose Employee...</option>' +
        employees.map(emp => `<option value="${emp.id}">${escapeHTML(emp.full_name)} (${escapeHTML(emp.employee_id)})</option>`).join('');
}

// --- Render Functions ---
function renderEmployeeTable() {
    const tbody = document.getElementById('employeeTableBody');
    if (!tbody) return;

    // Initialize table with pagination and search
    if (!document.getElementById('employees-search')) {
        initializeTable('employees', {
            rowsPerPage: 10,
            showSearch: true,
            showFilters: true,
            useMotherFilter: true,
            filters: [
                {
                    id: 'position',
                    label: 'Position',
                    type: 'select',
                    options: [
                        { value: 'Faculty', label: 'Faculty' },
                        { value: 'Staff', label: 'Staff' },
                        { value: 'Utility', label: 'Utility' },
                        { value: 'Payroll Officer', label: 'Payroll Officer' }
                    ]
                },
                {
                    id: 'department',
                    label: 'Department',
                    type: 'select',
                    options: [
                        { value: 'IT', label: 'Information Technology' },
                        { value: 'Education', label: 'Education' },
                        { value: 'Admin', label: 'Administration' },
                        { value: 'Utility', label: 'General Services' }
                    ]
                },
                {
                    id: 'status',
                    label: 'Status',
                    type: 'select',
                    options: [
                        { value: 'Active', label: 'Active' },
                        { value: 'Probationary', label: 'Probationary' },
                        { value: 'Contractual', label: 'Contractual' },
                        { value: 'Resigned', label: 'Resigned' }
                    ]
                }
            ]
        });
    }

    // Apply filters and search
    const filteredEmployees = applyTableFilters('employees', employees, ['full_name', 'employee_id', 'emp_code']);
    
    // Get paginated data
    const paginatedEmployees = getPaginatedData('employees', filteredEmployees);
    
    // Render table rows
    tbody.innerHTML = paginatedEmployees.map(emp => {
        const isFaculty = (emp.position || '').toLowerCase() === 'faculty';
        const loadCount = subjectLoads.filter(load => load.faculty_id == emp.id).length;
        const facultyLevel = (isFaculty && emp.faculty_level) ? emp.faculty_level : '---';
        const hireDate = emp.hire_date ? new Date(emp.hire_date).toLocaleDateString() : '---';
        const isResigned = (emp.status || 'Active') === 'Resigned';
        const statusLabel = emp.status || 'Active';
        const statusClass = statusLabel.toLowerCase().replace(/\s+/g, '-');

        const actionHtml = isFaculty ? `
            <button class="btn btn-info btn-sm" onclick="viewFacultyLoads('${emp.id}')" title="View Subject Loads">
                <i class="fas fa-book"></i> <span class="badge">${loadCount}</span>
            </button>
        ` : '<span class="text-muted">---</span>';

        const buttonsHtml = isResigned ? `
            <div class="action-buttons">
                <button class="btn-icon text-success" title="Reinstate Employee" onclick="reinstateEmployee('${emp.id}', '${escapeHTML(emp.full_name)}')"><i class="fas fa-user-check"></i></button>
                <button class="btn-icon" title="Edit Employee" onclick="editEmployee('${emp.id}')"><i class="fas fa-edit"></i></button>
            </div>
        ` : `
            <div class="action-buttons">
                <button class="btn-icon" title="Edit Employee" onclick="editEmployee('${emp.id}')"><i class="fas fa-edit"></i></button>
                <button class="btn-icon text-danger" title="Delete Employee" onclick="deleteEmployee('${emp.id}')"><i class="fas fa-trash"></i></button>
            </div>
        `;

        return `
            <tr>
                <td><strong>${escapeHTML(emp.employee_id)}</strong></td>
                <td>
                    <div class="user-info">
                        <img src="${emp.profile_picture ? escapeHTML(emp.profile_picture) : `https://ui-avatars.com/api/?name=${encodeURIComponent(emp.full_name)}&size=40&background=random`}" 
                             alt="${escapeHTML(emp.full_name)}" 
                             class="employee-avatar">
                        <div class="user-details">
                            <span class="name">${escapeHTML(emp.full_name)}</span>
                            <span class="email">${escapeHTML(emp.email || 'No email')}</span>
                        </div>
                    </div>
                </td>
                <td><span class="position-badge">${escapeHTML(emp.position)}</span></td>
                <td><span class="text-muted">${escapeHTML(emp.work_position || '---')}</span></td>
                <td>${escapeHTML(emp.department || '---')}</td>
                <td>${isFaculty ? `<span class="faculty-badge faculty-${facultyLevel.toLowerCase()}">${escapeHTML(facultyLevel)}</span>` : '<span class="text-muted">---</span>'}</td>
                <td><span class="hire-date">${hireDate}</span></td>
                <td>${actionHtml}</td>
                <td><span class="status-badge status-${statusClass}">${escapeHTML(statusLabel)}</span></td>
                <td><span class="text-muted">${escapeHTML(emp.work_status || '---')}</span></td>
                <td>${buttonsHtml}</td>
            </tr>
        `;
    }).join('') || '<tr><td colspan="11" class="text-center text-muted">No employees found.</td></tr>';
    
    // Render pagination
    renderPagination('employees', filteredEmployees.length);
}

function renderMasterSubjects() {
    const subjectTbody = document.getElementById('subjectsTableBody');
    if (subjectTbody) {
        subjectTbody.innerHTML = masterSubjects.map(s => `
            <tr>
                <td><strong>${escapeHTML(s.code)}</strong></td>
                <td>${escapeHTML(s.description)}</td>
                <td>${escapeHTML(s.units)}</td>
                <td>${escapeHTML(s.hours)}</td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick="editMasterSubject('${s.id}')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="deleteMasterSubject('${s.id}')"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="text-center">No subjects created yet.</td></tr>';
    }
}

// --- Reports & Export ---

// Payroll History Print/Export Functions (NEW - Fixes broken buttons)
async function printPayrollHistory() {
    const tableRows = document.querySelectorAll("#payrollTableBody tr");
    if (tableRows.length === 0 || tableRows[0].textContent.includes("No data")) {
        showToast("No payroll history data available to print.", 'error');
        return;
    }

    const totalBatchesEl = document.getElementById('stat-total-batches');
    const totalDisbursedEl = document.getElementById('stat-total-disbursed');
    const lastRunEl = document.getElementById('stat-last-run');

    const printWindow = window.open('', '', 'height=800,width=1200');

    printWindow.document.write('<html><head><title>Payroll History Report</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('@page { size: landscape; margin: 10mm; }');
    printWindow.document.write('body { font-family: "Inter", sans-serif; color: #333; margin: 0; padding: 20px; }');
    printWindow.document.write('h1 { color: #1e0178; margin-bottom: 5px; font-size: 24px; text-align: center; }');
    printWindow.document.write('.stats { display: flex; gap: 20px; margin-bottom: 30px; }');
    printWindow.document.write('.stat { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; flex: 1; }');
    printWindow.document.write('.stat-label { font-size: 12px; color: #666; }');
    printWindow.document.write('.stat-value { font-size: 18px; font-weight: bold; color: #1e0178; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; font-size: 11px; }');
    printWindow.document.write('th { background-color: #1e0178 !important; color: white !important; -webkit-print-color-adjust: exact; padding: 12px 8px; border: 1px solid #444; }');
    printWindow.document.write('td { border: 1px solid #ddd; padding: 10px 8px; }');
    printWindow.document.write('tr:nth-child(even) { background-color: #f9f9f9; }');
    printWindow.document.write('.status-active { color: #27ae60; font-weight: bold; }');
    printWindow.document.write('</style></head><body>');

    printWindow.document.write('<h1>PAYROLL HISTORY REPORT</h1>');

    // Print Stats Summary
    printWindow.document.write('<div class="stats">');
    printWindow.document.write(`<div class="stat"><div class="stat-label">Total Batches</div><div class="stat-value">${totalBatchesEl?.textContent || '0'}</div></div>`);
    printWindow.document.write(`<div class="stat"><div class="stat-label">Total Disbursed</div><div class="stat-value">${totalDisbursedEl?.textContent || '₱0.00'}</div></div>`);
    printWindow.document.write(`<div class="stat"><div class="stat-label">Last Run</div><div class="stat-value">${lastRunEl?.textContent || '---'}</div></div>`);
    printWindow.document.write('</div>');

    // Clone table
    const table = document.getElementById('payrollTable').cloneNode(true);
    table.querySelectorAll('button, .btn').forEach(el => el.remove());
    printWindow.document.write(table.outerHTML);

    printWindow.document.write('</body></html>');
    printWindow.document.close();

    printWindow.onload = () => {
        printWindow.print();
    };

    showToast('Print preview opened. Use browser print dialog to print/save PDF.', 'success');
}

async function exportPayrollHistory() {
    const { jsPDF } = window.jspdf;

    const tableRows = document.querySelectorAll("#payrollTableBody tr");
    if (tableRows.length === 0 || tableRows[0].textContent.includes("No data")) {
        showToast("No payroll history data available to export.", 'error');
        return;
    }

    const doc = new jsPDF('l', 'mm', 'a4');
    const totalBatchesEl = document.getElementById('stat-total-batches');
    const totalDisbursedEl = document.getElementById('stat-total-disbursed');
    const lastRunEl = document.getElementById('stat-last-run');

    // Header
    doc.setFontSize(18);
    doc.text('PAYROLL HISTORY SUMMARY', 14, 15);
    doc.setFontSize(12);
    doc.text(`Generated: ${new Date().toLocaleDateString('en-PH')} ${new Date().toLocaleTimeString('en-PH')}`, 14, 25);

    // Stats
    doc.setFontSize(10);
    doc.text(`Total Batches: ${totalBatchesEl?.textContent || '0'} | Total Disbursed: ${totalDisbursedEl?.textContent || '₱0.00'} | Last Run: ${lastRunEl?.textContent || '---'}`, 14, 35);

    // Table data
    const rows = [];
    tableRows.forEach(tr => {
        const cells = Array.from(tr.querySelectorAll('td'));
        if (cells.length >= 6) {
            rows.push([
                cells[0].textContent.trim(),
                cells[1].textContent.trim(),
                cells[2].textContent.replace('₱', '').trim(),
                cells[3].textContent.trim(),
                cells[4].textContent.trim(),
                cells[5].textContent.trim()
            ]);
        }
    });

    doc.autoTable({
        head: [['PAYROLL BATCH', 'PERIOD', 'TOTAL DISBURSED', 'PROCESSING DATE', 'CREATED BY', 'STATUS']],
        body: rows,
        startY: 45,
        styles: { fontSize: 9, cellPadding: 3, halign: 'center' },
        headStyles: {
            fillColor: [30, 1, 120],
            textColor: 255,
            fontStyle: 'bold',
            halign: 'center'
        },
        columnStyles: { 2: { halign: 'right' } }, // Right-align amounts
        margin: { top: 45 }
    });

    doc.save(`Payroll_History_${new Date().toISOString().split('T')[0]}.pdf`);
    showToast('Payroll history exported as PDF!', 'success');
}// --- Reports & Export ---
async function exportFacultyPayroll() {
    const { jsPDF } = window.jspdf;

    // Check if table is empty, if so, load latest
    let tableRows = document.querySelectorAll("#facultyPayrollTableBody tr");
    if (tableRows.length === 0 || tableRows[0].innerText.includes("No faculty payroll")) {
        await loadFacultyPayroll('latest');
        tableRows = document.querySelectorAll("#facultyPayrollTableBody tr");
    }

    if (tableRows.length === 0 || tableRows[0].innerText.includes("No faculty payroll")) {
        return showToast("No payroll data available to export.", 'error');
    }

    const doc = new jsPDF('l', 'mm', 'a3'); // Using A3 for 17 columns
    const period = document.getElementById('faculty-payroll-period').innerText;

    doc.setFontSize(18);
    doc.text("FACULTY PAYROLL REPORT", 14, 15);
    doc.setFontSize(11);
    doc.text(`Period: ${period}`, 14, 22);

    const rows = [];
    tableRows.forEach(tr => {
        const row = [];
        tr.querySelectorAll("td").forEach(td => {
            row.push(td.innerText.replace('₱', '').trim());
        });
        rows.push(row);
    });

    doc.autoTable({
        head: [['No.', 'Name', 'Basic Pay', 'Earned for the Period', 'Load', 'Over Time', 'Differential', 'Substitution', 'Adj. (+)', 'Absences', 'Latest/UT', 'HDMF Cont.', 'HDMF Cash Advance', 'HDMF MP2', 'Total Deduction', 'Honorarium', 'Net Pay']],
        body: rows,
        startY: 30,
        styles: { fontSize: 7, cellPadding: 1 },
        headStyles: { fillColor: [30, 1, 120] }
    });

    doc.save(`Faculty_Payroll_${period.replace(/ /g, '_')}.pdf`);
}

async function exportUtilityPayroll() {
    const { jsPDF } = window.jspdf;

    // Check if table is empty, if so, load latest
    let tableRows = document.querySelectorAll("#utilityPayrollTableBody tr");
    if (tableRows.length === 0 || tableRows[0].innerText.includes("No utility payroll")) {
        await loadUtilityPayroll('latest');
        tableRows = document.querySelectorAll("#utilityPayrollTableBody tr");
    }

    if (tableRows.length === 0 || tableRows[0].innerText.includes("No utility payroll")) {
        return showToast("No payroll data available to export.", 'error');
    }

    const doc = new jsPDF('l', 'mm', 'a3');
    const period = document.getElementById('utility-payroll-period').innerText;

    doc.setFontSize(18);
    doc.text("UTILITY PAYROLL REPORT", 14, 15);
    doc.setFontSize(11);
    doc.text(`Period: ${period}`, 14, 22);

    const rows = [];
    tableRows.forEach(tr => {
        const row = [];
        tr.querySelectorAll("td").forEach(td => {
            row.push(td.innerText.replace('₱', '').trim());
        });
        rows.push(row);
    });

    doc.autoTable({
        head: [['No.', 'Name', 'Rate per Day', 'Earned for the Period', 'OT/ Holiday Pay', 'Adj.(+)', 'Latest/UT', 'Adj. (-)', 'HDMF Cont.', 'HDMF Cash Advance', 'Cash Advance', 'Total Deduction', 'Net Pay', 'ATM', 'Non ATM']],
        body: rows,
        startY: 30,
        styles: { fontSize: 8, cellPadding: 2 },
        headStyles: { fillColor: [30, 1, 120] }
    });

    doc.save(`Utility_Payroll_${period.replace(/ /g, '_')}.pdf`);
}

async function printSpecializedPayroll(tableId, title) {
    const isFaculty = tableId.startsWith('faculty');
    const periodId = isFaculty ? 'faculty-payroll-period' : 'utility-payroll-period';
    const tbodyId = isFaculty ? 'facultyPayrollTableBody' : 'utilityPayrollTableBody';

    // Check if table is empty, if so, load latest
    let tableRows = document.querySelectorAll(`#${tbodyId} tr`);
    if (tableRows.length === 0 || tableRows[0].innerText.includes("No faculty payroll") || tableRows[0].innerText.includes("No utility payroll")) {
        if (isFaculty) await loadFacultyPayroll('latest');
        else await loadUtilityPayroll('latest');
    }

    const period = document.getElementById(periodId).innerText;
    if (period === '---') {
        return showToast("No payroll data available to print.", 'error');
    }

    const printWindow = window.open('', '', 'height=800,width=1200');

    printWindow.document.write('<html><head><title>' + title + '</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('@page { size: landscape; margin: 10mm; }');
    printWindow.document.write('body { font-family: "Inter", sans-serif; color: #333; margin: 0; padding: 20px; }');
    printWindow.document.write('h1 { color: #1e0178; margin-bottom: 5px; font-size: 24px; text-align: center; }');
    printWindow.document.write('.period { text-align: center; color: #666; margin-bottom: 30px; font-size: 14px; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; font-size: 9px; table-layout: fixed; }');
    printWindow.document.write('th { background-color: #1e0178 !important; color: white !important; -webkit-print-color-adjust: exact; padding: 10px 5px; text-transform: uppercase; border: 1px solid #444; }');
    printWindow.document.write('td { border: 1px solid #ddd; padding: 8px 4px; text-align: center; word-wrap: break-word; }');
    printWindow.document.write('tr:nth-child(even) { background-color: #f9f9f9; }');
    printWindow.document.write('.text-success { color: #27ae60 !important; font-weight: bold; }');
    printWindow.document.write('</style></head><body>');
    printWindow.document.write('<h1>' + title + '</h1>');
    printWindow.document.write('<div class="period"><strong>Payroll Period:</strong> ' + period + '</div>');

    // Clone the table to avoid modifying the original UI
    const tableClone = document.getElementById(tableId).cloneNode(true);
    // Remove any action buttons or icons if they exist in the table
    tableClone.querySelectorAll('button, i, .btn').forEach(el => el.remove());

    printWindow.document.write(tableClone.outerHTML);
    printWindow.document.write('</body></html>');

    printWindow.document.close();

    // Wait for content to load before printing
    printWindow.onload = function () {
        printWindow.print();
        // Optional: printWindow.close();
    };
}

// --- Employee Management ---

function openAddLoadModal(empId) {
    const emp = employees.find(e => e.id == empId);
    if (!emp) return;

    document.getElementById('subjectLoadForm').reset();
    document.getElementById('loadFacultyId').value = empId;

    // Populate subject select
    const subjectSelect = document.getElementById('loadSubjectSelect');
    if (subjectSelect) {
        subjectSelect.innerHTML = '<option value="">-- Choose Subject --</option>' +
            masterSubjects.map(s => `<option value="${s.id}">${s.code} - ${s.description}</option>`).join('');
    }

    const modal = document.getElementById('addLoadModal');
    if (modal) {
        modal.style.display = 'block';
        const title = modal.querySelector('h3');
        if (title) title.innerText = `Add Subject Load for ${emp.full_name}`;
    }
}

function onLoadSubjectChange(subjectId) {
    if (!subjectId) return;
    const subject = masterSubjects.find(s => s.id == subjectId);
    if (subject) {
        document.getElementById('loadSubjectCode').value = subject.code;
        document.getElementById('loadDescription').value = subject.description;
        document.getElementById('loadUnits').value = subject.units;
        document.getElementById('loadHours').value = subject.hours;
    }
}

async function resetPassword(userId) {
    if (!userId) return showToast('This employee does not have a user account.', 'error');
    
    const confirmResult = await Swal.fire({
        title: 'Reset Password?',
        text: "Are you sure you want to reset this employee's password? A new secure password will be generated.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1e0178',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, reset it!'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch(`backend/api.php?action=reset_password&user_id=${userId}`);
        const result = await response.json();
        if (result.success) {
            // Show the new password in a secure alert
            Swal.fire({
                icon: 'success',
                title: 'Password Reset Successful',
                html: `<p>${result.message}</p><p class="mt-2"><strong>Please share this password securely with the employee.</strong></p>`,
                confirmButtonColor: '#1e0178'
            });
        } else {
            showToast('Error: ' + (result.message || 'Failed to reset password.'), 'error');
        }
    }
}

let editingEmployeeId = null;

function editEmployee(id) {
    const emp = employees.find(e => e.id == id);
    if (!emp) return;

    editingEmployeeId = id;
    openModal('employeeModal');

    const form = document.getElementById('employeeForm');
    
    // Parse full_name into firstName, lastName, and middleInitial
    const fullName = emp.full_name || '';
    const nameParts = fullName.split(' ');
    let firstName = '';
    let lastName = '';
    let middleInitial = '';
    
    if (nameParts.length >= 3) {
        // Format: "FirstName M. LastName"
        firstName = nameParts[0];
        middleInitial = nameParts[1].replace('.', ''); // Remove the period
        lastName = nameParts.slice(2).join(' ');
    } else if (nameParts.length === 2) {
        // Format: "FirstName LastName"
        firstName = nameParts[0];
        lastName = nameParts[1];
    } else {
        // Fallback
        firstName = nameParts[0] || '';
        lastName = '';
    }
    
    form.firstName.value = firstName;
    form.lastName.value = lastName;
    form.middleInitial.value = middleInitial;
    form.fullName.value = fullName; // Display in readonly field
    
    form.dob.value = emp.dob || '';
    form.email.value = emp.email || '';
    form.position.value = emp.position;
    if (form.work_position) form.work_position.value = emp.work_position || '';
    form.department.value = emp.department;
    
    // Set faculty level if exists
    if (form.faculty_level) {
        form.faculty_level.value = emp.faculty_level || '';
    }
    
    // Set hire date if exists
    if (form.hire_date) {
        form.hire_date.value = emp.hire_date || '';
    }
    
    form.basicSalary.value = emp.basic_salary;
    
    // Set work status if exists
    if (form.work_status) form.work_status.value = emp.work_status || '';
    form.sss.value = emp.sss || '';
    form.philhealth.value = emp.philhealth || '';
    form.tin.value = emp.tin || '';
    form.pagibig.value = emp.pagibig || '';

    // Toggle faculty level visibility
    toggleSubjectStep();

    document.querySelector('#employeeModal h3').innerText = 'Edit Employee';
    document.getElementById('saveBtn').innerText = 'Update Employee';
}

async function deleteEmployee(id) {
    const confirmResult = await Swal.fire({
        title: 'Delete Employee?',
        text: "Are you sure you want to delete this employee? This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, delete!'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch(`backend/api.php?action=delete_employee&id=${id}`);
        const result = await response.json();
        if (result.success) {
            showToast('Employee deleted successfully.', 'success');
            fetchData();
        } else {
            showToast(result.message || 'Failed to delete employee.', 'error');
        }
    }
}

async function reinstateEmployee(id, name) {
    const confirmResult = await Swal.fire({
        title: 'Reinstate Employee?',
        html: `<p>Are you sure you want to reinstate <strong>${name}</strong>?</p><p class="text-muted small">This will change their status back to Active.</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Reinstate',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch('backend/api.php?action=reinstate_employee', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Employee reinstated successfully', 'success');
            fetchData();
        } else {
            showToast(result.message || 'Failed to reinstate employee', 'error');
        }
    }
}

function addSubjectRow() {
    const container = document.getElementById('subjectRowsContainer');
    if (!container) return;
    const rowId = Date.now();
    const row = document.createElement('div');
    row.className = 'subject-row-item';
    row.id = `subject-${rowId}`;
    row.innerHTML = `
        <input type="text" placeholder="Subject Name/Description" class="form-control" name="subDesc[]">
        <input type="number" placeholder="Units" class="form-control text-center" name="subUnits[]" value="3">
        <button type="button" class="btn btn-danger btn-sm" onclick="removeSubjectRow('${rowId}')">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(row);
}

function removeSubjectRow(id) {
    const row = document.getElementById(`subject-${id}`);
    if (row) row.remove();
}

async function saveEmployee() {
    const form = document.getElementById('employeeForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    // Validate firstName and lastName are provided
    if (!data.firstName || data.firstName.trim() === '') {
        showToast('First Name is required', 'error');
        return;
    }
    if (!data.lastName || data.lastName.trim() === '') {
        showToast('Last Name is required', 'error');
        return;
    }

    // Add editing ID if exists
    if (editingEmployeeId) data.id = editingEmployeeId;

    // Handle subject rows if Faculty
    if (data.position === 'Faculty') {
        const subDescs = Array.from(document.querySelectorAll('input[name="subDesc[]"]')).map(i => i.value);
        const subUnits = Array.from(document.querySelectorAll('input[name="subUnits[]"]')).map(i => i.value);
        data.subjects = subDescs.map((desc, i) => ({ description: desc, units: subUnits[i] }));
    }

    const saveBtn = document.getElementById('saveBtn');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }

    try {
        const response = await fetch('backend/api.php?action=save_employee', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            closeModal('employeeModal');
            await fetchData();
            resetEmpModal();
            showToast('Employee saved successfully!', 'success');
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    } catch (err) {
        console.error("Save error:", err);
        showToast("Failed to connect to the server.", 'error');
    } finally {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-check-circle"></i> Save Employee';
        }
    }
}

// --- Multi-step Wizard Logic ---
let currentStep = 1;
const totalSteps = 4;

function toggleSubjectStep() {
    const position = document.querySelector('select[name="position"]').value;
    const step4Indicator = document.getElementById('step4-indicator');
    const facultyLevelGroup = document.getElementById('facultyLevelGroup');
    
    if (position === 'Faculty') {
        step4Indicator.style.opacity = '1';
        step4Indicator.style.pointerEvents = 'auto';
        facultyLevelGroup.style.display = 'block';
    } else {
        step4Indicator.style.opacity = '0.3';
        step4Indicator.style.pointerEvents = 'none';
        facultyLevelGroup.style.display = 'none';
    }
}

function goEmpStep(n) {
    const steps = document.querySelectorAll('.form-step');
    const indicators = document.querySelectorAll('.stepper-item');
    const position = document.querySelector('select[name="position"]').value;

    // Bulletproofing: Validate current step before going forward
    if (n > 0 && !validateCurrentStep()) return;

    let nextStep = currentStep + n;

    // Skip Step 4 (Subjects) if not Faculty
    if (nextStep === 4 && position !== 'Faculty') {
        if (n > 0) {
            saveEmployee(); // Finalize at step 3 for non-faculty
            return;
        }
    }

    // Update visibility
    steps[currentStep - 1].classList.remove('active');
    indicators[currentStep - 1].classList.remove('active');
    if (n > 0) indicators[currentStep - 1].classList.add('completed');

    currentStep = nextStep;

    steps[currentStep - 1].classList.add('active');
    indicators[currentStep - 1].classList.add('active');
    indicators[currentStep - 1].classList.remove('completed');

    // Button states
    document.getElementById('prevBtn').style.display = currentStep === 1 ? 'none' : 'inline-block';

    const isLastStep = (position === 'Faculty' && currentStep === 4) || (position !== 'Faculty' && currentStep === 3);

    document.getElementById('nextBtn').style.display = isLastStep ? 'none' : 'inline-block';
    document.getElementById('saveBtn').style.display = isLastStep ? 'inline-block' : 'none';
}

function validateCurrentStep() {
    const currentStepEl = document.getElementById(`step${currentStep}`);
    const inputs = currentStepEl.querySelectorAll('input[required], select[required]');
    let isValid = true;
    let firstErrorInput = null;

    inputs.forEach(input => {
        const errorMsg = input.parentElement.querySelector('.error-msg');
        let fieldError = null;

        // Check HTML5 validity
        if (!input.checkValidity()) {
            fieldError = input.validationMessage;
        }
        
        // Specific Email Check
        if (!fieldError && input.type === 'email' && input.value) {
            if (!validateEmail(input.value)) {
                fieldError = "Invalid email format. Example: user@example.com";
            }
        }
        
        // Phone number validation
        if (!fieldError && input.name === 'contactNo' && input.value) {
            if (!validatePhone(input.value)) {
                fieldError = "Invalid phone format. Use: 09XXXXXXXXX or +639XXXXXXXXX";
            }
        }
        
        // Salary validation
        if (!fieldError && input.name === 'basicSalary' && input.value) {
            if (!validateSalary(input.value)) {
                fieldError = "Salary must be between 0 and 10,000,000";
            }
        }
        
        // Date validation
        if (!fieldError && input.type === 'date' && input.value) {
            if (!validateDate(input.value)) {
                fieldError = "Invalid date format";
            }
        }
        
        // Government ID validation (optional fields)
        if (!fieldError && input.value) {
            if (input.name === 'sss' && !validateGovernmentID(input.value, 'sss')) {
                fieldError = "Invalid SSS format. Use: XX-XXXXXXX-X (10-11 digits)";
            }
            if (input.name === 'tin' && !validateGovernmentID(input.value, 'tin')) {
                fieldError = "Invalid TIN format. Use: XXX-XXX-XXX-XXX (9-12 digits)";
            }
            if (input.name === 'philhealth' && !validateGovernmentID(input.value, 'philhealth')) {
                fieldError = "Invalid PhilHealth format (11-12 digits)";
            }
            if (input.name === 'pagibig' && !validateGovernmentID(input.value, 'pagibig')) {
                fieldError = "Invalid Pag-IBIG format (12 digits)";
            }
        }

        // Apply error styling
        if (fieldError) {
            input.classList.add('border-danger');
            if (errorMsg) {
                errorMsg.innerText = fieldError;
                errorMsg.style.display = 'block';
            }
            isValid = false;
            if (!firstErrorInput) firstErrorInput = input;
        } else {
            input.classList.remove('border-danger');
            if (errorMsg) errorMsg.style.display = 'none';
        }
    });
    
    // Faculty level validation for Faculty position
    if (currentStep === 2) {
        const position = document.querySelector('select[name="position"]');
        if (position && position.value === 'Faculty') {
            const facultyLevel = document.querySelector('select[name="faculty_level"]');
            if (facultyLevel && !facultyLevel.value) {
                const errorMsg = facultyLevel.parentElement.querySelector('.error-msg');
                facultyLevel.classList.add('border-danger');
                if (errorMsg) {
                    errorMsg.innerText = "Faculty level is required for Faculty position";
                    errorMsg.style.display = 'block';
                }
                isValid = false;
                if (!firstErrorInput) firstErrorInput = facultyLevel;
            }
        }
    }
    
    // Focus on first error field
    if (firstErrorInput) {
        firstErrorInput.focus();
    }

    return isValid;
}

function resetEmpModal() {
    currentStep = 1;
    editingEmployeeId = null;
    document.querySelectorAll('.form-step').forEach((s, i) => s.classList.toggle('active', i === 0));
    document.querySelectorAll('.stepper-item').forEach((s, i) => {
        s.classList.toggle('active', i === 0);
        s.classList.remove('completed');
    });
    document.getElementById('employeeForm').reset();
    document.getElementById('subjectRowsContainer').innerHTML = '';
    document.getElementById('prevBtn').style.display = 'none';
    document.getElementById('nextBtn').style.display = 'inline-block';
    document.getElementById('saveBtn').style.display = 'none';
    document.getElementById('employeeModalTitle').innerText = 'Add New Employee';
    
    // Clear the fullName display field
    const fullNameDisplay = document.getElementById('fullNameDisplay');
    if (fullNameDisplay) {
        fullNameDisplay.value = '';
    }
    
    toggleSubjectStep();
}

// --- Attendance ---
function renderAttendanceTable() {
    const tbody = document.getElementById('attendanceTableBody');
    if (!tbody) return;
    
    // Initialize table with pagination and search
    if (!document.getElementById('attendance-search')) {
        initializeTable('attendance', {
            rowsPerPage: 15,
            showSearch: true,
            showFilters: true,
            filters: [
                {
                    id: 'dateFrom',
                    label: 'From Date',
                    type: 'date'
                },
                {
                    id: 'dateTo',
                    label: 'To Date',
                    type: 'date'
                }
            ]
        });
    }

    // Apply filters and search (name and employee ID only)
    const filteredAttendance = applyTableFilters('attendance', attendanceLogs, ['full_name', 'emp_code']);
    
    // Get paginated data
    const paginatedAttendance = getPaginatedData('attendance', filteredAttendance);
    
    // Update Summary Stats
    const totalLogs = filteredAttendance.length;
    const ontimeCount = filteredAttendance.filter(l => l.status === 'On-Time').length;
    const lateCount = filteredAttendance.filter(l => l.status === 'Late').length;
    const absentCount = filteredAttendance.filter(l => l.status === 'Absent').length;

    if (document.getElementById('att-total-logs')) {
        document.getElementById('att-total-logs').innerText = totalLogs;
        document.getElementById('att-ontime-count').innerText = ontimeCount;
        document.getElementById('att-late-count').innerText = lateCount;
        document.getElementById('att-absent-count').innerText = absentCount;
    }

    tbody.innerHTML = paginatedAttendance.map(log => {
        const status = log.status || '---';
        const statusClass = status.toLowerCase().replace(' ', '-');
        
        // Build schedule display string
        let scheduleHtml = '';
        if (log.schedule && log.schedule.length > 0) {
            scheduleHtml = '<div class="schedule-list">';
            log.schedule.forEach(s => {
                const inTime = s.time_start ? formatTime(s.time_start) : '---';
                const outTime = s.time_end ? formatTime(s.time_end) : '---';
                scheduleHtml += `<div class="schedule-item">
                    <span class="schedule-subj">${escapeHTML(s.subject_code || s.subject_description || '')}</span>
                    <span class="schedule-time">${inTime} - ${outTime}</span>
                    ${s.room ? `<span class="schedule-room">${escapeHTML(s.room)}</span>` : ''}
                </div>`;
            });
            scheduleHtml += '</div>';
            
            // Add on-time indicators
            if (log.check_in) {
                const inClass = log.schedule_ontime_in ? 'ontime' : 'late-schedule';
                const inLabel = log.schedule_ontime_in ? 'On Time (Schedule)' : 'Late (Schedule)';
                scheduleHtml += `<div class="schedule-status ${inClass}"><i class="fas ${log.schedule_ontime_in ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${inLabel} In</div>`;
            }
            if (log.check_out && log.schedule_ontime_out !== null) {
                const outClass = log.schedule_ontime_out ? 'ontime' : 'late-schedule';
                const outLabel = log.schedule_ontime_out ? 'On Time (Schedule)' : 'Early (Schedule)';
                scheduleHtml += `<div class="schedule-status ${outClass}"><i class="fas ${log.schedule_ontime_out ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${outLabel} Out</div>`;
            }
        } else {
            scheduleHtml = '<span class="text-muted">---</span>';
        }

        // Formatted Employee Display - ensuring it's in one column
        const employeeDisplay = `
            <div class="table-emp-info">
                <div class="emp-avatar">${escapeHTML((log.full_name || 'E').charAt(0))}</div>
                <div class="emp-details">
                    <span class="emp-name">${escapeHTML(log.full_name || '---')}</span>
                    <span class="emp-id">${escapeHTML(log.emp_code || '---')}</span>
                </div>
            </div>
        `;

        return `
        <tr>
            <td>${employeeDisplay}</td>
            <td><span class="text-muted"><i class="far fa-calendar-alt"></i> ${escapeHTML(log.log_date)}</span></td>
            <td><strong>${log.check_in ? formatTime(log.check_in) : '---'}</strong></td>
            <td><span class="text-muted">${log.lunch_out ? formatTime(log.lunch_out) : '---'}</span></td>
            <td><span class="text-muted">${log.lunch_in ? formatTime(log.lunch_in) : '---'}</span></td>
            <td><strong>${log.check_out ? formatTime(log.check_out) : '---'}</strong></td>
            <td>
                <div class="status-pill-container">
                    <span class="status-badge status-${statusClass}">${escapeHTML(status)}</span>
                    ${log.late_minutes > 0 ? `<span class="late-tag">${log.late_minutes}m late</span>` : ''}
                </div>
            </td>
            <td class="schedule-cell">${scheduleHtml}</td>
            <td>
                <div class="table-actions">
                    <button class="btn-icon" title="View Details" onclick="viewAttendanceDetails(${log.id})"><i class="fas fa-eye"></i></button>
                    <button class="btn-icon delete" title="Flag/Report" onclick="flagAttendance(${log.id})"><i class="fas fa-flag"></i></button>
                </div>
            </td>
        </tr>
    `;
    }).join('') || '<tr><td colspan="9" class="text-center text-muted">No attendance records found.</td></tr>';
    
    // Render pagination
    renderPagination('attendance', filteredAttendance.length);
}

function viewAttendanceDetails(id) {
    // Implement detail view if needed
    console.log("Viewing attendance details for ID:", id);
}

function flagAttendance(id) {
    // Implement flagging if needed
    console.log("Flagging attendance ID:", id);
}

function exportAttendance() {
    // Implement export functionality
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'mm', 'a4');

    doc.text("Daily Attendance Logs", 14, 15);

    const rows = attendanceLogs.map(l => [
        l.emp_code,
        l.full_name,
        l.log_date,
        l.check_in || '---',
        l.lunch_out || '---',
        l.lunch_in || '---',
        l.check_out || '---',
        l.status
    ]);

    doc.autoTable({
        head: [['ID', 'Name', 'Date', 'Check-In', 'Lunch-Out', 'Lunch-In', 'Check-Out', 'Status']],
        body: rows,
        startY: 20
    });

    doc.save(`attendance_logs_${new Date().toISOString().split('T')[0]}.pdf`);
}

// Helper to format TIME from database (HH:MM:SS) to AM/PM
function formatTime(timeStr) {
    if (!timeStr || timeStr === '---') return '---';
    try {
        const [h, m] = timeStr.split(':');
        let hours = parseInt(h);
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12; // the hour '0' should be '12'
        return `${hours}:${m} ${ampm}`;
    } catch (e) {
        return timeStr;
    }
}

// --- Specialized Payroll ---
async function showRunFacultyPayroll() {
    const { value: formValues } = await Swal.fire({
        title: 'Run Faculty Payroll',
        html:
            '<label>Cut-off Start</label><input id="swal-input1" class="swal2-input" type="date" value="' + new Date().toISOString().split('T')[0] + '">' +
            '<label>Cut-off End</label><input id="swal-input2" class="swal2-input" type="date" value="' + new Date().toISOString().split('T')[0] + '">',
        focusConfirm: false,
        preConfirm: () => {
            return [
                document.getElementById('swal-input1').value,
                document.getElementById('swal-input2').value
            ]
        }
    });

    if (formValues) {
        const [start, end] = formValues;
        const result = await Swal.fire({
            title: 'Confirm Action',
            text: `Run Faculty Payroll for ${start} to ${end}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, run it!'
        });

        if (result.isConfirmed) {
            const response = await fetch('backend/api.php?action=run_specialized_payroll', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'faculty', start_date: start, end_date: end })
            });
            const resultData = await response.json();
            showToast(resultData.message, resultData.success ? 'success' : 'error');
            if (resultData.success) {
                const period = `${new Date(start).toLocaleDateString('en-US')} - ${new Date(end).toLocaleDateString('en-US')}`;
                document.getElementById('faculty-payroll-period').innerText = period;
                document.getElementById('faculty-cutoff-period').innerText = period;
                loadFacultyPayroll(period);
            }
        }
    }
}

async function showRunUtilityPayroll() {
    const { value: formValues } = await Swal.fire({
        title: 'Run Utility Payroll',
        html:
            '<label>Cut-off Start</label><input id="swal-input1" class="swal2-input" type="date" value="' + new Date().toISOString().split('T')[0] + '">' +
            '<label>Cut-off End</label><input id="swal-input2" class="swal2-input" type="date" value="' + new Date().toISOString().split('T')[0] + '">',
        focusConfirm: false,
        preConfirm: () => {
            return [
                document.getElementById('swal-input1').value,
                document.getElementById('swal-input2').value
            ]
        }
    });

    if (formValues) {
        const [start, end] = formValues;
        const result = await Swal.fire({
            title: 'Confirm Action',
            text: `Run Utility Payroll for ${start} to ${end}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, run it!'
        });

        if (result.isConfirmed) {
            const response = await fetch('backend/api.php?action=run_specialized_payroll', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'utility', start_date: start, end_date: end })
            });
            const resultData = await response.json();
            showToast(resultData.message, resultData.success ? 'success' : 'error');
            if (resultData.success) {
                const period = `${new Date(start).toLocaleDateString('en-US')} - ${new Date(end).toLocaleDateString('en-US')}`;
                document.getElementById('utility-payroll-period').innerText = period;
                document.getElementById('utility-cutoff-period').innerText = period;
                loadUtilityPayroll(period);
            }
        }
    }
}

async function loadFacultyPayroll(period = 'latest') {
    const tbody = document.getElementById('facultyPayrollTableBody');
    if (!tbody) return;

    const response = await fetch(`backend/api.php?action=get_faculty_payroll&period=${period}`);
    const result = await response.json();
    const data = result.data || [];
    const actualPeriod = result.period || '---';

    // Update Period Display in UI
    const periodDisplay = document.getElementById('faculty-payroll-period');
    if (periodDisplay) periodDisplay.innerText = actualPeriod;

    tbody.innerHTML = data.map((p, index) => {
        // AUTO-CALCULATION: Faculty Payroll
        const basicSalary = parseFloat(p.basic_salary) || 0;
        const basicPay = parseFloat(p.basic_pay) || 0;
        
        // Earnings
        const earnedForPeriod = basicPay; // From backend calculation
        const loadPay = parseFloat(p.load_pay) || 5000;
        const overTime = parseFloat(p.overtime_pay) || 0;
        const differential = parseFloat(p.differential_pay) || 0;
        const substitution = parseFloat(p.substitution_pay) || 0;
        const adjPlus = parseFloat(p.adj_plus) || 0;
        const honorarium = parseFloat(p.honorarium) || 0;
        
        // Deductions
        const absences = parseFloat(p.absence_deduction) || 0;
        const lateUT = parseFloat(p.late_deduction) || 0;
        const hdmfCont = parseFloat(p.hdmf_contribution) || 100;
        const hdmfLoans = parseFloat(p.hdmf_loans) || 0;
        const hdmfMP2 = parseFloat(p.hdmf_mp2) || 0;
        
        // AUTO-CALCULATED TOTALS
        const totalDeductions = absences + lateUT + hdmfCont + hdmfLoans + hdmfMP2;
        const totalEarnings = earnedForPeriod + loadPay + overTime + differential + substitution + adjPlus + honorarium;
        const netPay = totalEarnings - totalDeductions;
        
        return `
            <tr>
                <td>${index + 1}</td>
                <td><strong>${escapeHTML(p.full_name)}</strong><br><small>${escapeHTML(p.emp_code)}</small></td>
                <td class="currency">₱${basicSalary.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency earned">₱${earnedForPeriod.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency editable" data-field="load_pay" data-id="${p.id}">₱${loadPay.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency editable" data-field="overtime_pay" data-id="${p.id}">₱${overTime.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency editable" data-field="differential_pay" data-id="${p.id}">₱${differential.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency editable" data-field="substitution_pay" data-id="${p.id}">₱${substitution.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency editable" data-field="adj_plus" data-id="${p.id}">₱${adjPlus.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency deduction">(₱${absences.toLocaleString('en-US', {minimumFractionDigits: 2})})</td>
                <td class="currency deduction">(₱${lateUT.toLocaleString('en-US', {minimumFractionDigits: 2})})</td>
                <td class="currency editable" data-field="hdmf_contribution" data-id="${p.id}">₱${hdmfCont.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency editable" data-field="hdmf_loans" data-id="${p.id}">₱${hdmfLoans.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency editable" data-field="hdmf_mp2" data-id="${p.id}">₱${hdmfMP2.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency total-deduction"><strong>(₱${totalDeductions.toLocaleString('en-US', {minimumFractionDigits: 2})})</strong></td>
                <td class="currency editable" data-field="honorarium" data-id="${p.id}">₱${honorarium.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency net-pay"><strong>₱${netPay.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick="viewAndPrintPayslip(${p.employee_id}, '${escapeHTML(actualPeriod)}')" title="View & Print Payslip">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="printIndividualPayslip(${p.employee_id}, '${escapeHTML(actualPeriod)}')" title="Print Payslip">
                        <i class="fas fa-print"></i> Print
                    </button>
                </td>
            </tr>
        `;
    }).join('') || '<tr><td colspan="18" class="text-center">No faculty payroll records for this period.</td></tr>';
    
    // Make cells editable with auto-calculation
    makePayrollCellsEditable('faculty');
}

async function loadUtilityPayroll(period = 'latest') {
    const tbody = document.getElementById('utilityPayrollTableBody');
    if (!tbody) return;

    const response = await fetch(`backend/api.php?action=get_utility_payroll&period=${period}`);
    const result = await response.json();
    const data = result.data || [];
    const actualPeriod = result.period || '---';

    // Update Period Display in UI
    const periodDisplay = document.getElementById('utility-payroll-period');
    if (periodDisplay) periodDisplay.innerText = actualPeriod;

    tbody.innerHTML = data.map((p, index) => {
        // AUTO-CALCULATION: Utility Payroll
        const basicSalary = parseFloat(p.basic_salary) || 0;
        const ratePerDay = basicSalary / 22; // Standard working days per month
        
        // Earnings
        const earnedForPeriod = parseFloat(p.basic_pay) || 0;
        const otHolidayPay = parseFloat(p.ot_holiday_pay) || 0;
        const adjPlus = parseFloat(p.adj_plus) || 0;
        
        // Deductions
        const lateUT = parseFloat(p.late_deduction) || 0;
        const adjMinus = parseFloat(p.adj_minus) || 0;
        const hdmfCont = parseFloat(p.hdmf_contribution) || 100;
        const hdmfLoans = parseFloat(p.hdmf_loans) || 0;
        const cashAdvance = parseFloat(p.cash_advance) || 0;
        
        // AUTO-CALCULATED TOTALS
        const totalDeductions = lateUT + adjMinus + hdmfCont + hdmfLoans + cashAdvance;
        const totalEarnings = earnedForPeriod + otHolidayPay + adjPlus;
        const netPay = totalEarnings - totalDeductions;
        
        // ATM vs Non-ATM split
        const atm = netPay; // Default: all to ATM
        const nonAtm = 0;
        
        return `
            <tr>
                <td>${index + 1}</td>
                <td><strong>${escapeHTML(p.full_name)}</strong><br><small>${escapeHTML(p.emp_code)}</small></td>
                <td class="currency">₱${ratePerDay.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency earned">₱${earnedForPeriod.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency editable" data-field="ot_holiday_pay" data-id="${p.id}">₱${otHolidayPay.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency editable" data-field="adj_plus" data-id="${p.id}">₱${adjPlus.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency deduction">(₱${lateUT.toLocaleString('en-US', {minimumFractionDigits: 2})})</td>
                <td class="currency editable" data-field="adj_minus" data-id="${p.id}">(₱${adjMinus.toLocaleString('en-US', {minimumFractionDigits: 2})})</td>
                <td class="currency editable" data-field="hdmf_contribution" data-id="${p.id}">₱${hdmfCont.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency editable" data-field="hdmf_loans" data-id="${p.id}">₱${hdmfLoans.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency editable" data-field="cash_advance" data-id="${p.id}">₱${cashAdvance.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency total-deduction"><strong>(₱${totalDeductions.toLocaleString('en-US', {minimumFractionDigits: 2})})</strong></td>
                <td class="currency net-pay"><strong>₱${netPay.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></td>
                <td class="currency atm">₱${atm.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="currency non-atm">₱${nonAtm.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td>
                    <button class="btn btn-secondary btn-sm" onclick="viewAndPrintPayslip(${p.employee_id}, '${escapeHTML(actualPeriod)}')" title="View & Print Payslip">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="printIndividualPayslip(${p.employee_id}, '${escapeHTML(actualPeriod)}')" title="Print Payslip">
                        <i class="fas fa-print"></i> Print
                    </button>
                </td>
            </tr>
        `;
    }).join('') || '<tr><td colspan="16" class="text-center">No utility payroll records for this period.</td></tr>';
    
    // Make cells editable with auto-calculation
    makePayrollCellsEditable('utility');
}

// Switch between Faculty and Utility payroll
function switchPayrollType(type) {
    currentPayrollType = type;
    loadSpecializedPayroll(type, 'latest');
}

// Load specialized payroll for the given type
async function loadSpecializedPayroll(type, period = 'latest') {
    currentPayrollType = type;

    const facultySection = document.getElementById('faculty-payroll-section');
    const utilitySection = document.getElementById('utility-payroll-section');
    const periodDisplay = document.getElementById('specialized-payroll-period');

    if (type === 'faculty') {
        if (facultySection) facultySection.style.display = '';
        if (utilitySection) utilitySection.style.display = 'none';
        currentPayrollTableId = 'facultyPayrollTable';
        currentPayrollTitle = 'FACULTY PAYROLL';
        await loadFacultyPayroll(period);
    } else {
        if (facultySection) facultySection.style.display = 'none';
        if (utilitySection) utilitySection.style.display = '';
        currentPayrollTableId = 'utilityPayrollTable';
        currentPayrollTitle = 'UTILITY PAYROLL';
        await loadUtilityPayroll(period);
    }

    const periodEl = document.getElementById(type === 'faculty' ? 'faculty-payroll-period' : 'utility-payroll-period');
    const periodText = periodEl?.innerText || '---';
    if (periodDisplay) periodDisplay.innerText = periodText;
}

// Export the currently visible payroll
function exportCurrentPayroll() {
    if (currentPayrollType === 'faculty') {
        exportFacultyPayroll();
    } else {
        exportUtilityPayroll();
    }
}

// --- Payroll ---

// Make payroll cells editable with auto-calculation
function makePayrollCellsEditable(type) {
    const editableCells = document.querySelectorAll(`#${type}PayrollTableBody .editable`);
    
    editableCells.forEach(cell => {
        cell.addEventListener('dblclick', function() {
            const currentText = this.textContent.replace(/[₱(),]/g, '').trim();
            const currentValue = parseFloat(currentText) || 0;
            const field = this.dataset.field;
            const payrollId = this.dataset.id;
            
            // Create input field
            const input = document.createElement('input');
            input.type = 'number';
            input.step = '0.01';
            input.value = currentValue;
            input.style.cssText = 'width: 100px; padding: 4px; border: 2px solid #3b4fc9; border-radius: 4px; text-align: right; font-size: inherit;';
            
            // Replace cell content with input
            this.textContent = '';
            this.appendChild(input);
            input.focus();
            input.select();
            
            // Handle save on Enter or blur
            const saveValue = () => {
                const newValue = parseFloat(input.value) || 0;
                this.textContent = `₱${newValue.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                this.dataset.value = newValue;
                
                // Update backend
                updatePayrollField(payrollId, field, newValue);
                
                // Recalculate the entire row
                recalculatePayrollRow(this.closest('tr'), type);
            };
            
            input.addEventListener('blur', saveValue);
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    input.blur();
                }
            });
        });
    });
}

// Update payroll field in backend
async function updatePayrollField(payrollId, field, value) {
    try {
        await fetch('backend/api.php?action=update_payroll_field', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                payroll_id: payrollId,
                field: field,
                value: value
            })
        });
    } catch (error) {
        console.error('Failed to update payroll field:', error);
    }
}

// Recalculate payroll row totals
function recalculatePayrollRow(row, type) {
    if (type === 'faculty') {
        recalculateFacultyRow(row);
    } else if (type === 'utility') {
        recalculateUtilityRow(row);
    }
}

// Recalculate Faculty Payroll row
function recalculateFacultyRow(row) {
    const cells = row.querySelectorAll('td.currency');
    
    // Get all values from cells
    const getValue = (className) => {
        const cell = row.querySelector(`.${className}`);
        if (!cell) return 0;
        const text = cell.textContent.replace(/[₱(),]/g, '').trim();
        return parseFloat(text) || 0;
    };
    
    // Earnings
    const earnedForPeriod = getValue('earned');
    const loadPay = getValue('editable[data-field="load_pay"]');
    const overTime = getValue('editable[data-field="overtime_pay"]');
    const differential = getValue('editable[data-field="differential_pay"]');
    const substitution = getValue('editable[data-field="substitution_pay"]');
    const adjPlus = getValue('editable[data-field="adj_plus"]');
    const honorarium = getValue('editable[data-field="honorarium"]');
    
    // Deductions
    const absences = getValue('deduction:nth-of-type(1)');
    const lateUT = getValue('deduction:nth-of-type(2)');
    const hdmfCont = getValue('editable[data-field="hdmf_contribution"]');
    const hdmfLoans = getValue('editable[data-field="hdmf_loans"]');
    const hdmfMP2 = getValue('editable[data-field="hdmf_mp2"]');
    
    // Calculate totals
    const totalDeductions = absences + lateUT + hdmfCont + hdmfLoans + hdmfMP2;
    const totalEarnings = earnedForPeriod + loadPay + overTime + differential + substitution + adjPlus + honorarium;
    const netPay = totalEarnings - totalDeductions;
    
    // Update total deduction cell
    const totalDedCell = row.querySelector('.total-deduction');
    if (totalDedCell) {
        totalDedCell.innerHTML = `<strong>(₱${totalDeductions.toLocaleString('en-US', {minimumFractionDigits: 2})})</strong>`;
    }
    
    // Update net pay cell
    const netPayCell = row.querySelector('.net-pay');
    if (netPayCell) {
        netPayCell.innerHTML = `<strong>₱${netPay.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>`;
    }
}

// Recalculate Utility Payroll row
function recalculateUtilityRow(row) {
    const cells = row.querySelectorAll('td.currency');
    
    // Get all values from cells
    const getValue = (className) => {
        const cell = row.querySelector(`.${className}`);
        if (!cell) return 0;
        const text = cell.textContent.replace(/[₱(),]/g, '').trim();
        return parseFloat(text) || 0;
    };
    
    // Earnings
    const earnedForPeriod = getValue('earned');
    const otHolidayPay = getValue('editable[data-field="ot_holiday_pay"]');
    const adjPlus = getValue('editable[data-field="adj_plus"]');
    
    // Deductions
    const lateUT = getValue('deduction:nth-of-type(1)');
    const adjMinus = getValue('editable[data-field="adj_minus"]');
    const hdmfCont = getValue('editable[data-field="hdmf_contribution"]');
    const hdmfLoans = getValue('editable[data-field="hdmf_loans"]');
    const cashAdvance = getValue('editable[data-field="cash_advance"]');
    
    // Calculate totals
    const totalDeductions = lateUT + adjMinus + hdmfCont + hdmfLoans + cashAdvance;
    const totalEarnings = earnedForPeriod + otHolidayPay + adjPlus;
    const netPay = totalEarnings - totalDeductions;
    
    // Update total deduction cell
    const totalDedCell = row.querySelector('.total-deduction');
    if (totalDedCell) {
        totalDedCell.innerHTML = `<strong>(₱${totalDeductions.toLocaleString('en-US', {minimumFractionDigits: 2})})</strong>`;
    }
    
    // Update net pay cell
    const netPayCell = row.querySelector('.net-pay');
    if (netPayCell) {
        netPayCell.innerHTML = `<strong>₱${netPay.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>`;
    }
    
    // Update ATM column
    const atmCell = row.querySelector('.atm');
    if (atmCell) {
        atmCell.textContent = `₱${netPay.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    }
}
async function runPayroll() {
    const start_date = document.getElementById('payrollStartDate').value;
    const end_date = document.getElementById('payrollEndDate').value;
    const category = document.getElementById('payrollCategorySelect').value;

    if (!start_date || !end_date) {
        return showToast('Please select both a start and end date.', 'error');
    }

    const runBtn = document.querySelector('button[onclick="runPayroll()"]');
    const categoryText = category === 'all' ? 'all employees' : `${category} staff`;

    const confirmResult = await Swal.fire({
        title: 'Confirm Action',
        text: `Run payroll for ${categoryText} from ${start_date} to ${end_date}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e0178',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, run it!'
    });

    if (confirmResult.isConfirmed) {
        if (runBtn) {
            runBtn.disabled = true;
            runBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        }

        try {
            const response = await fetch('backend/api.php?action=run_payroll', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ start_date, end_date, category })
            });

            const result = await response.json();
            if (result.success) {
                showToast(result.message || `Payroll processed for ${start_date} to ${end_date}`, 'success');
                closeModal('runPayrollModal');
                await fetchData();
            } else {
                showToast("Error: " + (result.message || "Failed to process payroll."), 'error');
            }
        } catch (err) {
            console.error("Payroll error:", err);
            showToast("Failed to connect to the server.", 'error');
        } finally {
            if (runBtn) {
                runBtn.disabled = false;
                runBtn.innerHTML = '<i class="fas fa-play"></i> Run Payroll';
            }
        }
    }
}

function renderPayrollTable() {
    const tbody = document.getElementById('payrollTableBody');
    if (!tbody) return;

    fetch('backend/api.php?action=get_payroll_batches')
        .then(res => res.json())
        .then(batchList => {
            // Update stats (elements may not exist on all pages)
            if (batchList.length > 0) {
                const elBatches = document.getElementById('stat-total-batches');
                if (elBatches) elBatches.innerText = batchList.length;
                const totalDisbursed = batchList.reduce((sum, b) => sum + parseFloat(b.total_disbursed), 0);
                const elDisbursed = document.getElementById('stat-total-disbursed');
                if (elDisbursed) elDisbursed.innerText = `₱${totalDisbursed.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
                const elLastRun = document.getElementById('stat-last-run');
                if (elLastRun) elLastRun.innerText = batchList[0].period;
                const elStaffCount = document.getElementById('stat-last-staff-count');
                if (elStaffCount) elStaffCount.innerText = batchList[0].staff_count;
            }

            tbody.innerHTML = batchList.map((b, index) => `
                <tr>
                    <td><strong>BATCH-${101 + index}</strong></td>
                    <td>${escapeHTML(b.period)}</td>
                    <td>₱${parseFloat(b.total_disbursed).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
                    <td>${escapeHTML(new Date(b.processing_date).toLocaleDateString())}</td>
                    <td>Admin</td>
                    <td><span class="status-badge status-active">Completed</span></td>
                    <td>
                        <button class="btn btn-secondary btn-sm" style="padding:0;font-size:5px;line-height:1;min-height:auto;border-width:0" onclick="viewBatch('${escapeHTML(b.period)}')"><i class="fas fa-eye" style="font-size:8px"></i> View</button>
                        <button class="btn btn-danger btn-sm" style="padding:0;font-size:5px;line-height:1;min-height:auto;border-width:0" onclick="printBatchPayslips('${escapeHTML(b.period)}')"><i class="fas fa-print" style="font-size:8px"></i> Print All</button>
                    </td>
                </tr>
            `).join('');
        });
}

function showPayrollModal() {
    openModal('runPayrollModal');
    // Set default dates to current month
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];

    document.getElementById('payrollStartDate').value = firstDay;
    document.getElementById('payrollEndDate').value = lastDay;
    document.getElementById('payrollCategorySelect').value = 'all';
}

async function runPayrollDirect(start_date, end_date) {
    const response = await fetch('backend/api.php?action=run_payroll', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ start_date, end_date })
    });
    const result = await response.json();
    showToast(result.message, result.success ? 'success' : 'error');
    fetchData();
}

function viewBatch(period) {
    const records = payrollHistory.filter(p => p.period === period);
    if (records.length === 0) return showToast("No records found for this batch.", 'error');

    let report = `<div style="text-align: left; font-family: monospace;">
        <p><strong>Payroll Details for ${period}</strong></p>
        <hr>
        <div style="display: flex; justify-content: space-between;">
            <span><strong>Name</strong></span>
            <span><strong>Net Pay</strong></span>
        </div>
        <hr>`;
    
    records.forEach(r => {
        report += `<div style="display: flex; justify-content: space-between;">
            <span>${escapeHTML(r.full_name)}</span>
            <span>₱${parseFloat(r.net_pay).toLocaleString()}</span>
        </div>`;
    });
    report += `</div>`;

    Swal.fire({
        title: 'Batch Report',
        html: report,
        confirmButtonColor: '#1e0178'
    });
}

// View and Print Individual Payslip (for Faculty/Utility pages)
async function viewAndPrintPayslip(employeeId, period) {
    try {
        const response = await fetch(`backend/api.php?action=get_payslip&employee_id=${employeeId}&period=${encodeURIComponent(period)}`);
        const p = await response.json();
        
        if (!p || p.error) {
            return showToast("Failed to fetch payslip data.", 'error');
        }

        // Parse breakdown data
        let breakdown = {};
        try {
            breakdown = p.breakdown ? (typeof p.breakdown === 'string' ? JSON.parse(p.breakdown) : p.breakdown) : {};
        } catch (e) {
            console.error('Error parsing breakdown:', e);
        }

        // Build payslip HTML for modal
        const basicPay = parseFloat(p.basic_pay) || 0;
        const netPay = parseFloat(p.net_pay) || 0;
        
        let earningsHTML = '';
        let deductionsHTML = '';
        
        // Earnings
        earningsHTML += `<div class="payslip-item"><span style="color: #2c3e50; font-weight: 500;">Basic Pay</span><span style="color: #27ae60; font-weight: 600;">₱${basicPay.toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        
        if (breakdown.total_allowances && parseFloat(breakdown.total_allowances) > 0) {
            earningsHTML += `<div class="payslip-item"><span style="color: #2c3e50; font-weight: 500;">Total Allowances</span><span style="color: #27ae60; font-weight: 600;">₱${parseFloat(breakdown.total_allowances).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        if (breakdown.load_pay && parseFloat(breakdown.load_pay) > 0) {
            earningsHTML += `<div class="payslip-item"><span style="color: #2c3e50; font-weight: 500;">Load Pay</span><span style="color: #27ae60; font-weight: 600;">₱${parseFloat(breakdown.load_pay).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        if (breakdown.overtime && parseFloat(breakdown.overtime) > 0) {
            earningsHTML += `<div class="payslip-item"><span style="color: #2c3e50; font-weight: 500;">Overtime</span><span style="color: #27ae60; font-weight: 600;">₱${parseFloat(breakdown.overtime).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        if (breakdown.differential && parseFloat(breakdown.differential) > 0) {
            earningsHTML += `<div class="payslip-item"><span style="color: #2c3e50; font-weight: 500;">Differential</span><span style="color: #27ae60; font-weight: 600;">₱${parseFloat(breakdown.differential).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        if (breakdown.substitution && parseFloat(breakdown.substitution) > 0) {
            earningsHTML += `<div class="payslip-item"><span style="color: #2c3e50; font-weight: 500;">Substitution</span><span style="color: #27ae60; font-weight: 600;">₱${parseFloat(breakdown.substitution).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        if (breakdown.adj_plus && parseFloat(breakdown.adj_plus) > 0) {
            earningsHTML += `<div class="payslip-item"><span style="color: #2c3e50; font-weight: 500;">Adjustments (+)</span><span style="color: #27ae60; font-weight: 600;">₱${parseFloat(breakdown.adj_plus).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        if (breakdown.ot_holiday && parseFloat(breakdown.ot_holiday) > 0) {
            earningsHTML += `<div class="payslip-item"><span style="color: #2c3e50; font-weight: 500;">OT/Holiday Pay</span><span style="color: #27ae60; font-weight: 600;">₱${parseFloat(breakdown.ot_holiday).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        if (breakdown.honorarium && parseFloat(breakdown.honorarium) > 0) {
            earningsHTML += `<div class="payslip-item"><span style="color: #2c3e50; font-weight: 500;">Honorarium</span><span style="color: #27ae60; font-weight: 600;">₱${parseFloat(breakdown.honorarium).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        
        // Deductions
        if (breakdown.absences && parseFloat(breakdown.absences) > 0) {
            deductionsHTML += `<div class="payslip-item deduction"><span>Absences</span><span style="color: #c0392b; font-weight: 600;">-₱${parseFloat(breakdown.absences).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        if (breakdown.late_ut && parseFloat(breakdown.late_ut) > 0) {
            deductionsHTML += `<div class="payslip-item deduction"><span>Late/Undertime</span><span style="color: #c0392b; font-weight: 600;">-₱${parseFloat(breakdown.late_ut).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        if (breakdown.hdmf_cont && parseFloat(breakdown.hdmf_cont) > 0) {
            deductionsHTML += `<div class="payslip-item deduction"><span>HDMF Contribution</span><span style="color: #c0392b; font-weight: 600;">-₱${parseFloat(breakdown.hdmf_cont).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        if (breakdown.hdmf_loans && parseFloat(breakdown.hdmf_loans) > 0) {
            deductionsHTML += `<div class="payslip-item deduction"><span>HDMF Cash Advance</span><span style="color: #c0392b; font-weight: 600;">-₱${parseFloat(breakdown.hdmf_loans).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        if (breakdown.hdmf_mp2 && parseFloat(breakdown.hdmf_mp2) > 0) {
            deductionsHTML += `<div class="payslip-item deduction"><span>HDMF MP2</span><span style="color: #c0392b; font-weight: 600;">-₱${parseFloat(breakdown.hdmf_mp2).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        if (breakdown.cash_advance && parseFloat(breakdown.cash_advance) > 0) {
            deductionsHTML += `<div class="payslip-item deduction"><span>Cash Advance</span><span style="color: #c0392b; font-weight: 600;">-₱${parseFloat(breakdown.cash_advance).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        
        // Show individual employee-specific deductions with details
        if (breakdown.employee_deductions_details && breakdown.employee_deductions_details.length > 0) {
            breakdown.employee_deductions_details.forEach(function(deduction) {
                const amount = parseFloat(deduction.amount);
                if (amount > 0) {
                    deductionsHTML += `<div class="payslip-item deduction"><span>${escapeHTML(deduction.name)}</span><span style="color: #c0392b; font-weight: 600;">-₱${amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
                }
            });
        } else if (breakdown.employee_deductions && parseFloat(breakdown.employee_deductions) > 0) {
            // Fallback to total if details not available
            deductionsHTML += `<div class="payslip-item deduction"><span>Employee Deductions</span><span style="color: #c0392b; font-weight: 600;">-₱${parseFloat(breakdown.employee_deductions).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }
        
        if (breakdown.adj_minus && parseFloat(breakdown.adj_minus) > 0) {
            deductionsHTML += `<div class="payslip-item deduction"><span>Adjustments (-)</span><span style="color: #c0392b; font-weight: 600;">-₱${parseFloat(breakdown.adj_minus).toLocaleString('en-US', {minimumFractionDigits: 2})}</span></div>`;
        }

        const payslipHTML = `
            <div style="text-align: left; max-width: 700px; margin: 0 auto;">
                <div style="text-align: center; border-bottom: 3px solid #1e0178; padding-bottom: 15px; margin-bottom: 20px;">
                    <h2 style="color: #1e0178; margin: 0;">OFFICIAL PAYSLIP</h2>
                    <p style="margin: 5px 0; color: #666;">${escapeHTML(p.company_name || 'Company Name')}</p>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px;">
                    <div style="color: #2c3e50;"><strong style="color: #1e0178;">Name:</strong> ${escapeHTML(p.full_name)}</div>
                    <div style="color: #2c3e50;"><strong style="color: #1e0178;">Employee ID:</strong> ${escapeHTML(p.emp_code)}</div>
                    <div style="color: #2c3e50;"><strong style="color: #1e0178;">Position:</strong> ${escapeHTML(p.position)}</div>
                    <div style="color: #2c3e50;"><strong style="color: #1e0178;">Period:</strong> ${escapeHTML(p.period)}</div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <h4 style="color: #1e0178; border-bottom: 2px solid #1e0178; padding-bottom: 5px;">EARNINGS</h4>
                        ${earningsHTML}
                    </div>
                    <div>
                        <h4 style="color: #c0392b; border-bottom: 2px solid #c0392b; padding-bottom: 5px;">DEDUCTIONS</h4>
                        ${deductionsHTML}
                    </div>
                </div>
                
                <div style="background: linear-gradient(135deg, #1e0178, #667eea); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px;">
                    <div style="font-size: 14px; margin-bottom: 5px;">NET PAY</div>
                    <div style="font-size: 28px; font-weight: bold;">₱${netPay.toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                </div>
                
                <div style="text-align: center; margin-top: 20px; color: #999; font-size: 11px; font-style: italic;">
                    This is a computer-generated payslip and does not require a signature.
                </div>
            </div>
        `;

        await Swal.fire({
            title: 'Employee Payslip',
            html: payslipHTML,
            width: '800px',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-print"></i> Print PDF',
            cancelButtonText: 'Close',
            confirmButtonColor: '#1e0178',
            customClass: {
                popup: 'glass-modal'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Print as PDF
                printIndividualPayslip(employeeId, period);
            }
        });
        
    } catch (err) {
        console.error('Error viewing payslip:', err);
        showToast("Failed to load payslip.", 'error');
    }
}

// Print Individual Payslip as PDF
async function printIndividualPayslip(employeeId, period) {
    try {
        const response = await fetch(`backend/api.php?action=get_payslip&employee_id=${employeeId}&period=${encodeURIComponent(period)}`);
        const p = await response.json();
        
        if (!p || p.error) {
            return showToast("Failed to fetch payslip data.", 'error');
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Header
        doc.setFillColor(30, 1, 120);
        doc.rect(0, 0, 210, 40, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(22);
        doc.text('OFFICIAL PAYSLIP', 105, 20, { align: 'center' });
        doc.setFontSize(10);
        const companyName = p.company_name || 'Company';
        doc.text(companyName, 105, 30, { align: 'center' });

        // Employee Info
        doc.setTextColor(0);
        doc.setFontSize(12);
        doc.text('EMPLOYEE DETAILS', 20, 55);
        doc.line(20, 57, 190, 57);
        
        doc.setFontSize(10);
        const fullName = p.full_name || 'N/A';
        const empCode = p.emp_code || 'N/A';
        const position = p.position || 'N/A';
        const periodText = p.period || 'N/A';
        const createdAt = p.created_at ? new Date(p.created_at).toLocaleDateString() : 'N/A';
        
        doc.text(`Name: ${fullName}`, 20, 65);
        doc.text(`ID: ${empCode}`, 20, 72);
        doc.text(`Position: ${position}`, 20, 79);
        doc.text(`Period: ${periodText}`, 130, 65);
        doc.text(`Date: ${createdAt}`, 130, 72);

        // Parse breakdown data
        let breakdown = {};
        try {
            breakdown = p.breakdown ? (typeof p.breakdown === 'string' ? JSON.parse(p.breakdown) : p.breakdown) : {};
        } catch (e) {
            console.error('Error parsing breakdown:', e);
        }

        // Financials
        const basicPay = parseFloat(p.basic_pay) || 0;
        const netPay = parseFloat(p.net_pay) || 0;
        
        // Build earnings and deductions arrays
        const earnings = [];
        const deductionsList = [];
        
        // Add basic pay
        earnings.push(['Basic Pay', `PHP ${basicPay.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        
        // Add allowances if present
        if (breakdown.total_allowances && parseFloat(breakdown.total_allowances) > 0) {
            earnings.push(['Total Allowances', `PHP ${parseFloat(breakdown.total_allowances).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        
        // Add faculty-specific earnings
        if (breakdown.load_pay && parseFloat(breakdown.load_pay) > 0) {
            earnings.push(['Load Pay', `PHP ${parseFloat(breakdown.load_pay).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        if (breakdown.overtime && parseFloat(breakdown.overtime) > 0) {
            earnings.push(['Overtime', `PHP ${parseFloat(breakdown.overtime).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        if (breakdown.differential && parseFloat(breakdown.differential) > 0) {
            earnings.push(['Differential', `PHP ${parseFloat(breakdown.differential).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        if (breakdown.substitution && parseFloat(breakdown.substitution) > 0) {
            earnings.push(['Substitution', `PHP ${parseFloat(breakdown.substitution).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        if (breakdown.adj_plus && parseFloat(breakdown.adj_plus) > 0) {
            earnings.push(['Adjustments (+)', `PHP ${parseFloat(breakdown.adj_plus).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        if (breakdown.ot_holiday && parseFloat(breakdown.ot_holiday) > 0) {
            earnings.push(['OT/Holiday Pay', `PHP ${parseFloat(breakdown.ot_holiday).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        if (breakdown.honorarium && parseFloat(breakdown.honorarium) > 0) {
            earnings.push(['Honorarium', `PHP ${parseFloat(breakdown.honorarium).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        
        // Add deductions
        if (breakdown.absences && parseFloat(breakdown.absences) > 0) {
            deductionsList.push(['Absences', `- PHP ${parseFloat(breakdown.absences).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        if (breakdown.late_ut && parseFloat(breakdown.late_ut) > 0) {
            deductionsList.push(['Late/Undertime', `- PHP ${parseFloat(breakdown.late_ut).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        if (breakdown.hdmf_cont && parseFloat(breakdown.hdmf_cont) > 0) {
            deductionsList.push(['HDMF (Pag-IBIG) Contribution', `- PHP ${parseFloat(breakdown.hdmf_cont).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        if (breakdown.hdmf_loans && parseFloat(breakdown.hdmf_loans) > 0) {
            deductionsList.push(['HDMF (Pag-IBIG) Cash Advance', `- PHP ${parseFloat(breakdown.hdmf_loans).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        if (breakdown.hdmf_mp2 && parseFloat(breakdown.hdmf_mp2) > 0) {
            deductionsList.push(['HDMF MP2', `- PHP ${parseFloat(breakdown.hdmf_mp2).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        if (breakdown.cash_advance && parseFloat(breakdown.cash_advance) > 0) {
            deductionsList.push(['Cash Advance', `- PHP ${parseFloat(breakdown.cash_advance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        
        // Show individual employee-specific deductions with details
        if (breakdown.employee_deductions_details && breakdown.employee_deductions_details.length > 0) {
            breakdown.employee_deductions_details.forEach(function(deduction) {
                const amount = parseFloat(deduction.amount);
                if (amount > 0) {
                    deductionsList.push([deduction.name, `- PHP ${amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
                }
            });
        } else if (breakdown.employee_deductions && parseFloat(breakdown.employee_deductions) > 0) {
            // Fallback to total if details not available
            deductionsList.push(['Employee-Specific Deductions', `- PHP ${parseFloat(breakdown.employee_deductions).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        
        if (breakdown.adj_minus && parseFloat(breakdown.adj_minus) > 0) {
            deductionsList.push(['Adjustments (-)', `- PHP ${parseFloat(breakdown.adj_minus).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        
        // Add total deductions if not already itemized
        if (deductionsList.length === 0) {
            const deductions = parseFloat(p.deductions) || 0;
            deductionsList.push(['Total Deductions', `- PHP ${deductions.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
        }
        
        // Calculate totals for display
        const totalEarnings = earnings.reduce((sum, item) => {
            const amount = parseFloat(item[1].replace('PHP ', '').replace(/,/g, ''));
            return sum + (isNaN(amount) ? 0 : amount);
        }, 0);
        
        const totalDeductions = deductionsList.reduce((sum, item) => {
            const amount = parseFloat(item[1].replace('- PHP ', '').replace(/,/g, ''));
            return sum + (isNaN(amount) ? 0 : amount);
        }, 0);
        
        // Create table with earnings and deductions side by side
        const maxRows = Math.max(earnings.length, deductionsList.length);
        const tableBody = [];
        
        for (let i = 0; i < maxRows; i++) {
            const earning = earnings[i] || ['', ''];
            const deduction = deductionsList[i] || ['', ''];
            tableBody.push([earning[0], earning[1], deduction[0], deduction[1]]);
        }
        
        // Add totals row
        tableBody.push([
            'TOTAL EARNINGS', 
            `PHP ${totalEarnings.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`,
            'TOTAL DEDUCTIONS', 
            `- PHP ${totalDeductions.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`
        ]);
        
        doc.autoTable({
            startY: 90,
            head: [['Earnings', 'Amount', 'Deductions', 'Amount']],
            body: tableBody,
            theme: 'striped',
            headStyles: { fillColor: [30, 1, 120] },
            styles: { fontSize: 9 },
            columnStyles: {
                0: { fontStyle: 'bold' },
                2: { fontStyle: 'bold' }
            }
        });

        const netY = doc.lastAutoTable.finalY + 20;
        doc.setFillColor(232, 232, 232);
        doc.rect(20, netY - 10, 170, 20, 'F');
        doc.setFontSize(16);
        doc.setFont(undefined, 'bold');
        doc.text('NET PAY:', 30, netY + 3);
        doc.text(`PHP ${netPay.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`, 180, netY + 3, { align: 'right' });

        const safePeriod = periodText.replace(/[^a-zA-Z0-9]/g, '_');
        doc.save(`Payslip_${empCode}_${safePeriod}.pdf`);
        showToast('Payslip PDF generated!', 'success');
        
    } catch (err) {
        console.error('Error printing payslip:', err);
        showToast("Failed to generate payslip PDF.", 'error');
    }
}

// Print All Payslips for a Batch
async function printBatchPayslips(period) {
    try {
        const response = await fetch(`backend/api.php?action=get_payroll_by_period&period=${encodeURIComponent(period)}`);
        const result = await response.json();
        
        if (!result.success || !result.data || result.data.length === 0) {
            return showToast("No payroll records found for this period.", 'error');
        }

        const employees = result.data;
        
        const { value: confirmPrint } = await Swal.fire({
            title: 'Print All Payslips',
            html: `<p>Generate PDF payslips for <strong>${employees.length} employees</strong> for period <strong>${escapeHTML(period)}</strong>?</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-print"></i> Yes, Print All',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#1e0178'
        });

        if (!confirmPrint) return;

        showToast(`Generating ${employees.length} payslips...`, 'info');

        const periodText = period.replace(/[^a-zA-Z0-9]/g, '_');
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        for (let idx = 0; idx < employees.length; idx++) {
            const emp = employees[idx];
            if (idx > 0) doc.addPage();

            const pRes = await fetch(`backend/api.php?action=get_payslip&employee_id=${emp.employee_id}&period=${encodeURIComponent(period)}`);
            const p = await pRes.json();
            if (!p || p.error) continue;

            let breakdown = {};
            try {
                breakdown = p.breakdown ? (typeof p.breakdown === 'string' ? JSON.parse(p.breakdown) : p.breakdown) : {};
            } catch (e) {}

            const basicPay = parseFloat(p.basic_pay) || 0;
            const netPay = parseFloat(p.net_pay) || 0;
            const fullName = p.full_name || 'N/A';
            const empCode = p.emp_code || 'N/A';
            const position = p.position || 'N/A';

            // Header
            doc.setFillColor(30, 1, 120);
            doc.rect(0, 0, 210, 40, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(22);
            doc.text('OFFICIAL PAYSLIP', 105, 20, { align: 'center' });
            doc.setFontSize(10);
            const companyName = p.company_name || 'Company';
            doc.text(companyName, 105, 30, { align: 'center' });

            // Employee Info
            doc.setTextColor(0);
            doc.setFontSize(12);
            doc.text('EMPLOYEE DETAILS', 20, 55);
            doc.line(20, 57, 190, 57);
            doc.setFontSize(10);
            doc.text(`Name: ${fullName}`, 20, 65);
            doc.text(`ID: ${empCode}`, 20, 72);
            doc.text(`Position: ${position}`, 20, 79);
            doc.text(`Period: ${p.period || period}`, 130, 65);

            const earnings = [];
            const deductionsList = [];

            earnings.push(['Basic Pay', `PHP ${basicPay.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);

            if (breakdown.total_allowances && parseFloat(breakdown.total_allowances) > 0) {
                earnings.push(['Total Allowances', `PHP ${parseFloat(breakdown.total_allowances).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.load_pay && parseFloat(breakdown.load_pay) > 0) {
                earnings.push(['Load Pay', `PHP ${parseFloat(breakdown.load_pay).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.overtime && parseFloat(breakdown.overtime) > 0) {
                earnings.push(['Overtime', `PHP ${parseFloat(breakdown.overtime).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.differential && parseFloat(breakdown.differential) > 0) {
                earnings.push(['Differential', `PHP ${parseFloat(breakdown.differential).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.substitution && parseFloat(breakdown.substitution) > 0) {
                earnings.push(['Substitution', `PHP ${parseFloat(breakdown.substitution).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.adj_plus && parseFloat(breakdown.adj_plus) > 0) {
                earnings.push(['Adjustments (+)', `PHP ${parseFloat(breakdown.adj_plus).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.ot_holiday && parseFloat(breakdown.ot_holiday) > 0) {
                earnings.push(['OT/Holiday Pay', `PHP ${parseFloat(breakdown.ot_holiday).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.honorarium && parseFloat(breakdown.honorarium) > 0) {
                earnings.push(['Honorarium', `PHP ${parseFloat(breakdown.honorarium).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }

            if (breakdown.absences && parseFloat(breakdown.absences) > 0) {
                deductionsList.push(['Absences', `- PHP ${parseFloat(breakdown.absences).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.late_ut && parseFloat(breakdown.late_ut) > 0) {
                deductionsList.push(['Late/Undertime', `- PHP ${parseFloat(breakdown.late_ut).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.hdmf_cont && parseFloat(breakdown.hdmf_cont) > 0) {
                deductionsList.push(['HDMF (Pag-IBIG) Contribution', `- PHP ${parseFloat(breakdown.hdmf_cont).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.hdmf_loans && parseFloat(breakdown.hdmf_loans) > 0) {
                deductionsList.push(['HDMF (Pag-IBIG) Cash Advance', `- PHP ${parseFloat(breakdown.hdmf_loans).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.hdmf_mp2 && parseFloat(breakdown.hdmf_mp2) > 0) {
                deductionsList.push(['HDMF MP2', `- PHP ${parseFloat(breakdown.hdmf_mp2).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }
            if (breakdown.cash_advance && parseFloat(breakdown.cash_advance) > 0) {
                deductionsList.push(['Cash Advance', `- PHP ${parseFloat(breakdown.cash_advance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }

            if (breakdown.employee_deductions_details && breakdown.employee_deductions_details.length > 0) {
                breakdown.employee_deductions_details.forEach(function(deduction) {
                    const amount = parseFloat(deduction.amount);
                    if (amount > 0) {
                        deductionsList.push([deduction.name, `- PHP ${amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
                    }
                });
            } else if (breakdown.employee_deductions && parseFloat(breakdown.employee_deductions) > 0) {
                deductionsList.push(['Employee-Specific Deductions', `- PHP ${parseFloat(breakdown.employee_deductions).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }

            if (breakdown.adj_minus && parseFloat(breakdown.adj_minus) > 0) {
                deductionsList.push(['Adjustments (-)', `- PHP ${parseFloat(breakdown.adj_minus).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }

            if (deductionsList.length === 0) {
                const deductions = parseFloat(p.deductions) || 0;
                deductionsList.push(['Total Deductions', `- PHP ${deductions.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`]);
            }

            const totalEarnings = earnings.reduce((sum, item) => {
                const amount = parseFloat(item[1].replace('PHP ', '').replace(/,/g, ''));
                return sum + (isNaN(amount) ? 0 : amount);
            }, 0);

            const totalDeductions = deductionsList.reduce((sum, item) => {
                const amount = parseFloat(item[1].replace('- PHP ', '').replace(/,/g, ''));
                return sum + (isNaN(amount) ? 0 : amount);
            }, 0);

            const maxRows = Math.max(earnings.length, deductionsList.length);
            const tableBody = [];
            for (let i = 0; i < maxRows; i++) {
                const earning = earnings[i] || ['', ''];
                const deduction = deductionsList[i] || ['', ''];
                tableBody.push([earning[0], earning[1], deduction[0], deduction[1]]);
            }
            tableBody.push([
                'TOTAL EARNINGS',
                `PHP ${totalEarnings.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`,
                'TOTAL DEDUCTIONS',
                `- PHP ${totalDeductions.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`
            ]);

            doc.autoTable({
                startY: 90,
                head: [['Earnings', 'Amount', 'Deductions', 'Amount']],
                body: tableBody,
                theme: 'striped',
                headStyles: { fillColor: [30, 1, 120] },
                styles: { fontSize: 9 },
                columnStyles: {
                    0: { fontStyle: 'bold' },
                    2: { fontStyle: 'bold' }
                }
            });

            const netY = doc.lastAutoTable.finalY + 20;
            doc.setFillColor(232, 232, 232);
            doc.rect(20, netY - 10, 170, 20, 'F');
            doc.setFontSize(16);
            doc.setFont(undefined, 'bold');
            doc.text('NET PAY:', 30, netY + 3);
            doc.text(`PHP ${netPay.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`, 180, netY + 3, { align: 'right' });
            doc.setFont(undefined, 'normal');
        }

        doc.save(`Batch_Payslips_${periodText}.pdf`);
        showToast(`Successfully generated ${employees.length} payslips!`, 'success');

    } catch (err) {
        console.error('Error printing batch payslips:', err);
        showToast("Failed to generate batch payslips.", 'error');
    }
}

// --- Leave ---
function renderLeaveTable() {
    const leaveBalanceSelect = document.getElementById('leaveBalanceEmployeeSelect');
    if (leaveBalanceSelect) {
        leaveBalanceSelect.innerHTML = '<option value="">Select Employee...</option>' +
            employees.map(emp => `<option value="${emp.id}">${escapeHTML(emp.full_name)} (${escapeHTML(emp.employee_id)})</option>`).join('');
    }

    const tbody = document.getElementById('leaveTableBody');
    if (!tbody) return;
    tbody.innerHTML = leaveRequests.map(req => `
        <tr>
            <td>${escapeHTML(req.full_name)}</td>
            <td>${escapeHTML(req.type)}</td>
            <td>${escapeHTML(req.duration || '-')}</td>
            <td>${escapeHTML(req.reason)}</td>
            <td><span class="status-badge status-${req.status.toLowerCase()}">${escapeHTML(req.status)}</span></td>
            <td>
                ${req.status === 'Pending' ? `
                    <button class="btn btn-success btn-sm" onclick="updateLeaveStatus(${req.id}, 'Approved')"><i class="fas fa-check"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="updateLeaveStatus(${req.id}, 'Rejected')"><i class="fas fa-times"></i></button>
                ` : '<span class="text-muted">Processed</span>'}
            </td>
        </tr>
    `).join('') || '<tr><td colspan="6" class="text-center">No leave requests found.</td></tr>';
}

async function applyLeaveBalanceToAll() {
    const balanceInput = document.getElementById('newLeaveBalance');
    const balance = balanceInput?.value;
    if (balance === undefined || balance === null || balance === '') return showToast("Please enter a leave balance first.", 'error');
    
    const result = await Swal.fire({
        title: 'Confirm Action',
        text: "Apply this leave balance to ALL active employees?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, apply it!'
    });

    if (result.isConfirmed) {
        const response = await fetch('backend/api.php?action=bulk_update_leave_balance', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ balance })
        });
        const resultData = await response.json();
        showToast(resultData.message || (resultData.success ? "Leave balance applied to all employees." : "Failed to apply leave balance."), resultData.success ? 'success' : 'error');
        if (resultData.success) {
            if (balanceInput) balanceInput.value = '';
            fetchData();
        }
    }
}

async function updateLeaveBalance() {
    const employeeId = document.getElementById('leaveBalanceEmployeeSelect').value;
    const balanceInput = document.getElementById('newLeaveBalance');
    const balance = balanceInput.value;

    if (!employeeId || !balance) return showToast("Please select an employee and enter a balance.", 'error');

    const response = await fetch(`backend/api.php?action=update_leave_balance&employee_id=${employeeId}&balance=${balance}`);
    const result = await response.json();

    if (result.success) {
        showToast("Leave balance updated successfully.", 'success');
        if (balanceInput) balanceInput.value = '';
        fetchData();
    } else {
        showToast("Error: " + (result.message || "Failed to update balance."), 'error');
    }
}

async function updateLeaveStatus(id, status) {
    const response = await fetch('backend/api.php?action=update_leave_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
    });
    const result = await response.json();
    if (result.success) {
        showToast(`Leave request ${status.toLowerCase()} successfully.`, 'success');
        fetchData();
    } else {
        showToast(result.message || "Failed to update status.", 'error');
    }
}

function renderLoanTable() {
    const tbody = document.getElementById('loanTableBody');
    if (!tbody) return;
    tbody.innerHTML = loanRequests.map(req => {
        let actionButtons = '';
        const role = USER_ROLE.toLowerCase();

        if (req.status === 'Pending') {
            // HR and Admin can Approve/Reject
            if (role === 'admin' || role === 'hr') {
                actionButtons = `
                    <button class="btn btn-success btn-sm" onclick="updateLoanStatus(${req.id}, 'Approved')" title="Approve"><i class="fas fa-check"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="updateLoanStatus(${req.id}, 'Rejected')" title="Reject"><i class="fas fa-times"></i></button>
                `;
            } else {
                actionButtons = '<span class="text-muted">Awaiting HR Approval</span>';
            }
        } else if (req.status === 'Approved') {
            // Payroll can Distribute
            if (role === 'payroll' || role === 'payroll officer' || role === 'admin') {
                actionButtons = `
                    <button class="btn btn-primary btn-sm" onclick="updateLoanStatus(${req.id}, 'Distributed')" title="Mark as Distributed"><i class="fas fa-hand-holding-usd"></i> Distribute</button>
                `;
            } else {
                actionButtons = '<span class="text-info">Awaiting Distribution</span>';
            }
        } else if (req.status === 'Distributed') {
            // Payroll can mark as Paid
            if (role === 'payroll' || role === 'payroll officer' || role === 'admin') {
                actionButtons = `
                    <button class="btn btn-success btn-sm" onclick="updateLoanStatus(${req.id}, 'Paid')" title="Mark as Paid"><i class="fas fa-money-bill-wave"></i> Mark Paid</button>
                `;
            } else {
                actionButtons = '<span class="text-primary">Distributed</span>';
            }
        } else {
            actionButtons = `<span class="text-muted">${req.status}</span>`;
        }

        return `
            <tr>
                <td>${escapeHTML(req.full_name)}</td>
                <td>₱${parseFloat(req.amount).toLocaleString()}</td>
                <td>${escapeHTML(req.reason)}</td>
                <td><span class="status-badge status-${req.status.toLowerCase()}">${escapeHTML(req.status)}</span></td>
                <td>${actionButtons}</td>
            </tr>
        `;
    }).join('');
}

async function updateLoanStatus(id, status) {
    const response = await fetch('backend/api.php?action=update_loan_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
    });
    const result = await response.json();
    if (result.success) {
        showToast(`Cash advance status updated to ${status}.`, 'success');
        fetchData();
    } else {
        showToast(result.message || "Failed to update status.", 'error');
    }
}

function renderResignationTable() {
    const tbody = document.getElementById('resignationTableBody');
    if (!tbody) return;
    tbody.innerHTML = resignationRequests.map(req => {
        let actionButtons = '';
        
        if (req.status === 'Pending') {
            actionButtons = `
                <button class="btn btn-success btn-sm" onclick="updateResignationStatus(${req.id}, 'Processing')">Process</button>
                <button class="btn btn-danger btn-sm" onclick="declineResignation(${req.id}, '${escapeHTML(req.full_name)}')">Decline</button>
            `;
        } else if (req.status === 'Processing') {
            actionButtons = `<button class="btn btn-success btn-sm" onclick="updateResignationStatus(${req.id}, 'Completed')">Complete</button>`;
        } else if (req.status === 'Declined') {
            actionButtons = `<span class="badge badge-danger">Declined</span>`;
        } else {
            actionButtons = '<span class="text-muted">Processed</span>';
        }
        
        return `
            <tr>
                <td>${escapeHTML(req.full_name)}</td>
                <td>${escapeHTML(req.effective_date)}</td>
                <td>${escapeHTML(req.reason)}</td>
                <td><span class="status-badge status-${req.status.toLowerCase()}">${escapeHTML(req.status)}</span></td>
                <td>${actionButtons}</td>
            </tr>
        `;
    }).join('');
}

async function declineResignation(id, employeeName) {
    const result = await Swal.fire({
        title: 'Decline Resignation?',
        html: `<p>Are you sure you want to decline <strong>${employeeName}</strong>'s resignation request?</p>`,
        input: 'textarea',
        inputLabel: 'Reason for declining (optional)',
        inputPlaceholder: 'Enter reason...',
        showCancelButton: true,
        confirmButtonText: 'Decline',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        icon: 'warning'
    });

    if (result.isConfirmed) {
        const reason = result.value || '';
        const response = await fetch('backend/api.php?action=decline_resignation', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, reason })
        });
        const data = await response.json();
        if (data.success) {
            showToast('Resignation declined successfully', 'success');
            fetchData();
        } else {
            showToast(data.message || 'Failed to decline resignation', 'error');
        }
    }
}

async function updateResignationStatus(id, status) {
    const response = await fetch('backend/api.php?action=update_resignation_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
    });
    const result = await response.json();
    if (result.success) {
        showToast(`Resignation ${status.toLowerCase()} successfully.`, 'success');
        fetchData();
    } else {
        showToast(result.message || "Failed to update status.", 'error');
    }
}

// --- Deductions ---
function renderDeductions() {
    const govList = document.getElementById('gov-deductions-list');
    const companyList = document.getElementById('company-deductions-list');
    if (!govList || !companyList) return;

    const govDeductions = deductionsConfig.filter(d => d.is_government);
    const companyDeductions = deductionsConfig.filter(d => !d.is_government);

    govList.innerHTML = govDeductions.map(d => `
        <div class="deduction-item" style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid #eee;">
            <span>${d.name} (${d.value}${d.type === 'percentage' ? '%' : ' PHP'})</span>
            <div>
                <button class="btn btn-secondary btn-sm" onclick="editDeduction(${d.id})"><i class="fas fa-edit"></i></button>
                <button class="btn btn-danger btn-sm" onclick="deleteDeduction(${d.id})"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `).join('');

    companyList.innerHTML = companyDeductions.map(d => `
        <div class="deduction-item" style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid #eee;">
            <span>${d.name} (${d.value}${d.type === 'percentage' ? '%' : ' PHP'})</span>
            <div>
                <button class="btn btn-secondary btn-sm" onclick="editDeduction(${d.id})"><i class="fas fa-edit"></i></button>
                <button class="btn btn-danger btn-sm" onclick="deleteDeduction(${d.id})"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `).join('');
}



async function deleteDeduction(id) {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, delete it!'
    });

    if (result.isConfirmed) {
        const response = await fetch(`backend/api.php?action=delete_deduction&id=${id}`);
        const resultData = await response.json();
        if (resultData.success) {
            showToast("Deduction deleted successfully.", 'success');
            fetchData();
        } else {
            showToast(resultData.message || "Failed to delete deduction.", 'error');
        }
    }
}

async function editDeduction(id) {
    const deduction = deductionsConfig.find(d => d.id == id);
    if (!deduction) return;

    const { value: formValues } = await Swal.fire({
        title: 'Edit Deduction',
        html:
            `<label>Name</label><input id="swal-input1" class="swal2-input" value="${deduction.name}">` +
            `<label>Type</label><select id="swal-input2" class="swal2-input">
                <option value="percentage" ${deduction.type === 'percentage' ? 'selected' : ''}>Percentage</option>
                <option value="fixed" ${deduction.type === 'fixed' ? 'selected' : ''}>Fixed</option>
            </select>` +
            `<label>Value</label><input id="swal-input3" class="swal2-input" type="number" step="0.01" value="${deduction.value}">` +
            `<div style="margin-top: 10px;">
                <input id="swal-input4" type="checkbox" ${deduction.is_active ? 'checked' : ''}> <label for="swal-input4">Active</label>
            </div>` +
            `<div style="margin-top: 5px;">
                <input id="swal-input5" type="checkbox" ${deduction.is_government ? 'checked' : ''}> <label for="swal-input5">Government Mandated</label>
            </div>`,
        focusConfirm: false,
        preConfirm: () => {
            return {
                name: document.getElementById('swal-input1').value,
                type: document.getElementById('swal-input2').value,
                value: parseFloat(document.getElementById('swal-input3').value),
                is_active: document.getElementById('swal-input4').checked ? 1 : 0,
                is_government: document.getElementById('swal-input5').checked ? 1 : 0
            }
        }
    });

    if (formValues) {
        saveDeduction({ ...deduction, ...formValues });
    }
}

// --- Allowances ---
async function addAllowanceCategory() {
    const name = document.getElementById('allowanceName')?.value || '';
    const type = document.getElementById('allowanceType')?.value || '';
    const rate = document.getElementById('allowanceRate')?.value || '';

    if (!name || !rate) return showToast("Please enter a name and rate.", 'error');

    const response = await fetch('backend/api.php?action=add_allowance_category', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, type, rate })
    });
    const result = await response.json();
    showToast(result.message, result.success ? 'success' : 'error');
    if (result.success) {
        document.getElementById('allowanceName').value = '';
        document.getElementById('allowanceRate').value = '';
        renderAllowances();
    }
}

// ============================================================================
// MULTI-SELECT COMPONENT FUNCTIONS
// ============================================================================

// Toggle dropdown visibility
function toggleMultiSelect(type) {
    const dropdown = document.getElementById(`${type}Dropdown`);
    const header = dropdown?.previousElementSibling;
    
    if (dropdown) {
        const isOpen = dropdown.classList.contains('show');
        
        // Close all other dropdowns
        document.querySelectorAll('.multi-select-dropdown.show').forEach(d => {
            d.classList.remove('show');
            d.previousElementSibling?.classList.remove('active');
        });
        
        // Toggle current dropdown
        if (!isOpen) {
            dropdown.classList.add('show');
            header?.classList.add('active');
        }
    }
}

// Toggle individual option selection
function toggleMultiSelectOption(type, id) {
    const option = document.querySelector(`#${type}Options .multi-select-option[data-id="${id}"]`);
    const checkbox = option?.querySelector('input[type="checkbox"]');
    
    if (option && checkbox) {
        const isSelected = option.classList.contains('selected');
        
        if (isSelected) {
            option.classList.remove('selected');
            checkbox.checked = false;
        } else {
            option.classList.add('selected');
            checkbox.checked = true;
        }
        
        updateSelectedTags(type);
    }
}

// Update selected tags display
function updateSelectedTags(type) {
    const options = document.querySelectorAll(`#${type}Options .multi-select-option.selected`);
    const selectedContainer = document.getElementById(`${type}Selected`);
    const header = document.querySelector(`#${type}TypesList .multi-select-header`);
    const clearBtn = document.querySelector(`#${type}TypesList .multi-select-clear`);
    
    if (!selectedContainer || !header) return;
    
    // Clear existing tags
    selectedContainer.innerHTML = '';
    
    if (options.length === 0) {
        header.innerHTML = `
            <span class="multi-select-placeholder">Select ${type} types...</span>
            <i class="fas fa-chevron-down"></i>
        `;
        if (clearBtn) clearBtn.classList.remove('show');
    } else {
        // Update header with count
        header.innerHTML = `
            <span>${options.length} ${type}(s) selected</span>
            <i class="fas fa-chevron-down"></i>
        `;
        
        // Add clear button if not exists
        if (!clearBtn) {
            const newClearBtn = document.createElement('button');
            newClearBtn.className = 'multi-select-clear';
            newClearBtn.innerHTML = '<i class="fas fa-times"></i>';
            newClearBtn.onclick = (e) => {
                e.stopPropagation();
                clearAllSelections(type);
            };
            header.appendChild(newClearBtn);
        }
        if (clearBtn) clearBtn.classList.add('show');
        
        // Create tags for each selected option
        options.forEach(option => {
            const id = option.dataset.id;
            const label = option.querySelector('.multi-select-label')?.textContent;
            const meta = option.querySelector('.multi-select-meta')?.textContent;
            
            const tag = document.createElement('div');
            tag.className = 'multi-select-tag';
            tag.innerHTML = `
                <span>${label}</span>
                <div class="multi-select-tag-remove" onclick="removeMultiSelectOption('${type}', ${id})">
                    <i class="fas fa-times"></i>
                </div>
            `;
            selectedContainer.appendChild(tag);
        });
    }
}

// Remove a specific selection
function removeMultiSelectOption(type, id) {
    const option = document.querySelector(`#${type}Options .multi-select-option[data-id="${id}"]`);
    const checkbox = option?.querySelector('input[type="checkbox"]');
    
    if (option && checkbox) {
        option.classList.remove('selected');
        checkbox.checked = false;
        updateSelectedTags(type);
    }
}

// Clear all selections
function clearAllSelections(type) {
    const options = document.querySelectorAll(`#${type}Options .multi-select-option.selected`);
    options.forEach(option => {
        option.classList.remove('selected');
        const checkbox = option.querySelector('input[type="checkbox"]');
        if (checkbox) checkbox.checked = false;
    });
    updateSelectedTags(type);
}

// Get selected IDs
function getSelectedMultiSelectIds(type) {
    const selected = [];
    document.querySelectorAll(`#${type}Options .multi-select-option.selected`).forEach(option => {
        selected.push(parseInt(option.dataset.id));
    });
    return selected;
}

// Filter options based on search
function filterMultiSelect(type) {
    const searchTerm = document.getElementById(`${type}Search`)?.value.toLowerCase() || '';
    const options = document.querySelectorAll(`#${type}Options .multi-select-option`);
    
    options.forEach(option => {
        const label = option.querySelector('.multi-select-label')?.textContent.toLowerCase() || '';
        const meta = option.querySelector('.multi-select-meta')?.textContent.toLowerCase() || '';
        
        if (label.includes(searchTerm) || meta.includes(searchTerm)) {
            option.style.display = 'flex';
        } else {
            option.style.display = 'none';
        }
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.multi-select-container')) {
        document.querySelectorAll('.multi-select-dropdown.show').forEach(dropdown => {
            dropdown.classList.remove('show');
            dropdown.previousElementSibling?.classList.remove('active');
        });
    }
});

async function assignAllowance() {
    const employee_id = document.getElementById('assignEmployeeSelect')?.value || '';
    const selectedCategories = getSelectedMultiSelectIds('allowance');
    const override_amount = document.getElementById('allowanceOverrideAmount')?.value || '';
    const effective_date = document.getElementById('EffectiveDate')?.value || '';

    if (!employee_id) return showToast("Please select an employee.", 'error');
    if (selectedCategories.length === 0) return showToast("Please select at least one allowance category.", 'error');

    let successCount = 0;
    let errorCount = 0;

    // Assign each selected category
    for (const category_id of selectedCategories) {
        try {
            const response = await fetch('backend/api.php?action=assign_employee_allowance', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ employee_id, category_id, override_amount, effective_date })
            });
            const result = await response.json();
            if (result.success) {
                successCount++;
            } else {
                errorCount++;
            }
        } catch (error) {
            errorCount++;
        }
    }

    if (successCount > 0) {
        showToast(`${successCount} allowance(s) assigned successfully!`, 'success');
        renderAllowances();
    }
    if (errorCount > 0) {
        showToast(`${errorCount} allowance(s) failed to assign.`, 'error');
    }
}

async function applyAllowanceToAll() {
    const selectedCategories = getSelectedMultiSelectIds('allowance');
    const override_amount = document.getElementById('allowanceOverrideAmount')?.value || '';
    const effective_date = document.getElementById('EffectiveDate')?.value || '';

    if (selectedCategories.length === 0) return showToast("Please select at least one allowance category.", 'error');
    
    const confirmResult = await Swal.fire({
        title: 'Confirm Bulk Action',
        html: `Apply <strong>${selectedCategories.length}</strong> allowance(s) to <strong>ALL</strong> active employees?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, apply to all!',
        confirmButtonColor: '#667eea'
    });

    if (confirmResult.isConfirmed) {
        let successCount = 0;
        let errorCount = 0;
        
        // Apply each selected category to all employees
        for (const category_id of selectedCategories) {
            try {
                const response = await fetch('backend/api.php?action=bulk_assign_allowance', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ category_id, override_amount, effective_date })
                });
                const result = await response.json();
                if (result.success) {
                    successCount++;
                } else {
                    errorCount++;
                }
            } catch (error) {
                errorCount++;
            }
        }
        
        if (successCount > 0) {
            showToast(`${successCount} allowance(s) applied to all employees!`, 'success');
            renderAllowances();
        }
        if (errorCount > 0) {
            showToast(`${errorCount} allowance(s) failed to apply.`, 'error');
        }
    }
}

async function renderAllowances() {
    // 1. Render Categories Table
    const categoriesResponse = await fetch('backend/api.php?action=get_allowance_categories');
    const categories = await categoriesResponse.json();
    const categoriesBody = document.getElementById('allowanceCategoriesBody');
    if (categoriesBody) {
        categoriesBody.innerHTML = categories.map(c => `
            <tr>
                <td>${c.name}</td>
                <td>₱${parseFloat(c.rate).toLocaleString()}</td>
                <td>${c.type}</td>
                <td>
                    <button class="btn-icon delete" onclick="deleteAllowanceCategory(${c.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="4" class="text-center">No categories found.</td></tr>';
    }

    // 2. Render Assignment List for Multi-Select
    const optionsContainer = document.getElementById('allowanceOptions');
    if (optionsContainer) {
        optionsContainer.innerHTML = categories.map(c => `
            <div class="multi-select-option" data-id="${c.id}" onclick="toggleMultiSelectOption('allowance', ${c.id})">
                <input type="checkbox" id="allowance_${c.id}" value="${c.id}">
                <div class="multi-select-checkbox">
                    <i class="fas fa-check"></i>
                </div>
                <span class="multi-select-label">${c.name}</span>
                <span class="multi-select-meta">${c.type}: ₱${parseFloat(c.rate).toLocaleString()}</span>
            </div>
        `).join('') || '<p class="text-muted p-3 text-center">No categories available.</p>';
    }
    
    // Clear selected tags
    updateSelectedTags('allowance');

    // 3. Populate Employee Dropdown
    const empSelect = document.getElementById('assignEmployeeSelect');
    if (empSelect) {
        const empResponse = await fetch('backend/api.php?action=get_employees');
        const emps = await empResponse.json();
        empSelect.innerHTML = '<option value="">Select Employee...</option>' +
            emps.map(e => `<option value="${e.id}">${e.full_name} (${e.employee_id})</option>`).join('');
    }

    // 4. Render Breakdown Table
    // Correct backend action is 'get_employee_allowances' (was incorrectly calling 'get_allowance_breakdown')
    const breakdownResponse = await fetch('backend/api.php?action=get_employee_allowances');
    const breakdown = await breakdownResponse.json();
    const breakdownBody = document.getElementById('allowanceBreakdownBody');
    if (breakdownBody) {
        breakdownBody.innerHTML = breakdown.map(b => `
            <tr>
                <td><strong>${b.full_name}</strong><br><small>${b.emp_code}</small></td>
                <td>${b.category_name}</td>
                <td>₱${parseFloat(b.override_amount || b.category_rate).toLocaleString()}</td>
                <td>${b.effective_date || '---'}</td>
                <td>
                    <button class="btn-icon delete" onclick="deleteEmployeeAllowance(${b.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="text-center">No assignments found.</td></tr>';
    }
}

async function deleteEmployeeAllowance(id) {
    const confirmResult = await Swal.fire({
        title: 'Confirm Action',
        text: "Remove this allowance assignment?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, remove it!'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch(`backend/api.php?action=delete_employee_allowance&id=${id}`);
        const result = await response.json();
        if (result.success) {
            showToast("Allowance removed successfully.", 'success');
            renderAllowances();
        } else {
            showToast(result.message || "Failed to remove allowance.", 'error');
        }
    }
}

async function deleteAllowanceCategory(id) {
    const confirmResult = await Swal.fire({
        title: 'Confirm Action',
        text: "Delete this category? This will also remove assignments.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, delete it!'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch(`backend/api.php?action=delete_allowance_category&id=${id}`);
        const result = await response.json();
        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) renderAllowances();
    }
}

// --- Deductions ---
async function addDeductionCategory() {
    const name = document.getElementById('deductionName')?.value || '';
    const type = document.getElementById('deductionType')?.value || '';
    const value = document.getElementById('deductionRate')?.value || '';

    if (!name || !value) return showToast("Please enter a name and rate.", 'error');

    const response = await fetch('backend/api.php?action=save_deduction', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, type, value, is_active: true, is_government: false })
    });
    const result = await response.json();
    showToast(result.message, result.success ? 'success' : 'error');
    if (result.success) {
        document.getElementById('deductionName').value = '';
        document.getElementById('deductionRate').value = '';
        renderDeductions();
    }
}

async function assignDeduction() {
    const employee_id = document.getElementById('assignDeductionEmployeeSelect')?.value || '';
    const selectedDeductions = getSelectedMultiSelectIds('deduction');
    const override_amount = document.getElementById('deductionOverrideAmount')?.value || '';
    const effective_date = document.getElementById('deductionEffectiveDate')?.value || '';

    if (!employee_id) return showToast("Please select an employee.", 'error');
    if (selectedDeductions.length === 0) return showToast("Please select at least one deduction category.", 'error');

    let successCount = 0;
    let errorCount = 0;

    // Assign each selected deduction
    for (const deduction_id of selectedDeductions) {
        try {
            const response = await fetch('backend/api.php?action=assign_employee_deduction', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ employee_id, deduction_id, override_amount, effective_date })
            });
            const result = await response.json();
            if (result.success) {
                successCount++;
            } else {
                errorCount++;
            }
        } catch (error) {
            errorCount++;
        }
    }

    if (successCount > 0) {
        showToast(`${successCount} deduction(s) assigned successfully!`, 'success');
        renderDeductions();
    }
    if (errorCount > 0) {
        showToast(`${errorCount} deduction(s) failed to assign.`, 'error');
    }
}

async function applyDeductionToAll() {
    const selectedDeductions = getSelectedMultiSelectIds('deduction');
    const override_amount = document.getElementById('deductionOverrideAmount')?.value || '';
    const effective_date = document.getElementById('deductionEffectiveDate')?.value || '';

    if (selectedDeductions.length === 0) return showToast("Please select at least one deduction category.", 'error');
    
    const confirmResult = await Swal.fire({
        title: 'Confirm Bulk Action',
        html: `Apply <strong>${selectedDeductions.length}</strong> deduction(s) to <strong>ALL</strong> active employees?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, apply to all!',
        confirmButtonColor: '#667eea'
    });

    if (confirmResult.isConfirmed) {
        let successCount = 0;
        let errorCount = 0;
        
        // Apply each selected deduction to all employees
        for (const deduction_id of selectedDeductions) {
            try {
                const response = await fetch('backend/api.php?action=bulk_assign_deduction', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ deduction_id, override_amount, effective_date })
                });
                const result = await response.json();
                if (result.success) {
                    successCount++;
                } else {
                    errorCount++;
                }
            } catch (error) {
                errorCount++;
            }
        }
        
        if (successCount > 0) {
            showToast(`${successCount} deduction(s) applied to all employees!`, 'success');
            renderDeductions();
        }
        if (errorCount > 0) {
            showToast(`${errorCount} deduction(s) failed to apply.`, 'error');
        }
    }
}

async function renderDeductions() {
    // 1. Categories Table
    const categoriesResponse = await fetch('backend/api.php?action=get_deductions');
    const categories = await categoriesResponse.json();
    const categoriesBody = document.getElementById('deductionCategoriesBody');
    if (categoriesBody) {
        categoriesBody.innerHTML = categories.map(c => `
            <tr>
                <td>${c.name}</td>
                <td>₱${parseFloat(c.value).toLocaleString()}</td>
                <td>${c.type}</td>
                <td>
                    <button class="btn-icon delete" onclick="deleteDeductionCategory(${c.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="4" class="text-center">No categories found.</td></tr>';
    }

    // 2. Multi-Select List
    const optionsContainer = document.getElementById('deductionOptions');
    if (optionsContainer) {
        optionsContainer.innerHTML = categories.map(c => `
            <div class="multi-select-option" data-id="${c.id}" onclick="toggleMultiSelectOption('deduction', ${c.id})">
                <input type="checkbox" id="deduction_${c.id}" value="${c.id}">
                <div class="multi-select-checkbox">
                    <i class="fas fa-check"></i>
                </div>
                <span class="multi-select-label">${c.name}</span>
                <span class="multi-select-meta">${c.type}: ₱${parseFloat(c.value).toLocaleString()}</span>
            </div>
        `).join('') || '<p class="text-muted p-3 text-center">No categories available.</p>';
    }
    
    // Clear selected tags
    updateSelectedTags('deduction');

    // 3. Employee Dropdown
    const empSelect = document.getElementById('assignDeductionEmployeeSelect');
    if (empSelect) {
        const empResponse = await fetch('backend/api.php?action=get_employees');
        const emps = await empResponse.json();
        empSelect.innerHTML = '<option value="">Select Employee...</option>' +
            emps.map(e => `<option value="${e.id}">${e.full_name} (${e.employee_id})</option>`).join('');
    }

    // 4. Breakdown Table
    const breakdownResponse = await fetch('backend/api.php?action=get_deduction_breakdown');
    const breakdown = await breakdownResponse.json();
    const breakdownBody = document.getElementById('deductionBreakdownBody');
    if (breakdownBody) {
        breakdownBody.innerHTML = breakdown.map(b => `
            <tr>
                <td><strong>${b.full_name}</strong><br><small>${b.emp_code}</small></td>
                <td>${b.category_name}</td>
                <td>₱${parseFloat(b.override_amount || b.category_rate).toLocaleString()}</td>
                <td>${b.effective_date || '---'}</td>
                <td>
                    <button class="btn-icon delete" onclick="deleteEmployeeDeduction(${b.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="text-center">No assignments found.</td></tr>';
    }
}

async function deleteDeductionCategory(id) {
    const confirmResult = await Swal.fire({
        title: 'Confirm Action',
        text: "Delete this category?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, delete it!'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch(`backend/api.php?action=delete_deduction&id=${id}`);
        const result = await response.json();
        showToast(result.message, result.success ? 'success' : 'error');
        if (result.success) renderDeductions();
    }
}

async function deleteEmployeeDeduction(id) {
    const confirmResult = await Swal.fire({
        title: 'Confirm Action',
        text: "Remove this deduction assignment?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, remove it!'
    });

    if (confirmResult.isConfirmed) {
        const response = await fetch(`backend/api.php?action=delete_employee_deduction&id=${id}`);
        const result = await response.json();
        if (result.success) {
            showToast("Deduction removed successfully.", 'success');
            renderDeductions();
        } else {
            showToast(result.message || "Failed to remove deduction.", 'error');
        }
    }
}

// --- Settings ---
async function saveSettings() {
    const form = document.getElementById('settingsForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    const btn = document.getElementById('saveSettingsBtn');

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }

    try {
        const response = await fetch('backend/api.php?action=save_settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Settings saved successfully! Updating UI...',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            showToast("Error: " + result.message, 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Save System Settings';
            }
        }
    } catch (err) {
        console.error(err);
        showToast("An error occurred while saving settings.", 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save System Settings';
        }
    }
}

async function changePassword() {
    const oldPass = document.getElementById('oldPass').value;
    const newPass = document.getElementById('newPass').value;
    const confirmPass = document.getElementById('confirmPass').value;

    if (newPass !== confirmPass) {
        showToast("New passwords do not match!", 'error');
        return;
    }

    // Check if all requirements are met
    const reqs = document.querySelectorAll('.req-item');
    let allValid = true;
    reqs.forEach(req => {
        if (!req.classList.contains('valid')) {
            allValid = false;
        }
    });

    if (!allValid) {
        showToast("Password does not meet requirements!", 'error');
        return;
    }

    const response = await fetch('backend/api.php?action=change_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ oldPass, newPass })
    });

    const result = await response.json();
    if (result.success) {
        Swal.fire({
            icon: 'success',
            title: 'Password Updated',
            text: 'Your password has been changed successfully.',
            confirmButtonColor: '#1e0178'
        }).then(() => {
            closeModal('passwordModal');
        });
    } else {
        showToast(result.message, 'error');
    }
}

function checkPasswordStrength() {
    const password = document.getElementById('newPass').value;
    const reqs = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /\d/.test(password),
        special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
    };

    Object.keys(reqs).forEach(req => {
        const el = document.getElementById('req-' + req);
        if (el) {
            if (reqs[req]) {
                el.classList.add('valid');
            } else {
                el.classList.remove('valid');
            }
        }
    });
}

// --- Reports ---
function generateReport(type) {
    let csvContent = "data:text/csv;charset=utf-8,";
    let filename = `Report_${type}_${new Date().toISOString().split('T')[0]}.csv`;

    if (type === 'attendance') {
        csvContent += "Employee ID,Name,Date,Check-In,Check-Out,Status\n";
        attendanceLogs.forEach(log => csvContent += `${log.emp_code},${log.full_name},${log.log_date},${log.check_in},${log.check_out},${log.status}\n`);
    } else if (type === 'payroll') {
        csvContent += "Employee,Period,Basic Pay,Deductions,Net Pay,Status\n";
        payrollHistory.forEach(p => csvContent += `${p.full_name},${p.period},${p.basic_pay},${p.deductions},${p.net_pay},${p.status}\n`);
    } else if (type === 'employees') {
        csvContent += "Employee ID,Full Name,Position,Department,Status\n";
        employees.forEach(emp => csvContent += `${emp.employee_id},${emp.full_name},${emp.position},${emp.department},${emp.status}\n`);
    }

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

let selectedSubjectLoadId = null;

function viewFacultyLoads(empId) {
    const emp = employees.find(e => e.id == empId);
    if (!emp) return;

    const facultyLoads = subjectLoads.filter(load => load.faculty_id == empId);
    const tbody = document.getElementById('viewLoadsTableBody');
    const title = document.getElementById('viewLoadsTitle');

    if (title) title.innerText = `Subject Loads: ${emp.full_name}`;

    if (tbody) {
        tbody.innerHTML = facultyLoads.map(load => `
            <tr onclick="selectSubjectLoad(${load.id})" style="cursor:pointer;" class="load-row" data-load-id="${load.id}">
                <td><strong>${load.code}</strong></td>
                <td>${load.description}</td>
                <td>${load.units}</td>
                <td>${load.hours}</td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="event.stopPropagation(); deleteSubjectLoad('${load.id}'); viewFacultyLoads('${empId}');"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('') || '<tr><td colspan="5" class="text-center">No loads assigned to this faculty.</td></tr>';
    }
    
    selectedSubjectLoadId = null;
    document.getElementById('scheduleSection').style.display = 'none';

    openModal('viewLoadsModal');
}

function selectSubjectLoad(loadId) {
    selectedSubjectLoadId = loadId;
    document.querySelectorAll('.load-row').forEach(r => r.classList.remove('selected'));
    const row = document.querySelector(`.load-row[data-load-id="${loadId}"]`);
    if (row) row.classList.add('selected');
    document.getElementById('scheduleSection').style.display = 'block';
    loadSchedules(loadId);
}

async function loadSchedules(loadId) {
    try {
        const response = await fetch(`backend/api.php?action=get_subject_schedules&subject_load_id=${loadId}`);
        const schedules = await response.json();
        const tbody = document.getElementById('scheduleTableBody');
        if (tbody) {
            tbody.innerHTML = (Array.isArray(schedules) ? schedules : []).map(s => `
                <tr>
                    <td>${s.day_of_week}</td>
                    <td>${formatTime(s.time_start)}</td>
                    <td>${formatTime(s.time_end)}</td>
                    <td>${s.room || '---'}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="deleteSchedule(${s.id})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `).join('') || '<tr><td colspan="5" class="text-center">No schedules for this load.</td></tr>';
        }
    } catch (e) {
        console.error('Error loading schedules:', e);
    }
}

async function saveSchedule() {
    if (!selectedSubjectLoadId) {
        showToast('Select a subject load first', 'warning');
        return;
    }
    const day = document.getElementById('schedDay').value;
    const timeStart = document.getElementById('schedTimeStart').value;
    const timeEnd = document.getElementById('schedTimeEnd').value;
    const room = document.getElementById('schedRoom').value;
    
    if (!timeStart || !timeEnd) {
        showToast('Time start and time end are required', 'warning');
        return;
    }
    
    try {
        const response = await fetch('backend/api.php?action=save_subject_schedule', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                subject_load_id: selectedSubjectLoadId,
                day_of_week: day,
                time_start: timeStart,
                time_end: timeEnd,
                room: room
            })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Schedule added', 'success');
            document.getElementById('schedTimeStart').value = '';
            document.getElementById('schedTimeEnd').value = '';
            document.getElementById('schedRoom').value = '';
            loadSchedules(selectedSubjectLoadId);
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    } catch (e) {
        showToast('Failed to save schedule', 'error');
    }
}

async function deleteSchedule(id) {
    const confirmResult = await Swal.fire({
        title: 'Delete Schedule?',
        text: 'Are you sure?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        confirmButtonText: 'Delete'
    });
    if (!confirmResult.isConfirmed) return;
    try {
        const response = await fetch(`backend/api.php?action=delete_subject_schedule&id=${id}`);
        const result = await response.json();
        if (result.success) {
            showToast('Schedule deleted', 'success');
            if (selectedSubjectLoadId) loadSchedules(selectedSubjectLoadId);
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    } catch (e) {
        showToast('Failed to delete schedule', 'error');
    }
}

async function deleteSubjectLoad(id) {
    const confirmResult = await Swal.fire({
        title: 'Delete Load?',
        text: 'Are you sure you want to delete this subject load?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, delete it!'
    });

    if (confirmResult.isConfirmed) {
        try {
            const response = await fetch(`backend/api.php?action=delete_subject_load&id=${id}`);
            const result = await response.json();
            if (result.success) {
                showToast('Subject load deleted successfully', 'success');
                await fetchData('employees'); // Refresh since it can affect modals
            } else {
                showToast('Error: ' + result.message, 'error');
            }
        } catch (error) {
            showToast('Failed to connect to the server.', 'error');
        }
    }
}

async function saveSubjectLoad() {
    const form = document.getElementById('subjectLoadForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = {
        faculty_id: document.getElementById('loadFacultyId').value,
        code: document.getElementById('loadSubjectCode').value,
        description: document.getElementById('loadDescription').value,
        units: document.getElementById('loadUnits').value,
        hours: document.getElementById('loadHours').value
    };

    try {
        const response = await fetch('backend/api.php?action=save_subject_load', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            closeModal('addLoadModal');
            document.getElementById('subjectLoadForm').reset();
            showToast('Subject load saved successfully!', 'success');
            fetchData('employees');
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    } catch (err) {
        showToast('Failed to connect to the server.', 'error');
    }
}

function openAddSubjectModal() {
    document.getElementById('masterSubjectForm').reset();
    document.getElementById('subjectId').value = '';
    document.getElementById('subjectModalTitle').innerText = 'Create New Subject';
    openModal('subjectModal');
}

function editMasterSubject(id) {
    const subject = masterSubjects.find(s => s.id == id);
    if (!subject) return;

    document.getElementById('subjectId').value = subject.id;
    document.getElementById('subjectCode').value = subject.code;
    document.getElementById('subjectDescription').value = subject.description;
    document.getElementById('subjectUnits').value = subject.units;
    document.getElementById('subjectHours').value = subject.hours;

    document.getElementById('subjectModalTitle').innerText = 'Edit Subject';
    openModal('subjectModal');
}

async function deleteMasterSubject(id) {
    const confirmResult = await Swal.fire({
        title: 'Delete Subject?',
        text: 'Are you sure you want to delete this subject?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db261f',
        cancelButtonColor: '#1e0178',
        confirmButtonText: 'Yes, delete it!'
    });

    if (confirmResult.isConfirmed) {
        try {
            const response = await fetch(`backend/api.php?action=delete_subject&id=${id}`);
            const result = await response.json();
            if (result.success) {
                showToast('Subject deleted successfully', 'success');
                fetchData('subject_loads');
            } else {
                showToast('Error: ' + result.message, 'error');
            }
        } catch (error) {
            showToast('Failed to connect to the server.', 'error');
        }
    }
}

async function saveMasterSubject() {
    const form = document.getElementById('masterSubjectForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = {
        id: document.getElementById('subjectId').value,
        code: document.getElementById('subjectCode').value,
        description: document.getElementById('subjectDescription').value,
        units: document.getElementById('subjectUnits').value,
        hours: document.getElementById('subjectHours').value
    };

    try {
        const response = await fetch('backend/api.php?action=save_subject', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            closeModal('subjectModal');
            document.getElementById('masterSubjectForm').reset();
            document.getElementById('subjectId').value = '';
            document.getElementById('subjectModalTitle').innerText = 'Create New Subject';
            showToast('Subject saved successfully!', 'success');
            fetchData('subject_loads');
        } else {
            showToast('Error: ' + result.message, 'error');
        }
    } catch (err) {
        showToast('Failed to connect to the server.', 'error');
    }
}

// --- Biometrics Enrollment ---
let registeredFaceMatcher = null;
const faceManager = new FaceManager({
    stabilityRequired: 8,
    sampleCount: 5,
    stabilityThreshold: 12
});

async function initFaceRegistration() {
    const select = document.getElementById('regEmployeeSelect');
    const employeeId = select.value;
    if (!employeeId) return showToast("Please select an employee before starting registration.", "error");

    const video = document.getElementById('video');
    const canvas = document.getElementById('overlay');
    const captureBtn = document.getElementById('captureBtn');
    const startBtn = document.getElementById('startRegBtn');
    const placeholder = document.getElementById('camera-placeholder');
    const placeholderText = placeholder.querySelector('p');

    // Reset UI state before starting
    if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    placeholder.style.display = 'flex';
    placeholderText.innerText = "Initializing AI Models...";

    // Prevent file drag and drop on the video/canvas area to bypass camera
    const preventDragDrop = (e) => {
        e.preventDefault();
        if (e.type === 'drop' || e.type === 'paste') {
            Swal.fire({
                icon: 'error',
                title: 'Action Blocked',
                text: 'Only live camera feed is accepted – please use the built-in camera.',
                confirmButtonColor: '#1e0178'
            });
        }
    };
    
    if (video) {
        video.addEventListener('dragover', preventDragDrop);
        video.addEventListener('drop', preventDragDrop);
    }
    if (canvas) {
        canvas.addEventListener('dragover', preventDragDrop);
        canvas.addEventListener('drop', preventDragDrop);
    }
    
    // Prevent pasting images globally while in registration
    document.addEventListener('paste', (e) => {
        if (faceManager.registrationActive && e.clipboardData && e.clipboardData.files && e.clipboardData.files.length > 0) {
            preventDragDrop(e);
        }
    });

    // Ensure audio context is ready for beeps
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    function playSpoofBeep() {
        if (audioContext.state === 'suspended') audioContext.resume();
        const osc = audioContext.createOscillator();
        const gain = audioContext.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(800, audioContext.currentTime);
        gain.gain.setValueAtTime(0.5, audioContext.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
        osc.connect(gain);
        gain.connect(audioContext.destination);
        osc.start();
        osc.stop(audioContext.currentTime + 0.3);
    }

    try {
        // Load models with retry logic for stability
        let modelLoadAttempts = 0;
        const maxModelAttempts = 3;
        
        while (modelLoadAttempts < maxModelAttempts) {
            try {
                await faceManager.loadModels();
                break;
            } catch (err) {
                modelLoadAttempts++;
                if (modelLoadAttempts >= maxModelAttempts) {
                    throw new Error("Failed to load face recognition models after multiple attempts. Please refresh the page.");
                }
                placeholderText.innerText = `Retrying model load (${modelLoadAttempts}/${maxModelAttempts})...`;
                await new Promise(r => setTimeout(r, 1000));
            }
        }
        
        placeholderText.innerText = "Starting Camera...";

        // Start camera with device-specific settings for better compatibility
        await faceManager.startCamera(video, 640, 480);
        
        // Wait for video to be ready
        await new Promise((resolve, reject) => {
            const timeout = setTimeout(() => reject(new Error("Camera timeout. Please check permissions.")), 10000);
            
            if (video.readyState >= 2) {
                clearTimeout(timeout);
                resolve();
            } else {
                video.onloadeddata = () => {
                    clearTimeout(timeout);
                    resolve();
                };
                video.onerror = () => {
                    clearTimeout(timeout);
                    reject(new Error("Failed to load video stream."));
                };
            }
        });
        
        placeholder.style.display = 'none';

        startBtn.style.display = 'none';
        captureBtn.style.display = 'inline-block';
        captureBtn.disabled = true;

        faceManager.registrationActive = true;
        faceManager.isProcessing = false;

        // Use adaptive detection settings for better cross-device compatibility
        const detectorOptions = new faceapi.TinyFaceDetectorOptions({ 
            inputSize: 320,  // Higher input size for better accuracy
            scoreThreshold: 0.5  // Balanced threshold
        });

        let noFaceCount = 0;
        const maxNoFaceFrames = 30; // Reset stability after 30 frames without face

        const loop = async () => {
            if (!faceManager.stream || !faceManager.registrationActive) return;

            if (!faceManager.isProcessing) {
                try {
                    const detection = await faceapi.detectSingleFace(video, detectorOptions).withFaceLandmarks();
                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    if (detection) {
                        faceManager.lostFaceCount = 0;
                        noFaceCount = 0; // Reset counter when face is detected
                        
                        // Check stability
                        const isStable = faceManager.checkStability(detection.detection.box);
                        
                        // Check if face is frontal for better enrollment quality
                        const frontalCheck = faceManager.checkFrontalFace(detection.landmarks);
                        const isFrontal = frontalCheck.isFrontal;

                        // Passive-only liveness check (no active actions needed)
                        const isPassiveLive = faceManager.checkAllLiveness(detection.landmarks, detection.detection.box);

                        let status, color;
                        
                        if (!isPassiveLive) {
                            status = "NO PHOTO/IMAGE ACCEPTED";
                            color = "#db261f"; // Red error
                            captureBtn.disabled = true;
                            
                            playSpoofBeep();
                            
                            // Show error message
                            Swal.fire({
                                icon: 'error',
                                title: 'Presentation Attack Detected',
                                text: 'Photos and pictures are not accepted. Please present your live face directly to the camera.',
                                confirmButtonColor: '#1e0178'
                            });
                            
                            // Log spoof attempt
                            fetch('backend/api.php?action=log_spoof_attempt', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    company_id: currentCompanyId || 1, // Fallback if undefined in script context
                                    timestamp: new Date().toISOString(),
                                    reason: 'Photo/spoof detected during registration – access denied'
                                })
                            }).catch(e => console.error("Logging spoof failed:", e));
                            
                            faceManager.isProcessing = true;
                            setTimeout(() => { faceManager.isProcessing = false; }, 3000);
                        } else if (!isFrontal) {
                            // Face not looking straight
                            if (!frontalCheck.details.yawOk) {
                                status = "TURN FACE TO CAMERA";
                            } else if (!frontalCheck.details.pitchOk) {
                                status = "LOOK STRAIGHT";
                            } else if (!frontalCheck.details.rollOk) {
                                status = "KEEP HEAD LEVEL";
                            } else {
                                status = "FACE CAMERA";
                            }
                            color = "#f39c12"; // Orange
                        } else if (!isStable) {
                            status = "HOLD STILL...";
                            color = "#f39c12"; // Orange
                        } else {
                            // Frontal, stable, and passive liveness verified
                            status = "✓ PERFECT! CAPTURING...";
                            color = "#27ae60"; // Green
                            captureBtn.disabled = false; // Enabled
                        }

                        faceManager.drawDetection(canvas, video, detection, status, color);

                        // Only capture automatically if face is frontal, stable, AND passive liveness verified
                        if (isFrontal && isStable && isPassiveLive) {
                            faceManager.isProcessing = true;
                            setTimeout(() => saveFaceRegistration(), 300);
                        }
                    } else {
                        noFaceCount++;
                        faceManager.stabilityCounter = 0;
                        faceManager.lostFaceCount = (faceManager.lostFaceCount || 0) + 1;
                        captureBtn.disabled = true;
                        
                        // Show helpful message if no face detected for too long
                        if (noFaceCount > maxNoFaceFrames && noFaceCount % 30 === 0) {
                            showToast("No face detected. Please ensure good lighting and look at the camera.", "warning");
                        }
                    }
                } catch (err) {
                    console.error("Detection error:", err);
                    // Don't break the loop on detection errors
                }
            }
            requestAnimationFrame(loop);
        };
        loop();

    } catch (err) {
        console.error("Registration Error:", err);
        showToast(err.message, "error");
        stopRegistrationCamera();
    }
}

async function saveFaceRegistration() {
    const employeeId = document.getElementById('regEmployeeSelect').value;
    const video = document.getElementById('video');
    const captureBtn = document.getElementById('captureBtn');
    const canvas = document.getElementById('overlay');
    const ctx = canvas.getContext('2d');

    if (!employeeId) return;
    faceManager.isProcessing = true;

    if (captureBtn) {
        captureBtn.disabled = true;
        captureBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    }

    try {
        const averagedDescriptor = await faceManager.captureSamples(video, (current, total) => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = "#27ae60";
            ctx.font = "bold 24px Inter, sans-serif";
            ctx.textAlign = "center";

            // Un-mirror the text since the canvas is mirrored via CSS
            ctx.save();
            ctx.scale(-1, 1);
            ctx.fillText(`CAPTURING SAMPLE ${current}/${total}...`, -canvas.width / 2, canvas.height / 2);
            ctx.restore();
        });

        // Clear canvas after capture loop finishes
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (captureBtn) captureBtn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Saving Data...';

        const response = await fetch('backend/api.php?action=save_face_registration', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: employeeId, descriptor: averagedDescriptor })
        });

        const result = await response.json();
        if (result.success) {
            showToast("Registration Complete! Face data saved securely.", "success");
            setTimeout(() => {
                stopRegistrationCamera();
                // Optionally reload or just reset the select
                document.getElementById('regEmployeeSelect').value = '';
            }, 1500);
        } else {
            throw new Error(result.message);
        }
    } catch (err) {
        showToast("Registration Failed: " + err.message, "error");
        faceManager.isProcessing = false;
        if (captureBtn) {
            captureBtn.disabled = false;
            captureBtn.innerHTML = '<i class="fas fa-user-plus"></i> Retry Registration';
        }
    }
}

// Consolidating stop camera logic above, removing duplicate here
// --- Charts ---
function initCharts() {
    const ctxP = document.getElementById('payrollChart')?.getContext('2d');
    const ctxA = document.getElementById('attendanceChart')?.getContext('2d');
    const ctxM = document.getElementById('monthlyTrendsChart')?.getContext('2d');
    if (!ctxP || !ctxA) return;

    if (pChart) pChart.destroy();
    if (aChart) aChart.destroy();
    if (mChart) mChart.destroy();

    const parseMySqlDateTime = (value) => {
        if (!value) return null;
        const iso = String(value).includes('T') ? String(value) : String(value).replace(' ', 'T');
        const d = new Date(iso);
        if (isNaN(d.getTime())) return null;
        return d;
    };

    const monthLabel = (d) => d.toLocaleString('default', { month: 'short' });
    const monthKey = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    const now = new Date();
    const last6 = [];
    for (let i = 5; i >= 0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        last6.push({ key: monthKey(d), label: monthLabel(d) });
    }

    // Calculate payroll expenditure from real payroll data
    const sums = Object.fromEntries(last6.map(m => [m.key, 0]));
    for (const p of payrollHistory) {
        const d = parseMySqlDateTime(p.created_at);
        if (!d) continue;
        const key = monthKey(d);
        if (key in sums) sums[key] += parseFloat(p.net_pay || 0);
    }

    // Calculate attendance breakdown from REAL attendance logs
    const todayStr = new Date().toISOString().split('T')[0];
    
    // Get unique employees who checked in today
    const presentIds = new Set(
        attendanceLogs
            .filter(l => l.log_date === todayStr && l.check_in)
            .map(l => String(l.employee_id))
    );
    
    // Count employees on leave
    const onLeaveCount = employees.filter(e => (e.status || '').toLowerCase() === 'on leave').length;
    
    // Count active employees (not inactive)
    const activeCount = employees.filter(e => (e.status || '').toLowerCase() !== 'inactive').length;
    
    // Present = unique employees who checked in today
    const presentCount = presentIds.size;
    
    // Absent = active employees - present - on leave
    const absentCount = Math.max(activeCount - onLeaveCount - presentCount, 0);

    // Update dashboard stat cards with real data
    const totalEl = document.getElementById('stat-total-emp');
    const presentEl = document.getElementById('stat-present');
    const absentEl = document.getElementById('stat-absent');
    const leaveEl = document.getElementById('stat-leave');
    if (totalEl) totalEl.innerText = String(employees.length);
    if (presentEl) presentEl.innerText = String(presentCount);
    if (absentEl) absentEl.innerText = String(absentCount);
    if (leaveEl) leaveEl.innerText = String(leaveRequests.filter(r => r.status === 'Pending').length);

    // Payroll Expenditure Chart (Last 6 Months)
    pChart = new Chart(ctxP, {
        type: 'line',
        data: {
            labels: last6.map(m => m.label),
            datasets: [{
                label: 'Expenditure (₱)',
                data: last6.map(m => Math.round(sums[m.key])),
                borderColor: '#3b4fc9',
                backgroundColor: 'rgba(59, 79, 201, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Attendance Breakdown Chart (Today's Real Data)
    aChart = new Chart(ctxA, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'On Leave'],
            datasets: [{
                data: [presentCount, absentCount, onLeaveCount],
                backgroundColor: ['#27ae60', '#c0392b', '#f39c12']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = presentCount + absentCount + onLeaveCount;
                            const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Monthly Attendance Trends (stacked bar chart)
    if (ctxM) {
        const trendsByMonth = {};
        for (const l of attendanceLogs) {
            if (!l.log_date) continue;
            const m = l.log_date.substring(0, 7);
            if (!trendsByMonth[m]) trendsByMonth[m] = { on_time: 0, late: 0, absent: 0 };
            const s = (l.status || '').toLowerCase();
            if (s === 'on-time' || s === 'on time') trendsByMonth[m].on_time++;
            else if (s === 'late') trendsByMonth[m].late++;
            else if (s === 'absent') trendsByMonth[m].absent++;
        }
        
        const months = Object.keys(trendsByMonth).sort().slice(-6);
        const onTimeData = months.map(m => trendsByMonth[m].on_time);
        const lateData = months.map(m => trendsByMonth[m].late);
        const absentData = months.map(m => trendsByMonth[m].absent);
        const monthLabels = months.map(m => {
            const d = new Date(m + '-01');
            return d.toLocaleString('default', { month: 'short', year: '2-digit' });
        });

        mChart = new Chart(ctxM, {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [
                    { label: 'On-Time', data: onTimeData, backgroundColor: '#27ae60' },
                    { label: 'Late', data: lateData, backgroundColor: '#f39c12' },
                    { label: 'Absent', data: absentData, backgroundColor: '#c0392b' }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        mode: 'index',
                        intersect: true
                    }
                },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true }
                }
            }
        });
    }
}

// --- Auth ---
async function logout() {
    const confirmResult = await Swal.fire({
        title: 'Logout?',
        text: "Are you sure you want to logout?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e0178',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, logout'
    });

    if (confirmResult.isConfirmed) {
        await fetch('backend/api.php?action=logout');
        window.location.href = 'login.php';
    }
}

// Initialize on Load
window.onload = () => {
    fetchData();
    const dateFilter = document.getElementById('attendanceDateFilter');
    if (dateFilter) dateFilter.addEventListener('change', renderAttendanceTable);
    
    // Add event listeners for auto-generating full name
    const firstNameInput = document.querySelector('input[name="firstName"]');
    const lastNameInput = document.querySelector('input[name="lastName"]');
    const middleInitialInput = document.querySelector('input[name="middleInitial"]');
    const fullNameDisplay = document.getElementById('fullNameDisplay');
    
    if (firstNameInput && lastNameInput && fullNameDisplay) {
        const updateFullName = () => {
            const firstName = firstNameInput.value.trim();
            const lastName = lastNameInput.value.trim();
            const middleInitial = middleInitialInput ? middleInitialInput.value.trim() : '';
            
            if (firstName || lastName) {
                const fullName = firstName + (middleInitial ? ' ' + middleInitial.toUpperCase() + '.' : '') + (lastName ? ' ' + lastName : '');
                fullNameDisplay.value = fullName.trim();
            } else {
                fullNameDisplay.value = '';
            }
        };
        
        firstNameInput.addEventListener('input', updateFullName);
        lastNameInput.addEventListener('input', updateFullName);
        if (middleInitialInput) {
            middleInitialInput.addEventListener('input', (e) => {
                // Ensure only single character
                if (e.target.value.length > 1) {
                    e.target.value = e.target.value.charAt(0);
                }
                updateFullName();
            });
        }
    }

    // Payroll Bulk Operations Initialization (Task 1.3)
    initPayrollBulkSelection();
};

// Payroll Bulk Operations Functions (NEW for Task 1.3)
let selectedPayrollRows = new Set();

function initPayrollBulkSelection() {
    // Add select-all checkbox to payroll header if not exists
    const payrollHeader = document.querySelector('#payrollTable thead tr');
    if (payrollHeader && !payrollHeader.querySelector('th.select-col')) {
        const selectTh = document.createElement('th');
        selectTh.className = 'select-col';
        selectTh.innerHTML = '<input type="checkbox" id="selectAllPayroll" onchange="toggleSelectAllPayroll(this.checked)">';
        payrollHeader.prepend(selectTh);
        
        // Add checkboxes to table body rows
        const tableBody = document.querySelector('#payrollTableBody');
        const observer = new MutationObserver(addRowCheckboxes);
        observer.observe(tableBody, { childList: true });
        addRowCheckboxes(); // Initial call
    }
}

function addRowCheckboxes() {
    document.querySelectorAll('#payrollTableBody tr:not(:has(input[type="checkbox"]))').forEach(row => {
        const checkboxTd = document.createElement('td');
        checkboxTd.className = 'select-col';
        checkboxTd.innerHTML = '<input type="checkbox" class="payroll-row-select" onchange="togglePayrollRowSelection(this)">';
        row.prepend(checkboxTd);
    });
}

function toggleSelectAllPayroll(checked) {
    document.querySelectorAll('.payroll-row-select').forEach(cb => {
        cb.checked = checked;
        togglePayrollRowSelection(cb);
    });
}

function togglePayrollRowSelection(checkbox) {
    const row = checkbox.closest('tr');
    const payrollId = row.dataset.payrollId || row.cells[1]?.textContent.trim(); // Fallback to period cell
    
    if (checkbox.checked) {
        selectedPayrollRows.add(payrollId);
        row.classList.add('table-selected');
    } else {
        selectedPayrollRows.delete(payrollId);
        row.classList.remove('table-selected');
    }
}

async function bulkSalaryAdjustment(multiplier) {
    if (selectedPayrollRows.size === 0) {
        showToast('Select rows first or use "ALL" option', 'warning');
        return;
    }

    const {isConfirmed} = await Swal.fire({
        title: 'Bulk Salary Adjustment',
        html: `Apply ${(multiplier*100-100).toFixed(1)}% adjustment to ${selectedPayrollRows.size} selected payroll rows?`,
        icon: 'question',
        showCancelButton: true
    });

    if (isConfirmed) {
        const response = await fetch('backend/api.php?action=bulk_payroll_adjustment', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                payroll_ids: Array.from(selectedPayrollRows),
                multiplier: multiplier
            })
        });

        const result = await response.json();
        showToast(result.message || (result.success ? 'Bulk adjustment complete!' : 'Failed'), result.success ? 'success' : 'error');
        
        if (result.success) {
            selectedPayrollRows.clear();
            document.querySelectorAll('#payrollTable tr.table-selected').forEach(r => r.classList.remove('table-selected'));
            renderPayrollTable(); // Refresh
        }
    }
}

async function bulkUpdateSelected() {
    if (selectedPayrollRows.size === 0) {
        showToast('No rows selected', 'warning');
        return;
    }

    const {value} = await Swal.fire({
        title: 'Bulk Update Selected',
        html: `
            <input id="bulkAmount" class="swal2-input" type="number" step="0.01" placeholder="Enter new amount">
            <select id="bulkField" class="swal2-select">
                <option value="basic_pay">Basic Pay</option>
                <option value="net_pay">Net Pay</option>
                <option value="allowances">Allowances</option>
            </select>
        `,
        preConfirm: () => ({
            amount: parseFloat(document.getElementById('bulkAmount').value),
            field: document.getElementById('bulkField').value
        })
    });

    if (value) {
        const response = await fetch('backend/api.php?action=bulk_payroll_update', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                payroll_ids: Array.from(selectedPayrollRows),
                field: value.field,
                value: value.amount
            })
        });

        const result = await response.json();
        showToast(result.message || 'Bulk update complete!', result.success ? 'success' : 'error');
        
        if (result.success) {
            selectedPayrollRows.clear();
            renderPayrollTable();
        }
    }
}

// Add CSS for bulk selection
if (!document.getElementById('bulk-styles')) {
    const style = document.createElement('style');
    style.id = 'bulk-styles';
    style.textContent = `
        .select-col { width: 50px; text-align: center; }
        .table-selected { background: #e3f2fd !important; }
        #payrollTable input[type="checkbox"] { transform: scale(1.2); }
        .bulk-actions { display: flex; gap: 8px; margin: 0 10px; }
        .bulk-actions .btn { padding: 6px 12px; font-size: 0.85em; }
    `;
    document.head.appendChild(style);
}
