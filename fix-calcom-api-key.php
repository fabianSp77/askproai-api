<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;

echo "🔧 FIXING CAL.COM API KEY\n";
echo str_repeat("=", 60) . "\n\n";

$company = Company::withoutGlobalScopes()->first();

if (!$company) {
    echo "❌ No company found!\n";
    exit(1);
}

echo "🏢 Company: " . $company->name . "\n";
echo "Current API Key (first 20 chars): " . substr($company->calcom_api_key, 0, 20) . "...\n";

// Get the correct API key from .env
$correctApiKey = 'cal_live_bd7aedbdf12085c5312c79ba73585920';
$eventTypeId = 2026979;

// Update the company with the correct values
$company->calcom_api_key = $correctApiKey;
$company->calcom_event_type_id = $eventTypeId;
$company->save();

echo "\n✅ Updated Cal.com configuration:\n";
echo "   API Key: " . substr($correctApiKey, 0, 20) . "...\n";
echo "   Event Type ID: $eventTypeId\n";

// Test the API key
echo "\n🧪 Testing Cal.com API Key...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.cal.com/v1/me");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $correctApiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ API Key is VALID!\n";
    $data = json_decode($response, true);
    echo "   Connected as: " . ($data['username'] ?? 'Unknown') . "\n";
    echo "   Email: " . ($data['email'] ?? 'Unknown') . "\n";
} else {
    echo "❌ API Key invalid (HTTP $httpCode)\n";
    echo "   Response: " . substr($response, 0, 200) . "\n";
}

echo "\n✅ Configuration updated!\n";