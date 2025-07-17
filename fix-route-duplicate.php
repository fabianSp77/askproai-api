<?php
// Fix duplicate route names
// This script analyzes and fixes the duplicate staff.index route issue

echo "🔧 Fixing duplicate route issue...\n\n";

// Read route files
$apiRoutes = file_get_contents('routes/api.php');
$apiAdminRoutes = file_get_contents('routes/api-admin.php');

echo "📋 Current situation:\n";
echo "- routes/api.php has: Route::apiResource('staff', ...)\n";
echo "- routes/api-admin.php has: Route::apiResource('staff', ...)\n";
echo "\nThis creates duplicate route names like 'staff.index'\n\n";

echo "✅ Solution: Add name prefix to api-admin routes\n\n";

// Backup first
copy('routes/api-admin.php', 'routes/api-admin.php.backup-' . date('YmdHis'));
echo "📦 Backup created: routes/api-admin.php.backup-" . date('YmdHis') . "\n\n";

// Fix the api-admin routes by adding a name prefix
$updatedContent = str_replace(
    "Route::apiResource('staff', StaffController::class);",
    "Route::apiResource('staff', StaffController::class)->names(['index' => 'api.admin.staff.index', 'store' => 'api.admin.staff.store', 'show' => 'api.admin.staff.show', 'update' => 'api.admin.staff.update', 'destroy' => 'api.admin.staff.destroy']);",
    $apiAdminRoutes
);

// Write the updated content
file_put_contents('routes/api-admin.php', $updatedContent);

echo "✅ Fixed routes/api-admin.php\n\n";

// Clear route cache
echo "🔄 Clearing route cache...\n";
exec('php artisan route:clear', $output, $returnCode);
if ($returnCode === 0) {
    echo "✅ Route cache cleared\n\n";
} else {
    echo "⚠️  Could not clear route cache\n\n";
}

// Test route caching
echo "🧪 Testing route cache...\n";
exec('php artisan route:cache 2>&1', $output, $returnCode);
if ($returnCode === 0) {
    echo "✅ Route cache successful!\n";
    echo "🎉 Problem fixed!\n";
} else {
    echo "❌ Route cache still failing\n";
    echo "Output: " . implode("\n", $output) . "\n";
    echo "\nTrying alternative fix...\n";
    
    // Alternative: Change the URI instead
    $alternativeContent = str_replace(
        "Route::apiResource('staff', StaffController::class)",
        "Route::apiResource('admin-staff', StaffController::class)",
        file_get_contents('routes/api-admin.php.backup-' . date('YmdHis'))
    );
    
    file_put_contents('routes/api-admin.php', $alternativeContent);
    
    exec('php artisan route:clear', $output2);
    exec('php artisan route:cache 2>&1', $output2, $returnCode2);
    
    if ($returnCode2 === 0) {
        echo "✅ Alternative fix successful!\n";
        echo "Changed route from 'staff' to 'admin-staff' in api-admin.php\n";
    } else {
        echo "❌ Both fixes failed. Manual intervention needed.\n";
        echo "Please check routes/api.php and routes/api-admin.php\n";
    }
}

echo "\n📊 Summary:\n";
echo "- Backup created\n";
echo "- Routes updated\n";
echo "- Cache cleared\n";
echo "\nNext step: Test the application to ensure routes work correctly\n";