// Ultimate Force Modern Styles - The Nuclear Option
console.log('💣 ULTIMATE FORCE STYLES ACTIVATED - THIS WILL WORK!');

(function() {
    'use strict';
    
    // Create a style element and inject it at the VERY END of the document
    function injectStylesEverywhere() {
        // Remove any existing injected styles
        document.querySelectorAll('[data-force-styles]').forEach(el => el.remove());
        
        // CSS that CANNOT be overridden
        const ultimateCSS = `
            /* NUCLEAR OPTION - MAXIMUM SPECIFICITY */
            body .fi-page .retell-functions-wrapper .function-card-modern:not(#\\#):not(#\\#):not(#\\#) {
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.08) 100%) !important;
                backdrop-filter: blur(20px) !important;
                -webkit-backdrop-filter: blur(20px) !important;
                border: 1px solid rgba(99, 102, 241, 0.4) !important;
                border-radius: 20px !important;
                padding: 28px !important;
                position: relative !important;
                overflow: visible !important;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
                margin-bottom: 20px !important;
                box-shadow: 
                    0 10px 30px -10px rgba(99, 102, 241, 0.3),
                    0 20px 50px -20px rgba(139, 92, 246, 0.2),
                    inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
                transform: translateZ(0) !important;
                will-change: transform, box-shadow !important;
            }
            
            body.dark .fi-page .retell-functions-wrapper .function-card-modern:not(#\\#):not(#\\#):not(#\\#) {
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.25) 0%, rgba(139, 92, 246, 0.15) 100%) !important;
                border: 1px solid rgba(99, 102, 241, 0.5) !important;
                box-shadow: 
                    0 10px 40px -10px rgba(99, 102, 241, 0.4),
                    0 20px 60px -20px rgba(139, 92, 246, 0.3),
                    inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
            }
            
            body .fi-page .retell-functions-wrapper .function-card-modern:not(#\\#):not(#\\#):not(#\\#):hover {
                transform: translateY(-8px) scale(1.02) !important;
                box-shadow: 
                    0 20px 40px -15px rgba(99, 102, 241, 0.5),
                    0 30px 60px -30px rgba(139, 92, 246, 0.4),
                    inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
                border-color: rgba(99, 102, 241, 0.6) !important;
            }
            
            /* FORCE GRADIENT BADGES */
            .retell-functions-wrapper span.inline-flex[style*="Cal.com"]:not(#\\#):not(#\\#),
            .retell-functions-wrapper span.inline-flex:contains("Cal.com"):not(#\\#):not(#\\#) {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                color: white !important;
                padding: 6px 16px !important;
                border-radius: 24px !important;
                font-size: 13px !important;
                font-weight: 700 !important;
                letter-spacing: 0.5px !important;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
                box-shadow: 0 4px 12px -2px rgba(102, 126, 234, 0.4) !important;
            }
            
            .retell-functions-wrapper span.inline-flex[style*="Custom"]:not(#\\#):not(#\\#),
            .retell-functions-wrapper span.inline-flex:contains("Custom"):not(#\\#):not(#\\#) {
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
                color: white !important;
                padding: 6px 16px !important;
                border-radius: 24px !important;
                font-size: 13px !important;
                font-weight: 700 !important;
                box-shadow: 0 4px 12px -2px rgba(240, 147, 251, 0.4) !important;
            }
            
            /* FORCE GRADIENT BUTTONS */
            .retell-functions-wrapper button.btn-gradient-primary:not(#\\#):not(#\\#),
            .retell-functions-wrapper button[style*="gradient"]:not(#\\#):not(#\\#) {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                color: white !important;
                padding: 14px 28px !important;
                border-radius: 14px !important;
                font-weight: 700 !important;
                border: none !important;
                cursor: pointer !important;
                box-shadow: 0 8px 20px -6px rgba(102, 126, 234, 0.5) !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }
            
            .retell-functions-wrapper button.btn-gradient-primary:not(#\\#):not(#\\#):hover {
                transform: translateY(-3px) !important;
                box-shadow: 0 12px 28px -8px rgba(102, 126, 234, 0.6) !important;
            }
            
            /* GLASS MORPHISM OVERLAY */
            .function-card-modern::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(135deg, 
                    rgba(255, 255, 255, 0.1) 0%, 
                    rgba(255, 255, 255, 0.05) 50%, 
                    rgba(255, 255, 255, 0) 100%);
                border-radius: 20px;
                pointer-events: none;
                z-index: 1;
            }
        `;
        
        // Inject at the END of head
        const headStyle = document.createElement('style');
        headStyle.setAttribute('data-force-styles', 'head');
        headStyle.textContent = ultimateCSS;
        document.head.appendChild(headStyle);
        
        // Also inject at the END of body
        const bodyStyle = document.createElement('style');
        bodyStyle.setAttribute('data-force-styles', 'body');
        bodyStyle.textContent = ultimateCSS;
        document.body.appendChild(bodyStyle);
        
        console.log('💉 Styles injected at HEAD and BODY');
    }
    
    // Apply inline styles that OVERRIDE EVERYTHING
    function forceInlineStyles() {
        // Find all function cards
        const cards = document.querySelectorAll('.function-card-modern');
        console.log(`🎯 Found ${cards.length} function cards to style`);
        
        cards.forEach((card, index) => {
            // Remove any existing style attribute and rebuild from scratch
            card.removeAttribute('style');
            
            // Apply the ultimate inline style
            const ultimateStyle = `
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.08) 100%) !important;
                backdrop-filter: blur(20px) !important;
                -webkit-backdrop-filter: blur(20px) !important;
                border: 1px solid rgba(99, 102, 241, 0.4) !important;
                border-radius: 20px !important;
                padding: 28px !important;
                position: relative !important;
                overflow: visible !important;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
                margin-bottom: 20px !important;
                box-shadow: 0 10px 30px -10px rgba(99, 102, 241, 0.3), 0 20px 50px -20px rgba(139, 92, 246, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
                transform: translateZ(0) !important;
                will-change: transform, box-shadow !important;
            `;
            
            card.setAttribute('style', ultimateStyle);
            
            // Add a data attribute to track
            card.setAttribute('data-styled', 'ultimate');
            
            console.log(`✅ Styled card ${index + 1} with ultimate force`);
        });
        
        // Style badges
        const badges = document.querySelectorAll('.retell-functions-wrapper span.inline-flex');
        badges.forEach(badge => {
            const text = badge.textContent.trim();
            let badgeStyle = '';
            
            if (text.includes('Cal.com') || text.includes('cal')) {
                badgeStyle = `
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                    color: white !important;
                    padding: 6px 16px !important;
                    border-radius: 24px !important;
                    font-size: 13px !important;
                    font-weight: 700 !important;
                    letter-spacing: 0.5px !important;
                    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
                    box-shadow: 0 4px 12px -2px rgba(102, 126, 234, 0.4) !important;
                    display: inline-flex !important;
                `;
            } else if (text.includes('Custom') || text.includes('custom')) {
                badgeStyle = `
                    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
                    color: white !important;
                    padding: 6px 16px !important;
                    border-radius: 24px !important;
                    font-size: 13px !important;
                    font-weight: 700 !important;
                    box-shadow: 0 4px 12px -2px rgba(240, 147, 251, 0.4) !important;
                    display: inline-flex !important;
                `;
            } else if (text.includes('System') || text.includes('system')) {
                badgeStyle = `
                    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%) !important;
                    color: #744210 !important;
                    padding: 6px 16px !important;
                    border-radius: 24px !important;
                    font-size: 13px !important;
                    font-weight: 700 !important;
                    box-shadow: 0 4px 12px -2px rgba(251, 191, 36, 0.4) !important;
                    display: inline-flex !important;
                `;
            }
            
            if (badgeStyle) {
                badge.setAttribute('style', badgeStyle);
                console.log(`✅ Styled badge: ${text}`);
            }
        });
    }
    
    // Monitor and reapply
    function setupMonitoring() {
        // Watch for any changes
        const observer = new MutationObserver((mutations) => {
            let needsRestyle = false;
            
            mutations.forEach(mutation => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    const target = mutation.target;
                    if (target.classList.contains('function-card-modern') && 
                        target.getAttribute('data-styled') === 'ultimate' &&
                        !target.getAttribute('style').includes('blur(20px)')) {
                        needsRestyle = true;
                    }
                }
            });
            
            if (needsRestyle) {
                console.log('🔄 Style override detected, reapplying...');
                forceInlineStyles();
            }
        });
        
        // Observe the entire document
        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['style'],
            subtree: true
        });
    }
    
    // The ultimate application function
    function applyUltimateStyles() {
        console.log('🚀 APPLYING ULTIMATE FORCE STYLES');
        
        // Step 1: Inject CSS everywhere
        injectStylesEverywhere();
        
        // Step 2: Force inline styles
        setTimeout(forceInlineStyles, 100);
        
        // Step 3: Setup monitoring
        setTimeout(setupMonitoring, 200);
        
        console.log('✨ ULTIMATE STYLES APPLIED - THIS SHOULD WORK!');
    }
    
    // Apply immediately
    applyUltimateStyles();
    
    // Reapply periodically (every 500ms)
    setInterval(() => {
        const cards = document.querySelectorAll('.function-card-modern:not([data-styled="ultimate"])');
        if (cards.length > 0) {
            console.log('🔍 Found unstyled cards, applying ultimate force...');
            forceInlineStyles();
        }
    }, 500);
    
    // Make globally available
    window.applyModernStyles = applyUltimateStyles;
    window.forceUltimateStyles = forceInlineStyles;
    
    // Listen for tab changes
    document.addEventListener('click', (e) => {
        if (e.target.textContent && e.target.textContent.includes('Functions')) {
            setTimeout(applyUltimateStyles, 300);
        }
    });
    
    console.log('💪 ULTIMATE FORCE STYLES READY - Call applyModernStyles() or wait for auto-apply');
})();