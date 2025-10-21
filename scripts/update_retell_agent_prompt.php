<?php

/**
 * Script to push the agent configuration to Retell API
 * This updates the agent prompt in Retell to match our database
 */

require_once __DIR__ . '/../bootstrap/app.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Get the agent from database
$agent = DB::table('retell_agents')
    ->where('agent_id', 'agent_9a8202a740cd3120d96fcfda1e')
    ->first();

if (!$agent) {
    die("❌ Agent not found in database\n");
}

echo "🚀 Starting agent update to Retell API\n";
echo "Agent ID: {$agent->agent_id}\n";
echo "Current Version: {$agent->version}\n";

// Extract the configuration
$config = json_decode($agent->configuration, true);

if (!$config) {
    die("❌ Could not parse agent configuration JSON\n");
}

echo "✅ Configuration loaded\n";

// Prepare the payload for Retell API
$payload = [
    'agent_name' => $agent->name,
    'agent_id' => $agent->agent_id,
    'llm_config' => [
        'model' => 'gemini-2.5-flash',
        'system_prompt' => $config['prompt'] ?? '',
        'initial_message' => $config['first_sentence'] ?? 'Guten Tag! Wie kann ich Ihnen helfen?',
        'temperature' => 0.7,
    ],
    'language' => $config['language'] ?? 'de-DE',
];

echo "📤 Payload prepared for Retell API\n";

// Get Retell API key from config
$retellApiKey = config('services.retell.api_key');
if (!$retellApiKey) {
    die("❌ Retell API key not configured\n");
}

// Update agent via Retell API
$url = "https://api.retellai.com/update-agent/{$agent->agent_id}";

echo "🔗 Calling Retell API: {$url}\n";

try {
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$retellApiKey}",
        'Content-Type' => 'application/json',
    ])->patch($url, $payload);

    echo "📬 Retell API Response Status: {$response->status()}\n";

    if ($response->successful()) {
        echo "✅ Agent successfully updated in Retell!\n";

        // Update sync status in database
        DB::table('retell_agents')
            ->where('agent_id', $agent->agent_id)
            ->update([
                'is_published' => 1,
                'sync_status' => 'synced',
                'last_synced_at' => now(),
            ]);

        echo "✅ Database updated: is_published=1, sync_status=synced\n";
        echo "\n✅ COMPLETE: Agent updated and ready to use!\n";
    } else {
        echo "❌ Retell API Error: {$response->status()}\n";
        echo "Response: " . $response->body() . "\n";
        die("❌ Failed to update agent in Retell\n");
    }
} catch (\Exception $e) {
    echo "❌ Exception calling Retell API: " . $e->getMessage() . "\n";
    die("❌ Error: " . $e->getMessage() . "\n");
}
