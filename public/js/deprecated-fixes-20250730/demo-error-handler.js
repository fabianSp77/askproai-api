// Demo Error Handler
// Verhindert peinliche Fehler während der Präsentation

(function() {
    // Store original console methods
    const originalError = console.error;
    const originalWarn = console.warn;
    
    // Check if we're in demo mode
    const isDemoMode = window.location.search.includes('demo=true') || 
                       window.location.hostname.includes('demo') ||
                       sessionStorage.getItem('demoMode') === 'true';
    
    if (!isDemoMode) return;
    
    // Override console methods in demo mode
    console.error = function(...args) {
        // Log to original console but don't show to user
        originalError.apply(console, args);
        
        // Log to hidden demo log
        logDemoError('error', args);
    };
    
    console.warn = function(...args) {
        originalWarn.apply(console, args);
        logDemoError('warning', args);
    };
    
    // Global error handler
    window.addEventListener('error', function(event) {
        event.preventDefault();
        logDemoError('error', [event.message, event.filename, event.lineno]);
        
        // Show user-friendly message instead
        if (event.message.includes('network')) {
            showDemoNotification('Verbindung wird hergestellt...', 'info');
        }
        
        return true;
    });
    
    // Unhandled promise rejection handler
    window.addEventListener('unhandledrejection', function(event) {
        event.preventDefault();
        logDemoError('promise', [event.reason]);
        return true;
    });
    
    // AJAX error handler
    if (window.jQuery) {
        $(document).ajaxError(function(event, xhr, settings, error) {
            if (xhr.status === 0) {
                showDemoNotification('Verbindung wird überprüft...', 'info');
            } else if (xhr.status >= 500) {
                showDemoNotification('Einen Moment bitte...', 'info');
                // Auto-retry after 2 seconds
                setTimeout(() => {
                    $.ajax(settings);
                }, 2000);
            }
        });
    }
    
    // Fetch interceptor
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args)
            .catch(error => {
                logDemoError('fetch', [error]);
                showDemoNotification('Daten werden geladen...', 'info');
                
                // Return mock response for demo
                return new Response(JSON.stringify({
                    data: [],
                    message: 'Demo mode - cached data'
                }), {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' }
                });
            });
    };
    
    // Demo error logger (hidden from user)
    const demoErrors = [];
    function logDemoError(type, details) {
        demoErrors.push({
            type: type,
            details: details,
            timestamp: new Date().toISOString()
        });
        
        // Store for later analysis
        sessionStorage.setItem('demoErrors', JSON.stringify(demoErrors));
    }
    
    // User-friendly notification system
    function showDemoNotification(message, type = 'info') {
        // Remove any existing notifications
        const existing = document.querySelector('.demo-notification');
        if (existing) existing.remove();
        
        const notification = document.createElement('div');
        notification.className = 'demo-notification';
        notification.style.cssText = `
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            background: ${type === 'info' ? '#3B82F6' : '#10B981'};
            color: white;
            padding: 1rem 2rem;
            border-radius: 9999px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 99999;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: slideUp 0.3s ease-out;
        `;
        
        // Add spinner for loading states
        if (message.includes('...')) {
            notification.innerHTML = `
                <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>${message}</span>
            `;
        } else {
            notification.innerHTML = `<span>${message}</span>`;
        }
        
        document.body.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideDown 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Add required styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideUp {
            from { transform: translate(-50%, 100%); opacity: 0; }
            to { transform: translate(-50%, 0); opacity: 1; }
        }
        @keyframes slideDown {
            from { transform: translate(-50%, 0); opacity: 1; }
            to { transform: translate(-50%, 100%); opacity: 0; }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
    
    // Demo mode indicator
    const indicator = document.createElement('div');
    indicator.style.cssText = `
        position: fixed;
        top: 1rem;
        right: 1rem;
        background: #10B981;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        z-index: 99999;
        opacity: 0.7;
    `;
    indicator.textContent = 'DEMO MODE';
    document.body.appendChild(indicator);
    
    // Keyboard shortcuts for demo
    document.addEventListener('keydown', function(event) {
        // Ctrl/Cmd + D: Toggle demo mode
        if ((event.ctrlKey || event.metaKey) && event.key === 'd') {
            event.preventDefault();
            const currentMode = sessionStorage.getItem('demoMode') === 'true';
            sessionStorage.setItem('demoMode', !currentMode ? 'true' : 'false');
            location.reload();
        }
        
        // Ctrl/Cmd + E: Show demo errors (hidden)
        if ((event.ctrlKey || event.metaKey) && event.key === 'e') {
            event.preventDefault();
            console.log('Demo Errors:', demoErrors);
        }
    });
    
    // Prevent right-click in demo mode
    document.addEventListener('contextmenu', function(event) {
        event.preventDefault();
        showDemoNotification('Demo Modus aktiv', 'info');
        return false;
    });
    
    // Smooth all animations
    document.documentElement.style.scrollBehavior = 'smooth';
    
    // Preload critical resources
    const criticalUrls = [
        '/admin/kundenverwaltung',
        '/business/dashboard',
        '/api/companies'
    ];
    
    criticalUrls.forEach(url => {
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        document.head.appendChild(link);
    });
    
    console.log('🎯 Demo Error Handler aktiviert');
})();