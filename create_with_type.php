<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

$flow = [
    'global_prompt' => "Du bist Carola von Friseur 1. Sobald du Service, Datum und Uhrzeit hast, CALL check_availability_v17 mit bestaetigung=false zum Prüfen, dann mit bestaetigung=true zum Buchen. NIEMALS raten - IMMER die Function callen!",
    
    'tools' => [
        [
            'type' => 'custom',
            'tool_id' => 'tool-check-avail',
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
            'type' => 'conversation',
            'prompt' => 'Begrüße als Carola von Friseur 1.',
            'edges' => [
                ['destination_node_id' => 'main', 'transition_condition' => ['type' => 'always']]
            ]
        ],
        [
            'id' => 'main',
            'type' => 'conversation',
            'prompt' => 'Sammle Service, Datum, Uhrzeit. Call check_availability_v17.',
            'edges' => []
        ]
    ]
];

echo "Creating flow...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-conversation-flow', $flow);

echo "Status: {$response->status()}\n";

if ($response->successful()) {
    $data = $response->json();
    $flowId = $data['conversation_flow_id'];
    echo "✅ Flow: $flowId\n\n";
    
    // Create agent
    $agent = Http::withHeaders([
        'Authorization' => "Bearer $token",
        'Content-Type' => 'application/json'
    ])->post('https://api.retellai.com/create-agent', [
        'agent_name' => 'Friseur1 SIMPLE V2',
        'response_engine' => ['type' => 'conversation-flow', 'conversation_flow_id' => $flowId],
        'voice_id' => '11labs-Christopher',
        'language' => 'de-DE',
        'webhook_url' => 'https://api.askproai.de/api/webhooks/retell'
    ])->json();
    
    echo "✅ Agent: {$agent['agent_id']}\n";
    file_put_contents('simple_v2_agent_id.txt', $agent['agent_id']);
} else {
    echo "❌ {$response->body()}\n";
}
