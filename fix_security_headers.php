<?php
/**
 * Quick Fix for Security Headers CSP Conflicts
 * 
 * This script addresses the critical CSP conflicts found in the security audit.
 */

echo "=== AskProAI Security Headers Quick Fix ===\n\n";

// 1. Check current middleware registration
echo "1. CHECKING CURRENT MIDDLEWARE REGISTRATION:\n";
$kernelPath = '/var/www/api-gateway/app/Http/Kernel.php';
$kernelContent = file_get_contents($kernelPath);

if (strpos($kernelContent, 'SecurityHeaders::class') === false) {
    echo "❌ SecurityHeaders middleware NOT registered\n";
} else {
    echo "✅ SecurityHeaders middleware IS registered\n";
}

if (strpos($kernelContent, 'ThreatDetectionMiddleware::class') \!== false) {
    echo "⚠️  ThreatDetectionMiddleware IS registered (potential CSP conflict)\n";
} else {
    echo "ℹ️  ThreatDetectionMiddleware NOT found in global middleware\n";
}

// 2. Show current CSP policies
echo "\n2. CURRENT CSP POLICIES:\n";

$securityHeadersPath = '/var/www/api-gateway/app/Http/Middleware/SecurityHeaders.php';
$threatDetectionPath = '/var/www/api-gateway/app/Http/Middleware/ThreatDetectionMiddleware.php';

if (file_exists($securityHeadersPath)) {
    $securityContent = file_get_contents($securityHeadersPath);
    if (preg_match('/Content-Security-Policy.*?"([^"]*)"/', $securityContent, $matches)) {
        echo "SecurityHeaders CSP: " . substr($matches[1], 0, 100) . "...\n";
    }
}

if (file_exists($threatDetectionPath)) {
    $threatContent = file_get_contents($threatDetectionPath);
    if (preg_match('/Content-Security-Policy.*?"([^"]*)"/', $threatContent, $matches)) {
        echo "ThreatDetection CSP: " . substr($matches[1], 0, 100) . "...\n";
    }
}

// 3. Check which middleware is actually active
echo "\n3. ACTIVE MIDDLEWARE ANALYSIS:\n";
echo "Based on Kernel.php registration:\n";

// Look for middleware groups
if (strpos($kernelContent, "'web' => [") \!== false) {
    echo "- 'web' middleware group exists\n";
}

if (strpos($kernelContent, "'threat.detection'") \!== false) {
    echo "- 'threat.detection' alias registered\n";
}

// 4. Recommendations
echo "\n4. IMMEDIATE ACTIONS NEEDED:\n";
echo "✅ Register SecurityHeaders in global middleware\n";
echo "✅ Remove CSP from ThreatDetectionMiddleware\n";
echo "✅ Test admin panel with consolidated CSP\n";
echo "✅ Monitor browser console for CSP violations\n";

echo "\n5. BROWSER TESTING:\n";
echo "Open browser developer tools and check:\n";
echo "- Console tab for CSP violation errors\n";
echo "- Network tab for blocked resources\n";
echo "- Security tab for content security policy status\n";

echo "\n6. QUICK TEST COMMANDS:\n";
echo "# Test current headers:\n";
echo "curl -I http://localhost/admin/login 2>/dev/null | grep -i 'content-security\\|x-frame'\n\n";
echo "# Check for CSP violations in logs:\n";
echo "tail -f storage/logs/laravel.log | grep -i 'csp\\|security\\|blocked'\n";

echo "\n=== END OF QUICK FIX REPORT ===\n";
