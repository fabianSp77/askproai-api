/**
 * Browser-based debugging script for Transaction infolist rendering
 * This script will capture the actual HTML output and check for JavaScript errors
 */

// Wait for page to fully load
window.addEventListener('load', function() {
    console.log('🔍 Transaction Infolist Debug Script Started');
    
    // Capture any JavaScript errors
    const errors = [];
    const originalError = console.error;
    console.error = function(...args) {
        errors.push({
            type: 'error',
            message: args.join(' '),
            timestamp: new Date().toISOString()
        });
        originalError.apply(console, args);
    };
    
    const originalWarn = console.warn;
    console.warn = function(...args) {
        errors.push({
            type: 'warning', 
            message: args.join(' '),
            timestamp: new Date().toISOString()
        });
        originalWarn.apply(console, args);
    };
    
    setTimeout(() => {
        console.log('🔍 Starting HTML Analysis...');
        
        // 1. Check for Filament components
        const filamentComponents = {
            'fi-page': document.querySelectorAll('[class*="fi-page"]'),
            'fi-in-component': document.querySelectorAll('[class*="fi-in-component"]'),
            'fi-in-entry': document.querySelectorAll('[class*="fi-in-entry"]'),
            'fi-section': document.querySelectorAll('[class*="fi-section"]'),
            'fi-in-text': document.querySelectorAll('.fi-in-text'),
            'fi-grid': document.querySelectorAll('[class*="fi-grid"]'),
        };
        
        console.log('📊 Filament Component Search Results:');
        for (const [selector, elements] of Object.entries(filamentComponents)) {
            console.log(`   ${selector}: ${elements.length} found`);
            if (elements.length > 0) {
                console.log(`     Sample classes:`, Array.from(elements).slice(0, 3).map(el => el.className));
            }
        }
        
        // 2. Check for Livewire components
        const livewireComponents = {
            'livewire-components': document.querySelectorAll('[wire\\:id]'),
            'alpine-components': document.querySelectorAll('[x-data]'),
            'livewire-scripts': document.querySelectorAll('script[data-livewire]')
        };
        
        console.log('📊 Livewire Component Search Results:');
        for (const [selector, elements] of Object.entries(livewireComponents)) {
            console.log(`   ${selector}: ${elements.length} found`);
        }
        
        // 3. Check for specific transaction data
        const bodyText = document.body.textContent;
        const transactionDataPresence = {
            'transaction-keyword': bodyText.toLowerCase().includes('transaction'),
            'german-section': bodyText.includes('Transaktionsdetails'),
            'infolist-data': bodyText.includes('ID:') || bodyText.includes('Typ:'),
        };
        
        console.log('📊 Transaction Data Presence:');
        for (const [check, found] of Object.entries(transactionDataPresence)) {
            console.log(`   ${check}: ${found ? '✅' : '❌'}`);
        }
        
        // 4. Check for empty sections or missing content
        const sections = document.querySelectorAll('section, .fi-section');
        console.log(`📊 Found ${sections.length} sections on page`);
        
        const emptySections = Array.from(sections).filter(section => {
            const text = section.textContent.trim();
            return text.length === 0 || text === 'Loading...' || text === '';
        });
        
        console.log(`⚠️  Empty sections: ${emptySections.length}`);
        
        // 5. Check main content area
        const mainContent = document.querySelector('main, .fi-main, [role="main"]');
        if (mainContent) {
            console.log('✅ Main content area found');
            console.log(`   Text length: ${mainContent.textContent.trim().length} characters`);
            
            // Check if main content contains infolist
            const infolistInMain = mainContent.querySelectorAll('[class*="fi-in"]');
            console.log(`   Infolist components in main: ${infolistInMain.length}`);
        } else {
            console.log('❌ Main content area not found');
        }
        
        // 6. Check for specific transaction view elements
        const transactionElements = {
            'transaction-id': document.querySelector('[class*="badge"]') || document.querySelector('.fi-in-text'),
            'section-headings': document.querySelectorAll('h2, h3, .fi-section-header'),
            'grid-layouts': document.querySelectorAll('[class*="grid"]'),
        };
        
        console.log('📊 Transaction View Elements:');
        for (const [element, found] of Object.entries(transactionElements)) {
            if (found && found.length !== undefined) {
                console.log(`   ${element}: ${found.length} found`);
            } else {
                console.log(`   ${element}: ${found ? '✅ found' : '❌ not found'}`);
            }
        }
        
        // 7. Report JavaScript errors
        console.log('🚨 JavaScript Issues:');
        if (errors.length > 0) {
            console.log(`   Found ${errors.length} issues:`);
            errors.forEach((error, index) => {
                console.log(`   ${index + 1}. [${error.type.toUpperCase()}] ${error.message}`);
            });
        } else {
            console.log('   ✅ No JavaScript errors detected');
        }
        
        // 8. Check Livewire status
        if (window.Livewire) {
            console.log('✅ Livewire is loaded');
            console.log(`   Version: ${window.Livewire.version || 'Unknown'}`);
            
            // Get component information
            const components = document.querySelectorAll('[wire\\:id]');
            console.log(`   Active components: ${components.length}`);
            
            if (components.length > 0) {
                const componentInfo = Array.from(components).map(comp => ({
                    id: comp.getAttribute('wire:id'),
                    class: comp.getAttribute('class'),
                    hasContent: comp.textContent.trim().length > 0
                }));
                console.log('   Component details:', componentInfo);
            }
        } else {
            console.log('❌ Livewire not found on window object');
        }
        
        // 9. Check Alpine.js
        if (window.Alpine) {
            console.log('✅ Alpine.js is loaded');
        } else {
            console.log('❌ Alpine.js not found on window object');
        }
        
        // 10. Final summary
        const hasSectionsButNoContent = sections.length > 0 && emptySections.length === sections.length;
        const hasFilamentClasses = Object.values(filamentComponents).some(elements => elements.length > 0);
        const hasLivewireComponents = livewireComponents['livewire-components'].length > 0;
        
        console.log('📋 DIAGNOSTIC SUMMARY:');
        console.log(`   Sections found: ${sections.length}`);
        console.log(`   Filament CSS classes: ${hasFilamentClasses ? '✅' : '❌'}`);
        console.log(`   Livewire components: ${hasLivewireComponents ? '✅' : '❌'}`);
        console.log(`   Empty sections issue: ${hasSectionsButNoContent ? '⚠️ YES' : '✅ NO'}`);
        
        if (hasSectionsButNoContent) {
            console.log('🎯 ROOT CAUSE LIKELY: Infolist components are not rendering content into sections');
            console.log('🔧 POTENTIAL FIXES:');
            console.log('   1. Check if Livewire components are properly initialized');
            console.log('   2. Verify infolist data is being passed correctly');  
            console.log('   3. Check for JavaScript errors preventing component rendering');
            console.log('   4. Clear view cache and component cache');
        }
        
        // Save debug info to sessionStorage for inspection
        const debugInfo = {
            timestamp: new Date().toISOString(),
            url: window.location.href,
            errors: errors,
            components: filamentComponents,
            livewire: livewireComponents,
            hasContent: sections.length - emptySections.length,
            totalSections: sections.length
        };
        
        sessionStorage.setItem('filament-debug-info', JSON.stringify(debugInfo, null, 2));
        console.log('💾 Debug info saved to sessionStorage.getItem("filament-debug-info")');
        
    }, 2000); // Wait 2 seconds for dynamic content to load
    
});

// Also check for immediate errors
console.log('🚀 Transaction debug script loaded - waiting for page load...');