import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { ConfigProvider } from 'antd';
import deDE from 'antd/locale/de_DE';
import Login from './pages/auth/Login';
import 'antd/dist/reset.css';

// Setup axios defaults
import axios from 'axios';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Setup CSRF token
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
}

// Mount the login app
const appElement = document.getElementById('login-app');
if (appElement) {
    const csrfToken = appElement.dataset.csrf || '';
    
    const root = ReactDOM.createRoot(appElement);
    root.render(
        <BrowserRouter>
            <ConfigProvider locale={deDE}>
                <Login csrfToken={csrfToken} />
            </ConfigProvider>
        </BrowserRouter>
    );
}