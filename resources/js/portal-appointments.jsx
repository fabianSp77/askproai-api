import React from 'react';
import ReactDOM from 'react-dom/client';
import AppointmentsIndex from './Pages/Portal/Appointments/IndexV2';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import { BrowserRouter } from 'react-router-dom';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import '../css/app.css';

// Get CSRF token
const token = document.querySelector('meta[name="csrf-token"]');
const csrfToken = token ? token.getAttribute('content') : '';

// Mount Appointments component
const appointmentsIndexRoot = document.getElementById('appointments-index-root');
if (appointmentsIndexRoot) {
    const root = ReactDOM.createRoot(appointmentsIndexRoot);
    root.render(
        <BrowserRouter basename="/business">
            <ThemeProvider defaultTheme="system">
                <AuthProvider csrfToken={csrfToken}>
                    <AppointmentsIndex />
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