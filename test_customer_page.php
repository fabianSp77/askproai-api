<?php

/**
 * TEST: CustomerResource Page Loading
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CUSTOMER RESOURCE TEST ===\n\n";

// Login as admin
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
auth()->login($user);
echo "✅ Logged in as: {$user->email}\n\n";

// Test CustomerResource query
echo "Testing CustomerResource...\n";
try {
    $resourceClass = \App\Filament\Resources\CustomerResource::class;
    $query = $resourceClass::getEloquentQuery();
    $customers = $query->limit(10)->get();

    echo "✅ CustomerResource::getEloquentQuery() succeeded\n";
    echo "   Found {$customers->count()} customers\n\n";

} catch (\Illuminate\Database\QueryException $e) {
    echo "❌ SQL ERROR:\n";
    echo "   " . $e->getMessage() . "\n\n";
    exit(1);
} catch (\Exception $e) {
    echo "❌ ERROR:\n";
    echo "   {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n\n";
    exit(1);
}

// Test ListCustomers widgets
echo "Testing ListCustomers widgets...\n";
try {
    $page = new \App\Filament\Resources\CustomerResource\Pages\ListCustomers();

    // Get widgets
    $reflection = new ReflectionClass($page);
    $method = $reflection->getMethod('getHeaderWidgets');
    $method->setAccessible(true);
    $widgets = $method->invoke($page);

    echo "✅ ListCustomers::getHeaderWidgets() succeeded\n";
    echo "   Widgets count: " . count($widgets) . "\n";

    if (count($widgets) === 0) {
        echo "   ⚠️  No widgets active (disabled due to missing columns)\n";
    }

    echo "\n";

} catch (\Exception $e) {
    echo "❌ ERROR:\n";
    echo "   {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n\n";
    exit(1);
}

echo "=== SUMMARY ===\n";
echo "✅ CustomerResource tested successfully\n";
echo "✅ /admin/customers should load without errors\n";
echo "⚠️  CustomerStatsOverview widget disabled (missing columns)\n";

exit(0);
