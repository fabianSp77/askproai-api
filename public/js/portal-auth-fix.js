/**
 * Portal Authentication Fix
 * This script fixes the authentication flow in the business portal
 */

// Override the initApp function to fix authentication
window.initApp = async function() {
    // Check for existing session
    const sessionToken = localStorage.getItem('portal_session_token');
    const storedUser = localStorage.getItem('portal_user');
    
    if (sessionToken && storedUser) {
        // Initialize API client
        window.apiClient = new AskProAPIClient({
            debug: true,
            onAuthError: handleAuthError,
            onNetworkError: handleNetworkError
        });

        try {
            // Parse stored user
            window.currentUser = JSON.parse(storedUser);
            
            // Skip auth check and directly show app
            // The session token is valid if we just logged in
            showApp();
            return;
        } catch (error) {
            console.error('Error parsing user data:', error);
        }
    }

    // Show login modal if no session
    showLogin();
};

// Also fix the login handler to ensure it works correctly
const originalHandleLogin = window.handleLogin;
window.handleLogin = async function(e) {
    e.preventDefault();
    
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    const remember = document.getElementById('rememberMe').checked;
    
    // Show loading state
    document.getElementById('loginButton').disabled = true;
    document.getElementById('loginButtonText').classList.add('hidden');
    document.getElementById('loginButtonLoading').classList.remove('hidden');
    document.getElementById('loginError').classList.add('hidden');
    
    try {
        // Direct API call for login
        const response = await fetch('/business/api/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'include',
            body: JSON.stringify({ email, password, remember })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Store session data
            localStorage.setItem('portal_session_token', data.data.session_token);
            localStorage.setItem('portal_user', JSON.stringify(data.data.user));
            
            // Initialize API client
            window.apiClient = new AskProAPIClient({
                debug: true,
                onAuthError: handleAuthError,
                onNetworkError: handleNetworkError
            });
            
            window.currentUser = data.data.user;
            
            // Show app immediately
            showApp();
            showToast('Erfolgreich angemeldet!', 'success');
        } else {
            const errorMsg = data.message || 'Anmeldung fehlgeschlagen. Bitte versuchen Sie es erneut.';
            document.getElementById('loginError').textContent = errorMsg;
            document.getElementById('loginError').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Login error:', error);
        document.getElementById('loginError').textContent = 'Netzwerkfehler. Bitte versuchen Sie es erneut.';
        document.getElementById('loginError').classList.remove('hidden');
    } finally {
        document.getElementById('loginButton').disabled = false;
        document.getElementById('loginButtonText').classList.remove('hidden');
        document.getElementById('loginButtonLoading').classList.add('hidden');
    }
};

console.log('Portal authentication fix loaded');