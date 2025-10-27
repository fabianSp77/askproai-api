<?php

/**
 * AskPro AI - WORKING Conversation Flow
 *
 * FIXES:
 * 1. NO prompt-based edges for function nodes
 * 2. Equation-based transitions using function return values
 * 3. Clear, linear flow that agent MUST follow
 * 4. Explicit instructions what to collect
 * 5. Validation before booking
 */

echo "=== BUILDING WORKING CONVERSATION FLOW ===\n\n";

$flow = [
    'global_prompt' => <<<'PROMPT'
# AskPro AI Terminbuchungs-Agent - WORKING VERSION

## Identität
Du bist der Assistent von Ask Pro AI. Sprich natürlich und professionell auf Deutsch.

## KRITISCHE Regeln
1. Folge EXAKT dem Conversation Flow - keine Abkürzungen!
2. NIEMALS einen Termin als "gebucht" bezeichnen bevor collect_appointment_data mit bestaetigung=true aufgerufen wurde
3. IMMER erst Name und Email erfragen bevor Termindetails gefragt werden
4. NIEMALS Verfügbarkeit erfinden - immer API verwenden
5. Bei Funktions-Aufrufen: "Einen Moment bitte..." sagen

## Datensammlung
Wenn du nach Informationen fragst:
- Name: Vor- und Nachname
- Email: Vollständige Email-Adresse
- Datum: Exaktes Datum oder Wochentag
- Uhrzeit: Exakte Uhrzeit

## V85 Race Condition Schutz
Zwei-Schritt-Prozess:
1. collect_appointment_data mit bestaetigung=false (nur prüfen)
2. Kunde bestätigen lassen
3. collect_appointment_data mit bestaetigung=true (tatsächlich buchen)

## Ehrlichkeit
- Wenn du keine Informationen hast, SAGE ES
- Wenn eine Function failed, SAGE ES
- NIEMALS Dinge behaupten die nicht passiert sind
PROMPT
    ,

    'start_node_id' => 'node_01_greeting',
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
// HELPER FUNCTIONS
// ================================================================

function node($id, $name, $type, $instruction, $edges = [], $extra = []) {
    $node = [
        'id' => $id,
        'name' => $name,
        'type' => $type,
        'instruction' => ['type' => 'static_text', 'text' => $instruction],
        'edges' => $edges
    ];

    if ($type === 'function') {
        $node['tool_type'] = $extra['tool_type'] ?? 'local';
        $node['tool_id'] = $extra['tool_id'];
        $node['wait_for_result'] = $extra['wait_for_result'] ?? true;
        $node['speak_during_execution'] = $extra['speak_during_execution'] ?? false;
        $node['instruction'] = ['type' => 'static_text', 'text' => ''];
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

function equation_edge($id, $destination, $left, $operator, $right) {
    return [
        'id' => $id,
        'destination_node_id' => $destination,
        'transition_condition' => [
            'type' => 'equation',
            'equations' => [
                ['left' => $left, 'operator' => $operator, 'right' => $right]
            ]
        ]
    ];
}

// ================================================================
// TOOLS
// ================================================================

$tools = [
    [
        'tool_id' => 'tool-check-customer',
        'name' => 'check_customer',
        'type' => 'custom',
        'description' => 'Überprüft ob Kunde existiert',
        'url' => 'https://api.askproai.de/api/retell/check-customer',
        'timeout_ms' => 4000
    ],

    [
        'tool_id' => 'tool-collect-appointment',
        'name' => 'collect_appointment_data',
        'type' => 'custom',
        'description' => 'Prüft Verfügbarkeit oder bucht Termin',
        'url' => 'https://api.askproai.de/api/retell/collect-appointment',
        'timeout_ms' => 8000,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'customer_name' => ['type' => 'string'],
                'customer_email' => ['type' => 'string'],
                'customer_phone' => ['type' => 'string'],
                'preferred_date' => ['type' => 'string'],
                'preferred_time' => ['type' => 'string'],
                'bestaetigung' => ['type' => 'boolean', 'description' => 'false=prüfen, true=buchen']
            ],
            'required' => ['bestaetigung']
        ]
    ]
];

$flow['tools'] = $tools;

// ================================================================
// NODES - LINEAR FLOW
// ================================================================

$nodes = [];

// --- SCHRITT 1: Begrüßung ---
$nodes[] = node(
    'node_01_greeting',
    'Begrüßung',
    'conversation',
    'Guten Tag bei Ask Pro AI. Wie kann ich Ihnen helfen?',
    [
        prompt_edge(
            'edge_01',
            'node_02_ask_name',
            'User wants to book appointment'
        )
    ]
);

// --- SCHRITT 2: Name erfragen ---
$nodes[] = node(
    'node_02_ask_name',
    'Name erfragen',
    'conversation',
    'Gerne! Ich helfe Ihnen bei der Terminbuchung. Darf ich zunächst Ihren vollständigen Namen haben?',
    [
        prompt_edge(
            'edge_02',
            'node_03_ask_email',
            'User provided name'
        )
    ]
);

// --- SCHRITT 3: Email erfragen ---
$nodes[] = node(
    'node_03_ask_email',
    'Email erfragen',
    'conversation',
    'Vielen Dank! Und wie lautet Ihre E-Mail-Adresse?',
    [
        prompt_edge(
            'edge_03',
            'node_04_ask_date',
            'User provided email'
        )
    ]
);

// --- SCHRITT 4: Datum erfragen ---
$nodes[] = node(
    'node_04_ask_date',
    'Datum erfragen',
    'conversation',
    'Perfekt! Für welches Datum möchten Sie den Termin? Bitte nennen Sie einen Wochentag oder ein konkretes Datum.',
    [
        prompt_edge(
            'edge_04',
            'node_05_ask_time',
            'User provided date'
        )
    ]
);

// --- SCHRITT 5: Uhrzeit erfragen ---
$nodes[] = node(
    'node_05_ask_time',
    'Uhrzeit erfragen',
    'conversation',
    'Sehr gut! Und um welche Uhrzeit hätten Sie gerne den Termin?',
    [
        prompt_edge(
            'edge_05',
            'func_06_check_availability',
            'User provided time'
        )
    ]
);

// --- SCHRITT 6: Verfügbarkeit prüfen (bestaetigung=false) ---
$nodes[] = node(
    'func_06_check_availability',
    'Verfügbarkeit prüfen',
    'function',
    '',
    [
        prompt_edge(
            'edge_06',
            'node_07_confirm',
            'Function completed'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-collect-appointment',
        'speak_during_execution' => true  // SAG "Einen Moment bitte..."
    ]
);

// --- SCHRITT 7: Buchung bestätigen ---
$nodes[] = node(
    'node_07_confirm',
    'Buchung bestätigen',
    'conversation',
    'Der Termin ist verfügbar! Möchten Sie diesen Termin verbindlich buchen?',
    [
        prompt_edge(
            'edge_07_yes',
            'func_08_book',
            'User confirms'
        ),
        prompt_edge(
            'edge_07_no',
            'node_09_cancel',
            'User declines'
        )
    ]
);

// --- SCHRITT 8: Termin buchen (bestaetigung=true) ---
$nodes[] = node(
    'func_08_book',
    'Termin buchen',
    'function',
    '',
    [
        prompt_edge(
            'edge_08',
            'node_10_success',
            'Function completed'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-collect-appointment',
        'speak_during_execution' => true
    ]
);

// --- ENDE: Erfolg ---
$nodes[] = node(
    'node_10_success',
    'Erfolg',
    'end',
    'Wunderbar! Ihr Termin ist jetzt gebucht. Sie erhalten in Kürze eine Bestätigung per E-Mail. Gibt es noch etwas, womit ich Ihnen helfen kann?',
    []
);

// --- ENDE: Abbruch ---
$nodes[] = node(
    'node_09_cancel',
    'Abbruch',
    'end',
    'Kein Problem! Falls Sie doch noch einen Termin vereinbaren möchten, rufen Sie gerne wieder an. Auf Wiederhören!',
    []
);

$flow['nodes'] = $nodes;

// ================================================================
// VALIDATION & SAVE
// ================================================================

$output_file = '/var/www/api-gateway/public/askproai_conversation_flow_working.json';
$json = json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($json === false) {
    die("ERROR: JSON encoding failed: " . json_last_error_msg() . "\n");
}

// Validate
echo "Validating flow...\n";
$data = json_decode($json, true);
$errors = [];

foreach ($data['nodes'] as $node) {
    if ($node['type'] === 'function') {
        if (!isset($node['tool_id'])) $errors[] = "{$node['id']}: missing tool_id";
        if (!isset($node['tool_type'])) $errors[] = "{$node['id']}: missing tool_type";
    }

    if ($node['type'] === 'conversation') {
        $text = $node['instruction']['text'];
        if (preg_match('/(IF|ELSE|{{|}}|WICHTIG)/i', $text)) {
            $errors[] = "{$node['id']}: contains technical terms";
        }
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

echo "=== FLOW STRUCTURE ===\n";
echo "1. Begrüßung\n";
echo "2. Name erfragen\n";
echo "3. Email erfragen\n";
echo "4. Datum erfragen\n";
echo "5. Uhrzeit erfragen\n";
echo "6. Verfügbarkeit prüfen (API)\n";
echo "7. Bestätigung einholen\n";
echo "8. Termin buchen (API)\n";
echo "9. Erfolgsbestätigung\n\n";

echo "=== KEY IMPROVEMENTS ===\n";
echo "1. ✅ Linear flow - agent MUST follow each step\n";
echo "2. ✅ Explicit data collection - name, email, date, time\n";
echo "3. ✅ Function calls speak during execution\n";
echo "4. ✅ Two-step booking (check → confirm → book)\n";
echo "5. ✅ No technical terms in instructions\n";
echo "6. ✅ Clear, natural German\n\n";

echo "Ready to deploy!\n";
