<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Monitoring\QueryMonitor;
use Illuminate\Support\Facades\Log;

class MonitorQueries
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only monitor in development and when explicitly enabled
        if (!config('app.debug') || !$request->header('X-Monitor-Queries')) {
            return $next($request);
        }
        
        // Start monitoring
        QueryMonitor::start();
        
        // Process request
        $response = $next($request);
        
        // Stop monitoring and get results
        $results = QueryMonitor::stop();
        
        // Log N+1 queries if detected
        if (!empty($results['n1_queries'])) {
            Log::warning('N+1 queries detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'n1_queries' => $results['n1_queries'],
            ]);
        }
        
        // Add query stats to response headers
        if ($response->getStatusCode() < 400) {
            $response->headers->set('X-Query-Count', $results['total_queries']);
            $response->headers->set('X-Query-Time', round($results['total_time_ms'], 2) . 'ms');
            $response->headers->set('X-N1-Query-Count', count($results['n1_queries']));
            
            // Add detailed stats in development
            if (config('app.debug')) {
                $response->headers->set('X-Query-Stats', json_encode([
                    'total' => $results['total_queries'],
                    'time_ms' => round($results['total_time_ms'], 2),
                    'n1_count' => count($results['n1_queries']),
                    'slow_count' => count($results['slow_queries']),
                    'duplicate_count' => count($results['duplicate_queries']),
                ]));
            }
        }
        
        return $response;
    }
}