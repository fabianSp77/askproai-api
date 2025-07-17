<?php
// Fix ALL remaining route conflicts

echo "🔧 Fixing ALL remaining route conflicts...\n\n";

$filesToFix = [
    'routes/api.php' => [
        ['search' => "Route::apiResource('staff', App\Http\Controllers\API\StaffController::class);", 
         'replace' => "Route::apiResource('staff', App\Http\Controllers\API\StaffController::class)->names('api.staff');"],
        ['search' => "Route::apiResource('services', App\Http\Controllers\API\ServiceController::class);", 
         'replace' => "Route::apiResource('services', App\Http\Controllers\API\ServiceController::class)->names('api.services');"],
        ['search' => "Route::apiResource('calls', App\Http\Controllers\API\CallController::class);", 
         'replace' => "Route::apiResource('calls', App\Http\Controllers\API\CallController::class)->names('api.calls');"],
        ['search' => "Route::apiResource('appointments', App\Http\Controllers\API\AppointmentController::class);", 
         'replace' => "Route::apiResource('appointments', App\Http\Controllers\API\AppointmentController::class)->names('api.appointments');"],
        ['search' => "Route::apiResource('customers', App\Http\Controllers\API\CustomerController::class);", 
         'replace' => "Route::apiResource('customers', App\Http\Controllers\API\CustomerController::class)->names('api.customers');"],
    ],
    'routes/api-v2.php' => [
        ['search' => "Route::apiResource('appointments', AppointmentController::class);", 
         'replace' => "Route::apiResource('appointments', AppointmentController::class)->names('api.v2.appointments');"],
        ['search' => "Route::apiResource('customers', CustomerController::class);", 
         'replace' => "Route::apiResource('customers', CustomerController::class)->names('api.v2.customers');"],
    ]
];

// Process each file
foreach ($filesToFix as $file => $replacements) {
    if (!file_exists($file)) {
        echo "⚠️  File not found: $file\n";
        continue;
    }
    
    // Backup
    $backup = $file . '.backup-' . date('YmdHis');
    copy($file, $backup);
    echo "📦 Backed up: $file\n";
    
    // Read content
    $content = file_get_contents($file);
    $changed = false;
    
    // Apply replacements
    foreach ($replacements as $replacement) {
        if (strpos($content, $replacement['search']) !== false) {
            $content = str_replace($replacement['search'], $replacement['replace'], $content);
            echo "✅ Fixed: " . substr($replacement['search'], 0, 50) . "...\n";
            $changed = true;
        }
    }
    
    // Write back if changed
    if ($changed) {
        file_put_contents($file, $content);
        echo "💾 Updated: $file\n\n";
    } else {
        echo "ℹ️  No changes needed in: $file\n\n";
    }
}

// Also check business-portal routes
if (file_exists('routes/business-portal.php')) {
    $businessContent = file_get_contents('routes/business-portal.php');
    if (strpos($businessContent, "->name('business.api.appointments.show')") !== false &&
        strpos($businessContent, "Route::get('/appointments/{appointment}'") !== false) {
        echo "🔍 Found duplicate route in business-portal.php\n";
        echo "This needs manual review - two routes with same name\n\n";
    }
}

// Clear and test
echo "🔄 Clearing route cache...\n";
exec('php artisan route:clear 2>&1', $output);

echo "\n🧪 Testing route cache...\n";
exec('php artisan route:cache 2>&1', $cacheOutput, $returnCode);

if ($returnCode === 0) {
    echo "✅ SUCCESS! All route conflicts resolved!\n";
    echo "🎉 Route cache is working!\n";
} else {
    echo "❌ Still having issues:\n";
    echo implode("\n", $cacheOutput) . "\n";
    
    // Extract the specific error
    foreach ($cacheOutput as $line) {
        if (strpos($line, 'Unable to prepare route') !== false) {
            echo "\n⚠️  Specific issue: $line\n";
        }
    }
}

echo "\n📊 Summary:\n";
echo "- Fixed routes in api.php with 'api.' prefix\n";
echo "- Fixed routes in api-v2.php with 'api.v2.' prefix\n";
echo "- Admin routes already have 'admin.' prefix\n";
echo "- Route cache: " . ($returnCode === 0 ? "✅ Working" : "❌ Still has issues") . "\n";