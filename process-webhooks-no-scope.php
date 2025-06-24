<?php

use App\Models\WebhookEvent;
use App\Models\PhoneNumber;
use App\Models\Branch;
use App\Jobs\ProcessRetellCallEndedJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=================================\n";
echo "Direct Webhook Processing (No Scope)\n";
echo "=================================\n\n";

// Get pending webhooks
$pendingWebhooks = WebhookEvent::where('status', 'pending')
    ->whereIn('event_type', ['call_ended', 'call_analyzed'])
    ->orderBy('created_at', 'asc')
    ->get();

echo "Found " . count($pendingWebhooks) . " pending call webhooks\n\n";

if (count($pendingWebhooks) === 0) {
    echo "No pending webhooks to process.\n";
    exit(0);
}

$processed = 0;
$failed = 0;

foreach ($pendingWebhooks as $webhook) {
    echo "Processing webhook ID: {$webhook->id} (Event: {$webhook->event_type})\n";
    
    try {
        $payload = $webhook->payload;
        
        // Extract phone numbers
        $fromNumber = $payload['call']['from_number'] ?? null;
        $toNumber = $payload['call']['to_number'] ?? null;
        
        echo "  From: {$fromNumber}\n";
        echo "  To: {$toNumber}\n";
        
        // Find company by phone number WITHOUT scope
        $phoneNumberRecord = PhoneNumber::withoutGlobalScopes()
            ->where('number', $toNumber)
            ->where('is_active', true)
            ->first();
            
        $branch = null;
        $companyId = null;
            
        if ($phoneNumberRecord && $phoneNumberRecord->branch_id) {
            $branch = Branch::withoutGlobalScopes()->find($phoneNumberRecord->branch_id);
            if ($branch) {
                $companyId = $branch->company_id;
                echo "  Found via phone number: Branch {$branch->name} (Company ID: {$companyId})\n";
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
                echo "  Found branch directly: {$branch->name} (Company ID: {$companyId})\n";
            }
        }
        
        if (!$companyId) {
            throw new Exception("No active branch found for phone number: {$toNumber}");
        }
        
        // Set company context
        app()->instance('current_company_id', $companyId);
        config(['askproai.current_company_id' => $companyId]);
        
        // Process based on event type
        if ($webhook->event_type === 'call_ended') {
            echo "  Processing call_ended webhook...\n";
            
            // Update the ProcessRetellCallEndedJob to use company context
            DB::transaction(function () use ($webhook, $payload, $companyId) {
                // Create the job with company context
                $job = new ProcessRetellCallEndedJob(
                    $payload,
                    $webhook->id,
                    $webhook->correlation_id ?? \Str::uuid()->toString()
                );
                
                // Set company ID if the trait is available
                if (method_exists($job, 'setCompanyId')) {
                    $job->setCompanyId($companyId);
                }
                
                // Process the job synchronously
                $job->handle();
                
                // Mark as completed
                $webhook->update(['status' => 'completed']);
            });
            
            $processed++;
            echo "  ✅ Processed successfully\n";
            
        } elseif ($webhook->event_type === 'call_analyzed') {
            // For now, just mark as completed
            $webhook->update(['status' => 'completed']);
            $processed++;
            echo "  ✅ Marked as completed (call_analyzed)\n";
        }
        
    } catch (\Exception $e) {
        $failed++;
        echo "  ❌ Failed: " . $e->getMessage() . "\n";
        echo "  Stack trace: " . $e->getTraceAsString() . "\n";
        
        // Mark as failed
        $webhook->update([
            'status' => 'failed',
            'error' => $e->getMessage()
        ]);
        
        Log::error('Failed to process webhook', [
            'webhook_id' => $webhook->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    echo "\n";
}

echo "=================================\n";
echo "Summary\n";
echo "=================================\n";
echo "✅ Processed: {$processed}\n";
echo "❌ Failed: {$failed}\n";
echo "\nDone.\n";