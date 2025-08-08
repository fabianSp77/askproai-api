// White-Label Demo JavaScript
// Quick theme switcher for demonstration

document.addEventListener('DOMContentLoaded', function() {
    // Check if we're in demo mode
    const urlParams = new URLSearchParams(window.location.search);
    const demoMode = urlParams.get('demo') === 'white-label';
    
    if (!demoMode) return;
    
    // Add demo banner
    const banner = document.createElement('div');
    banner.className = 'white-label-demo-banner';
    banner.innerHTML = '🎨 White-Label Demo Mode - Zeigt verschiedene Branding-Optionen';
    document.body.insertBefore(banner, document.body.firstChild);
    
    // Add theme switcher
    const themeSwitcher = document.createElement('div');
    themeSwitcher.className = 'theme-switcher';
    themeSwitcher.innerHTML = `
        <h4>🎨 Branding wechseln:</h4>
        <button data-theme="default" class="active">AskProAI Standard</button>
        <button data-theme="techpartner">TechPartner GmbH</button>
        <button data-theme="dr-schmidt">Praxis Dr. Schmidt</button>
        <button data-theme="kanzlei-mueller">Kanzlei Müller</button>
        <button data-theme="salon-bella">Salon Bella</button>
    `;
    document.body.appendChild(themeSwitcher);
    
    // Theme switching logic
    const buttons = themeSwitcher.querySelectorAll('button');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            buttons.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Remove all theme classes
            document.body.classList.remove('white-label-enabled', 'company-techpartner', 'company-dr-schmidt', 'company-kanzlei-mueller', 'company-salon-bella');
            
            // Apply selected theme
            const theme = this.dataset.theme;
            if (theme !== 'default') {
                document.body.classList.add('white-label-enabled', `company-${theme}`);
                
                // Update logo area (if exists)
                updateLogo(theme);
            }
            
            // Show notification
            showNotification(`Branding gewechselt zu: ${this.textContent}`);
        });
    });
    
    // Update logo function
    function updateLogo(theme) {
        const logoArea = document.querySelector('.fi-sidebar-header');
        if (!logoArea) return;
        
        const logos = {
            'techpartner': '<div class="white-label-logo">🏢 TechPartner</div>',
            'dr-schmidt': '<div class="white-label-logo">🏥 Dr. Schmidt</div>',
            'kanzlei-mueller': '<div class="white-label-logo">⚖️ Kanzlei Müller</div>',
            'salon-bella': '<div class="white-label-logo">💇‍♀️ Salon Bella</div>'
        };
        
        if (logos[theme]) {
            logoArea.innerHTML = logos[theme];
        }
    }
    
    // Show notification function
    function showNotification(message) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 4rem;
            right: 2rem;
            background: #10B981;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 2000);
    }
    
    // Add animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    
    // Auto-switch themes for impressive demo
    if (urlParams.get('auto') === 'true') {
        let currentIndex = 0;
        const themes = ['default', 'techpartner', 'dr-schmidt', 'kanzlei-mueller', 'salon-bella'];
        
        setInterval(() => {
            currentIndex = (currentIndex + 1) % themes.length;
            const button = themeSwitcher.querySelector(`button[data-theme="${themes[currentIndex]}"]`);
            if (button) button.click();
        }, 5000); // Switch every 5 seconds
    }
});

// Helper function to inject CSS if not already loaded
if (!document.querySelector('link[href*="white-label-demo.css"]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = '/css/white-label-demo.css';
    document.head.appendChild(link);
}