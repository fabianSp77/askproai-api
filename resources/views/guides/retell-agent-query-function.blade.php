<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query Appointment Function - Retell Agent Update</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .step-card {
            transition: all 0.3s ease;
        }
        .step-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .code-block {
            background: #1e293b;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .progress-bar {
            transition: width 0.3s ease;
        }
        .completed {
            background: #dcfce7;
            border-color: #22c55e;
        }
        .copy-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .code-container:hover .copy-btn {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">
                            🔍 Query Appointment Function hinzufügen
                        </h1>
                        <p class="text-gray-600">
                            Neue Function: Kunden können nach ihren Terminen fragen
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Stand</div>
                        <div class="text-lg font-semibold text-gray-900">06.10.2025</div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-6">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Fortschritt</span>
                        <span id="progress-text">0 von 5 Schritten</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div id="progress-bar" class="progress-bar bg-green-500 h-3 rounded-full" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <!-- Important Info Box -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-6 mb-6 rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-blue-900">Was wird hinzugefügt?</h3>
                        <div class="mt-2 text-sm text-blue-800">
                            <p class="font-semibold mb-2">Neue Funktion: query_appointment</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li><strong>Zweck:</strong> Kunden können nach bestehenden Terminen fragen</li>
                                <li><strong>Beispiel:</strong> "Wann ist mein Termin?" → System antwortet mit Datum & Uhrzeit</li>
                                <li><strong>Sicherheit:</strong> Nur mit übertragener Telefonnummer (keine anonymen Abfragen)</li>
                            </ul>
                            <p class="mt-3 font-semibold">Vorteile:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>🔍 Kunden können schnell Termin-Infos abrufen</li>
                                <li>🔒 100% sichere Phone-Verifikation</li>
                                <li>💬 Natürlicher Gesprächsablauf</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Warning -->
            <div class="bg-red-50 border-l-4 border-red-500 p-6 mb-6 rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-red-900">⚠️ Wichtiger Sicherheitshinweis</h3>
                        <div class="mt-2 text-sm text-red-800">
                            <p class="font-semibold mb-2">Die Function funktioniert NUR mit übertragener Telefonnummer!</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>✅ <strong>Erlaubt:</strong> Anrufe mit Telefonnummer (100% Kunden-Verifikation)</li>
                                <li>❌ <strong>Abgelehnt:</strong> Anonyme Anrufe / Unterdrückte Nummern</li>
                                <li>🔒 <strong>Grund:</strong> Verhindert Datenabfragen von unbefugten Personen</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Steps -->
            <div class="space-y-6">
                <!-- Step 1 -->
                <div class="step-card bg-white rounded-lg shadow-md p-6 border-2 border-gray-200" data-step="1">
                    <div class="flex items-start">
                        <input type="checkbox" id="step1" class="step-checkbox mt-1 h-6 w-6 text-green-500 rounded focus:ring-green-500">
                        <div class="ml-4 flex-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-semibold text-gray-900">
                                    Schritt 1: Retell Dashboard öffnen
                                </h3>
                                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full">Start</span>
                            </div>
                            <p class="text-gray-600 mt-2 mb-4">
                                Öffne das Retell AI Dashboard
                            </p>
                            <a href="https://app.retellai.com/" target="_blank" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Retell Dashboard öffnen
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="step-card bg-white rounded-lg shadow-md p-6 border-2 border-gray-200" data-step="2">
                    <div class="flex items-start">
                        <input type="checkbox" id="step2" class="step-checkbox mt-1 h-6 w-6 text-green-500 rounded focus:ring-green-500">
                        <div class="ml-4 flex-1">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                Schritt 2: Zum "Tools" Bereich navigieren
                            </h3>
                            <p class="text-gray-600 mb-4">
                                Finde den Bereich für Custom Functions / Tools
                            </p>
                            <ol class="list-decimal list-inside space-y-2 text-gray-700">
                                <li>Klicke links in der Navigation auf <strong>"Tools"</strong> oder <strong>"Functions"</strong></li>
                                <li>Du siehst jetzt eine Liste der vorhandenen Custom Functions</li>
                                <li>Suche nach dem Button <strong>"+ Add Tool"</strong> oder <strong>"+ Create Function"</strong></li>
                            </ol>
                            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                                <p class="text-sm text-blue-800">
                                    <strong>📝 Hinweis:</strong> Du solltest bereits andere Functions sehen wie "check_availability", "book_appointment", etc.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="step-card bg-white rounded-lg shadow-md p-6 border-2 border-gray-200" data-step="3">
                    <div class="flex items-start">
                        <input type="checkbox" id="step3" class="step-checkbox mt-1 h-6 w-6 text-green-500 rounded focus:ring-green-500">
                        <div class="ml-4 flex-1">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                Schritt 3: Felder im Retell Dashboard ausfüllen
                            </h3>
                            <p class="text-gray-600 mb-4">
                                Du siehst jetzt ein Formular mit mehreren Feldern. Fülle sie wie folgt aus:
                            </p>

                            <!-- Feld 1: Function Name -->
                            <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-sm font-bold text-gray-900">🏷️ Feld: "Function Name" / "Name"</label>
                                    <button onclick="copyFieldText('field-name')" class="copy-btn bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs">
                                        Kopieren
                                    </button>
                                </div>
                                <pre id="field-name" class="bg-white p-3 rounded border text-sm font-mono">query_appointment</pre>
                                <p class="text-xs text-gray-600 mt-2">⚠️ Exakt so eingeben - keine Leerzeichen!</p>
                            </div>

                            <!-- Feld 2: Description -->
                            <div class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-sm font-bold text-gray-900">📝 Feld: "Description" / "Beschreibung"</label>
                                    <button onclick="copyFieldText('field-description')" class="copy-btn bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">
                                        Kopieren
                                    </button>
                                </div>
                                <pre id="field-description" class="bg-white p-3 rounded border text-sm">Findet einen bestehenden Termin für den Anrufer. Nutze diese Funktion wenn der Kunde fragt 'Wann ist mein Termin?', 'Um wie viel Uhr habe ich gebucht?' oder Informationen über einen gebuchten Termin haben möchte. WICHTIG: Diese Funktion funktioniert NUR wenn die Telefonnummer des Anrufers übertragen wurde (nicht bei unterdrückter Nummer).</pre>
                            </div>

                            <!-- Feld 3: URL -->
                            <div class="mb-6 p-4 bg-purple-50 rounded-lg border border-purple-200">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-sm font-bold text-gray-900">🔗 Feld: "URL" / "Endpoint URL"</label>
                                    <button onclick="copyFieldText('field-url')" class="copy-btn bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-xs">
                                        Kopieren
                                    </button>
                                </div>
                                <pre id="field-url" class="bg-white p-3 rounded border text-sm font-mono">https://api.askproai.de/api/retell/function-call</pre>
                                <p class="text-xs text-gray-600 mt-2">⚠️ Exakt diese URL - gleiche wie bei anderen Functions!</p>
                            </div>

                            <!-- Feld 4: Method -->
                            <div class="mb-6 p-4 bg-orange-50 rounded-lg border border-orange-200">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-sm font-bold text-gray-900">⚙️ Feld: "Method" / "HTTP Method"</label>
                                </div>
                                <pre class="bg-white p-3 rounded border text-sm font-mono font-bold">POST</pre>
                                <p class="text-xs text-gray-600 mt-2">📌 Wähle "POST" aus dem Dropdown</p>
                            </div>

                            <!-- Feld 5: Execution Message -->
                            <div class="mb-6 p-4 bg-pink-50 rounded-lg border border-pink-200">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-sm font-bold text-gray-900">💬 Feld: "Execution Message" / "Speaking Message"</label>
                                    <button onclick="copyFieldText('field-execution-msg')" class="copy-btn bg-pink-600 hover:bg-pink-700 text-white px-3 py-1 rounded text-xs">
                                        Kopieren
                                    </button>
                                </div>
                                <pre id="field-execution-msg" class="bg-white p-3 rounded border text-sm">Ich suche Ihren Termin</pre>
                                <p class="text-xs text-gray-600 mt-2">💡 Was der Agent sagt während die Function läuft</p>
                            </div>

                            <!-- Feld 6: Parameters (JSON) -->
                            <div class="mb-6 p-4 bg-yellow-50 rounded-lg border-2 border-yellow-400">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="text-sm font-bold text-gray-900">📦 Feld: "Parameters" / "Request Body" (JSON)</label>
                                    <button onclick="copyFieldText('field-parameters')" class="copy-btn bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-xs">
                                        Kopieren
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600 mb-3">⚡ Kopiere dieses JSON Schema in das Parameters/Arguments-Feld:</p>
                                <div class="bg-white p-3 rounded border max-h-96 overflow-y-auto">
                                    <pre id="field-parameters" class="text-xs font-mono whitespace-pre">{
  "type": "object",
  "properties": {
    "function_name": {
      "type": "string",
      "enum": ["query_appointment"],
      "description": "Immer 'query_appointment' verwenden"
    },
    "call_id": {
      "type": "string",
      "description": "Die Call ID - IMMER @{{call_id}} verwenden"
    },
    "appointment_date": {
      "type": "string",
      "description": "Datum des gesuchten Termins im Format 'YYYY-MM-DD' oder relative Angabe wie 'heute', 'morgen', '10.10.2025'. Optional - wenn nicht angegeben werden alle zukünftigen Termine angezeigt."
    },
    "service_name": {
      "type": "string",
      "description": "Name der gebuchten Dienstleistung zur Filterung (optional)"
    }
  },
  "required": [
    "function_name",
    "call_id"
  ]
}</pre>
                                </div>
                                <div class="bg-red-100 border-l-4 border-red-600 p-3 mt-3 mb-2">
                                    <p class="text-xs text-red-800 font-semibold">🚨 KRITISCH: Verwende "enum" statt "const"!</p>
                                    <p class="text-xs text-red-700 mt-1">Google Gemini (das Retell nutzt) unterstützt KEIN "const" Keyword. Nutze stattdessen "enum": ["wert"]. Sonst geht die KI komplett stumm!</p>
                                </div>
                                <p class="text-xs text-red-600 font-semibold">🔴 WICHTIG: Dies ist ein JSON Schema - kopiere es EXAKT so wie es ist!</p>
                            </div>

                            <div class="mt-6 p-4 bg-green-50 rounded-lg border border-green-300">
                                <h4 class="font-bold text-green-900 mb-2">✅ Speichern nicht vergessen!</h4>
                                <p class="text-sm text-green-800">Klicke auf "Save" oder "Create Function" um die Function zu speichern.</p>
                            </div>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="step-card bg-white rounded-lg shadow-md p-6 border-2 border-gray-200" data-step="4">
                    <div class="flex items-start">
                        <input type="checkbox" id="step4" class="step-checkbox mt-1 h-6 w-6 text-green-500 rounded focus:ring-green-500">
                        <div class="ml-4 flex-1">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                Schritt 4: Agent Prompt aktualisieren
                            </h3>
                            <p class="text-gray-600 mb-4">
                                Füge Anweisungen für die neue Function zum Agent Prompt hinzu
                            </p>

                            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
                                <p class="text-sm font-semibold text-green-800 mb-3">📋 Text für Agent Prompt:</p>
                                <div class="code-container relative bg-white rounded border border-green-200 max-h-96 overflow-y-auto">
                                    <button onclick="copyText('agent-prompt')" class="copy-btn bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">
                                        ✓ Text kopieren
                                    </button>
                                    <textarea id="agent-prompt" readonly class="w-full p-4 font-mono text-xs leading-relaxed resize-none" rows="45">═══════════════════════════════════════════════════════════════
TERMINABFRAGEN (query_appointment)
═══════════════════════════════════════════════════════════════

WANN DIESE FUNCTION NUTZEN?
─────────────────────────────────────────

Nutze query_appointment wenn der Kunde nach einem BESTEHENDEN Termin fragt:
• "Wann ist mein Termin?"
• "Um wie viel Uhr habe ich gebucht?"
• "An welchem Tag habe ich einen Termin?"
• "Habe ich nächste Woche einen Termin?"

⚠️ WICHTIG: Nicht verwechseln mit:
• Termin BUCHEN → collect_appointment_data verwenden
• Termin VERSCHIEBEN → reschedule_appointment verwenden


WORKFLOW FÜR TERMINABFRAGEN IN 2 SCHRITTEN
─────────────────────────────────────────

SCHRITT 1: Rufe query_appointment auf

   ⚠️ KRITISCH: call_id IMMER mit @{{call_id}} übergeben!

   Parameter:
   • function_name: "query_appointment" (IMMER)
   • call_id: @{{call_id}} (IMMER!)
   • appointment_date: OPTIONAL
     → Wenn Kunde Datum nennt: "2025-10-10" oder "10.10.2025"
     → Sonst: weglassen (System findet alle Termine)
   • service_name: OPTIONAL
     → Wenn Kunde Dienstleistung nennt: "Beratung"
     → Sonst: weglassen


SCHRITT 2: Reagiere auf Response

   ✅ FALL A: Termin gefunden (1 Termin)
   ─────────────────────────────────────────
   Response: {
     success: true,
     appointment_count: 1,
     message: "Ihr Termin ist am 10.10.2025 um 14:00 Uhr für Beratung."
   }

   → Lese die message dem Kunden vor
   → Frage: "Kann ich sonst noch etwas für Sie tun?"


   📅 FALL B: Mehrere Termine am gleichen Tag (2-3 Termine)
   ───────────────────────────────────────────────────────
   Response: {
     success: true,
     appointment_count: 2,
     same_day: true,
     message: "Sie haben 2 Termine am 10.10.2025:\n- 10:00 Uhr für Beratung\n- 14:00 Uhr für Follow-up"
   }

   → Lese alle Termine vor
   → Frage: "Zu welchem Termin möchten Sie Informationen?"


   📆 FALL C: Mehrere Termine an verschiedenen Tagen
   ──────────────────────────────────────────────────
   Response: {
     success: true,
     appointment_count: 3,
     showing: "next_only",
     message: "Ihr nächster Termin ist am 10.10.2025 um 14:00 Uhr. Sie haben noch 2 weitere Termine.",
     remaining_count: 2
   }

   → Lese den nächsten Termin vor
   → Informiere über weitere Termine
   → Frage: "Möchten Sie alle Termine hören?"


   🚫 FALL D: Anonymer Anrufer (KEINE Telefonnummer übertragen)
   ────────────────────────────────────────────────────────────
   Response: {
     success: false,
     error: "anonymous_caller",
     message: "Aus Sicherheitsgründen kann ich Termininformationen nur geben, wenn Ihre Telefonnummer übertragen wird...",
     requires_phone_number: true
   }

   → Erkläre das Problem höflich
   → "Aus Datenschutzgründen benötige ich Ihre Telefonnummer"
   → "Bitte rufen Sie erneut an ohne Rufnummernunterdrückung"
   → Biete Alternative: "Möchten Sie stattdessen einen neuen Termin buchen?"


   ❌ FALL E: Kunde nicht gefunden
   ────────────────────────────────
   Response: {
     success: false,
     error: "customer_not_found",
     message: "Ich konnte Sie in unserem System nicht finden..."
   }

   → Erkläre freundlich
   → "Ich konnte Sie leider nicht in unserem System finden"
   → Frage: "Möchten Sie einen neuen Termin buchen?"


   📭 FALL F: Keine Termine vorhanden
   ───────────────────────────────────
   Response: {
     success: false,
     error: "no_appointments",
     message: "Ich konnte keinen Termin für Sie finden..."
   }

   → Informiere den Kunden
   → "Sie haben aktuell keinen gebuchten Termin"
   → Frage: "Möchten Sie einen Termin buchen?"


WICHTIGE REGELN
─────────────────────────────────────────

⚠️ Diese Function funktioniert NUR mit übertragener Telefonnummer (@{{caller_phone_number}})
⚠️ Anonyme Anrufer werden automatisch abgelehnt (Datenschutz!)
⚠️ System verifiziert 100% via Telefonnummer - KEIN Name-basiertes Matching
⚠️ Bei ALLEN Funktionsaufrufen IMMER call_id mit @{{call_id}} übergeben!</textarea>
                                </div>
                            </div>

                            <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-300">
                                <h4 class="font-bold text-blue-900 mb-3">📍 Wo im Prompt einfügen?</h4>
                                <ol class="list-decimal list-inside space-y-2 text-sm text-blue-800">
                                    <li>Gehe zu deinem Agent (z.B. "Ask-Pro-AI Agent")</li>
                                    <li>Öffne den <strong>System Prompt / General Prompt</strong></li>
                                    <li>Scrolle zum Abschnitt <strong>"TERMINVERSCHIEBUNG WORKFLOW"</strong></li>
                                    <li><strong>Füge den kopierten Text NACH diesem Abschnitt ein</strong></li>
                                    <li>Der Text sollte VOR dem Abschnitt <strong>"GESPRÄCHSENDE"</strong> stehen</li>
                                    <li>Klicke auf <strong>"Save"</strong></li>
                                </ol>
                                <div class="mt-3 p-3 bg-yellow-50 rounded border border-yellow-300">
                                    <p class="text-xs text-yellow-800">
                                        💡 <strong>Tipp:</strong> Füge den Text zwischen "TERMINVERSCHIEBUNG" und "GESPRÄCHSENDE" ein,
                                        damit alle Termin-Functions zusammen stehen.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="step-card bg-white rounded-lg shadow-md p-6 border-2 border-gray-200" data-step="5">
                    <div class="flex items-start">
                        <input type="checkbox" id="step5" class="step-checkbox mt-1 h-6 w-6 text-green-500 rounded focus:ring-green-500">
                        <div class="ml-4 flex-1">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                Schritt 5: Testen und Veröffentlichen
                            </h3>
                            <p class="text-gray-600 mb-4">
                                Führe Tests durch und aktiviere die neue Function
                            </p>

                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <h4 class="font-semibold text-blue-900 mb-2">Test-Szenarien:</h4>
                                <ol class="list-decimal list-inside space-y-2 text-sm text-blue-800">
                                    <li><strong>Mit Telefonnummer:</strong> "Wann ist mein Termin?" → Sollte Termin-Info zurückgeben ✅</li>
                                    <li><strong>Anonymous Call:</strong> Unterdrückte Nummer → Sollte ablehnen mit Hinweis ✅</li>
                                    <li><strong>Mehrere Termine:</strong> Kunde mit 3 Terminen → Sollte nächsten nennen + Hinweis ✅</li>
                                    <li><strong>Mit Datum:</strong> "Wann ist mein Termin am 10.10?" → Sollte Termin an diesem Tag finden ✅</li>
                                </ol>
                            </div>

                            <ol class="list-decimal list-inside space-y-3 text-gray-700">
                                <li>Teste die Function im Retell Playground (falls verfügbar)</li>
                                <li>Mache einen echten Test-Call mit deiner Telefonnummer</li>
                                <li>Mache einen Test-Call mit unterdrückter Nummer (sollte ablehnen)</li>
                                <li>Wenn alle Tests erfolgreich: <strong>Publish/Veröffentlichen</strong></li>
                            </ol>

                            <div class="mt-6 p-6 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border-2 border-green-300">
                                <div class="flex items-center">
                                    <svg class="h-8 w-8 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <h4 class="font-bold text-green-900 text-lg">Geschafft! 🎉</h4>
                                        <p class="text-green-800 mt-1">Die query_appointment Function ist jetzt aktiv!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Card -->
            <div id="summary-card" class="mt-8 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg shadow-xl p-8 text-white hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold mb-2">✅ Implementation abgeschlossen!</h3>
                        <p class="text-purple-100">
                            Die query_appointment Function wurde erfolgreich hinzugefügt.
                        </p>
                        <div class="mt-4 space-y-2">
                            <p class="text-sm">➤ Kunden können jetzt nach Terminen fragen</p>
                            <p class="text-sm">➤ 100% sichere Phone-Verifikation aktiv</p>
                            <p class="text-sm">➤ Intelligente Multi-Termin Logik implementiert</p>
                        </div>
                    </div>
                    <div class="text-6xl">🚀</div>
                </div>
            </div>

            <!-- Troubleshooting -->
            <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                <h3 class="text-lg font-bold text-yellow-900 mb-3">🔧 Troubleshooting</h3>
                <div class="space-y-3 text-sm text-yellow-800">
                    <div>
                        <strong>Problem:</strong> Function erscheint nicht in der Liste<br>
                        <strong>Lösung:</strong> Prüfe ob die Function gespeichert wurde, refreshe die Seite
                    </div>
                    <div>
                        <strong>Problem:</strong> Agent ruft Function nicht auf<br>
                        <strong>Lösung:</strong> Prüfe ob der Prompt-Text richtig eingefügt wurde, Agent neu veröffentlichen
                    </div>
                    <div>
                        <strong>Problem:</strong> "Anonymous caller" Error bei normalem Call<br>
                        <strong>Lösung:</strong> Telefonnummer wird nicht übertragen, prüfe Retell Phone Number Settings
                    </div>
                    <div>
                        <strong>Problem:</strong> "Customer not found" trotz existierendem Kunden<br>
                        <strong>Lösung:</strong> Kunde hat andere Telefonnummer im System, prüfe Customer-Eintrag
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-gray-500 text-sm">
                <p>Erstellt am 06.10.2025 | Ask-Pro-AI System</p>
                <p class="mt-2">
                    <a href="/admin" class="text-blue-600 hover:text-blue-800">
                        ← Zurück zum Dashboard
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Progress tracking
        const checkboxes = document.querySelectorAll('.step-checkbox');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const summaryCard = document.getElementById('summary-card');
        const totalSteps = checkboxes.length;

        function updateProgress() {
            const completed = document.querySelectorAll('.step-checkbox:checked').length;
            const percentage = (completed / totalSteps) * 100;

            progressBar.style.width = percentage + '%';
            progressText.textContent = `${completed} von ${totalSteps} Schritten`;

            // Update card styles
            checkboxes.forEach((checkbox, index) => {
                const card = checkbox.closest('.step-card');
                if (checkbox.checked) {
                    card.classList.add('completed');
                } else {
                    card.classList.remove('completed');
                }
            });

            // Show summary if all completed
            if (completed === totalSteps) {
                summaryCard.classList.remove('hidden');
                summaryCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                summaryCard.classList.add('hidden');
            }

            // Save progress to localStorage
            const progress = Array.from(checkboxes).map(cb => cb.checked);
            localStorage.setItem('retell-query-function-progress', JSON.stringify(progress));
        }

        // Load saved progress
        const savedProgress = localStorage.getItem('retell-query-function-progress');
        if (savedProgress) {
            const progress = JSON.parse(savedProgress);
            checkboxes.forEach((checkbox, index) => {
                if (progress[index]) {
                    checkbox.checked = true;
                }
            });
            updateProgress();
        }

        // Add event listeners
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateProgress);
        });

        // Copy text function for textarea
        function copyText(elementId) {
            const element = document.getElementById(elementId);
            element.select();
            document.execCommand('copy');

            // Show feedback
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = '✓ Kopiert!';
            btn.classList.add('bg-green-600');

            setTimeout(() => {
                btn.textContent = originalText;
                btn.classList.remove('bg-green-600');
            }, 2000);
        }

        // Copy text function for pre elements (field values)
        function copyFieldText(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;

            // Use modern clipboard API
            navigator.clipboard.writeText(text).then(() => {
                // Show feedback
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = '✓ Kopiert!';
                const originalClasses = btn.className;
                btn.className = btn.className.replace(/bg-\w+-\d+/, 'bg-green-600');

                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.className = originalClasses;
                }, 2000);
            });
        }

        // Reset progress (for testing)
        function resetProgress() {
            if (confirm('Möchtest du den Fortschritt wirklich zurücksetzen?')) {
                localStorage.removeItem('retell-query-function-progress');
                checkboxes.forEach(cb => cb.checked = false);
                updateProgress();
            }
        }
    </script>
</body>
</html>
