<?php
// Direct test of Filament admin with session-based login

$url = 'https://api.askproai.de/admin';

// Use curl to get the page
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

echo "HTTP Code: $httpCode\n";
echo "Final URL: $finalUrl\n";
echo "Content length: " . strlen($response) . " bytes\n";

// Check what we got
if (strpos($response, 'Melden Sie sich an') !== false) {
    echo "✓ Redirected to login page (expected for unauthenticated user)\n";
} elseif (strpos($response, 'Dashboard') !== false) {
    echo "✓ Dashboard content detected\n";
} else {
    echo "? Unknown content\n";
}

// Look for Filament-specific content
if (strpos($response, 'filament') !== false) {
    echo "✓ Filament content detected\n";
}

// Look for navigation elements
if (strpos($response, 'sidebar') !== false || strpos($response, 'navigation') !== false) {
    echo "✓ Navigation elements detected\n";
}

curl_close($ch);
