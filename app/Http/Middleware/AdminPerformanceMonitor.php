<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminPerformanceMonitor
{
    public function handle(Request $request, Closure $next)
    {
        if (!str_contains($request->path(), 'admin')) {
            return $next($request);
        }
        
        $response = $next($request);
        
        if ($response->headers->get('content-type') === 'text/html') {
            $content = $response->getContent();
            
            $script = <<<'SCRIPT'
<script>
// Admin Performance Monitor
(function() {
    console.log('[Performance Monitor] Initializing...');
    
    // Override aggressive polling
    const originalSetInterval = window.setInterval;
    window.setInterval = function(fn, delay) {
        if (delay < 10000) {
            console.warn(`[Performance] Blocking aggressive interval: ${delay}ms`);
            delay = Math.max(delay, 30000); // Minimum 30 seconds
        }
        return originalSetInterval(fn, delay);
    };
    
    // Monitor Livewire
    if (window.Livewire) {
        let requestCount = 0;
        Livewire.hook('message.sent', () => {
            requestCount++;
            if (requestCount > 5) {
                console.error('[Performance] Too many Livewire requests!');
            }
        });
        
        // Reset counter every 10 seconds
        setInterval(() => requestCount = 0, 10000);
    }
    
    // Disable polling after 5 minutes of inactivity
    let lastActivity = Date.now();
    document.addEventListener('mousemove', () => lastActivity = Date.now());
    document.addEventListener('keypress', () => lastActivity = Date.now());
    
    setInterval(() => {
        if (Date.now() - lastActivity > 300000) { // 5 minutes
            document.querySelectorAll('[wire\\:poll]').forEach(el => {
                el.setAttribute('wire:poll.pause', '');
                console.log('[Performance] Paused polling due to inactivity');
            });
        }
    }, 60000); // Check every minute
})();
</script>
SCRIPT;
            
            $content = str_replace('</body>', $script . "\n</body>", $content);
            $response->setContent($content);
        }
        
        return $response;
    }
}