import './bootstrap';
import './echo';

// Initialize Laravel data for Echo
window.Laravel = {
    user: window.authUser || null,
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
};

// IMPORTANT: No Alpine.js or column manager code here!
// Those are admin-only features and should not affect public pages like login
// If you need admin features, use app-admin.js instead