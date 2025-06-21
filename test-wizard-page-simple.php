<?php

echo "=== Testing EventTypeSetupWizard Page ===\n\n";

// Test if the page URL works
$url = '/admin/event-type-setup-wizard';
echo "1. Testing page URL: $url\n";

// Create a simple curl request to test if the page loads
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000" . $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "   ✅ Page loads successfully (HTTP 200)\n";
} elseif ($httpCode == 302 || $httpCode == 301) {
    echo "   ⚠️  Page redirects (HTTP $httpCode) - likely requires authentication\n";
} else {
    echo "   ❌ Page error (HTTP $httpCode)\n";
}

// Test database state
echo "\n2. Testing database state:\n";
try {
    require __DIR__ . '/vendor/autoload.php';
    $app = require __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    // Check companies
    $companies = \App\Models\Company::count();
    echo "   Companies in DB: $companies\n";
    
    // Check branches
    $branches = \App\Models\Branch::withoutGlobalScopes()->count();
    $activeBranches = \App\Models\Branch::withoutGlobalScopes()->where('is_active', true)->count();
    echo "   Total branches: $branches (Active: $activeBranches)\n";
    
    // Check event types
    $eventTypes = \App\Models\CalcomEventType::withoutGlobalScopes()->count();
    echo "   Event Types: $eventTypes\n";
    
} catch (\Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n3. Recommendations:\n";
echo "   - If branches don't appear, check browser console for JavaScript errors\n";
echo "   - Enable Livewire debug mode to see component updates\n";
echo "   - Check network tab for Livewire requests when selecting company\n";
echo "   - Try clearing browser cache and Laravel cache\n";

echo "\n✅ Test complete!\n";