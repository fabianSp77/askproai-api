<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🎤 LIST AVAILABLE VOICES\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/list-voices');

if ($response->successful()) {
    $voices = $response->json();
    
    echo "Available voices: " . count($voices) . "\n\n";
    
    // Check if 11labs-Christopher exists
    $christopherExists = false;
    
    foreach ($voices as $voice) {
        $id = $voice['voice_id'] ?? 'N/A';
        $name = $voice['voice_name'] ?? 'N/A';
        $provider = $voice['provider'] ?? 'N/A';
        
        if (strpos($id, 'Christopher') !== false || strpos($name, 'Christopher') !== false) {
            echo "✅ FOUND: $id - $name ($provider)\n";
            $christopherExists = true;
        }
    }
    
    if (!$christopherExists) {
        echo "❌ 11labs-Christopher NOT FOUND!\n\n";
        echo "Available 11labs voices:\n";
        echo "────────────────────────────────────────────────────────\n";
        
        foreach ($voices as $voice) {
            $id = $voice['voice_id'] ?? 'N/A';
            $name = $voice['voice_name'] ?? 'N/A';
            $provider = $voice['provider'] ?? 'N/A';
            
            if (strpos($id, '11labs') !== false || $provider === 'elevenlabs') {
                echo "  • $id - $name\n";
            }
        }
    }
    
    echo "\n";
    
    // Check which voice the existing agent uses
    echo "Checking existing agent's voice:\n";
    $agentResp = Http::withHeaders(['Authorization' => "Bearer $token"])
        ->get('https://api.retellai.com/get-agent/agent_2d467d84eb674e5b3f5815d81c');
    
    if ($agentResp->successful()) {
        $agent = $agentResp->json();
        $voiceId = $agent['voice_id'] ?? 'N/A';
        echo "  Agent uses: $voiceId ✅\n\n";
        
        file_put_contents('working_voice_id.txt', $voiceId);
        echo "Saved working voice ID to: working_voice_id.txt\n";
    }
    
} else {
    echo "❌ Failed to list voices: {$response->status()}\n";
    echo $response->body() . "\n";
}

echo "\n";
