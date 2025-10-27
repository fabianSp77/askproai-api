<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

echo "═══════════════════════════════════════════════════════\n";
echo "🔧 UPDATING AGENT WEBHOOK EVENTS\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Send only the fields we want to update
$updatePayload = [
    'webhook_url' => 'https://api.askproai.de/api/webhooks/retell',
    'events_to_record' => [
        'call_started',
        'call_ended',
        'call_analyzed'
    ]
];

echo "📝 Updating agent with:\n";
echo "   Webhook URL: " . $updatePayload['webhook_url'] . "\n";
echo "   Events: " . implode(', ', $updatePayload['events_to_record']) . "\n\n";

// Update agent via API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/update-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updatePayload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Agent webhook events updated successfully!\n\n";
    $updated = json_decode($response, true);
    
    echo "📋 VERIFICATION:\n";
    echo "─────────────────────────────────────────\n";
    echo "Webhook URL: " . ($updated['webhook_url'] ?? 'N/A') . "\n";
    echo "Events to Record:\n";
    if (isset($updated['events_to_record'])) {
        foreach ($updated['events_to_record'] as $event) {
            echo "  ✓ $event\n";
        }
    } else {
        echo "  ❌ No events in response (check with list-agents)\n";
    }
    
    file_put_contents(__DIR__ . '/agent_webhook_update_response.json', json_encode($updated, JSON_PRETTY_PRINT));
    echo "\n✅ Response saved to: agent_webhook_update_response.json\n";
} else {
    echo "❌ Failed to update agent! HTTP $httpCode\n";
    echo "Response: $response\n";
}
