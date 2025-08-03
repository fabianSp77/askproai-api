// Emergency fix for framework loading - Issue #476
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚨 Emergency Framework Fix Loading...');
    
    // Force remove any blocking overlays
    document.querySelectorAll('[class*="overlay"]').forEach(el => {
        if (getComputedStyle(el).pointerEvents === 'none' || 
            getComputedStyle(el).position === 'fixed' ||
            getComputedStyle(el).position === 'absolute') {
            el.style.display = 'none';
        }
    });
    
    // Wait for both frameworks
    let attempts = 0;
    let checkInterval = setInterval(function() {
        attempts++;
        
        if (window.Alpine && window.Livewire) {
            clearInterval(checkInterval);
            console.log('✅ Frameworks loaded successfully');
            
            // Alpine is already started, just log success
            if (Alpine.started) {
                console.log('✅ Alpine.js already initialized');
            }
            
            // Livewire doesn't have rescan, but we can dispatch an event
            if (Livewire) {
                console.log('✅ Livewire ready');
                // Dispatch custom event for any listeners
                window.dispatchEvent(new CustomEvent('livewire:available'));
            }
            
            // Fix all click handlers
            fixClickHandlers();
        } else {
            console.log(`⏳ Waiting for frameworks... (attempt ${attempts}/50)`);
            
            if (!window.Alpine) console.log('❌ Alpine.js not loaded');
            if (!window.Livewire) console.log('❌ Livewire not loaded');
        }
        
        // Give up after 5 seconds
        if (attempts >= 50) {
            clearInterval(checkInterval);
            console.error('❌ Frameworks failed to load after 5 seconds');
            // Try to fix clicks anyway
            fixClickHandlers();
        }
    }, 100);
});

function fixClickHandlers() {
    console.log('🔧 Fixing click handlers...');
    
    // Force all interactive elements to be clickable
    const interactiveSelectors = [
        'button',
        'a',
        'input',
        'select',
        'textarea',
        '[role="button"]',
        '[role="link"]',
        '[wire\\:click]',
        '[x-on\\:click]',
        '[onclick]',
        '.fi-ta-action',
        '.fi-ac-action',
        '.fi-dropdown-trigger'
    ];
    
    interactiveSelectors.forEach(selector => {
        document.querySelectorAll(selector).forEach(el => {
            el.style.pointerEvents = 'auto';
            el.style.cursor = 'pointer';
        });
    });
    
    console.log('✅ Click handlers fixed');
}