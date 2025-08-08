<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Authenticate
$user = \App\Models\User::where('email', 'fabian@askproai.de')->first();
if ($user) {
    auth()->login($user);
}

echo "<h1>Filament Table Query Test</h1>";
echo "<pre>";

// Test 1: Direct query
echo "Test 1: Direct Database Query\n";
echo "==============================\n";
$calls = \App\Models\Call::where('company_id', $user->company_id ?? 1)
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

echo "Found " . count($calls) . " calls\n";
foreach ($calls as $call) {
    echo "  - ID: {$call->id}, Date: {$call->created_at}, Status: {$call->status}\n";
}

// Test 2: Check if Filament applies any global scopes
echo "\n\nTest 2: Model Scopes\n";
echo "====================\n";
$model = \App\Models\Call::class;
$scopes = (new $model)->getGlobalScopes();
echo "Global scopes: " . count($scopes) . "\n";
foreach ($scopes as $name => $scope) {
    echo "  - $name: " . get_class($scope) . "\n";
}

// Test 3: Check Filament table query
echo "\n\nTest 3: Filament Table Query\n";
echo "============================\n";
try {
    // Simulate what Filament does
    $query = \App\Models\Call::query();
    
    // Check if company filter is applied
    $sql = $query->toSql();
    $bindings = $query->getBindings();
    
    echo "Base SQL: $sql\n";
    echo "Bindings: " . json_encode($bindings) . "\n";
    
    // Apply company filter manually
    $query->where('company_id', $user->company_id ?? 1);
    echo "\nWith company filter SQL: " . $query->toSql() . "\n";
    echo "Company ID: " . ($user->company_id ?? 1) . "\n";
    
    $count = $query->count();
    echo "Total records: $count\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test 4: Check if it's a rendering issue
echo "\n\nTest 4: Table Column Values\n";
echo "===========================\n";
$sampleCall = \App\Models\Call::where('company_id', $user->company_id ?? 1)->first();
if ($sampleCall) {
    echo "Sample call data:\n";
    echo "  id: " . $sampleCall->id . "\n";
    echo "  created_at: " . $sampleCall->created_at . "\n";
    echo "  duration_sec: " . $sampleCall->duration_sec . "\n";
    echo "  status: " . $sampleCall->status . "\n";
    echo "  from_phone: " . ($sampleCall->from_phone ?? 'NULL') . "\n";
    echo "  to_phone: " . ($sampleCall->to_phone ?? 'NULL') . "\n";
    echo "  customer: " . ($sampleCall->customer ? $sampleCall->customer->name : 'NULL') . "\n";
}

echo "</pre>";

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If data shows here but not in Filament, it's a rendering issue</li>";
echo "<li>Try the simplified table at <a href='/admin/calls'>/admin/calls</a></li>";
echo "<li>Or use the working alternative at <a href='/calls-table.php'>/calls-table.php</a></li>";
echo "</ol>";