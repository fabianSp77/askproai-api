<?php
/**
 * Direct injection of click fix into page
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Inject Fix Test</title>
    <meta charset="UTF-8">
    <script>
        // IMMEDIATE FIX - Runs before anything else
        document.addEventListener('DOMContentLoaded', function() {
            console.error('INJECT FIX: Forcing all clicks to work');
            
            // Nuclear style injection
            const style = document.createElement('style');
            style.innerHTML = `
                * { pointer-events: auto !important; }
                *::before, *::after { display: none !important; }
                a, button { 
                    pointer-events: auto !important; 
                    cursor: pointer !important;
                    position: relative !important;
                    z-index: 999999 !important;
                }
            `;
            document.head.appendChild(style);
            
            // Force clicks on everything
            document.querySelectorAll('a, button, input').forEach(el => {
                el.style.pointerEvents = 'auto';
                el.onclick = function() { 
                    console.log('CLICKED:', this); 
                    return true;
                };
            });
        });
    </script>
</head>
<body>
    <h1>Injection Test</h1>
    <p>This page injects the fix directly. Test if these work:</p>
    
    <a href="/admin" style="background: blue; color: white; padding: 10px; display: inline-block;">Go to Admin</a>
    
    <button onclick="alert('Button works!')">Test Button</button>
    
    <p>If these work, the fix can be applied to your admin panel.</p>
</body>
</html>