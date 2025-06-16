<div class="live-validation-dashboard" x-data="liveValidationDashboard()" x-init="init()">
    <div class="space-y-6">
        <!-- Übersichts-Karten -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Gesamt-Status -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border-2"
                 :class="{
                     'border-green-500': overallStatus === 'success',
                     'border-yellow-500': overallStatus === 'warning',
                     'border-red-500': overallStatus === 'error',
                     'border-gray-300 dark:border-gray-600': overallStatus === 'pending'
                 }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Gesamt-Status</p>
                        <p class="text-lg font-semibold mt-1"
                           :class="{
                               'text-green-600 dark:text-green-400': overallStatus === 'success',
                               'text-yellow-600 dark:text-yellow-400': overallStatus === 'warning',
                               'text-red-600 dark:text-red-400': overallStatus === 'error',
                               'text-gray-600 dark:text-gray-400': overallStatus === 'pending'
                           }"
                           x-text="getStatusText(overallStatus)"></p>
                    </div>
                    <div class="h-10 w-10 rounded-full flex items-center justify-center"
                         :class="{
                             'bg-green-100 dark:bg-green-900/30': overallStatus === 'success',
                             'bg-yellow-100 dark:bg-yellow-900/30': overallStatus === 'warning',
                             'bg-red-100 dark:bg-red-900/30': overallStatus === 'error',
                             'bg-gray-100 dark:bg-gray-700': overallStatus === 'pending'
                         }">
                        <svg x-show="overallStatus === 'success'" class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <svg x-show="overallStatus === 'warning'" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <svg x-show="overallStatus === 'error'" class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <svg x-show="overallStatus === 'pending'" class="w-6 h-6 text-gray-600 dark:text-gray-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Kalender-Integration -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Kalender</p>
                        <p class="text-lg font-semibold mt-1" x-text="validationResults.calendar?.status || 'Prüfung läuft...'"></p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <span x-text="validationResults.calendar?.response_time || '-'"></span>ms Antwortzeit
                </div>
            </div>
            
            <!-- KI-Telefonie -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">KI-Telefonie</p>
                        <p class="text-lg font-semibold mt-1" x-text="validationResults.telephony?.status || 'Prüfung läuft...'"></p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <span x-text="validationResults.telephony?.active_agents || '0'"></span> aktive Agenten
                </div>
            </div>
            
            <!-- Letzte Prüfung -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Letzte Prüfung</p>
                        <p class="text-lg font-semibold mt-1" x-text="lastCheckTime || 'Noch nie'"></p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-2">
                    <button @click="runValidation()" 
                            :disabled="isValidating"
                            class="text-xs text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 disabled:opacity-50">
                        Jetzt prüfen
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Detaillierte Ergebnisse -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detaillierte Prüfergebnisse</h3>
            </div>
            
            <div class="p-6">
                <!-- Prüfungs-Timeline -->
                <div class="space-y-4">
                    <template x-for="check in detailedChecks" :key="check.id">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full flex items-center justify-center"
                                     :class="{
                                         'bg-green-100 dark:bg-green-900/30': check.status === 'success',
                                         'bg-yellow-100 dark:bg-yellow-900/30': check.status === 'warning',
                                         'bg-red-100 dark:bg-red-900/30': check.status === 'error',
                                         'bg-gray-100 dark:bg-gray-700': check.status === 'pending'
                                     }">
                                    <svg x-show="check.status === 'success'" class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <svg x-show="check.status === 'warning'" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    <svg x-show="check.status === 'error'" class="w-5 h-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div x-show="check.status === 'pending'" class="w-5 h-5 border-2 border-gray-400 border-t-transparent rounded-full animate-spin"></div>
                                </div>
                            </div>
                            
                            <div class="ml-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white" x-text="check.name"></h4>
                                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="check.duration"></span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" x-text="check.message"></p>
                                
                                <template x-if="check.details && check.details.length > 0">
                                    <div class="mt-2 space-y-1">
                                        <template x-for="detail in check.details" :key="detail">
                                            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span x-text="detail"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                
                <!-- Empfehlungen bei Problemen -->
                <template x-if="recommendations.length > 0">
                    <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">Empfohlene Maßnahmen</h4>
                        <ul class="space-y-1 text-sm text-yellow-700 dark:text-yellow-300">
                            <template x-for="rec in recommendations" :key="rec">
                                <li class="flex items-start">
                                    <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span x-text="rec"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>
            </div>
        </div>
    </div>
    
    <script>
        function liveValidationDashboard() {
            return {
                overallStatus: 'pending',
                validationResults: {},
                detailedChecks: [],
                recommendations: [],
                lastCheckTime: null,
                isValidating: false,
                pollingInterval: null,
                
                init() {
                    this.loadValidationStatus();
                    // Polling alle 5 Minuten
                    this.pollingInterval = setInterval(() => this.loadValidationStatus(), 300000);
                },
                
                destroy() {
                    if (this.pollingInterval) {
                        clearInterval(this.pollingInterval);
                    }
                },
                
                async loadValidationStatus() {
                    try {
                        const response = await fetch('/api/branch/' + @json($getRecord()?->id) + '/validation-status');
                        const data = await response.json();
                        
                        this.processValidationResults(data);
                    } catch (error) {
                        console.error('Fehler beim Laden der Validierung:', error);
                    }
                },
                
                processValidationResults(data) {
                    this.validationResults = data.results || {};
                    this.overallStatus = data.overall_status || 'pending';
                    this.lastCheckTime = data.last_check ? this.formatTime(data.last_check) : null;
                    
                    // Detaillierte Prüfungen verarbeiten
                    this.detailedChecks = [
                        {
                            id: 'cal_api',
                            name: 'Cal.com API Verbindung',
                            status: data.results.calendar?.api_status || 'pending',
                            duration: data.results.calendar?.response_time ? `${data.results.calendar.response_time}ms` : '-',
                            message: this.getCheckMessage('cal_api', data.results.calendar),
                            details: data.results.calendar?.errors || []
                        },
                        {
                            id: 'cal_events',
                            name: 'Kalender Event-Typen',
                            status: data.results.calendar?.event_types_status || 'pending',
                            duration: '-',
                            message: this.getCheckMessage('cal_events', data.results.calendar),
                            details: data.results.calendar?.event_types || []
                        },
                        {
                            id: 'retell_api',
                            name: 'Retell.ai API Verbindung',
                            status: data.results.telephony?.api_status || 'pending',
                            duration: data.results.telephony?.response_time ? `${data.results.telephony.response_time}ms` : '-',
                            message: this.getCheckMessage('retell_api', data.results.telephony),
                            details: data.results.telephony?.errors || []
                        },
                        {
                            id: 'retell_agents',
                            name: 'KI-Agenten Status',
                            status: data.results.telephony?.agents_status || 'pending',
                            duration: '-',
                            message: this.getCheckMessage('retell_agents', data.results.telephony),
                            details: data.results.telephony?.agents || []
                        },
                        {
                            id: 'services',
                            name: 'Service-Konfiguration',
                            status: data.results.services?.status || 'pending',
                            duration: '-',
                            message: this.getCheckMessage('services', data.results.services),
                            details: data.results.services?.issues || []
                        }
                    ];
                    
                    // Empfehlungen generieren
                    this.generateRecommendations(data);
                },
                
                getCheckMessage(checkId, data) {
                    const messages = {
                        'cal_api': {
                            'success': 'API-Verbindung erfolgreich',
                            'error': 'API-Verbindung fehlgeschlagen',
                            'warning': 'API-Verbindung instabil',
                            'pending': 'Prüfe API-Verbindung...'
                        },
                        'cal_events': {
                            'success': `${data?.event_types_count || 0} Event-Typen verfügbar`,
                            'error': 'Keine Event-Typen konfiguriert',
                            'warning': 'Einige Event-Typen haben Probleme',
                            'pending': 'Prüfe Event-Typen...'
                        },
                        'retell_api': {
                            'success': 'Retell.ai verbunden',
                            'error': 'Retell.ai nicht erreichbar',
                            'warning': 'Retell.ai Verbindung langsam',
                            'pending': 'Prüfe Retell.ai...'
                        },
                        'retell_agents': {
                            'success': `${data?.active_agents || 0} Agenten aktiv`,
                            'error': 'Keine aktiven Agenten',
                            'warning': 'Einige Agenten offline',
                            'pending': 'Prüfe Agenten-Status...'
                        },
                        'services': {
                            'success': 'Alle Services korrekt konfiguriert',
                            'error': 'Service-Konfiguration fehlerhaft',
                            'warning': 'Service-Konfiguration unvollständig',
                            'pending': 'Prüfe Service-Konfiguration...'
                        }
                    };
                    
                    const status = data?.[checkId + '_status'] || data?.status || 'pending';
                    return messages[checkId]?.[status] || 'Status unbekannt';
                },
                
                generateRecommendations(data) {
                    this.recommendations = [];
                    
                    if (data.results.calendar?.api_status === 'error') {
                        this.recommendations.push('Überprüfen Sie den Cal.com API-Schlüssel in den Einstellungen');
                    }
                    
                    if (data.results.calendar?.event_types_count === 0) {
                        this.recommendations.push('Konfigurieren Sie mindestens einen Event-Typ in Cal.com');
                    }
                    
                    if (data.results.telephony?.active_agents === 0) {
                        this.recommendations.push('Aktivieren Sie mindestens einen KI-Agenten für die Telefonannahme');
                    }
                    
                    if (data.results.services?.configured === 0) {
                        this.recommendations.push('Definieren Sie Services für diese Filiale');
                    }
                },
                
                getStatusText(status) {
                    const texts = {
                        'success': 'Alles funktioniert',
                        'warning': 'Teilweise Probleme',
                        'error': 'Kritische Fehler',
                        'pending': 'Wird geprüft...'
                    };
                    return texts[status] || status;
                },
                
                formatTime(timestamp) {
                    const date = new Date(timestamp);
                    const now = new Date();
                    const diff = Math.floor((now - date) / 1000);
                    
                    if (diff < 60) return 'Gerade eben';
                    if (diff < 3600) return `Vor ${Math.floor(diff / 60)} Min`;
                    if (diff < 86400) return `Vor ${Math.floor(diff / 3600)} Std`;
                    return date.toLocaleDateString('de-DE');
                },
                
                async runValidation() {
                    this.isValidating = true;
                    this.overallStatus = 'pending';
                    
                    try {
                        const response = await fetch('/api/branch/' + @json($getRecord()?->id) + '/validate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        
                        const data = await response.json();
                        this.processValidationResults(data);
                        
                        // Notification
                        Livewire.emit('notify', 'Validierung abgeschlossen');
                    } catch (error) {
                        console.error('Fehler bei der Validierung:', error);
                        Livewire.emit('notify', 'Validierung fehlgeschlagen', 'error');
                    } finally {
                        this.isValidating = false;
                    }
                }
            }
        }
    </script>
</div>
