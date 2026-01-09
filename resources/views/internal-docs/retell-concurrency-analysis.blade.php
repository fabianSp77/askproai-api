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
                                <td class="px-3 py-3 text-center font-medium">1–19</td>
                                <td class="px-3 py-3 text-right font-bold text-indigo-600">€0.29</td>
                                <td class="px-3 py-3 text-right text-gray-400">—</td>
                                <td class="px-3 py-3 text-right"><span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">61%</span></td>
                            </tr>
                            <tr class="border-b border-indigo-100 hover:bg-indigo-50" id="package-row-M">
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg flex items-center justify-center font-bold text-sm">M</span>
                                        Partner
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center font-medium">20–39</td>
                                <td class="px-3 py-3 text-right font-bold text-indigo-600">€0.27</td>
                                <td class="px-3 py-3 text-right text-green-600 font-medium">-€0.02</td>
                                <td class="px-3 py-3 text-right"><span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">58%</span></td>
                            </tr>
                            <tr class="hover:bg-indigo-50" id="package-row-L">
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="w-8 h-8 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg flex items-center justify-center font-bold text-sm">L</span>
                                        Enterprise
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center font-medium">40+</td>
                                <td class="px-3 py-3 text-right font-bold text-indigo-600">€0.24</td>
                                <td class="px-3 py-3 text-right text-green-600 font-medium">-€0.05</td>
                                <td class="px-3 py-3 text-right"><span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">53%</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-indigo-600 mt-3">💡 <strong>Incentive:</strong> Ein Partner mit 20 Unternehmen zahlt für <em>alle 20</em> nur €0.27/min statt €0.29/min!</p>
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
                <h3 class="font-bold text-indigo-900 mb-4 text-lg">Ergebnis: Monatliche Kalkulation</h3>

                <!-- Summary Cards -->
                <div class="grid md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
                        <p class="text-blue-100 text-xs font-medium uppercase">Gesamtumsatz</p>
                        <p id="result-revenue" class="text-3xl font-bold">€0</p>
                        <p class="text-blue-200 text-xs mt-1">Fix + Minuten</p>
                    </div>
                    <div class="bg-gradient-to-br from-red-400 to-red-500 rounded-xl p-4 text-white shadow-lg">
                        <p class="text-red-100 text-xs font-medium uppercase">Deine Kosten</p>
                        <p id="result-costs" class="text-3xl font-bold">€0</p>
                        <p class="text-red-200 text-xs mt-1">Retell + Twilio</p>
                    </div>
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
                        <p class="text-green-100 text-xs font-medium uppercase">Dein Gewinn</p>
                        <p id="result-profit" class="text-3xl font-bold">€0</p>
                        <p id="result-margin" class="text-green-200 text-xs mt-1">0% Marge</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
                        <p class="text-purple-100 text-xs font-medium uppercase">Gewinn/Kunde</p>
                        <p id="result-profit-per-customer" class="text-3xl font-bold">€0</p>
                        <p class="text-purple-200 text-xs mt-1">pro Monat</p>
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
                    <h4 class="font-semibold text-gray-800 mb-4">Kundenrechnung pro Monat nach Partner-Größe</h4>
                    <div class="grid md:grid-cols-4 gap-4" id="invoice-scenarios">
                        <!-- Will be filled by JavaScript -->
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        * Basierend auf <span id="invoice-calls-info">20 Anrufe/Tag × 3 Min</span>.
                        Der Minutenpreis sinkt mit steigender Partnergröße – der Vorteil wird an Kunden weitergegeben.
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
            fixgebuehr: 200,           // € pro Unternehmen/Monat (Bereitstellung)
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

        // Ermittle das Paket basierend auf der Unternehmensanzahl
        function getPackageForCompanyCount(companies) {
            if (companies < 20) return 'S';   // 1-19 Unternehmen
            if (companies < 40) return 'M';   // 20-39 Unternehmen
            return 'L';                       // 40+ Unternehmen
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

        // Szenario-Vergleichskarten (1, 10, 20, 40 Unternehmen)
        function updateInvoiceScenarios(callsPerDay, duration) {
            const scenarios = [1, 10, 20, 40];
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

                html += `
                    <div class="bg-white rounded-lg border-2 ${numCompanies === 1 ? 'border-gray-200' : 'border-' + (packageKey === 'S' ? 'gray' : packageKey === 'M' ? 'blue' : packageKey === 'L' ? 'purple' : 'amber') + '-300'} p-4 text-center">
                        <div class="flex items-center justify-center gap-2 mb-2">
                            <span class="w-8 h-8 bg-gradient-to-br ${pkg.gradient} text-white rounded-lg flex items-center justify-center font-bold text-sm">${packageKey}</span>
                            <span class="font-semibold text-gray-800">${numCompanies} Unternehmen</span>
                        </div>
                        <div class="space-y-1 text-sm">
                            <p class="text-gray-500">Minutenpreis: <strong class="text-gray-800">€${preis.toFixed(2)}</strong></p>
                            <p class="text-gray-400 text-xs">${anrufeProMonat.toLocaleString('de-DE')} Anrufe × ${minutenProKunde.toLocaleString('de-DE')} Min/Mo</p>
                            <div class="border-t pt-2 mt-2">
                                <p class="text-xs text-gray-400 uppercase tracking-wide">Pro Kunde/Monat</p>
                                <p class="text-xl font-bold text-amber-700">€${formatNumber(nettoProKunde)}</p>
                                <p class="text-xs text-gray-500">(€${formatNumber(bruttoProKunde)} brutto)</p>
                            </div>
                            <div class="border-t pt-2 mt-2 bg-gray-50 -mx-4 px-4 py-2">
                                <p class="text-xs text-gray-400">Einmalige Einrichtung gesamt:</p>
                                <p class="font-semibold text-green-700">€${formatNumber(einrichtungGesamt)}</p>
                                <p class="text-xs text-gray-400 mt-1">Monatl. Volumen gesamt:</p>
                                <p class="font-semibold text-blue-700">€${formatNumber(gesamtNetto)}</p>
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

            // Initialize calculator (will also update invoice)
            updateCalculator();
        });
    </script>
</body>
</html>
