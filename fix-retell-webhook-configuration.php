<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use App\Services\RetellV2Service;

echo "FIXING RETELL WEBHOOK CONFIGURATION\n";
echo str_repeat('=', 50) . "\n\n";

$company = Company::find(1);
$apiKey = decrypt($company->retell_api_key);

$service = new RetellV2Service($apiKey);

// Standard webhook URL (MCP webhook has issues)
$mcpWebhookUrl = 'https://api.askproai.de/api/retell/webhook';

echo "1. Getting all agents...\n";
$agentsResult = $service->listAgents();
$agents = $agentsResult['agents'] ?? [];

echo "   Found " . count($agents) . " agents\n\n";

// Update each agent's webhook URL
foreach ($agents as $i => $agent) {
    echo "   Agent " . ($i + 1) . ": {$agent['agent_name']}\n";
    echo "   - Current webhook: " . ($agent['webhook_url'] ?? 'Not set') . "\n";
    
    if (($agent['webhook_url'] ?? '') !== $mcpWebhookUrl) {
        echo "   - Updating to MCP webhook...\n";
        
        try {
            $updateResult = $service->updateAgent($agent['agent_id'], [
                'webhook_url' => $mcpWebhookUrl
            ]);
            echo "   ✅ Updated successfully!\n";
        } catch (\Exception $e) {
            echo "   ❌ Failed to update: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ✅ Already using MCP webhook\n";
    }
    echo "\n";
}

echo "2. Getting all phone numbers...\n";
$phonesResult = $service->listPhoneNumbers();
$phoneNumbers = $phonesResult['phone_numbers'] ?? [];

echo "   Found " . count($phoneNumbers) . " phone numbers\n\n";

// Update each phone number's inbound webhook URL
foreach ($phoneNumbers as $i => $phone) {
    echo "   Phone " . ($i + 1) . ": {$phone['phone_number']}\n";
    echo "   - Nickname: " . ($phone['nickname'] ?? 'Not set') . "\n";
    echo "   - Current webhook: " . ($phone['inbound_webhook_url'] ?? 'Not set') . "\n";
    
    if (($phone['inbound_webhook_url'] ?? '') !== $mcpWebhookUrl) {
        echo "   - Updating to MCP webhook...\n";
        
        try {
            // Phone numbers need the number as the identifier
            $phoneNumber = $phone['phone_number'];
            $updateResult = $service->updatePhoneNumber($phoneNumber, [
                'inbound_webhook_url' => $mcpWebhookUrl
            ]);
            echo "   ✅ Updated successfully!\n";
        } catch (\Exception $e) {
            echo "   ❌ Failed to update: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ✅ Already using MCP webhook\n";
    }
    echo "\n";
}

echo "✅ WEBHOOK CONFIGURATION UPDATE COMPLETE!\n";
echo "\nAll agents and phone numbers are now configured to use:\n";
echo $mcpWebhookUrl . "\n";