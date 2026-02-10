#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "=== Fetching Call Details from Retell API ===\n";

$retellApiKey = env('RETELL_TOKEN') ?: die("ERROR: RETELL_TOKEN not set in .env\n");
$retellBaseUrl = env('RETELL_BASE', 'https://api.retellai.com');

// Get all calls from today that are missing data
$incompleteCalls = Call::where('created_at', '>=', now()->subDays(2))
    ->where(function($query) {
        $query->whereNull('duration_sec')
            ->orWhereNull('sentiment')
            ->orWhereNull('transcript')
            ->orWhereNull('end_timestamp');
    })
    ->whereNotNull('retell_call_id')
    ->where('retell_call_id', 'NOT LIKE', 'temp_%')
    ->where('retell_call_id', 'NOT LIKE', 'call_missing%')
    ->get();

echo "Found " . $incompleteCalls->count() . " calls needing updates\n\n";

$updated = 0;
$failed = 0;

foreach ($incompleteCalls as $call) {
    echo "Processing call: {$call->retell_call_id}... ";

    try {
        // Fetch call details from Retell API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $retellApiKey,
            'Content-Type' => 'application/json',
        ])->get("{$retellBaseUrl}/v2/get-call/{$call->retell_call_id}");

        if ($response->successful()) {
            $callData = $response->json();

            // Extract all available data
            $updates = [];

            // Duration
            if (isset($callData['call_length'])) {
                $updates['duration_sec'] = $callData['call_length'];
                $updates['duration_ms'] = $callData['call_length'] * 1000;
            } elseif (isset($callData['duration_ms'])) {
                $updates['duration_ms'] = $callData['duration_ms'];
                $updates['duration_sec'] = round($callData['duration_ms'] / 1000);
            }

            // Status
            if (isset($callData['call_status']) && $callData['call_status'] === 'ended') {
                $updates['status'] = 'completed';
            }

            // Timestamps
            if (isset($callData['start_timestamp'])) {
                $updates['start_timestamp'] = \Carbon\Carbon::createFromTimestampMs($callData['start_timestamp']);
            }
            if (isset($callData['end_timestamp'])) {
                $updates['end_timestamp'] = \Carbon\Carbon::createFromTimestampMs($callData['end_timestamp']);
            }

            // Analysis data
            if (isset($callData['call_analysis'])) {
                $analysis = $callData['call_analysis'];

                if (isset($analysis['sentiment'])) {
                    $updates['sentiment'] = strtolower($analysis['sentiment']);
                }

                if (isset($analysis['summary'])) {
                    $updates['summary'] = $analysis['summary'];
                }

                if (isset($analysis['user_sentiment'])) {
                    $updates['sentiment'] = strtolower($analysis['user_sentiment']);
                }
            }

            // Transcript
            if (isset($callData['transcript'])) {
                $updates['transcript'] = $callData['transcript'];
            } elseif (isset($callData['transcript_object'])) {
                $transcript = '';
                foreach ($callData['transcript_object'] as $turn) {
                    $speaker = $turn['role'] === 'agent' ? 'Agent' : 'User';
                    $transcript .= "{$speaker}: {$turn['content']}\n";
                }
                $updates['transcript'] = $transcript;
            }

            // Recording URL
            if (isset($callData['recording_url'])) {
                $updates['recording_url'] = $callData['recording_url'];
            }

            // Disconnection reason
            if (isset($callData['disconnection_reason'])) {
                $updates['disconnection_reason'] = $callData['disconnection_reason'];
            }

            // Agent info
            if (isset($callData['agent_id'])) {
                $updates['retell_agent_id'] = $callData['agent_id'];
            }

            // Call successful flag
            if (isset($callData['call_successful'])) {
                $updates['call_successful'] = $callData['call_successful'];
            }

            // Phone numbers (in case they're missing)
            if (!$call->from_number && isset($callData['from_number'])) {
                $updates['from_number'] = $callData['from_number'];
            }
            if (!$call->to_number && isset($callData['to_number'])) {
                $updates['to_number'] = $callData['to_number'];
            }

            // Update the call record
            if (!empty($updates)) {
                $call->update($updates);
                echo "✅ Updated with " . count($updates) . " fields\n";

                // Show what was updated
                foreach ($updates as $field => $value) {
                    if ($field === 'transcript') {
                        echo "  - {$field}: " . substr($value, 0, 50) . "...\n";
                    } elseif ($field === 'summary') {
                        echo "  - {$field}: " . substr($value, 0, 100) . "...\n";
                    } else {
                        echo "  - {$field}: {$value}\n";
                    }
                }

                $updated++;
            } else {
                echo "⚠️ No new data available\n";
            }

        } else {
            echo "❌ API Error: " . $response->status() . "\n";
            if ($response->status() === 404) {
                echo "  Call not found in Retell\n";
            } elseif ($response->status() === 401) {
                echo "  Authentication failed - check API key\n";
            }
            $failed++;
        }

    } catch (\Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
        $failed++;
    }

    // Rate limiting - don't overwhelm the API
    usleep(500000); // 0.5 second delay between requests
}

// Now calculate costs for calls with duration
echo "\n=== Calculating Costs ===\n";

$callsNeedingCosts = Call::whereNotNull('duration_sec')
    ->where(function($query) {
        $query->whereNull('cost')
            ->orWhere('cost', 0)
            ->orWhereNull('customer_cost');
    })
    ->get();

echo "Found " . $callsNeedingCosts->count() . " calls needing cost calculation\n";

foreach ($callsNeedingCosts as $call) {
    try {
        // Basic cost calculation (adjust rates as needed)
        $ratePerMinute = 15; // cents per minute
        $minutes = ceil($call->duration_sec / 60);
        $cost = $minutes * $ratePerMinute;

        $call->update([
            'cost' => $cost,
            'base_cost' => $cost,
            'reseller_cost' => round($cost * 1.3), // 30% markup for reseller
            'customer_cost' => round($cost * 1.6), // 60% markup for customer
        ]);

        echo "💰 Updated costs for call {$call->id}: {$cost} cents\n";
    } catch (\Exception $e) {
        echo "❌ Cost calculation failed for call {$call->id}: " . $e->getMessage() . "\n";
    }
}

// Update session outcomes based on appointment_made
echo "\n=== Setting Session Outcomes ===\n";

$callsNeedingOutcome = Call::whereNull('session_outcome')
    ->whereNotNull('appointment_made')
    ->get();

foreach ($callsNeedingOutcome as $call) {
    $outcome = $call->appointment_made ? 'appointment_scheduled' : 'no_interest';
    $call->update(['session_outcome' => $outcome]);
    echo "📋 Set outcome for call {$call->id}: {$outcome}\n";
}

echo "\n=== Summary ===\n";
echo "✅ Successfully updated: {$updated} calls\n";
echo "❌ Failed: {$failed} calls\n";
echo "💰 Cost calculated for: " . $callsNeedingCosts->count() . " calls\n";
echo "📋 Outcomes set for: " . $callsNeedingOutcome->count() . " calls\n";