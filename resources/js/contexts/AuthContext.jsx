import React, { createContext, useContext, useState, useEffect } from 'react';
import axios from 'axios';

const AuthContext = createContext({});

// Configure axios defaults
axios.defaults.baseURL = '/api';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};

export const AuthProvider = ({ children, csrfToken, initialAuth }) => {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    // Set CSRF token
    useEffect(() => {
        if (csrfToken) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
        }
    }, [csrfToken]);

    // Setup axios interceptor for token
    useEffect(() => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }

        // Response interceptor for 401 errors
        const interceptor = axios.interceptors.response.use(
            response => response,
            error => {
                if (error.response?.status === 401) {
                    // Clear auth state on 401
                    logout();
                }
                return Promise.reject(error);
            }
        );

        return () => {
            axios.interceptors.response.eject(interceptor);
        };
    }, []);

    // Check current authentication status
    const checkAuth = async () => {
        try {
            setLoading(true);
            const response = await axios.get('/auth/user');
            if (response.data.user) {
                setUser(response.data.user);
                setError(null);
            } else {
                setUser(null);
            }
        } catch (error) {
            console.error('Auth check failed:', error);
            setUser(null);
            // Don't set error for 401s, just means not authenticated
            if (error.response?.status !== 401) {
                setError(error.response?.data?.message || 'Authentication check failed');
            }
        } finally {
            setLoading(false);
        }
    };

    // Initial auth check
    useEffect(() => {
        // If we have initialAuth from server, use it
        if (initialAuth?.user) {
            setUser(initialAuth.user);
            setLoading(false);
        } else {
            // Otherwise check via API
            checkAuth();
        }
    }, [initialAuth]);

    // Login function
    const login = async (email, password) => {
        try {
            setLoading(true);
            setError(null);

            const response = await axios.post('/auth/portal/login', {
                email,
                password,
                device_name: 'web'
            });

            if (response.data.requires_2fa) {
                return {
                    success: false,
                    requires_2fa: true,
                    user_id: response.data.user_id
                };
            }

            // Store token
            const token = response.data.token;
            localStorage.setItem('auth_token', token);
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

            // Set user
            setUser(response.data.user);
            
            return {
                success: true,
                user: response.data.user
            };
        } catch (error) {
            const message = error.response?.data?.errors?.email?.[0] || 
                          error.response?.data?.message || 
                          'Login failed';
            setError(message);
            throw new Error(message);
        } finally {
            setLoading(false);
        }
    };

    // Logout function
    const logout = async () => {
        try {
            await axios.post('/auth/logout');
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            // Clear local state regardless
            localStorage.removeItem('auth_token');
            delete axios.defaults.headers.common['Authorization'];
            setUser(null);
            setError(null);
        }
    };

    const value = {
        user,
        loading,
        error,
        login,
        logout,
        checkAuth,
        isAuthenticated: !!user,
        csrfToken
    };

    return (
        <AuthContext.Provider value={value}>
            {children}
        </AuthContext.Provider>
    );
};