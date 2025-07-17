<!DOCTYPE html>
<html>
<head>
    <title>Dropdown Debug Test</title>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for Alpine and other scripts to load
            setTimeout(function() {
                console.log('=== DROPDOWN DEBUG TEST ===');
                
                // Check if debug function exists
                if (typeof window.debugStackingContexts === 'function') {
                    window.debugStackingContexts();
                } else {
                    console.error('Debug function not found. Make sure bulk-action-fix.js is loaded.');
                }
                
                // Additional manual checks
                const sidebar = document.querySelector('.fi-sidebar');
                const dropdowns = document.querySelectorAll('.fi-dropdown-panel');
                
                if (sidebar) {
                    const sidebarStyles = window.getComputedStyle(sidebar);
                    console.log('\nSidebar computed styles:', {
                        zIndex: sidebarStyles.zIndex,
                        position: sidebarStyles.position,
                        transform: sidebarStyles.transform
                    });
                }
                
                if (dropdowns.length > 0) {
                    console.log('\nFound', dropdowns.length, 'dropdown panels');
                    dropdowns.forEach((dropdown, i) => {
                        const styles = window.getComputedStyle(dropdown);
                        console.log(`Dropdown ${i}:`, {
                            zIndex: styles.zIndex,
                            position: styles.position,
                            display: styles.display,
                            visibility: styles.visibility
                        });
                    });
                } else {
                    console.log('\nNo dropdown panels found yet. Open a dropdown menu to test.');
                }
                
                // Check for transform issues
                const tableRows = document.querySelectorAll('.fi-ta-row');
                console.log('\nTable rows with potential transform issues:', tableRows.length);
                
                console.log('\n=== END DEBUG TEST ===');
                console.log('Now try opening a bulk action dropdown to see real-time debug info.');
            }, 2000);
        });
    </script>
</head>
<body>
    <h1>Dropdown Debug Test</h1>
    <p>Open your browser console (F12) to see the debug information.</p>
    <p>Then navigate to the admin panel and try opening a bulk action dropdown.</p>
    
    <h2>Quick Commands:</h2>
    <ul>
        <li><code>debugStackingContexts()</code> - Run full stacking context analysis</li>
        <li><code>document.querySelector('.fi-sidebar').style.zIndex</code> - Check sidebar z-index</li>
        <li><code>document.querySelector('.fi-dropdown-panel').style.zIndex</code> - Check dropdown z-index</li>
    </ul>
</body>
</html>