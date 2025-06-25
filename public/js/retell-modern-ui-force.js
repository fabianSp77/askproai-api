// Retell Modern UI - Aggressive Force Apply
console.log('🚀 Retell Modern UI Force Loader Initialized');

// Define the modern styles
const MODERN_STYLES = {
    functionCard: {
        background: 'linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%)',
        backdropFilter: 'blur(10px)',
        WebkitBackdropFilter: 'blur(10px)',
        border: '1px solid rgba(99, 102, 241, 0.3)',
        borderRadius: '16px',
        padding: '24px',
        position: 'relative',
        overflow: 'hidden',
        transition: 'all 0.3s ease',
        marginBottom: '16px',
        boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)'
    },
    functionCardDark: {
        background: 'linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(139, 92, 246, 0.1) 100%)',
        border: '1px solid rgba(99, 102, 241, 0.4)'
    },
    badge: {
        cal: {
            background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            color: 'white',
            padding: '4px 12px',
            borderRadius: '20px',
            fontSize: '12px',
            fontWeight: '600',
            display: 'inline-flex'
        },
        custom: {
            background: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            color: 'white',
            padding: '4px 12px',
            borderRadius: '20px',
            fontSize: '12px',
            fontWeight: '600',
            display: 'inline-flex'
        },
        system: {
            background: 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
            color: '#744210',
            padding: '4px 12px',
            borderRadius: '20px',
            fontSize: '12px',
            fontWeight: '600',
            display: 'inline-flex'
        }
    }
};

function applyStylesToElement(element, styles) {
    // Build CSS text with !important flags
    let cssText = '';
    Object.keys(styles).forEach(key => {
        const cssProperty = key.replace(/([A-Z])/g, '-$1').toLowerCase();
        cssText += `${cssProperty}: ${styles[key]} !important; `;
    });
    
    // Apply all styles at once with !important
    element.style.cssText = cssText;
    
    // Force repaint
    element.style.display = 'none';
    element.offsetHeight; // Trigger reflow
    element.style.display = '';
}

function applyModernStyles() {
    console.log('🎨 Applying Modern Styles...');
    
    // Check if we're in dark mode
    const isDarkMode = document.documentElement.classList.contains('dark');
    
    // Find all function cards - be more specific
    const functionCards = document.querySelectorAll('.function-card-modern');
    console.log(`Found ${functionCards.length} function cards with .function-card-modern class`);
    
    // If no cards found with the class, try a more general selector
    if (functionCards.length === 0) {
        const generalCards = document.querySelectorAll('[x-show*="functions"] .space-y-4 > div > div.group');
        console.log(`Found ${generalCards.length} function cards with general selector`);
        generalCards.forEach((card, index) => {
            console.log(`Styling general card ${index + 1}`);
            applyStylesToElement(card, MODERN_STYLES.functionCard);
            if (isDarkMode) {
                applyStylesToElement(card, MODERN_STYLES.functionCardDark);
            }
        });
    }
    
    functionCards.forEach((card, index) => {
        // Check if this is within the functions tab
        const functionsTab = card.closest('[x-show*="functions"]');
        if (functionsTab) {
            console.log(`Styling function card ${index + 1}`);
            
            // Apply base styles
            applyStylesToElement(card, MODERN_STYLES.functionCard);
            
            // Apply dark mode styles if needed
            if (isDarkMode) {
                applyStylesToElement(card, MODERN_STYLES.functionCardDark);
            }
            
            // Add hover effect
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
                this.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)';
                this.style.borderColor = 'rgba(99, 102, 241, 0.5)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';
                this.style.borderColor = 'rgba(99, 102, 241, 0.3)';
            });
        }
    });
    
    // Find and style badges
    const badges = document.querySelectorAll('span.inline-flex.items-center.px-3.py-1, .function-badge');
    console.log(`Found ${badges.length} badges`);
    
    badges.forEach((badge, index) => {
        const text = badge.textContent.trim().toLowerCase();
        console.log(`Badge ${index + 1} text: "${text}"`);
        
        if (text.includes('cal')) {
            applyStylesToElement(badge, MODERN_STYLES.badge.cal);
        } else if (text.includes('custom')) {
            applyStylesToElement(badge, MODERN_STYLES.badge.custom);
        } else if (text.includes('system')) {
            applyStylesToElement(badge, MODERN_STYLES.badge.system);
        }
    });
    
    // Style gradient buttons
    const buttons = document.querySelectorAll('.btn-gradient-primary, button[style*="gradient"]');
    console.log(`Found ${buttons.length} gradient buttons`);
    
    buttons.forEach(button => {
        if (!button.style.background || !button.style.background.includes('gradient')) {
            button.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            button.style.color = 'white';
            button.style.padding = '12px 24px';
            button.style.borderRadius = '12px';
            button.style.fontWeight = '600';
            button.style.border = 'none';
            button.style.cursor = 'pointer';
        }
    });
    
    console.log('✅ Modern styles applied!');
}

// Make function globally available
window.applyModernStyles = applyModernStyles;
window.applyRetellModernStyles = applyModernStyles;

// Apply on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(applyModernStyles, 500);
    });
} else {
    setTimeout(applyModernStyles, 500);
}

// Apply when Alpine initializes
document.addEventListener('alpine:init', () => {
    console.log('Alpine initialized, applying styles...');
    setTimeout(applyModernStyles, 1000);
});

// Apply on Livewire updates
if (window.Livewire) {
    Livewire.hook('message.processed', () => {
        console.log('Livewire update detected, reapplying styles...');
        setTimeout(applyModernStyles, 200);
    });
}

// Watch for tab changes
setInterval(() => {
    const functionsTab = document.querySelector('[x-show*="functions"]');
    if (functionsTab && functionsTab.style.display !== 'none') {
        const needsStyles = document.querySelector('.function-card-modern:not([data-styled])');
        if (needsStyles) {
            console.log('Found unstyled elements, applying...');
            applyModernStyles();
            // Mark as styled
            document.querySelectorAll('.function-card-modern').forEach(el => {
                el.setAttribute('data-styled', 'true');
            });
        }
    }
}, 1000);

console.log('✨ Modern UI Force Loader ready! Click the refresh icon or call applyModernStyles() to apply styles.');