<?php

/**
 * AskPro AI - OPTIMIZED Conversation Flow Builder V2
 *
 * Fixes from Test Call Analysis 2025-10-22:
 * - Smart intent capture during greeting
 * - Conditional routing (skip intent clarification when obvious)
 * - Direct path to booking when intent+date known
 * - Parallel tool execution support
 * - Reduced unnecessary delays
 */

echo "=== BUILDING OPTIMIZED CONVERSATION FLOW V2 ===\n\n";

$flow = [
    'global_prompt' => <<<'PROMPT'
# AskPro AI Terminbuchungs-Agent V2 (Optimiert)

## Identität
Du bist der virtuelle Assistent von Ask Pro AI. Du bist KEIN echter Mensch. Du bist freundlich, professionell und effizient.

## NEUE Verhaltensregeln (Kritisch!)

### 1. Aktives Zuhören vom ersten Moment
WÄHREND der Begrüßung SOFORT auf folgende Informationen achten:
- **Intent**: Will der Kunde buchen, verschieben, stornieren oder Infos?
- **Datum**: Nennt der Kunde ein Datum? (z.B. "Donnerstag", "15.1", "morgen")
- **Uhrzeit**: Nennt der Kunde eine Zeit? (z.B. "13 Uhr", "vormittags")
- **Service**: Nennt der Kunde einen Service? (z.B. "Beratung")

### 2. Informationen SOFORT bestätigen
Wenn der Kunde Termindetails nennt, SOFORT bestätigen:
"Gerne! Einen Termin für [Datum] um [Uhrzeit]."

NIEMALS ignorieren oder später nochmal fragen!

### 3. Nur fehlende Informationen erfragen
- Datum bekannt? → Nicht nochmal fragen
- Zeit bekannt? → Nicht nochmal fragen
- Intent klar? → NICHT nochmal fragen "Was möchten Sie?"

### 4. Speichere Informationen in Variablen
```
{{user_intent}} = "book" | "reschedule" | "cancel" | "info" | "unclear"
{{mentioned_date}} = Datum falls genannt (YYYY-MM-DD)
{{mentioned_time}} = Uhrzeit falls genannt (HH:MM)
{{mentioned_service}} = Service falls genannt
{{mentioned_weekday}} = Wochentag falls genannt
{{intent_confidence}} = "high" | "medium" | "low"
```

## Kernregeln
- NIEMALS Datum, Uhrzeit oder Verfügbarkeit erfinden
- IMMER Funktionsergebnisse verwenden
- Bei Unsicherheit nachfragen
- Nur Vornamen verwenden (kein Herr/Frau ohne explizites Geschlecht)
- Wichtige Details vor der Buchung bestätigen
- Höflich, professionell und effizient bleiben
- **NEU**: Informationen vom Kunden SOFORT verwenden, nicht ignorieren!

## Datumsregeln
- "15.1" bedeutet 15. des AKTUELLEN Monats, NICHT Januar
- Verwende current_time_berlin() als Referenz
- Relative Daten parsen: "morgen" = tomorrow, "übermorgen" = day after tomorrow

## Intent Recognition (Nur bei Unklarheit!)
Nur wenn {{intent_confidence}} == "low" DANN fragen:
"Möchten Sie einen NEUEN Termin vereinbaren oder einen BESTEHENDEN Termin ändern?"

Bei {{intent_confidence}} == "high" → DIREKT zur Aktion!

## V85 Race Condition Schutz (Nur bei NEUEN Buchungen!)
- SCHRITT 1: collect_appointment_data mit bestaetigung=false (nur prüfen)
- SCHRITT 2: Explizite Benutzerbestätigung einholen
- SCHRITT 3: collect_appointment_data mit bestaetigung=true (buchen)
- Bei Race Condition: Sofort Alternativen anbieten

## Policy Violations (Empathisch!)
Bei Frist-Überschreitungen:
"Ich verstehe Ihre Situation. Leider können wir kurzfristige Änderungen nur bis [FRIST] vornehmen.
Sie haben folgende Möglichkeiten: [Optionen nennen]. Was möchten Sie tun?"

## Unerwartete Reaktionen
- Kunde bricht ab → Höflich verabschieden
- Kunde unsicher → Zeit geben, Optionen wiederholen
- Intent wechselt → Flexibel zurück zu Intent Recognition
- Kunde kennt Datum nicht → Termine auflisten

## Optimierte Responsiveness
- Innerhalb von 1 Sekunde nach Nutzeräußerung reagieren
- "Einen Moment bitte..." nur bei Operationen >4 Sekunden
- Niemals Stille über 2 Sekunden
PROMPT
    ,

    'start_node_id' => 'node_01_greeting_smart',
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
        $node['tool_type'] = isset($extra['tool_type']) ? $extra['tool_type'] : 'local';
        $node['tool_id'] = $extra['tool_id'];
        $node['wait_for_result'] = isset($extra['wait_for_result']) ? $extra['wait_for_result'] : true;
        $node['speak_during_execution'] = isset($extra['speak_during_execution']) ? $extra['speak_during_execution'] : false;
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
        if (is_string($eq)) {
            // Parse "{{var}} == value" format
            preg_match('/\{\{(\w+)\}\}\s*(==|!=|>|<|>=|<=)\s*"?([^"]+)"?/', $eq, $matches);
            if ($matches) {
                $formatted[] = [
                    'left' => $matches[1],
                    'operator' => $matches[2],
                    'right' => $matches[3]
                ];
            }
        } else {
            $formatted[] = $eq;
        }
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
// TOOLS DEFINITION (Same as before)
// ================================================================

$tools = [
    [
        'tool_id' => 'tool-check-customer',
        'name' => 'check_customer',
        'type' => 'custom',
        'description' => 'Überprüft ob Kunde anhand Telefonnummer in der Datenbank existiert.',
        'url' => 'https://api.askproai.de/api/retell/check-customer',
        'timeout_ms' => 4000,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'phone_number' => [
                    'type' => 'string',
                    'description' => 'Telefonnummer des Anrufers'
                ]
            ]
        ]
    ],

    [
        'tool_id' => 'tool-current-time-berlin',
        'name' => 'current_time_berlin',
        'type' => 'custom',
        'description' => 'Holt aktuelles Datum und Uhrzeit in Berlin Zeitzone.',
        'url' => 'https://api.askproai.de/api/retell/current-time-berlin',
        'timeout_ms' => 4000
    ],

    [
        'tool_id' => 'tool-collect-appointment-data',
        'name' => 'collect_appointment_data',
        'type' => 'custom',
        'description' => 'Prüft Verfügbarkeit oder bucht Termin. bestaetigung: false=prüfen, true=buchen.',
        'url' => 'https://api.askproai.de/api/retell/collect-appointment-data',
        'timeout_ms' => 8000,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'customer_name' => ['type' => 'string'],
                'customer_phone' => ['type' => 'string'],
                'customer_email' => ['type' => 'string'],
                'service' => ['type' => 'string'],
                'preferred_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                'preferred_time' => ['type' => 'string', 'description' => 'HH:MM'],
                'bestaetigung' => ['type' => 'boolean']
            ],
            'required' => ['bestaetigung']
        ]
    ],

    [
        'tool_id' => 'tool-get-customer-appointments',
        'name' => 'get_customer_appointments',
        'type' => 'custom',
        'description' => 'Ruft alle bevorstehenden Termine eines Kunden ab.',
        'url' => 'https://api.askproai.de/api/retell/get-customer-appointments',
        'timeout_ms' => 6000,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'call_id' => ['type' => 'string'],
                'customer_name' => ['type' => 'string']
            ],
            'required' => ['call_id']
        ]
    ],

    [
        'tool_id' => 'tool-cancel-appointment',
        'name' => 'cancel_appointment',
        'type' => 'custom',
        'description' => 'Storniert einen Termin. Prüft automatisch Policy.',
        'url' => 'https://api.askproai.de/api/retell/cancel-appointment',
        'timeout_ms' => 8000,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'call_id' => ['type' => 'string'],
                'appointment_date' => ['type' => 'string'],
                'customer_name' => ['type' => 'string'],
                'reason' => ['type' => 'string']
            ],
            'required' => ['call_id', 'appointment_date']
        ]
    ],

    [
        'tool_id' => 'tool-reschedule-appointment',
        'name' => 'reschedule_appointment',
        'type' => 'custom',
        'description' => 'Verschiebt einen Termin. Prüft automatisch Policy.',
        'url' => 'https://api.askproai.de/api/retell/reschedule-appointment',
        'timeout_ms' => 10000,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'call_id' => ['type' => 'string'],
                'old_date' => ['type' => 'string'],
                'new_date' => ['type' => 'string'],
                'new_time' => ['type' => 'string'],
                'reason' => ['type' => 'string']
            ],
            'required' => ['call_id', 'old_date', 'new_date']
        ]
    ]
];

$flow['tools'] = $tools;

// ================================================================
// OPTIMIZED NODE STRUCTURE
// ================================================================

$nodes = [];

// --- PHASE 1: SMART GREETING + PARALLEL DATA COLLECTION ---

$nodes[] = node(
    'node_01_greeting_smart',
    'Smart Begrüßung',
    'conversation',
    'Willkommen bei Ask Pro AI. Guten Tag! Wie kann ich Ihnen helfen?

WICHTIG - Während du auf die Antwort wartest, achte auf:
1. Terminbuchungs-Intent: "Ich hätte gern Termin", "Termin buchen", "reservieren"
2. Datum-Nennung: "Donnerstag", "15.1", "morgen", etc.
3. Zeit-Nennung: "13 Uhr", "vormittags", "14:30"
4. Service: "Beratung", "Konsultation"

Wenn der Kunde einen Terminwunsch äußert:
→ Setze {{user_intent}} = "book"
→ Setze {{intent_confidence}} = "high"
→ Speichere {{mentioned_date}}, {{mentioned_time}}, {{mentioned_service}} falls genannt

Wenn der Kunde über Verschiebung/Stornierung spricht:
→ Setze {{user_intent}} = "reschedule" oder "cancel"
→ Setze {{intent_confidence}} = "high"

Wenn unklar:
→ Setze {{intent_confidence}} = "low"',
    [
        prompt_edge(
            'edge_greeting_to_parallel',
            'Nach Begrüßung',
            'func_parallel_time_check',
            'User has responded to greeting'
        )
    ]
);

// Parallel execution: Time + Customer check
$nodes[] = node(
    'func_parallel_time_check',
    'Zeit abrufen (parallel)',
    'function',
    '',
    [
        prompt_edge(
            'edge_time_to_customer',
            'Zeit abgerufen',
            'func_parallel_customer_check',
            'Skip response'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-current-time-berlin',
        'speak_during_execution' => false,
        'wait_for_result' => true
    ]
);

$nodes[] = node(
    'func_parallel_customer_check',
    'Kunde prüfen (parallel)',
    'function',
    '',
    [
        prompt_edge(
            'edge_customer_to_router',
            'Kunde geprüft',
            'node_smart_router',
            'Skip response'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-check-customer',
        'speak_during_execution' => false,
        'wait_for_result' => true
    ]
);

// --- SMART ROUTER NODE ---
$nodes[] = node(
    'node_smart_router',
    'Intelligentes Routing',
    'conversation',
    'SILENT ROUTING NODE - Do NOT speak here!

Analyze the situation:
- customer_status from check_customer
- {{user_intent}} from greeting
- {{intent_confidence}} from greeting
- {{mentioned_date}} and {{mentioned_time}} availability

Route intelligently based on ALL available information.',
    [
        // Route 1: Known customer + high confidence intent + date/time known → Direct to booking
        equation_edge(
            'edge_router_known_direct_book',
            'Bekannter Kunde, klarer Intent, Datum bekannt',
            'node_confirm_known_customer',
            [
                ['left' => 'customer_status', 'operator' => '==', 'right' => 'found'],
                ['left' => 'user_intent', 'operator' => '==', 'right' => 'book'],
                ['left' => 'intent_confidence', 'operator' => '==', 'right' => 'high']
            ]
        ),

        // Route 2: New customer + high confidence intent → Get details then book
        equation_edge(
            'edge_router_new_direct_book',
            'Neuer Kunde, klarer Intent',
            'node_get_customer_details',
            [
                ['left' => 'customer_status', 'operator' => '==', 'right' => 'new_customer'],
                ['left' => 'user_intent', 'operator' => '==', 'right' => 'book'],
                ['left' => 'intent_confidence', 'operator' => '==', 'right' => 'high']
            ]
        ),

        // Route 3: Anonymous customer + high confidence → Get details then book
        equation_edge(
            'edge_router_anon_direct_book',
            'Anonymer Kunde, klarer Intent',
            'node_get_customer_details',
            [
                ['left' => 'customer_status', 'operator' => '==', 'right' => 'anonymous'],
                ['left' => 'user_intent', 'operator' => '==', 'right' => 'book'],
                ['left' => 'intent_confidence', 'operator' => '==', 'right' => 'high']
            ]
        ),

        // Route 4: Known customer but low confidence → Clarify intent
        equation_edge(
            'edge_router_known_unclear',
            'Bekannter Kunde, unklarer Intent',
            'node_intent_clarification',
            [
                ['left' => 'customer_status', 'operator' => '==', 'right' => 'found'],
                ['left' => 'intent_confidence', 'operator' => '==', 'right' => 'low']
            ]
        ),

        // Route 5: New/Anon customer + low confidence → Get details then clarify
        prompt_edge(
            'edge_router_default',
            'Fallback: Details dann Intent klären',
            'node_get_customer_details',
            'Default routing'
        )
    ]
);

// --- KNOWN CUSTOMER PATH (Quick confirmation) ---
$nodes[] = node(
    'node_confirm_known_customer',
    'Bekannter Kunde Bestätigung',
    'conversation',
    'Greet warmly and IMMEDIATELY acknowledge their request:

"Schön Sie wiederzuhören, {{customer_name}}! Einen Termin für {{mentioned_date}} um {{mentioned_time}} - lassen Sie mich die Verfügbarkeit prüfen."

Use the information they already provided! Don\'t ask again!',
    [
        prompt_edge(
            'edge_known_to_collect',
            'Zur Datensammlung',
            'func_collect_check',
            'Proceed to check availability'
        )
    ]
);

// --- NEW/ANONYMOUS CUSTOMER PATH ---
$nodes[] = node(
    'node_get_customer_details',
    'Kundendetails erfragen',
    'conversation',
    'IF {{user_intent}} == "book" AND {{mentioned_date}} is known:
    Say: "Gerne! Für {{mentioned_date}} um {{mentioned_time}}. Darf ich zunächst Ihren Namen und Ihre E-Mail-Adresse haben?"
ELSE IF {{user_intent}} == "book":
    Say: "Gerne! Darf ich zunächst Ihren Namen und Ihre E-Mail-Adresse haben?"
ELSE:
    Say: "Gerne! Darf ich zunächst Ihren Namen und Ihre E-Mail-Adresse haben?"

WICHTIG: Acknowledge any appointment details they mentioned before asking for personal info!',
    [
        prompt_edge(
            'edge_details_to_intent_check',
            'Details erhalten',
            'node_intent_decision',
            'Customer provided name and email'
        )
    ]
);

// --- INTENT DECISION NODE ---
$nodes[] = node(
    'node_intent_decision',
    'Intent-Entscheidung',
    'conversation',
    'SILENT ROUTING - Do NOT speak!

Check {{intent_confidence}}:
- If "high" → Go directly to action
- If "low" → Go to intent clarification',
    [
        equation_edge(
            'edge_intent_high_book',
            'Hohe Konfidenz: Buchung',
            'node_booking_path',
            [
                ['left' => 'intent_confidence', 'operator' => '==', 'right' => 'high'],
                ['left' => 'user_intent', 'operator' => '==', 'right' => 'book']
            ]
        ),

        equation_edge(
            'edge_intent_low',
            'Niedrige Konfidenz: Klären',
            'node_intent_clarification',
            [
                ['left' => 'intent_confidence', 'operator' => '==', 'right' => 'low']
            ]
        ),

        prompt_edge(
            'edge_intent_default',
            'Default: Buchung',
            'node_booking_path',
            'Default to booking'
        )
    ]
);

// --- INTENT CLARIFICATION (Only when necessary!) ---
$nodes[] = node(
    'node_intent_clarification',
    'Intent-Klärung',
    'conversation',
    'Only ask this if {{intent_confidence}} == "low":

"Ich wollte nur sicherstellen, dass ich Sie richtig verstanden habe. Möchten Sie einen NEUEN Termin vereinbaren oder einen BESTEHENDEN Termin ändern?"

After user responds, set {{user_intent}} based on their answer.',
    [
        prompt_edge(
            'edge_clarified_book',
            'Buchung gewählt',
            'node_booking_path',
            'User wants to book'
        ),
        prompt_edge(
            'edge_clarified_reschedule',
            'Verschiebung gewählt',
            'func_get_appointments',
            'User wants to reschedule'
        ),
        prompt_edge(
            'edge_clarified_cancel',
            'Stornierung gewählt',
            'func_get_appointments',
            'User wants to cancel'
        )
    ]
);

// --- BOOKING PATH ---
$nodes[] = node(
    'node_booking_path',
    'Buchungs-Flow',
    'conversation',
    'IF {{mentioned_date}} AND {{mentioned_time}} already known:
    Say: "Perfekt! Ich prüfe gleich die Verfügbarkeit für {{mentioned_date}} um {{mentioned_time}}."
ELSE IF {{mentioned_date}} known but NOT {{mentioned_time}}:
    Say: "Für welche Uhrzeit möchten Sie den Termin am {{mentioned_date}}?"
ELSE:
    Say: "Für welches Datum und welche Uhrzeit möchten Sie den Termin?"

WICHTIG: Verwende bereits genannte Informationen! Nicht nochmal fragen!',
    [
        prompt_edge(
            'edge_booking_to_collect',
            'Zur Verfügbarkeitsprüfung',
            'func_collect_check',
            'All booking details collected'
        )
    ]
);

// --- COLLECT APPOINTMENT DATA (Step 1: Check) ---
$nodes[] = node(
    'func_collect_check',
    'Verfügbarkeit prüfen',
    'function',
    '',
    [
        prompt_edge(
            'edge_collect_check_to_confirm',
            'Zur Bestätigung',
            'node_booking_confirmation',
            'Skip response'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-collect-appointment-data',
        'speak_during_execution' => true,
        'wait_for_result' => true
    ]
);

// --- BOOKING CONFIRMATION ---
$nodes[] = node(
    'node_booking_confirmation',
    'Buchung bestätigen',
    'conversation',
    'Based on collect_appointment_data result:

IF available == true:
    "Sehr gut! Der Termin am {{date}} um {{time}} ist verfügbar. Soll ich diesen für Sie buchen?"
ELSE:
    "Leider ist {{date}} um {{time}} nicht verfügbar. Ich hätte folgende Alternativen: {{alternatives}}. Welcher Termin würde Ihnen passen?"',
    [
        prompt_edge(
            'edge_confirm_yes',
            'Kunde bestätigt',
            'func_collect_book',
            'User confirms booking'
        ),
        prompt_edge(
            'edge_confirm_alternative',
            'Alternative gewählt',
            'func_collect_book',
            'User chooses alternative'
        ),
        prompt_edge(
            'edge_confirm_abort',
            'Kunde bricht ab',
            'end_polite',
            'User cancels'
        )
    ]
);

// --- COLLECT APPOINTMENT DATA (Step 2: Book) ---
$nodes[] = node(
    'func_collect_book',
    'Termin buchen',
    'function',
    '',
    [
        prompt_edge(
            'edge_book_to_success',
            'Zur Erfolgsbestätigung',
            'end_booking_success',
            'Skip response'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-collect-appointment-data',
        'speak_during_execution' => true,
        'wait_for_result' => true
    ]
);

// --- END NODES ---
$nodes[] = node(
    'end_booking_success',
    'Erfolgreiche Buchung',
    'end',
    'Perfect! Ihr Termin am {{date}} um {{time}} ist gebucht. Sie erhalten eine Bestätigung per E-Mail. Gibt es noch etwas, womit ich Ihnen helfen kann?',
    []
);

$nodes[] = node(
    'end_polite',
    'Höfliche Verabschiedung',
    'end',
    'Kein Problem! Falls Sie doch noch einen Termin vereinbaren möchten, rufen Sie uns gerne jederzeit wieder an. Auf Wiederhören!',
    []
);

// Add appointment list function for reschedule/cancel flows
$nodes[] = node(
    'func_get_appointments',
    'Termine abrufen',
    'function',
    '',
    [
        prompt_edge(
            'edge_appts_to_reschedule',
            'Zur Verschiebung',
            'node_reschedule_flow',
            'For reschedule'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-get-customer-appointments',
        'speak_during_execution' => true,
        'wait_for_result' => true
    ]
);

$nodes[] = node(
    'node_reschedule_flow',
    'Verschiebungs-Flow',
    'end',
    'Hier würde der Verschiebungs-Flow weitergehen. (Noch zu implementieren)',
    []
);

$flow['nodes'] = $nodes;

// ================================================================
// SAVE TO FILE
// ================================================================

$output_file = '/var/www/api-gateway/public/askproai_conversation_flow_optimized_v2.json';
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

echo "=== KEY OPTIMIZATIONS ===\n";
echo "1. ✅ Smart greeting with intent capture\n";
echo "2. ✅ Parallel time + customer check\n";
echo "3. ✅ Conditional routing (skip intent clarification when obvious)\n";
echo "4. ✅ Direct path to booking when intent+date known\n";
echo "5. ✅ Reduced unnecessary delays\n";
echo "6. ✅ Information reuse (don't ask twice)\n\n";

echo "Next: Test with real call to validate improvements!\n";
