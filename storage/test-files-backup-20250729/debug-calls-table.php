<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\User;
use App\Models\Call;
use Illuminate\Support\Facades\Auth;

$adminUser = User::where('email', 'fabian@askproai.de')->orWhere('email', 'admin@askproai.de')->first();
if (!$adminUser) {
    die("No admin user found!");
}

Auth::login($adminUser);

// Force company context
app()->instance('current_company_id', $adminUser->company_id);
app()->instance('company_context_source', 'web_auth');

echo "=== Debug Calls Table Loading ===\n\n";
echo "User: {$adminUser->email} (Company: {$adminUser->company_id})\n\n";

// Test 1: Basic query
echo "=== Test 1: Basic Query ===\n";
try {
    $calls = Call::with(['customer'])->limit(5)->get();
    echo "✅ Loaded " . count($calls) . " calls with eager loading\n";
    foreach ($calls as $call) {
        echo "- Call {$call->id}: Customer = " . ($call->customer ? $call->customer->name : 'NULL') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

// Test 2: CallResource query
echo "\n=== Test 2: CallResource Query ===\n";
try {
    $query = \App\Filament\Admin\Resources\CallResource::getEloquentQuery();
    echo "SQL: " . $query->toSql() . "\n";
    echo "Bindings: " . json_encode($query->getBindings()) . "\n";
    $calls = $query->limit(5)->get();
    echo "✅ Loaded " . count($calls) . " calls from CallResource\n";
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

// Test 3: Table configuration
echo "\n=== Test 3: Table Configuration ===\n";
try {
    $resource = new \App\Filament\Admin\Resources\CallResource();
    $table = new \Filament\Tables\Table();
    $table = $resource::table($table);
    
    // Get columns
    $columns = $table->getColumns();
    echo "Found " . count($columns) . " columns\n";
    
    // Check for problematic columns
    foreach ($columns as $column) {
        $name = $column->getName();
        echo "- Column: $name\n";
        
        // Check if column uses relationships
        if (str_contains($name, '.')) {
            echo "  ⚠️ Uses relationship: $name\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Test 4: Check Livewire component
echo "\n=== Test 4: Livewire Component ===\n";
try {
    $component = new \App\Filament\Admin\Resources\CallResource\Pages\ListCalls();
    echo "✅ ListCalls component created\n";
    
    // Try to mount it
    $component->mount();
    echo "✅ Component mounted successfully\n";
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Done ===\n";