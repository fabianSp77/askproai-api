/**
 * BYPASS hook.js - Ultimate solution
 */

console.error('🚨🚨🚨 BYPASSING hook.js INTERFERENCE 🚨🚨🚨');

(function() {
    'use strict';
    
    // 1. Detect if hook.js is present
    if (window.overrideMethod || document.querySelector('script[src*="hook.js"]')) {
        console.error('⚠️ hook.js DETECTED - Applying countermeasures');
    }
    
    // 2. Create iframe navigation function
    window.iframeNavigate = function(url) {
        console.log('🚨 IFRAME NAVIGATION TO:', url);
        
        // Create hidden iframe
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        document.body.appendChild(iframe);
        
        // Navigate parent from iframe
        iframe.contentWindow.parent.location.href = url;
    };
    
    // 3. Create form submission navigation
    window.formNavigate = function(url) {
        console.log('🚨 FORM NAVIGATION TO:', url);
        
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = url;
        form.style.display = 'none';
        document.body.appendChild(form);
        form.submit();
    };
    
    // 4. Override ALL links with multiple fallback methods
    function ultimateFixLinks() {
        document.querySelectorAll('a[href]').forEach(link => {
            const url = link.href || link.getAttribute('href');
            if (!url || url === '#' || link.dataset.ultimateFixed) return;
            
            link.dataset.ultimateFixed = 'true';
            
            // Method 1: Direct assignment
            link.href = url;
            
            // Method 2: onclick with multiple fallbacks
            link.onclick = function(e) {
                console.log('🚨 ULTIMATE CLICK - Trying all methods for:', url);
                
                // Try everything
                try {
                    // Method A: Direct navigation
                    window.location.href = url;
                } catch (e1) {
                    try {
                        // Method B: Replace
                        window.location.replace(url);
                    } catch (e2) {
                        try {
                            // Method C: Assign
                            window.location.assign(url);
                        } catch (e3) {
                            try {
                                // Method D: Form submit
                                formNavigate(url);
                            } catch (e4) {
                                // Method E: Iframe
                                iframeNavigate(url);
                            }
                        }
                    }
                }
                
                return false;
            };
            
            // Method 3: Touchstart for mobile
            link.addEventListener('touchstart', function(e) {
                e.preventDefault();
                window.location.href = url;
            }, {passive: false});
            
            // Method 4: Mousedown as backup
            link.addEventListener('mousedown', function(e) {
                if (e.button === 0) {
                    e.preventDefault();
                    window.location.href = url;
                }
            }, {passive: false});
            
            // Method 5: Contextmenu (right-click) fallback
            link.addEventListener('contextmenu', function(e) {
                if (confirm('Navigation blocked. Navigate to ' + url + '?')) {
                    e.preventDefault();
                    window.location.href = url;
                }
            });
        });
    }
    
    // 5. Create keyboard navigation
    let linkIndex = 0;
    const navigableLinks = [];
    
    function updateNavigableLinks() {
        navigableLinks.length = 0;
        document.querySelectorAll('a[href]:not([href="#"])').forEach(link => {
            navigableLinks.push(link);
        });
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            e.preventDefault();
            updateNavigableLinks();
            
            if (e.shiftKey) {
                linkIndex = (linkIndex - 1 + navigableLinks.length) % navigableLinks.length;
            } else {
                linkIndex = (linkIndex + 1) % navigableLinks.length;
            }
            
            if (navigableLinks[linkIndex]) {
                navigableLinks[linkIndex].style.outline = '3px solid red';
                navigableLinks[linkIndex].scrollIntoView({block: 'center'});
            }
        }
        
        if (e.key === 'Enter' && navigableLinks[linkIndex]) {
            const url = navigableLinks[linkIndex].href;
            console.log('🚨 KEYBOARD NAVIGATION TO:', url);
            window.location.href = url;
        }
    });
    
    // 6. Create visual navigation helper
    const navHelper = document.createElement('div');
    navHelper.id = 'navigation-helper';
    navHelper.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 20px;
        background: yellow;
        border: 2px solid black;
        padding: 10px;
        z-index: 2147483647;
        font-size: 12px;
        max-width: 300px;
    `;
    navHelper.innerHTML = `
        <strong>🚨 Navigation Help:</strong><br>
        • Click not working? Try right-click<br>
        • Use TAB + ENTER to navigate<br>
        • Emergency menu: Alt+H<br>
        • <a href="#" onclick="window.formNavigate('/admin'); return false;">Test: Go to Dashboard</a>
    `;
    document.body.appendChild(navHelper);
    
    // Run fixes
    ultimateFixLinks();
    setInterval(ultimateFixLinks, 1000);
    
    console.error('🚨 ULTIMATE BYPASS ACTIVE - Try clicking, right-clicking, or Tab+Enter');
})();