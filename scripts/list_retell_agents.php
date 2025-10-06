#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== Listing All Retell Agents ===\n\n";

$retellApiKey = env('RETELL_TOKEN', 'key_6ff998ba48e842092e04a5455d19');
$retellBaseUrl = env('RETELL_BASE', 'https://api.retellai.com');

try {
    // List all agents
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $retellApiKey,
        'Content-Type' => 'application/json',
    ])->get("{$retellBaseUrl}/list-agents");

    if ($response->successful()) {
        $agents = $response->json();

        if (!empty($agents)) {
            echo "Found " . count($agents) . " agent(s):\n\n";

            foreach ($agents as $agent) {
                echo "=====================================\n";
                echo "Agent ID: " . ($agent['agent_id'] ?? 'unknown') . "\n";
                echo "Name: " . ($agent['agent_name'] ?? 'unnamed') . "\n";
                echo "Webhook: " . ($agent['webhook_url'] ?? 'NOT SET') . "\n";
                echo "Language: " . ($agent['language'] ?? 'not set') . "\n";
                echo "Voice: " . ($agent['voice_id'] ?? 'not set') . "\n";
                echo "Created: " . ($agent['created_at'] ?? 'unknown') . "\n";

                if (isset($agent['last_modification'])) {
                    echo "Modified: " . $agent['last_modification'] . "\n";
                }

                // Check if this matches our webhook pattern
                if (isset($agent['webhook_url']) &&
                    str_contains($agent['webhook_url'], 'askproai')) {
                    echo "✅ This agent uses AskProAI webhook!\n";
                }

                echo "\n";
            }

            // Try to identify the correct agent
            $askproAgent = null;
            foreach ($agents as $agent) {
                if (isset($agent['agent_name']) &&
                    (str_contains(strtolower($agent['agent_name']), 'fabian') ||
                     str_contains(strtolower($agent['agent_name']), 'spitzer') ||
                     str_contains(strtolower($agent['agent_name']), 'askpro'))) {
                    $askproAgent = $agent;
                    break;
                }
            }

            if ($askproAgent) {
                echo "🎯 Likely AskProAI Agent Found:\n";
                echo "   ID: " . $askproAgent['agent_id'] . "\n";
                echo "   Name: " . $askproAgent['agent_name'] . "\n";
                echo "   Webhook: " . ($askproAgent['webhook_url'] ?? 'NOT SET') . "\n";

                $expectedWebhook = 'https://api.askproai.de/api/webhooks/retell';
                if (!isset($askproAgent['webhook_url']) ||
                    $askproAgent['webhook_url'] !== $expectedWebhook) {
                    echo "\n⚠️ Webhook needs to be updated to: {$expectedWebhook}\n";
                    echo "   Current: " . ($askproAgent['webhook_url'] ?? 'NOT SET') . "\n";

                    // Store agent ID for update
                    file_put_contents(
                        '/tmp/retell_agent_id.txt',
                        $askproAgent['agent_id']
                    );
                    echo "\n✅ Agent ID saved to /tmp/retell_agent_id.txt for update\n";
                }
            }

        } else {
            echo "No agents found.\n";
        }
    } else {
        echo "❌ Failed to list agents: " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}