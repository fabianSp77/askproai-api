// Emergency Sidebar Fix - Remove stuck fi-sidebar-open class
// GitHub Issues #427/#428/#430

// IMMEDIATE FIX - Run as soon as script loads
(function() {
    // Force remove the class immediately
    if (typeof document !== 'undefined' && document.body) {
        document.body.classList.remove('fi-sidebar-open');
        document.documentElement.classList.remove('fi-sidebar-open');
        
        // Also remove any inline styles that might be causing issues
        document.body.style.overflow = '';
        document.body.style.pointerEvents = '';
    }
})();

document.addEventListener('DOMContentLoaded', function() {
    console.log('Sidebar fix: Removing fi-sidebar-open class');
    
    // Remove fi-sidebar-open class immediately
    document.body.classList.remove('fi-sidebar-open');
    document.documentElement.classList.remove('fi-sidebar-open');
    
    // AGGRESSIVE: Remove the class every 100ms for the first 2 seconds
    let removeCount = 0;
    const aggressiveRemoval = setInterval(function() {
        document.body.classList.remove('fi-sidebar-open');
        removeCount++;
        if (removeCount > 20) { // 2 seconds
            clearInterval(aggressiveRemoval);
        }
    }, 100);
    
    // Monitor and prevent the class from being added
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                // Always remove on desktop, or if we're on a login page
                const isLoginPage = window.location.pathname.includes('login') || 
                                   document.querySelector('.fi-login-panel');
                
                if ((window.innerWidth >= 1024 || isLoginPage) && 
                    document.body.classList.contains('fi-sidebar-open')) {
                    console.log('Sidebar fix: Removing fi-sidebar-open class (observer)');
                    document.body.classList.remove('fi-sidebar-open');
                }
            }
        });
    });
    
    // Start observing
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });
    
    // Clean up on resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth >= 1024) {
                document.body.classList.remove('fi-sidebar-open');
            }
        }, 250);
    });
    
    // Ensure all interactive elements are clickable
    document.querySelectorAll('a, button, input, select, textarea, [role="button"]').forEach(function(el) {
        el.style.pointerEvents = 'auto';
    });
    
    // Ensure login forms are always accessible
    const loginForm = document.querySelector('.fi-simple-page, .fi-login-panel, form[wire\\:submit]');
    if (loginForm) {
        loginForm.style.position = 'relative';
        loginForm.style.zIndex = '100';
        loginForm.style.pointerEvents = 'auto';
    }
});

// Also run on Alpine init (Filament uses Alpine.js)
if (window.Alpine) {
    document.addEventListener('alpine:init', function() {
        document.body.classList.remove('fi-sidebar-open');
    });
}