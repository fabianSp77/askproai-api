/**
 * Force Modern Styles - The Most Aggressive Approach
 * This will make it literally impossible for Filament to override these styles
 */

(function() {
    'use strict';
    
    // CSS that will be forcefully applied
    const MODERN_STYLES = `
        /* Critical: Use !important on EVERYTHING and highest specificity */
        html body .fi-page .retell-functions-wrapper .function-card-modern,
        html body .fi-page .retell-functions-wrapper div.function-card-modern,
        html body .fi-page .retell-functions-wrapper [class*="function-card-modern"],
        .function-card-modern:not(#fake-id):not(#fake-id):not(#fake-id) {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(99, 102, 241, 0.4) !important;
            border-radius: 24px !important;
            padding: 32px !important;
            position: relative !important;
            overflow: hidden !important;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
            margin-bottom: 24px !important;
            box-shadow: 
                0 10px 40px -10px rgba(99, 102, 241, 0.3),
                0 0 0 1px rgba(99, 102, 241, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
            transform: translateZ(0) !important;
            will-change: transform, box-shadow !important;
        }
        
        /* Glassmorphism overlay */
        .function-card-modern::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: radial-gradient(
                800px circle at var(--mouse-x, 50%) var(--mouse-y, 50%),
                rgba(99, 102, 241, 0.1),
                transparent 40%
            ) !important;
            opacity: 0 !important;
            transition: opacity 0.3s !important;
            pointer-events: none !important;
            z-index: 1 !important;
        }
        
        .function-card-modern:hover::before {
            opacity: 1 !important;
        }
        
        /* Dark mode enhancements */
        .dark .function-card-modern {
            background: linear-gradient(
                135deg, 
                rgba(99, 102, 241, 0.25) 0%, 
                rgba(139, 92, 246, 0.15) 50%,
                rgba(99, 102, 241, 0.1) 100%
            ) !important;
            border: 1px solid rgba(99, 102, 241, 0.5) !important;
            box-shadow: 
                0 10px 40px -10px rgba(99, 102, 241, 0.5),
                0 0 0 1px rgba(99, 102, 241, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1),
                0 0 80px -20px rgba(99, 102, 241, 0.4) !important;
        }
        
        .function-card-modern:hover {
            transform: translateY(-8px) scale(1.02) !important;
            box-shadow: 
                0 20px 60px -15px rgba(99, 102, 241, 0.4),
                0 0 0 1px rgba(99, 102, 241, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2),
                0 0 120px -20px rgba(99, 102, 241, 0.5) !important;
            border-color: rgba(99, 102, 241, 0.6) !important;
        }
        
        /* Gradient buttons */
        .btn-gradient-primary,
        button[wire\\:click*="AddingFunction"],
        button[wire\\:click*="startAddingFunction"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            padding: 12px 24px !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            border: none !important;
            box-shadow: 0 4px 15px -3px rgba(102, 126, 234, 0.5) !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .btn-gradient-primary::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
            opacity: 0 !important;
            transition: opacity 0.3s !important;
        }
        
        .btn-gradient-primary:hover::before {
            opacity: 1 !important;
        }
        
        .btn-gradient-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px -5px rgba(102, 126, 234, 0.6) !important;
        }
        
        /* Icon containers with gradients */
        .function-card-modern .w-12.h-12 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 
                0 10px 20px -5px rgba(102, 126, 234, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
            border-radius: 16px !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .function-card-modern .w-12.h-12::after {
            content: '' !important;
            position: absolute !important;
            top: -50% !important;
            left: -50% !important;
            width: 200% !important;
            height: 200% !important;
            background: radial-gradient(
                circle,
                rgba(255, 255, 255, 0.3) 0%,
                transparent 70%
            ) !important;
            animation: shimmer 3s infinite !important;
        }
        
        @keyframes shimmer {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Glass cards */
        .glass-card {
            background: rgba(255, 255, 255, 0.05) !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 16px !important;
            padding: 16px !important;
            box-shadow: 
                0 8px 32px 0 rgba(31, 38, 135, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
        }
        
        /* Parameter cards */
        .parameter-card {
            background: linear-gradient(
                135deg,
                rgba(255, 255, 255, 0.05) 0%,
                rgba(255, 255, 255, 0.02) 100%
            ) !important;
            backdrop-filter: blur(5px) !important;
            border: 1px solid rgba(99, 102, 241, 0.2) !important;
            border-radius: 12px !important;
            padding: 16px !important;
            transition: all 0.3s ease !important;
        }
        
        .parameter-card:hover {
            background: linear-gradient(
                135deg,
                rgba(99, 102, 241, 0.1) 0%,
                rgba(99, 102, 241, 0.05) 100%
            ) !important;
            border-color: rgba(99, 102, 241, 0.4) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px -5px rgba(99, 102, 241, 0.3) !important;
        }
        
        /* Force all children to respect styles */
        .function-card-modern * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
        }
        
        /* Animation for pulse effect */
        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 
                    0 10px 40px -10px rgba(99, 102, 241, 0.3),
                    0 0 0 0 rgba(99, 102, 241, 0.4);
            }
            50% {
                box-shadow: 
                    0 10px 40px -10px rgba(99, 102, 241, 0.5),
                    0 0 0 10px rgba(99, 102, 241, 0);
            }
        }
        
        .function-card-modern.pulse {
            animation: pulse-glow 2s infinite !important;
        }
    `;
    
    // Function to create and inject styles in multiple ways
    function forceInjectStyles() {
        // Method 1: Create style element in head
        const styleId = 'force-modern-styles-' + Date.now();
        let styleElement = document.createElement('style');
        styleElement.id = styleId;
        styleElement.innerHTML = MODERN_STYLES;
        document.head.appendChild(styleElement);
        
        // Method 2: Create style element at the end of body
        let bodyStyle = document.createElement('style');
        bodyStyle.innerHTML = MODERN_STYLES;
        document.body.appendChild(bodyStyle);
        
        // Method 3: Use CSS variables that can't be overridden
        const root = document.documentElement;
        root.style.setProperty('--function-card-bg', 'linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%)', 'important');
        root.style.setProperty('--function-card-border', '1px solid rgba(99, 102, 241, 0.4)', 'important');
        root.style.setProperty('--function-card-radius', '24px', 'important');
        root.style.setProperty('--function-card-shadow', '0 10px 40px -10px rgba(99, 102, 241, 0.3)', 'important');
        
        // Method 4: Apply inline styles directly to elements
        applyInlineStyles();
        
        // Method 5: Create shadow DOM for critical elements (if needed)
        // This is commented out as it might break functionality
        // createShadowDOMElements();
        
        // Method 6: Override any new styles that get added
        observeAndOverrideStyles();
    }
    
    // Apply inline styles directly to elements
    function applyInlineStyles() {
        const functionCards = document.querySelectorAll('.function-card-modern');
        
        functionCards.forEach((card, index) => {
            // Apply inline styles with maximum priority
            const inlineStyles = {
                'background': 'linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%)',
                'backdrop-filter': 'blur(20px)',
                '-webkit-backdrop-filter': 'blur(20px)',
                'border': '1px solid rgba(99, 102, 241, 0.4)',
                'border-radius': '24px',
                'padding': '32px',
                'position': 'relative',
                'overflow': 'hidden',
                'transition': 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)',
                'margin-bottom': '24px',
                'box-shadow': '0 10px 40px -10px rgba(99, 102, 241, 0.3), 0 0 0 1px rgba(99, 102, 241, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.1)',
                'transform': 'translateZ(0)',
                'will-change': 'transform, box-shadow'
            };
            
            // Apply each style with !important
            Object.entries(inlineStyles).forEach(([property, value]) => {
                card.style.setProperty(property, value, 'important');
            });
            
            // Add data attribute to track styled elements
            card.setAttribute('data-modern-styled', 'true');
            card.setAttribute('data-style-index', index);
            
            // Add mouse tracking for gradient effect
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = ((e.clientX - rect.left) / rect.width) * 100;
                const y = ((e.clientY - rect.top) / rect.height) * 100;
                card.style.setProperty('--mouse-x', `${x}%`);
                card.style.setProperty('--mouse-y', `${y}%`);
            });
        });
        
        // Style buttons
        const buttons = document.querySelectorAll('.btn-gradient-primary, button[wire\\:click*="AddingFunction"]');
        buttons.forEach(button => {
            const buttonStyles = {
                'background': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'color': 'white',
                'padding': '12px 24px',
                'border-radius': '12px',
                'font-weight': '600',
                'transition': 'all 0.3s ease',
                'display': 'inline-flex',
                'align-items': 'center',
                'border': 'none',
                'box-shadow': '0 4px 15px -3px rgba(102, 126, 234, 0.5)',
                'text-shadow': '0 1px 2px rgba(0, 0, 0, 0.1)'
            };
            
            Object.entries(buttonStyles).forEach(([property, value]) => {
                button.style.setProperty(property, value, 'important');
            });
        });
    }
    
    // Observer to catch and override any style changes
    function observeAndOverrideStyles() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    const element = mutation.target;
                    if (element.classList.contains('function-card-modern') && 
                        element.getAttribute('data-modern-styled') === 'true') {
                        // Re-apply our styles if they were changed
                        setTimeout(() => applyInlineStyles(), 0);
                    }
                }
                
                // Check for new elements
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) { // Element node
                            if (node.classList && node.classList.contains('function-card-modern')) {
                                setTimeout(() => applyInlineStyles(), 0);
                            }
                            // Check children
                            const newCards = node.querySelectorAll('.function-card-modern');
                            if (newCards.length > 0) {
                                setTimeout(() => applyInlineStyles(), 0);
                            }
                        }
                    });
                }
            });
        });
        
        // Observe the entire document for changes
        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['style', 'class'],
            childList: true,
            subtree: true
        });
    }
    
    // Initialize everything
    function initialize() {
        console.log('🎨 Force Modern Styles: Initializing...');
        
        // Initial injection
        forceInjectStyles();
        
        // Re-inject styles periodically to ensure they stay
        setInterval(forceInjectStyles, 1000);
        
        // Re-apply inline styles frequently
        setInterval(applyInlineStyles, 500);
        
        // Add global function for manual refresh
        window.applyModernStyles = function() {
            console.log('🎨 Manually applying modern styles...');
            forceInjectStyles();
            // Add pulse effect temporarily
            document.querySelectorAll('.function-card-modern').forEach(card => {
                card.classList.add('pulse');
                setTimeout(() => card.classList.remove('pulse'), 2000);
            });
        };
        
        console.log('✅ Force Modern Styles: Ready! Use window.applyModernStyles() to refresh.');
    }
    
    // Wait for DOM and Livewire to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
    
    // Also reinitialize on Livewire updates
    if (window.Livewire) {
        window.Livewire.on('render', () => {
            setTimeout(applyInlineStyles, 100);
        });
    }
    
    // Listen for Alpine.js if present
    if (window.Alpine) {
        document.addEventListener('alpine:initialized', () => {
            setTimeout(applyInlineStyles, 100);
        });
    }
})();