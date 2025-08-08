/**
 * Console Cleanup for Production
 * This file overrides console methods to reduce noise in production
 */

(function() {
    'use strict';
    
    // Always apply cleanup unless debug mode is explicitly enabled
    const debugMode = localStorage.getItem('debugMode') === 'true';
    
    if (!debugMode) {
        // Store original console methods
        const originalConsole = {
            log: console.log,
            warn: console.warn,
            error: console.error,
            info: console.info,
            debug: console.debug
        };
        
        // Override console.log to filter out noise
        console.log = function(...args) {
            // Silent by default
            return;
        };
        
        // Override console.warn to be selective
        console.warn = function(...args) {
            const message = args[0]?.toString() || '';
            
            // Only show critical warnings
            const criticalPatterns = [
                /deprecat/i,
                /security/i,
                /vulnerab/i
            ];
            
            if (criticalPatterns.some(pattern => pattern.test(message))) {
                originalConsole.warn.apply(console, args);
            }
        };
        
        // Keep only real errors visible
        console.error = function(...args) {
            const message = args[0]?.toString() || '';
            
            // Block debug/test errors and Alpine/Livewire errors
            const debugPatterns = [
                /🔍|🔴|❌|⚠️|🔧|📋|✅|📊|🧪/,
                /DEBUGGER|TEST|CLICK DETECTED/i,
                /EVENT ADDED/i,
                /pointer-events/i,
                /ReferenceError.*is not defined/i,
                /\[Alpine\]/,
                /Uncaught ReferenceError/,
                /expandedCompanies/,
                /matchesSearch/,
                /closeDropdown/,
                /isCompanySelected/,
                /isBranchSelected/,
                /dateFilterDropdown/,
                /searchQuery/,
                /showDateFilter/,
                /hasSearchResults/
            ];
            
            if (!debugPatterns.some(pattern => pattern.test(message))) {
                originalConsole.error.apply(console, args);
            }
        };
        
        // Completely silence info and debug
        console.info = function() {};
        console.debug = function() {};
        
        // Provide a way to re-enable debug mode
        window.enableDebugMode = function() {
            localStorage.setItem('debugMode', 'true');
            console.log = originalConsole.log;
            console.info = originalConsole.info;
            console.debug = originalConsole.debug;
            console.log('Debug mode enabled. Refresh to see all logs.');
        };
        
        window.disableDebugMode = function() {
            localStorage.removeItem('debugMode');
            location.reload();
        };
        
        // Log that cleanup is active
        originalConsole.log('Console cleanup active. Use enableDebugMode() to see all logs.');
    }
})();