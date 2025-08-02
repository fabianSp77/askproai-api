<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ProgressiveEnhancementService;

class ProgressiveEnhancement
{
    protected ProgressiveEnhancementService $enhancementService;
    
    public function __construct(ProgressiveEnhancementService $enhancementService)
    {
        $this->enhancementService = $enhancementService;
    }
    
    public function handle(Request $request, Closure $next)
    {
        // Determine enhancement level
        $level = $this->enhancementService->getEnhancementLevel($request);
        
        // Share with views
        view()->share('enhancementLevel', $level);
        view()->share('enhancementAssets', $this->enhancementService->getAssets($level));
        view()->share('enhancementLayout', $this->enhancementService->getLayoutView($level));
        view()->share('performanceHints', $this->enhancementService->getPerformanceHints($level));
        
        // Add to request for controller access
        $request->attributes->set('enhancement_level', $level);
        
        // Set response headers for client hints
        $response = $next($request);
        
        if ($response->headers) {
            $response->headers->set('X-Enhancement-Level', $level);
            
            // Accept client hints for better detection
            $response->headers->set('Accept-CH', 'Save-Data, ECT, RTT, Downlink, Device-Memory');
            $response->headers->set('Accept-CH-Lifetime', '86400');
            
            // Cache headers based on level
            $cacheStrategy = $this->enhancementService->getCacheStrategy($level);
            if ($level === 0) {
                // Aggressive caching for no-JS version
                $response->headers->set('Cache-Control', 'public, max-age=' . $cacheStrategy['ttl']);
            }
        }
        
        return $response;
    }
}