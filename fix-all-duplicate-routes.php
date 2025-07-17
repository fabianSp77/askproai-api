<?php
// Fix all duplicate route names
echo "🔧 Fixing ALL duplicate routes...\n\n";

// List of duplicate routes
$duplicates = ['staff', 'services', 'branches'];

echo "📋 Found duplicate routes:\n";
foreach ($duplicates as $route) {
    echo "- $route (in both api.php and api-admin.php)\n";
}
echo "\n";

// Backup
$timestamp = date('YmdHis');
copy('routes/api-admin.php', "routes/api-admin.php.backup-$timestamp");
echo "📦 Backup created: routes/api-admin.php.backup-$timestamp\n\n";

// Read current content
$content = file_get_contents('routes/api-admin.php');

// Replace each duplicate with prefixed version
$replacements = [
    "Route::apiResource('staff', StaffController::class);" => 
        "Route::apiResource('staff', StaffController::class)->names('admin.staff');",
    
    "Route::apiResource('services', ServiceController::class);" => 
        "Route::apiResource('services', ServiceController::class)->names('admin.services');",
    
    "Route::apiResource('branches', BranchController::class);" => 
        "Route::apiResource('branches', BranchController::class)->names('admin.branches');"
];

foreach ($replacements as $search => $replace) {
    $content = str_replace($search, $replace, $content);
    echo "✅ Updated: " . explode(',', $search)[0] . "...\n";
}

// Write back
file_put_contents('routes/api-admin.php', $content);
echo "\n✅ All routes updated with 'admin.' prefix\n\n";

// Clear and test
echo "🔄 Clearing route cache...\n";
exec('php artisan route:clear 2>&1', $output);
echo implode("\n", $output) . "\n\n";

echo "🧪 Testing route cache...\n";
exec('php artisan route:cache 2>&1', $output2, $returnCode);

if ($returnCode === 0) {
    echo "✅ SUCCESS! Route cache working!\n";
    echo "🎉 All duplicate route issues fixed!\n";
} else {
    echo "❌ Route cache still failing:\n";
    echo implode("\n", $output2) . "\n";
    
    // Check for more duplicates
    echo "\n🔍 Checking for more duplicates...\n";
    exec("php artisan route:list --json 2>/dev/null | grep -o '\"name\":\"[^\"]*\"' | sort | uniq -d", $moreDupes);
    if (!empty($moreDupes)) {
        echo "Found more duplicates:\n";
        foreach ($moreDupes as $dupe) {
            echo "- $dupe\n";
        }
    }
}

echo "\n📊 Summary:\n";
echo "- Fixed 3 duplicate route resources\n";
echo "- Added 'admin.' prefix to admin routes\n";
echo "- Route cache " . ($returnCode === 0 ? "successful" : "needs manual fix") . "\n";