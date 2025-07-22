import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { ConfigProvider } from 'antd';
import deDE from 'antd/locale/de_DE';
import Login from './pages/auth/Login';
import 'antd/dist/reset.css';

// Setup axios defaults
import axios from 'axios';

console.log('login.jsx loaded');

// Debug info
console.log('React:', React);
console.log('ReactDOM:', ReactDOM);
console.log('Login component:', Login);

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Setup CSRF token
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    console.log('CSRF token set:', token);
} else {
    console.warn('No CSRF token found');
}

// Mount the login app
console.log('Looking for login-app element...');
const appElement = document.getElementById('login-app');

if (appElement) {
    console.log('Found login-app element:', appElement);
    const csrfToken = appElement.dataset.csrf || '';
    console.log('CSRF from data attribute:', csrfToken);
    
    try {
        const root = ReactDOM.createRoot(appElement);
        console.log('Created React root');
        
        root.render(
            <BrowserRouter>
                <ConfigProvider locale={deDE}>
                    <Login csrfToken={csrfToken} />
                </ConfigProvider>
            </BrowserRouter>
        );
        console.log('React render called');
    } catch (error) {
        console.error('Error rendering React app:', error);
        
        // Fallback: Show error in DOM
        appElement.innerHTML = `
            <div style="padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;">
                <h2>Error Loading Login</h2>
                <p>${error.message}</p>
                <pre>${error.stack}</pre>
            </div>
        `;
    }
} else {
    console.error('login-app element not found!');
    
    // Try to show error
    document.body.innerHTML += `
        <div style="padding: 20px; background: #f8d7da; color: #721c24;">
            Error: login-app element not found
        </div>
    `;
}