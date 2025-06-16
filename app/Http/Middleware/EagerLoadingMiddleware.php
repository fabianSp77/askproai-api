<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\EagerLoadingAnalyzer;

class EagerLoadingMiddleware
{
    /**
     * Route patterns and their optimal eager loading configurations
     */
    protected array $routeLoadingProfiles = [
        // API routes
        'api/appointments' => [
            'model' => \App\Models\Appointment::class,
            'profile' => 'standard',
            'relations' => ['customer', 'staff', 'branch', 'service'],
            'counts' => [],
        ],
        'api/appointments/*' => [
            'model' => \App\Models\Appointment::class,
            'profile' => 'full',
            'relations' => ['customer', 'staff', 'branch', 'service', 'calcomBooking'],
            'counts' => [],
        ],
        'api/customers' => [
            'model' => \App\Models\Customer::class,
            'profile' => 'minimal',
            'relations' => [],
            'counts' => ['appointments'],
        ],
        'api/customers/*' => [
            'model' => \App\Models\Customer::class,
            'profile' => 'full',
            'relations' => ['appointments', 'company'],
            'counts' => [],
        ],
        'api/staff' => [
            'model' => \App\Models\Staff::class,
            'profile' => 'standard',
            'relations' => ['branch', 'services'],
            'counts' => ['appointments'],
        ],
        'api/companies' => [
            'model' => \App\Models\Company::class,
            'profile' => 'minimal',
            'relations' => [],
            'counts' => ['branches', 'staff', 'customers'],
        ],
        'api/companies/*' => [
            'model' => \App\Models\Company::class,
            'profile' => 'full',
            'relations' => ['branches', 'staff', 'services'],
            'counts' => ['appointments', 'customers'],
        ],
        // Admin routes
        'admin/appointments' => [
            'optimize' => true,
            'cache_duration' => 5, // Cache query analysis for 5 minutes
        ],
        'admin/customers' => [
            'optimize' => true,
            'cache_duration' => 5,
        ],
    ];
    
    protected EagerLoadingAnalyzer $analyzer;
    
    public function __construct(EagerLoadingAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only optimize GET requests
        if (!$request->isMethod('GET')) {
            return $next($request);
        }
        
        $path = $request->path();
        $loadingConfig = $this->getLoadingConfigForPath($path);
        
        if ($loadingConfig) {
            // Store loading config in request for controllers to use
            $request->attributes->set('eager_loading_config', $loadingConfig);
            
            // Start query analysis in development
            if (config('app.debug') && ($loadingConfig['optimize'] ?? false)) {
                $this->analyzer->startAnalysis();
                
                $response = $next($request);
                
                $analysis = $this->analyzer->stopAnalysis();
                
                // Log suspicious patterns
                if (!empty($analysis['suspicious_patterns'])) {
                    logger()->warning('N+1 queries detected on route: ' . $path, $analysis);
                }
                
                // Add analysis to response headers in debug mode
                if ($request->wantsJson()) {
                    $response->headers->set('X-Query-Count', $analysis['total_queries']);
                    $response->headers->set('X-Query-Analysis', json_encode([
                        'total' => $analysis['total_queries'],
                        'n1_warnings' => count($analysis['suspicious_patterns']),
                    ]));
                }
                
                return $response;
            }
        }
        
        return $next($request);
    }
    
    /**
     * Get loading configuration for the given path
     */
    protected function getLoadingConfigForPath(string $path): ?array
    {
        // Direct match
        if (isset($this->routeLoadingProfiles[$path])) {
            return $this->routeLoadingProfiles[$path];
        }
        
        // Pattern match
        foreach ($this->routeLoadingProfiles as $pattern => $config) {
            if (Str::is($pattern, $path)) {
                return $config;
            }
        }
        
        // Check if it's an API route that should be optimized
        if (Str::startsWith($path, 'api/')) {
            return $this->inferLoadingConfig($path);
        }
        
        return null;
    }
    
    /**
     * Infer loading configuration based on route pattern
     */
    protected function inferLoadingConfig(string $path): array
    {
        $segments = explode('/', $path);
        $isDetail = count($segments) > 2 && is_numeric(end($segments));
        
        return [
            'profile' => $isDetail ? 'full' : 'standard',
            'optimize' => true,
            'inferred' => true,
        ];
    }
    
    /**
     * Register a custom loading profile for a route
     */
    public static function registerProfile(string $pattern, array $config): void
    {
        app(self::class)->routeLoadingProfiles[$pattern] = $config;
    }
}