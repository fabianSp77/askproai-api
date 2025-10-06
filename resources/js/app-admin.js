// Admin-specific JavaScript (with column manager)
import './bootstrap-admin';  // Load Alpine.js but don't start it yet
import './column-manager';  // Register column manager component
import './echo';

// Initialize Laravel data for Echo
window.Laravel = {
    user: window.authUser || null,
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
};

// Start Alpine.js after all components are registered
if (window.Alpine) {
    window.Alpine.start();
}

// Debug helper for admin area
window.debugComponents = () => {
    console.log('--- Admin Debug Info ---');
    console.log('Alpine available:', !!window.Alpine);
    console.log('Alpine version:', window.Alpine?.version);
    console.log('Column manager loaded');
    console.log('Alpine components:', window.Alpine?._components);
};