<?php
/**
 * Fix Retell Custom Functions - Version 2
 * 
 * Problem: Retell is not resolving system variables like {{caller_phone_number}}
 * Solution: Update custom functions to include call_id parameter and resolve phone number from database
 */

require_once __DIR__.'/vendor/autoload.php';

$customFunctions = [
    [
        "url" => "https://api.askproai.de/api/retell/collect-appointment",
        "name" => "collect_appointment_data",
        "description" => "NEVER ask for phone number. Use this to book an appointment after gathering details.",
        "speak_during_execution" => true,
        "speak_after_execution" => true,
        "properties" => [
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "call_id" => [
                        "type" => "string",
                        "description" => "Call ID (always pass '{{call_id}}')"
                    ],
                    "name" => [
                        "type" => "string",
                        "description" => "Customer name"
                    ],
                    "datum" => [
                        "type" => "string",
                        "description" => "Date (e.g., 'heute', 'morgen', '25.06.2025')"
                    ],
                    "uhrzeit" => [
                        "type" => "string",
                        "description" => "Time in 24h format (e.g., '14:00', '09:30')"
                    ],
                    "dienstleistung" => [
                        "type" => "string",
                        "description" => "Service requested (default: 'Beratung')"
                    ],
                    "email" => [
                        "type" => "string",
                        "description" => "Email for confirmation (optional)"
                    ]
                ],
                "required" => ["call_id", "name", "datum", "uhrzeit"]
            ]
        ]
    ],
    [
        "url" => "https://api.askproai.de/api/retell/check-customer",
        "name" => "check_customer",
        "description" => "Check if customer exists. Call at conversation start.",
        "speak_during_execution" => false,
        "speak_after_execution" => false,
        "properties" => [
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "call_id" => [
                        "type" => "string",
                        "description" => "Call ID (always pass '{{call_id}}')"
                    ]
                ],
                "required" => ["call_id"]
            ]
        ]
    ],
    [
        "url" => "https://api.askproai.de/api/retell/check-availability",
        "name" => "check_availability",
        "description" => "Check available appointment slots for a specific date and time",
        "speak_during_execution" => true,
        "speak_after_execution" => true,
        "properties" => [
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "date" => [
                        "type" => "string",
                        "description" => "Date to check (e.g., 'heute', 'morgen', '25.06.2025')"
                    ],
                    "time" => [
                        "type" => "string",
                        "description" => "Preferred time (optional, e.g., '09:00', '14:30')"
                    ]
                ],
                "required" => ["date"]
            ]
        ]
    ],
    [
        "url" => "https://api.askproai.de/api/retell/current-time-berlin",
        "name" => "current_time_berlin",
        "description" => "Get current time in Berlin timezone",
        "speak_during_execution" => false,
        "speak_after_execution" => false,
        "properties" => [
            "parameters" => [
                "type" => "object",
                "properties" => []
            ]
        ]
    ]
];

// Also create an updated system prompt that doesn't ask for phone numbers
$systemPrompt = <<<'PROMPT'
Sie sind ein freundlicher Terminbuchungsassistent für {{company_name}}.

WICHTIGE REGELN:
1. NIEMALS nach der Telefonnummer fragen - Sie haben diese bereits durch {{caller_phone_number}}
2. Beginnen Sie IMMER mit check_customer(call_id: "{{call_id}}")
3. Verwenden Sie IMMER "{{call_id}}" bei allen Funktionsaufrufen
4. Konvertieren Sie relative Datumsangaben (heute, morgen) in collect_appointment_data

ABLAUF:
1. Begrüßung und check_customer() aufrufen
2. Nach Terminwunsch fragen (Datum und Uhrzeit)
3. check_availability() zur Verfügbarkeitsprüfung
4. collect_appointment_data() zur Buchung

GESCHÄFTSZEITEN:
- Montag bis Freitag: 9:00 - 18:00 Uhr
- Samstag & Sonntag: Geschlossen

Bei Anfragen außerhalb der Geschäftszeiten:
- Vor 9:00: "Wir öffnen erst um 9:00 Uhr. Möchten Sie einen Termin ab 9:00 Uhr?"
- Nach 18:00: "Wir haben bereits geschlossen. Möchten Sie einen Termin für morgen?"
- Wochenende: "Am Wochenende haben wir geschlossen. Wie wäre es mit Montag?"

DYNAMISCHE VARIABLEN (werden automatisch ersetzt):
- {{caller_phone_number}}: Telefonnummer des Anrufers
- {{call_id}}: Eindeutige Call-ID
- {{company_name}}: Firmenname
- {{current_date}}: Heutiges Datum
- {{current_time}}: Aktuelle Uhrzeit

Seien Sie höflich, effizient und führen Sie den Kunden schnell zur Terminbuchung.
PROMPT;

// Save the configuration files
file_put_contents(__DIR__ . '/retell-custom-functions-v2.json', json_encode($customFunctions, JSON_PRETTY_PRINT));
file_put_contents(__DIR__ . '/retell-system-prompt-v2.txt', $systemPrompt);

echo "\n✅ Retell Custom Functions V2 erstellt!\n\n";
echo "📋 Nächste Schritte:\n";
echo "1. Öffnen Sie das Retell.ai Dashboard\n";
echo "2. Navigieren Sie zu Ihrem Agent\n";
echo "3. Aktualisieren Sie:\n";
echo "   a) System Prompt mit dem Inhalt aus: retell-system-prompt-v2.txt\n";
echo "   b) Custom Functions mit dem Inhalt aus: retell-custom-functions-v2.json\n";
echo "4. Stellen Sie sicher, dass diese Dynamic Variables aktiviert sind:\n";
echo "   - {{call_id}}\n";
echo "   - {{caller_phone_number}}\n";
echo "   - {{company_name}}\n";
echo "   - {{current_date}}\n";
echo "   - {{current_time}}\n\n";
echo "⚠️  WICHTIG: Die call_id wird jetzt verwendet, um die Telefonnummer aus der Datenbank zu holen!\n\n";

// Also update the checkCustomer function to work with call_id
echo "📝 Updating checkCustomer function to work with call_id...\n";

$checkCustomerUpdate = <<<'PHP'
// Add this to RetellCustomFunctionsController::checkCustomer() at the beginning:
$callId = $request->input('call_id');
if ($callId) {
    $call = Call::where('call_id', $callId)->first();
    if ($call && $call->from_number) {
        $phoneNumber = $call->from_number;
    }
}
PHP;

file_put_contents(__DIR__ . '/check-customer-update.txt', $checkCustomerUpdate);

echo "✅ Complete! The system now resolves phone numbers from the database using call_id.\n";