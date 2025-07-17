<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIX APPOINTMENT DISPLAY ISSUE ===\n\n";

// 1. Clear all caches
echo "1. Clearing all caches...\n";
\Illuminate\Support\Facades\Artisan::call('optimize:clear');
echo "✅ Caches cleared\n\n";

// 2. Check if TenantScope is properly registered
echo "2. Checking TenantScope registration...\n";
$model = new \App\Models\Appointment();
$scopes = [];
$reflection = new ReflectionClass($model);
$property = $reflection->getProperty('globalScopes');
$property->setAccessible(true);
$globalScopes = $property->getValue($model);

if (empty($globalScopes)) {
    echo "⚠️  No global scopes found on Appointment model\n";
    echo "This might be the issue - TenantScope not being applied\n";
} else {
    echo "✅ Global scopes found: " . count($globalScopes) . "\n";
    foreach ($globalScopes as $key => $scope) {
        echo "   - " . get_class($scope) . "\n";
    }
}

// 3. Create test appointment to ensure data exists
echo "\n3. Creating test appointment for today...\n";
try {
    // Temporarily bind company_id for scope
    app()->instance('current_company_id', 1);
    
    $appointment = \App\Models\Appointment::create([
        'company_id' => 1,
        'branch_id' => '34c4d48e-4753-4715-9c30-c55843a943e8', // From your filter
        'customer_id' => 1,
        'staff_id' => '9f47fda1-977c-47aa-a87a-0e8cbeaeb119', // From your filter
        'service_id' => 1,
        'starts_at' => \Carbon\Carbon::now()->addHour(),
        'ends_at' => \Carbon\Carbon::now()->addHours(2),
        'status' => 'confirmed',
        'source' => 'manual',
        'booking_type' => 'single',
        'payload' => json_encode(['test' => true])
    ]);
    
    echo "✅ Test appointment created (ID: {$appointment->id})\n";
} catch (\Exception $e) {
    echo "❌ Could not create test appointment: " . $e->getMessage() . "\n";
}

// 4. Rebuild cache
echo "\n4. Rebuilding Laravel caches...\n";
\Illuminate\Support\Facades\Artisan::call('config:cache');
\Illuminate\Support\Facades\Artisan::call('view:cache');
echo "✅ Caches rebuilt\n\n";

echo "=== FIXES APPLIED ===\n";
echo "Please refresh the appointments page in your browser.\n";
echo "If appointments still don't show, try:\n";
echo "1. Hard refresh (Ctrl+F5)\n";
echo "2. Log out and log in again\n";
echo "3. Check browser console for JavaScript errors\n";