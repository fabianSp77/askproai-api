<x-filament-panels::page>
    <div x-data="{
        testMode: 'voice',
        isRecording: false,
        isProcessing: false,
        testResults: null,
        recordingTime: 0,
        recordingInterval: null,
        
        startRecording() {
            this.isRecording = true;
            this.recordingTime = 0;
            this.recordingInterval = setInterval(() => {
                this.recordingTime++;
            }, 1000);
        },
        
        stopRecording() {
            this.isRecording = false;
            this.isProcessing = true;
            clearInterval(this.recordingInterval);
            
            // Simulate processing
            setTimeout(() => {
                this.isProcessing = false;
                this.testResults = {
                    success: true,
                    confidence: 0.94,
                    intent: 'book_appointment',
                    extractedData: {
                        service: 'Haarschnitt Herren',
                        date: '2024-03-25',
                        time: '14:00',
                        customer: 'Max Mustermann'
                    }
                };
            }, 2000);
        }
    }">
        
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">KI-Service Test Center</h2>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Testen Sie die KI-Integration für {{ $service->name }}</p>
                </div>
                
                <!-- Mode Switcher -->
                <div class="flex gap-2">
                    <button @click="testMode = 'voice'; testResults = null" 
                            :class="testMode === 'voice' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                            class="px-4 py-2 rounded-lg font-medium transition-colors">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                        Sprachtest
                    </button>
                    <button @click="testMode = 'text'; testResults = null" 
                            :class="testMode === 'text' ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                            class="px-4 py-2 rounded-lg font-medium transition-colors">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                        Texttest
                    </button>
                </div>
            </div>
        </div>

        <!-- Voice Test Mode -->
        <div x-show="testMode === 'voice'" class="space-y-6">
            <!-- Recording Interface -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-8 border border-blue-200 dark:border-blue-800">
                <div class="text-center">
                    <!-- Microphone Button -->
                    <div class="relative inline-block">
                        <button @click="isRecording ? stopRecording() : startRecording()"
                                :disabled="isProcessing"
                                class="relative w-32 h-32 rounded-full transition-all duration-300"
                                :class="isRecording ? 'bg-red-500 hover:bg-red-600 scale-110' : 'bg-blue-600 hover:bg-blue-700'">
                            
                            <!-- Pulse Animation -->
                            <div x-show="isRecording" 
                                 class="absolute inset-0 rounded-full bg-red-400 animate-ping"></div>
                            
                            <!-- Icon -->
                            <svg class="w-16 h-16 text-white mx-auto relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="!isRecording" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                <path x-show="isRecording" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                            </svg>
                        </button>
                        
                        <!-- Processing Spinner -->
                        <div x-show="isProcessing" 
                             class="absolute inset-0 rounded-full bg-gray-900/50 flex items-center justify-center">
                            <svg class="animate-spin h-12 w-12 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Status Text -->
                    <div class="mt-6">
                        <p x-show="!isRecording && !isProcessing && !testResults" class="text-gray-600 dark:text-gray-400">
                            Klicken Sie auf das Mikrofon und sprechen Sie eine Terminanfrage
                        </p>
                        <p x-show="isRecording" class="text-red-600 dark:text-red-400 font-medium animate-pulse">
                            Aufnahme läuft... (<span x-text="recordingTime"></span>s)
                        </p>
                        <p x-show="isProcessing" class="text-blue-600 dark:text-blue-400 font-medium">
                            Verarbeite Sprachaufnahme...
                        </p>
                    </div>
                </div>
            </div>

            <!-- Example Prompts -->
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Beispiel-Anfragen:</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <p class="text-sm text-gray-600 dark:text-gray-400 italic">
                            "Ich möchte einen Termin für einen Haarschnitt am Freitag um 14 Uhr"
                        </p>
                    </div>
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <p class="text-sm text-gray-600 dark:text-gray-400 italic">
                            "Haben Sie morgen noch einen freien Termin für Waschen, Schneiden, Föhnen?"
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Text Test Mode -->
        <div x-show="testMode === 'text'" class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <form @submit.prevent="isProcessing = true; setTimeout(() => { isProcessing = false; testResults = { success: true, confidence: 0.91, intent: 'book_appointment', extractedData: { service: 'Haarschnitt Herren', date: '2024-03-25', time: '14:00', customer: 'Max Mustermann' } }; }, 1500)">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Testnachricht eingeben
                            </label>
                            <textarea 
                                rows="4"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Geben Sie eine Terminanfrage ein..."
                            ></textarea>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" 
                                    :disabled="isProcessing"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                <span x-show="!isProcessing">Test durchführen</span>
                                <span x-show="isProcessing" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Verarbeite...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Test Results -->
        <div x-show="testResults" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" class="mt-8 space-y-6">
            
            <!-- Success/Error Banner -->
            <div :class="testResults?.success ? 'bg-emerald-50 border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-800' : 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800'"
                 class="rounded-xl p-6 border">
                <div class="flex items-start gap-4">
                    <div :class="testResults?.success ? 'bg-emerald-100 dark:bg-emerald-900/50' : 'bg-red-100 dark:bg-red-900/50'"
                         class="rounded-full p-2">
                        <svg x-show="testResults?.success" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <svg x-show="!testResults?.success" class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 :class="testResults?.success ? 'text-emerald-900 dark:text-emerald-100' : 'text-red-900 dark:text-red-100'"
                            class="font-semibold text-lg">
                            <span x-text="testResults?.success ? 'Test erfolgreich!' : 'Test fehlgeschlagen'"></span>
                        </h3>
                        <p :class="testResults?.success ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'"
                           class="mt-1">
                            Die KI hat die Anfrage <span x-text="testResults?.success ? 'erfolgreich verstanden und verarbeitet' : 'nicht korrekt verarbeiten können'"></span>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Detailed Results -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Confidence Score -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Konfidenz-Score</h4>
                    <div class="relative pt-1">
                        <div class="flex mb-2 items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold inline-block uppercase"
                                      :class="testResults?.confidence > 0.8 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                                    Vertrauen
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-semibold inline-block"
                                      :class="testResults?.confidence > 0.8 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
                                      x-text="Math.round((testResults?.confidence || 0) * 100) + '%'">
                                </span>
                            </div>
                        </div>
                        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                            <div :style="'width: ' + ((testResults?.confidence || 0) * 100) + '%'"
                                 :class="testResults?.confidence > 0.8 ? 'bg-emerald-500' : 'bg-amber-500'"
                                 class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center transition-all duration-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detected Intent -->
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Erkannte Absicht</h4>
                    <div class="flex items-center gap-3">
                        <div class="bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-lg p-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white" x-text="testResults?.intent || 'Unbekannt'"></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Terminbuchung erkannt</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Extracted Data -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Extrahierte Daten</h4>
                <div class="space-y-3">
                    <template x-for="(value, key) in testResults?.extractedData" :key="key">
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                            <span class="text-sm text-gray-600 dark:text-gray-400 capitalize" x-text="key.replace('_', ' ')"></span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="value"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between">
                <button @click="testResults = null" 
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    Neuer Test
                </button>
                <button class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Konfiguration anpassen
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
