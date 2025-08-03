<?php
echo "=== Security Headers Analysis ===\n";

$kernel = file_get_contents('app/Http/Kernel.php');
echo "SecurityHeaders registered: " . (strpos($kernel, 'SecurityHeaders') \!== false ? "YES" : "NO") . "\n";
echo "ThreatDetection registered: " . (strpos($kernel, 'ThreatDetectionMiddleware') \!== false ? "YES" : "NO") . "\n";

echo "\nCRITICAL: SecurityHeaders middleware NOT registered in kernel\!\n";
echo "Only ThreatDetectionMiddleware is setting CSP headers.\n";
