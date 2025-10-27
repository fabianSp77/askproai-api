<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$newAgentId = trim(file_get_contents(__DIR__ . '/llm_agent_id.txt'));
$phoneNumber = '+493033081738';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔍 VERIFY PHONE NUMBER ASSIGNMENT\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$resp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-phone-number/$phoneNumber");

if ($resp->successful()) {
    $phone = $resp->json();
    $currentAgent = $phone['inbound_agent_id'] ?? 'N/A';
    
    echo "Phone: $phoneNumber\n";
    echo "Current Agent: $currentAgent\n";
    echo "Expected Agent: $newAgentId\n\n";
    
    if ($currentAgent === $newAgentId) {
        echo "✅ Phone number correctly assigned to new LLM agent!\n\n";
    } else {
        echo "❌ Phone number still pointing to old agent!\n\n";
        echo "Attempting to update again...\n";
        
        $updateResp = Http::withHeaders([
            'Authorization' => "Bearer $token",
            'Content-Type' => 'application/json'
        ])->patch("https://api.retellai.com/update-phone-number/$phoneNumber", [
            'inbound_agent_id' => $newAgentId
        ]);
        
        echo "Update status: {$updateResp->status()}\n";
        
        if ($updateResp->successful()) {
            $updated = $updateResp->json();
            echo "✅ Updated! New agent: {$updated['inbound_agent_id']}\n\n";
        } else {
            echo "❌ Failed: {$updateResp->body()}\n\n";
        }
    }
} else {
    echo "❌ Failed to get phone number\n";
    echo $resp->body() . "\n";
}

