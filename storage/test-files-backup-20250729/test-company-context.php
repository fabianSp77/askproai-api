<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/admin/calls', 'GET');
$kernel->handle($request);

use Illuminate\Support\Facades\Auth;
use App\Models\User;

echo "=== Testing Company Context Setup ===\n\n";

// Find an admin user
$adminUser = User::where('email', 'fabian@askpro.ai')->first();
if (!$adminUser) {
    $adminUser = User::whereNotNull('company_id')->first();
}

if (!$adminUser) {
    echo "❌ No user with company found!\n";
    exit;
}

echo "✓ Found user: {$adminUser->email} (ID: {$adminUser->id})\n";
echo "✓ Company ID: {$adminUser->company_id}\n\n";

// Test manual auth
Auth::login($adminUser);
echo "✓ Manually logged in user\n";

// Check if ForceCompanyContext middleware sets context
$middleware = new \App\Http\Middleware\ForceCompanyContext();
$response = $middleware->handle($request, function($req) {
    return new \Illuminate\Http\Response('OK');
});

echo "\n=== Checking App Container ===\n";
echo "current_company_id: " . (app()->has('current_company_id') ? app('current_company_id') : 'NOT SET') . "\n";
echo "company_context_source: " . (app()->has('company_context_source') ? app('company_context_source') : 'NOT SET') . "\n";

// Test with CompanyScope
echo "\n=== Testing CompanyScope ===\n";
$callsQuery = \App\Models\Call::query();
echo "Query without scope removal: " . $callsQuery->toSql() . "\n";
$bindings = $callsQuery->getBindings();
echo "Bindings: " . json_encode($bindings) . "\n";

if (app()->has('current_company_id')) {
    $callsCount = \App\Models\Call::count();
    echo "✓ Calls with CompanyScope: {$callsCount}\n";
} else {
    echo "❌ Company context not set in app container!\n";
}

echo "\n=== Direct Test of ListCalls ===\n";
try {
    // Set company context manually for test
    app()->instance('current_company_id', $adminUser->company_id);
    app()->instance('company_context_source', 'test_script');
    
    $page = new \App\Filament\Admin\Resources\CallResource\Pages\ListCalls();
    $page->mount();
    echo "✓ ListCalls mounted successfully\n";
    
    // Check if table query works
    $tableQuery = \App\Filament\Admin\Resources\CallResource::getEloquentQuery();
    $count = $tableQuery->count();
    echo "✓ Table query count: {$count}\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Done ===\n";