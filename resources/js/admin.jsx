import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import AdminApp from './AdminApp';
import './bootstrap';
import '../css/app.css';

// Get CSRF token
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Mount React app
const container = document.getElementById('admin-app');
if (container) {
    const root = createRoot(container);
    root.render(
        <BrowserRouter>
            <AdminApp csrfToken={csrfToken} />
        </BrowserRouter>
    );
}