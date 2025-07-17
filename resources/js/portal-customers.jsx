import React from 'react';
import ReactDOM from 'react-dom/client';
import CustomersIndex from './Pages/Portal/Customers/Index';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';

// Get CSRF token
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// Mount Customers Index component
const customersIndexRoot = document.getElementById('customers-index-root');
if (customersIndexRoot) {
    const root = ReactDOM.createRoot(customersIndexRoot);
    root.render(
        <ThemeProvider defaultTheme="system">
            <AuthProvider csrfToken={csrfToken}>
                <CustomersIndex />
            </AuthProvider>
        </ThemeProvider>
    );
}