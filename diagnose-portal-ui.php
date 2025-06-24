<?php

// Diagnostic script for Company Integration Portal UI issues

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 Company Integration Portal UI Diagnostics\n";
echo "==========================================\n\n";

// 1. Check if view exists
$viewPath = resource_path('views/filament/admin/pages/company-integration-portal-fixed-v2.blade.php');
if (file_exists($viewPath)) {
    echo "✅ View file exists: company-integration-portal-fixed-v2.blade.php\n";
    echo "   Size: " . filesize($viewPath) . " bytes\n";
    echo "   Modified: " . date('Y-m-d H:i:s', filemtime($viewPath)) . "\n";
} else {
    echo "❌ View file missing: company-integration-portal-fixed-v2.blade.php\n";
}

// 2. Check CSS files
echo "\n📄 CSS Files:\n";
$cssFiles = [
    'resources/css/filament/admin/theme.css',
    'resources/css/filament/admin/company-integration-portal.css',
    'resources/css/filament/admin/company-integration-portal-clean.css',
    'resources/css/filament/admin/company-portal-fix.css'
];

foreach ($cssFiles as $css) {
    if (file_exists($css)) {
        echo "   ✅ $css (". filesize($css) . " bytes)\n";
    } else {
        echo "   ❌ $css (missing)\n";
    }
}

// 3. Check compiled assets
echo "\n🏗️ Compiled Assets:\n";
$manifest = public_path('build/manifest.json');
if (file_exists($manifest)) {
    $manifestData = json_decode(file_get_contents($manifest), true);
    echo "   ✅ Manifest exists with " . count($manifestData) . " entries\n";
    
    // Check for our specific CSS
    if (isset($manifestData['resources/css/filament/admin/company-integration-portal-clean.css'])) {
        echo "   ✅ company-integration-portal-clean.css is compiled\n";
    } else {
        echo "   ⚠️  company-integration-portal-clean.css not in manifest\n";
    }
} else {
    echo "   ❌ No build manifest found - run 'npm run build'\n";
}

// 4. Check JavaScript files
echo "\n📜 JavaScript Files:\n";
$jsFiles = [
    'resources/js/app.js',
    'resources/js/company-integration-portal.js',
    'resources/js/company-integration-portal-clean.js'
];

foreach ($jsFiles as $js) {
    if (file_exists($js)) {
        echo "   ✅ $js (". filesize($js) . " bytes)\n";
    } else {
        echo "   ❌ $js (missing)\n";
    }
}

// 5. Check Livewire component
echo "\n⚡ Livewire Component:\n";
$componentPath = app_path('Filament/Admin/Pages/CompanyIntegrationPortal.php');
if (file_exists($componentPath)) {
    $content = file_get_contents($componentPath);
    if (strpos($content, 'company-integration-portal-fixed-v2') !== false) {
        echo "   ✅ Component using correct view: company-integration-portal-fixed-v2\n";
    } else {
        echo "   ❌ Component not using the fixed view\n";
    }
} else {
    echo "   ❌ Component file missing\n";
}

// 6. Check for potential conflicts
echo "\n⚠️  Potential Conflicts:\n";
$viewsDir = resource_path('views/filament/admin/pages');
$portalViews = glob($viewsDir . '/company-integration-portal*.blade.php');
echo "   Found " . count($portalViews) . " portal view variants:\n";
foreach ($portalViews as $view) {
    echo "   - " . basename($view) . "\n";
}

// 7. Recommendations
echo "\n💡 Recommendations:\n";
echo "1. Run: chmod +x COMPANY_INTEGRATION_PORTAL_FIX_COMMANDS.sh\n";
echo "2. Run: ./COMPANY_INTEGRATION_PORTAL_FIX_COMMANDS.sh\n";
echo "3. Clear browser cache completely\n";
echo "4. Check browser console for JavaScript errors\n";
echo "5. Verify no browser extensions are interfering\n";

echo "\n✨ If all checks pass but UI still broken:\n";
echo "   - Take a screenshot of browser console errors\n";
echo "   - Check Network tab for 404 errors\n";
echo "   - Try a different browser\n";
echo "   - Check if Livewire is loading (window.Livewire should exist)\n";