<?php

namespace App\Http\Middleware;

use App\Services\ViewCacheService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AutoFixViewCache
{
    private ViewCacheService $cacheService;
    
    public function __construct(ViewCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }
    
    /**
     * Handle an incoming request.
     * Automatically fixes view cache errors when they occur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Circuit breaker: track failed attempts per session
        $sessionKey = 'view_cache_failures_' . session()->getId();
        $failureCount = cache()->get($sessionKey, 0);
        
        // If we've failed too many times in this session, bypass the middleware
        if ($failureCount >= 3) {
            Log::warning('View cache circuit breaker activated', [
                'session' => session()->getId(),
                'failures' => $failureCount,
                'url' => $request->fullUrl()
            ]);
            
            // Return a static error response without using views
            return $this->staticErrorResponse();
        }
        
        // Check if we've already attempted a fix for this request to prevent loops
        if ($request->attributes->get('view_cache_fix_attempted', false)) {
            // Don't attempt another fix, just proceed
            return $next($request);
        }
        
        // Disable this middleware for error pages to prevent loops
        if ($request->is('error/*') || $request->is('500') || $request->is('503')) {
            return $next($request);
        }
        
        // Also skip for static assets and health checks
        if ($request->is('*.css') || $request->is('*.js') || $request->is('*.png') || 
            $request->is('*.jpg') || $request->is('health') || $request->is('ping')) {
            return $next($request);
        }
        
        try {
            return $next($request);
        } catch (\ErrorException $e) {
            // Check if this is a view cache error
            if ($this->isViewCacheError($e)) {
                // Increment failure count for circuit breaker
                cache()->put($sessionKey, $failureCount + 1, now()->addMinutes(5));
                
                // Mark that we've attempted a fix
                $request->attributes->set('view_cache_fix_attempted', true);
                // Log the error
                Log::warning('View cache error detected, auto-fixing...', [
                    'error' => $e->getMessage(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip' => $request->ip()
                ]);
                
                // Try aggressive fix first (it's more reliable)
                $this->aggressiveFix();
                
                // Small delay to ensure filesystem operations complete
                usleep(100000); // 100ms
                
                // Clear request state to prevent issues
                app()->forgetInstance('request');
                app()->instance('request', $request);
                
                try {
                    // Retry the request with fresh cache
                    return $next($request);
                } catch (\ErrorException $retryError) {
                    // If it's still a view cache error, return a simple JSON response
                    if ($this->isViewCacheError($retryError)) {
                        Log::error('View cache auto-fix failed, returning error response');
                        
                        return response()->json([
                            'error' => 'View cache temporarily unavailable',
                            'message' => 'The system is automatically recovering. Please refresh in a few seconds.',
                            'status' => 503
                        ], 503);
                    }
                    
                    throw $retryError;
                } catch (\Exception $retryError) {
                    // If still failing, try service fix
                    if ($this->cacheService->autoFix()) {
                        Log::info('View cache auto-fixed successfully via service after retry');
                        return $next($request);
                    }
                    
                    // Last resort: return error response
                    Log::error('View cache auto-fix completely failed', [
                        'error' => $retryError->getMessage()
                    ]);
                    
                    throw $retryError;
                }
            }
            
            // If it's not a view cache error, rethrow
            throw $e;
        } catch (\Exception $e) {
            // Handle any other exceptions that might occur during view rendering
            if ($this->isViewRelatedError($e)) {
                Log::error('View-related error detected', [
                    'error' => $e->getMessage(),
                    'url' => $request->fullUrl()
                ]);
                
                // Try to recover
                $this->cacheService->rebuild();
                
                // Return a simple JSON response to avoid view rendering
                return response()->json([
                    'error' => 'System maintenance',
                    'message' => 'The system is recovering. Please try again in a moment.',
                    'status' => 503
                ], 503);
            }
            
            throw $e;
        }
    }
    
    /**
     * Check if the error is related to view cache
     */
    private function isViewCacheError(\ErrorException $e): bool
    {
        $message = $e->getMessage();
        
        return (strpos($message, 'filemtime(): stat failed') !== false && 
                strpos($message, 'storage/framework/views') !== false) ||
               (strpos($message, 'file_get_contents') !== false && 
                strpos($message, 'storage/framework/views') !== false) ||
               (strpos($message, 'No such file or directory') !== false && 
                strpos($message, 'views') !== false);
    }
    
    /**
     * Check if the error is related to view rendering
     */
    private function isViewRelatedError(\Exception $e): bool
    {
        $message = $e->getMessage();
        
        return strpos($message, 'View [') !== false ||
               strpos($message, 'view') !== false ||
               $e instanceof \Illuminate\View\ViewException;
    }
    
    /**
     * Aggressive fix for stubborn cache issues
     */
    private function aggressiveFix(): void
    {
        // First try to clear all caches directly
        try {
            // Remove all view cache files
            $viewPath = storage_path('framework/views');
            if (is_dir($viewPath)) {
                $files = glob($viewPath . '/*.php');
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
            
            // Clear only view cache to avoid disrupting other caches
            \Artisan::call('view:clear', [], new \Symfony\Component\Console\Output\NullOutput());
            
            // Reset OPcache
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            
            // Rebuild view cache
            \Artisan::call('view:cache', [], new \Symfony\Component\Console\Output\NullOutput());
            
            // Also run Filament optimizations if available
            if (class_exists('\Filament\FilamentServiceProvider')) {
                try {
                    \Artisan::call('filament:cache-components', [], new \Symfony\Component\Console\Output\NullOutput());
                } catch (\Exception $e) {
                    // Silently fail if command doesn't exist
                }
            }
            
            Log::info('Aggressive cache fix via Artisan completed');
        } catch (\Exception $e) {
            Log::warning('Artisan cache fix failed, trying shell script', ['error' => $e->getMessage()]);
            
            // Fallback to shell script
            $script = '/var/www/api-gateway/scripts/auto-fix-cache.sh';
            if (file_exists($script)) {
                exec($script . ' 2>&1', $output, $returnCode);
                
                if ($returnCode === 0) {
                    Log::info('Aggressive cache fix via shell completed successfully');
                } else {
                    Log::error('Aggressive cache fix via shell failed', [
                        'output' => implode("\n", $output),
                        'return_code' => $returnCode
                    ]);
                }
            }
        }
    }
    
    /**
     * Return a static error response without using Blade views
     * This prevents circular dependencies when error views themselves fail
     */
    private function staticErrorResponse()
    {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 500px;
            text-align: center;
        }
        h1 {
            color: #333;
            font-size: 48px;
            margin: 0 0 10px;
        }
        p {
            color: #666;
            line-height: 1.5;
            margin: 20px 0;
        }
        .refresh-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        .refresh-btn:hover {
            background: #5a67d8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚙️ System Maintenance</h1>
        <p>The system is performing automatic maintenance. This usually takes just a few seconds.</p>
        <p>Please refresh the page to continue.</p>
        <button onclick="window.location.reload()" class="refresh-btn">Refresh Page</button>
    </div>
</body>
</html>';
        
        return response($html, 503)
            ->header('Content-Type', 'text/html')
            ->header('Retry-After', '5');
    }
}