/**
 * GIBSYSNET API Helper
 * File: assets/js/api.js
 * Version: 2.1.0
 */

// Konfigurasi Global
const CONFIG = {
    API_BASE_URL: window.location.hostname === 'localhost' 
        ? 'http://localhost/gibsysnet/backend/index.php'
        : '../backend/index.php',
    APP_NAME: 'GIBSYSNET',
    VERSION: '2.1.0',
    TIMEOUT: 30000 // 30 detik
};

// ==================== API HELPER CLASS ====================
class API {
    constructor() {
        this.baseUrl = CONFIG.API_BASE_URL;
        this.timeout = CONFIG.TIMEOUT;
    }

    // Get authentication token
    getToken() {
        return localStorage.getItem('gibsysnet_token');
    }

    // Get user data
    getUser() {
        const userData = localStorage.getItem('gibsysnet_user');
        return userData ? JSON.parse(userData) : null;
    }

    // Set user data
    setUser(userData) {
        localStorage.setItem('gibsysnet_user', JSON.stringify(userData));
    }

    // Set token
    setToken(token) {
        localStorage.setItem('gibsysnet_token', token);
    }

    // Clear authentication data
    clearAuth() {
        localStorage.removeItem('gibsysnet_user');
        localStorage.removeItem('gibsysnet_token');
        localStorage.removeItem('gibsysnet_remember');
    }

    // Main request method
    async request(endpoint, method = 'GET', data = null, customHeaders = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);

        const url = `${this.baseUrl}?url=${endpoint}`;
        const token = this.getToken();
        
        // Default headers
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...customHeaders
        };
        
        // Add authorization header if token exists
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        // Request options
        const options = {
            method: method.toUpperCase(),
            headers: headers,
            signal: controller.signal,
            cache: 'no-cache',
            credentials: 'same-origin'
        };
        
        // Add body for POST, PUT, DELETE requests
        if (data && ['POST', 'PUT', 'DELETE'].includes(method.toUpperCase())) {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, options);
            clearTimeout(timeoutId);
            
            // Handle HTTP errors
            if (!response.ok) {
                return this.handleHttpError(response, endpoint);
            }
            
            // Parse response
            const result = await response.json();
            
            // Handle API errors (success = false)
            if (!result.success) {
                throw new Error(result.message || 'API request failed');
            }
            
            return result;
            
        } catch (error) {
            clearTimeout(timeoutId);
            return this.handleRequestError(error, endpoint);
        }
    }

    // Handle HTTP errors
    async handleHttpError(response, endpoint) {
        let errorMessage = `HTTP ${response.status}`;
        let errorData = null;
        
        try {
            errorData = await response.text();
            const parsed = JSON.parse(errorData);
            errorMessage = parsed.message || errorMessage;
        } catch (e) {
            errorMessage = errorData || errorMessage;
        }
        
        // Handle specific status codes
        switch (response.status) {
            case 401: // Unauthorized
                this.clearAuth();
                if (!window.location.pathname.includes('login.html')) {
                    window.location.href = 'login.html';
                }
                break;
                
            case 403: // Forbidden
                Utils.showNotification('Akses ditolak. Anda tidak memiliki izin.', 'error');
                break;
                
            case 404: // Not Found
                console.error(`Endpoint not found: ${endpoint}`);
                break;
                
            case 500: // Server Error
                Utils.showNotification('Terjadi kesalahan server. Silakan coba lagi.', 'error');
                break;
        }
        
        throw new Error(`${errorMessage} (${endpoint})`);
    }

    // Handle request errors (network, timeout, etc.)
    handleRequestError(error, endpoint) {
        console.error(`API Request Error (${endpoint}):`, error);
        
        let userMessage = 'Terjadi kesalahan jaringan';
        
        if (error.name === 'AbortError') {
            userMessage = 'Permintaan timeout. Silakan coba lagi.';
        } else if (error.name === 'TypeError' && error.message.includes('fetch')) {
            userMessage = 'Tidak dapat terhubung ke server. Periksa koneksi internet.';
        } else {
            userMessage = error.message || userMessage;
        }
        
        Utils.showNotification(userMessage, 'error');
        throw error;
    }

    // ==================== AUTH METHODS ====================
    async login(username, password) {
        try {
            const result = await this.request('auth/login', 'POST', {
                username: username,
                password: password
            });
            
            if (result.success && result.data) {
                // Save user data and token
                this.setUser(result.data);
                this.setToken(result.data.token);
                
                // Log successful login
                console.log(`Login successful: ${result.data.full_name} (${result.data.user_level})`);
                
                return result;
            }
            
            throw new Error(result.message || 'Login failed');
            
        } catch (error) {
            console.error('Login error:', error);
            throw error;
        }
    }

    async logout() {
        const user = this.getUser();
        if (user) {
            try {
                await this.request('auth/logout', 'POST', { user_id: user.id });
            } catch (error) {
                console.warn('Logout API error (ignored):', error);
            }
        }
        this.clearAuth();
        return true;
    }

    async changePassword(currentPassword, newPassword) {
        const user = this.getUser();
        if (!user) throw new Error('User not logged in');
        
        return await this.request('auth/change-password', 'POST', {
            user_id: user.id,
            current_password: currentPassword,
            new_password: newPassword
        });
    }

    async checkSession() {
        return await this.request('auth/check', 'GET');
    }

    // ==================== DASHBOARD METHODS ====================
    async getDashboardData() {
        const user = this.getUser();
        if (!user) throw new Error('User not logged in');
        
        return await this.request('dashboard/data', 'POST', {
            user_id: user.id,
            user_level: user.user_level
        });
    }

    async getDashboardStats() {
        return await this.request('dashboard/stats', 'GET');
    }

    // ==================== CLIENT METHODS ====================
    async getClients(filters = {}) {
        const queryString = new URLSearchParams(filters).toString();
        const endpoint = queryString ? `clients?${queryString}` : 'clients';
        return await this.request(endpoint, 'GET');
    }

    async getClientById(id) {
        return await this.request(`clients/${id}`, 'GET');
    }

    async searchClients(query, limit = 20) {
        return await this.request(`clients/search?q=${encodeURIComponent(query)}&limit=${limit}`, 'GET');
    }

    async createClient(clientData) {
        return await this.request('clients/create', 'POST', clientData);
    }

    async updateClient(id, clientData) {
        return await this.request(`clients/${id}`, 'PUT', clientData);
    }

    async deleteClient(id) {
        return await this.request(`clients/${id}`, 'DELETE');
    }

    // ==================== POLICY METHODS ====================
    async getPolicies(filters = {}) {
        const queryString = new URLSearchParams(filters).toString();
        const endpoint = queryString ? `policies?${queryString}` : 'policies';
        return await this.request(endpoint, 'GET');
    }

    async getPolicyById(id) {
        return await this.request(`policies/${id}`, 'GET');
    }

    async getExpiringPolicies(days = 30) {
        return await this.request(`policies/expiring?days=${days}`, 'GET');
    }

    async createPolicy(policyData) {
        return await this.request('policies/create', 'POST', policyData);
    }

    async updatePolicy(id, policyData) {
        return await this.request(`policies/${id}`, 'PUT', policyData);
    }

    async deletePolicy(id) {
        return await this.request(`policies/${id}`, 'DELETE');
    }

    // ==================== QUOTATION METHODS ====================
    async getQuotations(filters = {}) {
        const queryString = new URLSearchParams(filters).toString();
        const endpoint = queryString ? `quotations?${queryString}` : 'quotations';
        return await this.request(endpoint, 'GET');
    }

    async getQuotationById(id) {
        return await this.request(`quotations/${id}`, 'GET');
    }

    async getPendingQuotations() {
        return await this.request('quotations/pending', 'GET');
    }

    async createQuotation(quotationData) {
        return await this.request('quotations/create', 'POST', quotationData);
    }

    async updateQuotation(id, quotationData) {
        return await this.request(`quotations/${id}`, 'PUT', quotationData);
    }

    async deleteQuotation(id) {
        return await this.request(`quotations/${id}`, 'DELETE');
    }

    // ==================== CLAIM METHODS ====================
    async getClaims(filters = {}) {
        const queryString = new URLSearchParams(filters).toString();
        const endpoint = queryString ? `claims?${queryString}` : 'claims';
        return await this.request(endpoint, 'GET');
    }

    async getClaimById(id) {
        return await this.request(`claims/${id}`, 'GET');
    }

    async getPendingClaims() {
        return await this.request('claims/pending', 'GET');
    }

    async createClaim(claimData) {
        return await this.request('claims/create', 'POST', claimData);
    }

    async updateClaim(id, claimData) {
        return await this.request(`claims/${id}`, 'PUT', claimData);
    }

    async deleteClaim(id) {
        return await this.request(`claims/${id}`, 'DELETE');
    }

    // ==================== COMMISSION METHODS ====================
    async getCommissions(filters = {}) {
        const queryString = new URLSearchParams(filters).toString();
        const endpoint = queryString ? `commissions?${queryString}` : 'commissions';
        return await this.request(endpoint, 'GET');
    }

    async getCommissionById(id) {
        return await this.request(`commissions/${id}`, 'GET');
    }

    async getCommissionsByBroker(brokerId) {
        return await this.request(`commissions/broker/${brokerId}`, 'GET');
    }

    // ==================== REPORT METHODS ====================
    async getSummaryReport(startDate, endDate) {
        return await this.request(`reports/summary?start=${startDate}&end=${endDate}`, 'GET');
    }

    async getMonthlyReport(year = null, month = null) {
        const now = new Date();
        const reportYear = year || now.getFullYear();
        const reportMonth = month || now.getMonth() + 1;
        return await this.request(`reports/monthly?year=${reportYear}&month=${reportMonth}`, 'GET');
    }

    async getCommissionReport(startDate, endDate) {
        return await this.request(`reports/commission?start=${startDate}&end=${endDate}`, 'GET');
    }

    // ==================== USER METHODS ====================
    async getUsers(filters = {}) {
        const queryString = new URLSearchParams(filters).toString();
        const endpoint = queryString ? `users?${queryString}` : 'users';
        return await this.request(endpoint, 'GET');
    }

    async getUserById(id) {
        return await this.request(`users/${id}`, 'GET');
    }

    async updateUser(id, userData) {
        return await this.request(`users/${id}`, 'PUT', userData);
    }

    async deleteUser(id) {
        return await this.request(`users/${id}`, 'DELETE');
    }

    // ==================== ACTIVITY LOG METHODS ====================
    async getActivityLogs(limit = 50, userId = null) {
        let endpoint = `activity-logs?limit=${limit}`;
        if (userId) {
            endpoint += `&user_id=${userId}`;
        }
        return await this.request(endpoint, 'GET');
    }

    // ==================== SETTING METHODS ====================
    async getSettings() {
        return await this.request('settings', 'GET');
    }

    async getSetting(key) {
        const settings = await this.getSettings();
        if (settings.success && settings.data) {
            return settings.data.find(setting => setting.setting_key === key);
        }
        return null;
    }

    async updateSetting(key, value) {
        return await this.request(`settings/${key}`, 'PUT', { value: value });
    }

    // ==================== DOCUMENT METHODS ====================
    async uploadDocument(formData) {
        // For file upload, use FormData instead of JSON
        const endpoint = 'documents/upload';
        const url = `${this.baseUrl}?url=${endpoint}`;
        const token = this.getToken();
        
        const headers = {};
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        const options = {
            method: 'POST',
            headers: headers,
            body: formData
        };
        
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`Upload failed: ${response.status}`);
        }
        
        return await response.json();
    }

    // ==================== UTILITY METHODS ====================
    async healthCheck() {
        try {
            await this.request('auth/check', 'GET');
            return true;
        } catch (error) {
            return false;
        }
    }

    async getServerTime() {
        try {
            const response = await this.request('dashboard/stats', 'GET');
            return new Date(response.timestamp || Date.now());
        } catch (error) {
            return new Date();
        }
    }
}

// ==================== UTILITIES CLASS ====================
class Utils {
    // Format currency (IDR)
    static formatCurrency(amount, currency = 'IDR') {
        if (amount === null || amount === undefined || isNaN(amount)) {
            return 'Rp 0';
        }
        
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    // Format date
    static formatDate(dateString, format = 'short') {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        
        const options = {
            year: 'numeric',
            month: format === 'short' ? 'short' : 'long',
            day: 'numeric',
            timeZone: 'Asia/Jakarta'
        };
        
        return date.toLocaleDateString('id-ID', options);
    }

    // Format datetime
    static formatDateTime(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        
        return date.toLocaleString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'Asia/Jakarta'
        });
    }

    // Format time only
    static formatTime(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        
        return date.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            timeZone: 'Asia/Jakarta'
        });
    }

    // Format file size
    static formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Show notification/toast
    static showNotification(message, type = 'info', duration = 5000) {
        // Remove existing notification
        const existing = document.getElementById('app-notification');
        if (existing) existing.remove();

        // Create notification element
        const notification = document.createElement('div');
        notification.id = 'app-notification';
        notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full opacity-0 max-w-md`;
        
        // Set color based on type
        let bgColor, textColor, icon;
        switch(type.toLowerCase()) {
            case 'success':
                bgColor = 'bg-green-500';
                textColor = 'text-white';
                icon = 'fa-check-circle';
                break;
            case 'error':
                bgColor = 'bg-red-500';
                textColor = 'text-white';
                icon = 'fa-exclamation-circle';
                break;
            case 'warning':
                bgColor = 'bg-yellow-500';
                textColor = 'text-yellow-900';
                icon = 'fa-exclamation-triangle';
                break;
            case 'info':
                bgColor = 'bg-blue-500';
                textColor = 'text-white';
                icon = 'fa-info-circle';
                break;
            default:
                bgColor = 'bg-gray-500';
                textColor = 'text-white';
                icon = 'fa-info';
        }
        
        notification.className += ` ${bgColor} ${textColor}`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${icon} mr-3 text-lg"></i>
                <div class="flex-1">
                    <p class="font-medium">${message}</p>
                </div>
                <button class="ml-4 text-white hover:text-gray-200 focus:outline-none" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full', 'opacity-0');
            notification.classList.add('translate-x-0', 'opacity-100');
        }, 10);
        
        // Auto remove after duration
        setTimeout(() => {
            notification.classList.remove('translate-x-0', 'opacity-100');
            notification.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, duration);
    }

    // Validate email
    static validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Validate phone number (Indonesia)
    static validatePhone(phone) {
        const re = /^(\+62|62|0)[2-9][0-9]{7,11}$/;
        return re.test(phone.replace(/\s+/g, ''));
    }

    // Validate required fields
    static validateRequired(fields) {
        const errors = [];
        
        for (const [fieldName, value] of Object.entries(fields)) {
            if (!value || (typeof value === 'string' && value.trim() === '')) {
                errors.push(`${fieldName} harus diisi`);
            }
        }
        
        return errors;
    }

    // Debounce function
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Generate unique ID
    static generateId(prefix = '') {
        const timestamp = Date.now().toString(36);
        const random = Math.random().toString(36).substr(2, 5);
        return `${prefix}${timestamp}${random}`.toUpperCase();
    }

    // Copy text to clipboard
    static copyToClipboard(text) {
        return new Promise((resolve, reject) => {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text)
                    .then(resolve)
                    .catch(reject);
            } else {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                
                try {
                    const successful = document.execCommand('copy');
                    document.body.removeChild(textarea);
                    successful ? resolve() : reject(new Error('Copy failed'));
                } catch (err) {
                    document.body.removeChild(textarea);
                    reject(err);
                }
            }
        });
    }

    // Download file
    static downloadFile(filename, content, type = 'text/plain') {
        const blob = new Blob([content], { type: type });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Parse query parameters
    static getQueryParams() {
        const params = {};
        const queryString = window.location.search.substring(1);
        const pairs = queryString.split('&');
        
        for (const pair of pairs) {
            const [key, value] = pair.split('=');
            if (key) {
                params[decodeURIComponent(key)] = decodeURIComponent(value || '');
            }
        }
        
        return params;
    }

    // Set query parameter
    static setQueryParam(key, value) {
        const url = new URL(window.location);
        url.searchParams.set(key, value);
        window.history.pushState({}, '', url);
    }

    // Remove query parameter
    static removeQueryParam(key) {
        const url = new URL(window.location);
        url.searchParams.delete(key);
        window.history.pushState({}, '', url);
    }

    // Get today's date in YYYY-MM-DD format
    static getToday() {
        const now = new Date();
        return now.toISOString().split('T')[0];
    }

    // Add days to date
    static addDays(date, days) {
        const result = new Date(date);
        result.setDate(result.getDate() + days);
        return result;
    }

    // Calculate days between dates
    static daysBetween(date1, date2) {
        const diff = new Date(date2).getTime() - new Date(date1).getTime();
        return Math.ceil(diff / (1000 * 3600 * 24));
    }
}

// ==================== AUTH HELPER ====================
class Auth {
    // Check if user is logged in
    static isLoggedIn() {
        return localStorage.getItem('gibsysnet_token') !== null;
    }

    // Get user data
    static getUser() {
        const userData = localStorage.getItem('gibsysnet_user');
        return userData ? JSON.parse(userData) : null;
    }

    // Get user level
    static getUserLevel() {
        const user = this.getUser();
        return user ? user.user_level : null;
    }

    // Check if user has permission
    static hasPermission(permission) {
        const user = this.getUser();
        if (!user) return false;
        
        // Super admin has all permissions
        if (user.user_level === 'super_admin') return true;
        
        // Check user permissions array
        return user.permissions && user.permissions.includes(permission);
    }

    // Check if user has any of the permissions
    static hasAnyPermission(permissions) {
        return permissions.some(permission => this.hasPermission(permission));
    }

    // Check if user has all permissions
    static hasAllPermissions(permissions) {
        return permissions.every(permission => this.hasPermission(permission));
    }

    // Require login (redirect if not logged in)
    static requireLogin(redirectTo = 'login.html') {
        if (!this.isLoggedIn()) {
            localStorage.setItem('redirect_after_login', window.location.pathname);
            window.location.href = redirectTo;
            return false;
        }
        return true;
    }

    // Require permission (redirect if not authorized)
    static requirePermission(permission, redirectTo = 'dashboard-user.html') {
        if (!this.hasPermission(permission)) {
            Utils.showNotification('Anda tidak memiliki izin untuk mengakses halaman ini.', 'error');
            window.location.href = redirectTo;
            return false;
        }
        return true;
    }

    // Require user level
    static requireUserLevel(requiredLevel, redirectTo = 'dashboard-user.html') {
        const userLevel = this.getUserLevel();
        const levels = ['user', 'broker', 'manager', 'admin', 'super_admin'];
        
        const userLevelIndex = levels.indexOf(userLevel);
        const requiredLevelIndex = levels.indexOf(requiredLevel);
        
        if (userLevelIndex < requiredLevelIndex) {
            Utils.showNotification('Akses ditolak. Level user tidak mencukupi.', 'error');
            window.location.href = redirectTo;
            return false;
        }
        
        return true;
    }

    // Get redirect URL after login
    static getRedirectUrl() {
        const redirectUrl = localStorage.getItem('redirect_after_login');
        localStorage.removeItem('redirect_after_login');
        return redirectUrl || (this.getUserLevel() === 'user' ? 'dashboard-user.html' : 'dashboard-admin.html');
    }

    // Logout user
    static async logout(redirectToLogin = true) {
        const api = new API();
        await api.logout();
        
        if (redirectToLogin) {
            window.location.href = 'login.html';
        }
    }
}

// ==================== INITIALIZATION ====================
// Initialize global objects
window.GIBSYSNET = {
    API: API,
    Utils: Utils,
    Auth: Auth,
    CONFIG: CONFIG
};

// Make available globally
window.API = new API();
window.Utils = Utils;
window.Auth = Auth;

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function() {
    // Skip auth check for public pages
    const publicPages = ['login.html', 'index.html', 'register.html'];
    const currentPage = window.location.pathname.split('/').pop();
    
    if (!publicPages.includes(currentPage) && !Auth.isLoggedIn()) {
        Auth.requireLogin();
        return;
    }
    
    // Initialize common UI elements
    initCommonUI();
    
    // Update copyright year
    updateCopyrightYear();
    
    // Initialize tooltips
    initTooltips();
});

// Initialize common UI
function initCommonUI() {
    // Auto-hide notifications
    const autoHideNotifications = document.querySelectorAll('.auto-hide');
    autoHideNotifications.forEach(notification => {
        const duration = notification.dataset.duration || 5000;
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, duration);
    });
    
    // Initialize form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
    
    // Initialize confirmation dialogs
    const confirmLinks = document.querySelectorAll('a[data-confirm]');
    confirmLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Apakah Anda yakin?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

// Update copyright year
function updateCopyrightYear() {
    const yearElements = document.querySelectorAll('[data-current-year]');
    if (yearElements.length > 0) {
        const currentYear = new Date().getFullYear();
        yearElements.forEach(el => {
            el.textContent = currentYear;
        });
    }
}

// Initialize tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function(e) {
            const tooltipText = this.getAttribute('data-tooltip');
            if (tooltipText) {
                const tooltipEl = document.createElement('div');
                tooltipEl.className = 'fixed bg-gray-900 text-white text-sm px-3 py-2 rounded shadow-lg z-50 max-w-xs';
                tooltipEl.textContent = tooltipText;
                tooltipEl.style.left = `${e.pageX + 10}px`;
                tooltipEl.style.top = `${e.pageY + 10}px`;
                tooltipEl.id = 'dynamic-tooltip';
                document.body.appendChild(tooltipEl);
            }
        });
        
        tooltip.addEventListener('mouseleave', function() {
            const tooltipEl = document.getElementById('dynamic-tooltip');
            if (tooltipEl) {
                tooltipEl.remove();
            }
        });
    });
}

// Form validation helper
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('border-red-500');
            
            // Show error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'text-red-600 text-sm mt-1';
            errorDiv.textContent = 'Field ini wajib diisi';
            
            const existingError = field.parentNode.querySelector('.field-error');
            if (!existingError) {
                field.parentNode.appendChild(errorDiv);
                errorDiv.classList.add('field-error');
            }
        } else {
            field.classList.remove('border-red-500');
            
            // Remove error message
            const errorDiv = field.parentNode.querySelector('.field-error');
            if (errorDiv) {
                errorDiv.remove();
            }
        }
    });
    
    return isValid;
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        API: API,
        Utils: Utils,
        Auth: Auth,
        CONFIG: CONFIG
    };
}