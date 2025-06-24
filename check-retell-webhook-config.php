<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use App\Services\RetellV2Service;

echo "CHECKING RETELL WEBHOOK CONFIGURATION\n";
echo str_repeat('=', 50) . "\n\n";

$company = Company::find(1);
if (!$company || !$company->retell_api_key) {
    echo "❌ No Retell API key found for company\n";
    exit(1);
}

try {
    $apiKey = decrypt($company->retell_api_key);
    $service = new RetellV2Service($apiKey);
    
    // Expected webhook URLs
    $mcpWebhookUrl = 'https://api.askproai.de/api/mcp/retell/webhook';
    $legacyWebhookUrl = 'https://api.askproai.de/api/retell/webhook';
    
    echo "Expected webhook URLs:\n";
    echo "- MCP (new): $mcpWebhookUrl\n";
    echo "- Legacy: $legacyWebhookUrl\n\n";
    
    // Get all agents
    echo "1. Checking Agents:\n";
    echo str_repeat('-', 30) . "\n";
    
    $agentsResult = $service->listAgents();
    $agents = $agentsResult['agents'] ?? [];
    
    if (empty($agents)) {
        echo "❌ No agents found\n\n";
    } else {
        foreach ($agents as $agent) {
            echo "Agent: {$agent['agent_name']} (ID: {$agent['agent_id']})\n";
            echo "  Webhook URL: " . ($agent['webhook_url'] ?? 'NOT SET') . "\n";
            
            if (!isset($agent['webhook_url'])) {
                echo "  ⚠️  NO WEBHOOK URL SET!\n";
            } elseif ($agent['webhook_url'] === $mcpWebhookUrl) {
                echo "  ✅ Using MCP webhook (correct)\n";
            } elseif ($agent['webhook_url'] === $legacyWebhookUrl) {
                echo "  ⚠️  Using legacy webhook (needs update)\n";
            } else {
                echo "  ❌ Using unknown webhook URL\n";
            }
            echo "\n";
        }
    }
    
    // Get all phone numbers
    echo "2. Checking Phone Numbers:\n";
    echo str_repeat('-', 30) . "\n";
    
    $phonesResult = $service->listPhoneNumbers();
    $phoneNumbers = $phonesResult['phone_numbers'] ?? [];
    
    if (empty($phoneNumbers)) {
        echo "❌ No phone numbers found\n\n";
    } else {
        foreach ($phoneNumbers as $phone) {
            echo "Phone: {$phone['phone_number']}\n";
            echo "  Nickname: " . ($phone['nickname'] ?? 'Not set') . "\n";
            echo "  Inbound Webhook: " . ($phone['inbound_webhook_url'] ?? 'NOT SET') . "\n";
            echo "  Agent ID: " . ($phone['agent_id'] ?? 'NOT SET') . "\n";
            
            if (!isset($phone['inbound_webhook_url'])) {
                echo "  ⚠️  NO INBOUND WEBHOOK URL SET!\n";
            } elseif ($phone['inbound_webhook_url'] === $mcpWebhookUrl) {
                echo "  ✅ Using MCP webhook (correct)\n";
            } elseif ($phone['inbound_webhook_url'] === $legacyWebhookUrl) {
                echo "  ⚠️  Using legacy webhook (needs update)\n";
            } else {
                echo "  ❌ Using unknown webhook URL\n";
            }
            echo "\n";
        }
    }
    
    // Check recent webhook events in database
    echo "3. Recent Webhook Events in Database:\n";
    echo str_repeat('-', 30) . "\n";
    
    $recentWebhooks = \DB::table('webhook_events')
        ->where('source', 'retell')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    if ($recentWebhooks->isEmpty()) {
        echo "❌ No recent webhook events found\n";
    } else {
        foreach ($recentWebhooks as $webhook) {
            $payload = json_decode($webhook->payload, true);
            echo "Event: {$webhook->event} at {$webhook->created_at}\n";
            echo "  Status: " . ($webhook->processed_at ? 'processed' : 'pending') . "\n";
            echo "  Call ID: " . ($payload['call']['call_id'] ?? $payload['call_id'] ?? 'N/A') . "\n";
            if ($webhook->error) {
                echo "  Error: {$webhook->error}\n";
            }
            echo "\n";
        }
    }
    
    echo "\nSUMMARY:\n";
    echo str_repeat('=', 50) . "\n";
    
    // Count configurations
    $agentsWithMcp = 0;
    $agentsWithLegacy = 0;
    $agentsWithNoWebhook = 0;
    
    foreach ($agents as $agent) {
        if (!isset($agent['webhook_url'])) {
            $agentsWithNoWebhook++;
        } elseif ($agent['webhook_url'] === $mcpWebhookUrl) {
            $agentsWithMcp++;
        } elseif ($agent['webhook_url'] === $legacyWebhookUrl) {
            $agentsWithLegacy++;
        }
    }
    
    echo "Agents:\n";
    echo "  - With MCP webhook: $agentsWithMcp\n";
    echo "  - With legacy webhook: $agentsWithLegacy\n";
    echo "  - Without webhook: $agentsWithNoWebhook\n";
    echo "  - Total: " . count($agents) . "\n\n";
    
    if ($agentsWithLegacy > 0 || $agentsWithNoWebhook > 0) {
        echo "⚠️  ACTION REQUIRED: Run 'php fix-retell-webhook-configuration.php' to update webhooks\n";
    } else {
        echo "✅ All agents are properly configured with MCP webhook\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}