/**
 * Emergency Framework Loader
 * Ensures Alpine and Livewire are properly loaded and initialized
 */
(function() {
    'use strict';
    
    console.log('[Emergency Framework Loader] Starting...');
    
    let checkCount = 0;
    const maxChecks = 30;
    
    function checkAndLoadFrameworks() {
        checkCount++;
        
        // Check Alpine
        if (!window.Alpine) {
            console.log(`[Emergency Framework Loader] Attempt ${checkCount}: Alpine not found`);
            
            // Look for Alpine in common locations
            if (window.alpinejs) {
                window.Alpine = window.alpinejs;
                console.log('[Emergency Framework Loader] Found Alpine as window.alpinejs');
            } else if (window.AlpineJS) {
                window.Alpine = window.AlpineJS;
                console.log('[Emergency Framework Loader] Found Alpine as window.AlpineJS');
            }
        }
        
        // Check Livewire
        if (!window.Livewire) {
            console.log(`[Emergency Framework Loader] Attempt ${checkCount}: Livewire not found`);
            
            // Look for Livewire in common locations
            if (window.livewire) {
                window.Livewire = window.livewire;
                console.log('[Emergency Framework Loader] Found Livewire as window.livewire');
            }
        }
        
        // If both are loaded, initialize
        if (window.Alpine && window.Livewire) {
            console.log('[Emergency Framework Loader] Both frameworks detected!');
            
            // Initialize sidebar store BEFORE Alpine starts
            if (!window.Alpine.store || !window.Alpine.store('sidebar')) {
                console.log('[Emergency Framework Loader] Initializing sidebar store...');
                
                // Add the store method if it doesn't exist (for Alpine v2 compatibility)
                if (!window.Alpine.store) {
                    window.Alpine.store = function(name, value) {
                        if (!window.Alpine.stores) {
                            window.Alpine.stores = {};
                        }
                        if (value !== undefined) {
                            window.Alpine.stores[name] = value;
                        }
                        return window.Alpine.stores[name];
                    };
                }
                
                // Initialize sidebar store
                window.Alpine.store('sidebar', {
                    isOpen: window.matchMedia('(min-width: 1024px)').matches,
                    collapsedGroups: [],
                    
                    open() {
                        this.isOpen = true;
                    },
                    
                    close() {
                        this.isOpen = false;
                    },
                    
                    toggle() {
                        this.isOpen = !this.isOpen;
                    },
                    
                    groupIsCollapsed(group) {
                        return this.collapsedGroups.includes(group);
                    },
                    
                    toggleCollapsedGroup(group) {
                        if (this.groupIsCollapsed(group)) {
                            this.collapsedGroups = this.collapsedGroups.filter(g => g !== group);
                        } else {
                            this.collapsedGroups.push(group);
                        }
                    }
                });
                
                // Also add theme store
                if (!window.Alpine.store('theme')) {
                    window.Alpine.store('theme', 'system');
                }
                
                console.log('[Emergency Framework Loader] Sidebar store initialized');
            }
            
            // Make sure Alpine is started
            if (!window.Alpine.version && typeof window.Alpine.start === 'function') {
                console.log('[Emergency Framework Loader] Starting Alpine...');
                window.Alpine.start();
            }
            
            // Initialize all Alpine components
            setTimeout(() => {
                const uninitComponents = document.querySelectorAll('[x-data]:not([data-framework-init])');
                uninitComponents.forEach(el => {
                    try {
                        if (!el.__x) {
                            window.Alpine.initTree(el);
                            el.setAttribute('data-framework-init', 'true');
                            console.log('[Emergency Framework Loader] Initialized Alpine component');
                        }
                    } catch (e) {
                        console.error('[Emergency Framework Loader] Error initializing component:', e);
                    }
                });
                
                // Dispatch event to notify other scripts
                window.dispatchEvent(new CustomEvent('frameworks-loaded', {
                    detail: { alpine: true, livewire: true }
                }));
                
                // Run portal fix if available
                if (window.portalFix) {
                    console.log('[Emergency Framework Loader] Running portal fix...');
                    window.portalFix.reinit();
                }
            }, 100);
            
            return true;
        }
        
        // If not found after max attempts, try emergency load
        if (checkCount >= maxChecks) {
            console.error('[Emergency Framework Loader] Frameworks not found after max attempts');
            emergencyLoad();
            return true;
        }
        
        // Continue checking
        return false;
    }
    
    function emergencyLoad() {
        console.log('[Emergency Framework Loader] Attempting emergency load...');
        
        // Check if Livewire scripts exist but haven't executed
        const livewireScripts = Array.from(document.scripts).filter(s => 
            s.src && (s.src.includes('livewire') || s.textContent.includes('window.Livewire'))
        );
        
        if (livewireScripts.length > 0) {
            console.log('[Emergency Framework Loader] Found Livewire scripts:', livewireScripts.length);
            
            // Try to manually initialize Livewire
            if (window.livewireScriptConfig) {
                console.log('[Emergency Framework Loader] Found livewireScriptConfig');
            }
        }
        
        // Do NOT load Alpine from CDN - let Filament handle it
        if (!window.Alpine) {
            console.log('[Emergency Framework Loader] Alpine not found, but NOT loading from CDN');
            console.log('[Emergency Framework Loader] Filament should load Alpine properly');
            
            // Check if Alpine is in the process of being loaded
            const alpineScripts = Array.from(document.scripts).filter(s => 
                (s.src && s.src.includes('alpine')) || 
                (s.textContent && s.textContent.includes('window.Alpine'))
            );
            
            if (alpineScripts.length > 0) {
                console.log('[Emergency Framework Loader] Found Alpine scripts, waiting for them to load...');
                
                // Give Filament more time to load Alpine
                setTimeout(() => {
                    if (window.Alpine) {
                        console.log('[Emergency Framework Loader] Alpine loaded by Filament');
                        checkAndLoadFrameworks();
                    } else {
                        console.error('[Emergency Framework Loader] Alpine still not loaded after waiting');
                    }
                }, 1000);
            }
        }
    }
    
    // Start checking
    const checkInterval = setInterval(() => {
        if (checkAndLoadFrameworks()) {
            clearInterval(checkInterval);
        }
    }, 100);
    
    // Also check on various events
    document.addEventListener('DOMContentLoaded', checkAndLoadFrameworks);
    window.addEventListener('load', checkAndLoadFrameworks);
    
    // Listen for Livewire initialization
    document.addEventListener('livewire:init', () => {
        console.log('[Emergency Framework Loader] Livewire init event detected');
        setTimeout(checkAndLoadFrameworks, 100);
    });
    
    // Listen for Alpine initialization
    document.addEventListener('alpine:init', () => {
        console.log('[Emergency Framework Loader] Alpine init event detected');
        
        // Initialize stores immediately during Alpine init
        if (window.Alpine) {
            // Initialize sidebar store
            if (!window.Alpine.store('sidebar')) {
                console.log('[Emergency Framework Loader] Creating sidebar store during alpine:init');
                window.Alpine.store('sidebar', {
                    isOpen: window.matchMedia('(min-width: 1024px)').matches,
                    collapsedGroups: window.Alpine.$persist ? window.Alpine.$persist([]).as('filament.sidebar.collapsedGroups') : [],
                    
                    open() {
                        this.isOpen = true;
                    },
                    
                    close() {
                        this.isOpen = false;
                    },
                    
                    toggle() {
                        this.isOpen = !this.isOpen;
                    },
                    
                    groupIsCollapsed(group) {
                        return this.collapsedGroups.includes(group);
                    },
                    
                    toggleCollapsedGroup(group) {
                        if (this.groupIsCollapsed(group)) {
                            this.collapsedGroups = this.collapsedGroups.filter(g => g !== group);
                        } else {
                            this.collapsedGroups.push(group);
                        }
                    },
                    
                    collapseGroup(group) {
                        if (!this.groupIsCollapsed(group)) {
                            this.collapsedGroups.push(group);
                        }
                    },
                    
                    expandGroup(group) {
                        this.collapsedGroups = this.collapsedGroups.filter(g => g !== group);
                    }
                });
            }
            
            // Initialize theme store
            if (!window.Alpine.store('theme')) {
                window.Alpine.store('theme', 'system');
            }
        }
        
        setTimeout(checkAndLoadFrameworks, 100);
    });
    
    // Export debug function
    window.frameworkLoaderDebug = function() {
        console.log('=== Framework Loader Debug ===');
        console.log('Alpine:', window.Alpine ? 'Loaded' : 'Not loaded');
        console.log('Livewire:', window.Livewire ? 'Loaded' : 'Not loaded');
        
        // Check for framework scripts
        const scripts = Array.from(document.scripts);
        const alpineScripts = scripts.filter(s => s.src && s.src.includes('alpine'));
        const livewireScripts = scripts.filter(s => s.src && s.src.includes('livewire'));
        
        console.log('Alpine scripts:', alpineScripts.map(s => s.src));
        console.log('Livewire scripts:', livewireScripts.map(s => s.src));
        
        // Check window object
        console.log('Window properties with "alpine":', Object.keys(window).filter(k => k.toLowerCase().includes('alpine')));
        console.log('Window properties with "livewire":', Object.keys(window).filter(k => k.toLowerCase().includes('livewire')));
    };
})();