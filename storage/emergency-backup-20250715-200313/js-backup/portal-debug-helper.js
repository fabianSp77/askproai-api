/**
 * Portal Debug Helper
 * Comprehensive debugging tools for Alpine/Livewire issues
 */
(function() {
    'use strict';
    
    console.log('[Portal Debug Helper] Loading...');
    
    const debugTools = {
        /**
         * Show comprehensive status
         */
        status: function() {
            console.log('\n=== PORTAL STATUS REPORT ===\n');
            
            // Framework status
            console.log('🔧 FRAMEWORKS:');
            console.log('  Alpine.js:', window.Alpine ? `✅ v${window.Alpine.version || 'unknown'}` : '❌ Not loaded');
            console.log('  Livewire:', window.Livewire ? '✅ Loaded' : '❌ Not loaded');
            console.log('  Filament:', document.querySelector('.fi-body') ? '✅ Detected' : '❌ Not detected');
            
            // Component counts
            console.log('\n📊 COMPONENTS:');
            console.log('  Alpine components:', document.querySelectorAll('[x-data]').length);
            console.log('  - Initialized:', document.querySelectorAll('[x-data][data-alpine-init]').length);
            console.log('  - Uninitialized:', document.querySelectorAll('[x-data]:not([data-alpine-init])').length);
            console.log('  Livewire components:', document.querySelectorAll('[wire\\:id]').length);
            console.log('  Dropdowns:', document.querySelectorAll('.fi-dropdown').length);
            console.log('  Stat widgets:', document.querySelectorAll('.fi-wi-stats-overview-stat').length);
            
            // Interactive elements
            console.log('\n🖱️ INTERACTIVE ELEMENTS:');
            console.log('  wire:click elements:', document.querySelectorAll('[wire\\:click]').length);
            console.log('  wire:model elements:', document.querySelectorAll('[wire\\:model]').length);
            console.log('  x-on:click elements:', document.querySelectorAll('[x-on\\:click]').length);
            
            // Issues
            console.log('\n⚠️ POTENTIAL ISSUES:');
            this.checkIssues();
        },
        
        /**
         * Check for common issues
         */
        checkIssues: function() {
            let issues = 0;
            
            // Check for uninitialized Alpine components
            const uninitAlpine = document.querySelectorAll('[x-data]:not([data-alpine-init])');
            if (uninitAlpine.length > 0) {
                console.warn(`  - ${uninitAlpine.length} Alpine components not initialized`);
                issues++;
            }
            
            // Check for hidden stat values
            const hiddenStats = document.querySelectorAll('.fi-wi-stats-overview-stat-value');
            let hiddenCount = 0;
            hiddenStats.forEach(stat => {
                const computed = window.getComputedStyle(stat);
                if (computed.display === 'none' || computed.visibility === 'hidden' || computed.opacity === '0') {
                    hiddenCount++;
                }
            });
            if (hiddenCount > 0) {
                console.warn(`  - ${hiddenCount} stat values are hidden`);
                issues++;
            }
            
            // Check for disabled buttons
            const disabledInteractive = document.querySelectorAll('[wire\\:click][disabled], button[disabled]:not([wire\\:loading])');
            if (disabledInteractive.length > 0) {
                console.warn(`  - ${disabledInteractive.length} interactive elements are disabled`);
                issues++;
            }
            
            // Check for pointer-events: none
            const noPointer = Array.from(document.querySelectorAll('[wire\\:click], button')).filter(el => {
                return window.getComputedStyle(el).pointerEvents === 'none';
            });
            if (noPointer.length > 0) {
                console.warn(`  - ${noPointer.length} clickable elements have pointer-events: none`);
                issues++;
            }
            
            if (issues === 0) {
                console.log('  ✅ No issues detected');
            }
            
            return issues;
        },
        
        /**
         * Test specific dropdown
         */
        testDropdown: function(index = 0) {
            const dropdowns = document.querySelectorAll('.fi-dropdown');
            const dropdown = dropdowns[index];
            
            if (!dropdown) {
                console.error('Dropdown not found at index', index);
                return;
            }
            
            console.log('\n=== TESTING DROPDOWN ===');
            console.log('Element:', dropdown);
            console.log('Has x-data:', dropdown.hasAttribute('x-data'));
            console.log('Alpine instance:', !!dropdown.__x);
            
            if (dropdown.__x) {
                console.log('Alpine data:', dropdown.__x.$data);
            }
            
            const button = dropdown.querySelector('button');
            const panel = dropdown.querySelector('.fi-dropdown-panel');
            
            console.log('Button found:', !!button);
            console.log('Panel found:', !!panel);
            
            if (button) {
                console.log('Button attributes:', {
                    disabled: button.disabled,
                    'wire:click': button.getAttribute('wire:click'),
                    'x-on:click': button.getAttribute('x-on:click'),
                    style: button.getAttribute('style')
                });
                
                console.log('Clicking button...');
                button.click();
                
                setTimeout(() => {
                    if (panel) {
                        console.log('Panel visibility:', {
                            display: panel.style.display,
                            computedDisplay: window.getComputedStyle(panel).display,
                            classes: panel.className
                        });
                    }
                }, 100);
            }
        },
        
        /**
         * Test statistics widget
         */
        testStatWidget: function(index = 0) {
            const stats = document.querySelectorAll('.fi-wi-stats-overview-stat');
            const stat = stats[index];
            
            if (!stat) {
                console.error('Stat widget not found at index', index);
                return;
            }
            
            console.log('\n=== TESTING STAT WIDGET ===');
            console.log('Element:', stat);
            
            const value = stat.querySelector('.fi-wi-stats-overview-stat-value');
            const label = stat.querySelector('.fi-wi-stats-overview-stat-label');
            
            console.log('Value element:', value);
            console.log('Label element:', label);
            
            if (value) {
                console.log('Value content:', value.textContent);
                console.log('Value styles:', {
                    display: window.getComputedStyle(value).display,
                    visibility: window.getComputedStyle(value).visibility,
                    opacity: window.getComputedStyle(value).opacity
                });
            }
            
            // Check if it's a polled component
            const pollElement = stat.closest('[wire\\:poll]');
            if (pollElement) {
                console.log('Has wire:poll:', pollElement.getAttribute('wire:poll'));
                
                const component = pollElement.closest('[wire\\:id]');
                if (component && window.Livewire) {
                    const componentId = component.getAttribute('wire:id');
                    console.log('Livewire component ID:', componentId);
                    
                    const livewireComponent = window.Livewire.find(componentId);
                    if (livewireComponent) {
                        console.log('Refreshing component...');
                        livewireComponent.$refresh();
                    }
                }
            }
        },
        
        /**
         * Fix all issues
         */
        fixAll: function() {
            console.log('\n=== ATTEMPTING TO FIX ALL ISSUES ===');
            
            if (window.portalFix) {
                window.portalFix.reinit();
            }
            
            // Additional fixes
            setTimeout(() => {
                // Force show hidden stats
                document.querySelectorAll('.fi-wi-stats-overview-stat-value').forEach(el => {
                    el.style.display = 'block';
                    el.style.visibility = 'visible';
                    el.style.opacity = '1';
                });
                
                // Enable all disabled interactive elements
                document.querySelectorAll('[wire\\:click][disabled]').forEach(el => {
                    el.disabled = false;
                });
                
                console.log('✅ Fix attempt completed');
                this.status();
            }, 500);
        },
        
        /**
         * Monitor clicks
         */
        monitorClicks: function() {
            console.log('🖱️ Click monitoring enabled. Click any element to see details.');
            
            document.addEventListener('click', function(e) {
                console.log('\n--- CLICK DETECTED ---');
                console.log('Element:', e.target);
                console.log('Tag:', e.target.tagName);
                console.log('Classes:', e.target.className);
                console.log('Attributes:', {
                    'wire:click': e.target.getAttribute('wire:click'),
                    'wire:model': e.target.getAttribute('wire:model'),
                    'x-on:click': e.target.getAttribute('x-on:click'),
                    'x-data': e.target.getAttribute('x-data')
                });
                console.log('Prevented:', e.defaultPrevented);
                console.log('Propagation stopped:', e.cancelBubble);
            }, true);
        },
        
        /**
         * Enable visual debugging
         */
        visualDebug: function(enable = true) {
            if (enable) {
                document.body.classList.add('debug-mode');
                console.log('✅ Visual debug mode enabled');
                console.log('- Blue outline: wire:click elements');
                console.log('- Red dashed border: dropdowns');
            } else {
                document.body.classList.remove('debug-mode');
                console.log('❌ Visual debug mode disabled');
            }
        },
        
        /**
         * List all Livewire components
         */
        listLivewireComponents: function() {
            console.log('\n=== LIVEWIRE COMPONENTS ===');
            
            document.querySelectorAll('[wire\\:id]').forEach((el, i) => {
                const id = el.getAttribute('wire:id');
                const component = window.Livewire ? window.Livewire.find(id) : null;
                
                console.log(`\n${i + 1}. Component ID: ${id}`);
                console.log('  Element:', el);
                
                if (component) {
                    console.log('  Name:', component.name);
                    console.log('  Data:', component.$wire);
                } else {
                    console.log('  ❌ Component not found in Livewire');
                }
            });
        }
    };
    
    // Expose to window
    window.portalDebug = debugTools;
    
    // Auto-run status on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => debugTools.status(), 1000);
        });
    } else {
        setTimeout(() => debugTools.status(), 1000);
    }
    
    console.log('[Portal Debug Helper] Ready. Available commands:');
    console.log('- portalDebug.status() - Show complete status');
    console.log('- portalDebug.fixAll() - Attempt to fix all issues');
    console.log('- portalDebug.testDropdown(0) - Test specific dropdown');
    console.log('- portalDebug.testStatWidget(0) - Test stat widget');
    console.log('- portalDebug.monitorClicks() - Monitor all clicks');
    console.log('- portalDebug.visualDebug() - Enable visual debugging');
    console.log('- portalDebug.listLivewireComponents() - List all Livewire components');
})();