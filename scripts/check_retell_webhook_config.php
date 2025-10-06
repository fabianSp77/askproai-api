#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== Checking Retell Webhook Configuration ===\n\n";

$retellApiKey = env('RETELL_TOKEN', 'key_6ff998ba48e842092e04a5455d19');
$retellBaseUrl = env('RETELL_BASE', 'https://api.retellai.com');
$ourWebhookUrl = 'https://api.askproai.de/api/webhooks/retell';

// The agent ID we found from logs
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

echo "🔍 Fetching agent configuration for: {$agentId}\n";
echo "----------------------------------------\n";

try {
    // Get agent details
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $retellApiKey,
        'Content-Type' => 'application/json',
    ])->get("{$retellBaseUrl}/v2/get-agent/{$agentId}");

    if ($response->successful()) {
        $agentData = $response->json();

        echo "✅ Agent found: " . ($agentData['agent_name'] ?? 'Unknown') . "\n\n";

        // Check webhook URL
        echo "📌 Webhook Configuration:\n";
        $currentWebhook = $agentData['webhook_url'] ?? 'NOT SET';
        echo "  Current webhook: {$currentWebhook}\n";
        echo "  Expected webhook: {$ourWebhookUrl}\n";

        if ($currentWebhook !== $ourWebhookUrl) {
            echo "  ⚠️ WEBHOOK URL MISMATCH!\n\n";

            echo "Would you like to update the webhook URL? This will enable call_ended and call_analyzed events.\n";
            echo "Press 'y' to update, any other key to skip: ";

            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);

            if (trim($line) === 'y') {
                echo "\n📝 Updating webhook configuration...\n";

                // Update agent with correct webhook
                $updateResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $retellApiKey,
                    'Content-Type' => 'application/json',
                ])->patch("{$retellBaseUrl}/v2/update-agent/{$agentId}", [
                    'webhook_url' => $ourWebhookUrl,
                    // Ensure all events are enabled
                    'enable_backchannel' => true,
                    'enable_voicemail_detection' => true,
                    'end_call_after_silence_ms' => 30000,
                    'normalize_for_speech' => true,
                ]);

                if ($updateResponse->successful()) {
                    echo "✅ Webhook URL updated successfully!\n";
                    echo "✅ All webhook events enabled!\n";
                } else {
                    echo "❌ Failed to update webhook: " . $updateResponse->body() . "\n";
                }
            }
            fclose($handle);
        } else {
            echo "  ✅ Webhook URL is correct!\n";
        }

        // Check other important settings
        echo "\n🔧 Other Settings:\n";
        echo "  Language: " . ($agentData['language'] ?? 'not set') . "\n";
        echo "  Voice: " . ($agentData['voice_id'] ?? 'not set') . "\n";
        echo "  Model: " . ($agentData['llm_id'] ?? 'not set') . "\n";

        if (isset($agentData['enable_backchannel'])) {
            echo "  Backchannel: " . ($agentData['enable_backchannel'] ? '✅ Enabled' : '❌ Disabled') . "\n";
        }

        // Check if post-call analysis is enabled
        if (isset($agentData['post_call_analysis_data'])) {
            echo "\n📊 Post-Call Analysis Settings:\n";
            $analysis = $agentData['post_call_analysis_data'];

            if (is_array($analysis)) {
                echo "  ✅ Analysis enabled with " . count($analysis) . " prompts\n";
            } else {
                echo "  ⚠️ Analysis configuration unclear\n";
            }
        } else {
            echo "\n⚠️ Post-call analysis may not be configured\n";
            echo "This is why you might not receive call_analyzed events\n";
        }

        // List recent calls to verify
        echo "\n📞 Fetching recent calls to verify webhook delivery...\n";

        $callsResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $retellApiKey,
            'Content-Type' => 'application/json',
        ])->get("{$retellBaseUrl}/v2/list-calls", [
            'limit' => 5,
            'sort_order' => 'descending'
        ]);

        if ($callsResponse->successful()) {
            $calls = $callsResponse->json();

            if (isset($calls['results']) && count($calls['results']) > 0) {
                echo "\nLast 5 calls:\n";
                foreach ($calls['results'] as $call) {
                    $callId = $call['call_id'] ?? 'unknown';
                    $status = $call['call_status'] ?? 'unknown';
                    $startTime = isset($call['start_timestamp'])
                        ? date('Y-m-d H:i', $call['start_timestamp'] / 1000)
                        : 'unknown';

                    echo "  - {$callId}: {$status} at {$startTime}\n";
                }
            }
        }

    } else {
        echo "❌ Failed to fetch agent: " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Recommendations ===\n";
echo "1. ✅ Ensure webhook URL is set to: {$ourWebhookUrl}\n";
echo "2. ✅ Enable post-call analysis in Retell dashboard\n";
echo "3. ✅ Configure analysis prompts for sentiment detection\n";
echo "4. ✅ Test with a new call to verify all events are received\n";
echo "\nNote: Changes may take a few minutes to take effect.\n";