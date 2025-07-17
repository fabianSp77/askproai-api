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
portalAxios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Redirect to login if unauthenticated
            window.location.href = '/business/login';
        }
        return Promise.reject(error);
    }
);

export default portalAxios;