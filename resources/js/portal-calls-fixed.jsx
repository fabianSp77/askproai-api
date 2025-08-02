import React from 'react';
import ReactDOM from 'react-dom/client';
import CallsIndex from './Pages/Portal/Calls/Index';
import CallShow from './Pages/Portal/Calls/Show';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import { BrowserRouter } from 'react-router-dom';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
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
                    <ToastContainer
                        position="top-right"
                        autoClose={5000}
                        hideProgressBar={false}
                        newestOnTop={false}
                        closeOnClick
                        rtl={false}
                        pauseOnFocusLoss
                        draggable
                        pauseOnHover
                        theme="light"
                    />
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
                    <ToastContainer
                        position="top-right"
                        autoClose={5000}
                        hideProgressBar={false}
                        newestOnTop={false}
                        closeOnClick
                        rtl={false}
                        pauseOnFocusLoss
                        draggable
                        pauseOnHover
                        theme="light"
                    />
                </AuthProvider>
            </ThemeProvider>
        </BrowserRouter>
    );
}