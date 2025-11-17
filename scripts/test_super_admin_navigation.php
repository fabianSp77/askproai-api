<?php
/**
 * Test Super Admin Navigation - With Real User
 * Tests shouldRegisterNavigation() as authenticated super_admin
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║   SUPER ADMIN NAVIGATION TEST (With Authentication)           ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Find super_admin user
$superAdmin = User::whereHas('roles', function($q) {
    $q->where('name', 'super_admin');
})->first();

if (!$superAdmin) {
    echo "❌ ERROR: No super_admin user found!\n";
    echo "   Please ensure a user with super_admin role exists.\n\n";
    exit(1);
}

echo "👤 Testing as: {$superAdmin->email}\n";
echo "🔐 Role: super_admin\n";
echo "\n";

// Login as super_admin
auth()->login($superAdmin);

// Get all resource files
$resourcesPath = app_path('Filament/Resources');
$resourceFiles = glob($resourcesPath . '/*Resource.php');

$visibleResources = [];
$hiddenResources = [];

echo "📊 Testing " . count($resourceFiles) . " Resources...\n";
echo "─────────────────────────────────────────────────────────────────\n\n";

foreach ($resourceFiles as $file) {
    $resourceName = basename($file, '.php');
    $className = "App\\Filament\\Resources\\{$resourceName}";

    if (!class_exists($className)) {
        continue;
    }

    // Test shouldRegisterNavigation() as authenticated super_admin
    $isVisible = true;
    if (method_exists($className, 'shouldRegisterNavigation')) {
        $isVisible = $className::shouldRegisterNavigation();
    }

    // Get navigation label
    $content = file_get_contents($file);
    $navigationLabel = '';
    if (preg_match('/protected static \?string \$navigationLabel = [\'"]([^\'"]+)[\'"]/s', $content, $matches)) {
        $navigationLabel = $matches[1];
    }

    if ($isVisible) {
        $visibleResources[] = [
            'name' => $resourceName,
            'label' => $navigationLabel ?: $resourceName,
        ];
    } else {
        $hiddenResources[] = [
            'name' => $resourceName,
            'label' => $navigationLabel ?: $resourceName,
        ];
    }
}

// Display results
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║             ✅ VISIBLE RESOURCES (Super Admin)                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

foreach ($visibleResources as $resource) {
    echo "  ✅ {$resource['label']}\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║             ❌ HIDDEN RESOURCES (Still Blocked)                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

if (empty($hiddenResources)) {
    echo "  🎉 NONE! All resources are visible!\n";
} else {
    foreach ($hiddenResources as $resource) {
        echo "  ❌ {$resource['label']}\n";
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                        SUMMARY                                 ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$total = count($visibleResources) + count($hiddenResources);
$visibleCount = count($visibleResources);
$hiddenCount = count($hiddenResources);
$percentage = round($visibleCount / $total * 100, 1);

echo "  Total Resources:       {$total}\n";
echo "  ✅ Visible:            {$visibleCount} ({$percentage}%)\n";
echo "  ❌ Hidden:             {$hiddenCount} (" . round($hiddenCount / $total * 100, 1) . "%)\n";
echo "\n";

if ($hiddenCount === 0) {
    echo "🎉 SUCCESS: ALL RESOURCES VISIBLE FOR SUPER ADMIN!\n";
} else {
    echo "⚠️  WARNING: {$hiddenCount} resources still hidden (unexpected)\n";
}

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "Test Complete: " . date('Y-m-d H:i:s') . "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "\n";

// Logout
auth()->logout();
