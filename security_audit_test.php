<?php
// Security Headers Audit Test
echo "=== Security Headers Audit for AskProAI Admin Panel ===\n\n";

// 1. Check current environment
echo "1. ENVIRONMENT CHECK:\n";
echo "APP_ENV: " . (getenv('APP_ENV') ?: 'not set') . "\n";
echo "APP_DEBUG: " . (getenv('APP_DEBUG') ?: 'not set') . "\n";
echo "APP_URL: " . (getenv('APP_URL') ?: 'not set') . "\n\n";

// 2. Test CSP header generation
echo "2. CSP HEADER ANALYSIS:\n";

// Simulate SecurityHeaders middleware CSP
$isLocal = (getenv('APP_ENV') === 'local');
echo "Is Local Environment: " . ($isLocal ? 'YES' : 'NO') . "\n";

if (\!$isLocal) {
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com; " .
           "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; " .
           "font-src 'self' https://fonts.gstatic.com; " .
           "img-src 'self' data: https:; " .
           "connect-src 'self' wss: https://api.askproai.de; " .
           "frame-ancestors 'self';";
    echo "Production CSP would be: $csp\n";
} else {
    echo "No CSP in local environment\n";
}

// Check ThreatDetectionMiddleware CSP
$threatCsp = "default-src 'self' http: https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' http: https:; style-src 'self' 'unsafe-inline' http: https:; connect-src 'self' http: https: ws: wss:;";
echo "ThreatDetection CSP: $threatCsp\n\n";

// 3. Check for potential CSP conflicts
echo "3. POTENTIAL CSP CONFLICTS:\n";
$conflicts = [
    "Multiple middlewares setting CSP" => "SecurityHeaders + ThreatDetectionMiddleware both set CSP",
    "Unsafe-eval required" => "Filament/Alpine.js may need unsafe-eval for dynamic expressions",
    "Unsafe-inline styles" => "Filament CSS may use inline styles",
    "WebSocket connections" => "Real-time features may need 'wss:' in connect-src",
    "Vite dev server" => "Development may need additional origins"
];

foreach ($conflicts as $issue => $description) {
    echo "WARNING: $issue: $description\n";
}

echo "\n4. SECURITY RECOMMENDATIONS:\n";
$recommendations = [
    "Use nonces instead of unsafe-inline" => "Generate unique nonces for inline scripts/styles",
    "Separate dev/prod CSP" => "More permissive CSP for development, strict for production",
    "Remove duplicate CSP headers" => "Only one middleware should set CSP",
    "Test with browser dev tools" => "Check console for CSP violations",
    "Implement CSP reporting" => "Add report-uri to monitor violations"
];

foreach ($recommendations as $action => $description) {
    echo "RECOMMEND: $action: $description\n";
}

echo "\n5. FILAMENT-SPECIFIC ISSUES:\n";
echo "Filament uses Alpine.js which may require:\n";
echo "- 'unsafe-eval' for dynamic expressions\n";
echo "- 'unsafe-inline' for component styles\n";
echo "- WebSocket support for Livewire\n";
echo "- External CDNs for fonts/icons\n";

echo "\n6. NEXT STEPS:\n";
echo "1. Check browser console for CSP violations\n";
echo "2. Test admin panel functionality with strict CSP\n";
echo "3. Implement nonce-based CSP if needed\n";
echo "4. Remove duplicate security headers\n";
echo "5. Add CSP reporting endpoint\n";

