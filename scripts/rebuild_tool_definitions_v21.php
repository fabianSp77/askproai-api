<?php

/**
 * Rebuild Tool Definitions with Correct Parameters
 *
 * Root Cause: Tool definitions are corrupt - parameters show as "ERROR", "Array", "unknown"
 * Solution: Completely rebuild tool definitions with proper structure
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';

echo "🔧 REBUILDING TOOL DEFINITIONS\n";
echo str_repeat('=', 80) . "\n\n";

// Step 1: Get current flow
echo "1️⃣  Fetching current flow...\n";
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$flow = json_decode($flowResponse, true);
echo "   ✅ Current Flow Version: V{$flow['version']}\n\n";

// Step 2: Rebuild tool definitions
echo "2️⃣  Rebuilding tool definitions...\n";

// Define correct tool structures
$correctTools = [
    [
        'type' => 'custom',
        'name' => 'check_availability_v17',
        'description' => 'Prüft Verfügbarkeit für einen Termin bei Friseur1',
        'url' => 'https://api.askproai.de/api/webhooks/retell/function',
        'speak_during_execution' => true,
        'speak_after_execution' => true,
        'parameters' => [
            [
                'name' => 'name',
                'type' => 'string',
                'description' => 'Kundenname (Vor- und Nachname)',
                'required' => true
            ],
            [
                'name' => 'datum',
                'type' => 'string',
                'description' => 'Datum im Format DD.MM.YYYY oder relativ (morgen, heute, nächste Woche)',
                'required' => true
            ],
            [
                'name' => 'dienstleistung',
                'type' => 'string',
                'description' => 'Name der gewünschten Dienstleistung',
                'required' => true
            ],
            [
                'name' => 'uhrzeit',
                'type' => 'string',
                'description' => 'Uhrzeit im Format HH:MM',
                'required' => true
            ],
            [
                'name' => 'call_id',
                'type' => 'string',
                'description' => 'Retell Call ID für Kontext und Logging',
                'required' => true
            ]
        ]
    ],
    [
        'type' => 'custom',
        'name' => 'book_appointment_v17',
        'description' => 'Bucht einen Termin bei Friseur1',
        'url' => 'https://api.askproai.de/api/webhooks/retell/function',
        'speak_during_execution' => true,
        'speak_after_execution' => true,
        'parameters' => [
            [
                'name' => 'name',
                'type' => 'string',
                'description' => 'Kundenname (Vor- und Nachname)',
                'required' => true
            ],
            [
                'name' => 'datum',
                'type' => 'string',
                'description' => 'Datum im Format DD.MM.YYYY',
                'required' => true
            ],
            [
                'name' => 'dienstleistung',
                'type' => 'string',
                'description' => 'Name der gewünschten Dienstleistung',
                'required' => true
            ],
            [
                'name' => 'uhrzeit',
                'type' => 'string',
                'description' => 'Uhrzeit im Format HH:MM',
                'required' => true
            ],
            [
                'name' => 'call_id',
                'type' => 'string',
                'description' => 'Retell Call ID für Kontext und Logging',
                'required' => true
            ]
        ]
    ]
];

// Replace tool definitions
$toolsToKeep = [];
foreach ($flow['tools'] as $tool) {
    // Keep tools that are not being rebuilt
    if (!in_array($tool['name'], ['check_availability_v17', 'book_appointment_v17'])) {
        $toolsToKeep[] = $tool;
    }
}

// Add rebuilt tools
$flow['tools'] = array_merge($correctTools, $toolsToKeep);

echo "   ✅ Rebuilt 2 tool definitions with correct parameters\n\n";

// Step 3: Update flow
echo "3️⃣  Updating flow...\n";

$updatePayload = json_encode($flow);

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_POSTFIELDS, $updatePayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$updateResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $updated = json_decode($updateResponse, true);
    echo "   ✅ Flow updated to V{$updated['version']}\n\n";

    // Step 4: Verify
    echo "4️⃣  Verifying tool definitions...\n";
    $ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);
    $verifyResponse = curl_exec($ch);
    curl_close($ch);

    $verify = json_decode($verifyResponse, true);

    foreach ($verify['tools'] as $tool) {
        if (in_array($tool['name'], ['check_availability_v17', 'book_appointment_v17'])) {
            echo "   Tool: {$tool['name']}\n";

            $hasCallId = false;
            $paramCount = 0;

            if (isset($tool['parameters']) && is_array($tool['parameters'])) {
                foreach ($tool['parameters'] as $param) {
                    if (is_array($param) && isset($param['name'])) {
                        $paramCount++;
                        if ($param['name'] === 'call_id') {
                            $hasCallId = true;
                            echo "     ✅ call_id: {$param['description']}\n";
                        }
                    }
                }
            }

            echo "     Total parameters: {$paramCount}\n";

            if (!$hasCallId) {
                echo "     ❌ call_id still missing!\n";
            }

            echo "\n";
        }
    }

    echo str_repeat('=', 80) . "\n";
    echo "✅ SUCCESS! Tool definitions rebuilt!\n\n";
    echo "Next Steps:\n";
    echo "1. Publish as V22\n";
    echo "2. Run test call\n";
    echo "3. Verify call_id is passed correctly\n";

} else {
    echo "   ❌ Update failed! HTTP {$httpCode}\n";
    echo "   Response: {$updateResponse}\n";
}
