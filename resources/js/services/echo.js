import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Get the authentication token and type
const getAuthHeaders = () => {
    const token = localStorage.getItem('access_token') || 
                 document.querySelector('meta[name="api-token"]')?.getAttribute('content');
    
    const headers = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };
    
    // Add CSRF token if available
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }
    
    // Add Bearer token if available
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    
    return headers;
};

// Initialize Echo with configuration
const echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY || 'askproai-websocket-key',
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_PUSHER_PORT || 6001,
    wssPort: import.meta.env.VITE_PUSHER_PORT || 443,
    forceTLS: import.meta.env.VITE_PUSHER_SCHEME === 'https',
    encrypted: true,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
    auth: {
        headers: getAuthHeaders()
    },
    authEndpoint: '/broadcasting/auth',
    namespace: 'App.Events'
});

// Helper function to get company-specific channel
export const getCompanyChannel = (companyId) => {
    return echo.private(`company.${companyId}`);
};

// Helper function to get branch-specific channel
export const getBranchChannel = (branchId) => {
    return echo.private(`branch.${branchId}`);
};

// Helper function to get user-specific channel
export const getUserChannel = (userId) => {
    return echo.private(`user.${userId}`);
};

// Helper function to join presence channel
export const joinPresenceChannel = (channelName) => {
    return echo.join(channelName);
};

// Helper function to leave all channels
export const leaveAllChannels = () => {
    echo.leaveAllChannels();
};

// Helper to reconnect
export const reconnect = () => {
    echo.disconnect();
    echo.connect();
};

// Export the Echo instance
export default echo;