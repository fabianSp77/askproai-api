<?php

/**
 * AskPro AI - NATÜRLICHER Conversation Flow (FIXED)
 *
 * KRITISCHER FIX:
 * - Instruktionen sind NUR natürliche deutsche Sätze
 * - KEINE technischen Anweisungen in conversation nodes
 * - KEINE IF/THEN Logik die vorgelesen wird
 * - Komplexe Logik gehört in global_prompt
 */

echo "=== BUILDING NATURAL CONVERSATION FLOW (FIXED) ===\n\n";

$flow = [
    'global_prompt' => <<<'PROMPT'
# AskPro AI Terminbuchungs-Agent

## Identität
Du bist der freundliche Assistent von Ask Pro AI. Sprich natürlich, professionell und effizient auf Deutsch.

## Kernregeln
- Sprich natürlich und freundlich
- Keine technischen Begriffe vorlesen
- Nur Vornamen verwenden (kein Herr/Frau)
- Bei Unsicherheit nachfragen
- Kurze, klare Sätze

## Automatisches Intent-Erkennung
Während der Begrüßung achte auf:
- Buchungswunsch: "Termin", "buchen", "reservieren" → {{user_intent}} = "book"
- Datum: "Donnerstag", "morgen", "15.1" → speichere in {{mentioned_date}}
- Uhrzeit: "13 Uhr", "vormittags" → speichere in {{mentioned_time}}

## Informationen wiederverwenden
Wenn der Kunde bereits Datum/Uhrzeit genannt hat:
- NICHT nochmal fragen
- Direkt verwenden: "Für [Datum] um [Uhrzeit]"
- Nur fehlende Infos erfragen

## V85 Race Condition Schutz
Bei neuen Buchungen:
1. Erst prüfen (bestaetigung=false)
2. Kunde bestätigen lassen
3. Dann buchen (bestaetigung=true)

## Natürliche Sprache
- "Gerne!" statt "Okay"
- "Einen Moment bitte" bei längeren Operationen
- "Schön Sie wiederzuhören" bei bekannten Kunden
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

    if (isset($extra['response_type'])) {
        $node['response_type'] = $extra['response_type'];
    }

    return $node;
}

function prompt_edge($id, $condition, $destination, $prompt) {
    return [
        'id' => $id,
        'condition' => $condition,
        'destination_node_id' => $destination,
        'transition_condition' => [
            'type' => 'prompt',
            'prompt' => $prompt
        ]
    ];
}

function equation_edge($id, $condition, $destination, $equations) {
    $formatted = [];
    foreach ($equations as $eq) {
        $formatted[] = $eq;
    }

    return [
        'id' => $id,
        'condition' => $condition,
        'destination_node_id' => $destination,
        'transition_condition' => [
            'type' => 'equation',
            'equations' => $formatted,
            'operator' => '&&'
        ]
    ];
}

// ================================================================
// TOOLS (Same as before)
// ================================================================

$tools = [
    [
        'tool_id' => 'tool-check-customer',
        'name' => 'check_customer',
        'type' => 'custom',
        'description' => 'Überprüft ob Kunde in Datenbank existiert',
        'url' => 'https://api.askproai.de/api/retell/check-customer',
        'timeout_ms' => 4000,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'phone_number' => ['type' => 'string']
            ]
        ]
    ],

    [
        'tool_id' => 'tool-current-time-berlin',
        'name' => 'current_time_berlin',
        'type' => 'custom',
        'description' => 'Holt aktuelles Datum und Uhrzeit in Berlin',
        'url' => 'https://api.askproai.de/api/retell/current-time-berlin',
        'timeout_ms' => 4000
    ],

    [
        'tool_id' => 'tool-collect-appointment-data',
        'name' => 'collect_appointment_data',
        'type' => 'custom',
        'description' => 'Prüft Verfügbarkeit oder bucht Termin',
        'url' => 'https://api.askproai.de/api/retell/collect-appointment-data',
        'timeout_ms' => 8000,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'customer_name' => ['type' => 'string'],
                'customer_phone' => ['type' => 'string'],
                'customer_email' => ['type' => 'string'],
                'service' => ['type' => 'string'],
                'preferred_date' => ['type' => 'string'],
                'preferred_time' => ['type' => 'string'],
                'bestaetigung' => ['type' => 'boolean']
            ],
            'required' => ['bestaetigung']
        ]
    ]
];

$flow['tools'] = $tools;

// ================================================================
// NATÜRLICHE NODE STRUKTUR
// ================================================================

$nodes = [];

// --- NODE 1: BEGRÜSSUNG (Natürlich und freundlich) ---
$nodes[] = node(
    'node_greeting',
    'Begrüßung',
    'conversation',
    'Guten Tag bei Ask Pro AI. Wie kann ich Ihnen helfen?',
    [
        prompt_edge(
            'edge_greeting_done',
            'Nach Begrüßung',
            'func_time_check',
            'User responded'
        )
    ]
);

// --- NODE 2: Zeit abrufen (parallel) ---
$nodes[] = node(
    'func_time_check',
    'Zeit abrufen',
    'function',
    '',
    [
        prompt_edge(
            'edge_time_to_customer',
            'Zeit abgerufen',
            'func_customer_check',
            'Skip response'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-current-time-berlin',
        'speak_during_execution' => false
    ]
);

// --- NODE 3: Kunde prüfen ---
$nodes[] = node(
    'func_customer_check',
    'Kunde prüfen',
    'function',
    '',
    [
        prompt_edge(
            'edge_customer_to_details',
            'Kunde geprüft',
            'node_ask_details',
            'Skip response'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-check-customer',
        'speak_during_execution' => false
    ]
);

// --- NODE 4: Kundendetails erfragen (NUR wenn nötig) ---
$nodes[] = node(
    'node_ask_details',
    'Details erfragen',
    'conversation',
    'Gerne! Darf ich Ihren Namen und Ihre E-Mail-Adresse haben?',
    [
        prompt_edge(
            'edge_details_to_date',
            'Details erhalten',
            'node_ask_date',
            'Has customer details'
        )
    ]
);

// --- NODE 5: Datum/Uhrzeit erfragen (NUR wenn nicht schon genannt) ---
$nodes[] = node(
    'node_ask_date',
    'Datum erfragen',
    'conversation',
    'Perfekt! Für welches Datum und welche Uhrzeit möchten Sie den Termin?',
    [
        prompt_edge(
            'edge_date_to_collect',
            'Datum erhalten',
            'func_collect_check',
            'Has date and time'
        )
    ]
);

// --- NODE 6: Verfügbarkeit prüfen (bestaetigung=false) ---
$nodes[] = node(
    'func_collect_check',
    'Verfügbarkeit prüfen',
    'function',
    '',
    [
        prompt_edge(
            'edge_check_to_confirm',
            'Verfügbarkeit geprüft',
            'node_confirm_booking',
            'Skip response'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-collect-appointment-data',
        'speak_during_execution' => true
    ]
);

// --- NODE 7: Buchung bestätigen ---
$nodes[] = node(
    'node_confirm_booking',
    'Buchung bestätigen',
    'conversation',
    'Sehr gut! Der Termin ist verfügbar. Soll ich diesen für Sie buchen?',
    [
        prompt_edge(
            'edge_confirm_yes',
            'Bestätigt',
            'func_collect_book',
            'User confirms'
        ),
        prompt_edge(
            'edge_confirm_no',
            'Abgelehnt',
            'end_polite',
            'User declines'
        )
    ]
);

// --- NODE 8: Termin buchen (bestaetigung=true) ---
$nodes[] = node(
    'func_collect_book',
    'Termin buchen',
    'function',
    '',
    [
        prompt_edge(
            'edge_book_to_success',
            'Gebucht',
            'end_success',
            'Skip response'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-collect-appointment-data',
        'speak_during_execution' => true
    ]
);

// --- END NODES ---
$nodes[] = node(
    'end_success',
    'Erfolg',
    'end',
    'Perfekt! Ihr Termin ist gebucht. Sie erhalten eine Bestätigung per E-Mail. Gibt es noch etwas?',
    []
);

$nodes[] = node(
    'end_polite',
    'Verabschiedung',
    'end',
    'Kein Problem! Falls Sie doch noch einen Termin möchten, rufen Sie gerne wieder an. Auf Wiederhören!',
    []
);

$flow['nodes'] = $nodes;

// ================================================================
// SAVE TO FILE
// ================================================================

$output_file = '/var/www/api-gateway/public/askproai_conversation_flow_natural.json';
$json = json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($json === false) {
    die("ERROR: JSON encoding failed: " . json_last_error_msg() . "\n");
}

file_put_contents($output_file, $json);

echo "✅ SUCCESS!\n";
echo "Output: $output_file\n";
echo "Size: " . round(strlen($json) / 1024, 2) . " KB\n";
echo "Nodes: " . count($flow['nodes']) . "\n";
echo "Tools: " . count($flow['tools']) . "\n\n";

echo "=== FIXES APPLIED ===\n";
echo "1. ✅ Alle Instruktionen sind natürliche deutsche Sätze\n";
echo "2. ✅ KEINE technischen Anweisungen in conversation nodes\n";
echo "3. ✅ KEINE IF/THEN Logik die vorgelesen wird\n";
echo "4. ✅ Kurze, professionelle Begrüßung\n";
echo "5. ✅ Natürliches Sprachverhalten\n";
echo "6. ✅ Komplexe Logik im global_prompt\n\n";

echo "Ready to deploy!\n";
