import React from 'react';
import ReactDOM from 'react-dom/client';
import { AuthProvider } from './contexts/AuthContext.jsx';
import { ThemeProvider } from './contexts/ThemeContext';
import { LazyAnalytics } from './utils/lazyLoad.jsx';
import '../css/app.css';

// Performance optimization: Preload critical resources
const preloadCriticalAssets = () => {
    // Preload fonts
    const fontLink = document.createElement('link');
    fontLink.rel = 'preload';
    fontLink.as = 'font';
    fontLink.type = 'font/woff2';
    fontLink.href = '/fonts/Inter-var.woff2';
    fontLink.crossOrigin = 'anonymous';
    document.head.appendChild(fontLink);
    
    // Preconnect to API domain
    const preconnectLink = document.createElement('link');
    preconnectLink.rel = 'preconnect';
    preconnectLink.href = window.location.origin;
    document.head.appendChild(preconnectLink);
};

// Initialize performance optimizations
preloadCriticalAssets();

// Enable React concurrent features
const rootElement = document.getElementById('analytics-index-root');
if (rootElement) {
    // Create root with concurrent features
    const root = ReactDOM.createRoot(rootElement, {
        // Enable time slicing
        unstable_concurrentUpdatesByDefault: true
    });
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    // Use startTransition for non-urgent updates
    React.startTransition(() => {
        root.render(
            <React.StrictMode>
                <ThemeProvider defaultTheme="system">
                    <AuthProvider csrfToken={csrfToken}>
                        <LazyAnalytics />
                    </AuthProvider>
                </ThemeProvider>
            </React.StrictMode>
        );
    });
}

// Register service worker for caching (if available)
if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Service worker registration failed, continue without it
        });
    });
}