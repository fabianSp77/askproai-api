<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use Carbon\Carbon;

// Create an active test call (no end_timestamp)
$company = Company::first();

$call = Call::create([
    'retell_call_id' => 'test_active_' . time(),
    'company_id' => $company->id,
    'from_number' => '+491234567890',
    'to_number' => '+499876543210',
    'direction' => 'inbound',
    'status' => 'in_progress',
    'call_status' => 'active',
    'start_timestamp' => Carbon::now(),
    'end_timestamp' => null, // This is the key - no end timestamp means active
    'duration_sec' => null,
    'agent_id' => 'agent_test',
]);

echo "Created active test call with ID: {$call->id}\n";
echo "Retell Call ID: {$call->retell_call_id}\n";
echo "Status: {$call->status}\n";
echo "Start: {$call->start_timestamp}\n";
echo "End: " . ($call->end_timestamp ?? 'NULL (Active)') . "\n";
