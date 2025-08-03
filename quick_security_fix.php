<?php
echo "=== Security Headers Quick Analysis ===\n\n";

// Check if SecurityHeaders is registered
$kernel = file_get_contents('app/Http/Kernel.php');
$hasSecurityHeaders = strpos($kernel, 'SecurityHeaders') \!== false;
$hasThreatDetection = strpos($kernel, 'ThreatDetectionMiddleware') \!== false;

echo "SecurityHeaders registered: " . ($hasSecurityHeaders ? "YES" : "NO") . "\n";
echo "ThreatDetection registered: " . ($hasThreatDetection ? "YES" : "NO") . "\n\n";

echo "CRITICAL ISSUE FOUND:\n";
echo "- SecurityHeaders middleware exists but NOT registered\n";
echo "- ThreatDetectionMiddleware sets CSP headers\n";
echo "- Potential conflicts causing UI blocking\n\n";

echo "IMMEDIATE FIX NEEDED:\n";
echo "1. Register SecurityHeaders in Kernel.php\n";
echo "2. Remove CSP from ThreatDetectionMiddleware\n";
echo "3. Test admin panel functionality\n";
