<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\Call;
use App\Models\Company;
use Carbon\Carbon;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Set company context
$companyId = 85;
app()->instance('current_company_id', $companyId);

echo "Creating test calls for company ID: $companyId\n";

// Create some test calls for the period
$calls = [
    ['duration' => 5.5, 'date' => '2025-06-16'],
    ['duration' => 3.2, 'date' => '2025-06-17'],
    ['duration' => 12.8, 'date' => '2025-06-18'],
    ['duration' => 7.1, 'date' => '2025-06-19'],
    ['duration' => 2.4, 'date' => '2025-06-20'],
    ['duration' => 15.3, 'date' => '2025-06-21'],
    ['duration' => 4.7, 'date' => '2025-06-22'],
];

foreach ($calls as $callData) {
    $call = Call::create([
        'company_id' => $companyId,
        'tenant_id' => 1,
        'call_id' => 'test-' . uniqid(),
        'retell_call_id' => 'retell-' . uniqid(),
        'phone_number' => '+49 30 ' . rand(1000000, 9999999),
        'from_number' => '+49 30 ' . rand(1000000, 9999999),
        'to_number' => '+49 30 837 93 369',
        'duration_sec' => $callData['duration'] * 60,
        'duration_minutes' => $callData['duration'],
        'call_successful' => true,
        'direction' => 'inbound',
        'created_at' => Carbon::parse($callData['date'] . ' ' . rand(8, 20) . ':' . rand(0, 59)),
        'start_timestamp' => Carbon::parse($callData['date'] . ' ' . rand(8, 20) . ':' . rand(0, 59)),
        'summary' => 'Test call for invoice demo',
        'transcript' => 'This is a test call transcript.',
    ]);
    
    echo "Created call: {$call->id} - {$callData['duration']} minutes on {$callData['date']}\n";
}

$totalMinutes = array_sum(array_column($calls, 'duration'));
echo "\nTotal test calls created: " . count($calls) . "\n";
echo "Total minutes: " . $totalMinutes . "\n";