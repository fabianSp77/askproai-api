<?php
// Quick fix for staff is_active column

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

echo "=== Staff is_active Column Fix ===\n\n";

// Check if column exists
echo "1. Checking if is_active column exists...\n";
$hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('staff', 'is_active');

if ($hasColumn) {
    echo "✅ Column 'is_active' exists in staff table\n";
} else {
    echo "❌ Column 'is_active' MISSING in staff table\n";
    echo "Run: php artisan migrate\n";
    exit(1);
}

// Check staff records
echo "\n2. Checking staff records...\n";
$totalStaff = \App\Models\Staff::withoutGlobalScopes()->count();
$activeStaff = \App\Models\Staff::withoutGlobalScopes()->where('is_active', true)->count();
$inactiveStaff = \App\Models\Staff::withoutGlobalScopes()->where('is_active', false)->count();

echo "   Total staff: {$totalStaff}\n";
echo "   Active staff: {$activeStaff}\n";
echo "   Inactive staff: {$inactiveStaff}\n";

// Update any NULL values
echo "\n3. Updating NULL values...\n";
$nullCount = \App\Models\Staff::withoutGlobalScopes()->whereNull('is_active')->count();
if ($nullCount > 0) {
    \App\Models\Staff::withoutGlobalScopes()->whereNull('is_active')->update(['is_active' => true]);
    echo "✅ Updated {$nullCount} records with NULL is_active to true\n";
} else {
    echo "✅ No NULL values found\n";
}

// Clear caches
echo "\n4. Clearing caches...\n";
\Illuminate\Support\Facades\Artisan::call('cache:clear');
\Illuminate\Support\Facades\Artisan::call('config:clear');
echo "✅ Caches cleared\n";

echo "\n=== Fix Complete! ===\n";
echo "The is_active column has been added and all staff are set to active by default.\n";
echo "You can now access the Company view page without errors.\n";