import React from 'react';
import ReactDOM from 'react-dom/client';
import { AuthProvider } from './contexts/AuthContext.jsx';
import { ThemeProvider } from './contexts/ThemeContext';
import { BrowserRouter } from 'react-router-dom';
import DashboardIndex from './Pages/Portal/Dashboard/Index';
import '../css/app.css';

// Initialize React app
const rootElement = document.getElementById('app');
if (rootElement) {
    const root = ReactDOM.createRoot(rootElement);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    root.render(
        <BrowserRouter basename="/business">
            <ThemeProvider defaultTheme="system">
                <AuthProvider csrfToken={csrfToken}>
                    <DashboardIndex />
                </AuthProvider>
            </ThemeProvider>
        </BrowserRouter>
    );
}