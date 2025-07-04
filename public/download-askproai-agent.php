<?php
// Direct download endpoint for complete AskProAI Retell Agent Configuration

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="askproai-retell-agent-complete.json"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$agentConfig = [
    "agent_id" => "",
    "channel" => "voice",
    "last_modification_timestamp" => time() * 1000,
    "agent_name" => "AskProAI Terminbuchungs-Agent V1",
    "response_engine" => [
        "type" => "retell-llm",
        "llm_id" => null, // Muss nach Import in Retell.ai gesetzt werden
        "version" => 1
    ],
    "webhook_url" => "https://api.askproai.de/api/retell/webhook",
    "language" => "de-DE",
    "opt_out_sensitive_data_storage" => false,
    "end_call_after_silence_ms" => 10000,
    "post_call_analysis_data" => [
        [
            "type" => "string",
            "name" => "Name",
            "description" => "Der vollständige Name des Anrufers",
            "examples" => ["Max Mustermann", "Maria Schmidt"]
        ],
        [
            "type" => "string", 
            "name" => "Email",
            "description" => "Die Email Adresse des Anrufers"
        ],
        [
            "type" => "string",
            "name" => "Telefonnummer_Anrufer",
            "description" => "Die Telefonnummer des Anrufers"
        ],
        [
            "type" => "string",
            "name" => "Datum_Termin",
            "description" => "Das Datum für den gebuchten Termin im Format TT.MM.JJJJ"
        ],
        [
            "type" => "string",
            "name" => "Uhrzeit_Termin",
            "description" => "Die Uhrzeit wann der Termin startet im Format HH:MM"
        ],
        [
            "type" => "string",
            "name" => "Dienstleistung",
            "description" => "Die gewünschte Dienstleistung oder der Grund des Termins"
        ],
        [
            "type" => "string",
            "name" => "Mitarbeiter_Wunsch",
            "description" => "Bevorzugter Mitarbeiter falls genannt"
        ],
        [
            "type" => "string",
            "name" => "Kundenpraeferenzen",
            "description" => "Spezielle Wünsche oder zeitliche Einschränkungen des Kunden"
        ],
        [
            "type" => "string",
            "name" => "Zusammenfassung_Anruf",
            "description" => "Eine Zusammenfassung des gesamten Anrufs"
        ]
    ],
    "version" => 1,
    "is_published" => false,
    "version_title" => "AskProAI Terminbuchungs-Agent V1",
    "post_call_analysis_model" => "gpt-4o",
    "voice_id" => "elevenlabs-Matilda",
    "voice_temperature" => 0.2,
    "voice_speed" => 0.95,
    "volume" => 1.0,
    "enable_backchannel" => true,
    "backchannel_frequency" => 0.2,
    "backchannel_words" => [
        "ja",
        "genau", 
        "verstehe",
        "aha",
        "natürlich",
        "okay",
        "richtig",
        "mhm",
        "ach so"
    ],
    "max_call_duration_ms" => 300000,
    "interruption_sensitivity" => 0.6,
    "ambient_sound_volume" => 0.0,
    "responsiveness" => 1.0,
    "pronunciation_dictionary" => [
        [
            "word" => "AskProAI",
            "pronunciation" => "Ask Pro A I"
        ],
        [
            "word" => "Cal.com",
            "pronunciation" => "Cal kom"
        ]
    ],
    "normalize_for_speech" => true,
    "enable_voicemail_detection" => false,
    "user_dtmf_options" => new stdClass(),
    "retellLlmData" => [
        "llm_id" => null,
        "version" => 1,
        "model" => "gpt-4",
        "model_temperature" => 0.04,
        "model_high_priority" => true,
        "tool_call_strict_mode" => false,
        "general_prompt" => '## Systemvariablen
Du hast Zugriff auf folgende Systemvariablen:
- {{caller_phone_number}} - Die Telefonnummer des Anrufers (wenn verfügbar)
- {{current_time_berlin}} - Aktuelle Zeit in Berlin
- {{current_date}} - Aktuelles Datum
- {{current_time}} - Aktuelle Uhrzeit
- {{weekday}} - Aktueller Wochentag
- {{company_name}} - Name der Firma

WICHTIG: Wenn {{caller_phone_number}} vorhanden ist, frage NICHT nach der Telefonnummer!

# Rolle
Du bist der professionelle KI-Telefonassistent für {{company_name}}. Du hilfst Kunden bei der Terminvereinbarung und beantwortest Fragen zu den angebotenen Dienstleistungen. Du sprichst ausschließlich Deutsch.

## Kernaufgaben
1. Termine vereinbaren, ändern oder stornieren
2. Verfügbarkeiten prüfen
3. Informationen zu Services geben
4. Kundendaten erfassen
5. Professionell und freundlich kommunizieren

## Gesprächsführung

### Begrüßung
Nutze die Tageszeit für die passende Begrüßung:
- Morgens: "Guten Morgen, {{company_name}}, mein Name ist Clara. Wie kann ich Ihnen helfen?"
- Tagsüber: "Guten Tag, {{company_name}}, mein Name ist Clara. Wie kann ich Ihnen helfen?"
- Abends: "Guten Abend, {{company_name}}, mein Name ist Clara. Wie kann ich Ihnen helfen?"

### Terminvereinbarung
Bei Terminwünschen erfasse systematisch:
1. Gewünschte Dienstleistung
2. Name des Kunden
3. Telefonnummer (nur wenn nicht automatisch erfasst)
4. E-Mail (nur wenn Bestätigung gewünscht)
5. Bevorzugtes Datum und Uhrzeit
6. Besondere Wünsche oder Präferenzen

### Wichtige Verhaltensregeln
- IMMER "Sie" verwenden, niemals "Du"
- Kurze, klare Sätze
- Eine Frage nach der anderen
- Aktiv zuhören und bestätigen
- Bei Unklarheiten nachfragen
- Lösungsorientiert denken

## Datenschutz
- Nur notwendige Daten erfragen
- Vertrauliche Behandlung zusichern
- Keine sensiblen Daten wiederholen

## Gesprächsabschluss
- Alle Details zusammenfassen
- Nach weiteren Wünschen fragen
- Freundlich verabschieden
- Bei Buchungen: Bestätigung per E-Mail erwähnen',
        "general_tools" => [
            [
                "name" => "end_call",
                "type" => "end_call",
                "description" => "Beendet das Gespräch nach erfolgreicher Erledigung"
            ],
            [
                "name" => "check_availability",
                "type" => "custom",
                "description" => "Prüft verfügbare Termine für eine Dienstleistung",
                "url" => "https://api.askproai.de/api/functions/check-availability",
                "method" => "POST",
                "headers" => [
                    ["key" => "Content-Type", "value" => "application/json"]
                ],
                "parameter_type" => "json",
                "speak_during_execution" => true,
                "speak_after_execution" => true,
                "execution_message_description" => "Ich prüfe die Verfügbarkeit für Sie",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "service_type" => [
                            "type" => "string",
                            "description" => "Die gewünschte Dienstleistung"
                        ],
                        "preferred_date" => [
                            "type" => "string",
                            "description" => "Gewünschtes Datum (TT.MM.JJJJ)"
                        ],
                        "preferred_time" => [
                            "type" => "string",
                            "description" => "Bevorzugte Uhrzeit (HH:MM)"
                        ]
                    ],
                    "required" => ["service_type", "preferred_date"]
                ]
            ],
            [
                "name" => "book_appointment",
                "type" => "custom",
                "description" => "Bucht einen Termin mit allen erfassten Daten",
                "url" => "https://api.askproai.de/api/functions/book-appointment",
                "method" => "POST",
                "headers" => [
                    ["key" => "Content-Type", "value" => "application/json"]
                ],
                "parameter_type" => "json",
                "speak_during_execution" => true,
                "speak_after_execution" => true,
                "execution_message_description" => "Ich buche den Termin für Sie",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "customer_name" => [
                            "type" => "string",
                            "description" => "Vollständiger Name des Kunden"
                        ],
                        "phone" => [
                            "type" => "string",
                            "description" => "Telefonnummer des Kunden"
                        ],
                        "email" => [
                            "type" => "string",
                            "description" => "E-Mail-Adresse (optional)"
                        ],
                        "service_type" => [
                            "type" => "string",
                            "description" => "Gebuchte Dienstleistung"
                        ],
                        "date" => [
                            "type" => "string",
                            "description" => "Datum (TT.MM.JJJJ)"
                        ],
                        "time" => [
                            "type" => "string",
                            "description" => "Uhrzeit (HH:MM)"
                        ],
                        "notes" => [
                            "type" => "string",
                            "description" => "Zusätzliche Notizen"
                        ]
                    ],
                    "required" => ["customer_name", "phone", "service_type", "date", "time"]
                ]
            ],
            [
                "name" => "get_business_info",
                "type" => "custom",
                "description" => "Liefert Informationen über das Geschäft",
                "url" => "https://api.askproai.de/api/functions/business-info",
                "method" => "POST",
                "headers" => [
                    ["key" => "Content-Type", "value" => "application/json"]
                ],
                "parameter_type" => "json",
                "speak_during_execution" => false,
                "speak_after_execution" => true,
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "info_type" => [
                            "type" => "string",
                            "enum" => ["hours", "location", "services", "prices"],
                            "description" => "Art der Information"
                        ]
                    ],
                    "required" => ["info_type"]
                ]
            ],
            [
                "name" => "current_time_berlin",
                "type" => "custom", 
                "description" => "Liefert aktuelles Datum und Uhrzeit in Berlin",
                "url" => "https://api.askproai.de/api/zeitinfo?locale=de",
                "method" => "GET",
                "parameter_type" => "json",
                "speak_during_execution" => false,
                "speak_after_execution" => false,
                "parameters" => [
                    "type" => "object",
                    "properties" => []
                ]
            ]
        ],
        "begin_message" => "Guten Tag und herzlich willkommen. Wie kann ich Ihnen helfen?",
        "knowledge_base_ids" => [],
        "last_modification_timestamp" => time() * 1000,
        "is_published" => false
    ],
    "conversationFlow" => null,
    "llmURL" => null
];

// Output formatted JSON
echo json_encode($agentConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;