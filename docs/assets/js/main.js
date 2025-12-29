/**
 * GIBSYSNET Main JavaScript
 * File: assets/js/main.js
 * Version: 2.1.0
 * 
 * Catatan: File ini sekarang hanya berisi fungsi-fungsi khusus
 * untuk halaman tertentu. Fungsi umum sudah dipindah ke api.js
 */

// Include API Helper jika belum diinclude
if (typeof API === 'undefined') {
    console.warn('API Helper not loaded. Loading from assets/js/api.js');
    const script = document.createElement('script');
    script.src = 'assets/js/api.js';
    script.onload = function() {
        console.log('API Helper loaded successfully');
    };
    document.head.appendChild(script);
}

// ==================== DASHBOARD FUNCTIONS ====================
// Load dashboard data untuk admin/user dashboard
async function loadDashboardData() {
    try {
        // Check authentication
        if (!Auth.isLoggedIn()) {
            window.location.href = 'login.html';
            return;
        }
        
        const user = Auth.getUser();
        const api = new API();
        
        // Show loading state
        showLoading(true);
        
        // Get dashboard data from API
        const result = await api.getDashboardData();
        
        if (result.success && result.data) {
            // Update UI dengan data dari API
            updateDashboardUI(result.data, user);
        } else {
            console.error('Failed to load dashboard data:', result.message);
            Utils.showNotification('Gagal memuat data dashboard', 'error');
            loadFallbackData();
        }
        
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        Utils.showNotification('Terjadi kesalahan saat memuat data', 'error');
        loadFallbackData();
    } finally {
        showLoading(false);
    }
}

// Update UI dengan data dashboard
function updateDashboardUI(data, user) {
    // Update greeting
    updateGreeting(user);
    
    // Update stats cards
    updateStatsCards(data.stats);
    
    // Update tables and lists
    updateTables(data);
    
    // Update charts jika ada
    updateCharts(data);
    
    // Update recent activities
    updateRecentActivities(data.recent_activities);
    
    // Update notifications
    updateNotifications(data.notifications);
}

// Update greeting message
function updateGreeting(user) {
    const hour = new Date().getHours();
    let greeting = 'Selamat';
    
    if (hour < 12) greeting = 'Selamat pagi';
    else if (hour < 15) greeting = 'Selamat siang';
    else if (hour < 18) greeting = 'Selamat sore';
    else greeting = 'Selamat malam';
    
    const greetingElement = document.getElementById('greetingName');
    if (greetingElement) {
        greetingElement.textContent = `${greeting}, ${user.full_name.split(' ')[0]}!`;
    }
}

// Update stats cards
function updateStatsCards(stats) {
    const elements = {
        'totalPolicies': stats?.total_policies || 0,
        'totalClients': stats?.total_clients || 0,
        'totalPremium': stats?.total_premium_ytd || 0,
        'totalCommission': stats?.total_commission_ytd || 0,
        'pendingQuotations': stats?.pending_quotations || 0,
        'pendingClaims': stats?.pending_claims || 0,
        'upcomingRenewals': stats?.upcoming_renewals || 0,
        'activeUsers': stats?.total_users || 0
    };
    
    for (const [id, value] of Object.entries(elements)) {
        const element = document.getElementById(id);
        if (element) {
            if (id.includes('Premium') || id.includes('Commission')) {
                element.textContent = Utils.formatCurrency(value);
            } else {
                element.textContent = value.toLocaleString('id-ID');
            }
        }
    }
}

// Update tables
function updateTables(data) {
    // Update policies table
    if (data.recent_policies && data.recent_policies.length > 0) {
        updateTable('policiesTable', data.recent_policies, ['policy_number', 'client_name', 'policy_type', 'premium', 'status']);
    }
    
    // Update quotations table
    if (data.pending_quotations && data.pending_quotations.length > 0) {
        updateTable('quotationsTable', data.pending_quotations, ['quote_number', 'client_name', 'quote_type', 'premium', 'status']);
    }
    
    // Update renewals table
    if (data.upcoming_renewals && data.upcoming_renewals.length > 0) {
        updateTable('renewalsTable', data.upcoming_renewals, ['policy_number', 'client_name', 'end_date', 'days_left', 'action']);
    }
}

// Generic table update function
function updateTable(tableId, data, columns) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    data.forEach(item => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 transition-colors';
        
        columns.forEach(column => {
            const cell = document.createElement('td');
            cell.className = 'px-4 py-3 whitespace-nowrap';
            
            let cellContent = '';
            
            switch(column) {
                case 'policy_number':
                case 'quote_number':
                    cellContent = `<span class="font-medium text-blue-600">${item[column] || 'N/A'}</span>`;
                    break;
                    
                case 'client_name':
                    cellContent = item.company_name || item.client_name || 'N/A';
                    break;
                    
                case 'premium':
                    cellContent = Utils.formatCurrency(item[column] || 0);
                    break;
                    
                case 'status':
                    cellContent = getStatusBadge(item[column]);
                    break;
                    
                case 'end_date':
                    cellContent = Utils.formatDate(item[column]);
                    break;
                    
                case 'days_left':
                    const days = item.days_left || item.days_remaining || 0;
                    cellContent = getDaysBadge(days);
                    break;
                    
                case 'action':
                    cellContent = `<button class="text-blue-600 hover:text-blue-800 text-sm font-medium px-3 py-1 bg-blue-50 rounded-lg" onclick="handleRenewal('${item.policy_number}')">
                        <i class="fas fa-sync-alt mr-1"></i> Renew
                    </button>`;
                    break;
                    
                default:
                    cellContent = item[column] || '';
            }
            
            cell.innerHTML = cellContent;
            row.appendChild(cell);
        });
        
        tbody.appendChild(row);
    });
}

// Get status badge HTML
function getStatusBadge(status) {
    const statusMap = {
        'active': { color: 'green', text: 'Active' },
        'pending': { color: 'yellow', text: 'Pending' },
        'expired': { color: 'red', text: 'Expired' },
        'draft': { color: 'gray', text: 'Draft' },
        'sent': { color: 'blue', text: 'Sent' },
        'accepted': { color: 'green', text: 'Accepted' },
        'rejected': { color: 'red', text: 'Rejected' },
        'filed': { color: 'yellow', text: 'Filed' },
        'under_review': { color: 'blue', text: 'Under Review' },
        'approved': { color: 'green', text: 'Approved' },
        'paid': { color: 'green', text: 'Paid' },
        'closed': { color: 'gray', text: 'Closed' }
    };
    
    const statusInfo = statusMap[status] || { color: 'gray', text: status };
    
    return `<span class="px-2 py-1 text-xs rounded-full bg-${statusInfo.color}-100 text-${statusInfo.color}-800 font-medium">
        ${statusInfo.text}
    </span>`;
}

// Get days badge HTML
function getDaysBadge(days) {
    let color = 'green';
    let text = `${days} days`;
    
    if (days <= 7) {
        color = 'red';
    } else if (days <= 30) {
        color = 'yellow';
    }
    
    return `<span class="px-2 py-1 text-xs rounded-full bg-${color}-100 text-${color}-800 font-medium">
        ${text}
    </span>`;
}

// Update charts
function updateCharts(data) {
    // Implementation depends on chart library
    // Example with Chart.js or simple HTML update
    const chartElement = document.getElementById('productionChart');
    if (chartElement) {
        // Update chart data here
        console.log('Update chart with data:', data.chart_data);
    }
}

// Update recent activities
function updateRecentActivities(activities) {
    const container = document.getElementById('recentActivities');
    if (!container || !activities) return;
    
    container.innerHTML = '';
    
    activities.forEach(activity => {
        const activityElement = document.createElement('div');
        activityElement.className = 'bg-gray-50 p-3 rounded-lg mb-2';
        activityElement.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-${getActivityIcon(activity.activity_type)} text-blue-600 text-sm"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium">${activity.description}</p>
                    <p class="text-xs text-gray-500 mt-1">${Utils.formatDateTime(activity.created_at)}</p>
                </div>
            </div>
        `;
        container.appendChild(activityElement);
    });
}

// Get activity icon
function getActivityIcon(activityType) {
    const iconMap = {
        'login': 'sign-in-alt',
        'logout': 'sign-out-alt',
        'create': 'plus-circle',
        'update': 'edit',
        'delete': 'trash-alt',
        'upload': 'upload',
        'download': 'download',
        'payment': 'credit-card',
        'default': 'circle'
    };
    
    return iconMap[activityType] || iconMap.default;
}

// Update notifications
function updateNotifications(notifications) {
    const badge = document.getElementById('notificationBadge');
    const dropdown = document.getElementById('notificationDropdown');
    
    if (!notifications || notifications.length === 0) {
        if (badge) badge.style.display = 'none';
        if (dropdown) {
            dropdown.innerHTML = `
                <div class="px-4 py-8 text-center text-gray-500">
                    <i class="fas fa-bell-slash text-2xl mb-2"></i>
                    <p>Tidak ada notifikasi</p>
                </div>
            `;
        }
        return;
    }
    
    // Update badge count
    if (badge) {
        badge.textContent = notifications.length > 9 ? '9+' : notifications.length;
        badge.style.display = 'flex';
    }
    
    // Update dropdown content
    if (dropdown) {
        dropdown.innerHTML = '';
        
        notifications.forEach(notification => {
            const item = document.createElement('a');
            item.href = '#';
            item.className = 'block px-4 py-3 hover:bg-gray-50 border-b border-gray-100';
            item.innerHTML = `
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 ${getNotificationColor(notification.type)} rounded-full flex items-center justify-center mr-3">
                            <i class="fas ${getNotificationIcon(notification.type)} text-white text-sm"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium">${notification.title}</p>
                        <p class="text-xs text-gray-500 mt-1">${notification.message}</p>
                        <p class="text-xs text-gray-400 mt-1">${Utils.formatDateTime(notification.created_at)}</p>
                    </div>
                </div>
            `;
            dropdown.appendChild(item);
        });
        
        // Add view all link
        const viewAll = document.createElement('a');
        viewAll.href = 'notifications.html';
        viewAll.className = 'block text-center px-4 py-2 text-sm text-blue-600 hover:bg-blue-50';
        viewAll.innerHTML = '<i class="fas fa-eye mr-1"></i> Lihat Semua';
        dropdown.appendChild(viewAll);
    }
}

// Get notification color
function getNotificationColor(type) {
    const colorMap = {
        'info': 'bg-blue-500',
        'warning': 'bg-yellow-500',
        'error': 'bg-red-500',
        'success': 'bg-green-500'
    };
    
    return colorMap[type] || 'bg-gray-500';
}

// Get notification icon
function getNotificationIcon(type) {
    const iconMap = {
        'info': 'info-circle',
        'warning': 'exclamation-triangle',
        'error': 'exclamation-circle',
        'success': 'check-circle'
    };
    
    return iconMap[type] || 'bell';
}

// Load fallback data jika API gagal
function loadFallbackData() {
    console.log('Loading fallback data...');
    // Use existing HTML data or local storage
    const user = Auth.getUser();
    if (user) {
        updateGreeting(user);
    }
}

// Show/hide loading state
function showLoading(show) {
    const loadingElement = document.getElementById('loadingIndicator');
    if (loadingElement) {
        loadingElement.style.display = show ? 'block' : 'none';
    }
    
    // Disable/enable interactive elements
    const interactiveElements = document.querySelectorAll('button, a, input, select');
    interactiveElements.forEach(element => {
        if (show) {
            element.setAttribute('disabled', 'true');
            element.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            element.removeAttribute('disabled');
            element.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    });
}

// ==================== FORM HANDLING ====================
// Initialize form handlers
function initFormHandlers() {
    // Auto-save forms
    const autoSaveForms = document.querySelectorAll('form[data-autosave]');
    autoSaveForms.forEach(form => {
        const saveDelay = form.dataset.saveDelay || 2000;
        const saveFunction = Utils.debounce(async () => {
            await saveForm(form);
        }, saveDelay);
        
        form.addEventListener('input', saveFunction);
    });
    
    // File upload handlers
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            handleFileUpload(this);
        });
    });
}

// Save form data
async function saveForm(form) {
    const formId = form.id || 'unsaved-form';
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    try {
        // Save to localStorage as draft
        localStorage.setItem(`draft_${formId}`, JSON.stringify(data));
        
        // Show saved indicator
        const saveIndicator = document.getElementById('saveIndicator');
        if (saveIndicator) {
            saveIndicator.textContent = 'Disimpan';
            saveIndicator.classList.remove('invisible', 'opacity-0');
            setTimeout(() => {
                saveIndicator.classList.add('invisible', 'opacity-0');
            }, 2000);
        }
        
    } catch (error) {
        console.error('Error saving form:', error);
    }
}

// Handle file upload
async function handleFileUpload(input) {
    const files = input.files;
    if (!files || files.length === 0) return;
    
    const previewContainer = input.parentNode.querySelector('.file-preview');
    if (previewContainer) {
        previewContainer.innerHTML = '';
        
        for (const file of files) {
            const preview = document.createElement('div');
            preview.className = 'flex items-center p-2 bg-gray-50 rounded mb-2';
            preview.innerHTML = `
                <i class="fas fa-file text-gray-400 mr-2"></i>
                <span class="text-sm flex-1 truncate">${file.name}</span>
                <span class="text-xs text-gray-500">${Utils.formatFileSize(file.size)}</span>
            `;
            previewContainer.appendChild(preview);
        }
    }
}

// ==================== SEARCH FUNCTIONALITY ====================
// Initialize search
function initSearch() {
    const searchInputs = document.querySelectorAll('input[data-search]');
    searchInputs.forEach(input => {
        const searchDelay = input.dataset.delay || 500;
        const searchFunction = Utils.debounce(async () => {
            await performSearch(input.value, input.dataset.search);
        }, searchDelay);
        
        input.addEventListener('input', searchFunction);
    });
}

// Perform search
async function performSearch(query, searchType) {
    if (!query || query.length < 2) {
        clearSearchResults();
        return;
    }
    
    try {
        const api = new API();
        let results;
        
        switch(searchType) {
            case 'clients':
                results = await api.searchClients(query);
                break;
            case 'policies':
                results = await api.getPolicies({ search: query });
                break;
            case 'users':
                results = await api.getUsers({ search: query });
                break;
            default:
                console.warn('Unknown search type:', searchType);
                return;
        }
        
        if (results.success) {
            displaySearchResults(results.data, searchType);
        }
        
    } catch (error) {
        console.error('Search error:', error);
    }
}

// Display search results
function displaySearchResults(results, type) {
    const resultsContainer = document.getElementById('searchResults');
    if (!resultsContainer) return;
    
    resultsContainer.innerHTML = '';
    resultsContainer.classList.remove('hidden');
    
    if (!results || results.length === 0) {
        resultsContainer.innerHTML = '<div class="p-4 text-gray-500">Tidak ada hasil ditemukan</div>';
        return;
    }
    
    results.forEach(item => {
        const resultItem = document.createElement('a');
        resultItem.href = getItemLink(item, type);
        resultItem.className = 'block px-4 py-3 hover:bg-gray-50 border-b border-gray-100';
        
        let content = '';
        switch(type) {
            case 'clients':
                content = `
                    <div class="font-medium">${item.company_name || item.contact_person}</div>
                    <div class="text-sm text-gray-600">${item.client_code} • ${item.email}</div>
                `;
                break;
            case 'policies':
                content = `
                    <div class="font-medium">${item.policy_number}</div>
                    <div class="text-sm text-gray-600">${item.policy_type} • ${Utils.formatCurrency(item.premium)}</div>
                `;
                break;
            case 'users':
                content = `
                    <div class="font-medium">${item.full_name}</div>
                    <div class="text-sm text-gray-600">${item.user_id} • ${item.user_level}</div>
                `;
                break;
        }
        
        resultItem.innerHTML = content;
        resultsContainer.appendChild(resultItem);
    });
}

// Get item link
function getItemLink(item, type) {
    switch(type) {
        case 'clients': return `clients.html?id=${item.id}`;
        case 'policies': return `policies.html?id=${item.id}`;
        case 'users': return `users.html?id=${item.id}`;
        default: return '#';
    }
}

// Clear search results
function clearSearchResults() {
    const resultsContainer = document.getElementById('searchResults');
    if (resultsContainer) {
        resultsContainer.innerHTML = '';
        resultsContainer.classList.add('hidden');
    }
}

// ==================== EVENT HANDLERS ====================
// Handle policy renewal
async function handleRenewal(policyNumber) {
    if (!confirm(`Renew policy ${policyNumber}?`)) return;
    
    try {
        const api = new API();
        // Call renewal API here
        Utils.showNotification(`Policy ${policyNumber} renewed successfully`, 'success');
        
        // Refresh data
        setTimeout(() => {
            loadDashboardData();
        }, 1000);
        
    } catch (error) {
        console.error('Renewal error:', error);
        Utils.showNotification('Failed to renew policy', 'error');
    }
}

// Handle logout
async function handleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        await Auth.logout(true);
    }
}

// Handle print
function handlePrint() {
    window.print();
}

// Handle export
function handleExport(format = 'excel') {
    const table = document.querySelector('table');
    if (!table) {
        Utils.showNotification('No table data to export', 'warning');
        return;
    }
    
    let content = '';
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => cell.textContent.trim()).join('\t');
        content += rowData + '\n';
    });
    
    const filename = `export_${new Date().toISOString().split('T')[0]}.${format === 'excel' ? 'xls' : 'csv'}`;
    Utils.downloadFile(filename, content, format === 'excel' ? 'application/vnd.ms-excel' : 'text/csv');
    
    Utils.showNotification(`Data exported as ${format.toUpperCase()}`, 'success');
}

// ==================== PAGE-SPECIFIC INITIALIZATION ====================
// Initialize dashboard page
function initDashboardPage() {
    if (document.querySelector('.dashboard-page')) {
        loadDashboardData();
        
        // Auto-refresh every 5 minutes
        setInterval(() => {
            loadDashboardData();
        }, 5 * 60 * 1000);
    }
}

// Initialize data table pages
function initDataTablePages() {
    const tables = document.querySelectorAll('table[data-load]');
    tables.forEach(table => {
        const dataType = table.dataset.load;
        loadTableData(table, dataType);
    });
}

// Load table data
async function loadTableData(table, dataType) {
    try {
        const api = new API();
        let result;
        
        switch(dataType) {
            case 'clients':
                result = await api.getClients();
                break;
            case 'policies':
                result = await api.getPolicies();
                break;
            case 'users':
                result = await api.getUsers();
                break;
            default:
                return;
        }
        
        if (result.success && result.data) {
            renderTableData(table, result.data);
        }
        
    } catch (error) {
        console.error(`Error loading ${dataType}:`, error);
        Utils.showNotification(`Gagal memuat data ${dataType}`, 'error');
    }
}

// Render table data
function renderTableData(table, data) {
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    data.forEach(item => {
        const row = document.createElement('tr');
        // Add row data based on item type
        tbody.appendChild(row);
    });
}

// ==================== MAIN INITIALIZATION ====================
// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize common UI (from api.js)
    if (typeof initCommonUI === 'function') {
        initCommonUI();
    }
    
    // Initialize page-specific functions
    initDashboardPage();
    initFormHandlers();
    initSearch();
    initDataTablePages();
    
    // Add keyboard shortcuts
    initKeyboardShortcuts();
    
    // Handle offline/online status
    window.addEventListener('online', handleOnlineStatus);
    window.addEventListener('offline', handleOfflineStatus);
    
    // Initial status check
    updateOnlineStatus();
});

// Initialize keyboard shortcuts
function initKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const activeForm = document.querySelector('form:focus-within');
            if (activeForm) {
                activeForm.dispatchEvent(new Event('submit'));
            }
        }
        
        // Ctrl/Cmd + F to search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            const searchInput = document.querySelector('input[data-search]');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Escape to close modals
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (!modal.classList.contains('hidden')) {
                    modal.classList.add('hidden');
                }
            });
        }
    });
}

// Handle online status
function handleOnlineStatus() {
    updateOnlineStatus();
    Utils.showNotification('Koneksi internet kembali normal', 'success');
    
    // Sync any pending data
    syncPendingData();
}

// Handle offline status
function handleOfflineStatus() {
    updateOnlineStatus();
    Utils.showNotification('Anda sedang offline. Beberapa fitur mungkin tidak tersedia.', 'warning');
}

// Update online status indicator
function updateOnlineStatus() {
    const indicator = document.getElementById('onlineStatus');
    if (indicator) {
        if (navigator.onLine) {
            indicator.innerHTML = '<i class="fas fa-wifi text-green-500"></i> Online';
            indicator.classList.remove('text-red-500');
            indicator.classList.add('text-green-500');
        } else {
            indicator.innerHTML = '<i class="fas fa-wifi-slash text-red-500"></i> Offline';
            indicator.classList.remove('text-green-500');
            indicator.classList.add('text-red-500');
        }
    }
}

// Sync pending data when back online
async function syncPendingData() {
    // Implement offline data sync here
    console.log('Syncing pending data...');
}

// Error handler untuk unhandled errors
window.addEventListener('error', function(e) {
    console.error('Unhandled error:', e.error);
    Utils.showNotification('Terjadi kesalahan tidak terduga', 'error');
});

// Unhandled promise rejection handler
window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
    Utils.showNotification('Terjadi kesalahan sistem', 'error');
});