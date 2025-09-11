<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureViewCacheExists
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
        // Ensure view cache directory exists with correct permissions
        $viewPath = storage_path('framework/views');
        
        if (!is_dir($viewPath)) {
            mkdir($viewPath, 0775, true);
            chown($viewPath, 'www-data');
            chgrp($viewPath, 'www-data');
        }
        
        // Clear any stale view cache references
        if ($request->is('admin/*')) {
            try {
                // Force clear view cache if we're accessing admin
                \Artisan::call('view:clear', ['--quiet' => true]);
                \Artisan::call('view:cache', ['--quiet' => true]);
            } catch (\Exception $e) {
                // Silently continue if cache operations fail
            }
        }
        
        return $next($request);
    }
}