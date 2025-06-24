<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Company;
use App\Models\Branch;

echo "=== UI Rendering Check ===\n\n";

// Login as admin
$user = User::where('email', 'admin@askproai.de')->first();
if (!$user) {
    $user = User::first();
}

if ($user) {
    auth()->login($user);
    echo "✓ Logged in as: " . $user->email . "\n";
} else {
    echo "✗ No user found\n";
    exit(1);
}

// Set company context
$company = Company::first();
if ($company) {
    app()->instance('current_company', $company);
    echo "✓ Company context set: " . $company->name . "\n";
}

// Check database state
echo "\n=== Database State ===\n";
echo "Branches: " . Branch::count() . "\n";
echo "Active Branches: " . Branch::where('is_active', true)->count() . "\n";

// Check for branches with event types
$branchesWithEventTypes = Branch::has('eventTypes')->count();
echo "Branches with Event Types: " . $branchesWithEventTypes . "\n";

// Check CSS files
echo "\n=== CSS Files Check ===\n";
$cssFiles = [
    'public/build/assets/company-integration-portal-B0aoE1gJ.css',
    'public/build/assets/responsive-fixes-BZRNsz_u.css',
];

foreach ($cssFiles as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists (" . filesize($file) . " bytes)\n";
        
        // Check for key CSS rules
        $content = file_get_contents($file);
        if (strpos($content, 'branch-card') !== false) {
            echo "  ✓ Contains branch-card styles\n";
        }
        if (strpos($content, 'z-index:20') !== false || strpos($content, 'z-index: 20') !== false) {
            echo "  ✓ Contains z-index fixes\n";
        }
    } else {
        echo "✗ $file NOT FOUND\n";
    }
}

// Check view files
echo "\n=== View Files Check ===\n";
$viewFile = 'resources/views/filament/admin/pages/company-integration-portal.blade.php';
if (file_exists($viewFile)) {
    $content = file_get_contents($viewFile);
    if (strpos($content, '@vite') !== false) {
        echo "✓ View contains @vite directives\n";
    }
    if (strpos($content, 'company-integration-portal.css') !== false) {
        echo "✓ View includes company-integration-portal.css\n";
    }
    if (strpos($content, 'responsive-fixes.css') !== false) {
        echo "✓ View includes responsive-fixes.css\n";
    }
}

// Check manifest
echo "\n=== Build Manifest Check ===\n";
$manifest = json_decode(file_get_contents('public/build/manifest.json'), true);
if (isset($manifest['resources/css/filament/admin/company-integration-portal.css'])) {
    echo "✓ company-integration-portal.css in manifest\n";
}
if (isset($manifest['resources/css/filament/admin/responsive-fixes.css'])) {
    echo "✓ responsive-fixes.css in manifest\n";
}

echo "\n=== Summary ===\n";
echo "If all checks above show ✓, the UI should be working.\n";
echo "Make sure to:\n";
echo "1. Clear browser cache (Ctrl+Shift+R)\n";
echo "2. Check browser console for errors\n";
echo "3. Verify you're on the correct page: /admin/company-integration-portal\n";