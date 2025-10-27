<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔧 CREATE MINIMAL SIMPLE FLOW\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$flow = [
    'global_prompt' => "# Friseur 1 - Terminassistent

Du bist Carola, die freundliche Terminassistentin von Friseur 1.

## WICHTIG - Function Calling
Sobald du Service, Datum und Uhrzeit vom Kunden hast:
1. CALL check_availability_v17 mit bestaetigung=false (nur prüfen)
2. Wenn verfügbar und Kunde bestätigt → CALL check_availability_v17 mit bestaetigung=true (buchen)

RATE NIEMALS ob ein Termin frei ist - IMMER check_availability_v17 callen!",
    
    'tools' => [
        [
            'tool_id' => 'tool-check-avail',
            'name' => 'check_availability_v17',
            'url' => 'https://api.askproai.de/api/retell/v17/check-availability',
            'description' => 'Prüft Verfügbarkeit und bucht Termine. bestaetigung=false prüft nur, bestaetigung=true bucht verbindlich.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'datum' => [
                        'type' => 'string',
                        'description' => 'YYYY-MM-DD'
                    ],
                    'uhrzeit' => [
                        'type' => 'string',
                        'description' => 'HH:MM'
                    ],
                    'dienstleistung' => [
                        'type' => 'string',
                        'description' => 'Service name'
                    ],
                    'bestaetigung' => [
                        'type' => 'boolean',
                        'description' => 'false=check, true=book'
                    ]
                ],
                'required' => ['datum', 'uhrzeit', 'dienstleistung', 'bestaetigung']
            ]
        ]
    ],
    
    'nodes' => [
        [
            'id' => 'start',
            'type' => 'conversation',
            'prompt' => 'Begrüße den Kunden als Carola von Friseur 1. Frage freundlich wie du helfen kannst.',
            'edges' => [
                [
                    'destination_node_id' => 'main',
                    'transition_condition' => [
                        'type' => 'always'
                    ]
                ]
            ]
        ],
        [
            'id' => 'main',
            'type' => 'conversation',
            'prompt' => 'Sammle Service, Datum und Uhrzeit. Sobald komplett, rufe check_availability_v17 auf.',
            'edges' => []
        ]
    ]
];

echo "Creating conversation flow...\n";
echo "Tools: " . count($flow['tools']) . "\n";
echo "Nodes: " . count($flow['nodes']) . "\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-conversation-flow', $flow);

if (!$response->successful()) {
    echo "❌ Failed: {$response->status()}\n";
    echo $response->body() . "\n";
    exit(1);
}

$flowData = $response->json();
$flowId = $flowData['conversation_flow_id'];

echo "✅ Flow created: $flowId\n\n";

// Create agent
echo "Creating agent...\n";

$agentConfig = [
    'agent_name' => 'Friseur1 MINIMAL (No Prompt Transitions)',
    'response_engine' => [
        'type' => 'conversation-flow',
        'conversation_flow_id' => $flowId
    ],
    'voice_id' => '11labs-Christopher',
    'language' => 'de-DE',
    'enable_backchannel' => true,
    'responsiveness' => 1.0,
    'interruption_sensitivity' => 1,
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
    echo "❌ Failed: {$response->status()}\n";
    echo $response->body() . "\n";
    exit(1);
}

$agent = $response->json();
$agentId = $agent['agent_id'];

echo "✅ Agent created: $agentId\n\n";

file_put_contents(__DIR__ . '/minimal_agent_id.txt', $agentId);
file_put_contents(__DIR__ . '/minimal_flow_id.txt', $flowId);

echo "═══════════════════════════════════════════════════════════\n";
echo "✅ SUCCESS!\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "Agent ID: $agentId\n";
echo "Flow ID: $flowId\n\n";
echo "This agent has minimal complexity - LLM decides when to call functions.\n";
echo "No prompt-based node transitions!\n\n";

