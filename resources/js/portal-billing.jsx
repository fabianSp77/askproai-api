import React from 'react';
import ReactDOM from 'react-dom/client';
import BillingIndex from './Pages/Portal/Billing/IndexRefactored';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import '../css/app.css';

// Get CSRF token
const token = document.querySelector('meta[name="csrf-token"]');
const csrfToken = token ? token.getAttribute('content') : '';

// Mount Billing component
const billingIndexRoot = document.getElementById('billing-index-root');
if (billingIndexRoot) {
    const root = ReactDOM.createRoot(billingIndexRoot);
    root.render(
        <ThemeProvider defaultTheme="system">
            <AuthProvider csrfToken={csrfToken}>
                <BillingIndex />
            </AuthProvider>
        </ThemeProvider>
    );
}