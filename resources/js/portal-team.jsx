import React from 'react';
import ReactDOM from 'react-dom/client';
import TeamIndex from './Pages/Portal/Team/IndexModern';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import '../css/app.css';

// Get CSRF token
const token = document.querySelector('meta[name="csrf-token"]');
const csrfToken = token ? token.getAttribute('content') : '';

// Mount Team component
const teamIndexRoot = document.getElementById('team-index-root');
if (teamIndexRoot) {
    const root = ReactDOM.createRoot(teamIndexRoot);
    root.render(
        <ThemeProvider defaultTheme="system">
            <AuthProvider csrfToken={csrfToken}>
                <TeamIndex />
            </AuthProvider>
        </ThemeProvider>
    );
}