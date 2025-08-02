/**
 * CSRF Token Handler
 * 
 * Automatically handles CSRF tokens for all AJAX requests
 * Works with both traditional forms and modern SPA patterns
 */

class CsrfHandler {
    constructor() {
        this.token = null;
        this.tokenElement = null;
        this.init();
    }

    init() {
        // Find CSRF token from meta tag
        this.tokenElement = document.querySelector('meta[name="csrf-token"]');
        if (this.tokenElement) {
            this.token = this.tokenElement.getAttribute('content');
        }

        // Setup automatic token refresh on 419 errors
        this.setupInterceptors();
        
        // Setup form token injection
        this.setupFormTokens();
        
        // Refresh token periodically (every 30 minutes)
        setInterval(() => this.refreshToken(), 30 * 60 * 1000);
    }

    setupInterceptors() {
        // Axios interceptor
        if (window.axios) {
            // Request interceptor
            window.axios.interceptors.request.use(config => {
                if (this.token) {
                    config.headers['X-CSRF-TOKEN'] = this.token;
                    config.headers['X-Requested-With'] = 'XMLHttpRequest';
                }
                return config;
            });

            // Response interceptor for 419 errors
            window.axios.interceptors.response.use(
                response => response,
                async error => {
                    if (error.response?.status === 419) {
                        // Token expired, refresh and retry
                        await this.refreshToken();
                        error.config.headers['X-CSRF-TOKEN'] = this.token;
                        return window.axios.request(error.config);
                    }
                    return Promise.reject(error);
                }
            );
        }

        // Native fetch interceptor
        const originalFetch = window.fetch;
        window.fetch = async (url, options = {}) => {
            // Add CSRF token to headers
            if (this.token && !options.headers?.['X-CSRF-TOKEN']) {
                options.headers = {
                    ...options.headers,
                    'X-CSRF-TOKEN': this.token,
                    'X-Requested-With': 'XMLHttpRequest'
                };
            }

            try {
                const response = await originalFetch(url, options);
                
                // Handle 419 errors
                if (response.status === 419) {
                    await this.refreshToken();
                    options.headers['X-CSRF-TOKEN'] = this.token;
                    return originalFetch(url, options);
                }
                
                return response;
            } catch (error) {
                throw error;
            }
        };

        // jQuery AJAX setup
        if (window.$ && window.$.ajaxSetup) {
            window.$.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': this.token,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Handle 419 errors in jQuery
            window.$(document).ajaxError((event, xhr, settings, error) => {
                if (xhr.status === 419) {
                    this.refreshToken().then(() => {
                        // Update headers and retry
                        settings.headers = {
                            ...settings.headers,
                            'X-CSRF-TOKEN': this.token
                        };
                        window.$.ajax(settings);
                    });
                }
            });
        }
    }

    setupFormTokens() {
        // Add token to all forms
        document.querySelectorAll('form').forEach(form => {
            if (!form.querySelector('input[name="_token"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = this.token;
                form.appendChild(input);
            }
        });

        // Watch for dynamically added forms
        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeName === 'FORM') {
                        this.addTokenToForm(node);
                    } else if (node.querySelectorAll) {
                        node.querySelectorAll('form').forEach(form => {
                            this.addTokenToForm(form);
                        });
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    addTokenToForm(form) {
        if (!form.querySelector('input[name="_token"]')) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_token';
            input.value = this.token;
            form.appendChild(input);
        }
    }

    async refreshToken() {
        try {
            const response = await fetch('/api/csrf-token', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const data = await response.json();
                this.token = data.token;
                
                // Update meta tag
                if (this.tokenElement) {
                    this.tokenElement.setAttribute('content', this.token);
                }

                // Update all form tokens
                document.querySelectorAll('input[name="_token"]').forEach(input => {
                    input.value = this.token;
                });

                // Update jQuery if available
                if (window.$ && window.$.ajaxSetup) {
                    window.$.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': this.token
                        }
                    });
                }

                console.log('CSRF token refreshed');
            }
        } catch (error) {
            console.error('Failed to refresh CSRF token:', error);
        }
    }

    getToken() {
        return this.token;
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.csrfHandler = new CsrfHandler();
    });
} else {
    window.csrfHandler = new CsrfHandler();
}

export default CsrfHandler;