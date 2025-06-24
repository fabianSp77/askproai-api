<?php

use App\Models\WebhookEvent;
use App\Models\Call;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\Customer;
use App\Jobs\ProcessRetellCallEndedJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=================================\n";
echo "Test Webhook Processing\n";
echo "=================================\n\n";

// Get a single pending call_ended webhook
$webhook = WebhookEvent::where('status', 'pending')
    ->where('provider', 'retell')
    ->where('event_type', 'call_ended')
    ->first();

if (!$webhook) {
    echo "No pending call_ended webhooks found.\n";
    exit(0);
}

echo "Processing webhook ID: {$webhook->id}\n";
echo "Event type: {$webhook->event_type}\n";

$payload = $webhook->payload;
$callData = $payload['call'] ?? $payload;

echo "From: " . ($callData['from_number'] ?? 'unknown') . "\n";
echo "To: " . ($callData['to_number'] ?? 'unknown') . "\n";

// Resolve company
$toNumber = $callData['to_number'] ?? null;
$companyId = null;

if ($toNumber) {
    // Try phone number lookup
    $phoneNumber = PhoneNumber::withoutGlobalScopes()
        ->where('number', $toNumber)
        ->where('is_active', true)
        ->first();
        
    if ($phoneNumber && $phoneNumber->branch_id) {
        $branch = Branch::withoutGlobalScopes()->find($phoneNumber->branch_id);
        if ($branch) {
            $companyId = $branch->company_id;
            echo "Found company via phone number: Company ID {$companyId}\n";
        }
    }
    
    if (!$companyId) {
        // Try direct branch lookup
        $branch = Branch::withoutGlobalScopes()
            ->where('phone_number', $toNumber)
            ->where('is_active', true)
            ->first();
            
        if ($branch) {
            $companyId = $branch->company_id;
            echo "Found company via branch: Company ID {$companyId}\n";
        }
    }
}

if (!$companyId) {
    echo "ERROR: Could not resolve company ID!\n";
    exit(1);
}

// Set company context
app()->instance('current_company_id', $companyId);

echo "\nProcessing call_ended webhook...\n";

try {
    DB::transaction(function () use ($webhook, $payload, $callData, $companyId) {
        // Create or update call
        $callId = $callData['call_id'] ?? null;
        
        if (!$callId) {
            throw new Exception('No call_id in payload');
        }
        
        $call = Call::updateOrCreate(
            ['retell_call_id' => $callId],
            [
                'company_id' => $companyId,
                'from_number' => $callData['from_number'] ?? null,
                'to_number' => $callData['to_number'] ?? null,
                'direction' => $callData['direction'] ?? 'inbound',
                'status' => 'completed',
                'started_at' => isset($callData['start_timestamp']) 
                    ? \Carbon\Carbon::createFromTimestampMs($callData['start_timestamp']) 
                    : now(),
                'ended_at' => isset($callData['end_timestamp']) 
                    ? \Carbon\Carbon::createFromTimestampMs($callData['end_timestamp']) 
                    : now(),
                'duration_seconds' => $callData['duration_ms'] ?? 0 / 1000,
                'transcript' => $callData['transcript'] ?? null,
                'recording_url' => $callData['recording_url'] ?? null,
                'public_log_url' => $callData['public_log_url'] ?? null,
                'agent_id' => $callData['agent_id'] ?? null,
                'metadata' => $callData,
            ]
        );
        
        echo "Created/Updated call ID: {$call->id}\n";
        
        // Try to find/create customer
        if ($call->from_number) {
            $customer = Customer::withoutGlobalScopes()->firstOrCreate(
                [
                    'phone' => $call->from_number,
                    'company_id' => $companyId
                ],
                [
                    'first_name' => 'Phone',
                    'last_name' => 'Customer',
                    'source' => 'phone_call'
                ]
            );
            
            $call->customer_id = $customer->id;
            $call->save();
            
            echo "Created/Found customer ID: {$customer->id}\n";
        }
        
        // Check for appointment data
        $dynamicVars = $callData['retell_llm_dynamic_variables'] ?? [];
        if (!empty($dynamicVars)) {
            echo "\nDynamic variables found:\n";
            foreach ($dynamicVars as $key => $value) {
                echo "  $key: $value\n";
            }
        }
        
        // Mark webhook as completed
        $webhook->update(['status' => 'completed']);
        
        echo "\n✅ Webhook processed successfully!\n";
    });
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Mark webhook as failed
    $webhook->update([
        'status' => 'failed',
        'error' => $e->getMessage()
    ]);
}