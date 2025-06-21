<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Filament\Admin\Resources\StaffResource;
use Illuminate\Support\Facades\Auth;

echo "\033[1;34m=== TESTING STAFF PAGE ACCESS ===\033[0m\n\n";

// Login as user
$user = User::where('email', 'fabian@askproai.de')->first();
if (!$user) {
    echo "❌ User not found\n";
    exit(1);
}

Auth::login($user);
echo "✅ Logged in as: {$user->email}\n";
echo "✅ Company ID: {$user->company_id}\n";

// Test StaffResource
echo "\n\033[1;33mTesting StaffResource:\033[0m\n";

// Check if canViewAny
$canView = StaffResource::canViewAny();
echo "- Can view any: " . ($canView ? "✅ YES" : "❌ NO") . "\n";

// Check if user can access panel
$canAccessPanel = $user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin'));
echo "- Can access panel: " . ($canAccessPanel ? "✅ YES" : "❌ NO") . "\n";

// Try to get staff query
try {
    $query = StaffResource::getEloquentQuery();
    $staffCount = $query->count();
    echo "- Staff query works: ✅ YES (found {$staffCount} staff)\n";
} catch (\Exception $e) {
    echo "- Staff query works: ❌ NO\n";
    echo "  Error: " . $e->getMessage() . "\n";
}

// Check if staff table has required columns
echo "\n\033[1;33mChecking Staff table structure:\033[0m\n";
$columns = DB::select("SHOW COLUMNS FROM staff");
$columnNames = array_map(fn($col) => $col->Field, $columns);

$requiredColumns = ['id', 'company_id', 'name', 'email', 'active'];
foreach ($requiredColumns as $col) {
    $exists = in_array($col, $columnNames);
    echo "- Column '{$col}': " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "\n";
}

echo "\n\033[1;34m=== RESULT ===\033[0m\n";
if ($canView && $canAccessPanel) {
    echo "✅ /admin/staff should now be accessible without 403 error!\n";
} else {
    echo "❌ There might still be permission issues\n";
}

Auth::logout();