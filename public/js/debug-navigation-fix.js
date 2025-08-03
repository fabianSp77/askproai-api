/**
 * Debug Navigation Fix - Temporary solution to diagnose and fix navigation issues
 */

(function() {
    'use strict';
    
    // Only log if explicitly in debug mode
    const isDebugMode = localStorage.getItem('navigationDebug') === 'true';
    
    if (isDebugMode) {
        console.log('🔍 Navigation Debug Mode Activated');
    }
    
    // Make debug mode available to all functions
    window._navigationDebugMode = isDebugMode;
    
    // Wait for DOM
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }
    
    ready(function() {
        if (isDebugMode) {
            console.log('🔍 DOM Ready - Starting navigation diagnosis');
        }
        
        // 1. Remove ALL pointer-events: none styles
        function fixPointerEvents() {
            // Remove from stylesheets
            const styleSheets = document.styleSheets;
            for (let i = 0; i < styleSheets.length; i++) {
                try {
                    const rules = styleSheets[i].cssRules || styleSheets[i].rules;
                    if (rules) {
                        for (let j = rules.length - 1; j >= 0; j--) {
                            const rule = rules[j];
                            if (rule.style && rule.style.pointerEvents === 'none') {
                                if (isDebugMode) {
                                    console.log('🔧 Removing pointer-events: none from', rule.selectorText);
                                }
                                rule.style.pointerEvents = 'auto';
                            }
                        }
                    }
                } catch (e) {
                    // Cross-origin stylesheets
                }
            }
            
            // Fix inline styles
            document.querySelectorAll('*').forEach(el => {
                if (window.getComputedStyle(el).pointerEvents === 'none') {
                    el.style.pointerEvents = 'auto';
                }
            });
        }
        
        // 2. Ensure navigation links work
        function fixNavigationLinks() {
            const navLinks = document.querySelectorAll('.fi-sidebar-nav a, .fi-sidebar-item a, .fi-sidebar-nav button');
            console.log(`🔍 Found ${navLinks.length} navigation elements`);
            
            navLinks.forEach((link, index) => {
                // Force clickability
                link.style.pointerEvents = 'auto';
                link.style.cursor = 'pointer';
                link.style.position = 'relative';
                link.style.zIndex = '10';
                
                // Remove any click blockers
                const computedStyle = window.getComputedStyle(link);
                if (computedStyle.pointerEvents === 'none') {
                    console.error(`❌ Link ${index} still has pointer-events: none after fix!`);
                }
                
                // Add click logging
                link.addEventListener('click', function(e) {
                    console.log(`✅ Navigation click detected: ${this.textContent.trim()}`);
                    console.log('  - href:', this.getAttribute('href'));
                    console.log('  - wire:navigate:', this.hasAttribute('wire:navigate'));
                    
                    // If it has href and no wire:navigate, force navigation
                    if (this.getAttribute('href') && !this.hasAttribute('wire:navigate')) {
                        console.log('  - Forcing navigation to:', this.getAttribute('href'));
                        e.preventDefault();
                        e.stopPropagation();
                        window.location.href = this.getAttribute('href');
                    }
                }, true);
            });
        }
        
        // 3. Fix Alpine.js component issues
        function fixAlpineComponents() {
            if (window.Alpine) {
                console.log('✅ Alpine.js detected');
                
                // Add missing component methods as globals (temporary fix)
                const missingComponents = [
                    'expandedCompanies',
                    'matchesSearch', 
                    'closeDropdown',
                    'isCompanySelected',
                    'isBranchSelected',
                    'dateFilterDropdown',
                    'searchQuery',
                    'showDateFilter',
                    'hasSearchResults'
                ];
                
                missingComponents.forEach(comp => {
                    if (typeof window[comp] === 'undefined') {
                        console.log(`🔧 Adding stub for missing Alpine component: ${comp}`);
                        if (comp.includes('expanded') || comp.includes('show')) {
                            window[comp] = false;
                        } else if (comp.includes('search') || comp.includes('Query')) {
                            window[comp] = '';
                        } else if (comp.includes('matches') || comp.includes('is') || comp.includes('has')) {
                            window[comp] = function() { return false; };
                        } else {
                            window[comp] = function() { console.log(`Stub called: ${comp}`); };
                        }
                    }
                });
            } else {
                console.error('❌ Alpine.js not found!');
            }
        }
        
        // 4. Monitor for dynamic changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    // Re-apply fixes for new elements
                    fixPointerEvents();
                    fixNavigationLinks();
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        // Apply all fixes
        fixPointerEvents();
        fixNavigationLinks();
        fixAlpineComponents();
        
        // Re-apply periodically
        setInterval(() => {
            fixPointerEvents();
            fixNavigationLinks();
        }, 2000);
        
        console.log('✅ Navigation debug fix applied');
    });
})();