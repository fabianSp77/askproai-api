<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== FRISEUR 1 COMPLETE VERIFICATION ===\n\n";

// 1. Database Configuration
echo "📊 1. DATABASE CONFIGURATION\n";
echo str_repeat("=", 50) . "\n";

$branch = DB::table('branches')
    ->where('company_id', 1)
    ->where('name', 'Friseur 1 Zentrale')
    ->first();

echo "Branch: {$branch->name}\n";
echo "Agent ID: {$branch->retell_agent_id}\n";
echo "Phone: {$branch->phone_number}\n";
echo "Last Updated: {$branch->updated_at}\n\n";

// 2. Service Synonyms
echo "📝 2. SYNONYM SYSTEM\n";
echo str_repeat("=", 50) . "\n";

$synonymCount = DB::table('service_synonyms')
    ->join('services', 'service_synonyms.service_id', '=', 'services.id')
    ->where('services.company_id', 1)
    ->count();

echo "Total Synonyms: $synonymCount\n";

// Check Hair Detox specifically
$hairdetoxService = DB::table('services')
    ->where('company_id', 1)
    ->where('name', 'Hairdetox')
    ->first();

if ($hairdetoxService) {
    $hairdetoxSynonyms = DB::table('service_synonyms')
        ->where('service_id', $hairdetoxService->id)
        ->orderByDesc('confidence')
        ->get(['synonym', 'confidence']);

    echo "\n'Hairdetox' Synonyms:\n";
    foreach ($hairdetoxSynonyms as $syn) {
        $confidencePercent = round($syn->confidence * 100);
        echo "  → {$syn->synonym} ({$confidencePercent}%)\n";
    }
}

echo "\n";

// 3. Active Services
echo "🛍️  3. ACTIVE SERVICES\n";
echo str_repeat("=", 50) . "\n";

$services = DB::table('services')
    ->where('company_id', 1)
    ->where('is_active', true)
    ->orderBy('name')
    ->get(['name', 'price', 'duration_minutes']);

echo "Total: {$services->count()} services\n\n";
foreach ($services as $service) {
    echo sprintf("  • %-40s %6.2f EUR  %3d min\n",
        $service->name,
        $service->price,
        $service->duration_minutes
    );
}

echo "\n";

// 4. Retell Agent Status
echo "🤖 4. RETELL AGENT STATUS\n";
echo str_repeat("=", 50) . "\n";

$agentId = $branch->retell_agent_id;
$apiKey = config('services.retellai.api_key');
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

$agentResponse = Http::withHeaders([
    'Authorization' => "Bearer {$apiKey}",
    'Content-Type' => 'application/json'
])->get("{$baseUrl}/get-agent/{$agentId}");

if ($agentResponse->successful()) {
    $agent = $agentResponse->json();
    echo "Agent Name: " . ($agent['agent_name'] ?? 'Unknown') . "\n";
    echo "Agent ID: " . ($agent['agent_id'] ?? 'Unknown') . "\n";
    echo "Version: " . ($agent['version'] ?? 'unknown') . "\n";
    echo "Type: " . ($agent['response_engine']['type'] ?? 'unknown') . "\n";

    if (isset($agent['response_engine']['conversation_flow_id'])) {
        $flowId = $agent['response_engine']['conversation_flow_id'];
        echo "Flow ID: $flowId\n\n";

        // 5. Conversation Flow Details
        echo "💬 5. CONVERSATION FLOW DETAILS\n";
        echo str_repeat("=", 50) . "\n";

        $flowResponse = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->get("{$baseUrl}/get-conversation-flow/{$flowId}");

        if ($flowResponse->successful()) {
            $flow = $flowResponse->json();
            echo "Version: " . ($flow['version'] ?? 'unknown') . "\n";
            echo "Model: " . ($flow['model_choice']['model'] ?? 'unknown') . "\n";
            echo "Temperature: " . ($flow['model_temperature'] ?? 'unknown') . "\n";
            echo "Start Node: " . ($flow['start_node_id'] ?? 'unknown') . "\n\n";

            $prompt = $flow['global_prompt'] ?? '';
            echo "Global Prompt: " . strlen($prompt) . " chars\n\n";

            // Check for key services
            echo "Service Mentions in Prompt:\n";
            $checkServices = ['Hairdetox', 'Hair Detox', 'Herrenhaarschnitt',
                             'Balayage', 'Dauerwelle', 'Olaplex'];
            foreach ($checkServices as $serviceName) {
                $found = stripos($prompt, $serviceName) !== false ? '✅' : '❌';
                echo "  $found $serviceName\n";
            }

            echo "\n";

            // Check for important instructions
            echo "Critical Instructions:\n";
            $checkInstructions = [
                'check_availability_v17' => 'Backend function mentioned',
                'NIEMALS' => 'Never reject warning present',
                'VOLLSTÄNDIGE LISTE' => 'Complete list header present',
                'Synonyme' => 'Synonym section present'
            ];

            foreach ($checkInstructions as $pattern => $description) {
                $found = stripos($prompt, $pattern) !== false ? '✅' : '❌';
                echo "  $found $description\n";
            }
        }
    }
}

echo "\n";
echo str_repeat("=", 50) . "\n";
echo "✅ VERIFICATION COMPLETE\n";
echo str_repeat("=", 50) . "\n";
