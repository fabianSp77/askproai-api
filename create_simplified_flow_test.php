#!/usr/bin/env php
<?php

/**
 * SIMPLIFIED FLOW - Test Version
 *
 * Creates a NEW conversation flow with UNCONDITIONAL transitions
 * Does NOT modify existing flow!
 * Can easily switch back if it doesn't work
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🧪 CREATE SIMPLIFIED FLOW (TEST VERSION)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$simplifiedFlow = [
    'global_prompt' => "# Friseur 1 - Simplified Voice AI

Du bist Carola, Terminassistentin von Friseur 1.

## Deine Aufgabe
1. Begrüße den Kunden
2. Sammle: Service, Datum, Uhrzeit (Name ist optional wenn Kunde bekannt)
3. Das System prüft AUTOMATISCH die Verfügbarkeit
4. Du präsentierst das Ergebnis
5. Bei Bestätigung bucht das System AUTOMATISCH

## Services
- Herrenhaarschnitt (~30-45 Min)
- Damenhaarschnitt (~45-60 Min)
- Kinderhaarschnitt (~20-30 Min)
- Ansatzfärbung mit waschen, schneiden, föhnen (~2.5h)

## WICHTIG
- Sammle ALLE Daten in EINEM Schritt (nicht einzeln fragen)
- KEINE unnötigen Wiederholungen
- Kurze, natürliche Antworten
- Wenn Kunde sagt \"Herrenhaarschnitt morgen 9 Uhr, Hans Schuster\" → Du hast ALLES!

## Current Time
Nutze current_time_berlin() für Datumsberechnungen.
",

    'tools' => [
        [
            'tool_id' => 'tool-init-v2',
            'type' => 'custom',
            'timeout_ms' => 2000,
            'name' => 'initialize_call',
            'url' => 'https://api.askproai.de/api/retell/initialize-call',
            'description' => 'Background initialization. Runs silently.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'phone_number' => [
                        'type' => 'string',
                        'description' => 'Caller phone number'
                    ]
                ],
                'required' => []
            ]
        ],
        [
            'tool_id' => 'tool-check-v2',
            'type' => 'custom',
            'timeout_ms' => 5000,
            'name' => 'check_availability_v17',
            'url' => 'https://api.askproai.de/api/retell/v17/check-availability',
            'description' => 'Check appointment availability. Called automatically after data collection.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'description' => 'Customer name'
                    ],
                    'datum' => [
                        'type' => 'string',
                        'description' => 'Date in YYYY-MM-DD format'
                    ],
                    'uhrzeit' => [
                        'type' => 'string',
                        'description' => 'Time in HH:MM format'
                    ],
                    'dienstleistung' => [
                        'type' => 'string',
                        'description' => 'Service name'
                    ],
                    'mitarbeiter' => [
                        'type' => 'string',
                        'description' => 'Optional staff member name'
                    ],
                    'bestaetigung' => [
                        'type' => 'boolean',
                        'description' => 'false = just check, true = book'
                    ]
                ],
                'required' => ['datum', 'uhrzeit', 'dienstleistung']
            ]
        ],
        [
            'tool_id' => 'tool-book-v2',
            'type' => 'custom',
            'timeout_ms' => 5000,
            'name' => 'book_appointment_v17',
            'url' => 'https://api.askproai.de/api/retell/v17/book-appointment',
            'description' => 'Book appointment. Called automatically after customer confirmation.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'datum' => ['type' => 'string'],
                    'uhrzeit' => ['type' => 'string'],
                    'dienstleistung' => ['type' => 'string'],
                    'mitarbeiter' => ['type' => 'string'],
                    'bestaetigung' => ['type' => 'boolean', 'description' => 'Always true for booking']
                ],
                'required' => ['datum', 'uhrzeit', 'dienstleistung', 'bestaetigung']
            ]
        ]
    ],

    'nodes' => [
        // Node 1: Initialize (silent, parallel)
        [
            'id' => 'init_silent',
            'type' => 'function',
            'name' => 'Silent Init',
            'tool_id' => 'tool-init-v2',
            'tool_type' => 'local',
            'wait_for_result' => false,  // Don't wait
            'speak_during_execution' => false,  // Silent
            'instruction' => [
                'type' => 'static_text',
                'text' => 'Guten Tag bei Friseur 1, mein Name ist Carola. Wie kann ich Ihnen helfen?'
            ],
            'edges' => [
                [
                    'id' => 'edge_init_to_collect',
                    'destination_node_id' => 'collect_info',
                    'transition_condition' => [
                        'type' => 'expression',
                        'expression' => 'true'  // UNCONDITIONAL - always transition
                    ]
                ]
            ]
        ],

        // Node 2: Collect all appointment info
        [
            'id' => 'collect_info',
            'type' => 'conversation',
            'name' => 'Collect Appointment Info',
            'instruction' => [
                'type' => 'prompt',
                'text' => 'Sammle: Service, Datum, Uhrzeit, Name (falls nicht bekannt). Fasse Fragen zusammen. Wenn alles da ist, sage \"Einen Moment bitte, ich prüfe die Verfügbarkeit...\" und warte.'
            ],
            'edges' => [
                [
                    'id' => 'edge_collect_to_check',
                    'destination_node_id' => 'check_avail',
                    'transition_condition' => [
                        'type' => 'expression',
                        'expression' => 'true'  // UNCONDITIONAL - always go to check
                    ]
                ]
            ]
        ],

        // Node 3: Check availability (EXPLICIT function node)
        [
            'id' => 'check_avail',
            'type' => 'function',
            'name' => 'Check Availability',
            'tool_id' => 'tool-check-v2',
            'tool_type' => 'local',
            'wait_for_result' => true,  // Wait for response
            'speak_during_execution' => true,  // AI can speak while waiting
            'speak_after_execution' => false,
            'edges' => [
                [
                    'id' => 'edge_check_success',
                    'destination_node_id' => 'present_result',
                    'transition_condition' => [
                        'type' => 'expression',
                        'expression' => 'true'  // UNCONDITIONAL
                    ]
                ]
            ]
        ],

        // Node 4: Present result
        [
            'id' => 'present_result',
            'type' => 'conversation',
            'name' => 'Present Result',
            'instruction' => [
                'type' => 'prompt',
                'text' => 'Präsentiere das Verfügbarkeits-Ergebnis. Bei Verfügbarkeit: \"Der Termin ist verfügbar. Soll ich das so buchen?\" Bei Nicht-Verfügbarkeit: Zeige Alternativen.'
            ],
            'edges' => [
                [
                    'id' => 'edge_to_confirm',
                    'destination_node_id' => 'wait_confirm',
                    'transition_condition' => [
                        'type' => 'prompt',
                        'prompt' => 'Customer responded'
                    ]
                ]
            ]
        ],

        // Node 5: Wait for confirmation
        [
            'id' => 'wait_confirm',
            'type' => 'conversation',
            'name' => 'Wait for Confirmation',
            'instruction' => [
                'type' => 'prompt',
                'text' => 'Warte auf Kunden-Antwort. Bei \"Ja\" oder Bestätigung gehe zu Buchung. Bei \"Nein\" oder Ablehnung gehe zu Ende.'
            ],
            'edges' => [
                [
                    'id' => 'edge_confirm_yes',
                    'destination_node_id' => 'book_appt',
                    'transition_condition' => [
                        'type' => 'prompt',
                        'prompt' => 'Customer confirmed booking'
                    ]
                ],
                [
                    'id' => 'edge_confirm_no',
                    'destination_node_id' => 'end_node',
                    'transition_condition' => [
                        'type' => 'prompt',
                        'prompt' => 'Customer declined or wants changes'
                    ]
                ]
            ]
        ],

        // Node 6: Book appointment
        [
            'id' => 'book_appt',
            'type' => 'function',
            'name' => 'Book Appointment',
            'tool_id' => 'tool-book-v2',
            'tool_type' => 'local',
            'wait_for_result' => true,
            'speak_during_execution' => true,
            'speak_after_execution' => false,
            'edges' => [
                [
                    'id' => 'edge_book_done',
                    'destination_node_id' => 'end_node',
                    'transition_condition' => [
                        'type' => 'expression',
                        'expression' => 'true'
                    ]
                ]
            ]
        ],

        // Node 7: End
        [
            'id' => 'end_node',
            'type' => 'end_call',
            'name' => 'End Call',
            'instruction' => [
                'type' => 'prompt',
                'text' => 'Verabschiede dich höflich und beende das Gespräch.'
            ]
        ]
    ],

    'start_node_id' => 'init_silent'
];

echo "Creating simplified conversation flow...\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-conversation-flow', $simplifiedFlow);

if ($response->successful()) {
    $flowData = $response->json();
    $flowId = $flowData['conversation_flow_id'];

    echo "✅ Simplified Flow Created!\n\n";
    echo "Flow ID: $flowId\n";
    echo "Version: {$flowData['version']}\n";
    echo "Tools: " . count($flowData['tools']) . "\n";
    echo "Nodes: " . count($flowData['nodes']) . "\n\n";

    // Save flow ID for later use
    file_put_contents(__DIR__ . '/simplified_flow_id.txt', $flowId);

    echo "═══════════════════════════════════════════════════════════\n";
    echo "NEXT STEPS\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "Option 1: Create NEW Agent with Simplified Flow\n";
    echo "  → Safest, keep old agent as backup\n";
    echo "  → Run: php create_agent_with_simplified_flow.php\n\n";

    echo "Option 2: Update EXISTING Agent (agent_2d467d84eb674e5b3f5815d81c)\n";
    echo "  → Faster, but old flow is lost\n";
    echo "  → Can revert by switching back to old flow ID\n\n";

    echo "OLD Flow ID: conversation_flow_134a15784642\n";
    echo "NEW Flow ID: $flowId\n\n";

    echo "Which would you prefer?\n";

} else {
    echo "❌ Failed to create flow\n";
    echo "HTTP {$response->status()}\n";
    echo "Response: {$response->body()}\n";
    exit(1);
}
