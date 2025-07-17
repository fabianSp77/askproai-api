import { useState, useEffect, createContext, useContext } from 'react';

const AuthContext = createContext({});

export const AuthProvider = ({ children, initialAuth, csrfToken }) => {
    const [user, setUser] = useState(initialAuth?.user || null);
    const [permissions, setPermissions] = useState([]);
    const [loading, setLoading] = useState(false);

    const fetchPermissions = async () => {
        try {
            setLoading(true);
            const response = await fetch('/business/api/user/permissions', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                setPermissions(data.permissions || []);
            }
        } catch (error) {
            // Silently handle permissions fetch error
        } finally {
            setLoading(false);
        }
    };

    const hasPermission = (permission) => {
        return permissions.includes(permission);
    };

    const canManageBilling = () => {
        return hasPermission('billing.manage') || hasPermission('billing.pay');
    };

    const canViewBilling = () => {
        return hasPermission('billing.view');
    };

    const logout = async () => {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/business/logout';
        
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = csrfToken;
        
        form.appendChild(tokenInput);
        document.body.appendChild(form);
        form.submit();
    };

    useEffect(() => {
        if (user) {
            fetchPermissions();
        }
    }, [user]);

    const value = {
        user,
        permissions,
        loading,
        hasPermission,
        canManageBilling,
        canViewBilling,
        logout,
        csrfToken
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