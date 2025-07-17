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
axiosInstance.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        if (error.response?.status === 401) {
            window.location.href = '/business/login';
        }
        return Promise.reject(error);
    }
);

export default axiosInstance;