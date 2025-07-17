import React, { createContext, useContext, useState, useEffect } from 'react';

const AuthContext = createContext({
    user: null,
    csrfToken: '',
    loading: true,
    error: null
});

export const AuthProvider = ({ children, csrfToken, initialAuth }) => {
    const [user, setUser] = useState(initialAuth?.user || null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        // If we have initialAuth, use it
        if (initialAuth?.user) {
            setUser(initialAuth.user);
            setLoading(false);
            return;
        }
        
        // Otherwise try to get user info from meta tags or session
        const userMeta = document.querySelector('meta[name="user"]');
        if (userMeta) {
            try {
                const userData = JSON.parse(userMeta.getAttribute('content'));
                setUser(userData);
            } catch (e) {
                // Failed to parse user data - return null user
            }
        }
        setLoading(false);
    }, [initialAuth]);

    const value = {
        user,
        csrfToken,
        loading,
        error
    };

    return (
        <AuthContext.Provider value={value}>
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};