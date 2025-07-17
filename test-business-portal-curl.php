#!/usr/bin/env php
<?php

echo "=== Testing Business Portal Admin with CURL ===\n\n";

// Get cookies from a valid session
$cookieFile = __DIR__ . '/cookies.txt';
if (!file_exists($cookieFile)) {
    echo "Cookie file not found. Cannot authenticate.\n";
    exit(1);
}

// Make request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.askproai.de/admin/business-portal-admin');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
]);

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n\n";

if ($httpCode !== 200) {
    echo "Failed to load page. Response:\n";
    echo substr($html, 0, 1000) . "...\n";
    exit(1);
}

// Analyze the HTML
echo "1. Page Size: " . strlen($html) . " bytes\n\n";

// Check for our UI fix scripts
echo "2. UI Fix Scripts:\n";
$scripts = [
    'unified-ui-fix-v2.js' => 'Unified UI Fix V2',
    'emergency-button-fix.js' => 'Emergency Button Fix',
    'debug-loading-sequence.js' => 'Debug Loading Sequence',
    'dropdown-close-fix.js' => 'Dropdown Close Fix (Missing?)'
];

foreach ($scripts as $script => $name) {
    if (strpos($html, $script) !== false) {
        echo "   ✓ $name is included\n";
    } else {
        echo "   ✗ $name is NOT included\n";
    }
}

// Check for key UI elements
echo "\n3. Key UI Elements:\n";
$elements = [
    'fi-topbar-open-sidebar-btn' => 'Mobile Menu Button',
    'selectedCompanyId' => 'Company Selector',
    'openCustomerPortal' => 'Portal Button',
    'fi-dropdown' => 'Dropdown Components',
    'wire:model' => 'Livewire Models',
    'wire:click' => 'Livewire Click Handlers',
    'x-data' => 'Alpine Components'
];

foreach ($elements as $selector => $name) {
    $count = substr_count($html, $selector);
    echo "   - $name: $count occurrences\n";
}

// Extract all script tags
echo "\n4. All Script Tags:\n";
preg_match_all('/<script[^>]*src="([^"]+)"/', $html, $matches);
foreach ($matches[1] as $i => $script) {
    echo "   " . ($i + 1) . ". $script\n";
    if ($i >= 10) {
        echo "   ... and " . (count($matches[1]) - 10) . " more\n";
        break;
    }
}

// Check for inline scripts
echo "\n5. Inline Scripts:\n";
preg_match_all('/<script(?![^>]*src)[^>]*>(.*?)<\/script>/s', $html, $inlineScripts);
echo "   Found " . count($inlineScripts[1]) . " inline script blocks\n";

// Look for our specific inline fixes
foreach ($inlineScripts[1] as $script) {
    if (strpos($script, 'Unified UI Fix') !== false) {
        echo "   ✓ Found Unified UI Fix inline code\n";
    }
    if (strpos($script, 'Emergency Button Fix') !== false) {
        echo "   ✓ Found Emergency Button Fix inline code\n";
    }
    if (strpos($script, 'redirect-to-portal') !== false) {
        echo "   ✓ Found portal redirect listener\n";
    }
}

// Check for potential issues
echo "\n6. Potential Issues:\n";
if (strpos($html, 'dropdown-close-fix.js') !== false) {
    echo "   ⚠️  Page references dropdown-close-fix.js (404 error)\n";
    
    // Find where it's referenced
    preg_match('/.*dropdown-close-fix\.js.*/', $html, $lineMatch);
    if ($lineMatch) {
        echo "   Reference: " . trim(strip_tags($lineMatch[0])) . "\n";
    }
}

// Save a snippet for manual inspection
$snippetFile = __DIR__ . '/business-portal-snippet.html';
file_put_contents($snippetFile, $html);
echo "\n7. Full HTML saved to: $snippetFile\n";

echo "\nDone!\n";