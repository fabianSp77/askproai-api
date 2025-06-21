<?php

namespace App\Services\MCP;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class WebhookMCPServerFixed extends WebhookMCPServer
{
    /**
     * Save call record using direct DB insert to bypass model events
     */
    protected function saveCallRecord(array $callData, array $phoneResolution, Customer $customer): Call
    {
        $dynamicVars = $callData['retell_llm_dynamic_variables'] ?? [];
        $customData = $callData['call_analysis']['custom_analysis_data'] ?? [];
        
        // Build call data array
        $insertData = [
            'company_id' => $phoneResolution['company_id'],
            'branch_id' => $phoneResolution['branch_id'],
            'customer_id' => $customer->id,
            'retell_call_id' => $callData['call_id'],
            'call_id' => $callData['call_id'],
            'agent_id' => $callData['agent_id'] ?? null,
            'from_number' => $callData['from_number'] ?? null,
            'to_number' => $callData['to_number'] ?? $callData['to'] ?? null,
            'direction' => $callData['call_type'] ?? 'inbound',
            'call_status' => $callData['call_status'] ?? 'completed',
            'status' => 'completed',
            'transcript' => $callData['transcript'] ?? null,
            'summary' => $callData['call_analysis']['call_summary'] ?? null,
            'retell_dynamic_variables' => json_encode($dynamicVars),
            'webhook_data' => json_encode($callData),
            'extracted_name' => $dynamicVars['name'] ?? $customData['_name'] ?? null,
            'extracted_date' => $dynamicVars['datum'] ?? $customData['_datum__termin'] ?? null,
            'extracted_time' => $dynamicVars['uhrzeit'] ?? $customData['_uhrzeit__termin'] ?? null,
            'extracted_email' => $customData['_email'] ?? null,
            'audio_url' => $callData['recording_url'] ?? null,
            'public_log_url' => $callData['public_log_url'] ?? null,
            'created_at' => now(),
            'updated_at' => now()
        ];
        
        // Handle timestamps
        if (isset($callData['start_timestamp'])) {
            $insertData['start_timestamp'] = Carbon::createFromTimestampMs($callData['start_timestamp']);
        }
        if (isset($callData['end_timestamp'])) {
            $insertData['end_timestamp'] = Carbon::createFromTimestampMs($callData['end_timestamp']);
        }
        
        // Duration and cost
        $insertData['duration_sec'] = isset($callData['duration_ms']) ? round($callData['duration_ms'] / 1000) : 0;
        $insertData['duration_minutes'] = $insertData['duration_sec'] > 0 ? round($insertData['duration_sec'] / 60, 2) : 0;
        $insertData['cost'] = isset($callData['cost']) ? $callData['cost'] / 100 : 0;
        
        // Insert directly to bypass model events
        $callId = DB::table('calls')->insertGetId($insertData);
        
        // Return the call model without global scopes
        return Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);
    }
}