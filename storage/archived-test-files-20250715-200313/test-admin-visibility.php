<?php
/**
 * Test admin portal visibility
 */

// Quick test to see if admin portal HTML is rendering
$ch = curl_init('https://api.askproai.de/admin/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n\n";

// Check for key elements
$checks = [
    'Has HTML' => strpos($html, '<html') !== false,
    'Has Body' => strpos($html, '<body') !== false,
    'Has Filament' => strpos($html, 'fi-body') !== false,
    'Has Emergency CSS' => strpos($html, 'admin-emergency-fix.css') !== false,
    'Has Visibility Fix' => strpos($html, 'opacity: 1 !important') !== false,
    'Has Login Form' => strpos($html, 'login') !== false || strpos($html, 'email') !== false,
    'Has Alpine' => strpos($html, 'x-data') !== false,
    'Has Livewire' => strpos($html, 'wire:') !== false,
];

echo "Visibility Checks:\n";
echo str_repeat('-', 50) . "\n";
foreach ($checks as $test => $result) {
    echo sprintf("%-25s: %s\n", $test, $result ? '✅ PASS' : '❌ FAIL');
}

// Check for potential blockers
echo "\n\nPotential Issues:\n";
echo str_repeat('-', 50) . "\n";

if (strpos($html, 'display: none') !== false) {
    echo "⚠️  Found 'display: none' in HTML\n";
}

if (strpos($html, 'opacity: 0') !== false && strpos($html, 'opacity: 1 !important') === false) {
    echo "⚠️  Found 'opacity: 0' without override\n";
}

if (strpos($html, 'visibility: hidden') !== false && strpos($html, 'visibility: visible !important') === false) {
    echo "⚠️  Found 'visibility: hidden' without override\n";
}

if (preg_match('/x-cloak/i', $html)) {
    echo "⚠️  Found x-cloak directives (Alpine.js might not be initializing)\n";
}

// Extract any JavaScript errors
if (preg_match_all('/console\.(error|warn)\([\'"]([^\'"]+)[\'"]\)/', $html, $matches)) {
    echo "\n⚠️  JavaScript console messages:\n";
    foreach ($matches[2] as $msg) {
        echo "   - $msg\n";
    }
}

echo "\n\n💡 Direct Links:\n";
echo "- Admin Portal: https://api.askproai.de/admin/\n";
echo "- Test Page: https://api.askproai.de/admin-fix-test.html\n";
echo "- CSS Debug: https://api.askproai.de/test-admin-css.html\n";