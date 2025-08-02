<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\User;
use App\Models\Call;
use Illuminate\Support\Facades\Auth;

// Find admin user
$adminUser = User::where('email', 'fabian@askproai.de')->orWhere('email', 'admin@askproai.de')->first();

if (!$adminUser) {
    die("No admin user found!");
}

// Login as admin
Auth::login($adminUser);

echo "=== Direct Filament Test ===\n\n";
echo "User: {$adminUser->email} (Company ID: {$adminUser->company_id})\n\n";

// Force set company context using all methods
app()->instance('current_company_id', $adminUser->company_id);
app()->instance('company_context_source', 'web_auth');

echo "Company Context Set:\n";
echo "- current_company_id: " . app('current_company_id') . "\n";
echo "- company_context_source: " . app('company_context_source') . "\n\n";

// Test CompanyScope directly
echo "=== Testing CompanyScope ===\n";
$scope = new \App\Models\Scopes\CompanyScope();
$reflection = new ReflectionClass($scope);
$method = $reflection->getMethod('getCompanyId');
$method->setAccessible(true);
$companyId = $method->invoke($scope);
echo "CompanyScope->getCompanyId(): " . ($companyId ?? 'NULL') . "\n\n";

// Test Call query
echo "=== Testing Call Query ===\n";
try {
    $query = Call::query();
    echo "SQL: " . $query->toSql() . "\n";
    echo "Bindings: " . json_encode($query->getBindings()) . "\n";
    $count = $query->count();
    echo "Count: $count\n\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

// Test CallResource query
echo "=== Testing CallResource Query ===\n";
try {
    $resourceQuery = \App\Filament\Admin\Resources\CallResource::getEloquentQuery();
    echo "SQL: " . $resourceQuery->toSql() . "\n";
    echo "Bindings: " . json_encode($resourceQuery->getBindings()) . "\n";
    $count = $resourceQuery->count();
    echo "Count: $count\n\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

// Test if ListCalls page would work
echo "=== Testing ListCalls Page ===\n";
try {
    $page = new \App\Filament\Admin\Resources\CallResource\Pages\ListCalls();
    
    // Simulate Filament's boot process
    $page->boot();
    $page->mount();
    
    echo "✅ Page mounted successfully\n";
    
    // Test table query
    $table = $page->getTable();
    $query = $table->getQuery();
    echo "Table query count: " . $query->count() . "\n";
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Done ===\n";