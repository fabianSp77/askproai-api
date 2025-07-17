import React from 'react';
import ReactDOM from 'react-dom/client';
import AnalyticsIndex from './Pages/Portal/Analytics/IndexModern';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import '../css/app.css';

// Get CSRF token
const token = document.querySelector('meta[name="csrf-token"]');
const csrfToken = token ? token.getAttribute('content') : '';

// Mount Analytics component
const analyticsIndexRoot = document.getElementById('analytics-index-root');
if (analyticsIndexRoot) {
    const root = ReactDOM.createRoot(analyticsIndexRoot);
    root.render(
        <ThemeProvider defaultTheme="system">
            <AuthProvider csrfToken={csrfToken}>
                <AnalyticsIndex />
            </AuthProvider>
        </ThemeProvider>
    );
}