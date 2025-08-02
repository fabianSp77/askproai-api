<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use App\Models\User;
use App\Models\Call;
use Illuminate\Support\Facades\Auth;

$adminUser = User::where('email', 'admin@askproai.de')->first();
if (!$adminUser) {
    die("No admin user found!");
}

Auth::login($adminUser);

// Force company context
app()->instance('current_company_id', $adminUser->company_id);
app()->instance('company_context_source', 'web_auth');

echo "=== Testing Calls Page Components ===\n\n";

// Test 1: CallResource getEloquentQuery
echo "1. Testing CallResource Query:\n";
try {
    $query = \App\Filament\Admin\Resources\CallResource::getEloquentQuery();
    $count = $query->count();
    echo "✅ Query successful: $count calls found\n";
    
    // Check eager loading
    $eagerLoads = $query->getEagerLoads();
    echo "Eager loads: " . implode(', ', array_keys($eagerLoads)) . "\n";
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

// Test 2: Table columns
echo "\n2. Testing Table Columns:\n";
try {
    $call = Call::with(['customer', 'appointment', 'branch', 'callCharge'])->first();
    if ($call) {
        echo "Testing with call ID: {$call->id}\n";
        
        // Test sentiment formatting
        $sentimentFormat = function ($state) {
            return match ($state) {
                'positive' => 'Positiv',
                'negative' => 'Negativ',
                'neutral' => 'Neutral',
                default => '—'
            };
        };
        echo "- Sentiment: " . $sentimentFormat($call->sentiment) . "\n";
        
        // Test appointment status
        if ($call->appointment_made) {
            echo "- Appointment: Gebucht\n";
        } elseif ($call->appointment_requested) {
            echo "- Appointment: Angefragt\n";
        } else {
            echo "- Appointment: Kein Termin\n";
        }
        
        // Test relationships
        echo "- Customer: " . ($call->customer ? $call->customer->name : 'NULL') . "\n";
        echo "- Branch: " . ($call->branch ? $call->branch->name : 'NULL') . "\n";
        echo "- CallCharge: " . ($call->callCharge ? 'Present' : 'NULL') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

// Test 3: ListCalls mount
echo "\n3. Testing ListCalls Page:\n";
try {
    $page = new \App\Filament\Admin\Resources\CallResource\Pages\ListCalls();
    $page->mount();
    echo "✅ Page mounted successfully\n";
    
    // Get view data
    $viewData = $page->getViewData();
    echo "View data keys: " . implode(', ', array_keys($viewData)) . "\n";
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";