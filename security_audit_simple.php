<?php
echo "=== Security Headers Audit ===\n\n";

echo "1. Environment: " . (getenv('APP_ENV') ?: 'production') . "\n";
echo "2. Debug Mode: " . (getenv('APP_DEBUG') ?: 'false') . "\n\n";

echo "3. MIDDLEWARE ANALYSIS:\n";
echo "- SecurityHeaders.php: Sets CSP only in non-local environment\n";
echo "- ThreatDetectionMiddleware.php: Always sets CSP with broad permissions\n";
echo "- Both use 'unsafe-inline' and 'unsafe-eval'\n\n";

echo "4. CRITICAL FINDINGS:\n";
echo "- Multiple CSP headers may conflict\n";
echo "- ThreatDetection overrides SecurityHeaders\n";
echo "- Very permissive CSP allows 'unsafe-eval'\n";
echo "- No nonce-based CSP implementation\n\n";

echo "5. RECOMMENDATIONS:\n";
echo "- Remove CSP from ThreatDetectionMiddleware\n";
echo "- Use single CSP source in SecurityHeaders\n";
echo "- Implement nonce for inline scripts\n";
echo "- Test Filament compatibility with strict CSP\n";
