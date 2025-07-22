/**
 * AskProAI API Client
 * Handles all API communication with proper authentication and error handling
 */

class AskProAPIClient {
    constructor(options = {}) {
        this.baseURL = options.baseURL || '/business/api';
        this.sessionToken = localStorage.getItem('portal_session_token');
        this.csrfToken = this.getCsrfToken();
        this.debug = options.debug || false;
        this.retryAttempts = options.retryAttempts || 3;
        this.retryDelay = options.retryDelay || 1000;
        
        // Event callbacks
        this.onAuthError = options.onAuthError || null;
        this.onNetworkError = options.onNetworkError || null;
        
        // Request queue for offline support
        this.requestQueue = [];
        this.isOnline = navigator.onLine;
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Initialize interceptors
        this.setupInterceptors();
    }

    /**
     * Get CSRF token from meta tag or cookie
     */
    getCsrfToken() {
        // Try meta tag first
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            return metaTag.content;
        }
        
        // Try cookie
        const cookie = document.cookie.split(';').find(c => c.trim().startsWith('XSRF-TOKEN='));
        if (cookie) {
            return decodeURIComponent(cookie.split('=')[1]);
        }
        
        return null;
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Online/offline detection
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.processRequestQueue();
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
        });
    }

    /**
     * Setup request/response interceptors
     */
    setupInterceptors() {
        // Store original fetch
        this._originalFetch = window.fetch;
        
        // Override fetch
        window.fetch = async (url, options = {}) => {
            // Only intercept our API calls
            if (!url.startsWith(this.baseURL)) {
                return this._originalFetch(url, options);
            }
            
            // Add default headers
            options.headers = {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...options.headers
            };
            
            // Add authentication headers
            if (this.sessionToken) {
                options.headers['X-Session-Token'] = this.sessionToken;
            }
            
            if (this.csrfToken) {
                options.headers['X-CSRF-TOKEN'] = this.csrfToken;
            }
            
            // Add credentials
            options.credentials = 'include';
            
            // Log request if debug
            if (this.debug) {
                console.log('[API Request]', url, options);
            }
            
            try {
                const response = await this._originalFetch(url, options);
                
                // Log response if debug
                if (this.debug) {
                    console.log('[API Response]', response.status, response);
                }
                
                // Handle auth errors
                if (response.status === 401) {
                    this.handleAuthError();
                    throw new Error('Authentication required');
                }
                
                // Update CSRF token if present in response
                const newCsrfToken = response.headers.get('X-CSRF-TOKEN');
                if (newCsrfToken) {
                    this.csrfToken = newCsrfToken;
                }
                
                return response;
                
            } catch (error) {
                // Network error - queue request if offline
                if (!this.isOnline) {
                    this.queueRequest(url, options);
                }
                
                throw error;
            }
        };
    }

    /**
     * Make API request with retry logic
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        let lastError;
        
        // Always refresh CSRF token before request
        this.csrfToken = this.getCsrfToken();
        
        // Ensure headers are set
        options.headers = options.headers || {};
        options.headers['X-Requested-With'] = 'XMLHttpRequest';
        
        if (this.sessionToken) {
            options.headers['X-Session-Token'] = this.sessionToken;
        }
        
        if (this.csrfToken) {
            options.headers['X-CSRF-TOKEN'] = this.csrfToken;
        }
        
        options.credentials = 'include';
        
        for (let i = 0; i < this.retryAttempts; i++) {
            try {
                const response = await fetch(url, options);
                
                if (!response.ok) {
                    const error = await response.json().catch(() => ({}));
                    throw new APIError(error.message || 'Request failed', response.status, error);
                }
                
                const data = await response.json();
                return data;
                
            } catch (error) {
                lastError = error;
                
                // Don't retry auth errors
                if (error.status === 401) {
                    throw error;
                }
                
                // Wait before retry
                if (i < this.retryAttempts - 1) {
                    await this.delay(this.retryDelay * (i + 1));
                }
            }
        }
        
        throw lastError;
    }

    /**
     * GET request
     */
    async get(endpoint, params = {}) {
        const url = new URL(`${this.baseURL}${endpoint}`, window.location.origin);
        Object.keys(params).forEach(key => url.searchParams.append(key, params[key]));
        
        return this.request(url.pathname + url.search, {
            method: 'GET'
        });
    }

    /**
     * POST request
     */
    async post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    /**
     * PUT request
     */
    async put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    /**
     * DELETE request
     */
    async delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        });
    }

    /**
     * Authentication methods
     */
    async login(email, password, remember = false) {
        try {
            const response = await this.post('/auth/login', {
                email,
                password,
                remember
            });
            
            if (response.success) {
                this.sessionToken = response.data.session_token;
                this.csrfToken = response.data.csrf_token;
                localStorage.setItem('portal_session_token', this.sessionToken);
                
                // Store user data
                localStorage.setItem('portal_user', JSON.stringify(response.data.user));
            }
            
            return response;
            
        } catch (error) {
            throw error;
        }
    }

    async logout() {
        try {
            await this.post('/auth/logout');
        } finally {
            this.sessionToken = null;
            this.csrfToken = null;
            localStorage.removeItem('portal_session_token');
            localStorage.removeItem('portal_user');
            window.location.href = '/business/login';
        }
    }

    async checkAuth() {
        return this.get('/auth/check');
    }

    async refreshSession() {
        const response = await this.post('/auth/refresh');
        if (response.success) {
            this.csrfToken = response.csrf_token;
            localStorage.setItem('portal_user', JSON.stringify(response.user));
        }
        return response;
    }

    /**
     * Handle authentication errors
     */
    handleAuthError() {
        this.sessionToken = null;
        localStorage.removeItem('portal_session_token');
        localStorage.removeItem('portal_user');
        
        if (this.onAuthError) {
            this.onAuthError();
        } else {
            window.location.href = '/business/login';
        }
    }

    /**
     * Queue request for offline processing
     */
    queueRequest(url, options) {
        this.requestQueue.push({
            url,
            options,
            timestamp: Date.now()
        });
        
        // Store in localStorage for persistence
        localStorage.setItem('api_request_queue', JSON.stringify(this.requestQueue));
    }

    /**
     * Process queued requests when back online
     */
    async processRequestQueue() {
        const queue = [...this.requestQueue];
        this.requestQueue = [];
        
        for (const request of queue) {
            try {
                await this._originalFetch(request.url, request.options);
            } catch (error) {
                console.error('Failed to process queued request:', error);
            }
        }
        
        localStorage.removeItem('api_request_queue');
    }

    /**
     * Utility: delay function
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * API Endpoints
     */
    
    // Dashboard
    getDashboard(filters = {}) {
        return this.get('/dashboard', filters);
    }

    // Calls
    getCalls(params = {}) {
        return this.get('/calls', params);
    }

    getCall(id) {
        return this.get(`/calls/${id}`);
    }

    updateCallStatus(id, status) {
        return this.post(`/calls/${id}/status`, { status });
    }

    addCallNote(id, note) {
        return this.post(`/calls/${id}/notes`, { note });
    }

    exportCalls(ids = [], format = 'csv') {
        return this.post('/calls/export', { ids, format });
    }

    // Appointments
    getAppointments(params = {}) {
        return this.get('/appointments', params);
    }

    getAppointment(id) {
        return this.get(`/appointments/${id}`);
    }

    createAppointment(data) {
        return this.post('/appointments', data);
    }

    updateAppointment(id, data) {
        return this.put(`/appointments/${id}`, data);
    }

    updateAppointmentStatus(id, status) {
        return this.post(`/appointments/${id}/status`, { status });
    }

    deleteAppointment(id) {
        return this.delete(`/appointments/${id}`);
    }

    // Customers
    getCustomers(params = {}) {
        return this.get('/customers', params);
    }

    getCustomer(id) {
        return this.get(`/customers/${id}`);
    }

    createCustomer(data) {
        return this.post('/customers', data);
    }

    updateCustomer(id, data) {
        return this.put(`/customers/${id}`, data);
    }

    // Billing
    getBilling() {
        return this.get('/billing');
    }

    getTransactions(params = {}) {
        return this.get('/billing/transactions', params);
    }

    getUsage() {
        return this.get('/billing/usage');
    }

    topup(amount) {
        return this.post('/billing/topup', { amount });
    }

    getAutoTopupSettings() {
        return this.get('/billing/auto-topup');
    }

    updateAutoTopupSettings(settings) {
        return this.put('/billing/auto-topup', settings);
    }

    // Team
    getTeam(params = {}) {
        return this.get('/team', params);
    }

    inviteTeamMember(data) {
        return this.post('/team/invite', data);
    }

    updateTeamMember(id, data) {
        return this.put(`/team/${id}`, data);
    }

    // Settings
    getProfile() {
        return this.get('/settings/profile');
    }

    updateProfile(data) {
        return this.put('/settings/profile', data);
    }

    updatePassword(currentPassword, newPassword) {
        return this.put('/settings/password', {
            current_password: currentPassword,
            new_password: newPassword,
            new_password_confirmation: newPassword
        });
    }

    getNotificationSettings() {
        return this.get('/settings/notifications');
    }

    updateNotificationSettings(settings) {
        return this.put('/settings/notifications', settings);
    }

    // Notifications
    getNotifications(params = {}) {
        return this.get('/notifications', params);
    }

    markNotificationRead(id) {
        return this.post(`/notifications/${id}/read`);
    }

    markAllNotificationsRead() {
        return this.post('/notifications/read-all');
    }

    // Analytics
    getAnalytics(params = {}) {
        return this.get('/analytics', params);
    }

    // Goals
    getGoals() {
        return this.get('/goals');
    }

    createGoal(data) {
        return this.post('/goals', data);
    }

    updateGoal(id, data) {
        return this.put(`/goals/${id}`, data);
    }

    getGoalProgress(id) {
        return this.get(`/goals/${id}/progress`);
    }
}

/**
 * Custom API Error class
 */
class APIError extends Error {
    constructor(message, status, data = {}) {
        super(message);
        this.name = 'APIError';
        this.status = status;
        this.data = data;
        this.code = data.code || 'API_ERROR';
    }
}

// Export for use
window.AskProAPIClient = AskProAPIClient;
window.APIError = APIError;