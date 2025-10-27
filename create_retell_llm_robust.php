#!/usr/bin/env php
<?php

/**
 * CREATE RETELL LLM (the brain that decides when to call functions)
 *
 * Step 1: Create LLM with tools
 * Step 2: Create Agent with this LLM
 * Step 3: Phone number → Agent
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🧠 CREATE RETELL LLM (Step 1/3)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$llmConfig = [
    'model' => 'gpt-4o-mini',
    'start_speaker' => 'agent',
    'model_temperature' => 0.3,

    'general_prompt' => "# Friseur 1 - Voice AI Terminassistent

Du bist Carola, die freundliche Terminassistentin von Friseur 1.

## Services
- Herrenhaarschnitt (~30-45 Min)
- Damenhaarschnitt (~45-60 Min)
- Kinderhaarschnitt (~20-30 Min)
- Ansatzfärbung mit waschen, schneiden, föhnen (~2.5 Std)

## Team
Emma Williams, Fabian Spitzer, David Martinez, Michael Chen, Dr. Sarah Johnson

## Workflow

### 1. Begrüßung & Datensammlung
Sammle ALLE Informationen natürlich:
- Service (Herrenhaarschnitt, Damenhaarschnitt, etc.)
- Datum (morgen, Montag, 25.10., etc.)
- Uhrzeit (9 Uhr, 14:30, etc.)
- Name (falls Kunde nicht bekannt)
- Mitarbeiter (OPTIONAL - nur wenn explizit gewünscht)

**Beispiel:**
```
Kunde: Herrenhaarschnitt morgen 9 Uhr, Hans Schuster
Du: Perfekt! Herrenhaarschnitt morgen um 9 Uhr. Einen Moment...
```

### 2. Verfügbarkeit prüfen
Sobald du Service, Datum und Uhrzeit hast, CALL `check_availability_v17`:

```json
{
  \"name\": \"Hans Schuster\",
  \"datum\": \"2025-10-25\",
  \"uhrzeit\": \"09:00\",
  \"dienstleistung\": \"Herrenhaarschnitt\",
  \"mitarbeiter\": \"Fabian Spitzer\",
  \"bestaetigung\": false
}
```

**WICHTIG:**
- `bestaetigung: false` = nur prüfen!
- Sage \"Einen Moment bitte...\" BEVOR du callst
- Warte auf Response

### 3. Ergebnis mitteilen
**Verfügbar:**
```
Der Termin ist verfügbar! Soll ich buchen?
```

**Nicht verfügbar:**
```
Leider nicht verfügbar. Alternativen: [zeige alternatives aus response]
```

### 4. Buchen
Kunde sagt \"Ja\" → CALL `check_availability_v17` NOCHMAL:
```json
{
  \"bestaetigung\": true  // JETZT true!
}
```

Dann: \"Perfekt! Termin gebucht. Wir sehen uns morgen!\"

## Regeln
- NIEMALS Verfügbarkeit erfinden
- IMMER auf Function Response warten
- Kurze Antworten (1-2 Sätze)
- Keine Wiederholungen
- Bei Fehler: \"Es gab ein technisches Problem\"

## Datum Conversion
- \"morgen\" → current_date + 1 day → YYYY-MM-DD
- \"übermorgen\" → current_date + 2 days
- \"Montag\" → next Monday
- Format: YYYY-MM-DD (2025-10-25)

## Zeit Format
- \"9 Uhr\" → 09:00
- \"14:30\" → 14:30
- \"halb 3\" → 14:30
- Format: HH:MM
",

    'begin_message' => 'Guten Tag bei Friseur 1, mein Name ist Carola. Wie kann ich Ihnen helfen?',

    // Tools the LLM can call
    'general_tools' => [
        [
            'type' => 'custom',
            'name' => 'check_availability_v17',
            'description' => 'Check appointment availability OR book appointment. Use bestaetigung=false to check, bestaetigung=true to book. ALWAYS call this before telling customer about availability!',
            'url' => 'https://api.askproai.de/api/retell/v17/check-availability',
            'method' => 'POST',
            'speak_during_execution' => true,
            'speak_after_execution' => false,
            'parameters' => (object)[
                'type' => 'object',
                'properties' => (object)[
                    'name' => (object)[
                        'type' => 'string',
                        'description' => 'Customer name (optional if known from phone number)'
                    ],
                    'datum' => (object)[
                        'type' => 'string',
                        'description' => 'Date in YYYY-MM-DD format (e.g. 2025-10-25)'
                    ],
                    'uhrzeit' => (object)[
                        'type' => 'string',
                        'description' => 'Time in HH:MM format (e.g. 09:00 or 14:30)'
                    ],
                    'dienstleistung' => (object)[
                        'type' => 'string',
                        'description' => 'Service name: Herrenhaarschnitt, Damenhaarschnitt, Kinderhaarschnitt, or Ansatzfärbung waschen schneiden föhnen'
                    ],
                    'mitarbeiter' => (object)[
                        'type' => 'string',
                        'description' => 'Staff member first name (Emma, Fabian, David, Michael, Sarah) - optional, only if customer requests'
                    ],
                    'bestaetigung' => (object)[
                        'type' => 'boolean',
                        'description' => 'false = just check availability, true = actually book. ALWAYS use false first to check, then true to confirm booking!'
                    ]
                ],
                'required' => ['datum', 'uhrzeit', 'dienstleistung', 'bestaetigung']
            ]
        ],
        [
            'type' => 'custom',
            'name' => 'get_customer_appointments',
            'description' => 'Get list of existing customer appointments',
            'url' => 'https://api.askproai.de/api/retell/get-customer-appointments',
            'method' => 'POST',
            'speak_during_execution' => true,
            'speak_after_execution' => false,
            'parameters' => (object)[
                'type' => 'object',
                'properties' => (object)[],
                'required' => []
            ]
        ],
        [
            'type' => 'custom',
            'name' => 'cancel_appointment',
            'description' => 'Cancel an existing appointment',
            'url' => 'https://api.askproai.de/api/retell/cancel-appointment',
            'method' => 'POST',
            'speak_during_execution' => true,
            'speak_after_execution' => false,
            'parameters' => (object)[
                'type' => 'object',
                'properties' => (object)[
                    'appointment_id' => (object)[
                        'type' => 'string',
                        'description' => 'ID of appointment to cancel'
                    ]
                ],
                'required' => ['appointment_id']
            ]
        ],
        [
            'type' => 'custom',
            'name' => 'reschedule_appointment',
            'description' => 'Reschedule existing appointment to new date/time',
            'url' => 'https://api.askproai.de/api/retell/reschedule-appointment',
            'method' => 'POST',
            'speak_during_execution' => true,
            'speak_after_execution' => false,
            'parameters' => (object)[
                'type' => 'object',
                'properties' => (object)[
                    'appointment_id' => (object)['type' => 'string'],
                    'new_date' => (object)['type' => 'string', 'description' => 'YYYY-MM-DD'],
                    'new_time' => (object)['type' => 'string', 'description' => 'HH:MM']
                ],
                'required' => ['appointment_id', 'new_date', 'new_time']
            ]
        ]
    ]
];

echo "Creating Retell LLM with 4 custom tools...\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-retell-llm', $llmConfig);

if ($response->successful()) {
    $llm = $response->json();
    $llmId = $llm['llm_id'];

    echo "✅ RETELL LLM CREATED!\n\n";
    echo "LLM ID: $llmId\n";
    echo "Model: {$llm['model']}\n";
    echo "Tools: " . count($llm['general_tools']) . "\n\n";

    // Save LLM ID
    file_put_contents(__DIR__ . '/retell_llm_id.txt', $llmId);

    echo "═══════════════════════════════════════════════════════════\n";
    echo "STEP 2/3: Create Agent with this LLM\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    // Now create agent with this LLM
    $agentConfig = [
        'agent_name' => 'Friseur1 AI (LLM-based ROBUST)',
        'response_engine' => [
            'type' => 'retell-llm',
            'llm_id' => $llmId
        ],
        'voice_id' => '11labs-Christopher',
        'language' => 'de-DE',
        'enable_backchannel' => true,
        'responsiveness' => 1.0,
        'interruption_sensitivity' => 1,
        'enable_transcription_formatting' => false,
        'reminder_trigger_ms' => 10000,
        'reminder_max_count' => 2,
        'temperature' => 0.3,
        'max_call_duration_ms' => 1800000,
        'end_call_after_silence_ms' => 60000,
        'webhook_url' => 'https://api.askproai.de/api/webhooks/retell'
    ];

    $agentResponse = Http::withHeaders([
        'Authorization' => "Bearer $token",
        'Content-Type' => 'application/json'
    ])->post('https://api.retellai.com/create-agent', $agentConfig);

    if ($agentResponse->successful()) {
        $agent = $agentResponse->json();
        $agentId = $agent['agent_id'];

        echo "✅ AGENT CREATED!\n\n";
        echo "Agent ID: $agentId\n";
        echo "Agent Name: {$agent['agent_name']}\n";
        echo "Response Engine: {$agent['response_engine']['type']}\n";
        echo "LLM ID: {$agent['response_engine']['llm_id']}\n\n";

        // Save agent ID
        file_put_contents(__DIR__ . '/llm_based_agent_id.txt', $agentId);

        // Publish agent
        echo "═══════════════════════════════════════════════════════════\n";
        echo "STEP 3/3: Publishing Agent\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        $publishResponse = Http::withHeaders([
            'Authorization' => "Bearer $token",
        ])->post("https://api.retellai.com/publish-agent/$agentId");

        if ($publishResponse->successful()) {
            echo "✅ AGENT PUBLISHED!\n\n";
        } else {
            echo "⚠️  Publish: HTTP {$publishResponse->status()}\n";
            echo "   (May need manual publish in dashboard)\n\n";
        }

        echo "═══════════════════════════════════════════════════════════\n";
        echo "✅ COMPLETE! Ready to test!\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        echo "LLM ID: $llmId\n";
        echo "Agent ID: $agentId\n";
        echo "Dashboard: https://dashboard.retellai.com/agent/$agentId\n\n";

        echo "NEXT: Update phone number:\n";
        echo "  php update_phone_to_llm_agent.php\n\n";

        echo "OLD Agents (backups):\n";
        echo "  Flow-based: agent_2d467d84eb674e5b3f5815d81c\n\n";

    } else {
        echo "❌ Failed to create agent\n";
        echo "HTTP {$agentResponse->status()}\n";
        echo "Response: {$agentResponse->body()}\n";
        exit(1);
    }

} else {
    echo "❌ Failed to create LLM\n";
    echo "HTTP {$response->status()}\n";
    echo "Response: {$response->body()}\n";
    exit(1);
}
