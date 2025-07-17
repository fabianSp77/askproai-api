<?php
// Direct test of Business Portal Admin page functionality

require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('/admin/business-portal-admin', 'GET')
);

$html = $response->getContent();

echo "=== Business Portal Admin Page Analysis ===\n\n";

// Check for script inclusions
echo "1. Script Inclusions:\n";
preg_match_all('/<script[^>]*src="([^"]+)"[^>]*>/', $html, $scriptMatches);
$foundScripts = [];
foreach ($scriptMatches[1] as $script) {
    $foundScripts[] = $script;
    if (strpos($script, 'unified-ui-fix') !== false || 
        strpos($script, 'emergency-button') !== false ||
        strpos($script, 'debug-loading') !== false) {
        echo "   ✓ Found: $script\n";
    }
}

// Check for missing dropdown-close-fix.js
if (strpos($html, 'dropdown-close-fix.js') !== false) {
    echo "   ⚠️  WARNING: Page references dropdown-close-fix.js which doesn't exist!\n";
}

// Check for Livewire components
echo "\n2. Livewire Components:\n";
preg_match_all('/wire:id="([^"]+)"/', $html, $wireIds);
echo "   Found " . count($wireIds[1]) . " Livewire components\n";

// Check for wire:model elements
preg_match_all('/wire:model="([^"]+)"/', $html, $wireModels);
echo "\n3. Wire Models:\n";
foreach (array_unique($wireModels[1]) as $model) {
    echo "   - $model\n";
}

// Check for wire:click elements
preg_match_all('/wire:click="([^"]+)"/', $html, $wireClicks);
echo "\n4. Wire Click Actions:\n";
foreach (array_unique($wireClicks[1]) as $click) {
    echo "   - $click\n";
}

// Check for Alpine components
echo "\n5. Alpine Components:\n";
preg_match_all('/x-data="([^"]+)"/', $html, $alpineData);
echo "   Found " . count($alpineData[1]) . " Alpine components\n";

// Check for specific UI elements
echo "\n6. UI Elements:\n";
$elements = [
    'Mobile menu button' => 'fi-topbar-open-sidebar-btn',
    'Company selector' => 'selectedCompanyId',
    'Portal buttons' => 'openCustomerPortal',
    'Dropdowns' => 'fi-dropdown',
];

foreach ($elements as $name => $selector) {
    $count = substr_count($html, $selector);
    echo "   - $name: $count occurrences\n";
}

// Check for error messages or issues
echo "\n7. Potential Issues:\n";
if (strpos($html, 'x-cloak') !== false) {
    echo "   - x-cloak attributes found (may cause visibility issues)\n";
}
if (strpos($html, 'wire:loading') !== false) {
    echo "   - wire:loading found (may block interactions)\n";
}
if (strpos($html, 'disabled') !== false) {
    echo "   - disabled attributes found\n";
}

// Check authentication
echo "\n8. Authentication Status:\n";
if (strpos($html, 'Logout') !== false || strpos($html, 'logout') !== false) {
    echo "   ✓ User appears to be logged in\n";
} else {
    echo "   ⚠️  No logout button found - user may not be authenticated\n";
}

// Output a snippet of the page for debugging
echo "\n9. Page Snippet (first 500 chars after <body>):\n";
$bodyPos = strpos($html, '<body');
if ($bodyPos !== false) {
    $snippet = substr($html, $bodyPos, 500);
    echo "   " . str_replace("\n", "\n   ", $snippet) . "...\n";
}

echo "\n10. Script Loading Order:\n";
foreach ($foundScripts as $i => $script) {
    echo "   " . ($i + 1) . ". $script\n";
}

echo "\nDone!\n";