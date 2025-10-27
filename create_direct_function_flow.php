<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔧 CREATE SIMPLE DIRECT-CALL FLOW\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Strategy: Create flow where AI directly calls functions based on conversation context\n";
echo "No complex transitions - just let LLM decide when to call functions\n\n";

$flow = [
    'global_prompt' => "# Friseur 1 - Terminassistent

Du bist Carola, die freundliche Terminassistentin von Friseur 1.

## Deine Aufgaben
1. Begrüße den Kunden freundlich
2. Frage nach dem gewünschten Service (Haarschnitt, Färben, etc.)
3. Frage nach Wunschtermin (Datum und Uhrzeit)
4. SOBALD du Service, Datum und Uhrzeit hast → CALL check_availability_v17 mit bestaetigung=false
5. Wenn Termin verfügbar → Frage Kunde ob er buchen möchte
6. Wenn Kunde \"Ja\" sagt → CALL check_availability_v17 NOCHMAL mit bestaetigung=true

## WICHTIG
- Du MUSST check_availability_v17 aufrufen um Verfügbarkeit zu prüfen
- RATE NIEMALS ob ein Termin frei ist - IMMER die Function callen
- Erst prüfen (bestaetigung=false), dann buchen (bestaetigung=true)",
    
    'tools' => [
        [
            'tool_id' => 'tool-check-avail',
            'name' => 'check_availability_v17',
            'url' => 'https://api.askproai.de/api/retell/v17/check-availability',
            'method' => 'POST',
            'speak_during_execution' => true,
            'wait_for_result' => true,
            'description' => 'Prüft Verfügbarkeit eines Termins und bucht ihn optional. Verwende bestaetigung=false zum Prüfen, bestaetigung=true zum Buchen.',
            'parameters' => (object)[
                'type' => 'object',
                'properties' => (object)[
                    'datum' => (object)[
                        'type' => 'string',
                        'description' => 'Datum im Format YYYY-MM-DD (z.B. 2025-10-25)'
                    ],
                    'uhrzeit' => (object)[
                        'type' => 'string',
                        'description' => 'Uhrzeit im Format HH:MM (z.B. 09:00)'
                    ],
                    'dienstleistung' => (object)[
                        'type' => 'string',
                        'description' => 'Name der Dienstleistung (z.B. Haarschnitt, Färben)'
                    ],
                    'bestaetigung' => (object)[
                        'type' => 'boolean',
                        'description' => 'false = nur Verfügbarkeit prüfen, true = Termin verbindlich buchen'
                    ]
                ],
                'required' => ['datum', 'uhrzeit', 'dienstleistung', 'bestaetigung']
            ]
        ],
        [
            'tool_id' => 'tool-get-appts',
            'name' => 'get_customer_appointments',
            'url' => 'https://api.askproai.de/api/retell/get-customer-appointments',
            'method' => 'POST',
            'speak_during_execution' => false,
            'wait_for_result' => true,
            'description' => 'Ruft bestehende Termine eines Kunden ab',
            'parameters' => (object)[
                'type' => 'object',
                'properties' => (object)[],
                'required' => []
            ]
        ]
    ],
    
    'nodes' => [
        [
            'id' => 'start',
            'type' => 'conversation',
            'prompt' => 'Begrüße den Kunden freundlich. Stelle dich als Carola vor und frage wie du helfen kannst.',
            'edges' => [
                [
                    'destination_node_id' => 'main_conversation',
                    'transition_condition' => (object)[
                        'type' => 'always'
                    ]
                ]
            ]
        ],
        [
            'id' => 'main_conversation',
            'type' => 'conversation',
            'prompt' => 'Führe das Gespräch. Sammle Service, Datum und Uhrzeit. Sobald du alle Informationen hast, rufe check_availability_v17 auf. Nach der Prüfung, frage ob der Kunde buchen möchte.',
            'edges' => []
        ]
    ]
];

// Step 1: Create conversation flow
echo "Creating conversation flow...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-conversation-flow', $flow);

if (!$response->successful()) {
    echo "❌ Failed to create flow: {$response->status()}\n";
    echo $response->body() . "\n";
    exit(1);
}

$flowData = $response->json();
$flowId = $flowData['conversation_flow_id'];

echo "✅ Flow created: $flowId\n\n";

// Step 2: Create agent with this flow
echo "Creating agent...\n";

$agentConfig = [
    'agent_name' => 'Friseur1 AI SIMPLE (Direct Function Calls)',
    'response_engine' => [
        'type' => 'conversation-flow',
        'conversation_flow_id' => $flowId
    ],
    'voice_id' => '11labs-Christopher',
    'language' => 'de-DE',
    'enable_backchannel' => true,
    'responsiveness' => 1.0,
    'interruption_sensitivity' => 1,
    'enable_transcription_formatting' => false,
    'reminder_trigger_ms' => 10000,
    'reminder_max_count' => 2,
    'max_call_duration_ms' => 1800000,
    'end_call_after_silence_ms' => 60000,
    'webhook_url' => 'https://api.askproai.de/api/webhooks/retell'
];

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-agent', $agentConfig);

if (!$response->successful()) {
    echo "❌ Failed to create agent: {$response->status()}\n";
    echo $response->body() . "\n";
    exit(1);
}

$agent = $response->json();
$agentId = $agent['agent_id'];

echo "✅ Agent created: $agentId\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "✅ SUCCESS! Simple Direct-Call Flow Agent Created\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Agent ID: $agentId\n";
echo "Flow ID: $flowId\n";
echo "Tools: " . count($flow['tools']) . "\n";
echo "Nodes: " . count($flow['nodes']) . "\n\n";

echo "This agent has NO prompt-based transitions.\n";
echo "The LLM decides directly when to call functions based on the global_prompt.\n\n";

// Save agent ID
file_put_contents(__DIR__ . '/simple_agent_id.txt', $agentId);
echo "Agent ID saved to: simple_agent_id.txt\n\n";

