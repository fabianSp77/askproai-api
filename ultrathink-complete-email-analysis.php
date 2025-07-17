<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ULTRATHINK COMPLETE EMAIL ANALYSIS ===\n\n";
echo "Analyzing EVERY possible difference between test scripts and real browser clicks...\n\n";

// ===========================
// 1. SESSION & COOKIE ANALYSIS
// ===========================
echo "1. SESSION & COOKIE CONFIGURATION:\n";
echo "   Session Driver: " . config('session.driver') . "\n";
echo "   Session Domain: " . config('session.domain') . "\n";
echo "   Session Path: " . config('session.path') . "\n";
echo "   Session Secure: " . (config('session.secure') ? 'YES' : 'NO') . "\n";
echo "   Session SameSite: " . config('session.same_site') . "\n";
echo "   Cookie Domain: " . config('session.cookie_domain') . "\n";
echo "   Cookie Path: " . config('session.cookie_path') . "\n";
echo "   Session Lifetime: " . config('session.lifetime') . " minutes\n";

// Check if multiple session configs exist
$sessionConfigs = glob(config_path('session*.php'));
if (count($sessionConfigs) > 1) {
    echo "   ⚠️  MULTIPLE SESSION CONFIGS FOUND:\n";
    foreach ($sessionConfigs as $config) {
        echo "      - " . basename($config) . "\n";
    }
}

// ===========================
// 2. DOMAIN & URL ANALYSIS
// ===========================
echo "\n2. DOMAIN & URL CONFIGURATION:\n";
echo "   APP_URL: " . config('app.url') . "\n";
echo "   BUSINESS_PORTAL_URL: " . env('BUSINESS_PORTAL_URL', 'not set') . "\n";
echo "   API Domain: api.askproai.de\n";
echo "   Portal Domain: askproai.de\n";
echo "   ⚠️  CRITICAL: Different domains can cause session/cookie issues!\n";

// Check for domain mismatches
$appUrl = parse_url(config('app.url'));
$actualDomain = $_SERVER['HTTP_HOST'] ?? 'cli';
if ($appUrl['host'] !== $actualDomain && $actualDomain !== 'cli') {
    echo "   ❌ DOMAIN MISMATCH: Config says {$appUrl['host']} but actual is $actualDomain\n";
}

// ===========================
// 3. AUTHENTICATION ANALYSIS
// ===========================
echo "\n3. AUTHENTICATION GUARDS & PROVIDERS:\n";
$guards = config('auth.guards');
foreach ($guards as $name => $config) {
    echo "   Guard '$name':\n";
    echo "      Driver: " . ($config['driver'] ?? 'not set') . "\n";
    echo "      Provider: " . ($config['provider'] ?? 'not set') . "\n";
    if (isset($config['provider'])) {
        $provider = config("auth.providers.{$config['provider']}");
        echo "      Model: " . ($provider['model'] ?? 'not set') . "\n";
    }
}

// Check portal guard specifically
if (!isset($guards['portal'])) {
    echo "   ❌ CRITICAL: Portal guard not configured!\n";
}

// ===========================
// 4. MIDDLEWARE ANALYSIS
// ===========================
echo "\n4. MIDDLEWARE STACK FOR API ROUTES:\n";
$routeMiddleware = app('router')->getMiddleware();
$webMiddleware = config('app.middleware_groups.web', []);
echo "   Web Middleware (" . count($webMiddleware) . " total):\n";
foreach ($webMiddleware as $mw) {
    echo "      - " . (is_string($mw) ? class_basename($mw) : gettype($mw)) . "\n";
}

// Check specific route middleware
$route = app('router')->getRoutes()->match(
    app('request')->create('/business/api/calls/1/send-summary', 'POST')
);
if ($route) {
    echo "\n   Send-Summary Route Middleware:\n";
    foreach ($route->middleware() as $mw) {
        echo "      - $mw\n";
    }
}

// ===========================
// 5. CSRF TOKEN ANALYSIS
// ===========================
echo "\n5. CSRF TOKEN CONFIGURATION:\n";
$csrfMiddleware = app(\App\Http\Middleware\VerifyCsrfToken::class);
$reflection = new ReflectionClass($csrfMiddleware);
$property = $reflection->getProperty('except');
$property->setAccessible(true);
$exceptions = $property->getValue($csrfMiddleware);

echo "   CSRF Exceptions:\n";
foreach ($exceptions as $exception) {
    echo "      - $exception\n";
}

// Check if business routes are excluded
$businessExcluded = false;
foreach ($exceptions as $exception) {
    if (str_contains($exception, 'business')) {
        $businessExcluded = true;
        break;
    }
}
if ($businessExcluded) {
    echo "   ⚠️  WARNING: Some business routes bypass CSRF!\n";
} else {
    echo "   ✅ Business routes require CSRF token\n";
}

// ===========================
// 6. JAVASCRIPT BUILD ANALYSIS
// ===========================
echo "\n6. JAVASCRIPT BUILD & ASSETS:\n";
$manifestPath = public_path('build/manifest.json');
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $lastBuildTime = filemtime($manifestPath);
    echo "   Last Build: " . date('Y-m-d H:i:s', $lastBuildTime) . " (" . human_time_diff($lastBuildTime) . ")\n";
    
    // Find ShowV2 component
    $showV2Found = false;
    foreach ($manifest as $key => $value) {
        if (str_contains($key, 'ShowV2')) {
            $showV2Found = true;
            echo "   ✅ ShowV2 Component: " . ($value['file'] ?? 'unknown') . "\n";
        }
    }
    if (!$showV2Found) {
        echo "   ❌ ShowV2 component NOT FOUND in build!\n";
    }
    
    // Check bootstrap.js
    $bootstrapFound = false;
    foreach ($manifest as $key => $value) {
        if (str_contains($key, 'bootstrap')) {
            $bootstrapFound = true;
            echo "   Bootstrap: " . ($value['file'] ?? 'unknown') . "\n";
        }
    }
} else {
    echo "   ❌ NO BUILD MANIFEST FOUND!\n";
}

// ===========================
// 7. API ENDPOINT ANALYSIS
// ===========================
echo "\n7. API ENDPOINT VERIFICATION:\n";
$routes = app('router')->getRoutes();
$foundRoute = null;
foreach ($routes as $route) {
    if (str_contains($route->uri(), 'send-summary')) {
        $foundRoute = $route;
        echo "   Route: " . implode('|', $route->methods()) . " " . $route->uri() . "\n";
        echo "   Controller: " . $route->getActionName() . "\n";
        echo "   Name: " . ($route->getName() ?? 'unnamed') . "\n";
        
        // Check if route parameters are correct
        preg_match_all('/\{([^}]+)\}/', $route->uri(), $params);
        if (!empty($params[1])) {
            echo "   Parameters: " . implode(', ', $params[1]) . "\n";
        }
    }
}

if (!$foundRoute) {
    echo "   ❌ CRITICAL: send-summary route NOT FOUND!\n";
}

// ===========================
// 8. CORS CONFIGURATION
// ===========================
echo "\n8. CORS CONFIGURATION:\n";
$corsConfig = config('cors');
echo "   Allowed Origins: " . json_encode($corsConfig['allowed_origins'] ?? ['*']) . "\n";
echo "   Allowed Methods: " . json_encode($corsConfig['allowed_methods'] ?? ['*']) . "\n";
echo "   Allowed Headers: " . json_encode($corsConfig['allowed_headers'] ?? ['*']) . "\n";
echo "   Supports Credentials: " . ($corsConfig['supports_credentials'] ?? false ? 'YES' : 'NO') . "\n";

// Check if different domains are properly configured
if (isset($corsConfig['allowed_origins']) && is_array($corsConfig['allowed_origins'])) {
    $hasApiDomain = in_array('https://api.askproai.de', $corsConfig['allowed_origins']);
    $hasPortalDomain = in_array('https://askproai.de', $corsConfig['allowed_origins']);
    
    if (!$hasApiDomain || !$hasPortalDomain) {
        echo "   ⚠️  WARNING: Not all domains are in CORS allowed origins!\n";
    }
}

// ===========================
// 9. REACT ROUTING ANALYSIS
// ===========================
echo "\n9. REACT ROUTING & MOUNTING:\n";

// Check for React router setup
$jsFiles = glob(resource_path('js/**/*.{js,jsx}'), GLOB_BRACE);
$routerFiles = [];
foreach ($jsFiles as $file) {
    $content = file_get_contents($file);
    if (str_contains($content, 'BrowserRouter') || str_contains($content, 'Route')) {
        $routerFiles[] = basename($file);
    }
}
echo "   React Router Files: " . implode(', ', $routerFiles) . "\n";

// Check how ShowV2 is loaded
$showV2Import = null;
foreach ($jsFiles as $file) {
    $content = file_get_contents($file);
    if (str_contains($content, "import ShowV2") || str_contains($content, "from './ShowV2'")) {
        $showV2Import = basename($file);
        break;
    }
}
echo "   ShowV2 imported in: " . ($showV2Import ?? 'NOT FOUND') . "\n";

// ===========================
// 10. QUEUE & EMAIL ANALYSIS
// ===========================
echo "\n10. QUEUE & EMAIL CONFIGURATION:\n";
echo "   Queue Driver: " . config('queue.default') . "\n";
echo "   Mail Driver: " . config('mail.default') . "\n";
echo "   From Address: " . config('mail.from.address') . "\n";
echo "   Horizon Status: ";
$horizonStatus = shell_exec('php artisan horizon:status 2>&1');
echo trim($horizonStatus) . "\n";

// Check Redis connection
try {
    $redis = app('redis');
    $redis->ping();
    echo "   Redis: ✅ Connected\n";
    
    // Check queues
    $queues = ['default', 'emails', 'high'];
    foreach ($queues as $queue) {
        $count = $redis->llen("queues:$queue");
        if ($count > 0) {
            echo "   Queue '$queue': $count jobs\n";
        }
    }
} catch (\Exception $e) {
    echo "   Redis: ❌ " . $e->getMessage() . "\n";
}

// ===========================
// 11. REQUEST FLOW ANALYSIS
// ===========================
echo "\n11. REQUEST FLOW DIFFERENCES:\n";
echo "   Test Script Flow:\n";
echo "      1. PHP creates session directly\n";
echo "      2. Auth::guard('portal')->login() called\n";
echo "      3. Direct controller method call\n";
echo "      4. Same process, same session\n";
echo "\n";
echo "   Browser Flow:\n";
echo "      1. User logs in via /business/login\n";
echo "      2. Session cookie set in browser\n";
echo "      3. JavaScript makes fetch() request\n";
echo "      4. Cross-domain if API is on different subdomain\n";
echo "      5. CORS and cookies must be properly configured\n";

// ===========================
// 12. SPECIFIC ISSUES FOUND
// ===========================
echo "\n12. CRITICAL ISSUES DETECTED:\n";

$issues = [];

// Issue 1: Domain mismatch
if (config('session.domain') !== '.askproai.de') {
    $issues[] = "Session domain is '" . config('session.domain') . "' but should be '.askproai.de' for cross-subdomain";
}

// Issue 2: Secure cookies on HTTP
if (config('session.secure') && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
    $issues[] = "Secure cookies enabled but not using HTTPS";
}

// Issue 3: SameSite strict with cross-domain
if (config('session.same_site') === 'strict') {
    $issues[] = "SameSite=Strict prevents cross-domain cookie sharing";
}

// Issue 4: Missing portal guard in TenantScope
$tenantScopeFile = file_get_contents(app_path('Scopes/TenantScope.php'));
if (!str_contains($tenantScopeFile, "Auth::guard('portal')")) {
    $issues[] = "TenantScope doesn't check portal guard";
}

// Issue 5: JavaScript not sending cookies
if (!str_contains(file_get_contents(resource_path('js/bootstrap.js')), 'withCredentials')) {
    $issues[] = "Axios not configured with withCredentials";
}

if (empty($issues)) {
    echo "   No critical issues detected (but there might be subtle ones)\n";
} else {
    foreach ($issues as $i => $issue) {
        echo "   " . ($i + 1) . ". ❌ $issue\n";
    }
}

// ===========================
// 13. RECOMMENDATIONS
// ===========================
echo "\n13. IMMEDIATE ACTIONS TO TAKE:\n";
echo "   1. Check browser console: F12 → Console tab\n";
echo "   2. Check network tab: F12 → Network → Filter 'send-summary'\n";
echo "   3. Look for:\n";
echo "      - Red errors in console\n";
echo "      - 4xx/5xx status codes\n";
echo "      - CORS errors\n";
echo "      - 'Blocked by CORS policy' messages\n";
echo "   4. Share the full browser console output\n";
echo "   5. Share the network request details (headers, response)\n";

echo "\n=== END OF ANALYSIS ===\n";

// Helper function
function human_time_diff($timestamp) {
    $diff = time() - $timestamp;
    if ($diff < 60) return "$diff seconds ago";
    if ($diff < 3600) return round($diff/60) . " minutes ago";
    if ($diff < 86400) return round($diff/3600) . " hours ago";
    return round($diff/86400) . " days ago";
}