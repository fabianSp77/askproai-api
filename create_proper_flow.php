<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🎯 CREATE PROPER SIMPLE FLOW\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$flow = [
    'global_prompt' => "Du bist Carola, Terminassistentin bei Friseur 1. Sei freundlich und professionell. WICHTIG: Sobald du Service, Datum und Uhrzeit hast, CALL check_availability_v17 mit bestaetigung=false. Wenn verfügbar und Kunde will buchen, CALL check_availability_v17 mit bestaetigung=true.",
    
    'tools' => [
        [
            'type' => 'custom',
            'tool_id' => 'tool-check',
            'name' => 'check_availability_v17',
            'url' => 'https://api.askproai.de/api/retell/v17/check-availability',
            'description' => 'Prüft Verfügbarkeit und bucht Termine.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'datum' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                    'uhrzeit' => ['type' => 'string', 'description' => 'HH:MM'],
                    'dienstleistung' => ['type' => 'string'],
                    'bestaetigung' => ['type' => 'boolean', 'description' => 'false=check, true=book']
                ],
                'required' => ['datum', 'uhrzeit', 'dienstleistung', 'bestaetigung']
            ]
        ]
    ],
    
    'nodes' => [
        [
            'id' => 'start',
            'type' => 'start_node'
        ],
        [
            'id' => 'greet',
            'type' => 'speak_node',
            'speak' => 'Guten Tag bei Friseur 1, mein Name ist Carola. Wie kann ich Ihnen helfen?'
        ],
        [
            'id' => 'collect',
            'type' => 'collect_info_node',
            'collect_data' => [
                ['name' => 'dienstleistung', 'type' => 'string', 'description' => 'Service (Haarschnitt, Färben, etc.)', 'required' => true],
                ['name' => 'datum', 'type' => 'string', 'description' => 'Datum YYYY-MM-DD', 'required' => true],
                ['name' => 'uhrzeit', 'type' => 'string', 'description' => 'Uhrzeit HH:MM', 'required' => true]
            ]
        ],
        [
            'id' => 'check_avail',
            'type' => 'function',
            'tool_id' => 'tool-check',
            'wait_for_result' => true,
            'speak_during_execution' => true,
            'speak_after_execution' => false
        ]
    ],
    
    'edges' => [
        ['source' => 'start', 'destination' => 'greet'],
        ['source' => 'greet', 'destination' => 'collect'],
        ['source' => 'collect', 'destination' => 'check_avail']
    ]
];

echo "Creating flow with proper node types...\n";
echo "Tools: 1\n";
echo "Nodes: 4 (start → speak → collect → function)\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-conversation-flow', $flow);

echo "Status: {$response->status()}\n";

if ($response->successful()) {
    $data = $response->json();
    $flowId = $data['conversation_flow_id'];
    echo "✅ Flow created: $flowId\n\n";
    
    echo "Creating agent...\n";
    
    $agentResp = Http::withHeaders([
        'Authorization' => "Bearer $token",
        'Content-Type' => 'application/json'
    ])->post('https://api.retellai.com/create-agent', [
        'agent_name' => 'Friseur1 PROPER SIMPLE',
        'response_engine' => ['type' => 'conversation-flow', 'conversation_flow_id' => $flowId],
        'voice_id' => '11labs-Christopher',
        'language' => 'de-DE',
        'enable_backchannel' => true,
        'responsiveness' => 1.0,
        'interruption_sensitivity' => 1,
        'webhook_url' => 'https://api.askproai.de/api/webhooks/retell'
    ]);
    
    if ($agentResp->successful()) {
        $agent = $agentResp->json();
        echo "✅ Agent created: {$agent['agent_id']}\n\n";
        
        file_put_contents('proper_simple_agent_id.txt', $agent['agent_id']);
        file_put_contents('proper_simple_flow_id.txt', $flowId);
        
        echo "═══════════════════════════════════════════════════════════\n";
        echo "✅ ERFOLG!\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        echo "Agent ID: {$agent['agent_id']}\n";
        echo "Flow ID: $flowId\n\n";
        echo "Flow: start → speak → collect_info → function_call\n";
        echo "NO prompt-based transitions!\n\n";
    } else {
        echo "❌ Agent failed: {$agentResp->status()}\n";
        echo $agentResp->body() . "\n";
    }
} else {
    echo "❌ Flow failed\n";
    echo $response->body() . "\n";
}
