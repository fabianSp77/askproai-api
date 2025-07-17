import axios from 'axios';
import { message } from 'antd';

const adminAxios = axios.create({
    baseURL: '/api/admin',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    withCredentials: true
});

// Add CSRF token to requests
adminAxios.interceptors.request.use(
    (config) => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                      document.querySelector('[data-csrf]')?.getAttribute('data-csrf');
        
        if (token) {
            config.headers['X-CSRF-TOKEN'] = token;
        }
        
        // Debug logging (remove in production)
        console.log('Admin API Request:', config.method.toUpperCase(), config.url);
        
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Handle responses
adminAxios.interceptors.response.use(
    (response) => {
        // Debug logging (remove in production)
        console.log('Admin API Response:', response.config.url, response.status);
        return response;
    },
    (error) => {
        // Debug logging
        console.error('Admin API Error:', error.response?.status, error.response?.data);
        
        if (error.response?.status === 401) {
            message.error('Sitzung abgelaufen. Bitte melden Sie sich erneut an.');
            window.location.href = '/admin/login';
        } else if (error.response?.status === 403) {
            message.error('Keine Berechtigung für diese Aktion');
        } else if (error.response?.status === 404) {
            message.error('API Endpoint nicht gefunden');
        } else if (error.response?.status === 422) {
            const errors = error.response.data.errors;
            if (errors) {
                const firstError = Object.values(errors)[0];
                message.error(Array.isArray(firstError) ? firstError[0] : firstError);
            } else {
                message.error(error.response.data.message || 'Validierungsfehler');
            }
        } else if (error.response?.status === 500) {
            message.error('Serverfehler. Bitte versuchen Sie es später erneut.');
        } else if (!error.response) {
            message.error('Netzwerkfehler. Bitte überprüfen Sie Ihre Internetverbindung.');
        }
        
        return Promise.reject(error);
    }
);

export default adminAxios;