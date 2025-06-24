<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\RetellV2Service;

echo "🔍 CHECKING CURRENT WEBHOOK CONFIGURATION\n";
echo str_repeat('=', 50) . "\n\n";

$company = Company::find(1);
$apiKey = decrypt($company->retell_api_key);
$service = new RetellV2Service($apiKey);

// Check Musterfriseur agent
echo "1. MUSTERFRISEUR AGENT:\n";
$agentsResult = $service->listAgents();
$agents = $agentsResult['agents'] ?? [];

foreach ($agents as $agent) {
    if (strpos($agent['agent_name'], 'Musterfriseur') !== false) {
        echo "\n📱 " . $agent['agent_name'] . "\n";
        echo "   Agent ID: " . $agent['agent_id'] . "\n";
        echo "   Webhook URL: " . ($agent['webhook_url'] ?? 'NOT SET') . "\n";
        echo "   Last Updated: " . ($agent['last_modification'] ?? 'unknown') . "\n";
    }
}

// Check phone number
echo "\n\n2. PHONE NUMBER +493083793369:\n";
$phonesResult = $service->listPhoneNumbers();
$phoneNumbers = $phonesResult['phone_numbers'] ?? [];

foreach ($phoneNumbers as $phone) {
    if ($phone['phone_number'] === '+493083793369') {
        echo "\n☎️  " . $phone['phone_number'] . "\n";
        echo "   Nickname: " . ($phone['nickname'] ?? 'none') . "\n";
        echo "   Agent ID: " . ($phone['agent_id'] ?? 'NOT SET') . "\n";
        echo "   Inbound Webhook: " . ($phone['inbound_webhook_url'] ?? 'NOT SET') . "\n";
        echo "   Status: " . ($phone['phone_number_pretty'] ?? 'unknown') . "\n";
    }
}

// Check if the endpoints are reachable
echo "\n\n3. ENDPOINT TESTS:\n";

$testUrls = [
    'https://api.askproai.de/api/retell/webhook-test',
    'https://api.askproai.de/api/retell/webhook',
    'https://api.askproai.de/api/retell/collect-appointment'
];

foreach ($testUrls as $url) {
    echo "\n Testing: $url\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "   Status: $httpCode ";
    if ($httpCode === 405) {
        echo "✅ (Method Not Allowed - endpoint exists)\n";
    } elseif ($httpCode === 200) {
        echo "✅ (OK)\n";
    } elseif ($httpCode === 404) {
        echo "❌ (Not Found)\n";
    } else {
        echo "⚠️\n";
    }
}

echo "\n\n4. CUSTOM FUNCTIONS:\n";
// Check first agent's custom functions
if (!empty($agents)) {
    $firstAgent = null;
    foreach ($agents as $agent) {
        if (strpos($agent['agent_name'], 'Musterfriseur') !== false) {
            $firstAgent = $agent;
            break;
        }
    }
    
    if ($firstAgent && isset($firstAgent['custom_functions'])) {
        echo "\nCustom functions configured:\n";
        foreach ($firstAgent['custom_functions'] as $func) {
            echo "   - " . $func['name'] . "\n";
        }
    } else {
        echo "\n❌ No custom functions found!\n";
    }
}