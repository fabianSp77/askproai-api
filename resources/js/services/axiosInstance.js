import axios from 'axios';

const axiosInstance = axios.create({
    baseURL: '/business/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    withCredentials: true
});

// Add CSRF token to requests
axiosInstance.interceptors.request.use(
    (config) => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                      document.querySelector('[data-csrf]')?.getAttribute('data-csrf');
        
        if (token) {
            config.headers['X-CSRF-TOKEN'] = token;
        }
        
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Handle responses
let retryCount = 0;
const maxRetries = 2;

axiosInstance.interceptors.response.use(
    (response) => {
        return response;
    },
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
                return axiosInstance(originalRequest);
            }
            
            // Only redirect after retries exhausted
            if (retryCount >= maxRetries) {
                window.location.href = '/business/login';
            }
        }
        
        return Promise.reject(error);
    }
);

export default axiosInstance;