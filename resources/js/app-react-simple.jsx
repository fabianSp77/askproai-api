import './bootstrap';
import '../css/app.css';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { ThemeProvider } from './contexts/ThemeContext';
import { AuthProvider } from './hooks/useAuth.jsx';
import PortalApp from './PortalAppModern';

// Initialize React app
document.addEventListener('DOMContentLoaded', () => {
    const rootElement = document.getElementById('app');
    if (rootElement) {
        // Get initial data from blade template
        const authData = JSON.parse(rootElement.dataset.auth || '{}');
        const csrfToken = rootElement.dataset.csrf;
        const initialRoute = rootElement.dataset.initialRoute;
        
        const root = createRoot(rootElement);
        root.render(
            <AuthProvider initialAuth={authData} csrfToken={csrfToken}>
                <ThemeProvider>
                    <BrowserRouter basename="/business">
                        <PortalApp initialAuth={authData} csrfToken={csrfToken} initialRoute={initialRoute} />
                    </BrowserRouter>
                </ThemeProvider>
            </AuthProvider>
        );
    }
});