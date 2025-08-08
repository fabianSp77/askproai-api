/**
 * Business Portal API Client - Simplified Version
 * Fixed version without fetch override issues
 */

class BusinessPortalAPI {
    constructor(options = {}) {
        this.baseURL = options.baseURL || '/business/api';
        this.csrfToken = this.getCsrfToken();
        this.debug = options.debug || false;
    }

    /**
     * Get CSRF token from meta tag
     */
    getCsrfToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.content : null;
    }

    /**
     * Make API request
     */
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        
        // Ensure CSRF token is current
        this.csrfToken = this.getCsrfToken();
        
        // Set default options
        const requestOptions = {
            ...options,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.csrfToken,
                ...options.headers
            },
            credentials: 'same-origin'
        };

        try {
            const response = await fetch(url, requestOptions);
            
            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || `HTTP ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('API Request failed:', error);
            throw error;
        }
    }

    /**
     * GET request
     */
    async get(endpoint, params = {}) {
        const url = new URL(`${this.baseURL}${endpoint}`, window.location.origin);
        Object.keys(params).forEach(key => {
            if (params[key] !== undefined && params[key] !== null && params[key] !== '') {
                url.searchParams.append(key, params[key]);
            }
        });
        
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

    // Authentication
    async checkAuth() {
        try {
            const response = await fetch('/business/api/auth/check', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.getCsrfToken()
                },
                credentials: 'same-origin'
            });
            
            if (response.ok) {
                return await response.json();
            }
            
            return { authenticated: false };
        } catch (error) {
            console.error('Auth check failed:', error);
            return { authenticated: false };
        }
    }

    // API Endpoints
    getCalls(params = {}) {
        return this.get('/calls', params);
    }

    getCall(id) {
        return this.get(`/calls/${id}`);
    }

    getDashboardStats() {
        return this.get('/dashboard/stats');
    }

    getRecentCalls() {
        return this.get('/dashboard/recent-calls');
    }

    getUpcomingAppointments() {
        return this.get('/dashboard/upcoming-appointments');
    }
}

// Create global instance
window.portalAPI = new BusinessPortalAPI();