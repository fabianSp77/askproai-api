<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\RetellV2Service;

echo "🔧 SWITCHING TO TEST WEBHOOK (NO SIGNATURE VERIFICATION)\n";
echo str_repeat('=', 50) . "\n\n";

$company = Company::find(1);
$apiKey = decrypt($company->retell_api_key);
$service = new RetellV2Service($apiKey);

// Test webhook URL (bypasses signature verification)
$testWebhookUrl = 'https://api.askproai.de/api/retell/webhook-test';

echo "New webhook URL: $testWebhookUrl\n\n";

// Update agents
echo "Updating agents...\n";
$agentsResult = $service->listAgents();
$agents = $agentsResult['agents'] ?? [];

foreach ($agents as $agent) {
    if ($agent['agent_name'] === 'Online: Musterfriseur Terminierung') {
        echo "📱 Updating: " . $agent['agent_name'] . "\n";
        
        try {
            $service->updateAgent($agent['agent_id'], [
                'webhook_url' => $testWebhookUrl
            ]);
            echo "✅ Updated to test webhook\n\n";
        } catch (\Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n\n";
        }
    }
}

// Update phone numbers
echo "Updating phone numbers...\n";
$phonesResult = $service->listPhoneNumbers();
$phoneNumbers = $phonesResult['phone_numbers'] ?? [];

foreach ($phoneNumbers as $phone) {
    if ($phone['phone_number'] === '+493083793369') {
        echo "☎️  Updating: " . $phone['phone_number'] . "\n";
        
        try {
            $service->updatePhoneNumber($phone['phone_number'], [
                'inbound_webhook_url' => $testWebhookUrl
            ]);
            echo "✅ Updated to test webhook\n\n";
        } catch (\Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n\n";
        }
    }
}

echo "\n✅ DONE! Test webhook configured for Musterfriseur.\n";
echo "📞 Make a test call to +493083793369\n";
echo "📊 Check webhooks at: https://api.askproai.de/api/retell/webhook-debug\n";