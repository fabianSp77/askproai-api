<?php

/**
 * AskPro AI - CORRECT Conversation Flow
 *
 * Nach Retell.ai Best Practices 2025:
 * 1. Global Prompt für Logik und Regeln
 * 2. Node Instructions KURZ und NATÜRLICH
 * 3. Keine technischen Begriffe in Instructions
 * 4. Static Text für feste Sätze
 * 5. Prompt nur für kurze, klare Anweisungen
 */

echo "=== BUILDING CORRECT CONVERSATION FLOW ===\n\n";

$flow = [
    'global_prompt' => <<<'PROMPT'
# AskPro AI Smart Booking Agent

## Deine Rolle
Du bist der intelligente Terminbuchungs-Assistent von Ask Pro AI.
Sprich natürlich, freundlich und effizient auf Deutsch.

## KRITISCHE Regel: Intent Recognition
Wenn der Kunde im ersten Satz bereits Informationen nennt (Name, Datum, Uhrzeit),
ERKENNE diese sofort und verwende sie. Frage NIEMALS nach Informationen die bereits genannt wurden!

Beispiel:
❌ FALSCH:
User: "Hans Schubert, Donnerstag 13 Uhr"
Du: "Darf ich Ihren Namen haben?"

✅ RICHTIG:
User: "Hans Schubert, Donnerstag 13 Uhr"
Du: "Gerne Herr Schubert! Für Donnerstag um 13 Uhr. Darf ich noch Ihre E-Mail?"

## Benötigte Informationen
Für eine Terminbuchung brauchst du:
1. Name (Vor- und Nachname)
2. E-Mail-Adresse
3. Wunschdatum (Wochentag oder konkretes Datum)
4. Wunschuhrzeit

## Datensammlung Strategie
- Sammle fehlende Informationen in natürlichem Gespräch
- Bestätige was der Kunde bereits gesagt hat
- Fasse mehrere Fragen zusammen wenn möglich (z.B. "Name und Email")
- Gehe direkt zur Verfügbarkeitsprüfung sobald du alle 4 Infos hast

## Function Calls
1. Verfügbarkeitsprüfung: Rufe collect_appointment_data mit bestaetigung=false auf
2. Buchung: Rufe collect_appointment_data mit bestaetigung=true auf
3. NIEMALS Verfügbarkeit erfinden - immer API-Result verwenden

## Ehrlichkeit
- Wenn API-Call failed: "Es gab ein technisches Problem"
- Wenn nicht verfügbar: "Dieser Termin ist leider nicht verfügbar"
- NIEMALS "gebucht" sagen bevor API-Call mit bestaetigung=true erfolgreich war
PROMPT
    ,

    'start_node_id' => 'node_greeting',
    'start_speaker' => 'agent',

    'model_choice' => [
        'type' => 'cascading',
        'model' => 'gpt-4o-mini'
    ],

    'model_temperature' => 0.3,

    'tools' => [],
    'nodes' => []
];

// ================================================================
// TOOLS
// ================================================================

$tools = [
    [
        'tool_id' => 'tool-collect-appointment',
        'name' => 'collect_appointment_data',
        'type' => 'custom',
        'description' => 'Check availability or book appointment at Ask Pro AI',
        'url' => 'https://api.askproai.de/api/retell/collect-appointment',
        'timeout_ms' => 10000,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'customer_name' => [
                    'type' => 'string',
                    'description' => 'Full name of customer'
                ],
                'customer_email' => [
                    'type' => 'string',
                    'description' => 'Email address'
                ],
                'customer_phone' => [
                    'type' => 'string',
                    'description' => 'Phone number (optional)'
                ],
                'preferred_date' => [
                    'type' => 'string',
                    'description' => 'Preferred date (weekday or specific date)'
                ],
                'preferred_time' => [
                    'type' => 'string',
                    'description' => 'Preferred time'
                ],
                'bestaetigung' => [
                    'type' => 'boolean',
                    'description' => 'false = check availability only, true = confirm booking'
                ]
            ],
            'required' => ['customer_name', 'customer_email', 'preferred_date', 'preferred_time', 'bestaetigung']
        ]
    ]
];

$flow['tools'] = $tools;

// ================================================================
// HELPER FUNCTIONS
// ================================================================

function node($id, $name, $type, $instruction, $edges = [], $extra = []) {
    $node = [
        'id' => $id,
        'name' => $name,
        'type' => $type,
        'instruction' => $instruction,
        'edges' => $edges
    ];

    if ($type === 'function') {
        $node['tool_type'] = $extra['tool_type'] ?? 'local';
        $node['tool_id'] = $extra['tool_id'];
        $node['wait_for_result'] = $extra['wait_for_result'] ?? true;
        $node['speak_during_execution'] = $extra['speak_during_execution'] ?? true;
    }

    return $node;
}

function prompt_edge($id, $destination, $prompt) {
    return [
        'id' => $id,
        'destination_node_id' => $destination,
        'transition_condition' => [
            'type' => 'prompt',
            'prompt' => $prompt
        ]
    ];
}

// ================================================================
// NODES - CORRECT & CLEAN
// ================================================================

$nodes = [];

// --- NODE 1: Greeting ---
$nodes[] = node(
    'node_greeting',
    'Begrüßung',
    'conversation',
    [
        'type' => 'static_text',
        'text' => 'Guten Tag bei Ask Pro AI. Wie kann ich Ihnen helfen?'
    ],
    [
        prompt_edge(
            'edge_greeting',
            'node_collect_info',
            'User wants to book appointment'
        )
    ]
);

// --- NODE 2: Collect Information (KURZ & KLAR!) ---
$nodes[] = node(
    'node_collect_info',
    'Informationen sammeln',
    'conversation',
    [
        'type' => 'prompt',
        'text' => 'Collect any missing information: customer name, email, preferred date, and preferred time. If customer already provided some information, acknowledge it and only ask for what is missing.'
    ],
    [
        prompt_edge(
            'edge_info_collected',
            'func_check_availability',
            'All required information collected'
        )
    ]
);

// --- NODE 3: Check Availability (NUR für sprechen!) ---
$nodes[] = node(
    'func_check_availability',
    'Verfügbarkeit prüfen',
    'function',
    [
        'type' => 'static_text',
        'text' => 'Einen Moment bitte, ich prüfe die Verfügbarkeit.'
    ],
    [
        prompt_edge(
            'edge_check_done',
            'node_confirm',
            'Availability check completed'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-collect-appointment',
        'speak_during_execution' => true,
        'wait_for_result' => true
    ]
);

// --- NODE 4: Confirm Booking (KURZ!) ---
$nodes[] = node(
    'node_confirm',
    'Buchung bestätigen',
    'conversation',
    [
        'type' => 'prompt',
        'text' => 'Based on availability check result, either confirm the appointment is available and ask if customer wants to book it, or inform that the time is not available and offer alternatives.'
    ],
    [
        prompt_edge(
            'edge_confirm_yes',
            'func_book',
            'Customer confirms booking'
        ),
        prompt_edge(
            'edge_confirm_no',
            'node_cancel',
            'Customer declines or wants different time'
        )
    ]
);

// --- NODE 5: Book Appointment (NUR für sprechen!) ---
$nodes[] = node(
    'func_book',
    'Termin buchen',
    'function',
    [
        'type' => 'static_text',
        'text' => 'Einen Moment bitte, ich buche den Termin.'
    ],
    [
        prompt_edge(
            'edge_book_done',
            'node_success',
            'Booking completed'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-collect-appointment',
        'speak_during_execution' => true,
        'wait_for_result' => true
    ]
);

// --- NODE 6: Success ---
$nodes[] = node(
    'node_success',
    'Erfolg',
    'end',
    [
        'type' => 'static_text',
        'text' => 'Wunderbar! Ihr Termin ist gebucht. Sie erhalten eine Bestätigung per E-Mail.'
    ],
    []
);

// --- NODE 7: Cancel ---
$nodes[] = node(
    'node_cancel',
    'Abbruch',
    'end',
    [
        'type' => 'static_text',
        'text' => 'Kein Problem! Falls Sie doch noch einen Termin möchten, rufen Sie gerne wieder an. Auf Wiederhören!'
    ],
    []
);

$flow['nodes'] = $nodes;

// ================================================================
// VALIDATION & SAVE
// ================================================================

$output_file = '/var/www/api-gateway/public/askproai_conversation_flow_correct.json';
$json = json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($json === false) {
    die("ERROR: JSON encoding failed: " . json_last_error_msg() . "\n");
}

// Validate
echo "Validating flow...\n";
$data = json_decode($json, true);
$errors = [];

foreach ($data['nodes'] as $node) {
    $instr = $node['instruction']['text'];

    // Check for technical terms that shouldn't be in instructions
    if (preg_match('/(WICHTIG|STRATEGIE|BEISPIELE|PFLICHT|JETZT rufe|Extrahiere|WENN|→)/i', $instr)) {
        $errors[] = "{$node['id']}: instruction contains technical terms/commands";
    }

    // Check instruction length (should be short!)
    if (strlen($instr) > 300) {
        $errors[] = "{$node['id']}: instruction too long (" . strlen($instr) . " chars)";
    }

    if ($node['type'] === 'function') {
        if (!isset($node['tool_id'])) $errors[] = "{$node['id']}: missing tool_id";
        if (!isset($node['tool_type'])) $errors[] = "{$node['id']}: missing tool_type";
    }
}

if (!empty($errors)) {
    echo "❌ VALIDATION ERRORS:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    exit(1);
}

file_put_contents($output_file, $json);

echo "✅ SUCCESS!\n";
echo "Output: $output_file\n";
echo "Size: " . round(strlen($json) / 1024, 2) . " KB\n";
echo "Nodes: " . count($flow['nodes']) . "\n";
echo "Tools: " . count($flow['tools']) . "\n\n";

echo "=== CORRECT FLOW ARCHITECTURE ===\n";
echo "1. Begrüßung (static text)\n";
echo "2. Informationen sammeln (short prompt)\n";
echo "3. Verfügbarkeit prüfen (static text for speaking)\n";
echo "4. Bestätigung (short prompt)\n";
echo "5. Termin buchen (static text for speaking)\n";
echo "6. Erfolg (static text)\n\n";

echo "=== IMPROVEMENTS ===\n";
echo "1. ✅ KURZE Instructions (keine langen technischen Texte)\n";
echo "2. ✅ Logik im Global Prompt (nicht in node instructions)\n";
echo "3. ✅ Static Text für feste Sätze\n";
echo "4. ✅ Prompt nur für kurze, klare Anweisungen\n";
echo "5. ✅ Keine technischen Begriffe mehr\n\n";

echo "Ready to deploy!\n";
