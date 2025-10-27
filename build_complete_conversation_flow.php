<?php

/**
 * AskPro AI - Complete Conversation Flow Builder
 * Erstellt den vollständigen Flow mit allen Funktionen:
 * - Neue Buchung
 * - Terminverschiebung
 * - Stornierung
 * - Terminabfrage
 */

echo "=== BUILDING COMPLETE CONVERSATION FLOW ===\n\n";

$flow = [
    'global_prompt' => <<<'PROMPT'
# AskPro AI Terminbuchungs-Agent

## Identität
Du bist der virtuelle Assistent von Ask Pro AI. Du bist KEIN echter Mensch. Du bist freundlich, professionell und effizient.

## Kernregeln
- NIEMALS Datum, Uhrzeit oder Verfügbarkeit erfinden
- IMMER Funktionsergebnisse verwenden
- Bei Unsicherheit nachfragen
- Nur Vornamen verwenden (kein Herr/Frau ohne explizites Geschlecht)
- Wichtige Details vor der Buchung bestätigen
- Höflich, professionell und effizient bleiben

## Datumsregeln
- "15.1" bedeutet 15. des AKTUELLEN Monats, NICHT Januar
- Verwende current_time_berlin() als Referenz
- Relative Daten parsen: "morgen" = tomorrow, "übermorgen" = day after tomorrow

## Intent Recognition (KRITISCH!)
Bei unklaren Äußerungen IMMER nachfragen:
"Möchten Sie einen NEUEN Termin vereinbaren oder einen BESTEHENDEN Termin ändern?"

Optionen:
1. Neuen Termin buchen
2. Bestehenden Termin verschieben
3. Bestehenden Termin absagen
4. Termin-Information abfragen

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

## Anti-Silence Regel
- Innerhalb von 2 Sekunden nach Nutzeräußerung sprechen
- Während Verarbeitung: "Einen Moment bitte..."
- Niemals Stille über 3 Sekunden
PROMPT
    ,

    'start_node_id' => 'node_01_greeting',
    'start_speaker' => 'agent',

    'model_choice' => [
        'type' => 'cascading',
        'model' => 'gpt-4o-mini'
    ],

    'model_temperature' => 0.3,

    'tools' => [],  // Will be populated below
    'nodes' => []   // Will be populated below
];

// ================================================================
// TOOLS DEFINITION
// ================================================================

$tools = [
    // Tool 1: Check Customer
    [
        'tool_id' => 'tool-check-customer',
        'name' => 'check_customer',
        'type' => 'custom',
        'description' => 'Überprüft ob Kunde anhand Telefonnummer in der Datenbank existiert. Gibt customer_status zurück: found (bekannter Kunde), new_customer (neue Nummer, bekannter Name), oder anonymous (unbekannt).',
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

    // Tool 2: Current Time
    [
        'tool_id' => 'tool-current-time-berlin',
        'name' => 'current_time_berlin',
        'type' => 'custom',
        'description' => 'Holt aktuelles Datum und Uhrzeit in Berlin Zeitzone. Essentiell für Datumsberechnung bei relativen Angaben wie morgen oder 15.1.',
        'url' => 'https://api.askproai.de/api/retell/current-time-berlin',
        'timeout_ms' => 4000
    ],

    // Tool 3: Collect Appointment Data
    [
        'tool_id' => 'tool-collect-appointment-data',
        'name' => 'collect_appointment_data',
        'type' => 'custom',
        'description' => 'Prüft Verfügbarkeit oder bucht Termin. Parameter bestaetigung: false=nur prüfen, true=tatsächlich buchen. Gibt Verfügbarkeit und ggf. Alternativen zurück.',
        'url' => 'https://api.askproai.de/api/retell/collect-appointment-data',
        'timeout_ms' => 8000,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'customer_name' => ['type' => 'string', 'description' => 'Name des Kunden'],
                'customer_phone' => ['type' => 'string', 'description' => 'Telefonnummer des Kunden'],
                'service' => ['type' => 'string', 'description' => 'Dienstleistung (z.B. Beratung)'],
                'preferred_date' => ['type' => 'string', 'description' => 'Gewünschtes Datum im Format YYYY-MM-DD'],
                'preferred_time' => ['type' => 'string', 'description' => 'Gewünschte Uhrzeit im Format HH:MM'],
                'bestaetigung' => ['type' => 'boolean', 'description' => 'false = nur Verfügbarkeit prüfen, true = tatsächlich buchen']
            ],
            'required' => ['bestaetigung']
        ]
    ],

    // Tool 4: Get Customer Appointments
    [
        'tool_id' => 'tool-get-customer-appointments',
        'name' => 'get_customer_appointments',
        'type' => 'custom',
        'description' => 'Ruft alle bevorstehenden Termine eines Kunden ab. Gibt Liste mit Datum, Zeit, Service zurück.',
        'url' => 'https://api.askproai.de/api/retell/get-customer-appointments',
        'timeout_ms' => 6000,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'call_id' => ['type' => 'string', 'description' => 'Retell Call ID'],
                'customer_name' => ['type' => 'string', 'description' => 'Name des Kunden (optional)']
            ],
            'required' => ['call_id']
        ]
    ],

    // Tool 5: Cancel Appointment
    [
        'tool_id' => 'tool-cancel-appointment',
        'name' => 'cancel_appointment',
        'type' => 'custom',
        'description' => 'Storniert einen Termin. Prüft automatisch Policy (Fristen, Gebühren). Gibt Erfolg oder Policy-Violation zurück.',
        'url' => 'https://api.askproai.de/api/retell/cancel-appointment',
        'timeout_ms' => 8000,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'call_id' => ['type' => 'string', 'description' => 'Retell Call ID'],
                'appointment_date' => ['type' => 'string', 'description' => 'Termin-Datum im Format YYYY-MM-DD'],
                'customer_name' => ['type' => 'string', 'description' => 'Name des Kunden'],
                'reason' => ['type' => 'string', 'description' => 'Grund der Stornierung']
            ],
            'required' => ['call_id', 'appointment_date']
        ]
    ],

    // Tool 6: Reschedule Appointment
    [
        'tool_id' => 'tool-reschedule-appointment',
        'name' => 'reschedule_appointment',
        'type' => 'custom',
        'description' => 'Verschiebt einen Termin auf neues Datum/Zeit. Prüft automatisch Policy (Fristen, Gebühren). Gibt Erfolg oder Policy-Violation zurück.',
        'url' => 'https://api.askproai.de/api/retell/reschedule-appointment',
        'timeout_ms' => 10000,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'call_id' => ['type' => 'string', 'description' => 'Retell Call ID'],
                'old_date' => ['type' => 'string', 'description' => 'Aktuelles Termin-Datum YYYY-MM-DD'],
                'new_date' => ['type' => 'string', 'description' => 'Neues Termin-Datum YYYY-MM-DD'],
                'new_time' => ['type' => 'string', 'description' => 'Neue Uhrzeit HH:MM'],
                'customer_name' => ['type' => 'string', 'description' => 'Name des Kunden'],
                'reason' => ['type' => 'string', 'description' => 'Grund der Verschiebung']
            ],
            'required' => ['call_id', 'old_date', 'new_date', 'new_time']
        ]
    ]
];

$flow['tools'] = $tools;

echo "✓ Tools defined: " . count($tools) . "\n";

// ================================================================
// NODES DEFINITION
// ================================================================

$nodes = [];
$x = 100;  // Start X position
$y = 1200; // Start Y position

// Helper function to create node
function node($id, $name, $type, $instruction, $edges = [], $extra = []) {
    global $x, $y;

    $node = array_merge([
        'id' => $id,
        'name' => $name,
        'type' => $type,
        'display_position' => ['x' => $x, 'y' => $y]
    ], $extra);

    if ($instruction) {
        $node['instruction'] = $instruction;
    }

    if ($edges) {
        $node['edges'] = $edges;
    }

    if ($type === 'conversation' && !isset($node['knowledge_base_ids'])) {
        $node['knowledge_base_ids'] = [];
    }

    $x += 400; // Move right for next node
    if ($x > 5000) {
        $x = 100;
        $y += 400; // Move down
    }

    return $node;
}

function edge($id, $condition, $destination, $type = 'prompt', $prompt = null, $equations = null) {
    $edge = [
        'id' => $id,
        'condition' => $condition,
        'destination_node_id' => $destination
    ];

    if ($type === 'prompt') {
        $edge['transition_condition'] = [
            'type' => 'prompt',
            'prompt' => $prompt ?? $condition
        ];
    } elseif ($type === 'equation') {
        $edge['transition_condition'] = [
            'type' => 'equation',
            'equations' => $equations,
            'operator' => '&&'
        ];
    }

    return $edge;
}

// PHASE 1: Begrüßung & Kontext
// ============================================================

$nodes[] = node(
    'node_01_greeting',
    'Begrüßung',
    'conversation',
    ['type' => 'static_text', 'text' => 'Willkommen bei Ask Pro AI. Guten Tag!'],
    [edge('edge_greeting_time', 'Greeting completed', 'func_01_current_time')],
    ['start_speaker' => 'agent']
);

$nodes[] = node(
    'func_01_current_time',
    'Zeit abrufen',
    'function',
    ['type' => 'static_text', 'text' => ''],
    [edge('edge_time_customer', 'Time retrieved', 'func_01_check_customer')],
    ['tool_id' => 'tool-current-time-berlin', 'tool_type' => 'local', 'speak_during_execution' => false, 'wait_for_result' => true]
);

$nodes[] = node(
    'func_01_check_customer',
    'Kunde prüfen',
    'function',
    ['type' => 'static_text', 'text' => ''],
    [edge('edge_customer_routing', 'Customer checked', 'node_02_customer_routing')],
    ['tool_id' => 'tool-check-customer', 'tool_type' => 'local', 'speak_during_execution' => false, 'wait_for_result' => true]
);

// PHASE 2: Kunden-Routing
// ============================================================

$nodes[] = node(
    'node_02_customer_routing',
    'Kunden-Routing',
    'conversation',
    ['type' => 'prompt', 'text' => 'Route customer based on status from check_customer. Do NOT speak - just route silently.'],
    [
        edge('edge_routing_known', 'Bekannter Kunde', 'node_03a_known_customer', 'equation', null, [
            ['left' => 'customer_status', 'operator' => '==', 'right' => 'found']
        ]),
        edge('edge_routing_new', 'Neuer Kunde', 'node_03b_new_customer', 'equation', null, [
            ['left' => 'customer_status', 'operator' => '==', 'right' => 'new_customer']
        ]),
        edge('edge_routing_anonymous', 'Anonymer Kunde', 'node_03c_anonymous_customer', 'equation', null, [
            ['left' => 'customer_status', 'operator' => '==', 'right' => 'anonymous']
        ])
    ]
);

$nodes[] = node(
    'node_03a_known_customer',
    'Bekannter Kunde Begrüßung',
    'conversation',
    ['type' => 'prompt', 'text' => 'Greet by first name warmly. Example: "Schön Sie wiederzuhören, {{customer_name}}!"'],
    [edge('edge_known_intent', 'Greeting done', 'node_04_intent_enhanced')]
);

$nodes[] = node(
    'node_03b_new_customer',
    'Neuer Kunde Begrüßung',
    'conversation',
    ['type' => 'prompt', 'text' => 'Greet warmly and introduce yourself. Example: "Herzlich willkommen bei Ask Pro AI!"'],
    [edge('edge_new_intent', 'Greeting done', 'node_04_intent_enhanced')]
);

$nodes[] = node(
    'node_03c_anonymous_customer',
    'Anonymer Kunde Begrüßung',
    'conversation',
    ['type' => 'prompt', 'text' => 'Greet professionally: "Guten Tag! Wie kann ich Ihnen helfen?"'],
    [edge('edge_anon_name', 'Need name', 'node_05_name_collection')]
);

$nodes[] = node(
    'node_05_name_collection',
    'Namen erfragen',
    'conversation',
    ['type' => 'prompt', 'text' => 'Ask politely: "Darf ich zunächst Ihren Namen erfragen?"'],
    [edge('edge_name_intent', 'Name collected', 'node_04_intent_enhanced')]
);

// PHASE 3: Enhanced Intent Recognition
// ============================================================

$nodes[] = node(
    'node_04_intent_enhanced',
    'Intent-Erkennung',
    'conversation',
    ['type' => 'prompt', 'text' => <<<'INTENT'
Erkenne die Absicht des Kunden. Frage bei Unklarheit IMMER nach:

"Möchten Sie einen NEUEN Termin vereinbaren oder einen BESTEHENDEN Termin ändern?"

Intents:
1. Neuer Termin → node_06_service_selection
2. Termin verschieben → func_get_appointments (dann reschedule flow)
3. Termin stornieren → func_get_appointments (dann cancel flow)
4. Termin abfragen → func_get_appointments

Achte auf Schlüsselwörter:
- "buchen", "vereinbaren", "neu" → NEUER Termin
- "verschieben", "umbuchen", "ändern" → VERSCHIEBUNG
- "absagen", "stornieren", "cancel" → STORNIERUNG
- "wann", "welcher", "habe ich" → ABFRAGE
INTENT
    ],
    [
        edge('edge_intent_new', 'Neuer Termin', 'node_06_service_selection'),
        edge('edge_intent_modify', 'Termin ändern/abfragen', 'func_get_appointments')
    ]
);

// Get appointments function node (used by all modify/cancel/lookup flows)
$nodes[] = node(
    'func_get_appointments',
    'Termine abrufen',
    'function',
    ['type' => 'static_text', 'text' => 'Einen Moment, ich schaue nach Ihren Terminen...'],
    [edge('edge_appointments_display', 'Appointments retrieved', 'node_appointments_display')],
    ['tool_id' => 'tool-get-customer-appointments', 'tool_type' => 'local', 'speak_during_execution' => true, 'wait_for_result' => true]
);

$nodes[] = node(
    'node_appointments_display',
    'Termine anzeigen',
    'conversation',
    ['type' => 'prompt', 'text' => <<<'DISPLAY'
Liste alle Termine auf (wenn vorhanden):

Falls KEINE Termine:
"Sie haben aktuell keine bevorstehenden Termine. Möchten Sie einen neuen Termin vereinbaren?"
→ Zu node_06_service_selection

Falls Termine vorhanden:
"Ich habe folgende Termine für Sie:
1. [Datum], [Uhrzeit] - [Service]
2. ...

Was möchten Sie mit diesen Terminen tun?"

Optionen anbieten:
- Einen Termin verschieben
- Einen Termin absagen
- Neuen Termin buchen
DISPLAY
    ],
    [
        edge('edge_display_reschedule', 'Verschieben', 'node_reschedule_identify'),
        edge('edge_display_cancel', 'Stornieren', 'node_cancel_identify'),
        edge('edge_display_new', 'Neu buchen', 'node_06_service_selection'),
        edge('edge_display_polite', 'Abbruch', 'node_98_polite_goodbye')
    ]
);

// PHASE 4: NEW BOOKING FLOW (existing)
// ============================================================

$nodes[] = node(
    'node_06_service_selection',
    'Dienstleistung auswählen',
    'conversation',
    ['type' => 'prompt', 'text' => 'Ask: "Für welche Dienstleistung möchten Sie einen Termin? Wir bieten Beratung, Behandlung und Check-ups an."'],
    [edge('edge_service_datetime', 'Service selected', 'node_07_datetime_collection')]
);

$nodes[] = node(
    'node_07_datetime_collection',
    'Datum & Zeit erfragen',
    'conversation',
    ['type' => 'prompt', 'text' => 'CRITICAL: NEVER invent date/time! Ask: "Für welchen Tag und welche Uhrzeit?" Parse relative dates using current_time_berlin data. ALWAYS confirm: "Das wäre {{weekday}}, der {{date}} um {{time}} Uhr. Richtig?"'],
    [edge('edge_datetime_valid', 'Datum und Zeit erfasst', 'func_08_availability_check')]
);

$nodes[] = node(
    'func_08_availability_check',
    'Verfügbarkeit prüfen',
    'function',
    ['type' => 'static_text', 'text' => 'Einen Moment, ich prüfe die Verfügbarkeit.'],
    [
        edge('edge_avail_available', 'Slot verfügbar', 'node_09a_booking_confirmation'),
        edge('edge_avail_notavailable', 'Nicht verfügbar', 'node_09b_alternative_offering')
    ],
    ['tool_id' => 'tool-collect-appointment-data', 'tool_type' => 'local', 'speak_during_execution' => true, 'wait_for_result' => true]
);

$nodes[] = node(
    'node_09a_booking_confirmation',
    'Buchungsbestätigung',
    'conversation',
    ['type' => 'prompt', 'text' => 'Confirm booking: "Der Termin am {{date}} um {{time}} Uhr ist verfügbar. Soll ich diesen verbindlich für Sie buchen?"'],
    [
        edge('edge_confirm_yes', 'Ja buchen', 'func_09c_final_booking'),
        edge('edge_confirm_no', 'Nein abbrechen', 'node_98_polite_goodbye')
    ]
);

$nodes[] = node(
    'node_09b_alternative_offering',
    'Alternativen anbieten',
    'conversation',
    ['type' => 'prompt', 'text' => 'Use alternatives from function result. "Dieser Termin ist leider nicht verfügbar. Ich kann Ihnen folgende Alternativen anbieten: [Liste]. Passt einer davon?"'],
    [
        edge('edge_alt_yes', 'Alternative gewählt', 'func_08_availability_check'),
        edge('edge_alt_no', 'Keine passt', 'node_98_polite_goodbye')
    ]
);

$nodes[] = node(
    'func_09c_final_booking',
    'Termin buchen',
    'function',
    ['type' => 'static_text', 'text' => 'Ich buche den Termin für Sie...'],
    [
        edge('edge_booking_success', 'Erfolg', 'node_14_success_goodbye'),
        edge('edge_booking_race', 'Race Condition', 'node_15_race_condition_handler')
    ],
    ['tool_id' => 'tool-collect-appointment-data', 'tool_type' => 'local', 'speak_during_execution' => true, 'wait_for_result' => true]
);

$nodes[] = node(
    'node_14_success_goodbye',
    'Erfolgsbestätigung',
    'conversation',
    ['type' => 'prompt', 'text' => 'Confirm success warmly: "Perfekt! Ihr Termin am {{date}} um {{time}} Uhr ist gebucht. Sie erhalten eine Bestätigung per E-Mail. Vielen Dank und bis bald!"'],
    [],
    ['skip_response_edge' => edge('edge_success_end', 'End call', 'end_node_success', 'prompt', 'Skip response')]
);

$nodes[] = node(
    'node_15_race_condition_handler',
    'Race Condition Handler',
    'conversation',
    ['type' => 'prompt', 'text' => 'Explain race condition empathetically: "Entschuldigung, dieser Termin wurde gerade eben von jemand anderem gebucht. Darf ich Ihnen einen der Alternativtermine anbieten?"'],
    [edge('edge_race_alternatives', 'Alternativen anbieten', 'node_09b_alternative_offering')]
);

// PHASE 5: RESCHEDULE FLOW
// ============================================================

$nodes[] = node(
    'node_reschedule_identify',
    'Termin für Verschiebung identifizieren',
    'conversation',
    ['type' => 'prompt', 'text' => 'Ask: "Welchen Termin möchten Sie verschieben? Bitte nennen Sie mir das Datum." If customer doesn\'t know: Reference the appointments just listed.'],
    [edge('edge_reschedule_datetime', 'Termin identifiziert', 'node_reschedule_datetime')]
);

$nodes[] = node(
    'node_reschedule_datetime',
    'Neues Datum/Zeit für Verschiebung',
    'conversation',
    ['type' => 'prompt', 'text' => 'Ask: "Auf welches Datum und welche Uhrzeit möchten Sie den Termin verschieben?"'],
    [edge('edge_reschedule_execute', 'Neues Datum erfasst', 'func_reschedule_execute')]
);

$nodes[] = node(
    'func_reschedule_execute',
    'Verschiebung durchführen',
    'function',
    ['type' => 'static_text', 'text' => 'Einen Moment, ich verschiebe Ihren Termin...'],
    [
        edge('edge_reschedule_success', 'Erfolg', 'node_reschedule_success'),
        edge('edge_reschedule_policy', 'Policy Violation', 'node_policy_violation_handler'),
        edge('edge_reschedule_error', 'Fehler', 'node_99_error_goodbye')
    ],
    ['tool_id' => 'tool-reschedule-appointment', 'tool_type' => 'local', 'speak_during_execution' => true, 'wait_for_result' => true]
);

$nodes[] = node(
    'node_reschedule_success',
    'Verschiebung erfolgreich',
    'conversation',
    ['type' => 'prompt', 'text' => 'Confirm: "Perfekt! Ihr Termin wurde erfolgreich auf {{new_date}} um {{new_time}} Uhr verschoben. Sie erhalten eine Bestätigung per E-Mail. Gibt es noch etwas?"'],
    [
        edge('edge_reschedule_done', 'Fertig', 'node_98_polite_goodbye'),
        edge('edge_reschedule_more', 'Weiterer Service', 'node_04_intent_enhanced')
    ]
);

// PHASE 6: CANCELLATION FLOW
// ============================================================

$nodes[] = node(
    'node_cancel_identify',
    'Termin für Stornierung identifizieren',
    'conversation',
    ['type' => 'prompt', 'text' => 'Ask: "Welchen Termin möchten Sie absagen? Bitte nennen Sie mir das Datum." If customer doesn\'t know: Reference the appointments just listed.'],
    [edge('edge_cancel_confirm', 'Termin identifiziert', 'node_cancel_confirmation')]
);

$nodes[] = node(
    'node_cancel_confirmation',
    'Stornierung bestätigen',
    'conversation',
    ['type' => 'prompt', 'text' => 'Ask for confirmation: "Möchten Sie den Termin am {{date}} um {{time}} Uhr wirklich absagen?"'],
    [
        edge('edge_cancel_yes', 'Ja absagen', 'func_cancel_execute'),
        edge('edge_cancel_no', 'Nein behalten', 'node_98_polite_goodbye')
    ]
);

$nodes[] = node(
    'func_cancel_execute',
    'Stornierung durchführen',
    'function',
    ['type' => 'static_text', 'text' => 'Einen Moment, ich storniere den Termin...'],
    [
        edge('edge_cancel_success', 'Erfolg', 'node_cancel_success'),
        edge('edge_cancel_policy', 'Policy Violation', 'node_policy_violation_handler'),
        edge('edge_cancel_error', 'Fehler', 'node_99_error_goodbye')
    ],
    ['tool_id' => 'tool-cancel-appointment', 'tool_type' => 'local', 'speak_during_execution' => true, 'wait_for_result' => true]
);

$nodes[] = node(
    'node_cancel_success',
    'Stornierung erfolgreich',
    'conversation',
    ['type' => 'prompt', 'text' => 'Confirm empathetically: "Ihr Termin am {{date}} um {{time}} Uhr wurde storniert. Sie erhalten eine Bestätigungs-E-Mail. Möchten Sie einen neuen Termin vereinbaren?"'],
    [
        edge('edge_cancel_newbooking', 'Neuer Termin', 'node_06_service_selection'),
        edge('edge_cancel_done', 'Nein danke', 'node_98_polite_goodbye')
    ]
);

// PHASE 7: EDGE CASES & ERROR HANDLING
// ============================================================

$nodes[] = node(
    'node_policy_violation_handler',
    'Policy Verletzung',
    'conversation',
    ['type' => 'prompt', 'text' => <<<'POLICY'
Handle policy violations empathetically. Use the error message from the function.

Example:
"Ich verstehe Ihre Situation. Leider können wir kurzfristige {{action}} nur bis {{deadline}} vornehmen.

Sie haben folgende Möglichkeiten:
1. {{option_1}}
2. {{option_2}}

Was möchten Sie tun?"

Always offer alternatives, never just say "nicht möglich".
POLICY
    ],
    [
        edge('edge_policy_alt1', 'Alternative 1', 'node_06_service_selection'),
        edge('edge_policy_abort', 'Abbrechen', 'node_98_polite_goodbye')
    ]
);

$nodes[] = node(
    'node_98_polite_goodbye',
    'Höfliche Verabschiedung',
    'conversation',
    ['type' => 'prompt', 'text' => 'Say: "Kein Problem! Rufen Sie gerne jederzeit wieder an wenn Sie einen Termin benötigen. Ich wünsche Ihnen noch einen schönen Tag. Auf Wiederhören!"'],
    [],
    ['skip_response_edge' => edge('edge_polite_end', 'End call', 'end_node_polite', 'prompt', 'Skip response')]
);

$nodes[] = node(
    'node_99_error_goodbye',
    'Fehlerbehandlung',
    'conversation',
    ['type' => 'prompt', 'text' => 'Apologize professionally: "Entschuldigung, es gab einen technischen Fehler. Bitte rufen Sie uns direkt an unter {{phone_number}} oder versuchen Sie es später nochmal. Vielen Dank für Ihr Verständnis."'],
    [],
    ['skip_response_edge' => edge('edge_error_end', 'End call', 'end_node_error', 'prompt', 'Skip response')]
);

// END NODES
// ============================================================

$nodes[] = node(
    'end_node_success',
    'Anruf beenden (Erfolg)',
    'end',
    ['type' => 'prompt', 'text' => 'Politely end the call']
);

$nodes[] = node(
    'end_node_polite',
    'Anruf beenden (Höflich)',
    'end',
    ['type' => 'prompt', 'text' => 'Politely end the call']
);

$nodes[] = node(
    'end_node_error',
    'Anruf beenden (Fehler)',
    'end',
    ['type' => 'prompt', 'text' => 'Politely end the call']
);

$flow['nodes'] = $nodes;

echo "✓ Nodes created: " . count($nodes) . "\n\n";

// ================================================================
// SAVE FLOW
// ================================================================

$outputFile = 'public/askproai_conversation_flow_complete.json';
file_put_contents(
    $outputFile,
    json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo "=== FLOW SAVED ===\n";
echo "File: $outputFile\n";
echo "Size: " . round(filesize($outputFile) / 1024, 2) . " KB\n\n";

echo "=== STATISTICS ===\n";
echo "Tools: " . count($flow['tools']) . "\n";
echo "Nodes: " . count($flow['nodes']) . "\n";

$nodeTypes = [];
foreach ($flow['nodes'] as $node) {
    $type = $node['type'];
    $nodeTypes[$type] = ($nodeTypes[$type] ?? 0) + 1;
}

foreach ($nodeTypes as $type => $count) {
    echo "  - $type: $count\n";
}

echo "\n✅ COMPLETE FLOW READY!\n";
