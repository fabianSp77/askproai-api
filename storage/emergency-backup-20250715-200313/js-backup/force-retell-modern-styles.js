// Force apply modern styles to Retell Ultimate Dashboard
(function() {
    'use strict';
    
    // Modern styles definition
    const modernStyles = {
        functionCard: `
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%) !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(99, 102, 241, 0.3) !important;
            border-radius: 16px !important;
            padding: 24px !important;
            position: relative !important;
            overflow: hidden !important;
            transition: all 0.3s ease !important;
            margin-bottom: 16px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
        `,
        glassCard: `
            background: rgba(255, 255, 255, 0.05) !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            transition: all 0.3s ease !important;
        `,
        gradientButton: `
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            padding: 12px 24px !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            border: none !important;
            cursor: pointer !important;
        `,
        badge: {
            cal: `
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                color: white !important;
                padding: 4px 12px !important;
                border-radius: 20px !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                display: inline-flex !important;
            `,
            custom: `
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
                color: white !important;
                padding: 4px 12px !important;
                border-radius: 20px !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                display: inline-flex !important;
            `,
            system: `
                background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%) !important;
                color: #744210 !important;
                padding: 4px 12px !important;
                border-radius: 20px !important;
                font-size: 12px !important;
                font-weight: 600 !important;
                display: inline-flex !important;
            `
        }
    };

    function applyModernStyles() {
        console.log('🎨 Applying modern styles to Retell Dashboard...');
        
        // Apply to function cards
        const functionCards = document.querySelectorAll('.function-card-modern, [class*="function-card"], [x-show*="expandedFunctions"]');
        functionCards.forEach(card => {
            if (card.closest('[x-show="selectedTab === \'functions\'"]')) {
                card.style.cssText = modernStyles.functionCard;
                console.log('✅ Applied modern style to function card');
            }
        });
        
        // Apply to glass cards
        const glassCards = document.querySelectorAll('.glass-card, .rounded-xl.p-6.border-2');
        glassCards.forEach(card => {
            card.style.cssText = modernStyles.glassCard;
        });
        
        // Apply to gradient buttons
        const buttons = document.querySelectorAll('.btn-gradient-primary, [class*="gradient"]');
        buttons.forEach(btn => {
            if (btn.tagName === 'BUTTON' || btn.tagName === 'A') {
                btn.style.cssText = modernStyles.gradientButton;
            }
        });
        
        // Apply to badges
        const badges = document.querySelectorAll('.function-badge, .badge-gradient-cal, .badge-gradient-custom, span.inline-flex.items-center.px-3.py-1');
        badges.forEach(badge => {
            const text = badge.textContent.trim().toLowerCase();
            if (text.includes('cal')) {
                badge.style.cssText = modernStyles.badge.cal;
            } else if (text.includes('custom')) {
                badge.style.cssText = modernStyles.badge.custom;
            } else if (text.includes('system')) {
                badge.style.cssText = modernStyles.badge.system;
            }
        });
        
        // Apply to specific function containers
        const functionContainers = document.querySelectorAll('[x-show="selectedTab === \'functions\'"] .space-y-4 > div');
        functionContainers.forEach(container => {
            if (container.querySelector('h4') && container.querySelector('p')) {
                container.style.cssText = modernStyles.functionCard;
            }
        });
    }

    // Apply styles on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyModernStyles);
    } else {
        applyModernStyles();
    }

    // Apply styles after Livewire updates
    if (window.Livewire) {
        Livewire.hook('message.processed', (message, component) => {
            setTimeout(applyModernStyles, 100);
        });
        
        // Also listen for element updates
        Livewire.hook('element.updated', (el, component) => {
            setTimeout(applyModernStyles, 50);
        });
    }

    // Apply styles on Alpine.js updates
    if (window.Alpine) {
        document.addEventListener('alpine:initialized', () => {
            setTimeout(applyModernStyles, 200);
        });
    }

    // Reapply periodically to catch any dynamic updates
    setInterval(applyModernStyles, 2000);
    
    // Add global CSS rules
    const style = document.createElement('style');
    style.textContent = `
        /* Force dark mode compatibility */
        .dark .function-card-modern,
        .dark [x-show="selectedTab === 'functions'"] .space-y-4 > div {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(139, 92, 246, 0.1) 100%) !important;
            border: 1px solid rgba(99, 102, 241, 0.4) !important;
        }
        
        /* Ensure hover effects */
        .function-card-modern:hover,
        [x-show="selectedTab === 'functions'"] .space-y-4 > div:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            border-color: rgba(99, 102, 241, 0.5) !important;
        }
    `;
    document.head.appendChild(style);
    
    console.log('✨ Modern styles force-loader initialized');
})();