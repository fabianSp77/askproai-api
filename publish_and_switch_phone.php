<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$agentId = trim(file_get_contents(__DIR__ . '/llm_agent_id.txt'));
$phoneNumber = '+493033081738';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "📱 PUBLISH AGENT & SWITCH PHONE NUMBER\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Step 1: Publish agent
echo "Step 1: Publishing agent $agentId...\n";

$publishResp = Http::withHeaders([
    'Authorization' => "Bearer $token"
])->post("https://api.retellai.com/publish-agent/$agentId");

echo "  Status: {$publishResp->status()}\n";

if ($publishResp->successful()) {
    echo "  ✅ Agent published!\n\n";
} else {
    echo "  ⚠️ Publish response: " . substr($publishResp->body(), 0, 100) . "\n\n";
}

// Step 2: Update phone number
echo "Step 2: Switching phone $phoneNumber to new agent...\n";

$phoneResp = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->patch("https://api.retellai.com/update-phone-number/$phoneNumber", [
    'agent_id' => $agentId
]);

echo "  Status: {$phoneResp->status()}\n";

if ($phoneResp->successful()) {
    $phone = $phoneResp->json();
    echo "  ✅ Phone number updated!\n";
    echo "  Inbound Agent: {$phone['inbound_agent_id']}\n\n";
    
    echo "═══════════════════════════════════════════════════════════\n";
    echo "✅✅✅ COMPLETE! READY FOR TEST CALL! ✅✅✅\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    echo "LLM Agent: $agentId\n";
    echo "Phone: $phoneNumber\n";
    echo "Voice: 11labs-Carola (de-DE)\n\n";
    
    echo "NEXT: Make test call to $phoneNumber\n";
    echo "Expected behavior:\n";
    echo "  1. AI answers as Carola from Friseur 1\n";
    echo "  2. Asks for Service, Date, Time\n";
    echo "  3. CALLS check_availability_v17 to check availability\n";
    echo "  4. If available, asks if customer wants to book\n";
    echo "  5. CALLS check_availability_v17 again to book\n\n";
    
    echo "Monitor backend: tail -f storage/logs/laravel.log\n\n";
    
} else {
    echo "  ❌ Failed to update phone\n";
    echo "  Response: {$phoneResp->body()}\n";
}

