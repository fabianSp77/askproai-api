<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\RetellV2Service;

echo "🔍 DIAGNOSING PHONE-AGENT MAPPING ISSUE\n";
echo str_repeat('=', 50) . "\n\n";

$company = Company::find(1);
$apiKey = decrypt($company->retell_api_key);
$service = new RetellV2Service($apiKey);

// Check phone number configuration
echo "1. PHONE NUMBER CONFIGURATION\n";
$phonesResult = $service->listPhoneNumbers();
$phoneNumbers = $phonesResult['phone_numbers'] ?? [];

foreach ($phoneNumbers as $phone) {
    if ($phone['phone_number'] === '+493083793369') {
        echo "\n☎️  Phone: " . $phone['phone_number'] . "\n";
        echo "   Nickname: " . ($phone['nickname'] ?? 'none') . "\n";
        echo "   Agent ID: " . ($phone['agent_id'] ?? 'NOT SET') . "\n";
        echo "   Inbound Webhook: " . ($phone['inbound_webhook_url'] ?? 'NOT SET') . "\n";
        
        if (($phone['agent_id'] ?? '') === 'agent_9a8202a740cd3120d96fcfda1e') {
            echo "   ❌ WRONG AGENT! This is the Rechtliches agent\n";
        }
        
        // Check if there's a default agent setting
        if (isset($phone['inbound_agent_id'])) {
            echo "   Inbound Agent ID: " . $phone['inbound_agent_id'] . "\n";
        }
    }
}

// Check both agents
echo "\n\n2. AGENT COMPARISON\n";
echo str_repeat('-', 30) . "\n";

$musterfriseurAgentId = 'agent_321b510badbbc129d1464ec8bd';
$rechtlichesAgentId = 'agent_9a8202a740cd3120d96fcfda1e';

$agents = $service->listAgents();

foreach ($agents['agents'] ?? [] as $agent) {
    if ($agent['agent_id'] === $musterfriseurAgentId || $agent['agent_id'] === $rechtlichesAgentId) {
        echo "\n" . ($agent['agent_id'] === $musterfriseurAgentId ? "✅ CORRECT" : "❌ WRONG") . " Agent:\n";
        echo "   Name: " . $agent['agent_name'] . "\n";
        echo "   ID: " . $agent['agent_id'] . "\n";
        echo "   Webhook URL: " . ($agent['webhook_url'] ?? 'NOT SET') . "\n";
        echo "   Webhook Events: " . json_encode($agent['webhook_events'] ?? []) . "\n";
        echo "   Custom Functions: " . (isset($agent['custom_functions']) && count($agent['custom_functions']) > 0 ? count($agent['custom_functions']) . " configured" : "NONE") . "\n";
    }
}

echo "\n\n3. RECOMMENDED SOLUTION\n";
echo str_repeat('-', 30) . "\n";
echo "The phone number is not correctly linked to the Musterfriseur agent.\n";
echo "Visit the new Retell Configuration Center to fix this:\n\n";
echo "🔗 https://api.askproai.de/admin/retell-configuration-center\n\n";
echo "In the Configuration Center, you can:\n";
echo "- See all agents and their configurations\n";
echo "- Update phone number assignments\n";
echo "- Test webhooks and custom functions\n";
echo "- Monitor call status in real-time\n";