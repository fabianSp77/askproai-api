/**
 * ULTRATHINK: Portal Auth Bypass
 * 
 * Dieses Script verhindert, dass die React App zu /login redirected
 * wenn keine Auth vorhanden ist.
 */

(function() {
    'use strict';
    
    console.log('🎯 Portal Auth Bypass aktiviert!');
    
    // Demo Mode aktivieren
    window.__DEMO_MODE__ = true;
    localStorage.setItem('demo_mode', 'true');
    
    // Fake User setzen falls nicht vorhanden
    if (!localStorage.getItem('portal_user')) {
        const demoUser = {
            id: 1,
            name: 'Demo User',
            email: 'demo@askproai.de',
            company_id: 1,
            role: 'user'
        };
        
        localStorage.setItem('portal_user', JSON.stringify(demoUser));
        console.log('✅ Demo User gesetzt:', demoUser);
    }
    
    // Auth Token setzen
    if (!localStorage.getItem('auth_token')) {
        localStorage.setItem('auth_token', 'demo-bypass-token-' + Date.now());
        console.log('✅ Auth Token gesetzt');
    }
    
    // Verhindere Redirects zu /login
    const originalLocation = window.location;
    Object.defineProperty(window, 'location', {
        get: function() {
            return originalLocation;
        },
        set: function(value) {
            if (typeof value === 'string' && value.includes('/login')) {
                console.warn('🛑 Login redirect blockiert!', value);
                return;
            }
            if (typeof value === 'object' && value.href && value.href.includes('/login')) {
                console.warn('🛑 Login redirect blockiert!', value.href);
                return;
            }
            originalLocation.href = value;
        }
    });
    
    // Überschreibe location.href setter
    const descriptor = Object.getOwnPropertyDescriptor(window.location, 'href');
    Object.defineProperty(window.location, 'href', {
        get: descriptor.get,
        set: function(value) {
            if (value.includes('/login')) {
                console.warn('🛑 Login redirect zu', value, 'blockiert!');
                return;
            }
            descriptor.set.call(this, value);
        }
    });
    
    console.log('✅ Portal Auth Bypass vollständig aktiv!');
})();