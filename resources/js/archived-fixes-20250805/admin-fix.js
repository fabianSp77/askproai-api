// EMERGENCY JAVASCRIPT FIX für Admin Panel - Issue #506
console.error('[ADMIN-FIX] 🚨 EMERGENCY FIX LOADING...');

// Funktion um alle Event Handler zu entfernen
function removeAllEventListeners(element) {
    const newElement = element.cloneNode(true);
    element.parentNode.replaceChild(newElement, element);
    return newElement;
}

// Hauptfixfunktion
function applyEmergencyFixes() {
    console.error('[ADMIN-FIX] Applying emergency fixes...');
    
    // 1. EXTREM: Entferne ALLE pointer-events: none
    const allElements = document.querySelectorAll('*');
    allElements.forEach(el => {
        const style = window.getComputedStyle(el);
        if (style.pointerEvents === 'none' || style.pointerEvents === 'inherit') {
            el.style.setProperty('pointer-events', 'auto', 'important');
        }
    });
    
    // 2. SIDEBAR FIX - Mache alle Sidebar-Links klickbar
    const sidebarLinks = document.querySelectorAll('.fi-sidebar a, .fi-sidebar button, .fi-sidebar [role="button"], .fi-sidebar-nav-item a');
    console.error('[ADMIN-FIX] Found ' + sidebarLinks.length + ' sidebar links');
    
    sidebarLinks.forEach((link, index) => {
        // Entferne alle Event Listener
        const newLink = removeAllEventListeners(link);
        
        // Force styles
        newLink.style.setProperty('pointer-events', 'auto', 'important');
        newLink.style.setProperty('cursor', 'pointer', 'important');
        newLink.style.setProperty('position', 'relative', 'important');
        newLink.style.setProperty('z-index', '999999', 'important');
        newLink.style.setProperty('display', 'block', 'important');
        
        // Debug click handler
        newLink.addEventListener('click', function(e) {
            console.error('[ADMIN-FIX] CLICK on link #' + index + ':', this.textContent, this.href);
            // Ensure navigation happens
            if (this.href && !e.defaultPrevented) {
                console.error('[ADMIN-FIX] Navigating to:', this.href);
                // Force navigation if needed
                setTimeout(() => {
                    if (window.location.href === this.href) return;
                    window.location.href = this.href;
                }, 100);
            }
        }, true);
    });
    
    // 3. REMOVE ALL OVERLAYS
    document.querySelectorAll('.fixed.inset-0, .absolute.inset-0, [class*="overlay"], [class*="backdrop"]').forEach(el => {
        if (!el.classList.contains('fi-main') && !el.classList.contains('fi-sidebar')) {
            el.style.setProperty('display', 'none', 'important');
            el.style.setProperty('pointer-events', 'none', 'important');
        }
    });
    
    // 4. FIX LAYOUT CONTAINERS
    const containers = ['.fi-layout', '.fi-main', '.fi-main-ctn', '.fi-page', '.fi-sidebar'];
    containers.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(el => {
            el.style.setProperty('pointer-events', 'auto', 'important');
            el.style.setProperty('position', 'relative', 'important');
            
            // Fix overflow for content visibility
            if (selector === '.fi-main' || selector === '.fi-main-ctn') {
                el.style.setProperty('overflow-x', 'auto', 'important');
                el.style.setProperty('overflow-y', 'auto', 'important');
            }
        });
    });
    
    // 5. AGGRESSIVE Z-INDEX FIX
    document.querySelectorAll('.fi-sidebar, .fi-sidebar-nav').forEach(el => {
        el.style.setProperty('z-index', '999999', 'important');
        el.style.setProperty('position', 'relative', 'important');
    });
    
    // 6. CREATE DEBUG INDICATOR
    let debugDiv = document.getElementById('admin-fix-debug');
    if (!debugDiv) {
        debugDiv = document.createElement('div');
        debugDiv.id = 'admin-fix-debug';
        debugDiv.style.cssText = 'position:fixed;bottom:10px;right:10px;background:#dc2626;color:white;padding:10px;z-index:999999;font-family:monospace;font-size:12px;border-radius:4px;';
        document.body.appendChild(debugDiv);
    }
    debugDiv.innerHTML = '🚨 JS FIX ACTIVE<br>Links: ' + sidebarLinks.length + '<br>Time: ' + new Date().toLocaleTimeString();
    
    // 7. GLOBAL CLICK INTERCEPTOR
    document.removeEventListener('click', globalClickHandler, true);
    document.addEventListener('click', globalClickHandler, true);
}

// Global click handler
function globalClickHandler(e) {
    const target = e.target;
    const link = target.closest('a, button, [role="button"]');
    
    if (link) {
        console.error('[ADMIN-FIX] Global click on:', link.textContent || link.className);
        
        // Check if click was prevented
        if (e.defaultPrevented) {
            console.error('[ADMIN-FIX] Click was prevented! Forcing navigation...');
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            if (link.href) {
                setTimeout(() => {
                    window.location.href = link.href;
                }, 0);
            }
        }
    }
}

// RUN FIXES MULTIPLE TIMES
// Immediately
applyEmergencyFixes();

// On DOMContentLoaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyEmergencyFixes);
} else {
    // DOM already loaded
    setTimeout(applyEmergencyFixes, 0);
}

// After short delays to catch dynamic content
setTimeout(applyEmergencyFixes, 100);
setTimeout(applyEmergencyFixes, 500);
setTimeout(applyEmergencyFixes, 1000);
setTimeout(applyEmergencyFixes, 2000);

// On Alpine init
document.addEventListener('alpine:init', () => {
    console.error('[ADMIN-FIX] Alpine initialized, reapplying fixes...');
    setTimeout(applyEmergencyFixes, 100);
});

// On Livewire load
document.addEventListener('livewire:load', () => {
    console.error('[ADMIN-FIX] Livewire loaded, reapplying fixes...');
    setTimeout(applyEmergencyFixes, 100);
});

// Monitor for dynamic changes
let observer = new MutationObserver(() => {
    // Debounce
    clearTimeout(window.adminFixTimeout);
    window.adminFixTimeout = setTimeout(applyEmergencyFixes, 200);
});

// Start observing when DOM is ready
if (document.body) {
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style', 'class']
    });
} else {
    document.addEventListener('DOMContentLoaded', () => {
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class']
        });
    });
}

// EXTREME: Override addEventListener to prevent blocking
const originalAddEventListener = EventTarget.prototype.addEventListener;
EventTarget.prototype.addEventListener = function(type, listener, options) {
    if (type === 'click' && this.matches && this.matches('.fi-sidebar a, .fi-sidebar button, .fi-sidebar-nav-item')) {
        console.warn('[ADMIN-FIX] Intercepted click listener on sidebar element');
        // Add non-blocking version
        return originalAddEventListener.call(this, type, function(e) {
            console.error('[ADMIN-FIX] Click event on sidebar element');
            if (typeof listener === 'function') {
                listener.call(this, e);
            }
            // Never prevent default on navigation
            if (this.href) {
                e.stopPropagation();
            }
        }, options);
    }
    return originalAddEventListener.call(this, type, listener, options);
};

console.error('[ADMIN-FIX] 🚨 EMERGENCY FIX LOADED - Monitoring for issues...');