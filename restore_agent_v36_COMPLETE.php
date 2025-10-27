<?php

/**
 * RESTORE AGENT V36 - COMPLETE CONFIGURATION
 *
 * CRITICAL FIX: V35/V36 deployments deleted all webhooks and tools!
 * This script restores the complete agent configuration.
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$baseUrl = 'https://api.askproai.de';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║   🚨 AGENT V36 COMPLETE RESTORE                            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

// Get current agent
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);

echo "Current Agent Version: " . ($agent['version'] ?? 'N/A') . "\n";
echo "Current Flow ID: " . ($agent['response_engine']['conversation_flow_id'] ?? 'N/A') . "\n\n";

// Build complete agent configuration
$agentConfig = [
    // Keep existing settings
    'agent_name' => $agent['agent_name'] ?? 'Friseur 1 AI Assistant',
    'voice_id' => $agent['voice_id'] ?? 'openai-Fiona',
    'language' => $agent['language'] ?? 'de-DE',
    'response_engine' => $agent['response_engine'], // Keep conversation flow

    // RESTORE WEBHOOKS
    'call_start_webhook_url' => "{$baseUrl}/api/webhooks/retell",
    'call_end_webhook_url' => "{$baseUrl}/api/webhooks/retell",
    'call_analyzed_webhook_url' => "{$baseUrl}/api/webhooks/retell",

    // RESTORE TOOLS/FUNCTIONS
    'tools' => [
        [
            'type' => 'custom',
            'name' => 'initialize_call',
            'description' => 'Initialize the call and identify the customer',
            'url' => "{$baseUrl}/api/webhooks/retell/function",
            'speak_during_execution' => false,
            'speak_after_execution' => false,
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'phone_number' => [
                        'type' => 'string',
                        'description' => 'Customer phone number'
                    ]
                ],
                'required' => []
            ]
        ],
        [
            'type' => 'custom',
            'name' => 'check_availability_v17',
            'description' => 'Check appointment availability for a specific date, time and service',
            'url' => "{$baseUrl}/api/webhooks/retell/function",
            'speak_during_execution' => true,
            'speak_after_execution' => false,
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'description' => 'Customer name'
                    ],
                    'datum' => [
                        'type' => 'string',
                        'description' => 'Appointment date in DD.MM.YYYY format (e.g., 24.10.2025)'
                    ],
                    'uhrzeit' => [
                        'type' => 'string',
                        'description' => 'Appointment time in HH:MM format (e.g., 10:00)'
                    ],
                    'dienstleistung' => [
                        'type' => 'string',
                        'description' => 'Service type (e.g., Herrenhaarschnitt, Damenhaarschnitt)'
                    ],
                    'bestaetigung' => [
                        'type' => 'boolean',
                        'description' => 'Whether this is a confirmation (false for initial check)'
                    ]
                ],
                'required' => ['datum', 'uhrzeit', 'dienstleistung']
            ]
        ],
        [
            'type' => 'custom',
            'name' => 'book_appointment_v17',
            'description' => 'Book a confirmed appointment',
            'url' => "{$baseUrl}/api/webhooks/retell/function",
            'speak_during_execution' => true,
            'speak_after_execution' => false,
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'description' => 'Customer name'
                    ],
                    'datum' => [
                        'type' => 'string',
                        'description' => 'Appointment date in DD.MM.YYYY format'
                    ],
                    'uhrzeit' => [
                        'type' => 'string',
                        'description' => 'Appointment time in HH:MM format'
                    ],
                    'dienstleistung' => [
                        'type' => 'string',
                        'description' => 'Service type'
                    ],
                    'telefonnummer' => [
                        'type' => 'string',
                        'description' => 'Customer phone number'
                    ]
                ],
                'required' => ['name', 'datum', 'uhrzeit', 'dienstleistung']
            ]
        ],
        [
            'type' => 'custom',
            'name' => 'get_alternatives',
            'description' => 'Get alternative appointment slots if requested time is not available',
            'url' => "{$baseUrl}/api/webhooks/retell/function",
            'speak_during_execution' => false,
            'speak_after_execution' => false,
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'datum' => [
                        'type' => 'string',
                        'description' => 'Requested date'
                    ],
                    'uhrzeit' => [
                        'type' => 'string',
                        'description' => 'Requested time'
                    ],
                    'dienstleistung' => [
                        'type' => 'string',
                        'description' => 'Service type'
                    ]
                ],
                'required' => ['datum', 'dienstleistung']
            ]
        ]
    ]
];

echo "=== STEP 1: Updating Agent Configuration ===\n";
echo "Webhooks:\n";
echo "  - call_start: {$baseUrl}/api/webhooks/retell\n";
echo "  - call_end: {$baseUrl}/api/webhooks/retell\n";
echo "  - call_analyzed: {$baseUrl}/api/webhooks/retell\n";
echo "Tools: " . count($agentConfig['tools']) . " functions\n";
foreach ($agentConfig['tools'] as $tool) {
    echo "  - " . $tool['name'] . "\n";
}
echo "\n";

// Update agent
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/update-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($agentConfig)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 201) {
    echo "❌ Agent update failed: HTTP {$httpCode}\n";
    echo "Response: " . substr($response, 0, 2000) . "\n";
    exit(1);
}

echo "✅ Agent updated: HTTP {$httpCode}\n\n";

sleep(2);

// Publish agent
echo "=== STEP 2: Publishing Agent ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/publish-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 201) {
    echo "❌ Publish failed: HTTP {$httpCode}\n";
    exit(1);
}

echo "✅ Agent published!\n\n";

sleep(2);

// Verify
echo "=== STEP 3: Verification ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$response = curl_exec($ch);
curl_close($ch);

$verifyAgent = json_decode($response, true);

$webhooksOk = (
    isset($verifyAgent['call_start_webhook_url']) &&
    isset($verifyAgent['call_end_webhook_url']) &&
    isset($verifyAgent['call_analyzed_webhook_url'])
);

$toolsOk = (
    isset($verifyAgent['tools']) &&
    count($verifyAgent['tools']) >= 4
);

echo "Agent Version: " . ($verifyAgent['version'] ?? 'N/A') . "\n";
echo "Webhooks: " . ($webhooksOk ? "✅ CONFIGURED" : "❌ MISSING") . "\n";
echo "Tools: " . ($toolsOk ? "✅ " . count($verifyAgent['tools']) . " functions" : "❌ MISSING") . "\n\n";

if ($webhooksOk && $toolsOk) {
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║         🎉 AGENT FULLY RESTORED! 🎉                        ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";

    echo "📋 RESTORED:\n";
    echo "  ✅ call_start_webhook_url\n";
    echo "  ✅ call_end_webhook_url\n";
    echo "  ✅ call_analyzed_webhook_url\n";
    echo "  ✅ initialize_call function\n";
    echo "  ✅ check_availability_v17 function\n";
    echo "  ✅ book_appointment_v17 function\n";
    echo "  ✅ get_alternatives function\n\n";

    echo "🧪 READY TO TEST:\n";
    echo "  Call: +493033081738\n";
    echo "  Webhooks will now fire!\n";
    echo "  Functions will be called!\n";
    echo "  Calls will appear in Admin Panel!\n\n";
} else {
    echo "⚠️  Verification incomplete - please check manually\n";
}
