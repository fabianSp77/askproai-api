<?php

echo "=== TESTING LIVEWIRE LOGIN FUNCTIONALITY ===\n\n";

// Test 1: Check if login page loads
echo "1. Testing login page load...\n";
$loginUrl = 'https://api.askproai.de/admin/login';
$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$loginHtml = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "✅ Login page loads successfully (HTTP 200)\n";
    
    // Extract CSRF token
    preg_match('/<meta name="csrf-token" content="([^"]+)"/', $loginHtml, $matches);
    $csrfToken = $matches[1] ?? null;
    
    if ($csrfToken) {
        echo "✅ CSRF token found: " . substr($csrfToken, 0, 20) . "...\n";
    } else {
        echo "❌ CSRF token not found\n";
    }
    
    // Check for Livewire
    if (strpos($loginHtml, 'wire:') !== false) {
        echo "✅ Livewire components detected\n";
    } else {
        echo "⚠️  No Livewire components found\n";
    }
    
    // Check for Filament
    if (strpos($loginHtml, 'filament') !== false) {
        echo "✅ Filament framework detected\n";
    } else {
        echo "❌ Filament framework not detected\n";
    }
} else {
    echo "❌ Login page failed to load (HTTP $httpCode)\n";
}

// Test 2: Check PHP-FPM status
echo "\n2. Checking PHP-FPM status...\n";
exec('systemctl is-active php8.3-fpm', $output, $returnCode);
if ($returnCode == 0) {
    echo "✅ PHP-FPM is active\n";
} else {
    echo "❌ PHP-FPM is not active\n";
}

// Test 3: Check recent errors
echo "\n3. Checking for recent memory errors...\n";
$errorLog = '/var/log/nginx/api.askproai.de.error.log';
$recentErrors = shell_exec("tail -20 $errorLog | grep -i 'memory\\|exhausted' | wc -l");
$errorCount = intval(trim($recentErrors));

if ($errorCount > 0) {
    echo "⚠️  Found $errorCount recent memory errors\n";
    echo "Recent memory errors:\n";
    echo shell_exec("tail -20 $errorLog | grep -i 'memory\\|exhausted' | tail -5");
} else {
    echo "✅ No recent memory errors found\n";
}

// Test 4: Check Livewire route
echo "\n4. Checking Livewire update route...\n";
exec('php artisan route:list | grep livewire/update', $routes);
if (!empty($routes)) {
    echo "✅ Livewire update route registered:\n";
    echo implode("\n", $routes) . "\n";
} else {
    echo "⚠️  Livewire update route not found in route list\n";
}

// Test 5: Memory usage check
echo "\n5. Current memory usage...\n";
$memoryUsage = memory_get_usage(true) / 1024 / 1024;
$memoryPeak = memory_get_peak_usage(true) / 1024 / 1024;
echo "Current: " . round($memoryUsage, 2) . " MB\n";
echo "Peak: " . round($memoryPeak, 2) . " MB\n";

echo "\n=== SUMMARY ===\n";
echo "If you still experience login issues:\n";
echo "1. Clear browser cache and cookies\n";
echo "2. Try incognito/private browsing mode\n";
echo "3. Check browser console for JavaScript errors\n";
echo "4. Monitor: tail -f /var/log/nginx/api.askproai.de.error.log\n";
echo "5. Test with: curl -X POST https://api.askproai.de/livewire/update -H 'X-Livewire: true'\n";

echo "\n=== TEST COMPLETE ===\n";