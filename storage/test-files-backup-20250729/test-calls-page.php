<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

// Login as user
$user = \App\Models\User::where('email', 'fabian@askproai.de')->first();
if (!$user) {
    die("User not found\n");
}

auth()->login($user);

// Set company context
app()->instance('current_company_id', $user->company_id);
app()->instance('company_context_source', 'web_auth');

echo "=== Testing Calls Page ===\n\n";
echo "User: " . $user->email . "\n";
echo "Company ID: " . $user->company_id . "\n\n";

// Test the query that CallResource uses
echo "=== Testing Call Query ===\n";

// Without scope removal (current)
$query1 = \App\Models\Call::query()
    ->with(['customer:id,first_name,last_name', 'appointment:id,status', 'branch:id,name', 'company:id,name'])
    ->select('calls.*');

echo "Query WITH CompanyScope:\n";
echo "SQL: " . $query1->toSql() . "\n";
echo "Bindings: " . json_encode($query1->getBindings()) . "\n";
echo "Count: " . $query1->count() . "\n\n";

// With scope removal (old way)
$query2 = \App\Models\Call::query()
    ->withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
    ->with(['customer:id,first_name,last_name', 'appointment:id,status', 'branch:id,name', 'company:id,name'])
    ->select('calls.*');

echo "Query WITHOUT CompanyScope:\n";
echo "SQL: " . $query2->toSql() . "\n";
echo "Count: " . $query2->count() . "\n\n";

// Test pagination
echo "=== Testing Pagination ===\n";
$paginated = $query1->paginate(25);
echo "Total records: " . $paginated->total() . "\n";
echo "Per page: " . $paginated->perPage() . "\n";
echo "Current page: " . $paginated->currentPage() . "\n";
echo "Last page: " . $paginated->lastPage() . "\n\n";

// Test performance
echo "=== Performance Test ===\n";
$start = microtime(true);
$data = $query1->limit(25)->get();
$time = (microtime(true) - $start) * 1000;
echo "Time to load 25 records: " . round($time, 2) . "ms\n";
echo "Memory used: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB\n\n";

// Check for issues
echo "=== Potential Issues ===\n";
if ($query2->count() > 1000) {
    echo "⚠️ WARNING: Loading " . $query2->count() . " records without CompanyScope could cause performance issues!\n";
}

if ($query1->count() == 0) {
    echo "⚠️ WARNING: No calls found for company " . $user->company_id . "\n";
} else {
    echo "✅ Found " . $query1->count() . " calls for company " . $user->company_id . "\n";
}

echo "\n=== Test Complete ===\n";