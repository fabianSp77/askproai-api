<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Retell\RetellV2Service;

echo "FIXING WEBHOOK BACK TO STANDARD ENDPOINT\n";
echo "=========================================\n\n";

$retell = new RetellV2Service();

// The WORKING webhook URL (not MCP)
$standardWebhookUrl = 'https://api.askproai.de/api/retell/webhook';

echo "Setting webhook URL to: $standardWebhookUrl\n\n";

// Get all agents
$agents = $retell->listAgents();
echo "Found " . count($agents) . " agents\n\n";

$updated = 0;
foreach ($agents as $agent) {
    echo "Agent: " . $agent['agent_name'] . "\n";
    echo "Current webhook: " . ($agent['webhook_url'] ?? 'Not set') . "\n";
    
    if (($agent['webhook_url'] ?? '') !== $standardWebhookUrl) {
        echo "Updating...\n";
        
        $updateData = [
            'webhook_url' => $standardWebhookUrl
        ];
        
        try {
            $result = $retell->updateAgent($agent['agent_id'], $updateData);
            echo "✅ Updated successfully!\n\n";
            $updated++;
        } catch (\Exception $e) {
            echo "❌ Failed: " . $e->getMessage() . "\n\n";
        }
    } else {
        echo "✅ Already using standard webhook\n\n";
    }
}

// Update phone numbers
echo "\n\nUpdating Phone Numbers...\n";
echo "========================\n\n";

$phoneNumbers = $retell->listPhoneNumbers();
echo "Found " . count($phoneNumbers) . " phone numbers\n\n";

foreach ($phoneNumbers as $phone) {
    echo "Phone: " . $phone['phone_number'] . "\n";
    echo "Current webhook: " . ($phone['inbound_webhook_url'] ?? 'Not set') . "\n";
    
    if (($phone['inbound_webhook_url'] ?? '') !== $standardWebhookUrl) {
        echo "Updating...\n";
        
        $updateData = [
            'inbound_webhook_url' => $standardWebhookUrl
        ];
        
        try {
            $result = $retell->updatePhoneNumber($phone['phone_number'], $updateData);
            echo "✅ Updated successfully!\n\n";
            $updated++;
        } catch (\Exception $e) {
            echo "❌ Failed: " . $e->getMessage() . "\n\n";
        }
    } else {
        echo "✅ Already using standard webhook\n\n";
    }
}

echo "\n✅ COMPLETE! Updated $updated configurations to use standard webhook.\n";
echo "Webhook URL: $standardWebhookUrl\n\n";
echo "⚠️  IMPORTANT: Make sure the webhook secret in Retell matches RETELL_WEBHOOK_SECRET in .env!\n";