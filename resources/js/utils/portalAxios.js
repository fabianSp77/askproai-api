import axios from 'axios';

// Create axios instance for portal API requests
const portalAxios = axios.create({
    baseURL: '/business/api',
    withCredentials: true, // Important for session cookies
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
});

// Add request interceptor to ensure CSRF token is always included
portalAxios.interceptors.request.use((config) => {
    // Get CSRF token from meta tag
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token.content;
    }
    
    return config;
}, (error) => {
    return Promise.reject(error);
});

// Add response interceptor to handle authentication errors
let retryCount = 0;
const maxRetries = 2;

portalAxios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;
        
        if (error.response?.status === 401 && !originalRequest._retry && retryCount < maxRetries) {
            // Skip redirect in demo mode
            if (window.__DEMO_MODE__ || localStorage.getItem('demo_mode') === 'true') {
                return Promise.reject(error);
            }
            
            originalRequest._retry = true;
            retryCount++;
            
            // Wait a bit for auth overrides to potentially fix the issue
            await new Promise(resolve => setTimeout(resolve, 500));
            
            // Check localStorage again before redirecting
            const storedUser = localStorage.getItem('portal_user');
            if (storedUser) {
                // User exists in localStorage, retry the request
                retryCount = 0;
                return portalAxios(originalRequest);
            }
            
            // Only redirect after retries exhausted
            if (retryCount >= maxRetries) {
                window.location.href = '/business/login';
            }
        }
        
        return Promise.reject(error);
    }
);

export default portalAxios;