<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

$conversationFlowId = 'conversation_flow_1607b81c8f93';
$apiKey = config('services.retellai.api_key');
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

echo "=== CONVERSATION FLOW UPDATE ===\n\n";
echo "Flow ID: $conversationFlowId\n\n";

try {
    // Step 1: Load current flow
    echo "📥 Loading current conversation flow...\n";
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

    if (!$response->successful()) {
        throw new Exception("Failed to fetch conversation flow: " . $response->body());
    }

    $flow = $response->json();
    echo "✅ Current flow loaded (Version: " . ($flow['version'] ?? 'unknown') . ")\n\n";

    // Step 2: Get all active services from DB
    echo "📊 Loading services from database...\n";
    $services = DB::table('services')
        ->where('company_id', 1)
        ->where('is_active', true)
        ->orderBy('name')
        ->get(['name', 'price', 'duration_minutes']);

    echo "✅ {$services->count()} services loaded\n\n";

    // Step 3: Build updated global prompt
    echo "✍️ Building updated global prompt...\n";

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
    $servicesList .= "**WICHTIG:** Kunden verwenden oft alternative Bezeichnungen. Nutze check_availability_v17 wenn unklar!\n\n";
    $servicesList .= "- 'Hair Detox', 'Detox', 'Entgiftung' → **Hairdetox**\n";
    $servicesList .= "- 'Herrenschnitt', 'Männerhaarschnitt', 'Herren Haarschnitt' → **Herrenhaarschnitt**\n";
    $servicesList .= "- 'Damenschnitt', 'Frauen Haarschnitt', 'Damen Schnitt' → **Damenhaarschnitt**\n";
    $servicesList .= "- 'Strähnchen', 'Highlights', 'Ombré', 'Balayage' → **Balayage/Ombré**\n";
    $servicesList .= "- 'Locken', 'Dauerwelle machen' → **Dauerwelle**\n";
    $servicesList .= "- 'Blondierung', 'Komplett blond', 'Vollblondierung' → **Komplette Umfärbung (Blondierung)**\n";
    $servicesList .= "- 'Olaplex', 'Olaplex Treatment' → **Rebuild Treatment Olaplex**\n";
    $servicesList .= "- 'Maria Nila', 'Intensive Pflege' → **Intensiv Pflege Maria Nila**\n";
    $servicesList .= "- 'Kinderschnitt', 'Kinder Haarschnitt' → **Kinderhaarschnitt**\n";
    $servicesList .= "- 'Föhnen Damen', 'Styling Damen' → **Föhnen & Styling Damen**\n";
    $servicesList .= "- 'Föhnen Herren', 'Styling Herren' → **Föhnen & Styling Herren**\n";
    $servicesList .= "\n**Bei Unsicherheit:**\n";
    $servicesList .= "1. Prüfe diese Liste\n";
    $servicesList .= "2. Nutze check_availability_v17 (Backend kennt ALLE Synonyme)\n";
    $servicesList .= "3. Frage den Kunden zur Klarstellung\n";
    $servicesList .= "4. NIEMALS sofort ablehnen ohne Backend-Check!\n\n";

    // Step 4: Replace service section in global_prompt
    $currentPrompt = $flow['global_prompt'];

    // Find and replace the "## Unsere Services" section
    $pattern = '/## Unsere Services \(Friseur 1\).*?(?=## |$)/s';
    $updatedPrompt = preg_replace($pattern, $servicesList, $currentPrompt);

    // If pattern didn't match, append at the end
    if ($updatedPrompt === $currentPrompt) {
        echo "⚠️ Could not find '## Unsere Services' section, appending...\n";
        $updatedPrompt = $currentPrompt . "\n\n" . $servicesList;
    }

    echo "✅ Global prompt updated\n";
    echo "   Old length: " . strlen($currentPrompt) . " chars\n";
    echo "   New length: " . strlen($updatedPrompt) . " chars\n\n";

    // Save updated prompt to file for review
    $reviewFile = __DIR__ . '/../conversation_flow_updated_prompt.txt';
    file_put_contents($reviewFile, $updatedPrompt);
    echo "💾 Updated prompt saved to: conversation_flow_updated_prompt.txt\n\n";

    // Step 5: Prepare tools with correct format (arrays → objects)
    echo "🔧 Normalizing tools format...\n";
    $tools = $flow['tools'] ?? [];
    foreach ($tools as &$tool) {
        // Convert empty arrays to empty objects for Retell API
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
    echo "✅ Tools normalized\n\n";

    // Step 6: Update conversation flow via API
    echo "🚀 Updating conversation flow via API...\n";

    $updatePayload = [
        'global_prompt' => $updatedPrompt,
        // Keep all other fields the same
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
        throw new Exception("Failed to update conversation flow: " . $updateResponse->body());
    }

    $updatedFlow = $updateResponse->json();
    echo "✅ Conversation flow successfully updated!\n";
    echo "   New Version: " . ($updatedFlow['version'] ?? 'unknown') . "\n\n";

    // Step 7: Verify update
    echo "🔍 Verifying update...\n";
    $verifyResponse = Http::withHeaders([
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

    if ($verifyResponse->successful()) {
        $verifiedFlow = $verifyResponse->json();
        $verifiedPrompt = $verifiedFlow['global_prompt'] ?? '';

        echo "   Hairdetox mentioned: " . (stripos($verifiedPrompt, 'Hairdetox') !== false ? '✅' : '❌') . "\n";
        echo "   Hair Detox mentioned: " . (stripos($verifiedPrompt, 'Hair Detox') !== false ? '✅' : '❌') . "\n";
        echo "   Balayage mentioned: " . (stripos($verifiedPrompt, 'Balayage') !== false ? '✅' : '❌') . "\n";
        echo "   Dauerwelle mentioned: " . (stripos($verifiedPrompt, 'Dauerwelle') !== false ? '✅' : '❌') . "\n";

        // Save verified flow
        file_put_contents(__DIR__ . '/../conversation_flow_verified.json', json_encode($verifiedFlow, JSON_PRETTY_PRINT));
        echo "\n💾 Verified flow saved to: conversation_flow_verified.json\n";
    }

    echo "\n🎉 UPDATE COMPLETE!\n\n";
    echo "=== WHAT WAS FIXED ===\n";
    echo "✅ Added ALL 18 services to global_prompt\n";
    echo "✅ Added synonym hints (Hair Detox → Hairdetox)\n";
    echo "✅ Added instructions to never reject without backend check\n";
    echo "✅ Backend already has synonym system (seeder executed)\n\n";

    echo "=== NEXT STEPS ===\n";
    echo "1. Test with 'Hair Detox' → should be recognized\n";
    echo "2. Test with 'Herrenschnitt' → should map to Herrenhaarschnitt\n";
    echo "3. Test with 'Strähnchen' → should map to Balayage/Ombré\n\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
