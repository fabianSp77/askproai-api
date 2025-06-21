#!/usr/bin/env php
<?php

/**
 * Fix Livewire headers access issue in middleware
 * This script updates all middleware to properly check response type before accessing headers
 */

$middlewareFiles = [
    '/var/www/api-gateway/app/Http/Middleware/LivewireDebugMiddleware.php',
    '/var/www/api-gateway/app/Http/Middleware/DebugLivewire.php',
    '/var/www/api-gateway/app/Http/Middleware/CorrelationIdMiddleware.php',
    '/var/www/api-gateway/app/Http/Middleware/ThreatDetectionMiddleware.php',
    '/var/www/api-gateway/app/Http/Middleware/EnsureTenantContext.php',
    '/var/www/api-gateway/app/Http/Middleware/AdaptiveRateLimitMiddleware.php',
    '/var/www/api-gateway/app/Http/Middleware/ApiAuthMiddleware.php',
    '/var/www/api-gateway/app/Http/Middleware/CacheApiResponseByRoute.php',
    '/var/www/api-gateway/app/Http/Middleware/CacheApiResponse.php',
    '/var/www/api-gateway/app/Http/Middleware/DebugAllRequests.php',
    '/var/www/api-gateway/app/Http/Middleware/DebugLogin.php',
    '/var/www/api-gateway/app/Http/Middleware/MonitoringMiddleware.php',
    '/var/www/api-gateway/app/Http/Middleware/SessionManager.php',
    '/var/www/api-gateway/app/Http/Middleware/LoginDebugger.php',
    '/var/www/api-gateway/app/Http/Middleware/QueryMonitor.php',
    '/var/www/api-gateway/app/Http/Middleware/MonitorQueries.php',
    '/var/www/api-gateway/app/Http/Middleware/EnhancedRateLimiting.php',
    '/var/www/api-gateway/app/Http/Middleware/MobileDetector.php',
    '/var/www/api-gateway/app/Http/Middleware/EagerLoadingMiddleware.php',
];

$fixedCount = 0;

foreach ($middlewareFiles as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Pattern 1: Direct access to $response->headers without proper check
    // Replace $response->headers->get/set/add with proper check
    $patterns = [
        // Pattern for ->headers->set
        '/(\$response)(->headers->set\([^)]+\));/m' => 
            'if ($1 instanceof \Illuminate\Http\Response || $1 instanceof \Symfony\Component\HttpFoundation\Response) {
            $1$2;
        }',
        
        // Pattern for ->headers->get
        '/(\$response->headers->get\([^)]+\))/m' => 
            '(($response instanceof \Illuminate\Http\Response || $response instanceof \Symfony\Component\HttpFoundation\Response) ? $1 : null)',
        
        // Pattern for ->headers->add
        '/(\$response)(->headers->add\([^)]+\));/m' => 
            'if ($1 instanceof \Illuminate\Http\Response || $1 instanceof \Symfony\Component\HttpFoundation\Response) {
            $1$2;
        }',
    ];
    
    // Apply fixes
    foreach ($patterns as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }
    
    // Special case for accessing headers property directly in conditions
    $content = preg_replace(
        '/if\s*\(method_exists\(\$response,\s*[\'"]headers[\'"]\)\s*\|\|\s*property_exists\(\$response,\s*[\'"]headers[\'"]\)\)\s*{/',
        'if ($response instanceof \Illuminate\Http\Response || $response instanceof \Symfony\Component\HttpFoundation\Response) {',
        $content
    );
    
    // Save if changed
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Fixed: $file\n";
        $fixedCount++;
    }
}

echo "\nFixed $fixedCount files.\n";

// Clear all caches
echo "\nClearing caches...\n";
exec('cd /var/www/api-gateway && php artisan optimize:clear');
exec('cd /var/www/api-gateway && php artisan config:clear');
exec('cd /var/www/api-gateway && php artisan route:clear');
exec('cd /var/www/api-gateway && php artisan view:clear');

echo "\nDone! All middleware files have been fixed and caches cleared.\n";