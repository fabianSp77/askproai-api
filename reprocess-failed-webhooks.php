<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "=== Reprocessing Failed Webhooks ===\n\n";

// Get failed webhooks from webhook_logs
$failedWebhooks = DB::table('webhook_logs')
    ->where('provider', 'retell')
    ->where('status', 'error')
    ->orderBy('created_at', 'asc')
    ->get();

echo "Found " . count($failedWebhooks) . " failed webhooks to reprocess\n\n";

$processed = 0;
$errors = 0;

foreach ($failedWebhooks as $webhookLog) {
    echo "Processing webhook ID: {$webhookLog->id} from {$webhookLog->created_at}\n";
    
    try {
        $payload = json_decode($webhookLog->payload, true);
        
        if (!$payload || !isset($payload['call'])) {
            echo "  ❌ Invalid payload format\n";
            $errors++;
            continue;
        }
        
        $callData = $payload['call'];
        $callId = $callData['call_id'] ?? null;
        
        if (!$callId) {
            echo "  ❌ No call_id found\n";
            $errors++;
            continue;
        }
        
        // Check if call already exists
        $existingCall = Call::where('retell_call_id', $callId)
            ->orWhere('call_id', $callId)
            ->first();
            
        if ($existingCall) {
            echo "  ⚠️  Call already exists (ID: {$existingCall->id})\n";
            continue;
        }
        
        // Find company/branch by phone number
        $toNumber = $callData['to_number'] ?? null;
        $company = null;
        $branch = null;
        
        if ($toNumber) {
            // Try to find branch first
            $branch = Branch::where('phone_number', $toNumber)
                ->where('is_active', true)
                ->first();
                
            if ($branch) {
                $company = $branch->company;
                echo "  ✓ Found branch: {$branch->name} (Company: {$company->name})\n";
            } else {
                // Try company phone number
                $company = Company::where('phone_number', $toNumber)->first();
                if ($company) {
                    echo "  ✓ Found company by phone: {$company->name}\n";
                }
            }
        }
        
        // Fallback to first company
        if (!$company) {
            $company = Company::first();
            echo "  ⚠️  Using default company: {$company->name}\n";
        }
        
        // Create call record
        $call = new Call();
        $call->company_id = $company->id;
        $call->branch_id = $branch ? $branch->id : null;
        $call->retell_call_id = $callId;
        $call->call_id = $callId;
        $call->agent_id = $callData['agent_id'] ?? null;
        $call->from_number = $callData['from_number'] ?? null;
        $call->to_number = $toNumber;
        $call->direction = $callData['call_type'] ?? 'inbound';
        $call->status = $callData['call_status'] ?? 'completed';
        
        // Timestamps
        if (isset($callData['start_timestamp'])) {
            $call->start_timestamp = Carbon::createFromTimestampMs($callData['start_timestamp']);
        }
        if (isset($callData['end_timestamp'])) {
            $call->end_timestamp = Carbon::createFromTimestampMs($callData['end_timestamp']);
        }
        
        // Duration and cost
        $call->duration_sec = isset($callData['duration_ms']) ? round($callData['duration_ms'] / 1000) : 0;
        $call->cost = isset($callData['cost']) ? $callData['cost'] / 100 : 0;
        
        // Transcript and analysis
        $call->transcript = $callData['transcript'] ?? null;
        $call->transcript_object = $callData['transcript_object'] ?? null;
        $call->audio_url = $callData['recording_url'] ?? null;
        $call->public_log_url = $callData['public_log_url'] ?? null;
        
        // Analysis data
        if (isset($callData['call_analysis'])) {
            $analysis = $callData['call_analysis'];
            $call->summary = $analysis['call_summary'] ?? null;
            $call->sentiment = $analysis['sentiment'] ?? null;
            $call->analysis = $analysis;
            
            // Extract custom data
            if (isset($analysis['custom_analysis_data'])) {
                $customData = $analysis['custom_analysis_data'];
                $call->extracted_name = $customData['_name'] ?? null;
                $call->extracted_email = $customData['_email'] ?? null;
                $call->extracted_date = $customData['_datum__termin'] ?? null;
                $call->extracted_time = $customData['_uhrzeit__termin'] ?? null;
            }
        }
        
        // Extract from dynamic variables (alternative location)
        $dynamicVars = $callData['retell_llm_dynamic_variables'] ?? [];
        if (!empty($dynamicVars)) {
            $call->extracted_name = $call->extracted_name ?? $dynamicVars['name'] ?? null;
            $call->extracted_date = $call->extracted_date ?? $dynamicVars['datum'] ?? null;
            $call->extracted_time = $call->extracted_time ?? $dynamicVars['uhrzeit'] ?? null;
        }
        
        $call->save();
        echo "  ✓ Call saved (ID: {$call->id})\n";
        
        // Create/find customer
        if ($call->from_number) {
            $customer = Customer::firstOrCreate(
                [
                    'phone' => $call->from_number,
                    'company_id' => $company->id
                ],
                [
                    'first_name' => $call->extracted_name ?? 'Unknown',
                    'last_name' => 'Customer',
                    'email' => $call->extracted_email,
                    'source' => 'phone_call'
                ]
            );
            
            $call->customer_id = $customer->id;
            $call->save();
            echo "  ✓ Customer linked (ID: {$customer->id})\n";
        }
        
        // Check if appointment should be created
        if (!empty($dynamicVars['booking_confirmed']) && 
            !empty($dynamicVars['datum']) && 
            !empty($dynamicVars['uhrzeit'])) {
            
            echo "  📅 Creating appointment...\n";
            
            try {
                $date = Carbon::parse($dynamicVars['datum']);
                $time = $dynamicVars['uhrzeit'];
                [$hours, $minutes] = explode(':', $time);
                $startTime = $date->copy()->setTime((int)$hours, (int)$minutes);
                $endTime = $startTime->copy()->addMinutes(30);
                
                $appointment = Appointment::create([
                    'customer_id' => $call->customer_id,
                    'branch_id' => $call->branch_id,
                    'company_id' => $call->company_id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => 'scheduled',
                    'notes' => "Gebucht über Telefon-KI\n" . ($dynamicVars['kundenwunsch'] ?? ''),
                    'source' => 'phone_ai',
                    'call_id' => $call->id
                ]);
                
                echo "  ✓ Appointment created (ID: {$appointment->id})\n";
            } catch (\Exception $e) {
                echo "  ❌ Failed to create appointment: " . $e->getMessage() . "\n";
            }
        }
        
        // Mark webhook as processed
        DB::table('webhook_logs')
            ->where('id', $webhookLog->id)
            ->update([
                'status' => 'reprocessed',
                'updated_at' => now()
            ]);
            
        $processed++;
        echo "  ✅ Webhook reprocessed successfully!\n\n";
        
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
        echo "  Trace: " . $e->getTraceAsString() . "\n\n";
        $errors++;
    }
}

echo "\n=== Summary ===\n";
echo "Total webhooks: " . count($failedWebhooks) . "\n";
echo "Successfully processed: $processed\n";
echo "Errors: $errors\n";

// Check current call count
$callCount = Call::count();
echo "\nTotal calls in database: $callCount\n";