import React from 'react';
import ReactDOM from 'react-dom/client';
import SettingsIndex from './Pages/Portal/Settings/Index';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import '../css/app.css';

// Get CSRF token
const token = document.querySelector('meta[name="csrf-token"]');
const csrfToken = token ? token.getAttribute('content') : '';

// Mount Settings component
const settingsIndexRoot = document.getElementById('settings-index-root');
if (settingsIndexRoot) {
    const root = ReactDOM.createRoot(settingsIndexRoot);
    root.render(
        <ThemeProvider defaultTheme="system">
            <AuthProvider csrfToken={csrfToken}>
                <SettingsIndex />
            </AuthProvider>
        </ThemeProvider>
    );
}