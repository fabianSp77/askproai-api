import React, { createContext, useState, useContext, useEffect } from 'react';
import axios from 'axios';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';

const AuthContext = createContext(null);

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};

export const AuthProvider = ({ children }) => {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    // Set default axios headers
    useEffect(() => {
        const token = localStorage.getItem('admin_token');
        if (token) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }
    }, []);

    // Check if user is authenticated on mount
    useEffect(() => {
        checkAuth();
    }, []);

    const checkAuth = async () => {
        const token = localStorage.getItem('admin_token');
        
        if (!token) {
            setLoading(false);
            navigate('/admin/login');
            return;
        }

        try {
            const response = await axios.get('/api/admin/auth/user');
            setUser(response.data);
            setLoading(false);
        } catch (error) {
            console.error('Auth check failed:', error);
            localStorage.removeItem('admin_token');
            delete axios.defaults.headers.common['Authorization'];
            setLoading(false);
            navigate('/admin/login');
        }
    };

    const login = async (email, password) => {
        try {
            const response = await axios.post('/api/admin/auth/login', {
                email,
                password
            });

            const { token, user } = response.data;
            
            // Store token
            localStorage.setItem('admin_token', token);
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            
            // Set user
            setUser(user);
            
            toast.success('Erfolgreich angemeldet!');
            navigate('/admin');
            
            return { success: true };
        } catch (error) {
            console.error('Login error:', error);
            const message = error.response?.data?.message || 'Anmeldung fehlgeschlagen';
            toast.error(message);
            return { success: false, message };
        }
    };

    const logout = async () => {
        try {
            await axios.post('/api/admin/auth/logout');
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            // Clear local data
            localStorage.removeItem('admin_token');
            delete axios.defaults.headers.common['Authorization'];
            setUser(null);
            navigate('/admin/login');
            toast.info('Sie wurden abgemeldet');
        }
    };

    const refreshToken = async () => {
        try {
            const response = await axios.post('/api/admin/auth/refresh');
            const { token } = response.data;
            
            localStorage.setItem('admin_token', token);
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            
            return true;
        } catch (error) {
            console.error('Token refresh failed:', error);
            return false;
        }
    };

    // Axios interceptor for 401 responses
    useEffect(() => {
        const interceptor = axios.interceptors.response.use(
            (response) => response,
            async (error) => {
                if (error.response?.status === 401) {
                    // Try to refresh token
                    const refreshed = await refreshToken();
                    
                    if (refreshed) {
                        // Retry original request
                        return axios(error.config);
                    } else {
                        // Refresh failed, logout
                        await logout();
                    }
                }
                return Promise.reject(error);
            }
        );

        return () => {
            axios.interceptors.response.eject(interceptor);
        };
    }, []);

    const value = {
        user,
        loading,
        login,
        logout,
        checkAuth,
        refreshToken,
        isAuthenticated: !!user,
        isSuperAdmin: user?.role === 'super-admin',
        isAdmin: user?.role === 'admin' || user?.role === 'super-admin',
        hasPermission: (permission) => {
            return user?.permissions?.includes(permission) || user?.role === 'super-admin';
        }
    };

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};