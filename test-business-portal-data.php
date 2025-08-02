<?php

// Simple test script to debug business portal data loading

require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Manually authenticate as a portal user for testing
$user = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();
if (!$user) {
    echo "No portal user found with email demo@askproai.de\n";
    exit(1);
}

echo "Testing with user: {$user->email} (Company ID: {$user->company_id})\n";

// Set up the application context
app()->instance('current_company_id', $user->company_id);
app()->instance('company_context_source', 'portal_auth');

// Test 1: Check if we can get calls
echo "\n=== Testing Call Query ===\n";
$calls = \App\Models\Call::where('company_id', $user->company_id)
    ->limit(5)
    ->get();
echo "Calls found: " . $calls->count() . "\n";

// Test 2: Check appointments
echo "\n=== Testing Appointment Query ===\n";
$appointments = \App\Models\Appointment::where('company_id', $user->company_id)
    ->limit(5)
    ->get();
echo "Appointments found: " . $appointments->count() . "\n";

// Test 3: Check customers
echo "\n=== Testing Customer Query ===\n";
$customers = \App\Models\Customer::where('company_id', $user->company_id)
    ->limit(5)
    ->get();
echo "Customers found: " . $customers->count() . "\n";

// Test 4: Check if CompanyScope is interfering
echo "\n=== Testing with CompanyScope disabled ===\n";
$callsWithoutScope = \App\Models\Call::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)
    ->where('company_id', $user->company_id)
    ->limit(5)
    ->get();
echo "Calls found without CompanyScope: " . $callsWithoutScope->count() . "\n";

// Test 5: Check PrepaidBalance
echo "\n=== Testing PrepaidBalance ===\n";
$balance = \App\Models\PrepaidBalance::where('company_id', $user->company_id)->first();
if ($balance) {
    echo "Balance found: €" . number_format($balance->balance + $balance->bonus_balance, 2) . "\n";
} else {
    echo "No balance found\n";
}

// Test 6: Direct database query
echo "\n=== Direct Database Query ===\n";
$db = \DB::connection();
$directCalls = $db->select("SELECT COUNT(*) as count FROM calls WHERE company_id = ?", [$user->company_id]);
echo "Direct query - Calls in database: " . $directCalls[0]->count . "\n";

// Test 7: Check the dashboard API logic
echo "\n=== Testing Dashboard API Logic ===\n";
$today = now()->startOfDay();
$endDate = $today->copy()->endOfDay();

$todaysCalls = \App\Models\Call::where('company_id', $user->company_id)
    ->whereBetween('created_at', [$today, $endDate])
    ->count();
echo "Today's calls: " . $todaysCalls . "\n";

$todaysAppointments = \App\Models\Appointment::where('company_id', $user->company_id)
    ->whereDate('starts_at', $today->toDateString())
    ->count();
echo "Today's appointments: " . $todaysAppointments . "\n";

// Test 8: Check recent activity query
echo "\n=== Testing Recent Activity ===\n";
$recentCalls = \App\Models\Call::where('company_id', $user->company_id)
    ->with('customer')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();
echo "Recent calls for activity: " . $recentCalls->count() . "\n";

foreach ($recentCalls as $call) {
    echo "  - Call ID: {$call->id}, From: {$call->from_number}, Created: {$call->created_at}\n";
}