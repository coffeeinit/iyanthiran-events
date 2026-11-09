// Admin Panel JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeAdminPanel();
    initializeDataTables();
    initializeFileUploads();
    initializeCharts();
    initializeFormValidation();
});

function initializeAdminPanel() {
    // Sidebar toggle for mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });
    }
    
    // Active menu highlighting
    const currentPath = window.location.pathname;
    const menuLinks = document.querySelectorAll('.sidebar-menu a');
    
    menuLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath || 
            (currentPath.includes(link.getAttribute('href')) && link.getAttribute('href') !== '#')) {
            link.classList.add('active');
        }
    });
    
    // Auto-refresh dashboard stats
    if (document.querySelector('.dashboard-stats')) {
        setInterval(refreshDashboardStats, 30000); // Refresh every 30 seconds
    }
}

function initializeDataTables() {
    const tables = document.querySelectorAll('.admin-table table');
    
    tables.forEach(table => {
        // Add sorting functionality
        const headers = table.querySelectorAll('thead th');
        headers.forEach((header, index) => {
            if (!header.classList.contains('no-sort')) {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => sortTable(table, index));
            }
        });
        
        // Add search functionality
        addTableSearch(table);
    });
}

function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const header = table.querySelectorAll('thead th')[columnIndex];
    
    const isAscending = !header.classList.contains('sort-desc');
    
    // Clear all sort classes
    table.querySelectorAll('thead th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });
    
    // Add appropriate sort class
    header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
    
    rows.sort((a, b) => {
        const aVal = a.cells[columnIndex].textContent.trim();
        const bVal = b.cells[columnIndex].textContent.trim();
        
        // Check if values are numbers
        if (!isNaN(aVal) && !isNaN(bVal)) {
            return isAscending ? aVal - bVal : bVal - aVal;
        }
        
        // Check if values are dates
        const aDate = new Date(aVal);
        const bDate = new Date(bVal);
        if (!isNaN(aDate) && !isNaN(bDate)) {
            return isAscending ? aDate - bDate : bDate - aDate;
        }
        
        // String comparison
        return isAscending ? 
            aVal.localeCompare(bVal) : 
            bVal.localeCompare(aVal);
    });
    
    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

function addTableSearch(table) {
    const searchContainer = document.createElement('div');
    searchContainer.className = 'table-search mb-3';
    searchContainer.innerHTML = `
        <input type="text" class="form-control" placeholder="Search..." />
    `;
    
    table.parentNode.insertBefore(searchContainer, table);
    
    const searchInput = searchContainer.querySelector('input');
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

function initializeFileUploads() {
    const fileUploadAreas = document.querySelectorAll('.file-upload-area');
    
    fileUploadAreas.forEach(area => {
        const input = area.querySelector('input[type="file"]');
        
        // Drag and drop functionality
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        area.addEventListener('dragleave', function() {
            this.classList.remove('dragover');
        });
        
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            handleFileUpload(files, input);
        });
        
        // Click to upload
        area.addEventListener('click', function() {
            input.click();
        });
        
        input.addEventListener('change', function() {
            handleFileUpload(this.files, this);
        });
    });
}

function handleFileUpload(files, input) {
    const maxSize = 1024 * 1024 * 1024; // 1GB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
    
    Array.from(files).forEach(file => {
        // Validate file size
        if (file.size > maxSize) {
            showAlert(`File ${file.name} is too large. Maximum size is 1GB.`, 'danger');
            return;
        }
        
        // Validate file type
        if (!allowedTypes.includes(file.type)) {
            showAlert(`File ${file.name} is not a supported format.`, 'danger');
            return;
        }
        
        // Create preview
        createFilePreview(file, input.closest('.file-upload-area'));
    });
}

function createFilePreview(file, container) {
    const preview = document.createElement('div');
    preview.className = 'file-preview';
    
    if (file.type.startsWith('image/')) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.style.maxWidth = '200px';
        img.style.maxHeight = '200px';
        preview.appendChild(img);
    } else if (file.type.startsWith('video/')) {
        const video = document.createElement('video');
        video.src = URL.createObjectURL(file);
        video.controls = true;
        video.style.maxWidth = '200px';
        video.style.maxHeight = '200px';
        preview.appendChild(video);
    }
    
    const fileName = document.createElement('p');
    fileName.textContent = file.name;
    preview.appendChild(fileName);
    
    const removeBtn = document.createElement('button');
    removeBtn.textContent = 'Remove';
    removeBtn.className = 'btn btn-sm btn-danger';
    removeBtn.addEventListener('click', () => preview.remove());
    preview.appendChild(removeBtn);
    
    container.appendChild(preview);
}

function initializeCharts() {
    // Dashboard analytics charts
    if (typeof Chart !== 'undefined') {
        initializeRegistrationChart();
        initializeTrafficChart();
    }
}

function initializeRegistrationChart() {
    const ctx = document.getElementById('registrationChart');
    if (ctx) {
        fetch('analytics.php?action=registration_data')
            .then(response => response.json())
            .then(data => {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Registrations',
                            data: data.values,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            });
    }
}

function initializeTrafficChart() {
    const ctx = document.getElementById('trafficChart');
    if (ctx) {
        fetch('analytics.php?action=traffic_data')
            .then(response => response.json())
            .then(data => {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.values,
                            backgroundColor: [
                                '#3498db',
                                '#2ecc71',
                                '#f39c12',
                                '#e74c3c',
                                '#9b59b6'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            });
    }
}

function initializeFormValidation() {
    const forms = document.querySelectorAll('.admin-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateInput(input));
            input.addEventListener('input', () => clearInputError(input));
        });
    });
}

function validateForm(form) {
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateInput(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateInput(input) {
    const value = input.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Clear previous errors
    clearInputError(input);
    
    // Required validation
    if (input.hasAttribute('required') && !value) {
        errorMessage = 'This field is required';
        isValid = false;
    }
    
    // Email validation
    if (input.type === 'email' && value && !isValidEmail(value)) {
        errorMessage = 'Please enter a valid email address';
        isValid = false;
    }
    
    // URL validation
    if (input.type === 'url' && value && !isValidURL(value)) {
        errorMessage = 'Please enter a valid URL';
        isValid = false;
    }
    
    // Number validation
    if (input.type === 'number' && value) {
        const min = input.getAttribute('min');
        const max = input.getAttribute('max');
        
        if (min && parseFloat(value) < parseFloat(min)) {
            errorMessage = `Value must be at least ${min}`;
            isValid = false;
        }
        
        if (max && parseFloat(value) > parseFloat(max)) {
            errorMessage = `Value must be at most ${max}`;
            isValid = false;
        }
    }
    
    if (!isValid) {
        showInputError(input, errorMessage);
    }
    
    return isValid;
}

function showInputError(input, message) {
    input.classList.add('is-invalid');
    
    let errorDiv = input.parentNode.querySelector('.invalid-feedback');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        input.parentNode.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

function clearInputError(input) {
    input.classList.remove('is-invalid');
    
    const errorDiv = input.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidURL(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

// Export functions
function exportData(type, filters = {}) {
    const params = new URLSearchParams(filters);
    params.append('export', type);
    
    window.location.href = `export.php?${params.toString()}`;
}

function refreshDashboardStats() {
    fetch('dashboard.php?action=stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatCards(data.stats);
            }
        })
        .catch(error => {
            console.error('Error refreshing stats:', error);
        });
}

function updateStatCards(stats) {
    const statCards = document.querySelectorAll('.stat-card');
    
    statCards.forEach(card => {
        const statType = card.dataset.stat;
        if (stats[statType] !== undefined) {
            const valueElement = card.querySelector('.stat-card-value');
            if (valueElement) {
                valueElement.textContent = stats[statType];
            }
        }
    });
}

// Alert system for admin panel
function showAlert(message, type = 'info', duration = 5000) {
    const alertContainer = document.getElementById('alertContainer') || createAlertContainer();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        <i class="fas fa-${getAlertIcon(type)}"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto-remove after duration
    if (duration > 0) {
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, duration);
    }
}

function getAlertIcon(type) {
    const icons = {
        success: 'check-circle',
        danger: 'exclamation-triangle',
        warning: 'exclamation-circle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alertContainer';
    container.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
    `;
    document.body.appendChild(container);
    return container;
}

// Confirmation dialogs
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Bulk actions
function initializeBulkActions() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const bulkActionSelect = document.getElementById('bulkAction');
    const bulkActionBtn = document.getElementById('bulkActionBtn');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            itemCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActionButton();
        });
    }
    
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionButton);
    });
    
    if (bulkActionBtn) {
        bulkActionBtn.addEventListener('click', function() {
            const selectedItems = Array.from(itemCheckboxes)
                .filter(checkbox => checkbox.checked)
                .map(checkbox => checkbox.value);
            
            const action = bulkActionSelect.value;
            
            if (selectedItems.length === 0) {
                showAlert('Please select at least one item', 'warning');
                return;
            }
            
            if (!action) {
                showAlert('Please select an action', 'warning');
                return;
            }
            
            executeBulkAction(action, selectedItems);
        });
    }
}

function updateBulkActionButton() {
    const selectedCount = document.querySelectorAll('.item-checkbox:checked').length;
    const bulkActionBtn = document.getElementById('bulkActionBtn');
    
    if (bulkActionBtn) {
        bulkActionBtn.disabled = selectedCount === 0;
        bulkActionBtn.textContent = `Apply to ${selectedCount} item(s)`;
    }
}

function executeBulkAction(action, items) {
    const actionMap = {
        delete: 'Are you sure you want to delete the selected items?',
        hide: 'Are you sure you want to hide the selected items?',
        show: 'Are you sure you want to show the selected items?'
    };
    
    const confirmMessage = actionMap[action] || 'Are you sure you want to perform this action?';
    
    if (confirm(confirmMessage)) {
        const formData = new FormData();
        formData.append('bulk_action', action);
        formData.append('items', JSON.stringify(items));
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Bulk action error:', error);
            showAlert('An error occurred while performing the action', 'danger');
        });
    }
}

// Initialize bulk actions when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeBulkActions();
});

// Export admin functions for global use
window.AdminPanel = {
    showAlert,
    confirmDelete,
    confirmAction,
    exportData,
    refreshDashboardStats
};
