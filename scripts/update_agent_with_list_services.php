<?php

// Bootstrap Laravel
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$retellBase = config('services.retell.base_url') ?? 'https://api.retellai.com';
$retellToken = config('services.retell.api_key') ?? config('services.retell.token');
$agentId = 'agent_b36ecd3927a81834b6d56ab07b'; // AskProAI Agent V127

echo "╔════════════════════════════════════════════════════════╗\n";
echo "║ Updating Retell Agent with list_services Function     ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Read new prompt
$promptFile = __DIR__ . '/../retell_agent_prompt_v127_with_list_services.md';
if (!file_exists($promptFile)) {
    echo "❌ Prompt file not found: $promptFile\n";
    exit(1);
}

$newPrompt = file_get_contents($promptFile);

echo "Step 1: Reading new prompt... ✓\n\n";

// Define new functions with list_services
$functions = [
    [
        'name' => 'list_services',
        'description' => 'Get available services for this company. Shows all services with duration and price.',
        'parameters' => [
            'type' => 'object',
            'properties' => [],
            'required' => []
        ]
    ],
    [
        'name' => 'collect_appointment_data',
        'description' => 'Collect and verify appointment data. First call without bestaetigung to check availability, then call with bestaetigung: true to book.',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'call_id' => [
                    'type' => 'string',
                    'description' => 'Unique call identifier (use {{call_id}})'
                ],
                'service_id' => [
                    'type' => 'number',
                    'description' => 'The service ID from list_services (e.g., 32 or 47)'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Customer full name'
                ],
                'datum' => [
                    'type' => 'string',
                    'description' => 'Date in DD.MM.YYYY format (e.g., "23.10.2025")'
                ],
                'uhrzeit' => [
                    'type' => 'string',
                    'description' => 'Time in HH:MM format, 24-hour (e.g., "14:00")'
                ],
                'dienstleistung' => [
                    'type' => 'string',
                    'description' => 'Service name for reference'
                ],
                'bestaetigung' => [
                    'type' => 'boolean',
                    'description' => 'Set to false to check availability, true to confirm booking'
                ],
                'email' => [
                    'type' => 'string',
                    'description' => 'Customer email (optional)'
                ]
            ],
            'required' => ['call_id', 'service_id', 'name', 'datum', 'uhrzeit', 'dienstleistung']
        ]
    ],
    [
        'name' => 'cancel_appointment',
        'description' => 'Cancel an existing appointment',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'call_id' => ['type' => 'string'],
                'appointment_id' => ['type' => 'string']
            ],
            'required' => ['call_id', 'appointment_id']
        ]
    ],
    [
        'name' => 'reschedule_appointment',
        'description' => 'Reschedule an existing appointment to a new time',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'call_id' => ['type' => 'string'],
                'appointment_id' => ['type' => 'string'],
                'neues_datum' => ['type' => 'string'],
                'neue_uhrzeit' => ['type' => 'string']
            ],
            'required' => ['call_id', 'appointment_id', 'neues_datum', 'neue_uhrzeit']
        ]
    ]
];

echo "Step 2: Preparing function definitions...\n";
echo "  - list_services ✓\n";
echo "  - collect_appointment_data (updated with service_id) ✓\n";
echo "  - cancel_appointment ✓\n";
echo "  - reschedule_appointment ✓\n\n";

// Prepare update payload
$payload = [
    'agent_name' => 'AskProAI Agent V127 - Mit dynamischer Service-Auswahl',
    'agent_prompt' => $newPrompt,
    'language' => 'de',
    'functions' => $functions,
    'webhook_url' => config('app.url') . '/api/retell/webhook',
    'enable_analytic' => true
];

echo "Step 3: Sending update request to Retell API...\n";
echo "  Base URL: $retellBase\n";
echo "  Agent ID: $agentId\n\n";

// Update agent via Retell API
$response = Http::withHeaders([
    'Authorization' => "Bearer $retellToken",
    'Content-Type' => 'application/json'
])->patch("$retellBase/agent/$agentId", $payload);

if ($response->successful()) {
    echo "✅ Agent successfully updated!\n\n";

    $data = $response->json();
    echo "Updated Agent Details:\n";
    echo "  Name: " . ($data['agent_name'] ?? 'N/A') . "\n";
    echo "  ID: " . ($data['agent_id'] ?? $agentId) . "\n";
    echo "  Language: " . ($data['language'] ?? 'de') . "\n";
    echo "  Functions: " . count($data['functions'] ?? []) . "\n";
    echo "  Last Updated: " . date('Y-m-d H:i:s') . "\n\n";

    echo "╔════════════════════════════════════════════════════════╗\n";
    echo "║ ✅ AGENT SUCCESSFULLY UPDATED                         ║\n";
    echo "╚════════════════════════════════════════════════════════╝\n\n";

    echo "Changes:\n";
    echo "  ✅ Added list_services() function\n";
    echo "  ✅ Updated collect_appointment_data with service_id parameter\n";
    echo "  ✅ Updated agent prompt with new workflow\n\n";

    echo "Agent is now ready for service selection flow!\n";

} else {
    echo "❌ Failed to update agent!\n";
    echo "Status: " . $response->status() . "\n";
    echo "Error: " . $response->body() . "\n";
    exit(1);
}
