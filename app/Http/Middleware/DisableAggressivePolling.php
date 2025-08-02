<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableAggressivePolling
{
    /**
     * Disable aggressive polling in admin panel to prevent browser overload
     */
    public function handle(Request $request, Closure $next)
    {
        // Inject performance monitoring script
        $response = $next($request);
        
        if ($response->headers->get('content-type') === 'text/html' && 
            str_contains($request->path(), 'admin')) {
            
            $content = $response->getContent();
            
            // Inject performance protection script before closing body tag
            $performanceScript = <<<'SCRIPT'
<script>
(function() {
    // Performance Protection Script
    console.log('[Performance] Initializing browser protection...');
    
    // Track all intervals and timeouts
    const intervals = new Set();
    const timeouts = new Set();
    let pollingWarningShown = false;
    
    // Override setInterval to prevent aggressive polling
    const originalSetInterval = window.setInterval;
    window.setInterval = function(callback, delay, ...args) {
        // Warn about aggressive polling (< 1 second)
        if (delay < 1000) {
            console.warn(`[Performance] Aggressive polling detected: ${delay}ms interval. Consider increasing to 5000ms+`);
            
            // Force minimum 1 second for very aggressive polling
            if (delay < 500) {
                console.error(`[Performance] BLOCKING: ${delay}ms interval is too aggressive. Forcing to 1000ms.`);
                delay = 1000;
            }
        }
        
        const id = originalSetInterval.call(this, callback, delay, ...args);
        intervals.add(id);
        
        // Show warning if too many intervals
        if (intervals.size > 10 && !pollingWarningShown) {
            pollingWarningShown = true;
            console.error(`[Performance] WARNING: ${intervals.size} active intervals detected! This may cause browser overload.`);
        }
        
        return id;
    };
    
    // Track clearInterval
    const originalClearInterval = window.clearInterval;
    window.clearInterval = function(id) {
        intervals.delete(id);
        return originalClearInterval.call(this, id);
    };
    
    // Monitor Livewire polling
    if (window.Livewire) {
        document.addEventListener('livewire:load', function() {
            console.log('[Performance] Monitoring Livewire components...');
            
            // Find all wire:poll elements
            const pollingElements = document.querySelectorAll('[wire\\:poll], [wire\\:poll\\.keep-alive]');
            pollingElements.forEach(el => {
                const pollAttribute = el.getAttribute('wire:poll') || el.getAttribute('wire:poll.keep-alive');
                if (pollAttribute) {
                    const match = pollAttribute.match(/\.(\d+)s/);
                    if (match && parseInt(match[1]) < 5) {
                        console.warn(`[Performance] Component polling every ${match[1]}s - consider increasing to 10s+`, el);
                    }
                }
            });
        });
    }
    
    // Performance metrics logger
    let metricsInterval = setInterval(() => {
        if (performance.memory) {
            const mb = Math.round(performance.memory.usedJSHeapSize / 1024 / 1024);
            if (mb > 500) {
                console.error(`[Performance] High memory usage: ${mb}MB`);
            }
        }
        
        // Check for DOM bloat
        const nodeCount = document.getElementsByTagName('*').length;
        if (nodeCount > 10000) {
            console.warn(`[Performance] High DOM node count: ${nodeCount}`);
        }
    }, 30000); // Check every 30 seconds
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        intervals.forEach(id => clearInterval(id));
        timeouts.forEach(id => clearTimeout(id));
        clearInterval(metricsInterval);
    });
    
    console.log('[Performance] Browser protection initialized');
})();
</script>
SCRIPT;
            
            $content = str_replace('</body>', $performanceScript . '</body>', $content);
            $response->setContent($content);
        }
        
        return $response;
    }
}