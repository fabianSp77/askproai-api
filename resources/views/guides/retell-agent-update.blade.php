<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retell Agent Update - Schritt für Schritt Anleitung</title>
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
                            🤖 Retell Agent Update
                        </h1>
                        <p class="text-gray-600">
                            Workflow-Korrektur: Automatische Buchung implementieren
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Stand</div>
                        <div class="text-lg font-semibold text-gray-900">01.10.2025</div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-6">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>Fortschritt</span>
                        <span id="progress-text">0 von 6 Schritten</span>
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
                        <h3 class="text-lg font-medium text-blue-900">Wichtige Änderung</h3>
                        <div class="mt-2 text-sm text-blue-800">
                            <p class="font-semibold mb-2">Was wird geändert?</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li><strong>Alt:</strong> 2-Schritt-Prozess (prüfen → fragen → buchen)</li>
                                <li><strong>Neu:</strong> 1-Schritt-Prozess (automatische Buchung wenn verfügbar)</li>
                            </ul>
                            <p class="mt-3 font-semibold">Vorteile:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>⚡ Schneller (1 API Call statt 2)</li>
                                <li>✅ Weniger Fehler</li>
                                <li>🎯 Bessere User Experience</li>
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
                                Öffne das Retell AI Dashboard in einem neuen Tab
                            </p>
                            <a href="https://app.retellai.com/" target="_blank" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Retell Dashboard öffnen
                            </a>
                            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-700">
                                    <strong>Tipp:</strong> Logge dich mit deinen Retell-Zugangsdaten ein
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="step-card bg-white rounded-lg shadow-md p-6 border-2 border-gray-200" data-step="2">
                    <div class="flex items-start">
                        <input type="checkbox" id="step2" class="step-checkbox mt-1 h-6 w-6 text-green-500 rounded focus:ring-green-500">
                        <div class="ml-4 flex-1">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                Schritt 2: Agent finden
                            </h3>
                            <p class="text-gray-600 mb-4">
                                Navigiere zum richtigen Agent
                            </p>
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            <strong>Agent-Details:</strong>
                                        </p>
                                        <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                                            <li>Name: <strong>"Online: Assistent für Fabian Spitzer Rechtliches/V33"</strong></li>
                                            <li>Aktuelle Version: <strong>44 (V33)</strong></li>
                                            <li>Model: gemini-2.0-flash</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <ol class="list-decimal list-inside space-y-2 text-gray-700">
                                <li>Klicke links auf <strong>"Agents"</strong></li>
                                <li>Suche nach <strong>"Fabian Spitzer"</strong> oder <strong>"V33"</strong></li>
                                <li>Klicke auf den Agent um ihn zu öffnen</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="step-card bg-white rounded-lg shadow-md p-6 border-2 border-gray-200" data-step="3">
                    <div class="flex items-start">
                        <input type="checkbox" id="step3" class="step-checkbox mt-1 h-6 w-6 text-green-500 rounded focus:ring-green-500">
                        <div class="ml-4 flex-1">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                Schritt 3: System Prompt öffnen
                            </h3>
                            <p class="text-gray-600 mb-4">
                                Finde den Bereich für die Prompt-Bearbeitung
                            </p>
                            <ol class="list-decimal list-inside space-y-2 text-gray-700 mb-4">
                                <li>Scrolle zur Sektion <strong>"General prompt"</strong> oder <strong>"System prompt"</strong></li>
                                <li>Klicke auf <strong>"Edit"</strong> oder das Textfeld</li>
                                <li>Du siehst jetzt den aktuellen Prompt-Text</li>
                            </ol>
                            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                                <p class="text-sm text-blue-800">
                                    <strong>Was du sehen solltest:</strong> Ein großes Textfeld mit dem aktuellen System-Prompt (ca. 500+ Zeilen Text)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="step-card bg-white rounded-lg shadow-md p-6 border-2 border-gray-200" data-step="4">
                    <div class="flex items-start">
                        <input type="checkbox" id="step4" class="step-checkbox mt-1 h-6 w-6 text-green-500 rounded focus:ring-green-500">
                        <div class="ml-4 flex-1">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                Schritt 4: Alten Abschnitt finden und löschen
                            </h3>
                            <p class="text-gray-600 mb-4">
                                Suche und entferne den veralteten Workflow-Abschnitt
                            </p>

                            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                                <p class="text-sm font-semibold text-red-800 mb-2">🔍 Suche nach diesem Text:</p>
                                <div class="code-container relative">
                                    <pre class="code-block text-xs">## KRITISCHE WORKFLOW-ANWEISUNGEN FÜR TERMINBUCHUNGEN</pre>
                                    <button onclick="copyText('search-text')" class="copy-btn bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-xs">
                                        Kopieren
                                    </button>
                                    <textarea id="search-text" style="position:absolute;left:-9999px">## KRITISCHE WORKFLOW-ANWEISUNGEN FÜR TERMINBUCHUNGEN</textarea>
                                </div>
                            </div>

                            <ol class="list-decimal list-inside space-y-3 text-gray-700">
                                <li>Drücke <kbd class="px-2 py-1 bg-gray-200 rounded">Strg+F</kbd> (Windows) oder <kbd class="px-2 py-1 bg-gray-200 rounded">Cmd+F</kbd> (Mac)</li>
                                <li>Suche nach: <code class="bg-gray-200 px-2 py-1 rounded">KRITISCHE WORKFLOW-ANWEISUNGEN</code></li>
                                <li><strong>Lösche den GESAMTEN Abschnitt</strong> von:
                                    <ul class="list-disc list-inside ml-6 mt-2 space-y-1">
                                        <li>Start: <code class="bg-gray-200 px-2 py-1 rounded text-xs">## KRITISCHE WORKFLOW-ANWEISUNGEN...</code></li>
                                        <li>Ende: Bis zum nächsten <code class="bg-gray-200 px-2 py-1 rounded text-xs">##</code>-Abschnitt</li>
                                    </ul>
                                </li>
                            </ol>

                            <div class="mt-4 p-4 bg-yellow-50 rounded-lg">
                                <p class="text-sm text-yellow-800">
                                    <strong>⚠️ Wichtig:</strong> Lösche NUR diesen einen Abschnitt, nicht den gesamten Prompt!
                                </p>
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
                                Schritt 5: Neuen Text einfügen
                            </h3>
                            <p class="text-gray-600 mb-4">
                                Füge den aktualisierten Workflow-Abschnitt ein
                            </p>

                            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
                                <p class="text-sm font-semibold text-green-800 mb-3">📋 Neuer Text zum Einfügen:</p>
                                <div class="code-container relative bg-white rounded border border-green-200 max-h-96 overflow-y-auto">
                                    <button onclick="copyText('new-prompt')" class="copy-btn bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">
                                        ✓ Text kopieren
                                    </button>
                                    <textarea id="new-prompt" readonly class="w-full p-4 font-mono text-xs leading-relaxed resize-none" rows="25">## VEREINFACHTER TERMINBUCHUNGS-WORKFLOW

### Automatische Buchung (Standardverhalten)

Das System bucht **automatisch** wenn der Wunschtermin verfügbar ist. Es gibt KEINEN zwei-Schritt-Prozess mehr.

**WORKFLOW:**

1. **Sammle alle Termindaten**:
   - Name (erfragen)
   - Datum (erfragen)
   - Uhrzeit (erfragen)
   - Dienstleistung (erfragen)
   - E-Mail (NUR wenn Kunde Bestätigung wünscht)
   - Telefonnummer (NIEMALS fragen wenn {{caller_phone_number}} vorhanden!)

2. **Rufe `collect_appointment_data` auf** mit allen Daten
   - WICHTIG: `call_id` IMMER mit {{call_id}} übergeben
   - Parameter `bestaetigung` weglassen oder nicht setzen
   - System prüft Verfügbarkeit UND bucht automatisch wenn verfügbar

3. **Reagiere auf die Antwort**:

   **Fall A: Termin wurde gebucht** (`status: "booked"`)
   ```
   Bestätige dem Kunden:
   "Perfekt! Ihr Termin wurde erfolgreich gebucht für [Datum] um [Uhrzeit].
   Sie erhalten in Kürze eine Bestätigungsemail."
   ```

   **Fall B: Termin nicht verfügbar, Alternativen vorhanden** (`status: "not_available"`)
   ```
   1. Lese die Alternativen aus der Response vor
   2. Warte auf Kundenauswahl (z.B. "13 Uhr nehme ich")
   3. Rufe collect_appointment_data NOCHMAL auf mit:
      - Den NEUEN Daten (neue Uhrzeit/Datum aus Alternative)
      - Alle anderen Daten gleich lassen
      - `bestaetigung` NICHT setzen (automatische Buchung)
   4. System bucht automatisch die gewählte Alternative
   ```

   **Fall C: Keine Verfügbarkeit** (`status: "no_availability"`)
   ```
   Lese die Nachricht aus der Response vor. Diese erklärt:
   - System hat erfolgreich geprüft
   - Keine Termine in nächsten 14 Tagen verfügbar
   - Kein technischer Fehler

   Biete an:
   "Möchten Sie, dass wir Sie zurückrufen, sobald neue Termine verfügbar sind?"
   ```

### WICHTIG: Wann `bestaetigung: false` verwenden?

**NUR verwenden wenn du explizit NUR prüfen willst, OHNE zu buchen**

Beispiel: Kunde sagt "Schau mal ob 14 Uhr frei ist, ich bin mir noch nicht sicher"
→ Dann: `bestaetigung: false` setzen
→ System prüft nur, bucht NICHT automatisch

**NORMAL-FALL**: Parameter `bestaetigung` einfach weglassen
→ System bucht automatisch wenn verfügbar</textarea>
                                </div>
                            </div>

                            <ol class="list-decimal list-inside space-y-2 text-gray-700 mt-4">
                                <li>Klicke auf <strong>"✓ Text kopieren"</strong> (Button oben rechts)</li>
                                <li>Gehe zurück zum Retell Prompt-Editor</li>
                                <li>Füge den Text an der Stelle ein, wo du den alten Text gelöscht hast</li>
                                <li>Stelle sicher, dass der Text richtig formatiert ist</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Step 6 -->
                <div class="step-card bg-white rounded-lg shadow-md p-6 border-2 border-gray-200" data-step="6">
                    <div class="flex items-start">
                        <input type="checkbox" id="step6" class="step-checkbox mt-1 h-6 w-6 text-green-500 rounded focus:ring-green-500">
                        <div class="ml-4 flex-1">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                Schritt 6: Speichern und Veröffentlichen
                            </h3>
                            <p class="text-gray-600 mb-4">
                                Erstelle eine neue Agent-Version und aktiviere sie
                            </p>

                            <ol class="list-decimal list-inside space-y-3 text-gray-700">
                                <li>Scrolle nach unten und klicke <strong>"Save"</strong> oder <strong>"Speichern"</strong></li>
                                <li>Retell erstellt automatisch eine neue Version (wird Version 45 / V34 sein)</li>
                                <li>Klicke auf <strong>"Publish"</strong> oder <strong>"Veröffentlichen"</strong></li>
                                <li>Bestätige die Veröffentlichung</li>
                            </ol>

                            <div class="mt-6 p-6 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border-2 border-green-300">
                                <div class="flex items-center">
                                    <svg class="h-8 w-8 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        <h4 class="font-bold text-green-900 text-lg">Geschafft! 🎉</h4>
                                        <p class="text-green-800 mt-1">Der Agent ist jetzt aktualisiert und bereit für Test-Calls.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                                <p class="text-sm font-semibold text-blue-900 mb-2">Nächste Schritte:</p>
                                <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                                    <li>Führe einen Test-Call durch</li>
                                    <li>Prüfe ob automatische Buchung funktioniert</li>
                                    <li>Beobachte das Verhalten bei Alternativen</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Card -->
            <div id="summary-card" class="mt-8 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg shadow-xl p-8 text-white hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold mb-2">✅ Alle Schritte abgeschlossen!</h3>
                        <p class="text-purple-100">
                            Der Retell Agent wurde erfolgreich aktualisiert.
                        </p>
                        <div class="mt-4 space-y-2">
                            <p class="text-sm">➤ Neues Verhalten: Automatische Buchung bei Verfügbarkeit</p>
                            <p class="text-sm">➤ Schneller: 1 API Call statt 2</p>
                            <p class="text-sm">➤ Bessere UX für Endkunden</p>
                        </div>
                    </div>
                    <div class="text-6xl">🚀</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-gray-500 text-sm">
                <p>Erstellt am 01.10.2025 | Ask-Pro-AI System</p>
                <p class="mt-2">
                    <a href="/admin" class="text-blue-600 hover:text-blue-800">
                        ← Zurück zum Dashboard
                    </a>
                </p>
            </div>        </div>
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
            localStorage.setItem('retell-agent-update-progress', JSON.stringify(progress));
        }

        // Load saved progress
        const savedProgress = localStorage.getItem('retell-agent-update-progress');
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

        // Copy text function
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

        // Reset progress (for testing)
        function resetProgress() {
            if (confirm('Möchtest du den Fortschritt wirklich zurücksetzen?')) {
                localStorage.removeItem('retell-agent-update-progress');
                checkboxes.forEach(cb => cb.checked = false);
                updateProgress();
            }
        }
    </script>
</body>
</html>
