<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retell.ai Concurrency & CPS Analyse | AskPro Dokumentation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .copy-btn:hover { transform: scale(1.05); }
        .copy-btn:active { transform: scale(0.95); }
        pre { white-space: pre-wrap; word-wrap: break-word; }
        .copy-success { display: none; }
        .copy-success.show { display: flex; }
        .copy-default.hide { display: none; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-8">
        <div class="max-w-5xl mx-auto px-6">
            <div class="flex items-center gap-3 mb-2">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                <span class="text-sm font-medium bg-white/20 px-3 py-1 rounded-full">Retell.ai Integration</span>
            </div>
            <h1 class="text-3xl font-bold">Concurrency & CPS Analyse</h1>
            <p class="text-indigo-100 mt-2">Technische Dokumentation zu Parallelanrufen und Rate Limits</p>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-6 py-10">

        <!-- ================================================================== -->
        <!-- OPEN REQUEST TRACKER - Retell Support                              -->
        <!-- ================================================================== -->
        <section id="support-tracker" class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl shadow-lg border-2 border-amber-300 p-6 mb-8">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-amber-500 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h2 class="text-xl font-bold text-amber-900">Offene Anfrage: Retell Support</h2>
                        <span class="px-3 py-1 bg-amber-500 text-white text-xs font-bold rounded-full animate-pulse">
                            ⏳ Warte auf Antwort
                        </span>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4 mb-4">
                        <div class="bg-white/60 rounded-lg p-3 border border-amber-200">
                            <p class="text-xs text-amber-700 font-medium mb-1">Gesendet an</p>
                            <p class="font-semibold text-amber-900">Rita (Retell.ai Support)</p>
                        </div>
                        <div class="bg-white/60 rounded-lg p-3 border border-amber-200">
                            <p class="text-xs text-amber-700 font-medium mb-1">Datum</p>
                            <p class="font-semibold text-amber-900">09. Januar 2026</p>
                        </div>
                    </div>

                    <div class="bg-white/80 rounded-lg p-4 border border-amber-200 mb-4">
                        <p class="text-sm font-semibold text-amber-800 mb-2">📋 Offene Fragen:</p>
                        <ol class="text-sm text-amber-900 space-y-2 list-decimal list-inside">
                            <li><strong>Account vs. Per-Number Concurrency</strong> – Gibt es zwei getrennte Limits?</li>
                            <li><strong>Peak Handling</strong> – Kosten für 10 parallele Anrufe pro Nummer?</li>
                            <li><strong>Hunting Groups</strong> – Können wir Per-Number-Limits umgehen?</li>
                            <li><strong>Enterprise Pricing</strong> – Volumenrabatte bei ~20 Kunden?</li>
                        </ol>
                    </div>

                    <div class="flex items-center gap-3 text-sm">
                        <span class="text-amber-700">Nach Antwort:</span>
                        <a href="#next-steps" class="text-amber-800 hover:text-amber-900 underline font-medium">→ Nächste Schritte</a>
                        <span class="text-amber-400">|</span>
                        <a href="#implementation-phases" class="text-amber-800 hover:text-amber-900 underline font-medium">→ Implementierungsplan</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Summary -->
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="w-8 h-8 bg-amber-100 text-amber-600 rounded-lg flex items-center justify-center text-sm">!</span>
                Schnellübersicht
            </h2>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                    <h3 class="font-semibold text-blue-800 mb-2">Concurrent Calls</h3>
                    <p class="text-blue-700 text-sm">Maximale Anzahl <strong>gleichzeitig aktiver</strong> Gespräche</p>
                    <div class="mt-3 text-2xl font-bold text-blue-900">20 inklusive</div>
                    <p class="text-xs text-blue-600 mt-1">+$8/Monat pro zusätzlichem Slot</p>
                </div>

                <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
                    <h3 class="font-semibold text-purple-800 mb-2">Twilio CPS (Calls Per Second)</h3>
                    <p class="text-purple-700 text-sm">Wie viele <strong>neue Anrufe pro Sekunde</strong> starten können</p>
                    <div class="mt-3 text-2xl font-bold text-purple-900">1 Standard</div>
                    <p class="text-xs text-purple-600 mt-1">Upgrade: $25 (2 CPS) bis $100 (5 CPS)</p>
                </div>
            </div>
        </section>

        <!-- Current Settings -->
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Aktuelle Account-Einstellungen</h2>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Limit</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Aktueller Wert</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Bedeutung</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="px-4 py-3 font-medium">Concurrent Calls Limit</td>
                            <td class="px-4 py-3"><span class="bg-green-100 text-green-700 px-2 py-1 rounded">20</span></td>
                            <td class="px-4 py-3 text-gray-600">Max. gleichzeitig aktive Gespräche (account-weit)</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">LLM Token Limit</td>
                            <td class="px-4 py-3"><span class="bg-gray-100 text-gray-700 px-2 py-1 rounded">32768</span></td>
                            <td class="px-4 py-3 text-gray-600">Max. Tokens für KI-Prompt</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Telnyx CPS</td>
                            <td class="px-4 py-3"><span class="bg-gray-100 text-gray-700 px-2 py-1 rounded">1</span></td>
                            <td class="px-4 py-3 text-gray-600">Neue Anrufe/Sekunde via Telnyx</td>
                        </tr>
                        <tr class="bg-amber-50">
                            <td class="px-4 py-3 font-medium">Twilio CPS</td>
                            <td class="px-4 py-3"><span class="bg-amber-100 text-amber-700 px-2 py-1 rounded">1</span></td>
                            <td class="px-4 py-3 text-amber-700 font-medium">Neue Anrufe/Sekunde via Twilio (AskPro nutzt Twilio)</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Custom Telephony CPS</td>
                            <td class="px-4 py-3"><span class="bg-gray-100 text-gray-700 px-2 py-1 rounded">1</span></td>
                            <td class="px-4 py-3 text-gray-600">Neue Anrufe/Sekunde via Custom SIP</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- CPS vs Concurrent Explanation -->
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">CPS vs. Concurrent Calls - Der Unterschied</h2>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="border border-gray-200 rounded-lg p-5">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                        <span class="w-6 h-6 bg-blue-500 text-white rounded text-xs flex items-center justify-center">C</span>
                        Concurrent Calls (Gleichzeitig Aktiv)
                    </h3>
                    <p class="text-gray-600 text-sm mb-3">
                        Wie viele Gespräche können <strong>zur gleichen Zeit laufen</strong>?
                    </p>
                    <div class="bg-gray-50 rounded p-3 text-sm font-mono">
                        14:00:00 → Anruf A startet<br>
                        14:00:30 → Anruf B startet<br>
                        14:01:00 → Anruf C startet<br>
                        14:01:30 → A, B, C laufen = <span class="text-blue-600 font-bold">3 concurrent</span>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-5">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                        <span class="w-6 h-6 bg-purple-500 text-white rounded text-xs flex items-center justify-center">S</span>
                        CPS (Calls Per Second)
                    </h3>
                    <p class="text-gray-600 text-sm mb-3">
                        Wie viele NEUE Anrufe können <strong>innerhalb derselben Sekunde starten</strong>?
                    </p>
                    <div class="bg-gray-50 rounded p-3 text-sm font-mono">
                        14:00:00.100 → Anruf A startet<br>
                        14:00:00.200 → Anruf B will starten...<br>
                        <span class="text-red-600">CPS=1: Anruf B wird abgelehnt!</span><br>
                        <span class="text-green-600">CPS=2: Anruf B startet auch</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Detailed CPS Explanation -->
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="w-8 h-8 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                Was bedeutet CPS genau? (Technische Details)
            </h2>

            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                <h4 class="font-bold text-purple-900 mb-2">CPS = Calls Per Second = Anrufe Pro Sekunde</h4>
                <p class="text-purple-800">
                    CPS ist die <strong>Rate</strong>, mit der NEUE Anrufe innerhalb eines 1-Sekunden-Fensters gestartet werden können.
                    Es ist buchstäblich "dieselbe Sekunde" - gemessen von .000 bis .999 Millisekunden.
                </p>
            </div>

            <h3 class="font-semibold text-gray-700 mb-3">Beispiel: Was passiert bei CPS = 1?</h3>

            <div class="bg-gray-900 text-gray-100 rounded-lg p-4 font-mono text-sm mb-6 overflow-x-auto">
<pre>
<span class="text-gray-500">// Sekunde 0: 14:00:00.000 - 14:00:00.999</span>
14:00:00<span class="text-amber-400">.100</span> → Kunde A ruft an    → <span class="text-green-400">Verbunden</span> (1. Anruf dieser Sekunde)
14:00:00<span class="text-amber-400">.250</span> → Kunde B ruft an    → <span class="text-red-400">CPS-Limit erreicht!</span>
14:00:00<span class="text-amber-400">.500</span> → Kunde C ruft an    → <span class="text-red-400">CPS-Limit erreicht!</span>
14:00:00<span class="text-amber-400">.800</span> → Kunde D ruft an    → <span class="text-red-400">CPS-Limit erreicht!</span>

<span class="text-gray-500">// Sekunde 1: 14:00:01.000 - 14:00:01.999</span>
14:00:01<span class="text-amber-400">.050</span> → Kunde E ruft an    → <span class="text-green-400">Verbunden</span> (1. Anruf DIESER Sekunde)
14:00:01<span class="text-amber-400">.300</span> → Kunde F ruft an    → <span class="text-red-400">CPS-Limit erreicht!</span>

<span class="text-gray-500">// Sekunde 2: 14:00:02.000 - 14:00:02.999</span>
14:00:02<span class="text-amber-400">.200</span> → Kunde G ruft an    → <span class="text-green-400">Verbunden</span> (1. Anruf DIESER Sekunde)
</pre>
            </div>

            <h3 class="font-semibold text-gray-700 mb-3">Was passiert mit abgelehnten Anrufen?</h3>

            <div class="overflow-x-auto mb-6">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Provider</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Anruf-Typ</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Verhalten bei CPS-Überschreitung</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="px-4 py-3 font-medium">Twilio</td>
                            <td class="px-4 py-3">Outbound (API)</td>
                            <td class="px-4 py-3"><span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">Gequeued</span> - Anruf wird in nächster Sekunde ausgeführt</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Twilio</td>
                            <td class="px-4 py-3">SIP Trunking</td>
                            <td class="px-4 py-3"><span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs">SIP 503</span> - Anruf schlägt fehl</td>
                        </tr>
                        <tr class="bg-amber-50">
                            <td class="px-4 py-3 font-medium">Telnyx</td>
                            <td class="px-4 py-3">Alle</td>
                            <td class="px-4 py-3"><span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs">SIP 503</span> - "CPS Limit Reached" - abgelehnt</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Plivo</td>
                            <td class="px-4 py-3">Outbound</td>
                            <td class="px-4 py-3"><span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">Gequeued</span> - Nächstes CPS-Intervall</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Plivo</td>
                            <td class="px-4 py-3">Inbound</td>
                            <td class="px-4 py-3"><span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs">SIP 486</span> - Busy Signal</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-800 mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Outbound (Du rufst an)
                    </h4>
                    <p class="text-blue-700 text-sm">
                        Bei API-initiierten Anrufen werden überschüssige Calls meist <strong>gequeued</strong> und in der nächsten Sekunde ausgeführt.
                        Twilio queued bis zu 24h an Anrufen.
                    </p>
                </div>

                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h4 class="font-semibold text-red-800 mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        Inbound (Kunden rufen an)
                    </h4>
                    <p class="text-red-700 text-sm">
                        Bei eingehenden Anrufen (Support-Hotline!) werden überschüssige Calls oft <strong>abgelehnt</strong>.
                        Der Kunde hört ein Besetztzeichen oder "Alle Leitungen belegt".
                    </p>
                </div>
            </div>

            <div class="mt-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
                <h4 class="font-semibold text-amber-800 mb-2">Kritisch für Support-Hotlines!</h4>
                <p class="text-amber-700 text-sm">
                    Bei einer Support-Hotline sind es <strong>Inbound-Anrufe</strong>. Wenn 5 Kunden in derselben Sekunde anrufen
                    und CPS=1 ist, bekommen 4 Kunden möglicherweise ein Besetztzeichen - obwohl genug Concurrent-Kapazität frei wäre!
                </p>
                <p class="text-amber-800 text-sm mt-2 font-medium">
                    Das ist der Grund, warum wir Retell fragen müssen, was genau bei Inbound-CPS-Überschreitung passiert.
                </p>
            </div>
        </section>

        <!-- Pricing Table -->
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Twilio CPS Preisstruktur</h2>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">CPS Level</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Preis/Monat</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Bedeutung</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Kosten/CPS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="bg-green-50">
                            <td class="px-4 py-3 font-medium">1 CPS</td>
                            <td class="px-4 py-3"><span class="text-green-600 font-bold">$0</span> (inklusive)</td>
                            <td class="px-4 py-3 text-gray-600">1 neuer Anruf pro Sekunde</td>
                            <td class="px-4 py-3 text-gray-400">-</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">2 CPS</td>
                            <td class="px-4 py-3 font-bold">$25</td>
                            <td class="px-4 py-3 text-gray-600">2 neue Anrufe pro Sekunde</td>
                            <td class="px-4 py-3 text-gray-500">$25/CPS</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">3 CPS</td>
                            <td class="px-4 py-3 font-bold">$50</td>
                            <td class="px-4 py-3 text-gray-600">3 neue Anrufe pro Sekunde</td>
                            <td class="px-4 py-3 text-gray-500">$25/CPS</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">4 CPS</td>
                            <td class="px-4 py-3 font-bold">$75</td>
                            <td class="px-4 py-3 text-gray-600">4 neue Anrufe pro Sekunde</td>
                            <td class="px-4 py-3 text-gray-500">$25/CPS</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">5 CPS</td>
                            <td class="px-4 py-3 font-bold">$100</td>
                            <td class="px-4 py-3 text-gray-600">5 neue Anrufe pro Sekunde</td>
                            <td class="px-4 py-3 text-gray-500">$25/CPS</td>
                        </tr>
                        <tr class="bg-gray-50">
                            <td class="px-4 py-3 font-medium">mehr als 5 CPS</td>
                            <td class="px-4 py-3 text-gray-500">Support kontaktieren</td>
                            <td class="px-4 py-3 text-gray-600">Enterprise-Preise</td>
                            <td class="px-4 py-3 text-gray-500">Verhandelbar</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Use Case Calculation -->
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">AskPro Use Case Berechnung</h2>

            <div class="bg-indigo-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-indigo-800 mb-2">Geplantes Szenario</h3>
                <ul class="text-indigo-700 text-sm space-y-1">
                    <li><strong>20 Unternehmen</strong> (Support-Hotlines)</li>
                    <li><strong>~20 Anrufe pro Unternehmen/Tag</strong> = 400 Anrufe/Tag gesamt</li>
                    <li><strong>~12.000 Anrufe/Monat</strong> projiziert</li>
                    <li>Peak: Bis zu <strong>10 gleichzeitige Anrufer</strong> pro Unternehmen</li>
                    <li>Jedes Unternehmen hat mindestens 1 Telefonnummer</li>
                </ul>
            </div>

            <h3 class="font-semibold text-gray-700 mb-3">Szenario-Analyse</h3>

            <div class="space-y-4">
                <div class="border border-green-200 bg-green-50 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="bg-green-500 text-white text-xs px-2 py-1 rounded">Normal</span>
                        <span class="font-medium text-green-800">Gleichmäßige Verteilung</span>
                    </div>
                    <p class="text-green-700 text-sm">
                        400 Anrufe/Tag verteilt über 8h = ~50 Anrufe/Stunde = <1 Anruf/Minute.
                        <strong>CPS=1 würde theoretisch reichen</strong> - aber Support-Hotlines sind NIE gleichmäßig verteilt!
                    </p>
                </div>

                <div class="border border-amber-200 bg-amber-50 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="bg-amber-500 text-white text-xs px-2 py-1 rounded">Peak - Realistisch</span>
                        <span class="font-medium text-amber-800">Service-Ausfall / Marketing-Kampagne / Montag morgen</span>
                    </div>
                    <p class="text-amber-700 text-sm">
                        <strong>Szenario:</strong> Ein Unternehmen hat ein Problem. 10 frustrierte Kunden rufen gleichzeitig an.
                        Mit <strong>CPS=1 erreichen nur 1 von 10 Anrufern</strong> den Support. 9 hören Besetztzeichen.
                        <br><br>
                        <strong>Mindestens CPS=5-10 erforderlich</strong> für zuverlässigen Support-Betrieb.
                    </p>
                </div>

                <div class="border border-red-200 bg-red-50 rounded-lg p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded">Worst Case</span>
                        <span class="font-medium text-red-800">Mehrere Unternehmen haben Peak gleichzeitig</span>
                    </div>
                    <p class="text-red-700 text-sm">
                        Beispiel: 5 Unternehmen haben gleichzeitig Peaks mit je 10 Anrufern = 50 gleichzeitige Anrufe.
                        <strong>Übersteigt Concurrent Limit (20) UND CPS-Limit.</strong>
                        Enterprise-Lösung mit flexiblen Limits erforderlich.
                    </p>
                </div>
            </div>

            <div class="mt-6 bg-gray-100 rounded-lg p-4">
                <h4 class="font-semibold text-gray-800 mb-3">Kostenprojektion bei Retell</h4>
                <div class="grid md:grid-cols-3 gap-4 text-sm">
                    <div class="bg-white rounded p-3 border">
                        <p class="text-gray-500 text-xs">Gesprächskosten (geschätzt)</p>
                        <p class="font-bold text-lg">~$2.520/Monat</p>
                        <p class="text-gray-500 text-xs">12.000 Calls × 3 Min × $0,07</p>
                    </div>
                    <div class="bg-white rounded p-3 border">
                        <p class="text-gray-500 text-xs">CPS Upgrade (5 CPS)</p>
                        <p class="font-bold text-lg">$100/Monat</p>
                        <p class="text-gray-500 text-xs">Wenn per-account</p>
                    </div>
                    <div class="bg-white rounded p-3 border">
                        <p class="text-gray-500 text-xs">Enterprise-Schwelle</p>
                        <p class="font-bold text-lg text-green-600">Erreicht!</p>
                        <p class="text-gray-500 text-xs">>$3.000/Monat qualifiziert</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Cost Analysis Section -->
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <span class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                Kostenanalyse (Echte Rechnungsdaten)
            </h2>

            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-6">
                <p class="text-emerald-800 text-sm">
                    <strong>Basierend auf:</strong> Retell Dashboard (Dec 01 - Jan 09, 2026) + Twilio Rechnung (Dec 2025)
                </p>
            </div>

            <!-- Retell Invoice Breakdown -->
            <h3 class="font-semibold text-gray-700 mb-3">Retell-Rechnung (Nov 21 - Dec 21, 2025): $30.48</h3>
            <div class="overflow-x-auto mb-6">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Komponente</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Menge</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Preis</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Betrag</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="bg-blue-50">
                            <td class="px-4 py-3 font-medium">Retell Voice + Elevenlabs TTS</td>
                            <td class="px-4 py-3 text-right">17.040 sec</td>
                            <td class="px-4 py-3 text-right">$0.07/60s</td>
                            <td class="px-4 py-3 text-right font-bold">$19.88</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">GPT 4.1 mini (fast tier)</td>
                            <td class="px-4 py-3 text-right">15.227 sec</td>
                            <td class="px-4 py-3 text-right">$0.024/60s</td>
                            <td class="px-4 py-3 text-right">$6.10</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">LLM Token Surcharge</td>
                            <td class="px-4 py-3 text-right">3.091 tokens</td>
                            <td class="px-4 py-3 text-right">$0.001/token</td>
                            <td class="px-4 py-3 text-right">$3.09</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Retell Voice + Cartesia TTS</td>
                            <td class="px-4 py-3 text-right">572 sec</td>
                            <td class="px-4 py-3 text-right">$0.07/60s</td>
                            <td class="px-4 py-3 text-right">$0.67</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">GPT 4o mini LLM (fast tier)</td>
                            <td class="px-4 py-3 text-right">2.277 sec</td>
                            <td class="px-4 py-3 text-right">$0.009/60s</td>
                            <td class="px-4 py-3 text-right">$0.34</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">GPT 4o mini Text</td>
                            <td class="px-4 py-3 text-right">76 messages</td>
                            <td class="px-4 py-3 text-right">$0.002-0.006</td>
                            <td class="px-4 py-3 text-right">$0.31</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-500">Sonstige</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">$0.06</td>
                        </tr>
                        <tr class="bg-gray-100 font-bold">
                            <td class="px-4 py-3" colspan="3">Gesamt Retell</td>
                            <td class="px-4 py-3 text-right text-lg">$30.48</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Dashboard Summary -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h4 class="font-semibold text-blue-800 mb-2">Dashboard-Zusammenfassung (Dec 01 - Jan 09, 2026)</h4>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="bg-white rounded p-3 border border-blue-100">
                        <p class="text-gray-500 text-xs">Gesamtkosten</p>
                        <p class="font-bold text-xl text-blue-800">$28.42</p>
                    </div>
                    <div class="bg-white rounded p-3 border border-blue-100">
                        <p class="text-gray-500 text-xs">Call Minutes</p>
                        <p class="font-bold text-xl text-blue-800">258.65 min</p>
                    </div>
                    <div class="bg-white rounded p-3 border border-blue-100">
                        <p class="text-gray-500 text-xs">Durchschnitt/Min (Retell)</p>
                        <p class="font-bold text-xl text-blue-800">$0.11</p>
                    </div>
                </div>
            </div>

            <!-- Twilio Invoice Breakdown -->
            <h3 class="font-semibold text-gray-700 mb-3">Twilio-Rechnung (Dec 1 - Dec 31, 2025): $13.34</h3>
            <div class="overflow-x-auto mb-6">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Komponente</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Menge</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600">Betrag</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="px-4 py-3 font-medium">Phone Numbers (Germany Local)</td>
                            <td class="px-4 py-3 text-right">8 Nummern</td>
                            <td class="px-4 py-3 text-right">$9.20</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Elastic SIP Trunking (Inbound Germany)</td>
                            <td class="px-4 py-3 text-right">335 min</td>
                            <td class="px-4 py-3 text-right">$2.01</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-500">MwSt (19% VAT)</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">$2.13</td>
                        </tr>
                        <tr class="bg-gray-100 font-bold">
                            <td class="px-4 py-3" colspan="2">Gesamt Twilio (brutto)</td>
                            <td class="px-4 py-3 text-right text-lg">$13.34</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Cost per Minute Calculation -->
            <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <span class="w-6 h-6 bg-emerald-500 text-white rounded text-sm flex items-center justify-center">★</span>
                Gesamtkosten pro Minute
            </h3>

            <div class="overflow-x-auto mb-6">
                <table class="w-full text-sm">
                    <thead class="bg-emerald-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-emerald-800">Komponente</th>
                            <th class="px-4 py-3 text-right font-medium text-emerald-800">$/min</th>
                            <th class="px-4 py-3 text-right font-medium text-emerald-800">€/min</th>
                            <th class="px-4 py-3 text-left font-medium text-emerald-800">Anmerkung</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="px-4 py-3 font-medium">Retell Platform</td>
                            <td class="px-4 py-3 text-right">$0.110</td>
                            <td class="px-4 py-3 text-right">€0.107</td>
                            <td class="px-4 py-3 text-gray-600">LLM + TTS + Voice Engine</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Twilio SIP Trunking</td>
                            <td class="px-4 py-3 text-right">$0.006</td>
                            <td class="px-4 py-3 text-right">€0.006</td>
                            <td class="px-4 py-3 text-gray-600">Inbound Germany ($2.01/335 min)</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">Twilio Nummern (8 Stk.)</td>
                            <td class="px-4 py-3 text-right">$0.036</td>
                            <td class="px-4 py-3 text-right">€0.035</td>
                            <td class="px-4 py-3 text-gray-600">Fixkosten, sinkt bei mehr Nutzung!</td>
                        </tr>
                        <tr class="bg-emerald-100 font-bold">
                            <td class="px-4 py-3">GESAMT (netto)</td>
                            <td class="px-4 py-3 text-right text-lg">$0.152</td>
                            <td class="px-4 py-3 text-right text-lg">€0.148</td>
                            <td class="px-4 py-3 text-emerald-700">Ohne deutsche MwSt</td>
                        </tr>
                        <tr class="bg-emerald-50">
                            <td class="px-4 py-3 font-medium">Mit 19% MwSt auf Twilio</td>
                            <td class="px-4 py-3 text-right">$0.159</td>
                            <td class="px-4 py-3 text-right">€0.154</td>
                            <td class="px-4 py-3 text-gray-600">Endverbraucher-relevant</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Scaling Effect -->
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                <h4 class="font-semibold text-amber-800 mb-2">Skalierungseffekt: Fixkosten sinken!</h4>
                <p class="text-amber-700 text-sm mb-3">
                    Bei höherer Nutzung sinken die Kosten pro Minute, weil die Telefonnummern-Fixkosten auf mehr Minuten verteilt werden:
                </p>
                <div class="grid md:grid-cols-3 gap-4 text-sm">
                    <div class="bg-white rounded p-3 border border-amber-200">
                        <p class="text-gray-500 text-xs">Aktuell (258 min)</p>
                        <p class="font-bold text-lg">$0.152/min</p>
                        <p class="text-gray-500 text-xs">= €0.148/min</p>
                    </div>
                    <div class="bg-white rounded p-3 border border-amber-200">
                        <p class="text-gray-500 text-xs">Bei 1.000 min/Monat</p>
                        <p class="font-bold text-lg">$0.125/min</p>
                        <p class="text-gray-500 text-xs">= €0.121/min</p>
                    </div>
                    <div class="bg-white rounded p-3 border border-amber-200">
                        <p class="text-gray-500 text-xs">Bei 10.000 min/Monat</p>
                        <p class="font-bold text-lg text-green-600">$0.117/min</p>
                        <p class="text-gray-500 text-xs">= €0.114/min</p>
                    </div>
                </div>
            </div>

            <!-- Customer Pricing Recommendation -->
            <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <span class="w-6 h-6 bg-indigo-500 text-white rounded text-sm flex items-center justify-center">€</span>
                Preisempfehlung für Kunden
            </h3>

            <div class="overflow-x-auto mb-6">
                <table class="w-full text-sm">
                    <thead class="bg-indigo-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-indigo-800">Marge</th>
                            <th class="px-4 py-3 text-right font-medium text-indigo-800">$/min</th>
                            <th class="px-4 py-3 text-right font-medium text-indigo-800">€/min</th>
                            <th class="px-4 py-3 text-left font-medium text-indigo-800">Empfehlung</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-500">Selbstkosten</td>
                            <td class="px-4 py-3 text-right">$0.15</td>
                            <td class="px-4 py-3 text-right">€0.15</td>
                            <td class="px-4 py-3 text-gray-500">Breakeven (keine Marge)</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">+50% Marge</td>
                            <td class="px-4 py-3 text-right">$0.23</td>
                            <td class="px-4 py-3 text-right">€0.22</td>
                            <td class="px-4 py-3 text-gray-600">Niedriger Einstieg</td>
                        </tr>
                        <tr class="bg-green-50">
                            <td class="px-4 py-3 font-medium">+100% Marge</td>
                            <td class="px-4 py-3 text-right font-bold">$0.30</td>
                            <td class="px-4 py-3 text-right font-bold">€0.29</td>
                            <td class="px-4 py-3 text-green-700">✓ Empfohlen (Standard)</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">+150% Marge</td>
                            <td class="px-4 py-3 text-right">$0.38</td>
                            <td class="px-4 py-3 text-right">€0.37</td>
                            <td class="px-4 py-3 text-gray-600">Premium-Segment</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium">+200% Marge</td>
                            <td class="px-4 py-3 text-right">$0.45</td>
                            <td class="px-4 py-3 text-right">€0.44</td>
                            <td class="px-4 py-3 text-gray-600">Enterprise/White-Label</td>
                        </tr>
                        <tr class="bg-blue-50">
                            <td class="px-4 py-3 font-medium">Marktüblich</td>
                            <td class="px-4 py-3 text-right">$0.30-0.50</td>
                            <td class="px-4 py-3 text-right">€0.29-0.49</td>
                            <td class="px-4 py-3 text-blue-700">Wettbewerbs-Range</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Revenue Projection -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-5">
                <h4 class="font-semibold text-green-800 mb-3 flex items-center gap-2">
                    Umsatzprojektion bei 20 Kunden
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-medium">Paket M: €0.27/min</span>
                </h4>
                <div class="grid md:grid-cols-5 gap-4 text-sm">
                    <div class="bg-white rounded p-3 border border-green-200">
                        <p class="text-gray-500 text-xs">Volumen (20×20×3×30)</p>
                        <p class="font-bold text-lg">36.000 min/M</p>
                    </div>
                    <div class="bg-white rounded p-3 border border-green-200">
                        <p class="text-gray-500 text-xs">Selbstkosten</p>
                        <p class="font-bold text-lg">~€4.100/M</p>
                        <p class="text-gray-400 text-xs">36k × €0.113 + 20 × €1.15</p>
                    </div>
                    <div class="bg-white rounded p-3 border border-green-200">
                        <p class="text-gray-500 text-xs">Fixgebühr (20×€200)</p>
                        <p class="font-bold text-lg text-blue-600">€4.000/M</p>
                    </div>
                    <div class="bg-white rounded p-3 border border-green-200">
                        <p class="text-gray-500 text-xs">Minuten (36k×€0.27)</p>
                        <p class="font-bold text-lg text-blue-600">€9.720/M</p>
                    </div>
                    <div class="bg-white rounded p-3 border border-green-200">
                        <p class="text-gray-500 text-xs">Gewinn</p>
                        <p class="font-bold text-lg text-green-600">~€9.620/M</p>
                        <p class="text-gray-400 text-xs">70% Marge</p>
                    </div>
                </div>
                <p class="text-xs text-green-700 mt-3">Gesamtumsatz: €13.720/M (Fixgebühr €4.000 + Minuten €9.720) − Selbstkosten €4.100 = <strong>€9.620 Gewinn</strong></p>
            </div>
        </section>

        <!-- ================================================================== -->
        <!-- PRICING CONFIGURATION PANEL                                         -->
        <!-- ================================================================== -->
        <section id="config-panel" class="bg-gradient-to-br from-slate-50 to-gray-100 rounded-2xl shadow-xl border border-gray-200 mb-8 overflow-hidden">
            <!-- Panel Header -->
            <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-slate-800 to-slate-700 text-white cursor-pointer" onclick="toggleConfigPanel()">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold">Preiskonfiguration</h2>
                        <p class="text-sm text-slate-300">Minutenpreise und Fixkosten flexibel anpassen</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span id="config-status" class="hidden px-3 py-1 rounded-full text-xs font-medium bg-amber-500 text-white animate-pulse">
                        Ungespeichert
                    </span>
                    <button id="toggle-config-btn" class="p-2 hover:bg-white/10 rounded-lg transition-colors" aria-expanded="false" aria-controls="config-content">
                        <svg class="w-5 h-5 transition-transform duration-300" id="toggle-config-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Panel Content (Collapsible) -->
            <div id="config-content" class="hidden">
                <div class="p-6">
                    <!-- Package Prices Grid -->
                    <div class="grid md:grid-cols-3 gap-4 mb-6">
                        <!-- Package S -->
                        <div class="package-config-card bg-white rounded-xl p-5 border-2 border-gray-200 transition-all duration-300 hover:border-gray-400 hover:shadow-lg" data-package="S">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-10 h-10 bg-gradient-to-br from-gray-400 to-gray-500 text-white rounded-xl flex items-center justify-center font-bold text-lg shadow-md">S</span>
                                    <div>
                                        <p class="font-semibold text-gray-800">Standard</p>
                                        <p class="text-xs text-gray-500">1–19 Firmen</p>
                                    </div>
                                </div>
                                <span class="config-modified-dot hidden w-3 h-3 bg-amber-500 rounded-full animate-pulse" title="Geändert"></span>
                            </div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-gray-500 font-medium">EUR</span>
                                <input type="number" id="config-preis-s" step="0.01" min="0.15" max="1.00" value="0.29" data-default="0.29"
                                       class="flex-1 text-center text-xl font-mono font-bold border-2 border-gray-300 rounded-lg py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200"
                                       oninput="handleConfigChange(this)">
                                <span class="text-gray-500">/min</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Marge:</span>
                                <span id="config-margin-s" class="font-semibold text-green-600">61%</span>
                            </div>
                        </div>

                        <!-- Package M -->
                        <div class="package-config-card bg-white rounded-xl p-5 border-2 border-gray-200 transition-all duration-300 hover:border-blue-400 hover:shadow-lg" data-package="M">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl flex items-center justify-center font-bold text-lg shadow-md">M</span>
                                    <div>
                                        <p class="font-semibold text-gray-800">Partner</p>
                                        <p class="text-xs text-gray-500">20–39 Firmen</p>
                                    </div>
                                </div>
                                <span class="config-modified-dot hidden w-3 h-3 bg-amber-500 rounded-full animate-pulse" title="Geändert"></span>
                            </div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-gray-500 font-medium">EUR</span>
                                <input type="number" id="config-preis-m" step="0.01" min="0.15" max="1.00" value="0.27" data-default="0.27"
                                       class="flex-1 text-center text-xl font-mono font-bold border-2 border-gray-300 rounded-lg py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200"
                                       oninput="handleConfigChange(this)">
                                <span class="text-gray-500">/min</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Marge:</span>
                                <span id="config-margin-m" class="font-semibold text-green-600">58%</span>
                            </div>
                        </div>

                        <!-- Package L -->
                        <div class="package-config-card bg-white rounded-xl p-5 border-2 border-gray-200 transition-all duration-300 hover:border-purple-400 hover:shadow-lg" data-package="L">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <span class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl flex items-center justify-center font-bold text-lg shadow-md">L</span>
                                    <div>
                                        <p class="font-semibold text-gray-800">Enterprise</p>
                                        <p class="text-xs text-gray-500">40+ Firmen</p>
                                    </div>
                                </div>
                                <span class="config-modified-dot hidden w-3 h-3 bg-amber-500 rounded-full animate-pulse" title="Geändert"></span>
                            </div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-gray-500 font-medium">EUR</span>
                                <input type="number" id="config-preis-l" step="0.01" min="0.15" max="1.00" value="0.24" data-default="0.24"
                                       class="flex-1 text-center text-xl font-mono font-bold border-2 border-gray-300 rounded-lg py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all duration-200"
                                       oninput="handleConfigChange(this)">
                                <span class="text-gray-500">/min</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Marge:</span>
                                <span id="config-margin-l" class="font-semibold text-green-600">53%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Settings -->
                    <details class="group mb-6">
                        <summary class="flex items-center gap-2 cursor-pointer py-3 px-4 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors list-none">
                            <svg class="w-5 h-5 text-gray-500 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span class="font-medium text-gray-700">Erweiterte Einstellungen</span>
                            <span class="text-xs text-gray-500">(Fixkosten, Selbstkosten, Tage/Monat)</span>
                        </summary>
                        <div class="grid md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-200">
                            <div class="bg-white rounded-lg p-4 border border-gray-200 md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">CPS-Modell (Fixgebühr/Unternehmen)</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="cps-option relative cursor-pointer">
                                        <input type="radio" name="cps-model" value="1" data-fixgebuehr="50"
                                               class="sr-only peer" onchange="handleCpsChange(this)">
                                        <div class="border-2 border-gray-300 rounded-lg p-3 text-center transition-all
                                                    peer-checked:border-indigo-500 peer-checked:bg-indigo-50
                                                    hover:border-gray-400 hover:bg-gray-50">
                                            <div class="flex items-center justify-center gap-2 mb-1">
                                                <span class="w-6 h-6 bg-gray-400 text-white rounded-full flex items-center justify-center text-xs font-bold">1</span>
                                                <span class="font-bold text-gray-800">1 CPS</span>
                                            </div>
                                            <p class="text-lg font-bold text-indigo-600">€50<span class="text-xs font-normal text-gray-500">/Mo</span></p>
                                            <p class="text-xs text-gray-500">1 Anruf gleichzeitig</p>
                                        </div>
                                    </label>
                                    <label class="cps-option relative cursor-pointer">
                                        <input type="radio" name="cps-model" value="5" data-fixgebuehr="200"
                                               class="sr-only peer" checked onchange="handleCpsChange(this)">
                                        <div class="border-2 border-gray-300 rounded-lg p-3 text-center transition-all
                                                    peer-checked:border-indigo-500 peer-checked:bg-indigo-50
                                                    hover:border-gray-400 hover:bg-gray-50">
                                            <div class="flex items-center justify-center gap-2 mb-1">
                                                <span class="w-6 h-6 bg-indigo-500 text-white rounded-full flex items-center justify-center text-xs font-bold">5</span>
                                                <span class="font-bold text-gray-800">5 CPS</span>
                                            </div>
                                            <p class="text-lg font-bold text-indigo-600">€200<span class="text-xs font-normal text-gray-500">/Mo</span></p>
                                            <p class="text-xs text-gray-500">5 Anrufe gleichzeitig</p>
                                        </div>
                                    </label>
                                </div>
                                <input type="hidden" id="config-fixgebuehr" value="200" data-default="200">
                            </div>
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <label class="block text-sm font-medium text-gray-700 mb-2" for="config-selbstkosten">Selbstkosten/Minute</label>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-500">EUR</span>
                                    <input type="number" id="config-selbstkosten" step="0.001" min="0.05" max="0.25" value="0.113" data-default="0.113"
                                           class="flex-1 text-center font-mono font-bold border-2 border-gray-300 rounded-lg py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                                           oninput="handleConfigChange(this)">
                                    <span class="text-gray-500">/min</span>
                                </div>
                            </div>
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <label class="block text-sm font-medium text-gray-700 mb-2" for="config-nummernkosten">Kosten/Telefonnummer</label>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-500">EUR</span>
                                    <input type="number" id="config-nummernkosten" step="0.05" min="0.50" max="5.00" value="1.15" data-default="1.15"
                                           class="flex-1 text-center font-mono font-bold border-2 border-gray-300 rounded-lg py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                                           oninput="handleConfigChange(this)">
                                    <span class="text-gray-500">/Mo</span>
                                </div>
                            </div>
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <label class="block text-sm font-medium text-gray-700 mb-2" for="config-tage">Tage pro Monat</label>
                                <div class="flex items-center gap-2">
                                    <input type="number" id="config-tage" step="1" min="28" max="31" value="30" data-default="30"
                                           class="flex-1 text-center font-mono font-bold border-2 border-gray-300 rounded-lg py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                                           oninput="handleConfigChange(this)">
                                    <span class="text-gray-500">Tage</span>
                                </div>
                            </div>
                        </div>
                    </details>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-gray-200">
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Änderungen werden <strong>live</strong> im Rechner angezeigt</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <button onclick="resetConfig()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200 flex items-center gap-2 min-h-[44px]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Zurücksetzen
                            </button>
                            <button onclick="shareConfig()" class="px-4 py-2 text-sm font-medium text-indigo-700 bg-indigo-100 hover:bg-indigo-200 rounded-lg transition-colors duration-200 flex items-center gap-2 min-h-[44px]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                                Link teilen
                            </button>
                            <button onclick="saveConfig()" id="save-config-btn" class="px-5 py-2 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2 min-h-[44px]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Speichern
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Interactive Revenue Calculator -->
        <section class="bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 rounded-xl shadow-lg border-2 border-indigo-200 p-6 mb-8" id="calculator-section">
            <h2 class="text-2xl font-bold text-indigo-900 mb-2 flex items-center gap-3">
                <span class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 text-white rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </span>
                Umsatz- & Gewinnrechner
            </h2>
            <p class="text-indigo-700 mb-6">Berechne deinen Umsatz basierend auf Kundenanzahl und Minutenpaketen</p>

            <!-- Pricing Model Info -->
            <div class="bg-white/80 backdrop-blur rounded-xl p-5 mb-6 border border-indigo-100">
                <h3 class="font-semibold text-indigo-800 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Dein Preismodell
                </h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-200">
                        <p class="text-xs text-indigo-600 font-medium uppercase tracking-wide">Fixgebühr pro Unternehmen</p>
                        <p class="text-3xl font-bold text-indigo-900">€200<span class="text-lg font-normal text-indigo-600">/Monat</span></p>
                        <ul class="mt-2 text-sm text-indigo-700 space-y-1">
                            <li class="flex items-center gap-1"><svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> 1 Telefonnummer</li>
                            <li class="flex items-center gap-1"><svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> 5 parallele Gespräche</li>
                        </ul>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                        <p class="text-xs text-purple-600 font-medium uppercase tracking-wide">Deine variablen Selbstkosten</p>
                        <p class="text-3xl font-bold text-purple-900">€0.113<span class="text-lg font-normal text-purple-600">/min</span></p>
                        <ul class="mt-2 text-sm text-purple-700 space-y-1">
                            <li>Retell: €0.107/min</li>
                            <li>Twilio SIP: €0.006/min</li>
                            <li class="text-purple-500 text-xs mt-1">+ €1.15/Nummer/Monat (fixe Kosten)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Partner-Staffelpreise nach Unternehmenszahl -->
            <div class="bg-white/80 backdrop-blur rounded-xl p-5 mb-6 border border-indigo-100">
                <h3 class="font-semibold text-indigo-800 mb-2">Partner-Staffelpreise</h3>
                <p class="text-sm text-indigo-600 mb-4">Je mehr Unternehmen dein Partner anbindet, desto günstiger der Minutenpreis für <strong>alle</strong> seine Kunden!</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-indigo-200">
                                <th class="px-3 py-2 text-left font-semibold text-indigo-800">Paket</th>
                                <th class="px-3 py-2 text-center font-semibold text-indigo-800">Unternehmen</th>
                                <th class="px-3 py-2 text-right font-semibold text-indigo-800">€/min</th>
                                <th class="px-3 py-2 text-right font-semibold text-indigo-800">Rabatt</th>
                                <th class="px-3 py-2 text-right font-semibold text-indigo-800">Deine Marge</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-indigo-100 hover:bg-indigo-50" id="package-row-S">
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="w-8 h-8 bg-gradient-to-br from-gray-400 to-gray-500 text-white rounded-lg flex items-center justify-center font-bold text-sm">S</span>
                                        Standard
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center font-medium" id="pkg-range-S">1–19</td>
                                <td class="px-3 py-3 text-right font-bold text-indigo-600" id="pkg-price-S">€0.29</td>
                                <td class="px-3 py-3 text-right text-gray-400">—</td>
                                <td class="px-3 py-3 text-right"><span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700" id="pkg-margin-S">61%</span></td>
                            </tr>
                            <tr class="border-b border-indigo-100 hover:bg-indigo-50" id="package-row-M">
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg flex items-center justify-center font-bold text-sm">M</span>
                                        Partner
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center font-medium" id="pkg-range-M">20–39</td>
                                <td class="px-3 py-3 text-right font-bold text-indigo-600" id="pkg-price-M">€0.27</td>
                                <td class="px-3 py-3 text-right text-green-600 font-medium" id="pkg-diff-M">-€0.02</td>
                                <td class="px-3 py-3 text-right"><span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700" id="pkg-margin-M">58%</span></td>
                            </tr>
                            <tr class="hover:bg-indigo-50" id="package-row-L">
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="w-8 h-8 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg flex items-center justify-center font-bold text-sm">L</span>
                                        Enterprise
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center font-medium" id="pkg-range-L">40+</td>
                                <td class="px-3 py-3 text-right font-bold text-indigo-600" id="pkg-price-L">€0.24</td>
                                <td class="px-3 py-3 text-right text-green-600 font-medium" id="pkg-diff-L">-€0.05</td>
                                <td class="px-3 py-3 text-right"><span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700" id="pkg-margin-L">53%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-indigo-600 mt-3" id="pkg-incentive-text">💡 <strong>Incentive:</strong> Ein Partner mit 20 Unternehmen zahlt für <em>alle 20</em> nur €0.27/min statt €0.29/min!</p>
            </div>

            <!-- Calculator Inputs -->
            <div class="bg-white/80 backdrop-blur rounded-xl p-5 mb-6 border border-indigo-100">
                <h3 class="font-semibold text-indigo-800 mb-4">Einstellungen</h3>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Anzahl Unternehmen -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Anzahl Unternehmen</label>
                        <input type="range" id="calc-companies" min="1" max="60" value="10"
                               class="w-full h-2 bg-indigo-200 rounded-lg appearance-none cursor-pointer accent-indigo-600"
                               oninput="updateCalculator()">
                        <div class="flex justify-between mt-1">
                            <span class="text-xs text-gray-500">1</span>
                            <span id="calc-companies-value" class="text-lg font-bold text-indigo-600">10</span>
                            <span class="text-xs text-gray-500">60</span>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button onclick="setCompanies(10)" class="min-w-[44px] min-h-[44px] px-3 py-2 text-sm font-medium bg-indigo-100 hover:bg-indigo-200 active:bg-indigo-300 rounded-lg transition-colors" aria-label="10 Unternehmen (Standard)">10</button>
                            <button onclick="setCompanies(20)" class="min-w-[44px] min-h-[44px] px-3 py-2 text-sm font-medium bg-indigo-100 hover:bg-indigo-200 active:bg-indigo-300 rounded-lg transition-colors" aria-label="20 Unternehmen (Partner)">20</button>
                            <button onclick="setCompanies(40)" class="min-w-[44px] min-h-[44px] px-3 py-2 text-sm font-medium bg-indigo-100 hover:bg-indigo-200 active:bg-indigo-300 rounded-lg transition-colors" aria-label="40 Unternehmen (Enterprise)">40</button>
                        </div>
                    </div>

                    <!-- Anrufe pro Tag -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Anrufe pro Unternehmen/Tag</label>
                        <input type="range" id="calc-calls" min="1" max="50" value="20"
                               class="w-full h-2 bg-indigo-200 rounded-lg appearance-none cursor-pointer accent-indigo-600"
                               oninput="updateCalculator()">
                        <div class="flex justify-between mt-1">
                            <span class="text-xs text-gray-500">1</span>
                            <span id="calc-calls-value" class="text-lg font-bold text-indigo-600">20</span>
                            <span class="text-xs text-gray-500">50</span>
                        </div>
                    </div>

                    <!-- Durchschnittliche Dauer -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ø Anrufdauer (Minuten)</label>
                        <input type="range" id="calc-duration" min="1" max="10" value="3" step="0.5"
                               class="w-full h-2 bg-indigo-200 rounded-lg appearance-none cursor-pointer accent-indigo-600"
                               oninput="updateCalculator()">
                        <div class="flex justify-between mt-1">
                            <span class="text-xs text-gray-500">1</span>
                            <span id="calc-duration-value" class="text-lg font-bold text-indigo-600">3</span>
                            <span class="text-xs text-gray-500">10</span>
                        </div>
                    </div>

                    <!-- Aktuelles Paket (automatisch) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Aktuelles Paket</label>
                        <div id="current-package-display" class="bg-gradient-to-br from-gray-400 to-gray-500 text-white px-4 py-3 rounded-lg text-center">
                            <span class="text-2xl font-bold">S</span>
                            <p class="text-xs opacity-80">€0.29/min</p>
                        </div>
                        <p class="text-xs text-gray-500 mt-2 text-center">Basierend auf Unternehmensanzahl</p>
                        <!-- Hidden input for compatibility -->
                        <input type="hidden" id="calc-package" value="S">
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div class="bg-white rounded-xl p-6 border-2 border-indigo-300 shadow-lg">
                <h3 class="font-bold text-indigo-900 mb-4 text-lg flex items-center gap-2">
                    Ergebnis: Monatliche Kalkulation
                    <span class="text-xs font-normal text-indigo-500 bg-indigo-100 px-2 py-1 rounded-full">
                        <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        Hover für Details
                    </span>
                </h3>

                <!-- Summary Cards with Tooltips -->
                <div class="grid md:grid-cols-4 gap-4 mb-6">
                    <!-- Gesamtumsatz Card -->
                    <div class="group relative">
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg cursor-help transition-all duration-300 hover:scale-105 hover:shadow-xl hover:from-blue-600 hover:to-blue-700">
                            <div class="flex items-center justify-between">
                                <p class="text-blue-100 text-xs font-medium uppercase">Gesamtumsatz</p>
                                <svg class="w-4 h-4 text-blue-200 opacity-60 group-hover:opacity-100" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                            </div>
                            <p id="result-revenue" class="text-3xl font-bold">€0</p>
                            <p class="text-blue-200 text-xs mt-1">Fix + Minuten</p>
                        </div>
                        <!-- Tooltip -->
                        <div class="absolute z-20 left-0 right-0 top-full mt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 pointer-events-none">
                            <div class="bg-gray-900 text-white text-xs rounded-lg p-4 shadow-2xl border border-blue-400">
                                <div class="absolute -top-2 left-6 w-4 h-4 bg-gray-900 rotate-45 border-l border-t border-blue-400"></div>
                                <p class="font-bold text-blue-300 mb-2 text-sm">📊 Umsatz-Berechnung</p>
                                <div class="space-y-2 font-mono text-[11px]">
                                    <div class="flex justify-between border-b border-gray-700 pb-1">
                                        <span class="text-gray-400">Fixgebühren:</span>
                                        <span id="tooltip-fix-calc" class="text-blue-300">0 Firmen × €200</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-700 pb-1">
                                        <span class="text-gray-400">Minutenumsatz:</span>
                                        <span id="tooltip-minute-calc" class="text-blue-300">0 min × €0.29</span>
                                    </div>
                                    <div class="flex justify-between pt-1 font-bold">
                                        <span class="text-white">= Gesamt:</span>
                                        <span id="tooltip-revenue-total" class="text-green-400">€0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deine Kosten Card -->
                    <div class="group relative">
                        <div class="bg-gradient-to-br from-red-400 to-red-500 rounded-xl p-4 text-white shadow-lg cursor-help transition-all duration-300 hover:scale-105 hover:shadow-xl hover:from-red-500 hover:to-red-600">
                            <div class="flex items-center justify-between">
                                <p class="text-red-100 text-xs font-medium uppercase">Deine Kosten</p>
                                <svg class="w-4 h-4 text-red-200 opacity-60 group-hover:opacity-100" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                            </div>
                            <p id="result-costs" class="text-3xl font-bold">€0</p>
                            <p class="text-red-200 text-xs mt-1">Retell + Twilio</p>
                        </div>
                        <!-- Tooltip -->
                        <div class="absolute z-20 left-0 right-0 top-full mt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 pointer-events-none">
                            <div class="bg-gray-900 text-white text-xs rounded-lg p-4 shadow-2xl border border-red-400">
                                <div class="absolute -top-2 left-6 w-4 h-4 bg-gray-900 rotate-45 border-l border-t border-red-400"></div>
                                <p class="font-bold text-red-300 mb-2 text-sm">💰 Kosten-Berechnung</p>
                                <div class="space-y-2 font-mono text-[11px]">
                                    <div class="flex justify-between border-b border-gray-700 pb-1">
                                        <span class="text-gray-400">Telefonnummern:</span>
                                        <span id="tooltip-number-calc" class="text-red-300">0 × €1.15/Mo</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-700 pb-1">
                                        <span class="text-gray-400">Minutenkosten:</span>
                                        <span id="tooltip-mincost-calc" class="text-red-300">0 min × €0.113</span>
                                    </div>
                                    <div class="flex justify-between pt-1 font-bold">
                                        <span class="text-white">= Gesamt:</span>
                                        <span id="tooltip-costs-total" class="text-yellow-400">€0</span>
                                    </div>
                                </div>
                                <p class="text-gray-400 text-[10px] mt-2 italic">Retell €0.07 + Twilio €0.043 = €0.113/min</p>
                            </div>
                        </div>
                    </div>

                    <!-- Dein Gewinn Card -->
                    <div class="group relative">
                        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg cursor-help transition-all duration-300 hover:scale-105 hover:shadow-xl hover:from-green-600 hover:to-emerald-700">
                            <div class="flex items-center justify-between">
                                <p class="text-green-100 text-xs font-medium uppercase">Dein Gewinn</p>
                                <svg class="w-4 h-4 text-green-200 opacity-60 group-hover:opacity-100" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                            </div>
                            <p id="result-profit" class="text-3xl font-bold">€0</p>
                            <p id="result-margin" class="text-green-200 text-xs mt-1">0% Marge</p>
                        </div>
                        <!-- Tooltip -->
                        <div class="absolute z-20 left-0 right-0 top-full mt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 pointer-events-none">
                            <div class="bg-gray-900 text-white text-xs rounded-lg p-4 shadow-2xl border border-green-400">
                                <div class="absolute -top-2 left-6 w-4 h-4 bg-gray-900 rotate-45 border-l border-t border-green-400"></div>
                                <p class="font-bold text-green-300 mb-2 text-sm">🎯 Gewinn-Berechnung</p>
                                <div class="space-y-2 font-mono text-[11px]">
                                    <div class="flex justify-between border-b border-gray-700 pb-1">
                                        <span class="text-gray-400">Umsatz:</span>
                                        <span id="tooltip-profit-revenue" class="text-blue-300">€0</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-700 pb-1">
                                        <span class="text-gray-400">− Kosten:</span>
                                        <span id="tooltip-profit-costs" class="text-red-300">€0</span>
                                    </div>
                                    <div class="flex justify-between pt-1 font-bold">
                                        <span class="text-white">= Gewinn:</span>
                                        <span id="tooltip-profit-total" class="text-green-400">€0</span>
                                    </div>
                                </div>
                                <div class="mt-2 pt-2 border-t border-gray-700">
                                    <div class="flex justify-between text-[10px]">
                                        <span class="text-gray-400">Marge-Formel:</span>
                                        <span class="text-gray-300">(Gewinn ÷ Umsatz) × 100</span>
                                    </div>
                                    <div class="flex justify-between text-[11px] font-bold mt-1">
                                        <span class="text-green-400">= Marge:</span>
                                        <span id="tooltip-margin-value" class="text-green-300">0%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gewinn/Kunde Card -->
                    <div class="group relative">
                        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg cursor-help transition-all duration-300 hover:scale-105 hover:shadow-xl hover:from-purple-600 hover:to-purple-700">
                            <div class="flex items-center justify-between">
                                <p class="text-purple-100 text-xs font-medium uppercase">Gewinn/Kunde</p>
                                <svg class="w-4 h-4 text-purple-200 opacity-60 group-hover:opacity-100" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                            </div>
                            <p id="result-profit-per-customer" class="text-3xl font-bold">€0</p>
                            <p class="text-purple-200 text-xs mt-1">pro Monat</p>
                        </div>
                        <!-- Tooltip -->
                        <div class="absolute z-20 left-0 right-0 top-full mt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 pointer-events-none">
                            <div class="bg-gray-900 text-white text-xs rounded-lg p-4 shadow-2xl border border-purple-400">
                                <div class="absolute -top-2 left-6 w-4 h-4 bg-gray-900 rotate-45 border-l border-t border-purple-400"></div>
                                <p class="font-bold text-purple-300 mb-2 text-sm">👤 Gewinn pro Kunde</p>
                                <div class="space-y-2 font-mono text-[11px]">
                                    <div class="flex justify-between border-b border-gray-700 pb-1">
                                        <span class="text-gray-400">Gesamtgewinn:</span>
                                        <span id="tooltip-percust-profit" class="text-green-300">€0</span>
                                    </div>
                                    <div class="flex justify-between border-b border-gray-700 pb-1">
                                        <span class="text-gray-400">÷ Anzahl Kunden:</span>
                                        <span id="tooltip-percust-count" class="text-purple-300">0</span>
                                    </div>
                                    <div class="flex justify-between pt-1 font-bold">
                                        <span class="text-white">= Pro Kunde:</span>
                                        <span id="tooltip-percust-result" class="text-purple-400">€0</span>
                                    </div>
                                </div>
                                <p class="text-gray-400 text-[10px] mt-2 italic">Monatlicher Gewinn je Endkunde</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Breakdown -->
                <div class="bg-gray-50 rounded-xl p-4">
                    <h4 class="font-semibold text-gray-800 mb-3">Detailaufschlüsselung</h4>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-2">Umsatz</p>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Fixgebühren (<span id="detail-companies">0</span> × €200)</span>
                                    <span id="detail-fix-revenue" class="font-medium">€0</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Minutenumsatz (<span id="detail-minutes">0</span> min × €<span id="detail-price">0.29</span>)</span>
                                    <span id="detail-minute-revenue" class="font-medium">€0</span>
                                </div>
                                <div class="flex justify-between border-t pt-2 font-bold text-blue-600">
                                    <span>Gesamtumsatz</span>
                                    <span id="detail-total-revenue">€0</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-2">Kosten</p>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Telefonnummern (<span id="detail-numbers">0</span> × €1.15)</span>
                                    <span id="detail-number-costs" class="font-medium">€0</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Minutenkosten (<span id="detail-minutes2">0</span> min × €0.113)</span>
                                    <span id="detail-minute-costs" class="font-medium">€0</span>
                                </div>
                                <div class="flex justify-between border-t pt-2 font-bold text-red-600">
                                    <span>Gesamtkosten</span>
                                    <span id="detail-total-costs">€0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scenario Comparison -->
            <div class="mt-6 bg-white/80 backdrop-blur rounded-xl p-5 border border-indigo-100">
                <h3 class="font-semibold text-indigo-800 mb-4">Szenario-Vergleich (Paketgrenzen: 10 / 20 / 40 Unternehmen)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-indigo-200 bg-indigo-50">
                                <th class="px-3 py-2 text-left font-semibold text-indigo-800">Szenario</th>
                                <th class="px-3 py-2 text-right font-semibold text-indigo-800">Minuten/M</th>
                                <th class="px-3 py-2 text-right font-semibold text-indigo-800">Umsatz</th>
                                <th class="px-3 py-2 text-right font-semibold text-indigo-800">Kosten</th>
                                <th class="px-3 py-2 text-right font-semibold text-indigo-800">Gewinn</th>
                                <th class="px-3 py-2 text-right font-semibold text-indigo-800">Marge</th>
                            </tr>
                        </thead>
                        <tbody id="scenario-table">
                            <!-- Will be filled by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Invoice Preview Section -->
            <div class="mt-6 bg-white rounded-xl p-6 border-2 border-amber-200 shadow-lg">
                <h3 class="font-bold text-amber-900 mb-2 text-lg flex items-center gap-2">
                    <span class="w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-600 text-white rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </span>
                    Rechnungsvorschau für Endkunden
                </h3>
                <p class="text-amber-700 text-sm mb-4">So sieht die Rechnung für ein Unternehmen in deinem aktuellen Szenario aus</p>

                <!-- Pricing Structure Overview -->
                <div class="bg-amber-50 rounded-lg p-4 mb-6 border border-amber-200">
                    <h4 class="font-semibold text-amber-800 mb-3">Preisstruktur pro Unternehmen</h4>
                    <div class="grid md:grid-cols-3 gap-4 text-sm">
                        <div class="bg-white rounded p-3 border border-amber-100">
                            <p class="text-gray-500 text-xs uppercase tracking-wide">Einmalig</p>
                            <p class="font-bold text-xl text-amber-700">€1.500</p>
                            <p class="text-gray-600 text-xs">Einrichtung Call Flow</p>
                        </div>
                        <div class="bg-white rounded p-3 border border-amber-100">
                            <p class="text-gray-500 text-xs uppercase tracking-wide">Monatlich Fix</p>
                            <p class="font-bold text-xl text-amber-700">€200</p>
                            <p class="text-gray-600 text-xs">Bereitstellung (5 CPS)</p>
                        </div>
                        <div class="bg-white rounded p-3 border border-amber-100">
                            <p class="text-gray-500 text-xs uppercase tracking-wide">Variable</p>
                            <p class="font-bold text-xl text-amber-700" id="invoice-price-per-min">€0.29</p>
                            <p class="text-gray-600 text-xs">pro Gesprächsminute</p>
                        </div>
                    </div>
                    <p class="text-xs text-amber-600 mt-3">
                        <strong>Hinweis:</strong> 5 CPS = 5 neue Anrufe können pro Sekunde starten.
                        Bis zu 10 Gespräche können gleichzeitig aktiv sein.
                        <br>Alternative: 1 CPS für €100/Monat verfügbar.
                    </p>
                </div>

                <!-- Sample Invoice -->
                <div class="border-2 border-gray-300 rounded-xl overflow-hidden">
                    <!-- Invoice Header -->
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-xl font-bold">RECHNUNG</p>
                                <p class="text-gray-300 text-sm">AskPro AI Voice Services</p>
                            </div>
                            <div class="text-right">
                                <p class="text-gray-300 text-xs">Rechnungsnummer</p>
                                <p class="font-mono">INV-2026-XXXX</p>
                                <p class="text-gray-300 text-xs mt-1">Datum: <span id="invoice-date"></span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Body -->
                    <div class="p-4 bg-white">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b-2 border-gray-200">
                                    <th class="text-left py-2 font-semibold text-gray-700">Position</th>
                                    <th class="text-right py-2 font-semibold text-gray-700">Menge</th>
                                    <th class="text-right py-2 font-semibold text-gray-700">Einzelpreis</th>
                                    <th class="text-right py-2 font-semibold text-gray-700">Gesamt</th>
                                </tr>
                            </thead>
                            <tbody id="invoice-items">
                                <!-- Will be filled by JavaScript -->
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-gray-300">
                                    <td colspan="3" class="py-2 text-right font-semibold text-gray-700">Netto:</td>
                                    <td class="py-2 text-right font-bold text-gray-900" id="invoice-net">€0,00</td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="py-1 text-right text-gray-600">MwSt. (19%):</td>
                                    <td class="py-1 text-right text-gray-600" id="invoice-vat">€0,00</td>
                                </tr>
                                <tr class="bg-amber-50">
                                    <td colspan="3" class="py-3 text-right font-bold text-amber-800 text-lg">Gesamtbetrag:</td>
                                    <td class="py-3 text-right font-bold text-amber-800 text-lg" id="invoice-total">€0,00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Invoice Footer -->
                    <div class="bg-gray-100 p-3 text-xs text-gray-600 border-t">
                        <p><strong>Zahlungsziel:</strong> 14 Tage netto | <strong>Bankverbindung:</strong> IBAN DE89 XXXX XXXX XXXX XXXX XX</p>
                    </div>
                </div>

                <!-- Scenario Comparison Cards -->
                <div class="mt-6">
                    <h4 class="font-semibold text-gray-800 mb-2">Kundenrechnung pro Monat nach Partnergröße</h4>
                    <p class="text-sm text-gray-600 mb-4">
                        <strong>Staffelpreise:</strong>
                        <span class="inline-flex items-center gap-1 ml-2"><span class="w-5 h-5 bg-gray-400 text-white rounded text-xs flex items-center justify-center font-bold">S</span> 1–19 Unternehmen = €0,29/min</span>
                        <span class="inline-flex items-center gap-1 ml-3"><span class="w-5 h-5 bg-blue-500 text-white rounded text-xs flex items-center justify-center font-bold">M</span> ab 20 = €0,27/min</span>
                        <span class="inline-flex items-center gap-1 ml-3"><span class="w-5 h-5 bg-purple-500 text-white rounded text-xs flex items-center justify-center font-bold">L</span> ab 40 = €0,24/min</span>
                    </p>
                    <div class="grid md:grid-cols-3 gap-4" id="invoice-scenarios">
                        <!-- Will be filled by JavaScript -->
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        * Basierend auf <span id="invoice-calls-info">20 Anrufe/Tag × 3 Min</span>.
                        Der günstigere Minutenpreis gilt für <strong>alle</strong> Unternehmen des Partners.
                    </p>
                </div>

                <!-- Margin Analysis -->
                <div class="mt-6 bg-green-50 rounded-lg p-4 border border-green-200">
                    <h4 class="font-semibold text-green-800 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Deine Marge pro Kunde
                    </h4>
                    <div class="grid md:grid-cols-2 gap-4" id="margin-analysis">
                        <!-- Will be filled by JavaScript -->
                    </div>
                </div>

                <!-- Additional Services Note with real prices -->
                <div class="mt-4 bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <h4 class="font-semibold text-blue-800 mb-2">Gebühren für Änderungen (nach Abnahme)</h4>
                    <div class="grid md:grid-cols-2 gap-3 text-sm">
                        <div class="bg-white rounded p-2 border border-blue-100">
                            <span class="text-gray-700">Kleine Anpassung (Text, Prompt)</span>
                            <span class="float-right font-semibold text-blue-800">€250</span>
                        </div>
                        <div class="bg-white rounded p-2 border border-blue-100">
                            <span class="text-gray-700">Call Flow / AI Agent Anpassung</span>
                            <span class="float-right font-semibold text-blue-800">€500</span>
                        </div>
                        <div class="bg-white rounded p-2 border border-blue-100">
                            <span class="text-gray-700">Webhook-Integration / Gateway</span>
                            <span class="float-right font-semibold text-blue-800">€500</span>
                        </div>
                        <div class="bg-white rounded p-2 border border-blue-100">
                            <span class="text-gray-700">Komplexe Änderung (mehrere Bereiche)</span>
                            <span class="float-right font-semibold text-amber-700">nach Absprache</span>
                        </div>
                        <div class="bg-white rounded p-2 border border-blue-100 md:col-span-2">
                            <span class="text-gray-700">Support & Beratung</span>
                            <span class="float-right font-semibold text-amber-700">nach Absprache (inkl. Kontingent)</span>
                        </div>
                    </div>
                    <p class="text-xs text-blue-600 mt-2">
                        <strong>CPS-Pakete:</strong> 1 CPS (€100/Mo) • 5 CPS (€200/Mo) • &gt;5 CPS: nach Absprache
                    </p>
                </div>

                <!-- Copyable Quote Template -->
                <div class="mt-6 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-5 border-2 border-emerald-200">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-bold text-emerald-800 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Angebot zum Kopieren
                        </h4>
                        <button onclick="copyQuote()" id="copyQuoteBtn" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2 min-w-[140px] min-h-[44px] justify-center" aria-label="Angebot in Zwischenablage kopieren">
                            <span class="copy-default flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                </svg>
                                Kopieren
                            </span>
                            <span class="copy-success items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Kopiert!
                            </span>
                        </button>
                    </div>
                    <div id="quote-template" class="bg-white rounded-lg p-4 font-mono text-sm whitespace-pre-wrap border border-emerald-200 max-h-96 overflow-y-auto">
                        <!-- Will be filled by JavaScript -->
                    </div>
                    <p class="text-xs text-emerald-600 mt-2">
                        Dieses Angebot wird automatisch basierend auf deinen Eingaben generiert.
                        Du kannst es direkt in E-Mails oder Dokumente einfügen.
                    </p>
                </div>
            </div>
        </section>

        <!-- Email Template -->
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8" id="email-section">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-800">E-Mail Vorlage für Retell Support</h2>
                <button id="copyBtn" onclick="copyEmail()" class="copy-btn bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                    <span class="copy-default flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                        </svg>
                        Kopieren
                    </span>
                    <span class="copy-success items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Kopiert!
                    </span>
                </button>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 mb-4 border">
                <p class="text-sm text-gray-600 mb-1"><strong>An:</strong> support@retellai.com</p>
                <p class="text-sm text-gray-600"><strong>Betreff:</strong> Twilio CPS Pricing & Multi-Tenant Architecture Guidance - Account fabhandy@gmail.com</p>
            </div>

            <div id="email-content" class="bg-gray-900 text-gray-100 rounded-lg p-6 font-mono text-sm overflow-x-auto">
<pre>Hi Retell Team,

I'm reaching out regarding our multi-tenant voice AI deployment using your platform.
We have questions about Twilio CPS limits and need your guidance on the best architecture.

ACCOUNT INFORMATION
-------------------
Account Email: fabhandy@gmail.com
Account Holder: Fabian Spitzer (Fab)
Current Plan: Pay-as-you-go
Telephony Provider: Twilio
Company Type: Early-stage Startup

CURRENT SETTINGS (from our Limits page)
---------------------------------------
- Concurrent Calls Limit: 20
- LLM Token Limit: 32768
- Telnyx CPS: 1
- Twilio CPS: 1 (this is our primary concern)
- Custom Telephony CPS: 1

OUR USE CASE - SIGNIFICANT VOLUME
---------------------------------
We're building a multi-tenant AI voice platform for enterprise support hotlines:

- Target: ~20 enterprise customers in the next months
- Volume per customer: ~20 calls per day (400 total calls/day)
- CRITICAL: These are SUPPORT HOTLINES - peak scenarios are unavoidable
- Peak scenario: Up to 10 simultaneous inbound calls per customer
  (e.g., after service outages, product issues, or marketing campaigns)
- Each customer has their own dedicated phone number via Twilio

Projected monthly volume: 20 customers x 20 calls x 30 days = 12,000+ calls/month

THE CHALLENGE - INBOUND CPS FOR SUPPORT HOTLINES
------------------------------------------------
With Twilio CPS = 1, only one new call can start per second. For support hotlines,
this is a critical problem:

SCENARIO: A customer's service goes down. 10 frustrated users call the support
hotline within the same second.
- With CPS=1: Only 1 caller gets through. 9 callers hear a busy signal.
- This is unacceptable for our enterprise customers who rely on us for support.

Since these are INBOUND calls (customers calling in), we're concerned that
exceeded CPS limits result in rejected calls rather than queued calls.

OUR QUESTIONS
-------------
1. INBOUND CPS BEHAVIOR (CRITICAL)
   What happens when inbound CPS limit is exceeded?
   - Are calls queued and connected in the next second?
   - Or are they rejected with a busy signal / SIP error?
   This is critical for support hotline use cases.

2. CPS SCOPE
   We see the following Twilio CPS pricing:
   - 2 CPS = $25/month
   - 3 CPS = $50/month
   - 4 CPS = $75/month
   - 5 CPS = $100/month

   Is this pricing per-account (shared across all numbers) or per-phone-number?

3. ARCHITECTURE RECOMMENDATION
   For 20 enterprise customers with potential 10+ simultaneous inbound calls each:
   - What CPS level do you recommend?
   - Should we use multiple accounts? Multiple numbers per customer?
   - What's your recommended architecture for this scale?

4. STARTUP-FRIENDLY PRICING
   We're an early-stage startup. Our business model depends on Retell working
   reliably for our customers. However, we face a challenge:

   - We only generate revenue when our customers use the platform
   - High upfront costs for CPS/concurrency before revenue comes in is difficult
   - We need a solution that scales WITH our revenue

   Are there options for:
   - Usage-based CPS pricing (pay per call, not flat monthly)?
   - Startup programs with reduced initial costs?
   - Trial period with higher limits to validate our use case?
   - Graduated pricing that grows with our usage?

5. ENTERPRISE PATH
   As we scale beyond 20 customers:
   - At what volume do we qualify for enterprise pricing?
   - What would enterprise CPS limits look like?
   - Can we discuss a partnership or early-adopter program?

WHAT WE NEED
------------
1. Clear answer on inbound CPS behavior (queue vs. reject)
2. Recommended architecture for our multi-tenant support hotline use case
3. Flexible pricing options that work for an early-stage startup
4. A path to scale without prohibitive upfront costs

We're committed to building on Retell and want to become a significant customer
as we grow. We'd love to find a solution that works for both sides.

Would you be available for a quick call to discuss the optimal setup?

Best regards,
Fabian Spitzer
fabhandy@gmail.com

P.S. We're happy to provide case studies or testimonials as we grow -
we believe in the Retell platform and want to make this partnership work.</pre>
            </div>

            <div class="mt-4 flex gap-3">
                <button onclick="copyEmail()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-medium transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                    </svg>
                    E-Mail Text kopieren
                </button>
                <a href="mailto:support@retellai.com?subject=Twilio%20CPS%20Pricing%20%26%20Multi-Tenant%20Architecture%20Guidance%20-%20Account%20fabhandy%40gmail.com"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-medium transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    In Mail-App öffnen
                </a>
            </div>
        </section>

        <!-- Strategic Options -->
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Strategische Optionen</h2>

            <div class="space-y-4">
                <div class="border border-gray-200 rounded-lg p-5">
                    <div class="flex items-start gap-3">
                        <span class="w-8 h-8 bg-green-100 text-green-600 rounded-lg flex items-center justify-center font-bold">A</span>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800">CPS auf 5 erhöhen</h3>
                            <p class="text-gray-600 text-sm mt-1">Einfachste Lösung: $100/Monat für 5 neue Anrufe/Sekunde.</p>
                            <div class="mt-2 text-sm">
                                <span class="text-green-600">Einfach</span>
                                <span class="text-gray-400 mx-2">|</span>
                                <span class="text-amber-600">Begrenzt auf 5 CPS</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-5">
                    <div class="flex items-start gap-3">
                        <span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center font-bold">B</span>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800">Multi-Account Architektur</h3>
                            <p class="text-gray-600 text-sm mt-1">Jeder Kunde bekommt eigenen Retell-Account mit eigenem CPS-Limit.</p>
                            <div class="mt-2 text-sm">
                                <span class="text-green-600">Skalierbar</span>
                                <span class="text-gray-400 mx-2">|</span>
                                <span class="text-amber-600">Komplexeres Management</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-5">
                    <div class="flex items-start gap-3">
                        <span class="w-8 h-8 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center font-bold">C</span>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800">Enterprise-Verhandlung</h3>
                            <p class="text-gray-600 text-sm mt-1">Mit Retell verhandeln für custom CPS-Limits und Volumenrabatte.</p>
                            <div class="mt-2 text-sm">
                                <span class="text-green-600">Beste Preise möglich</span>
                                <span class="text-gray-400 mx-2">|</span>
                                <span class="text-amber-600">Erfordert Gespräch</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Next Steps -->
        <section class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl border border-indigo-100 p-6">
            <h2 class="text-xl font-semibold text-indigo-900 mb-4">Nächste Schritte</h2>

            <ol class="space-y-3">
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 bg-indigo-600 text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">1</span>
                    <div>
                        <p class="font-medium text-indigo-900">E-Mail an Retell senden</p>
                        <p class="text-indigo-700 text-sm">Vorlage oben kopieren und absenden</p>
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 bg-indigo-400 text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">2</span>
                    <div>
                        <p class="font-medium text-indigo-900">Antwort abwarten</p>
                        <p class="text-indigo-700 text-sm">Klärung zu CPS-Scope (per-account vs. per-number)</p>
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 bg-indigo-300 text-white rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">3</span>
                    <div>
                        <p class="font-medium text-indigo-900">Architektur-Entscheidung</p>
                        <p class="text-indigo-700 text-sm">Basierend auf Retell-Feedback die beste Option wählen</p>
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span class="w-6 h-6 bg-indigo-200 text-indigo-600 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0">4</span>
                    <div>
                        <p class="font-medium text-indigo-900">Monitoring implementieren</p>
                        <p class="text-indigo-700 text-sm">CPS/Concurrency-Tracking im AskPro-Code einbauen</p>
                    </div>
                </li>
            </ol>
        </section>

        <!-- ================================================================== -->
        <!-- NEXT STEPS & IMPLEMENTATION PLAN                                    -->
        <!-- ================================================================== -->
        <section id="next-steps" class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl shadow-lg border-2 border-emerald-200 p-6 mb-8">
            <h2 class="text-xl font-bold text-emerald-900 mb-4 flex items-center gap-3">
                <span class="w-10 h-10 bg-emerald-500 text-white rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </span>
                Nächste Schritte (nach Retell-Antwort)
            </h2>

            <div class="grid md:grid-cols-3 gap-4">
                <div class="bg-white/70 rounded-lg p-4 border border-emerald-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 bg-emerald-500 text-white rounded-full flex items-center justify-center text-xs font-bold">1</span>
                        <h3 class="font-semibold text-emerald-800">Architektur-Entscheidung</h3>
                    </div>
                    <ul class="text-sm text-emerald-700 space-y-1 ml-8">
                        <li>• Multi-Number vs. Per-Number?</li>
                        <li>• Hunting Groups sinnvoll?</li>
                        <li>• Enterprise-Deal möglich?</li>
                    </ul>
                </div>

                <div class="bg-white/70 rounded-lg p-4 border border-emerald-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 bg-emerald-500 text-white rounded-full flex items-center justify-center text-xs font-bold">2</span>
                        <h3 class="font-semibold text-emerald-800">Kosten-Kalkulation</h3>
                    </div>
                    <ul class="text-sm text-emerald-700 space-y-1 ml-8">
                        <li>• Calculator oben anpassen</li>
                        <li>• Echte CPS-Kosten eintragen</li>
                        <li>• Break-Even berechnen</li>
                    </ul>
                </div>

                <div class="bg-white/70 rounded-lg p-4 border border-emerald-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-6 h-6 bg-emerald-500 text-white rounded-full flex items-center justify-center text-xs font-bold">3</span>
                        <h3 class="font-semibold text-emerald-800">Implementierung</h3>
                    </div>
                    <ul class="text-sm text-emerald-700 space-y-1 ml-8">
                        <li>• Monitoring einbauen</li>
                        <li>• Alerting konfigurieren</li>
                        <li>• Dashboard-Widget</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="implementation-phases" class="bg-gradient-to-br from-slate-50 to-gray-100 rounded-xl shadow-lg border border-gray-300 p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-3">
                <span class="w-10 h-10 bg-gray-700 text-white rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </span>
                Implementierungsplan
            </h2>

            <div class="space-y-4">
                <div class="flex gap-4 items-start">
                    <div class="flex-shrink-0 w-24 text-right">
                        <span class="text-xs text-gray-500">Phase 1</span>
                        <p class="font-semibold text-gray-700">2-3h</p>
                    </div>
                    <div class="flex-1 bg-white rounded-lg p-4 border border-gray-200">
                        <h3 class="font-semibold text-gray-800 mb-1">Basis-Monitoring</h3>
                        <p class="text-sm text-gray-600">Retell GET /get-concurrency API integrieren, Redis-basiertes Tracking, Filament Dashboard-Widget</p>
                    </div>
                </div>

                <div class="flex gap-4 items-start">
                    <div class="flex-shrink-0 w-24 text-right">
                        <span class="text-xs text-gray-500">Phase 2</span>
                        <p class="font-semibold text-gray-700">1-2h</p>
                    </div>
                    <div class="flex-1 bg-white rounded-lg p-4 border border-gray-200">
                        <h3 class="font-semibold text-gray-800 mb-1">Alerting</h3>
                        <p class="text-sm text-gray-600">80% Auslastungs-Warning, Slack/Email Benachrichtigung, Log-Aggregation</p>
                    </div>
                </div>

                <div class="flex gap-4 items-start">
                    <div class="flex-shrink-0 w-24 text-right">
                        <span class="text-xs text-gray-500">Phase 3</span>
                        <p class="font-semibold text-gray-700">2-3h</p>
                    </div>
                    <div class="flex-1 bg-white rounded-lg p-4 border border-gray-200">
                        <h3 class="font-semibold text-gray-800 mb-1">Graceful Handling</h3>
                        <p class="text-sm text-gray-600">429-Error-Handler, Queue für wartende Anrufe (optional), Fallback-Strategie</p>
                    </div>
                </div>
            </div>

            <div class="mt-4 p-3 bg-amber-50 rounded-lg border border-amber-200">
                <p class="text-sm text-amber-800">
                    <strong>⚠️ Hinweis:</strong> Implementierung erst nach Klärung der Concurrency-Architektur mit Retell starten.
                </p>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-100 border-t border-gray-200 py-6 mt-10">
        <div class="max-w-5xl mx-auto px-6 text-center text-gray-500 text-sm">
            <p>AskPro AI Gateway - Interne Dokumentation</p>
            <p class="mt-1">Erstellt: {{ now()->format('d.m.Y') }} | Retell.ai Integration</p>
        </div>
    </footer>

    <script>
        // =====================================================
        // CONFIGURATION - Preismodell (Unternehmensbasiert)
        // =====================================================
        const CONFIG = {
            // Partner-Perspektive (Selbstkosten)
            cps: 5,                    // Calls Per Second Modell (1 oder 5)
            fixgebuehr: 200,           // € pro Unternehmen/Monat (1 CPS=50€, 5 CPS=200€)
            selbstkosten: 0.113,       // € pro Minute (Retell + Twilio SIP)
            nummernkosten: 1.15,       // € pro Telefonnummer/Monat (Twilio)
            tage_pro_monat: 30,

            // ═══════════════════════════════════════════════════════════════
            // KUNDENPREISE (aus ServiceFeeTemplate Seeder)
            // ═══════════════════════════════════════════════════════════════
            kunde: {
                // Setup (einmalig)
                setup: {
                    basic: 500,         // SETUP_BASIC - Basis-Einrichtung
                    professional: 1500, // SETUP_PROFESSIONAL - Standard
                    enterprise: 5000,   // SETUP_ENTERPRISE - Komplett
                },
                // Bereitstellung (monatlich) - CPS-Pakete
                // Hinweis: >5 CPS nur nach individueller Absprache
                bereitstellung: {
                    cps1: 100,   // 1 CPS - Basis
                    cps5: 200,   // 5 CPS - Standard (empfohlen)
                },
                // Änderungsgebühren (einmalig)
                // Hinweis: Call Flow und AI Agent sind identisch (€500)
                aenderungen: {
                    minor: 250,       // CHANGE_MINOR - Kleine Anpassung
                    callflow: 500,    // CHANGE_FLOW / CHANGE_AGENT - Call Flow / AI Agent Anpassung
                    gateway: 500,     // CHANGE_GATEWAY - Webhook-Integration
                    complex: 'n.A.',  // Nach Absprache - komplexe Änderungen
                },
                // Support
                // Hinweis: Nach Absprache - inkl. gewisses Kontingent, danach Abrechnung
                support: {
                    info: 'Nach Absprache (inkl. Kontingent)',
                },
            },

            // Staffelpreise nach Unternehmensanzahl
            // Der günstigere Preis gilt für ALLE Unternehmen des Partners
            pakete: {
                'S':  { maxCompanies: 19,  preis: 0.29, label: 'Standard',   gradient: 'from-gray-400 to-gray-500' },
                'M':  { maxCompanies: 39,  preis: 0.27, label: 'Partner',    gradient: 'from-blue-500 to-blue-600' },
                'L':  { maxCompanies: 999, preis: 0.24, label: 'Enterprise', gradient: 'from-purple-500 to-purple-600' }
            }
        };

        // Deep clone für Reset-Funktion
        const DEFAULT_CONFIG = JSON.parse(JSON.stringify(CONFIG));

        // =====================================================
        // CONFIG MANAGEMENT SYSTEM
        // =====================================================
        let configIsDirty = false;

        // Toggle config panel
        function toggleConfigPanel() {
            const content = document.getElementById('config-content');
            const icon = document.getElementById('toggle-config-icon');
            const btn = document.getElementById('toggle-config-btn');
            const isExpanded = btn.getAttribute('aria-expanded') === 'true';

            content.classList.toggle('hidden', isExpanded);
            icon.classList.toggle('rotate-180', !isExpanded);
            btn.setAttribute('aria-expanded', !isExpanded);
        }

        // Handle CPS model change (radio buttons)
        function handleCpsChange(radio) {
            const fixgebuehr = parseFloat(radio.dataset.fixgebuehr);
            const cps = parseInt(radio.value);

            // Update CONFIG
            CONFIG.fixgebuehr = fixgebuehr;
            CONFIG.cps = cps;

            // Update hidden input for compatibility
            const hiddenInput = document.getElementById('config-fixgebuehr');
            if (hiddenInput) {
                hiddenInput.value = fixgebuehr;
            }

            // Trigger full update
            updateDirtyState();
            updatePackagePriceTable();
            updateCalculator();
        }

        // Handle any config input change
        function handleConfigChange(input) {
            const value = parseFloat(input.value);
            if (isNaN(value)) return;

            // Update CONFIG based on input ID
            const id = input.id;
            if (id === 'config-preis-s') CONFIG.pakete.S.preis = value;
            else if (id === 'config-preis-m') CONFIG.pakete.M.preis = value;
            else if (id === 'config-preis-l') CONFIG.pakete.L.preis = value;
            else if (id === 'config-fixgebuehr') CONFIG.fixgebuehr = value;
            else if (id === 'config-selbstkosten') CONFIG.selbstkosten = value;
            else if (id === 'config-nummernkosten') CONFIG.nummernkosten = value;
            else if (id === 'config-tage') CONFIG.tage_pro_monat = value;

            // Mark as modified
            markInputModified(input);
            updateConfigMargins();
            updateDirtyState();

            // Live update all displays
            updatePackagePriceTable();
            updateCalculator();
        }

        // Mark individual input as modified
        function markInputModified(input) {
            const defaultValue = parseFloat(input.dataset.default);
            const currentValue = parseFloat(input.value);
            const isModified = Math.abs(defaultValue - currentValue) > 0.001;

            input.classList.toggle('border-amber-500', isModified);
            input.classList.toggle('bg-amber-50', isModified);

            // Update card's modified dot
            const card = input.closest('.package-config-card');
            if (card) {
                const dot = card.querySelector('.config-modified-dot');
                if (dot) dot.classList.toggle('hidden', !isModified);
                card.classList.toggle('border-amber-400', isModified);
            }
        }

        // Update margin displays for each package (in config panel)
        function updateConfigMargins() {
            const costs = CONFIG.selbstkosten;
            ['S', 'M', 'L'].forEach(pkg => {
                const price = CONFIG.pakete[pkg].preis;
                const margin = ((price - costs) / price) * 100;
                const el = document.getElementById(`config-margin-${pkg.toLowerCase()}`);
                if (el) {
                    el.textContent = `${margin.toFixed(0)}%`;
                    el.className = margin < 30 ? 'font-semibold text-red-600'
                                 : margin < 50 ? 'font-semibold text-amber-600'
                                 : 'font-semibold text-green-600';
                }
            });
        }

        // Update the static package price table with dynamic values
        function updatePackagePriceTable() {
            const costs = CONFIG.selbstkosten;
            const priceS = CONFIG.pakete.S.preis;
            const priceM = CONFIG.pakete.M.preis;
            const priceL = CONFIG.pakete.L.preis;

            // Update prices
            const setPriceCell = (id, price) => {
                const el = document.getElementById(id);
                if (el) el.textContent = `€${price.toFixed(2)}`;
            };
            setPriceCell('pkg-price-S', priceS);
            setPriceCell('pkg-price-M', priceM);
            setPriceCell('pkg-price-L', priceL);

            // Update price differences (relative to S)
            const setDiffCell = (id, diff) => {
                const el = document.getElementById(id);
                if (el) el.textContent = diff < 0 ? `-€${Math.abs(diff).toFixed(2)}` : `+€${diff.toFixed(2)}`;
            };
            setDiffCell('pkg-diff-M', priceM - priceS);
            setDiffCell('pkg-diff-L', priceL - priceS);

            // Update margins
            const setMarginCell = (id, price) => {
                const margin = ((price - costs) / price) * 100;
                const el = document.getElementById(id);
                if (el) {
                    el.textContent = `${margin.toFixed(0)}%`;
                    // Update color class
                    el.className = `px-2 py-1 rounded text-xs font-medium ${
                        margin >= 50 ? 'bg-green-100 text-green-700' :
                        margin >= 30 ? 'bg-yellow-100 text-yellow-700' :
                        'bg-red-100 text-red-700'
                    }`;
                }
            };
            setMarginCell('pkg-margin-S', priceS);
            setMarginCell('pkg-margin-M', priceM);
            setMarginCell('pkg-margin-L', priceL);

            // Update ranges based on CONFIG thresholds
            const rangeS = document.getElementById('pkg-range-S');
            const rangeM = document.getElementById('pkg-range-M');
            const rangeL = document.getElementById('pkg-range-L');
            if (rangeS) rangeS.textContent = `1–${CONFIG.pakete.S.maxCompanies}`;
            if (rangeM) rangeM.textContent = `${CONFIG.pakete.S.maxCompanies + 1}–${CONFIG.pakete.M.maxCompanies}`;
            if (rangeL) rangeL.textContent = `${CONFIG.pakete.M.maxCompanies + 1}+`;

            // Update incentive text
            const incentiveEl = document.getElementById('pkg-incentive-text');
            if (incentiveEl) {
                incentiveEl.innerHTML = `💡 <strong>Incentive:</strong> Ein Partner mit ${CONFIG.pakete.S.maxCompanies + 1} Unternehmen zahlt für <em>alle ${CONFIG.pakete.S.maxCompanies + 1}</em> nur €${priceM.toFixed(2)}/min statt €${priceS.toFixed(2)}/min!`;
            }
        }

        // Update dirty state indicator
        function updateDirtyState() {
            const isDirty = JSON.stringify(CONFIG) !== JSON.stringify(DEFAULT_CONFIG);
            configIsDirty = isDirty;
            const statusEl = document.getElementById('config-status');
            if (statusEl) statusEl.classList.toggle('hidden', !isDirty);
        }

        // Reset config to defaults
        function resetConfig() {
            if (!confirm('Alle Preise auf Standard zurücksetzen?')) return;

            // Reset CONFIG
            Object.assign(CONFIG, JSON.parse(JSON.stringify(DEFAULT_CONFIG)));

            // Update form inputs
            applyConfigToForm();
            updateConfigMargins();
            updateDirtyState();
            updateCalculator();

            // Clear localStorage
            localStorage.removeItem('retell_pricing_config');
            showToast('Auf Standardwerte zurückgesetzt', 'success');
        }

        // Save config to localStorage
        function saveConfig() {
            localStorage.setItem('retell_pricing_config', JSON.stringify({
                pakete: {
                    S: { preis: CONFIG.pakete.S.preis },
                    M: { preis: CONFIG.pakete.M.preis },
                    L: { preis: CONFIG.pakete.L.preis }
                },
                cps: CONFIG.cps,
                fixgebuehr: CONFIG.fixgebuehr,
                selbstkosten: CONFIG.selbstkosten,
                nummernkosten: CONFIG.nummernkosten,
                tage_pro_monat: CONFIG.tage_pro_monat
            }));
            localStorage.setItem('retell_pricing_saved_at', new Date().toISOString());

            // Update default to current (so dirty state clears)
            Object.assign(DEFAULT_CONFIG, JSON.parse(JSON.stringify(CONFIG)));
            updateDirtyState();

            // Re-mark all inputs (clears modified state)
            document.querySelectorAll('[id^="config-"]').forEach(input => {
                if (input.type === 'number') {
                    input.dataset.default = input.value;
                    markInputModified(input);
                }
            });

            showToast('Einstellungen gespeichert', 'success');
        }

        // Share config via URL
        function shareConfig() {
            const configStr = btoa(JSON.stringify({
                s: CONFIG.pakete.S.preis,
                m: CONFIG.pakete.M.preis,
                l: CONFIG.pakete.L.preis,
                cps: CONFIG.cps,
                fix: CONFIG.fixgebuehr,
                sk: CONFIG.selbstkosten
            }));
            const url = new URL(window.location);
            url.searchParams.set('pricing', configStr);

            navigator.clipboard.writeText(url.toString())
                .then(() => showToast('Link in Zwischenablage kopiert!', 'success'))
                .catch(() => showToast('Fehler beim Kopieren', 'error'));
        }

        // Load config from localStorage or URL
        function loadConfig() {
            // 1. Check URL params first
            const params = new URLSearchParams(window.location.search);
            if (params.has('pricing')) {
                try {
                    const data = JSON.parse(atob(params.get('pricing')));
                    if (data.s) CONFIG.pakete.S.preis = data.s;
                    if (data.m) CONFIG.pakete.M.preis = data.m;
                    if (data.l) CONFIG.pakete.L.preis = data.l;
                    if (data.cps) {
                        CONFIG.cps = data.cps;
                        CONFIG.fixgebuehr = data.cps === 1 ? 50 : 200;
                    }
                    if (data.fix) CONFIG.fixgebuehr = data.fix;
                    if (data.sk) CONFIG.selbstkosten = data.sk;
                    console.log('Config loaded from URL');
                    return true;
                } catch (e) {
                    console.warn('Invalid URL config');
                }
            }

            // 2. Check localStorage
            const saved = localStorage.getItem('retell_pricing_config');
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    if (data.pakete?.S?.preis) CONFIG.pakete.S.preis = data.pakete.S.preis;
                    if (data.pakete?.M?.preis) CONFIG.pakete.M.preis = data.pakete.M.preis;
                    if (data.pakete?.L?.preis) CONFIG.pakete.L.preis = data.pakete.L.preis;
                    if (data.cps) {
                        CONFIG.cps = data.cps;
                        CONFIG.fixgebuehr = data.cps === 1 ? 50 : 200;
                    }
                    if (data.fixgebuehr) CONFIG.fixgebuehr = data.fixgebuehr;
                    if (data.selbstkosten) CONFIG.selbstkosten = data.selbstkosten;
                    if (data.nummernkosten) CONFIG.nummernkosten = data.nummernkosten;
                    if (data.tage_pro_monat) CONFIG.tage_pro_monat = data.tage_pro_monat;
                    console.log('Config loaded from localStorage');
                    return true;
                } catch (e) {
                    console.warn('Invalid localStorage config');
                }
            }
            return false;
        }

        // Apply CONFIG values to form inputs
        function applyConfigToForm() {
            const setValue = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.value = value;
            };

            setValue('config-preis-s', CONFIG.pakete.S.preis);
            setValue('config-preis-m', CONFIG.pakete.M.preis);
            setValue('config-preis-l', CONFIG.pakete.L.preis);
            setValue('config-fixgebuehr', CONFIG.fixgebuehr);
            setValue('config-selbstkosten', CONFIG.selbstkosten);
            setValue('config-nummernkosten', CONFIG.nummernkosten);
            setValue('config-tage', CONFIG.tage_pro_monat);

            // Set CPS radio button based on CONFIG.cps
            const cpsRadio = document.querySelector(`input[name="cps-model"][value="${CONFIG.cps}"]`);
            if (cpsRadio) cpsRadio.checked = true;

            // Update modified indicators
            document.querySelectorAll('[id^="config-"]').forEach(input => {
                if (input.type === 'number') markInputModified(input);
            });
        }

        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-xl text-white font-medium z-50 transform transition-all duration-300 ${
                type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-gray-800'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Ermittle das Paket basierend auf der Unternehmensanzahl (DYNAMIC - uses CONFIG)
        function getPackageForCompanyCount(companies) {
            if (companies <= CONFIG.pakete.S.maxCompanies) return 'S';
            if (companies <= CONFIG.pakete.M.maxCompanies) return 'M';
            return 'L';
        }

        // =====================================================
        // HELPER FUNCTIONS
        // =====================================================
        function formatEuro(value) {
            return '€' + value.toLocaleString('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }

        function formatEuroDecimal(value) {
            return '€' + value.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function formatPercent(value) {
            return value.toFixed(0) + '%';
        }

        // =====================================================
        // TOOLTIP UPDATES
        // =====================================================
        function updateTooltips(companies, result) {
            // Umsatz-Tooltip
            document.getElementById('tooltip-fix-calc').textContent =
                `${companies} Firmen × €200 = ${formatEuro(result.fixRevenue)}`;
            document.getElementById('tooltip-minute-calc').textContent =
                `${result.totalMinuten.toLocaleString('de-DE')} min × €${result.preis.toFixed(2)}`;
            document.getElementById('tooltip-revenue-total').textContent = formatEuro(result.totalRevenue);

            // Kosten-Tooltip
            document.getElementById('tooltip-number-calc').textContent =
                `${companies} × €1.15/Mo = ${formatEuroDecimal(result.numberCosts)}`;
            document.getElementById('tooltip-mincost-calc').textContent =
                `${result.totalMinuten.toLocaleString('de-DE')} min × €0.113`;
            document.getElementById('tooltip-costs-total').textContent = formatEuro(result.totalCosts);

            // Gewinn-Tooltip
            document.getElementById('tooltip-profit-revenue').textContent = formatEuro(result.totalRevenue);
            document.getElementById('tooltip-profit-costs').textContent = `−${formatEuro(result.totalCosts)}`;
            document.getElementById('tooltip-profit-total').textContent = formatEuro(result.profit);
            document.getElementById('tooltip-margin-value').textContent = formatPercent(result.margin);

            // Gewinn/Kunde-Tooltip
            document.getElementById('tooltip-percust-profit').textContent = formatEuro(result.profit);
            document.getElementById('tooltip-percust-count').textContent = `${companies} Kunden`;
            document.getElementById('tooltip-percust-result').textContent = formatEuro(result.profitPerCustomer);
        }

        // =====================================================
        // CALCULATOR LOGIC
        // =====================================================
        function calculateScenario(companies, callsPerDay, duration, packageKey = null) {
            // Automatische Paketermittlung wenn nicht explizit angegeben
            const actualPackageKey = packageKey || getPackageForCompanyCount(companies);
            const pkg = CONFIG.pakete[actualPackageKey];

            // Basis-Minuten pro Unternehmen/Monat (keine Multiplier mehr, da Preise fix)
            const basisMinutenProUnternehmen = callsPerDay * duration * CONFIG.tage_pro_monat;
            const totalMinuten = basisMinutenProUnternehmen * companies;

            // Umsatz
            const fixRevenue = companies * CONFIG.fixgebuehr;
            const minuteRevenue = totalMinuten * pkg.preis;
            const totalRevenue = fixRevenue + minuteRevenue;

            // Kosten
            const numberCosts = companies * CONFIG.nummernkosten;
            const minuteCosts = totalMinuten * CONFIG.selbstkosten;
            const totalCosts = numberCosts + minuteCosts;

            // Gewinn
            const profit = totalRevenue - totalCosts;
            const margin = totalRevenue > 0 ? (profit / totalRevenue) * 100 : 0;
            const profitPerCustomer = companies > 0 ? profit / companies : 0;

            return {
                companies,
                basisMinutenProUnternehmen,
                totalMinuten,
                fixRevenue,
                minuteRevenue,
                totalRevenue,
                numberCosts,
                minuteCosts,
                totalCosts,
                profit,
                margin,
                profitPerCustomer,
                preis: pkg.preis,
                packageKey: actualPackageKey,
                packageLabel: pkg.label,
                packageGradient: pkg.gradient
            };
        }

        function setCompanies(value) {
            document.getElementById('calc-companies').value = value;
            updateCalculator();
        }

        function updateCalculator() {
            // Get input values with NaN fallback protection
            const companiesRaw = parseInt(document.getElementById('calc-companies').value);
            const callsPerDayRaw = parseInt(document.getElementById('calc-calls').value);
            const durationRaw = parseFloat(document.getElementById('calc-duration').value);

            const companies = isNaN(companiesRaw) ? 10 : companiesRaw;
            const callsPerDay = isNaN(callsPerDayRaw) ? 20 : callsPerDayRaw;
            const duration = isNaN(durationRaw) ? 3 : durationRaw;

            // Update display values
            document.getElementById('calc-companies-value').textContent = companies;
            document.getElementById('calc-calls-value').textContent = callsPerDay;
            document.getElementById('calc-duration-value').textContent = duration;

            // Calculate main scenario (Paket wird automatisch ermittelt)
            const result = calculateScenario(companies, callsPerDay, duration);

            // Update package display
            const packageDisplay = document.getElementById('current-package-display');
            packageDisplay.className = `bg-gradient-to-br ${result.packageGradient} text-white px-4 py-3 rounded-lg text-center transition-all`;
            packageDisplay.innerHTML = `<span class="text-2xl font-bold">${result.packageKey}</span><p class="text-xs opacity-80">€${result.preis.toFixed(2)}/min</p>`;
            document.getElementById('calc-package').value = result.packageKey;

            // Update package table highlighting
            updatePackageTableHighlight(result.packageKey);

            // Update result cards
            document.getElementById('result-revenue').textContent = formatEuro(result.totalRevenue);
            document.getElementById('result-costs').textContent = formatEuro(result.totalCosts);
            document.getElementById('result-profit').textContent = formatEuro(result.profit);
            document.getElementById('result-margin').textContent = formatPercent(result.margin) + ' Marge';
            document.getElementById('result-profit-per-customer').textContent = formatEuro(result.profitPerCustomer);

            // Update detail breakdown
            document.getElementById('detail-companies').textContent = companies;
            document.getElementById('detail-fix-revenue').textContent = formatEuro(result.fixRevenue);
            document.getElementById('detail-minutes').textContent = result.totalMinuten.toLocaleString('de-DE');
            document.getElementById('detail-price').textContent = result.preis.toFixed(2);
            document.getElementById('detail-minute-revenue').textContent = formatEuro(result.minuteRevenue);
            document.getElementById('detail-total-revenue').textContent = formatEuro(result.totalRevenue);
            document.getElementById('detail-numbers').textContent = companies;
            document.getElementById('detail-number-costs').textContent = formatEuroDecimal(result.numberCosts);
            document.getElementById('detail-minutes2').textContent = result.totalMinuten.toLocaleString('de-DE');
            document.getElementById('detail-minute-costs').textContent = formatEuro(result.minuteCosts);
            document.getElementById('detail-total-costs').textContent = formatEuro(result.totalCosts);

            // Update tooltips with detailed calculations
            updateTooltips(companies, result);

            // Update scenario comparison table
            updateScenarioTable(callsPerDay, duration);

            // Update invoice preview
            updateInvoice(companies, callsPerDay, duration, result);
            updateInvoiceScenarios(callsPerDay, duration);
            updateMarginAnalysis(callsPerDay, duration, result);
        }

        // Highlight aktuelles Paket in der statischen Tabelle
        function updatePackageTableHighlight(packageKey) {
            ['S', 'M', 'L'].forEach(key => {
                const row = document.getElementById('package-row-' + key);
                if (row) {
                    if (key === packageKey) {
                        row.classList.add('bg-indigo-100', 'ring-2', 'ring-indigo-400');
                        row.classList.remove('hover:bg-indigo-50');
                    } else {
                        row.classList.remove('bg-indigo-100', 'ring-2', 'ring-indigo-400');
                        row.classList.add('hover:bg-indigo-50');
                    }
                }
            });
        }

        // Szenario-Tabelle mit automatischer Paketzuweisung
        function updateScenarioTable(callsPerDay, duration) {
            // Zeige praktische Beispiele: 10 (S), 20 (M Start), 40 (L Start)
            const scenarios = [10, 20, 40];
            const tbody = document.getElementById('scenario-table');
            const currentCompanies = parseInt(document.getElementById('calc-companies').value);

            let html = '';
            for (const numCompanies of scenarios) {
                // Jedes Szenario bekommt automatisch das richtige Paket
                const result = calculateScenario(numCompanies, callsPerDay, duration);
                const isCurrentSelection = numCompanies === currentCompanies;
                const rowClass = isCurrentSelection ? 'bg-indigo-100 font-semibold' : '';
                const pkg = CONFIG.pakete[result.packageKey];

                html += `
                    <tr class="${rowClass} border-b border-gray-100 hover:bg-gray-50 cursor-pointer" onclick="setCompanies(${numCompanies})">
                        <td class="px-3 py-2">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 bg-gradient-to-br ${pkg.gradient} text-white rounded-lg flex items-center justify-center font-bold text-xs">${result.packageKey}</span>
                                <div>
                                    <span class="font-medium">${numCompanies} Unternehmen</span>
                                    <span class="text-gray-500 text-xs block">€${result.preis.toFixed(2)}/min</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2 text-right">${result.totalMinuten.toLocaleString('de-DE')}</td>
                        <td class="px-3 py-2 text-right text-blue-600 font-medium">${formatEuro(result.totalRevenue)}</td>
                        <td class="px-3 py-2 text-right text-red-600">${formatEuro(result.totalCosts)}</td>
                        <td class="px-3 py-2 text-right text-green-600 font-bold">${formatEuro(result.profit)}</td>
                        <td class="px-3 py-2 text-right">
                            <span class="inline-block px-2 py-1 rounded text-xs font-medium ${result.margin >= 50 ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}">${formatPercent(result.margin)}</span>
                        </td>
                    </tr>
                `;
            }
            tbody.innerHTML = html;
        }

        // =====================================================
        // INVOICE PREVIEW FUNCTIONS
        // =====================================================

        // Einzelkundenrechnung basierend auf aktuellem Szenario
        function updateInvoice(companies, callsPerDay, duration, result) {
            // Berechnungen
            const anrufeProMonat = callsPerDay * CONFIG.tage_pro_monat;
            const minutenProKunde = anrufeProMonat * duration;
            const preis = result.preis;

            // Preise aus CONFIG (neue Struktur)
            const bereitstellung = CONFIG.kunde.bereitstellung.cps5;
            const minutenKosten = minutenProKunde * preis;
            const nettoGesamt = bereitstellung + minutenKosten;
            const mwst = nettoGesamt * 0.19;
            const bruttoGesamt = nettoGesamt + mwst;

            // Invoice Items aufbauen
            const invoiceItems = document.getElementById('invoice-items');
            invoiceItems.innerHTML = `
                <tr class="border-b border-gray-100">
                    <td class="py-3">
                        <div>
                            <span class="font-medium">Monatliche Bereitstellung (5 CPS)</span>
                            <span class="text-gray-500 text-xs block">AI Voice Service Pauschal</span>
                        </div>
                    </td>
                    <td class="py-3 text-right">1</td>
                    <td class="py-3 text-right">€${formatNumber(bereitstellung)}</td>
                    <td class="py-3 text-right font-medium">€${formatNumber(bereitstellung)}</td>
                </tr>
                <tr class="border-b border-gray-100">
                    <td class="py-3">
                        <div>
                            <span class="font-medium">Gesprächsminuten (Paket ${result.packageKey})</span>
                            <span class="text-gray-500 text-xs block">${anrufeProMonat.toLocaleString('de-DE')} Anrufe × ${duration} Min = ${minutenProKunde.toLocaleString('de-DE')} Min</span>
                        </div>
                    </td>
                    <td class="py-3 text-right">${minutenProKunde.toLocaleString('de-DE')}</td>
                    <td class="py-3 text-right">€${preis.toFixed(2)}</td>
                    <td class="py-3 text-right font-medium">€${formatNumber(minutenKosten)}</td>
                </tr>
            `;

            // Summen aktualisieren
            document.getElementById('invoice-net').textContent = `€${formatNumber(nettoGesamt)}`;
            document.getElementById('invoice-vat').textContent = `€${formatNumber(mwst)}`;
            document.getElementById('invoice-total').textContent = `€${formatNumber(bruttoGesamt)}`;

            // Dynamischer Minutenpreis im Header
            document.getElementById('invoice-price-per-min').textContent = `€${preis.toFixed(2)}`;

            // Anruf-Info
            document.getElementById('invoice-calls-info').textContent = `${callsPerDay} Anrufe/Tag × ${duration} Min = ${anrufeProMonat.toLocaleString('de-DE')} Gespräche/Monat`;

            // Update Quote Template
            updateQuoteTemplate(companies, callsPerDay, duration, result);
        }

        // Szenario-Vergleichskarten (1, 20, 40 Unternehmen - je eine pro Preisstufe)
        function updateInvoiceScenarios(callsPerDay, duration) {
            const scenarios = [1, 20, 40];
            const container = document.getElementById('invoice-scenarios');
            const anrufeProMonat = callsPerDay * CONFIG.tage_pro_monat;
            const minutenProKunde = anrufeProMonat * duration;

            let html = '';
            for (const numCompanies of scenarios) {
                const packageKey = getPackageForCompanyCount(numCompanies);
                const pkg = CONFIG.pakete[packageKey];
                const preis = pkg.preis;

                // Einzelkundenkosten (neue CONFIG-Struktur)
                const bereitstellung = CONFIG.kunde.bereitstellung.cps5;
                const minutenKosten = minutenProKunde * preis;
                const nettoProKunde = bereitstellung + minutenKosten;
                const bruttoProKunde = nettoProKunde * 1.19;

                // Gesamtvolumen für Partner bei dieser Unternehmenszahl
                const gesamtNetto = nettoProKunde * numCompanies;
                const einrichtungGesamt = CONFIG.kunde.setup.professional * numCompanies;

                // Bereichsanzeige für Preisstufe
                const tierRange = packageKey === 'S' ? '1–19 Unternehmen' : packageKey === 'M' ? 'ab 20 Unternehmen' : 'ab 40 Unternehmen';

                html += `
                    <div class="bg-white rounded-lg border-2 border-${packageKey === 'S' ? 'gray' : packageKey === 'M' ? 'blue' : 'purple'}-300 p-4 text-center">
                        <div class="flex flex-col items-center mb-3">
                            <span class="w-10 h-10 bg-gradient-to-br ${pkg.gradient} text-white rounded-lg flex items-center justify-center font-bold text-lg mb-1">${packageKey}</span>
                            <span class="font-bold text-gray-800">${pkg.label}</span>
                            <span class="text-xs text-gray-500">${tierRange}</span>
                        </div>
                        <div class="bg-${packageKey === 'S' ? 'gray' : packageKey === 'M' ? 'blue' : 'purple'}-50 rounded-lg p-2 mb-3">
                            <p class="text-2xl font-bold text-${packageKey === 'S' ? 'gray-700' : packageKey === 'M' ? 'blue-700' : 'purple-700'}">€${preis.toFixed(2)}<span class="text-sm font-normal">/min</span></p>
                        </div>
                        <div class="space-y-1 text-sm">
                            <p class="text-gray-400 text-xs">${anrufeProMonat.toLocaleString('de-DE')} Anrufe × ${duration} Min</p>
                            <div class="border-t pt-2 mt-2">
                                <p class="text-xs text-gray-400 uppercase tracking-wide">Beispiel: ${numCompanies} ${numCompanies === 1 ? 'Kunde' : 'Kunden'}</p>
                                <p class="text-xl font-bold text-amber-700">€${formatNumber(nettoProKunde)}<span class="text-sm font-normal text-gray-500">/Kunde</span></p>
                                <p class="text-xs text-gray-500">(€${formatNumber(bruttoProKunde)} brutto)</p>
                            </div>
                            <div class="border-t pt-2 mt-2 bg-gray-50 -mx-4 px-4 py-2 rounded-b-lg">
                                <p class="text-xs text-gray-400">Bei ${numCompanies} Kunden:</p>
                                <p class="font-semibold text-blue-700">€${formatNumber(gesamtNetto)}/Monat</p>
                            </div>
                        </div>
                    </div>
                `;
            }
            container.innerHTML = html;
        }

        // Margenanalyse für Partner
        function updateMarginAnalysis(callsPerDay, duration, result) {
            const container = document.getElementById('margin-analysis');
            const anrufeProMonat = callsPerDay * CONFIG.tage_pro_monat;
            const minutenProKunde = anrufeProMonat * duration;
            const preis = result.preis;

            // Kosten pro Kunde (Selbstkosten)
            const kostenProKunde = minutenProKunde * CONFIG.selbstkosten + CONFIG.nummernkosten;

            // Umsatz pro Kunde (neue CONFIG-Struktur)
            const umsatzProKunde = CONFIG.kunde.bereitstellung.cps5 + (minutenProKunde * preis);

            // Marge pro Kunde
            const margeProKunde = umsatzProKunde - kostenProKunde;
            const margeProKundeProzent = (margeProKunde / umsatzProKunde) * 100;

            // Einrichtungsgewinn (neue CONFIG-Struktur)
            const einrichtungsgebuehrer = CONFIG.kunde.setup.professional;

            container.innerHTML = `
                <div class="bg-white rounded-lg p-4 border border-green-200">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Pro Kunde monatlich</p>
                    <div class="flex justify-between items-baseline">
                        <span class="text-gray-700">Dein Umsatz:</span>
                        <span class="font-semibold text-blue-700">€${formatNumber(umsatzProKunde)}</span>
                    </div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-gray-700">Deine Kosten:</span>
                        <span class="font-semibold text-red-600">€${formatNumber(kostenProKunde)}</span>
                    </div>
                    <div class="flex justify-between items-baseline mt-2 pt-2 border-t border-green-200">
                        <span class="font-bold text-green-800">Deine Marge:</span>
                        <div class="text-right">
                            <span class="font-bold text-2xl text-green-700">€${formatNumber(margeProKunde)}</span>
                            <span class="text-xs text-green-600 block">(${margeProKundeProzent.toFixed(1)}%)</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg p-4 border border-green-200">
                    <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Einmalig bei Neukunde</p>
                    <div class="flex justify-between items-baseline">
                        <span class="text-gray-700">Einrichtungsgebühr:</span>
                        <span class="font-semibold text-green-700">€${formatNumber(einrichtungsgebuehrer)}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Zzgl. deines Aufwands für Call-Flow-Einrichtung
                        (wird separat kalkuliert)
                    </p>
                    <div class="mt-3 pt-3 border-t border-green-200">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Break-Even nach:</p>
                        <p class="font-bold text-lg text-green-700">~1 Monat</p>
                        <p class="text-xs text-gray-500">Einrichtung amortisiert sich schnell</p>
                    </div>
                </div>
            `;
        }

        // Angebotsvorlage generieren (kopierbar)
        function updateQuoteTemplate(companies, callsPerDay, duration, result) {
            const container = document.getElementById('quote-template');
            const today = new Date();
            const dateStr = today.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const validUntil = new Date(today.getTime() + 30 * 24 * 60 * 60 * 1000);
            const validUntilStr = validUntil.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });

            const anrufeProMonat = callsPerDay * CONFIG.tage_pro_monat;
            const minutenProMonat = anrufeProMonat * duration;
            const preis = result.preis;

            const einrichtung = CONFIG.kunde.setup.professional;
            const bereitstellung = CONFIG.kunde.bereitstellung.cps5;
            const minutenKosten = minutenProMonat * preis;
            const nettoMonatlich = bereitstellung + minutenKosten;
            const mwstMonatlich = nettoMonatlich * 0.19;
            const bruttoMonatlich = nettoMonatlich + mwstMonatlich;

            const quoteText = `═══════════════════════════════════════════════════════════════
                 ANGEBOT - AI Voice Service
═══════════════════════════════════════════════════════════════

Datum: ${dateStr}
Gültig bis: ${validUntilStr}
Angebots-Nr.: ANG-${today.getFullYear()}-XXXX

───────────────────────────────────────────────────────────────
LEISTUNGSUMFANG
───────────────────────────────────────────────────────────────

Erwartetes Volumen:
• ${callsPerDay} Anrufe pro Tag
• ${duration} Minuten durchschnittliche Gesprächsdauer
• = ${anrufeProMonat.toLocaleString('de-DE')} Gespräche pro Monat
• = ${minutenProMonat.toLocaleString('de-DE')} Minuten pro Monat

───────────────────────────────────────────────────────────────
EINMALIGE KOSTEN
───────────────────────────────────────────────────────────────

Professional Einrichtung                          €${formatNumber(einrichtung)}
(Inkl. Custom Call Flow, Agent-Konfiguration,
Integration, Testing und Deployment)

───────────────────────────────────────────────────────────────
MONATLICHE KOSTEN
───────────────────────────────────────────────────────────────

Pos.  Beschreibung                    Menge      Preis     Gesamt
────  ─────────────────────────────  ─────  ─────────  ─────────
1     Bereitstellung AI Voice (5 CPS)    1   €${formatNumber(bereitstellung)}   €${formatNumber(bereitstellung)}
2     Gesprächsminuten (Paket ${result.packageKey})   ${minutenProMonat.toLocaleString('de-DE')}   €${preis.toFixed(2)}   €${formatNumber(minutenKosten)}

                                           ─────────────────────
                                           Netto:    €${formatNumber(nettoMonatlich)}
                                           MwSt 19%: €${formatNumber(mwstMonatlich)}
                                           ═════════════════════
                                           Gesamt:   €${formatNumber(bruttoMonatlich)}

───────────────────────────────────────────────────────────────
LEISTUNGSMERKMALE
───────────────────────────────────────────────────────────────

✓ 5 CPS - Bis zu 5 neue Anrufe pro Sekunde
✓ Bis zu 10 gleichzeitige aktive Gespräche
✓ 24/7 Verfügbarkeit
✓ Deutschsprachiger AI Agent
✓ Anrufaufzeichnung & Transkription
✓ Dashboard mit Echtzeit-Analytics

───────────────────────────────────────────────────────────────
OPTIONALE UPGRADES
───────────────────────────────────────────────────────────────

Höhere CPS-Kapazität (>5 parallel):          nach Absprache
Support & Beratung:                          nach Absprache (inkl. Kontingent)

───────────────────────────────────────────────────────────────
ÄNDERUNGSGEBÜHREN (nach Abnahme)
───────────────────────────────────────────────────────────────

Kleine Anpassung (Text, Prompt):             €${formatNumber(CONFIG.kunde.aenderungen.minor)}
Call Flow / AI Agent Anpassung:              €${formatNumber(CONFIG.kunde.aenderungen.callflow)}
Webhook-Integration / Gateway:               €${formatNumber(CONFIG.kunde.aenderungen.gateway)}
Komplexe Änderung (mehrere Bereiche):        nach Absprache

═══════════════════════════════════════════════════════════════
`;

            container.textContent = quoteText;
        }

        // Zahlenformatierung mit Komma
        function formatNumber(value) {
            return value.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // =====================================================
        // COPY FUNCTIONS
        // =====================================================

        // Angebot kopieren
        function copyQuote() {
            const quoteContent = document.getElementById('quote-template').textContent;
            navigator.clipboard.writeText(quoteContent).then(function() {
                const btn = document.getElementById('copyQuoteBtn');
                const defaultSpan = btn.querySelector('.copy-default');
                const successSpan = btn.querySelector('.copy-success');

                defaultSpan.classList.add('hide');
                successSpan.classList.add('show');
                btn.classList.remove('bg-emerald-600', 'hover:bg-emerald-700');
                btn.classList.add('bg-green-600');

                setTimeout(function() {
                    defaultSpan.classList.remove('hide');
                    successSpan.classList.remove('show');
                    btn.classList.remove('bg-green-600');
                    btn.classList.add('bg-emerald-600', 'hover:bg-emerald-700');
                }, 2000);
            }).catch(function(err) {
                console.error('Clipboard copy failed:', err);
                alert('Konnte nicht in die Zwischenablage kopieren. Bitte manuell kopieren.');
            });
        }

        // E-Mail kopieren
        function copyEmail() {
            const emailContent = document.getElementById('email-content').innerText;
            navigator.clipboard.writeText(emailContent).then(function() {
                const btn = document.getElementById('copyBtn');
                const defaultSpan = btn.querySelector('.copy-default');
                const successSpan = btn.querySelector('.copy-success');

                defaultSpan.classList.add('hide');
                successSpan.classList.add('show');
                btn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
                btn.classList.add('bg-green-600');

                setTimeout(function() {
                    defaultSpan.classList.remove('hide');
                    successSpan.classList.remove('show');
                    btn.classList.remove('bg-green-600');
                    btn.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
                }, 2000);
            }).catch(function(err) {
                console.error('Clipboard copy failed:', err);
                // Fallback: Show alert with text
                alert('Konnte nicht in die Zwischenablage kopieren. Bitte manuell kopieren:\n\n' + emailContent.substring(0, 200) + '...');
            });
        }

        // =====================================================
        // INITIALIZE ON PAGE LOAD
        // =====================================================
        document.addEventListener('DOMContentLoaded', function() {
            // Set invoice date to today
            const today = new Date();
            const dateStr = today.toLocaleDateString('de-DE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            document.getElementById('invoice-date').textContent = dateStr;

            // Load saved configuration (URL params or localStorage)
            const configLoaded = loadConfig();

            // Apply config values to form inputs
            applyConfigToForm();

            // Update all displays with CONFIG values
            updateConfigMargins();
            updatePackagePriceTable();

            // Show notification if config was loaded
            if (configLoaded) {
                showToast('Gespeicherte Einstellungen geladen', 'info');
            }

            // Initialize calculator (will also update invoice)
            updateCalculator();
        });
    </script>
</body>
</html>
