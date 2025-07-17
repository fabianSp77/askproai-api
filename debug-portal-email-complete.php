<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== COMPLETE Portal Email Debug ===\n\n";

// 1. Test the exact same endpoint that frontend uses
echo "1. Testing the EXACT frontend endpoint:\n";

$ch = curl_init();

// Get CSRF token
curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/business");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/portal-cookies.txt");
curl_setopt($ch, CURLOPT_COOKIEFILE, "/tmp/portal-cookies.txt");
$html = curl_exec($ch);

// Extract CSRF token
preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches);
$csrfToken = $matches[1] ?? null;

echo "   CSRF Token: " . ($csrfToken ? substr($csrfToken, 0, 20) . "..." : "NOT FOUND") . "\n";

// Try to send email via API
$callId = 232;
curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/business/api/calls/{$callId}/send-summary");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'recipients' => ['fabianspitzer@icloud.com'],
    'include_transcript' => true,
    'include_csv' => true,
    'message' => 'Test via CURL - ' . date('H:i:s')
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-CSRF-TOKEN: ' . $csrfToken,
    'X-Requested-With: XMLHttpRequest'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "   HTTP Status: $httpCode\n";
echo "   Response: " . substr($response, 0, 200) . "...\n\n";

curl_close($ch);

// 2. Check JavaScript build
echo "2. Checking JavaScript build:\n";
$manifestPath = public_path('build/manifest.json');
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    $jsFile = null;
    foreach ($manifest as $key => $value) {
        if (str_contains($key, 'ShowV2')) {
            $jsFile = $value['file'] ?? null;
            break;
        }
    }
    if ($jsFile) {
        echo "   ✅ ShowV2.jsx built to: $jsFile\n";
        $buildTime = filemtime(public_path('build/' . $jsFile));
        echo "   Build time: " . date('Y-m-d H:i:s', $buildTime) . "\n";
    } else {
        echo "   ❌ ShowV2.jsx not found in build!\n";
    }
} else {
    echo "   ❌ No build manifest found!\n";
}

// 3. Check for JavaScript errors in bootstrap.js
echo "\n3. Checking bootstrap.js CSRF setup:\n";
$bootstrapPath = resource_path('js/bootstrap.js');
$bootstrapContent = file_get_contents($bootstrapPath);
if (str_contains($bootstrapContent, 'window.Laravel.csrfToken')) {
    echo "   ✅ CSRF token setup found in bootstrap.js\n";
} else {
    echo "   ❌ CSRF token setup NOT found in bootstrap.js\n";
}

// 4. Check React app mounting
echo "\n4. Checking React app mounting:\n";
$viewFiles = glob(resource_path('views/portal/calls/*.blade.php'));
$foundShowV2 = false;
foreach ($viewFiles as $file) {
    $content = file_get_contents($file);
    if (str_contains($content, 'ShowV2') || str_contains($content, 'show-v2')) {
        echo "   ✅ ShowV2 referenced in: " . basename($file) . "\n";
        $foundShowV2 = true;
    }
}
if (!$foundShowV2) {
    echo "   ⚠️  ShowV2 component not found in any blade template\n";
    echo "   Maybe it's loaded dynamically via React Router?\n";
}

// 5. Recent logs
echo "\n5. Recent relevant logs:\n";
$logs = shell_exec("tail -50 " . storage_path('logs/laravel.log') . " | grep -i 'portal email\\|send-summary' | tail -5");
if ($logs) {
    echo $logs;
} else {
    echo "   No recent portal email logs\n";
}

echo "\n=== PROBLEM ANALYSIS ===\n";
echo "Based on the debug info, the issue is likely:\n";
echo "1. ❌ Frontend is not sending the request at all (JavaScript error)\n";
echo "2. ❌ CSRF token mismatch (session issue)\n";
echo "3. ❌ Authentication lost between page load and API call\n";
echo "4. ❌ React component not properly mounted/initialized\n";

echo "\n=== NEXT STEPS ===\n";
echo "1. Open Browser DevTools (F12)\n";
echo "2. Go to Console tab\n";
echo "3. Clear console\n";
echo "4. Try to send email from Business Portal\n";
echo "5. Look for:\n";
echo "   - Red error messages\n";
echo "   - 'Sending summary to:' log message\n";
echo "   - Network errors\n";
echo "6. Share the console output with me\n";