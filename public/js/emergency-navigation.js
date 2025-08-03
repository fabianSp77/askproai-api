/**
 * Emergency Navigation - Direct URL navigation when clicks don't work
 */

console.warn('🚨 EMERGENCY NAVIGATION ACTIVATED - Creating manual navigation panel');

(function() {
    'use strict';
    
    // Create emergency navigation panel
    const panel = document.createElement('div');
    panel.id = 'emergency-nav-panel';
    panel.style.cssText = `
        position: fixed;
        top: 10px;
        right: 10px;
        background: white;
        border: 2px solid red;
        padding: 20px;
        z-index: 999999;
        box-shadow: 0 0 20px rgba(0,0,0,0.3);
        max-width: 300px;
        max-height: 80vh;
        overflow-y: auto;
    `;
    
    panel.innerHTML = `
        <h3 style="margin-top:0; color:red;">🚨 Emergency Navigation</h3>
        <p style="font-size:12px; color:#666;">Click issues detected - use these direct links:</p>
        <style>
            #emergency-nav-panel a {
                display: block;
                padding: 8px 12px;
                margin: 5px 0;
                background: #3B82F6;
                color: white !important;
                text-decoration: none;
                border-radius: 4px;
                cursor: pointer !important;
                pointer-events: auto !important;
            }
            #emergency-nav-panel a:hover {
                background: #2563EB;
            }
            #emergency-nav-panel button {
                background: #EF4444;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 4px;
                cursor: pointer;
                margin-top: 10px;
            }
        </style>
    `;
    
    // Find all navigation links
    const links = [];
    
    // Sidebar navigation
    document.querySelectorAll('.fi-sidebar-nav a').forEach(link => {
        const text = link.textContent.trim();
        const href = link.getAttribute('href');
        if (href && text) {
            links.push({text, href});
        }
    });
    
    // Add any other important links
    document.querySelectorAll('a[wire\\:navigate]').forEach(link => {
        const text = link.textContent.trim();
        const href = link.getAttribute('href');
        if (href && text && !links.find(l => l.href === href)) {
            links.push({text, href});
        }
    });
    
    // Add manual common routes
    const commonRoutes = [
        {text: '🏠 Dashboard', href: '/admin'},
        {text: '📊 Calls', href: '/admin/calls'},
        {text: '👥 Customers', href: '/admin/customers'},
        {text: '📅 Appointments', href: '/admin/appointments'},
        {text: '🏢 Companies', href: '/admin/companies'},
        {text: '🏪 Branches', href: '/admin/branches'},
        {text: '👷 Staff', href: '/admin/staff'},
        {text: '🌐 Language Settings', href: '/admin/language-settings'},
        {text: '🤖 AI Call Center', href: '/admin/a-i-call-center'},
        {text: '⚙️ Retell Config', href: '/admin/retell-configuration-center'},
        {text: '🚪 Logout', href: '/admin/logout'}
    ];
    
    // Merge and deduplicate
    commonRoutes.forEach(route => {
        if (!links.find(l => l.href === route.href)) {
            links.push(route);
        }
    });
    
    // Create links in panel
    const linksHtml = links.map(link => 
        `<a href="${link.href}" onclick="window.location.href='${link.href}'; return false;">${link.text}</a>`
    ).join('');
    
    panel.innerHTML += linksHtml;
    
    // Add close button
    panel.innerHTML += `
        <button onclick="document.getElementById('emergency-nav-panel').remove()">
            Close Emergency Nav
        </button>
    `;
    
    // Add to page
    document.body.appendChild(panel);
    
    // Also try to fix navigation directly
    function forceNavigation() {
        document.querySelectorAll('a[href]').forEach(link => {
            const href = link.getAttribute('href');
            if (href && !link.dataset.emergencyFixed) {
                link.dataset.emergencyFixed = 'true';
                
                // Remove all event listeners and add direct navigation
                const newLink = link.cloneNode(true);
                newLink.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Emergency navigation to:', href);
                    window.location.href = href;
                    return false;
                };
                link.parentNode.replaceChild(newLink, link);
            }
        });
    }
    
    forceNavigation();
    
    // Run periodically
    setInterval(forceNavigation, 2000);
    
    console.warn('Emergency navigation panel created. Use the red panel on the right to navigate.');
    
    // Expose for manual use
    window.emergencyNavigate = function(url) {
        console.log('Emergency navigating to:', url);
        window.location.href = url;
    };
})();