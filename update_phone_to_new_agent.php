#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$newAgentId = 'agent_2d467d84eb674e5b3f5815d81c';
$phoneNumber = '+493033081738';

echo "\n📞 UPDATING PHONE NUMBER TO NEW AGENT\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// List all phone numbers
$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get('https://api.retellai.com/list-phone-numbers');

if (!$response->successful()) {
    echo "❌ Failed to list phone numbers\n";
    echo "HTTP {$response->status()}\n";
    exit(1);
}

$phones = $response->json();

echo "Phone numbers found: " . count($phones) . "\n\n";

// Debug: Show structure
if (!empty($phones)) {
    echo "First phone structure:\n";
    print_r(array_keys($phones[0]));
    echo "\n";
}

$phoneId = null;
$currentAgentId = null;

foreach ($phones as $phone) {
    // Debug each phone
    $num = $phone['phone_number'] ?? 'N/A';
    echo "Checking: $num\n";

    if ($num === $phoneNumber) {
        // Retell uses phone number itself as ID in update endpoint!
        $phoneId = $phoneNumber;
        $currentAgentId = $phone['inbound_agent_id'] ?? null;

        echo "\n✅ Found phone number!\n";
        echo "  Number: $phoneNumber\n";
        echo "  Current Agent: " . ($currentAgentId ?? 'None') . "\n\n";
        break;
    }
}

if (!$phoneId) {
    echo "\n❌ Phone number $phoneNumber not found\n";
    echo "Available numbers:\n";
    foreach ($phones as $phone) {
        echo "  - " . ($phone['phone_number'] ?? $phone['number'] ?? 'N/A') . "\n";
    }
    exit(1);
}

// Update to new agent
echo "Updating phone to new agent...\n";
echo "  From Agent: $currentAgentId\n";
echo "  To Agent: $newAgentId\n\n";

$updateResponse = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->patch("https://api.retellai.com/update-phone-number/$phoneId", [
    'agent_id' => $newAgentId
]);

if ($updateResponse->successful()) {
    echo "✅ Phone number updated successfully!\n\n";

    echo "═══════════════════════════════════════════════════════════\n";
    echo "🎉 SYSTEM READY FOR TESTING\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "Test Call Instructions:\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo "1. Call: $phoneNumber\n";
    echo "2. Say: 'Herrenhaarschnitt morgen 9 Uhr, mein Name ist Hans Schuster'\n";
    echo "3. Listen for:\n";
    echo "   ✅ AI prüft Verfügbarkeit (nicht halluziniert)\n";
    echo "   ✅ Echte Alternativen wenn nicht verfügbar\n";
    echo "   ✅ Keine unnötigen Fragen\n\n";

    echo "Verify After Call:\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo "php get_latest_call_analysis.php\n\n";

    echo "Expected Results:\n";
    echo "  ✅ initialize_call called\n";
    echo "  ✅ check_availability_v17 called (WITH backend request!)\n";
    echo "  ✅ Backend logs show real API calls\n";
    echo "  ✅ No hallucination\n\n";

    echo "New Agent Details:\n";
    echo "  Agent ID: $newAgentId\n";
    echo "  Dashboard: https://dashboard.retellai.com/agent/$newAgentId\n\n";

} else {
    echo "❌ Failed to update phone number\n";
    echo "HTTP {$updateResponse->status()}\n";
    echo "Response: {$updateResponse->body()}\n\n";

    echo "Manual Update Required:\n";
    echo "  1. Open: https://dashboard.retellai.com/phone-numbers\n";
    echo "  2. Find: $phoneNumber\n";
    echo "  3. Change Agent to: $newAgentId\n";
    exit(1);
}
