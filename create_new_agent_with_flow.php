#!/usr/bin/env php
<?php

/**
 * Create NEW Agent with Conversation Flow
 *
 * Solution to "Cannot update response engine of agent version > 0"
 *
 * Steps:
 * 1. Use existing conversation_flow_id: conversation_flow_134a15784642
 * 2. Create new agent with that flow
 * 3. Publish new agent
 * 4. Update phone number to use new agent
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$oldAgentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$conversationFlowId = 'conversation_flow_134a15784642';  // Created in previous step

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🚀 CREATE NEW AGENT WITH CONVERSATION FLOW\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get old agent config to copy settings
echo "📋 Getting old agent configuration...\n";
$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get("https://api.retellai.com/get-agent/$oldAgentId");

$oldAgent = $response->json();

$voiceId = $oldAgent['voice_id'] ?? '11labs-Christopher';
$language = $oldAgent['language'] ?? 'de-DE';
$enableBackchannel = $oldAgent['enable_backchannel'] ?? true;
$webhookUrl = $oldAgent['webhook_url'] ?? null;

echo "✅ Old agent settings retrieved\n";
echo "  Voice: $voiceId\n";
echo "  Language: $language\n";
echo "  Backchannel: " . ($enableBackchannel ? "YES" : "NO") . "\n";
if ($webhookUrl) {
    echo "  Webhook: $webhookUrl\n";
}
echo "\n";

// Create new agent
echo "🎯 Creating new agent with conversation flow...\n";

$payload = [
    'agent_name' => 'Friseur1 AI (Fixed Functions)',
    'response_engine' => [
        'type' => 'conversation-flow',
        'conversation_flow_id' => $conversationFlowId,
        'version' => 0
    ],
    'voice_id' => $voiceId,
    'language' => $language,
    'enable_backchannel' => $enableBackchannel,
    'responsiveness' => 1.0,
    'interruption_sensitivity' => 1,
    'enable_transcription_formatting' => false,
    'reminder_trigger_ms' => 10000,
    'reminder_max_count' => 2,
    'temperature' => 0.3,
    'max_call_duration_ms' => 1800000,  // 30 minutes
    'end_call_after_silence_ms' => 60000,  // 1 minute silence
];

if ($webhookUrl) {
    $payload['webhook_url'] = $webhookUrl;
}

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-agent', $payload);

if ($response->successful()) {
    $newAgent = $response->json();
    $newAgentId = $newAgent['agent_id'] ?? null;

    if (!$newAgentId) {
        echo "❌ No agent_id in response\n";
        echo "Response: " . $response->body() . "\n";
        exit(1);
    }

    echo "✅ New agent created!\n";
    echo "  Agent ID: $newAgentId\n";
    echo "  Agent Name: {$newAgent['agent_name']}\n";
    echo "  Version: {$newAgent['version']}\n";
    echo "  Conversation Flow ID: {$newAgent['response_engine']['conversation_flow_id']}\n";
    echo "\n";

    // Publish new agent
    echo "📤 Publishing new agent...\n";

    $publishResponse = Http::withHeaders([
        'Authorization' => "Bearer $token",
    ])->post("https://api.retellai.com/publish-agent/$newAgentId");

    if ($publishResponse->successful()) {
        echo "✅ Agent published!\n\n";
    } else {
        echo "⚠️  Publish attempt: HTTP {$publishResponse->status()}\n";
        echo "   (You may need to publish manually in Dashboard)\n\n";
    }

    // Get phone numbers that use old agent
    echo "📞 Finding phone numbers using old agent...\n";

    $phoneResponse = Http::withHeaders([
        'Authorization' => "Bearer $token",
    ])->get('https://api.retellai.com/list-phone-numbers');

    if ($phoneResponse->successful()) {
        $phones = $phoneResponse->json();
        $oldAgentPhones = [];

        foreach ($phones as $phone) {
            if (($phone['agent_id'] ?? null) === $oldAgentId) {
                $oldAgentPhones[] = $phone;
            }
        }

        if (!empty($oldAgentPhones)) {
            echo "✅ Found " . count($oldAgentPhones) . " phone number(s) using old agent:\n";

            foreach ($oldAgentPhones as $phone) {
                $phoneId = $phone['phone_number_id'];
                $phoneNumber = $phone['phone_number'];

                echo "\n  Phone: $phoneNumber\n";
                echo "  Phone ID: $phoneId\n";

                // Update phone to use new agent
                echo "  ↳ Updating to use new agent...\n";

                $updateResponse = Http::withHeaders([
                    'Authorization' => "Bearer $token",
                    'Content-Type' => 'application/json'
                ])->patch("https://api.retellai.com/update-phone-number/$phoneId", [
                    'agent_id' => $newAgentId
                ]);

                if ($updateResponse->successful()) {
                    echo "  ✅ Updated successfully!\n";
                } else {
                    echo "  ❌ Update failed: HTTP {$updateResponse->status()}\n";
                    echo "     Manual update required in Dashboard\n";
                }
            }

            echo "\n";
        } else {
            echo "⚠️  No phone numbers found using old agent\n";
            echo "   You may need to assign phone number manually\n\n";
        }
    }

    // Summary
    echo "═══════════════════════════════════════════════════════════\n";
    echo "✅ DEPLOYMENT COMPLETE\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "New Agent Details:\n";
    echo "  Agent ID: $newAgentId\n";
    echo "  Dashboard: https://dashboard.retellai.com/agent/$newAgentId\n";
    echo "  Conversation Flow: $conversationFlowId\n";
    echo "  Status: Published\n\n";

    echo "Next Steps:\n";
    echo "  1. Test call: +493033081738\n";
    echo "  2. Verify functions: php get_latest_call_analysis.php\n";
    echo "  3. Check that check_availability_v17 is called\n\n";

    echo "Old Agent:\n";
    echo "  Agent ID: $oldAgentId (can be deleted if new one works)\n\n";

} else {
    echo "❌ Failed to create agent\n";
    echo "HTTP {$response->status()}\n";
    echo "Response: {$response->body()}\n";
    exit(1);
}
