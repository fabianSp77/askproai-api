<?php

echo "=== CLEAN DESIGN VERIFICATION - Issue #510 ===\n\n";

// Test login page
echo "1. Testing Login Page Design...\n";
$ch = curl_init('https://api.askproai.de/admin/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$loginHtml = curl_exec($ch);
curl_close($ch);

// Extract and analyze design elements
$designChecks = [
    'Clean CSS loaded' => (strpos($loginHtml, 'clean-professional.css') !== false || strpos($loginHtml, 'filament-Cg0SmsET.css') !== false),
    'Nuclear CSS removed' => (strpos($loginHtml, 'nuclear-fix.css') === false && strpos($loginHtml, 'filament.admin-B4vaIg1z.css') === false),
    'No red banners' => (strpos($loginHtml, 'NUCLEAR FIX ACTIVE') === false && strpos($loginHtml, 'background: #dc2626') === false),
    'No debug divs' => (strpos($loginHtml, 'admin-fix-debug') === false && strpos($loginHtml, 'navigation-fix-debug') === false),
    'Professional colors' => (strpos($loginHtml, '--primary: #3b82f6') !== false),
    'No console errors' => (strpos($loginHtml, 'console.error') === false),
    'Clean navigation JS' => (strpos($loginHtml, 'clean-navigation.js') !== false || strpos($loginHtml, 'filament.clean-navigation') !== false)
];

foreach ($designChecks as $check => $result) {
    echo "   " . ($result ? "✅" : "❌") . " $check\n";
}

// Check CSS specifics
echo "\n2. CSS Analysis:\n";
if (preg_match_all('/<link[^>]*href="[^"]*\.css[^"]*"[^>]*>/', $loginHtml, $cssMatches)) {
    echo "   Total CSS files loaded: " . count($cssMatches[0]) . "\n";
    foreach ($cssMatches[0] as $cssLink) {
        if (strpos($cssLink, 'clean-professional') !== false || strpos($cssLink, 'filament-Cg0SmsET') !== false) {
            echo "   ✅ Clean design CSS: $cssLink\n";
        } elseif (strpos($cssLink, 'nuclear') !== false || strpos($cssLink, 'B4vaIg1z') !== false) {
            echo "   ❌ Nuclear CSS still loaded: $cssLink\n";
        }
    }
}

// Check JavaScript
echo "\n3. JavaScript Analysis:\n";
if (preg_match_all('/<script[^>]*src="[^"]*\.js[^"]*"[^>]*>/', $loginHtml, $jsMatches)) {
    echo "   Total JS files loaded: " . count($jsMatches[0]) . "\n";
    foreach ($jsMatches[0] as $jsScript) {
        if (strpos($jsScript, 'clean-navigation') !== false || strpos($jsScript, 'BKjKyjRZ') !== false) {
            echo "   ✅ Clean navigation JS: " . substr($jsScript, 0, 100) . "...\n";
        } elseif (strpos($jsScript, 'nuclear-unblock') !== false || strpos($jsScript, 'CLwT45xe') !== false) {
            echo "   ❌ Nuclear JS still loaded: " . substr($jsScript, 0, 100) . "...\n";
        }
    }
}

// Check for problematic inline styles
echo "\n4. Inline Styles Check:\n";
$inlinePointerEvents = preg_match_all('/style="[^"]*pointer-events:\s*auto\s*!important[^"]*"/', $loginHtml);
$inlineZIndex = preg_match_all('/style="[^"]*z-index:\s*999999[^"]*"/', $loginHtml);
echo "   Inline pointer-events overrides: $inlinePointerEvents\n";
echo "   Inline high z-index: $inlineZIndex\n";

// Visual elements check
echo "\n5. Visual Design Elements:\n";
$visualChecks = [
    'Login form present' => strpos($loginHtml, 'wire:submit="authenticate"') !== false,
    'Email input field' => strpos($loginHtml, 'type="email"') !== false,
    'Password field' => strpos($loginHtml, 'wire:model="data.password"') !== false,
    'Submit button' => strpos($loginHtml, 'type="submit"') !== false,
    'Filament branding' => strpos($loginHtml, 'fi-logo') !== false
];

foreach ($visualChecks as $element => $present) {
    echo "   " . ($present ? "✅" : "❌") . " $element\n";
}

echo "\n=== SUMMARY ===\n";
$totalChecks = count($designChecks);
$passedChecks = count(array_filter($designChecks));
$percentage = round(($passedChecks / $totalChecks) * 100);

echo "Design Quality Score: $passedChecks/$totalChecks ($percentage%)\n";

if ($percentage >= 90) {
    echo "✅ Clean professional design is successfully implemented!\n";
} elseif ($percentage >= 70) {
    echo "⚠️  Design is mostly clean but has some issues remaining.\n";
} else {
    echo "❌ Design still has significant issues.\n";
}

echo "\nNext steps:\n";
echo "1. Clear browser cache and reload https://api.askproai.de/admin/login\n";
echo "2. Verify no red banners or debug messages appear\n";
echo "3. Check that the login form has professional styling\n";
echo "4. Test that all navigation links work properly\n";
?>