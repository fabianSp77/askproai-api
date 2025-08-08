<?php
// Debug Mobile Sidebar
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Mobile Sidebar</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .debug-box { background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .success { background: #d1fae5; color: #065f46; }
        .error { background: #fee2e2; color: #991b1b; }
        .info { background: #dbeafe; color: #1e40af; }
        pre { background: #1f2937; color: #10b981; padding: 15px; border-radius: 6px; overflow-x: auto; }
        button { padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; margin: 5px; }
        button:hover { background: #2563eb; }
        .mobile-frame { width: 375px; height: 667px; border: 2px solid #333; border-radius: 20px; margin: 20px auto; position: relative; overflow: hidden; }
        .mobile-frame iframe { width: 100%; height: 100%; border: none; }
    </style>
</head>
<body>
    <h1>Debug Mobile Sidebar Text</h1>
    
    <div class="debug-box">
        <h3>Issue: "In der Mobile Variante sehe ich nur Icons und kein Text"</h3>
        <p>Testing mobile sidebar text visibility fixes</p>
    </div>
    
    <div class="debug-box info">
        <h3>Applied Fixes:</h3>
        <ul>
            <li>✅ Created <code>mobile-sidebar-text-fix.css</code> with forced text visibility</li>
            <li>✅ Added <code>mobile-sidebar-text-fix.js</code> for Alpine override</li>
            <li>✅ Included inline fix in <code>base.blade.php</code></li>
            <li>✅ Updated Alpine store to sync mobile state</li>
        </ul>
    </div>
    
    <div>
        <button onclick="testInlineStyles()">Test Inline Styles</button>
        <button onclick="testAlpineState()">Test Alpine State</button>
        <button onclick="injectFix()">Inject Emergency Fix</button>
    </div>
    
    <div id="output" class="debug-box" style="margin-top: 20px;">
        <pre id="debug-output">Ready for testing...</pre>
    </div>
    
    <div class="mobile-frame">
        <iframe id="admin-frame" src="/admin"></iframe>
    </div>
    
    <script>
        const output = document.getElementById('debug-output');
        
        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            output.innerHTML += `\n[${timestamp}] ${type.toUpperCase()}: ${message}`;
            output.scrollTop = output.scrollHeight;
        }
        
        function testInlineStyles() {
            log('Testing inline styles in iframe...', 'info');
            
            try {
                const iframe = document.getElementById('admin-frame');
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                
                // Check for mobile sidebar fix
                const hasFix = iframeDoc.querySelector('style')?.textContent.includes('fi-sidebar-open');
                log(`Mobile fix styles present: ${hasFix}`, hasFix ? 'success' : 'error');
                
                // Check Alpine store
                const hasAlpine = iframe.contentWindow.Alpine !== undefined;
                log(`Alpine.js loaded: ${hasAlpine}`, hasAlpine ? 'success' : 'error');
                
                if (hasAlpine && iframe.contentWindow.Alpine.store('sidebar')) {
                    const sidebarStore = iframe.contentWindow.Alpine.store('sidebar');
                    log(`Sidebar store isOpen: ${sidebarStore.isOpen}`, 'info');
                }
                
            } catch (e) {
                log(`Error accessing iframe: ${e.message}`, 'error');
            }
        }
        
        function testAlpineState() {
            log('Testing Alpine state...', 'info');
            
            try {
                const iframe = document.getElementById('admin-frame');
                const iframeWin = iframe.contentWindow;
                
                if (iframeWin.Alpine && iframeWin.Alpine.store('sidebar')) {
                    const store = iframeWin.Alpine.store('sidebar');
                    log(`Current isOpen: ${store.isOpen}`, 'info');
                    
                    // Try toggling
                    store.toggle();
                    log(`After toggle: ${store.isOpen}`, 'info');
                    
                    // Check body class
                    const bodyClass = iframeWin.document.body.className;
                    log(`Body classes: ${bodyClass}`, 'info');
                    
                    // Check text visibility
                    const labels = iframeWin.document.querySelectorAll('.fi-sidebar-item-label');
                    log(`Found ${labels.length} sidebar labels`, 'info');
                    
                    if (labels.length > 0) {
                        const firstLabel = labels[0];
                        const styles = iframeWin.getComputedStyle(firstLabel);
                        log(`First label display: ${styles.display}, opacity: ${styles.opacity}`, 'info');
                    }
                } else {
                    log('Alpine sidebar store not found', 'error');
                }
            } catch (e) {
                log(`Error: ${e.message}`, 'error');
            }
        }
        
        function injectFix() {
            log('Injecting emergency fix...', 'warning');
            
            try {
                const iframe = document.getElementById('admin-frame');
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                
                // Inject aggressive CSS
                const style = iframeDoc.createElement('style');
                style.textContent = `
                    @media (max-width: 1024px) {
                        body.fi-sidebar-open .fi-sidebar * {
                            opacity: 1 !important;
                            visibility: visible !important;
                        }
                        body.fi-sidebar-open .fi-sidebar span {
                            display: inline-block !important;
                        }
                        body.fi-sidebar-open .fi-sidebar [x-show] {
                            display: initial !important;
                        }
                    }
                `;
                iframeDoc.head.appendChild(style);
                
                // Force Alpine state
                if (iframe.contentWindow.Alpine && iframe.contentWindow.Alpine.store('sidebar')) {
                    iframe.contentWindow.Alpine.store('sidebar').isOpen = true;
                }
                
                // Force display on all labels
                const labels = iframeDoc.querySelectorAll('.fi-sidebar-item-label, .fi-sidebar-group-label');
                labels.forEach(label => {
                    label.style.display = 'inline-block';
                    label.style.opacity = '1';
                    label.style.visibility = 'visible';
                });
                
                log(`Emergency fix applied! Forced ${labels.length} labels visible`, 'success');
                
            } catch (e) {
                log(`Error injecting fix: ${e.message}`, 'error');
            }
        }
        
        // Auto-test when iframe loads
        document.getElementById('admin-frame').onload = function() {
            log('Admin panel loaded in iframe', 'success');
            setTimeout(() => {
                testInlineStyles();
            }, 1000);
        };
    </script>
</body>
</html>