<?php

namespace App\Listeners;

use App\Events\OutboundCallInitiated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogOutboundCall implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OutboundCallInitiated $event): void
    {
        $call = $event->call;
        $metadata = $event->metadata;
        
        // Log to retell-mcp channel
        Log::channel('retell-mcp')->info('Outbound call initiated', [
            'call_id' => $call->id,
            'retell_call_id' => $call->retell_call_id,
            'company_id' => $call->company_id,
            'branch_id' => $call->branch_id,
            'customer_id' => $call->customer_id,
            'to_number' => $call->to_number,
            'from_number' => $call->from_number,
            'purpose' => $metadata['purpose'] ?? 'unknown',
            'initiated_by' => $metadata['initiated_by'] ?? 'system',
            'campaign_id' => $metadata['campaign_id'] ?? null,
        ]);
        
        // Track metrics
        if (config('retell-mcp.monitoring.metrics_enabled')) {
            app('monitoring.metrics')->increment('retell_mcp.calls.initiated', 1, [
                'company_id' => $call->company_id,
                'purpose' => $metadata['purpose'] ?? 'unknown',
            ]);
        }
        
        // Store call initiation metadata for analysis
        $call->metadata = array_merge($call->metadata ?? [], [
            'initiation_logged_at' => now()->toISOString(),
            'initiation_metadata' => $metadata,
        ]);
        $call->save();
    }
}