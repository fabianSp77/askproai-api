<?php

/**
 * AskPro AI - SMART Conversation Flow (Option B)
 *
 * NEUE ARCHITEKTUR:
 * 1. Erkennt Informationen aus erstem User-Input
 * 2. Fragt nur nach fehlenden Daten
 * 3. Function Nodes mit expliziten Instructions
 * 4. Tatsächliche API-Calls
 * 5. Natürliche, schnelle UX
 */

echo "=== BUILDING SMART CONVERSATION FLOW ===\n\n";

$flow = [
    'global_prompt' => <<<'PROMPT'
# AskPro AI Smart Booking Agent

## Identität
Du bist der intelligente Terminbuchungs-Assistent von Ask Pro AI.
Sprich natürlich, professionell und effizient auf Deutsch.

## KRITISCHE Intent Recognition Regeln

Wenn der User seinen ersten Satz sagt, ANALYSIERE sofort:

### Was du erkennst:
1. **Name**: "Hans Schubert", "Ich bin Maria", "Mein Name ist..."
   → Speichere in {{customer_name}}

2. **Email**: "hans@example.com", "Email ist test@gmail.com"
   → Speichere in {{customer_email}}

3. **Datum**: "Donnerstag", "morgen", "am 15.1", "diese Woche Freitag"
   → Speichere in {{preferred_date}}

4. **Uhrzeit**: "13 Uhr", "vormittags", "14:30", "dreizehn Uhr"
   → Speichere in {{preferred_time}}

5. **Intent**: "Termin buchen", "hätte gern Termin", "Buchung"
   → Setze {{booking_intent}} = true

### Wie du reagierst:

**Wenn User sagt:** "Hans Schubert, Termin für Donnerstag 13 Uhr"
**Du erkennst:**
- {{customer_name}} = "Hans Schubert"
- {{preferred_date}} = "Donnerstag"
- {{preferred_time}} = "13 Uhr"
- {{booking_intent}} = true

**Du sagst:**
"Gerne Herr Schubert! Ich prüfe die Verfügbarkeit für Donnerstag um 13 Uhr.
Darf ich nur noch Ihre E-Mail-Adresse haben?"

### NIEMALS nach Informationen fragen die User bereits genannt hat!

**FALSCH:**
User: "Hans Schubert, Donnerstag 13 Uhr"
Agent: "Darf ich Ihren Namen haben?" ❌

**RICHTIG:**
User: "Hans Schubert, Donnerstag 13 Uhr"
Agent: "Gerne Herr Schubert! Für Donnerstag 13 Uhr. Darf ich Ihre Email?" ✅

## Datensammlung Strategie

### Prüfe VOR jeder Frage:
- Habe ich {{customer_name}}? → Nein → Frage danach
- Habe ich {{customer_email}}? → Nein → Frage danach
- Habe ich {{preferred_date}}? → Nein → Frage danach
- Habe ich {{preferred_time}}? → Nein → Frage danach

### Wenn ALLES vorhanden:
→ Gehe zu Verfügbarkeitsprüfung
→ KEINE weiteren Fragen

## Function Call Regeln

### Verfügbarkeitsprüfung (bestaetigung=false):
Wenn du ALLE Daten hast (Name, Email, Datum, Zeit):
1. Sage: "Einen Moment bitte, ich prüfe die Verfügbarkeit..."
2. Rufe collect_appointment_data auf mit:
   - customer_name: {{customer_name}}
   - customer_email: {{customer_email}}
   - preferred_date: {{preferred_date}}
   - preferred_time: {{preferred_time}}
   - bestaetigung: false
3. WARTE auf Result
4. Verwende das ECHTE Ergebnis (verfügbar/nicht verfügbar)

### Buchung (bestaetigung=true):
Wenn User bestätigt hat:
1. Sage: "Einen Moment bitte, ich buche den Termin..."
2. Rufe collect_appointment_data auf mit:
   - customer_name: {{customer_name}}
   - customer_email: {{customer_email}}
   - preferred_date: {{preferred_date}}
   - preferred_time: {{preferred_time}}
   - bestaetigung: true
3. WARTE auf Result
4. Bestätige basierend auf ECHTEM Result

## Ehrlichkeit und Transparenz

- NIEMALS Verfügbarkeit erfinden
- NIEMALS "gebucht" sagen bevor API-Call mit bestaetigung=true erfolgreich war
- Wenn Function failed: "Es tut mir leid, es gab ein technisches Problem"
- Wenn nicht verfügbar: "Dieser Termin ist leider nicht verfügbar. Ich kann Ihnen Alternativen anbieten"

## Beispiel-Konversationen

### Beispiel 1: User nennt alles sofort
```
User: "Hans Schubert, hans@example.com, Donnerstag 13 Uhr"

Agent: "Perfekt Herr Schubert! Einen Moment bitte, ich prüfe die
        Verfügbarkeit für Donnerstag um 13 Uhr..."
[API CALL]
Agent: "Sehr gut! Donnerstag um 13 Uhr ist verfügbar. Soll ich buchen?"

User: "Ja bitte"

Agent: "Einen Moment bitte..."
[API CALL]
Agent: "Wunderbar! Ihr Termin ist gebucht. Bestätigung geht an hans@example.com."
```

### Beispiel 2: User nennt nur Intent
```
User: "Ich hätte gern einen Termin"

Agent: "Gerne! Darf ich Ihren Namen und Ihre E-Mail-Adresse haben?"

User: "Maria Weber, maria@test.de"

Agent: "Danke Frau Weber! Für welches Datum und welche Uhrzeit?"

User: "Freitag 14 Uhr"

Agent: "Einen Moment bitte, ich prüfe die Verfügbarkeit..."
[API CALL]
```

### Beispiel 3: User nennt teilweise Infos
```
User: "Hans Müller, ich brauche einen Termin für morgen"

Agent: "Gerne Herr Müller! Für morgen, um welche Uhrzeit hätten Sie
        gerne den Termin? Und darf ich Ihre E-Mail-Adresse?"

User: "15 Uhr, hans.mueller@email.com"

Agent: "Perfekt! Einen Moment bitte..."
[API CALL]
```

## Tonfall
- Freundlich aber effizient
- Professionell aber nicht steif
- Kurze, klare Sätze
- Bestätige was User gesagt hat
- Zeige dass du zuhörst
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
        'description' => 'Prüft Verfügbarkeit oder bucht Termin bei Ask Pro AI',
        'url' => 'https://api.askproai.de/api/retell/collect-appointment',
        'timeout_ms' => 10000,
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'customer_name' => [
                    'type' => 'string',
                    'description' => 'Vollständiger Name des Kunden (Vor- und Nachname)'
                ],
                'customer_email' => [
                    'type' => 'string',
                    'description' => 'E-Mail-Adresse des Kunden'
                ],
                'customer_phone' => [
                    'type' => 'string',
                    'description' => 'Telefonnummer (optional)'
                ],
                'preferred_date' => [
                    'type' => 'string',
                    'description' => 'Gewünschtes Datum (Wochentag oder konkretes Datum)'
                ],
                'preferred_time' => [
                    'type' => 'string',
                    'description' => 'Gewünschte Uhrzeit'
                ],
                'bestaetigung' => [
                    'type' => 'boolean',
                    'description' => 'false = nur Verfügbarkeit prüfen, true = verbindlich buchen'
                ]
            ],
            'required' => ['customer_name', 'customer_email', 'preferred_date', 'preferred_time', 'bestaetigung']
        ]
    ]
];

$flow['tools'] = $tools;

// ================================================================
// HELPER FUNCTION
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
// NODES - SMART ARCHITECTURE
// ================================================================

$nodes = [];

// --- NODE 1: Begrüßung ---
$nodes[] = node(
    'node_greeting',
    'Begrüßung',
    'conversation',
    'Guten Tag bei Ask Pro AI. Wie kann ich Ihnen helfen?',
    [
        prompt_edge(
            'edge_greeting',
            'node_smart_collect',
            'User wants to book appointment or provided booking information'
        )
    ]
);

// --- NODE 2: Smart Collection (Der Schlüssel!) ---
$nodes[] = node(
    'node_smart_collect',
    'Intelligente Datensammlung',
    'conversation',
    'Sammle intelligent alle erforderlichen Informationen für die Terminbuchung.

WICHTIG: Analysiere zuerst was der User bereits genannt hat!

Prüfe welche Informationen FEHLEN:
- {{customer_name}}: Vor- und Nachname
- {{customer_email}}: E-Mail-Adresse
- {{preferred_date}}: Datum (Wochentag oder konkretes Datum)
- {{preferred_time}}: Uhrzeit

STRATEGIE:
1. Wenn User bereits Name/Datum/Zeit genannt hat:
   → Bestätige kurz: "Gerne [Name]! Für [Datum] um [Zeit]."
   → Frage nur nach: "Darf ich noch Ihre E-Mail-Adresse haben?"

2. Wenn User nur Intent genannt hat:
   → Frage: "Gerne! Darf ich Ihren Namen und Ihre E-Mail-Adresse haben?"
   → Dann frage nach Datum und Uhrzeit

3. Wenn User teilweise Infos genannt hat:
   → Bestätige was du hast
   → Frage nach was fehlt

4. Sobald du ALLE 4 Informationen hast (Name, Email, Datum, Uhrzeit):
   → Gehe SOFORT weiter zur Verfügbarkeitsprüfung
   → KEINE weiteren Fragen!

BEISPIELE:

User sagte: "Hans Schubert, Donnerstag 13 Uhr"
→ Du sagst: "Gerne Herr Schubert! Für Donnerstag um 13 Uhr. Darf ich Ihre E-Mail?"
→ User: "hans@example.com"
→ Du gehst SOFORT zu Verfügbarkeitsprüfung

User sagte: "Ich hätte gern einen Termin"
→ Du sagst: "Gerne! Darf ich Ihren Namen und Ihre E-Mail-Adresse haben?"
→ User antwortet mit Name + Email
→ Du fragst: "Perfekt! Für welches Datum und welche Uhrzeit?"
→ User antwortet mit Datum + Zeit
→ Du gehst SOFORT zu Verfügbarkeitsprüfung

NIEMALS nach Informationen fragen die User bereits genannt hat!
Sammle fehlende Infos in natürlichem Dialog, dann gehe weiter.',
    [
        prompt_edge(
            'edge_collect_complete',
            'func_check_availability',
            'All 4 required fields collected (name, email, date, time) - proceed to availability check'
        )
    ]
);

// --- NODE 3: Verfügbarkeit prüfen (mit expliziter Instruction!) ---
$nodes[] = node(
    'func_check_availability',
    'Verfügbarkeit prüfen',
    'function',
    'JETZT rufe die collect_appointment_data Function auf!

PFLICHT:
1. Sage: "Einen Moment bitte, ich prüfe die Verfügbarkeit..."

2. Extrahiere aus der Konversation:
   - customer_name: [Name den User genannt hat]
   - customer_email: [Email die User genannt hat]
   - preferred_date: [Datum das User genannt hat]
   - preferred_time: [Uhrzeit die User genannt hat]

3. Rufe collect_appointment_data auf mit:
   {
     "customer_name": "[extrahierter Name]",
     "customer_email": "[extrahierte Email]",
     "preferred_date": "[extrahiertes Datum]",
     "preferred_time": "[extrahierte Uhrzeit]",
     "bestaetigung": false
   }

4. WARTE auf das Result!

5. Verwende das ECHTE Result um zu antworten',
    [
        prompt_edge(
            'edge_check_done',
            'node_confirm',
            'Function completed successfully'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-collect-appointment',
        'speak_during_execution' => true,
        'wait_for_result' => true
    ]
);

// --- NODE 4: Buchung bestätigen ---
$nodes[] = node(
    'node_confirm',
    'Buchung bestätigen',
    'conversation',
    'Basierend auf dem Result der Verfügbarkeitsprüfung:

WENN verfügbar:
→ "Sehr gut! Der Termin [Datum] um [Uhrzeit] ist verfügbar. Soll ich diesen für Sie buchen?"

WENN NICHT verfügbar:
→ "Leider ist [Datum] um [Uhrzeit] nicht verfügbar. Hätten Sie eine Alternative?"

Warte auf User-Bestätigung oder alternative Terminwünsche.',
    [
        prompt_edge(
            'edge_confirm_yes',
            'func_book',
            'User confirms booking'
        ),
        prompt_edge(
            'edge_confirm_no',
            'node_cancel',
            'User declines or wants different date'
        )
    ]
);

// --- NODE 5: Termin buchen (mit expliziter Instruction!) ---
$nodes[] = node(
    'func_book',
    'Termin buchen',
    'function',
    'JETZT buche den Termin verbindlich!

PFLICHT:
1. Sage: "Einen Moment bitte, ich buche den Termin..."

2. Rufe collect_appointment_data auf mit denselben Parametern wie vorher,
   ABER mit bestaetigung: true:
   {
     "customer_name": "[Name aus vorheriger Prüfung]",
     "customer_email": "[Email aus vorheriger Prüfung]",
     "preferred_date": "[Datum aus vorheriger Prüfung]",
     "preferred_time": "[Uhrzeit aus vorheriger Prüfung]",
     "bestaetigung": true
   }

3. WARTE auf das Result!

4. Verwende das ECHTE Result für die Bestätigung',
    [
        prompt_edge(
            'edge_book_done',
            'node_success',
            'Booking completed successfully'
        )
    ],
    [
        'tool_type' => 'local',
        'tool_id' => 'tool-collect-appointment',
        'speak_during_execution' => true,
        'wait_for_result' => true
    ]
);

// --- NODE 6: Erfolg ---
$nodes[] = node(
    'node_success',
    'Erfolg',
    'end',
    'Wunderbar! Ihr Termin ist jetzt gebucht. Sie erhalten in Kürze eine Bestätigung per E-Mail an [customer_email]. Gibt es noch etwas, womit ich Ihnen helfen kann?',
    []
);

// --- NODE 7: Abbruch ---
$nodes[] = node(
    'node_cancel',
    'Abbruch',
    'end',
    'Kein Problem! Falls Sie doch noch einen Termin vereinbaren möchten, rufen Sie gerne wieder an. Auf Wiederhören!',
    []
);

$flow['nodes'] = $nodes;

// ================================================================
// VALIDATION & SAVE
// ================================================================

$output_file = '/var/www/api-gateway/public/askproai_conversation_flow_smart.json';
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

        // Check that function has non-empty instruction
        $instr = $node['instruction']['text'];
        if (empty(trim($instr))) {
            $errors[] = "{$node['id']}: function node has empty instruction (needs explicit instructions!)";
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

echo "=== SMART FLOW ARCHITECTURE ===\n";
echo "1. Begrüßung\n";
echo "2. Smart Collection (erkennt bereits genannte Infos!)\n";
echo "3. Verfügbarkeit prüfen (mit expliziten Instructions)\n";
echo "4. Bestätigung einholen\n";
echo "5. Termin buchen (mit expliziten Instructions)\n";
echo "6. Erfolgsbestätigung\n\n";

echo "=== KEY IMPROVEMENTS ===\n";
echo "1. ✅ Intent Recognition - erkennt Name/Datum/Zeit aus erstem Input\n";
echo "2. ✅ Smart Collection - fragt nur nach fehlenden Infos\n";
echo "3. ✅ Explicit Function Instructions - Agent weiß was zu tun ist\n";
echo "4. ✅ Keine wiederholten Fragen mehr\n";
echo "5. ✅ Tatsächliche API-Calls (nicht halluziniert)\n";
echo "6. ✅ Natürliche, schnelle UX\n\n";

echo "Ready to deploy!\n";
