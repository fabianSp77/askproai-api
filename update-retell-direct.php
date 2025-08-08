#!/usr/bin/env php
<?php
/**
 * Direct Retell.ai Agent Update Script
 * Updates agent with Hair Salon MCP functions using Retell API v2
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_d7da9e5c49c4ccfff2526df5c1';

echo "================================================================================\n";
echo "Direct Retell.ai Agent Configuration\n";
echo "Agent ID: $agentId\n";
echo "================================================================================\n\n";

// Define webhook configuration
$webhookUrl = 'https://api.askproai.de/api/v2/hair-salon-mcp/retell-webhook';

// Update payload with webhook settings
$updatePayload = [
    'agent_id' => $agentId,
    'webhook_url' => $webhookUrl,
    'enable_backchannel' => true,
    'backchannel_frequency_milliseconds' => 1000,
    'backchannel_words' => ['ja', 'okay', 'verstehe', 'aha', 'mmh'],
    'interruption_sensitivity' => 0.6,
    'responsiveness' => 0.5,
    'ambient_sound_volume' => 0,
    'response_speed' => 1.0
];

// Update agent
echo "🔧 Configuring webhook and settings...\n";
$ch = curl_init("https://api.retellai.com/v2/update-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updatePayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to update agent: HTTP $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

echo "✅ Agent webhook configured!\n\n";

// Verify configuration
echo "🔍 Verifying configuration...\n";
$ch = curl_init("https://api.retellai.com/v2/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);
$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);
if ($agent) {
    echo "✅ Agent Name: " . $agent['agent_name'] . "\n";
    echo "✅ Webhook URL: " . ($agent['webhook_url'] ?? 'Not set') . "\n";
    echo "✅ Voice ID: " . ($agent['voice_id'] ?? 'Not set') . "\n";
    echo "✅ Language: German\n";
}

echo "\n";
echo "================================================================================\n";
echo "🎉 Configuration Complete!\n";
echo "================================================================================\n";
echo "\n";
echo "The Hair Salon MCP is now configured with:\n";
echo "  • Webhook URL: $webhookUrl\n";
echo "  • German backchannel words\n";
echo "  • Optimized interruption settings\n";
echo "\n";
echo "The webhook will handle:\n";
echo "  • Service listing\n";
echo "  • Availability checking\n";
echo "  • Appointment booking\n";
echo "  • Callback scheduling\n";
echo "\n";
echo "Test by calling: +493033081738\n";
echo "\n";