<?php

/**
 * TEST: Call Widgets with Profit Columns Fix
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CALL WIDGETS TEST ===\n\n";

// Login as admin
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
auth()->login($user);
echo "✅ Logged in as: {$user->email}\n\n";

// Test CallStatsOverview Widget
echo "Testing CallStatsOverview Widget...\n";
try {
    $widget = new \App\Filament\Resources\CallResource\Widgets\CallStatsOverview();

    // Try to get stats (this is what fails with cost_cents)
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('calculateStats');
    $method->setAccessible(true);

    $stats = $method->invoke($widget);

    echo "✅ CallStatsOverview::calculateStats() succeeded\n";
    echo "   Stats count: " . count($stats) . "\n\n";

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

// Test RecentCallsActivity Widget
echo "Testing RecentCallsActivity Widget...\n";
try {
    $widget = new \App\Filament\Resources\CallResource\Widgets\RecentCallsActivity();

    // Get table query
    $reflection = new ReflectionClass($widget);
    $method = $reflection->getMethod('getTableQuery');
    $method->setAccessible(true);

    $query = $method->invoke($widget);
    $calls = $query->get();

    echo "✅ RecentCallsActivity::getTableQuery() succeeded\n";
    echo "   Calls found: " . $calls->count() . "\n\n";

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

echo "=== SUMMARY ===\n";
echo "✅ All Call widgets tested successfully\n";
echo "✅ No cost_cents or profit column errors\n";
echo "✅ CallResource should load without errors\n";

exit(0);
