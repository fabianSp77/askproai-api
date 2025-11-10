<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Services\Retell\RetellAgentManagementService;

// WIRKLICH RICHTIGER Friseur 1 Agent (aus DB: branches.retell_agent_id)
$agentId = 'agent_b36ecd3927a81834b6d56ab07b';
$apiKey = config('services.retellai.api_key');
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

echo "=== UPDATE WIRKLICH RICHTIGER FRISEUR 1 AGENT ===\n\n";
echo "Agent ID: $agentId (aus DB: Friseur 1 Zentrale)\n\n";

$service = new RetellAgentManagementService();

try {
    // Step 1: Get agent details
    echo "📥 Getting agent details...\n";
    $agent = $service->getAgentStatus($agentId);
    
    if (!$agent) {
        throw new Exception("Agent not found!");
    }
    
    echo "✅ Agent gefunden: " . ($agent['agent_name'] ?? 'Unknown') . "\n";
    echo "   Version: " . ($agent['version'] ?? 'unknown') . "\n";
    echo "   Type: " . ($agent['response_engine']['type'] ?? 'unknown') . "\n";
    
    $engineType = $agent['response_engine']['type'] ?? 'unknown';
    
    if ($engineType === 'conversation-flow') {
        echo "   Flow ID: " . ($agent['response_engine']['conversation_flow_id'] ?? 'N/A') . "\n\n";
        
        $conversationFlowId = $agent['response_engine']['conversation_flow_id'];
        
        // Step 2: Load current flow
        echo "📥 Loading conversation flow...\n";
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

        if (!$response->successful()) {
            throw new Exception("Failed to fetch flow: " . $response->body());
        }

        $flow = $response->json();
        echo "✅ Flow loaded (Version: " . ($flow['version'] ?? 'unknown') . ")\n\n";

        // Step 3: Get services
        echo "📊 Loading services...\n";
        $services = DB::table('services')
            ->where('company_id', 1)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'price', 'duration_minutes']);
        
        echo "✅ {$services->count()} services loaded\n\n";

        // Step 4: Build service list
        echo "✍️ Building service list...\n";
        $servicesList = "## Unsere Services (Friseur 1) - VOLLSTÄNDIGE LISTE\n\n";
        $servicesList .= "**WICHTIG:** Dies sind ALLE verfügbaren Dienstleistungen. Sage NIEMALS 'Wir bieten [X] nicht an', ohne vorher diese Liste geprüft oder check_availability_v17 aufgerufen zu haben!\n\n";
        $servicesList .= "### Alle verfügbaren Services:\n\n";

        foreach ($services as $service) {
            $servicesList .= sprintf(
                "- **%s** (%.2f EUR, %d Minuten)\n",
                $service->name,
                $service->price,
                $service->duration_minutes
            );
        }

        $servicesList .= "\n### Häufige Synonyme & Varianten:\n\n";
        $servicesList .= "- 'Hair Detox', 'Detox', 'Entgiftung' → **Hairdetox**\n";
        $servicesList .= "- 'Herrenschnitt', 'Männerhaarschnitt' → **Herrenhaarschnitt**\n";
        $servicesList .= "- 'Strähnchen', 'Highlights', 'Ombré', 'Balayage' → **Balayage/Ombré**\n";
        $servicesList .= "- 'Locken' → **Dauerwelle**\n";
        $servicesList .= "- 'Blondierung' → **Komplette Umfärbung (Blondierung)**\n";
        $servicesList .= "- 'Olaplex' → **Rebuild Treatment Olaplex**\n";
        $servicesList .= "\n**Bei Unsicherheit:** Nutze check_availability_v17 (Backend kennt ALLE Synonyme)\n\n";

        // Step 5: Update prompt
        $currentPrompt = $flow['global_prompt'];
        $pattern = '/## Unsere Services \(Friseur 1\).*?(?=## |$)/s';
        $updatedPrompt = preg_replace($pattern, $servicesList, $currentPrompt);

        if ($updatedPrompt === $currentPrompt) {
            $updatedPrompt = $currentPrompt . "\n\n" . $servicesList;
        }

        echo "✅ Prompt updated (" . strlen($currentPrompt) . " → " . strlen($updatedPrompt) . " chars)\n\n";

        // Step 6: Normalize tools
        $tools = $flow['tools'] ?? [];
        foreach ($tools as &$tool) {
            if (isset($tool['headers']) && is_array($tool['headers']) && empty($tool['headers'])) {
                $tool['headers'] = (object)[];
            }
            if (isset($tool['query_params']) && is_array($tool['query_params']) && empty($tool['query_params'])) {
                $tool['query_params'] = (object)[];
            }
            if (isset($tool['response_variables']) && is_array($tool['response_variables']) && empty($tool['response_variables'])) {
                $tool['response_variables'] = (object)[];
            }
        }

        // Step 7: Update flow
        echo "🚀 Updating conversation flow...\n";
        $updatePayload = [
            'global_prompt' => $updatedPrompt,
            'nodes' => $flow['nodes'],
            'tools' => $tools,
            'model_choice' => $flow['model_choice'] ?? ['type' => 'cascading', 'model' => 'gpt-4o-mini'],
            'model_temperature' => $flow['model_temperature'] ?? 0.3,
            'start_node_id' => $flow['start_node_id'] ?? 'func_00_initialize',
            'start_speaker' => $flow['start_speaker'] ?? 'agent',
            'begin_after_user_silence_ms' => $flow['begin_after_user_silence_ms'] ?? 800,
        ];

        $updateResponse = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->patch("{$baseUrl}/update-conversation-flow/{$conversationFlowId}", $updatePayload);

        if (!$updateResponse->successful()) {
            throw new Exception("Update failed: " . $updateResponse->body());
        }

        echo "✅ Flow updated!\n\n";

        // Step 8: Publish agent
        echo "🚀 Publishing agent...\n";
        $publishResponse = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->post("{$baseUrl}/publish-agent/{$agentId}");

        if ($publishResponse->successful()) {
            echo "✅ Agent published!\n\n";
        } else {
            echo "⚠️ Publish status: " . $publishResponse->status() . "\n\n";
        }

        // Verify
        echo "🔍 Verifying...\n";
        $verifyResponse = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

        if ($verifyResponse->successful()) {
            $verified = $verifyResponse->json();
            $verifiedPrompt = $verified['global_prompt'] ?? '';
            echo "   Hairdetox: " . (stripos($verifiedPrompt, 'Hairdetox') !== false ? '✅' : '❌') . "\n";
            echo "   Hair Detox: " . (stripos($verifiedPrompt, 'Hair Detox') !== false ? '✅' : '❌') . "\n";
            echo "   Balayage: " . (stripos($verifiedPrompt, 'Balayage') !== false ? '✅' : '❌') . "\n";
        }

        echo "\n🎉 DONE!\n";
        
    } elseif ($engineType === 'retell-llm') {
        echo "   LLM ID: " . ($agent['response_engine']['llm_id'] ?? 'N/A') . "\n\n";
        echo "⚠️ This is an LLM-based agent, updating LLM prompt...\n\n";
        
        // Get LLM
        $llmId = $agent['response_engine']['llm_id'];
        $llmData = $service->getLlmData($llmId);
        
        if (!$llmData) {
            throw new Exception("Could not fetch LLM data");
        }
        
        echo "✅ LLM loaded\n\n";
        
        // Build service list for LLM
        $services = DB::table('services')
            ->where('company_id', 1)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'price', 'duration_minutes']);
        
        $servicesList = "\n\n## VERFÜGBARE SERVICES (ALLE 18):\n\n";
        foreach ($services as $service) {
            $servicesList .= sprintf("- %s (%.2f EUR, %d Min)\n", $service->name, $service->price, $service->duration_minutes);
        }
        $servicesList .= "\nSYNONYME: Hair Detox → Hairdetox | Herrenschnitt → Herrenhaarschnitt | Strähnchen → Balayage/Ombré\n";
        $servicesList .= "WICHTIG: Nutze check_availability_v17 bei unklaren Service-Namen!\n";
        
        $currentPrompt = $llmData['general_prompt'] ?? '';
        $updatedPrompt = $currentPrompt . $servicesList;
        
        // Update LLM
        $result = $service->updateLlmData($llmId, $updatedPrompt, $llmData['general_tools'] ?? []);
        
        if ($result['success']) {
            echo "✅ LLM updated! New version: " . ($result['llm_version'] ?? 'unknown') . "\n";
            
            // Update agent to use new LLM version
            $service->updateAgentLlmVersion($agentId, $llmId, $result['llm_version']);
            echo "✅ Agent updated to use new LLM version\n";
        }
        
        echo "\n🎉 DONE!\n";
    } else {
        echo "⚠️ Unknown agent type: $engineType\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
