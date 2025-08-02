import React from 'react';
import ReactDOM from 'react-dom/client';
import CallsIndex from './Pages/Portal/Calls/Index';
import CallShow from './Pages/Portal/Calls/Show';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import { BrowserRouter } from 'react-router-dom';
import '../css/app.css';

// Get CSRF token
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// Mount Calls Index component
const callsIndexRoot = document.getElementById('calls-index-root');
if (callsIndexRoot) {
    const root = ReactDOM.createRoot(callsIndexRoot);
    root.render(
        <BrowserRouter basename="/business">
            <ThemeProvider defaultTheme="system">
                <AuthProvider csrfToken={csrfToken}>
                    <CallsIndex />
                </AuthProvider>
            </ThemeProvider>
        </BrowserRouter>
    );
}

// Mount Call Show component
const callShowRoot = document.getElementById('call-show-root');
if (callShowRoot) {
    const callId = callShowRoot.dataset.callId;
    const root = ReactDOM.createRoot(callShowRoot);
    root.render(
        <BrowserRouter basename="/business">
            <ThemeProvider defaultTheme="system">
                <AuthProvider csrfToken={csrfToken}>
                    <CallShow callId={callId} />
                </AuthProvider>
            </ThemeProvider>
        </BrowserRouter>
    );
}