<?php

namespace App\Services;

use App\Models\Call;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\CurrencyConverter;

class CallDataRefresher
{
    public function refresh(Call $call): bool
    {
        // Versuche retell_call_id zuerst, dann call_id
        $callId = $call->retell_call_id ?? $call->call_id;
        
        if (!$callId) {
            Log::warning('Refresh aborted – no call ID found', [
                'db_id' => $call->id,
                'call_id' => $call->call_id,
                'retell_call_id' => $call->retell_call_id
            ]);
            return false;
        }

        $base  = rtrim(config('services.retell.base') ?: config('services.retell.base_url'), '/');   // z. B. https://api.retellai.com
        $token = config('services.retell.api_key') ?: config('services.retell.token');              // Bearer-Key

        Log::info('Attempting to refresh call data', [
            'call_id' => $callId,
            'base_url' => $base,
            'has_token' => !empty($token)
        ]);

        // --- Retell v2-Endpoint ------------------------------------------------
        $url = "{$base}/v2/get-call/{$callId}";

        try {
            $res = Http::withToken($token)
                ->timeout(30)
                ->get($url);

            Log::info('Retell API response', [
                'url' => $url,
                'status' => $res->status(),
                'successful' => $res->successful(),
                'headers' => $res->headers()
            ]);
            
            if ($res->status() !== 200) {
                Log::error('Retell API error response', [
                    'status' => $res->status(),
                    'body' => $res->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Retell API request failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return false;
        }

        if ($res->failed()) {
            Log::warning('Retell API error', [
                'db_id'  => $call->id,
                'status' => $res->status(),
            ]);
            return false;
        }

        $data = $res->json();
        if (!$data || !is_array($data)) {
            Log::warning('Retell response invalid or empty', ['db_id' => $call->id]);
            return false;
        }

        // --- speichern ---------------------------------------------------------
        $updateData = [];
        
        // Analysis data - store entire analysis as JSON
        if (isset($data['call_analysis']) && is_array($data['call_analysis'])) {
            // Add missing sentiment if found
            if (isset($data['call_analysis']['sentiment']) && !isset($data['call_analysis']['analysis']['sentiment'])) {
                $data['call_analysis']['sentiment'] = $data['call_analysis']['sentiment'];
            }
            
            // Add important phrases if found
            if (isset($data['call_analysis']['important_phrases'])) {
                $data['call_analysis']['important_phrases'] = $data['call_analysis']['important_phrases'];
            }
            
            $updateData['analysis'] = $data['call_analysis'];
        }
        
        // Transcript
        if (isset($data['transcript'])) {
            $updateData['transcript'] = $data['transcript'];
        }
        
        // Summary (von Retell generiert)
        if (isset($data['call_analysis']['call_summary'])) {
            $updateData['summary'] = $data['call_analysis']['call_summary'];
        } elseif (isset($data['summary'])) {
            $updateData['summary'] = $data['summary'];
        }
        
        // Additional fields from call_analysis
        if (isset($data['call_analysis']['sentiment'])) {
            $updateData['sentiment'] = $data['call_analysis']['sentiment'];
        }
        
        if (isset($data['call_analysis']['intent'])) {
            $updateData['intent'] = $data['call_analysis']['intent'];
        }
        
        // Call successful
        if (isset($data['call_analysis']['call_successful'])) {
            $updateData['call_successful'] = $data['call_analysis']['call_successful'];
        }
        
        if (isset($data['call_analysis']['extracted_info'])) {
            $extracted = $data['call_analysis']['extracted_info'];
            $updateData['extracted_name'] = $extracted['name'] ?? null;
            $updateData['extracted_email'] = $extracted['email'] ?? null;
            $updateData['extracted_date'] = $extracted['date'] ?? null;
            $updateData['extracted_time'] = $extracted['time'] ?? null;
            $updateData['extracted_service'] = $extracted['service'] ?? null;
        }
        
        if (isset($data['call_analysis']['appointment_requested'])) {
            $updateData['appointment_requested'] = $data['call_analysis']['appointment_requested'];
        }
        
        // Recording URL (nur audio_url existiert in der DB)
        if (isset($data['recording_url'])) {
            $updateData['audio_url'] = $data['recording_url'];
        }
        
        // Transcript object (mit Tool calls)
        if (isset($data['transcript_object'])) {
            $updateData['transcript_object'] = $data['transcript_object'];
        }
        
        // Cost und duration
        if (isset($data['call_cost'])) {
            // Convert cents to euros (Retell sends costs in cents)
            $updateData['cost'] = CurrencyConverter::convertRetellCostToEuros($data['call_cost']);
            
            // Store full cost breakdown with euro conversions
            if (is_array($data['call_cost'])) {
                $updateData['cost_breakdown'] = CurrencyConverter::formatCostBreakdown($data['call_cost']);
            }
        }
        
        if (isset($data['duration_ms'])) {
            $updateData['duration_sec'] = round($data['duration_ms'] / 1000);
        }
        
        // Timestamps (convert from milliseconds to datetime)
        if (isset($data['start_timestamp'])) {
            $updateData['start_timestamp'] = \Carbon\Carbon::createFromTimestampMs($data['start_timestamp']);
        }
        
        if (isset($data['end_timestamp'])) {
            $updateData['end_timestamp'] = \Carbon\Carbon::createFromTimestampMs($data['end_timestamp']);
        }
        
        // Public log URL
        if (isset($data['public_log_url'])) {
            $updateData['public_log_url'] = $data['public_log_url'];
        }
        
        // Telephony identifier
        if (isset($data['telephony_identifier']) && !empty($data['telephony_identifier'])) {
            if (!isset($updateData['analysis'])) {
                $updateData['analysis'] = $call->analysis ?? [];
            }
            $updateData['analysis']['telephony_identifier'] = $data['telephony_identifier'];
        }
        
        // LLM token usage
        if (isset($data['llm_token_usage']) && !empty($data['llm_token_usage'])) {
            if (!isset($updateData['analysis'])) {
                $updateData['analysis'] = $call->analysis ?? [];
            }
            $updateData['analysis']['llm_token_usage'] = $data['llm_token_usage'];
        }
        
        // Latency metrics
        if (isset($data['latency']) && !empty($data['latency'])) {
            if (!isset($updateData['analysis'])) {
                $updateData['analysis'] = $call->analysis ?? [];
            }
            $updateData['analysis']['latency'] = $data['latency'];
        }
        
        Log::info('Updating call with data', [
            'db_id' => $call->id,
            'fields_to_update' => array_keys($updateData),
            'has_summary' => isset($updateData['summary']),
            'has_analysis' => isset($updateData['analysis'])
        ]);
        
        if (!empty($updateData)) {
            $call->update($updateData);
            return true;
        }
        
        Log::warning('No data to update', ['db_id' => $call->id]);
        return false;
    }
}
