// Company Integration Portal Debug Script
// This script helps identify and fix click interaction issues

(function() {
    'use strict';
    
    console.log('=== Company Integration Portal Debug Started ===');
    
    // Wait for DOM and Livewire to be ready
    function runDebug() {
        // 1. Check for Livewire
        if (!window.Livewire) {
            console.error('❌ Livewire not found! This is critical for wire:click to work.');
            return;
        }
        console.log('✅ Livewire is loaded');
        
        // 2. Find all interactive elements
        const wireClickElements = document.querySelectorAll('[wire\\:click]');
        const alpineElements = document.querySelectorAll('[x-data]');
        const buttons = document.querySelectorAll('button');
        
        console.log(`Found ${wireClickElements.length} wire:click elements`);
        console.log(`Found ${alpineElements.length} Alpine.js elements`);
        console.log(`Found ${buttons.length} buttons`);
        
        // 3. Check for overlay issues
        wireClickElements.forEach((element, index) => {
            const rect = element.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;
            const topElement = document.elementFromPoint(centerX, centerY);
            
            if (topElement !== element && !element.contains(topElement)) {
                console.warn(`⚠️ Element ${index} may be blocked:`, {
                    element: element,
                    wireClick: element.getAttribute('wire:click'),
                    blockedBy: topElement,
                    rect: rect
                });
            }
        });
        
        // 4. Check for CSS issues
        const companyCards = document.querySelectorAll('.company-card');
        companyCards.forEach((card, index) => {
            const styles = window.getComputedStyle(card);
            if (styles.pointerEvents === 'none') {
                console.error(`❌ Company card ${index} has pointer-events: none`);
            }
            if (styles.cursor !== 'pointer') {
                console.warn(`⚠️ Company card ${index} missing pointer cursor`);
            }
        });
        
        // 5. Monitor click events
        document.addEventListener('click', function(e) {
            const target = e.target;
            const wireClick = target.getAttribute('wire:click') || target.closest('[wire\\:click]')?.getAttribute('wire:click');
            
            if (wireClick) {
                console.log('🖱️ Wire:click triggered:', wireClick);
                console.log('Event details:', {
                    target: target,
                    propagationStopped: e.defaultPrevented,
                    bubbles: e.bubbles
                });
            }
        }, true);
        
        // 6. Check for JavaScript errors
        window.addEventListener('error', function(e) {
            console.error('❌ JavaScript Error:', e.message, e.filename, e.lineno);
        });
        
        // 7. Monitor Livewire events
        if (window.Livewire) {
            Livewire.on('error', (error) => {
                console.error('❌ Livewire Error:', error);
            });
            
            Livewire.hook('message.sent', (message, component) => {
                console.log('📤 Livewire message sent:', message);
            });
            
            Livewire.hook('message.failed', (message, component) => {
                console.error('❌ Livewire message failed:', message);
            });
        }
        
        // 8. Test specific buttons
        const testButtons = {
            'selectCompany': 'Company selection',
            'testCalcomIntegration': 'Cal.com test',
            'testRetellIntegration': 'Retell test',
            'refreshData': 'Refresh data'
        };
        
        Object.entries(testButtons).forEach(([method, description]) => {
            const elements = document.querySelectorAll(`[wire\\:click*="${method}"]`);
            if (elements.length === 0) {
                console.warn(`⚠️ No elements found for ${description} (${method})`);
            } else {
                console.log(`✅ Found ${elements.length} elements for ${description}`);
            }
        });
        
        console.log('=== Debug Complete ===');
    }
    
    // Run debug when everything is loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runDebug);
    } else if (window.Livewire) {
        runDebug();
    } else {
        document.addEventListener('livewire:load', runDebug);
    }
})();