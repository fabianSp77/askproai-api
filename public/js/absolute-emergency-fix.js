/**
 * ABSOLUTE EMERGENCY FIX - No dependencies, just make it work
 */

// Wait for page to be ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', applyAbsoluteFix);
} else {
    applyAbsoluteFix();
}

function applyAbsoluteFix() {
    console.error('🔴 ABSOLUTE EMERGENCY FIX RUNNING');
    
    // 1. Create manual navigation menu
    try {
        const menu = document.createElement('div');
        menu.id = 'emergency-menu';
        menu.innerHTML = `
            <style>
                #emergency-menu {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    border: 3px solid red;
                    padding: 20px;
                    z-index: 2147483647;
                    box-shadow: 0 0 30px rgba(0,0,0,0.5);
                    max-height: 80vh;
                    overflow-y: auto;
                }
                #emergency-menu h3 {
                    margin: 0 0 10px 0;
                    color: red;
                    font-size: 18px;
                }
                #emergency-menu a {
                    display: block;
                    padding: 10px;
                    margin: 5px 0;
                    background: #3B82F6;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    font-weight: bold;
                }
                #emergency-menu a:hover {
                    background: #2563EB;
                }
                #emergency-menu button {
                    width: 100%;
                    padding: 10px;
                    background: red;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    font-weight: bold;
                    cursor: pointer;
                    margin-top: 10px;
                }
            </style>
            <h3>🚨 EMERGENCY NAVIGATION</h3>
            <p style="font-size:12px;">Click any link below:</p>
        `;
        
        // Add navigation links
        const links = [
            {url: '/admin', text: '🏠 Dashboard'},
            {url: '/admin/calls', text: '📞 Calls'},
            {url: '/admin/customers', text: '👥 Customers'},
            {url: '/admin/appointments', text: '📅 Appointments'},
            {url: '/admin/companies', text: '🏢 Companies'},
            {url: '/admin/branches', text: '🏪 Branches'},
            {url: '/admin/staff', text: '👷 Staff'},
            {url: '/admin/a-i-call-center', text: '🤖 AI Call Center'},
            {url: '/admin/language-settings', text: '🌐 Language Settings'},
            {url: '/admin/retell-configuration-center', text: '⚙️ Retell Config'},
            {url: '/admin/logout', text: '🚪 Logout'}
        ];
        
        links.forEach(link => {
            const a = document.createElement('a');
            a.href = link.url;
            a.textContent = link.text;
            a.onclick = function(e) {
                e.preventDefault();
                console.log('Navigating to:', link.url);
                window.location.href = link.url;
                return false;
            };
            menu.appendChild(a);
        });
        
        // Add close button
        const closeBtn = document.createElement('button');
        closeBtn.textContent = 'Close Emergency Menu';
        closeBtn.onclick = function() {
            document.getElementById('emergency-menu').remove();
        };
        menu.appendChild(closeBtn);
        
        // Add to page
        document.body.appendChild(menu);
        console.log('✅ Emergency menu created');
    } catch (e) {
        console.error('Failed to create emergency menu:', e);
    }
    
    // 2. Try to fix all links
    try {
        const allLinks = document.querySelectorAll('a');
        let fixedCount = 0;
        
        allLinks.forEach(link => {
            if (link.href || link.getAttribute('href')) {
                const url = link.href || link.getAttribute('href');
                
                // Clone and replace to remove all event listeners
                const newLink = link.cloneNode(true);
                newLink.style.cursor = 'pointer';
                newLink.style.pointerEvents = 'auto';
                
                // Simple click handler
                newLink.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Link clicked, navigating to:', url);
                    window.location.href = url;
                    return false;
                };
                
                // Replace original
                if (link.parentNode) {
                    link.parentNode.replaceChild(newLink, link);
                    fixedCount++;
                }
            }
        });
        
        console.log(`✅ Fixed ${fixedCount} links`);
    } catch (e) {
        console.error('Failed to fix links:', e);
    }
    
    // 3. Add keyboard navigation
    document.addEventListener('keydown', function(e) {
        // Alt+D = Dashboard
        if (e.altKey && e.key === 'd') {
            window.location.href = '/admin';
        }
        // Alt+C = Calls
        if (e.altKey && e.key === 'c') {
            window.location.href = '/admin/calls';
        }
        // Alt+H = Help (show menu)
        if (e.altKey && e.key === 'h') {
            alert('Keyboard shortcuts:\nAlt+D = Dashboard\nAlt+C = Calls\nAlt+U = Customers\nAlt+A = Appointments');
        }
    });
    
    console.error('🔴 EMERGENCY FIX COMPLETE - Use red menu or Alt+H for help');
}