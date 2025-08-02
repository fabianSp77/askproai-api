<?php

namespace App\Http\Middleware;

use App\Services\QueryPerformanceMonitor;
use Closure;
use Illuminate\Http\Request;

class QueryPerformanceMiddleware
{
    protected QueryPerformanceMonitor $monitor;

    public function __construct(QueryPerformanceMonitor $monitor)
    {
        $this->monitor = $monitor;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Start monitoring
        $this->monitor->start();

        // Process request
        $response = $next($request);

        // Add debug toolbar in development
        if (config('app.debug') && $request->wantsJson() === false) {
            $stats = $this->monitor->getStats();

            // Add stats to response headers
            $response->headers->set('X-DB-Query-Count', $stats['total_queries']);
            $response->headers->set('X-DB-Query-Time', $stats['total_time_ms'] . 'ms');

            // Inject performance widget into HTML response
            if ($response->headers->get('Content-Type') === 'text/html; charset=UTF-8' ||
                str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
                $content = $response->getContent();
                $widget = $this->generatePerformanceWidget($stats);

                // Inject before closing body tag
                $content = str_replace('</body>', $widget . '</body>', $content);
                $response->setContent($content);
            }
        }

        return $response;
    }

    /**
     * Generate performance widget HTML.
     */
    private function generatePerformanceWidget(array $stats): string
    {
        $bgColor = $stats['slow_queries'] > 0 ? '#ff6b6b' : ($stats['total_queries'] > 50 ? '#ffd93d' : '#51cf66');

        return <<<HTML
<!-- Query Performance Widget -->
<div id="query-performance-widget" style="
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: {$bgColor};
    color: #fff;
    padding: 10px 15px;
    border-radius: 5px;
    font-family: monospace;
    font-size: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    cursor: pointer;
    z-index: 9999;
" onclick="this.style.display='none'">
    <strong>DB Queries:</strong> {$stats['total_queries']}<br>
    <strong>Time:</strong> {$stats['total_time_ms']}ms<br>
    <strong>Avg:</strong> {$stats['average_time_ms']}ms
    {$this->getWarnings($stats)}
</div>
HTML;
    }

    /**
     * Get warning messages.
     */
    private function getWarnings(array $stats): string
    {
        $warnings = [];

        if ($stats['slow_queries'] > 0) {
            $warnings[] = "<br><strong style='color: #fff;'>⚠️ {$stats['slow_queries']} slow queries!</strong>";
        }

        if (! empty($stats['n_plus_one_suspects'])) {
            $count = count($stats['n_plus_one_suspects']);
            $warnings[] = "<br><strong style='color: #fff;'>⚠️ {$count} possible N+1 problems!</strong>";
        }

        if (count($stats['duplicate_queries']) > 5) {
            $warnings[] = "<br><strong style='color: #fff;'>⚠️ Many duplicate queries!</strong>";
        }

        return implode('', $warnings);
    }
}
