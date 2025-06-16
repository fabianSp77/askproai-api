<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Call Data Analysis -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-phone class="w-5 h-5" />
                    <span>Anrufdaten Analyse</span>
                </div>
            </x-slot>
            
            @php
                $analysis = $this->getCallDataAnalysis();
            @endphp
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">{{ $analysis['total'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Gesamt Anrufe</div>
                </div>
                
                <div class="text-center">
                    <div class="text-3xl font-bold {{ $analysis['withTranscript'] > 0 ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $analysis['withTranscript'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Mit Transkript</div>
                    <div class="text-xs text-gray-500">{{ $analysis['total'] > 0 ? round($analysis['withTranscript'] / $analysis['total'] * 100) : 0 }}%</div>
                </div>
                
                <div class="text-center">
                    <div class="text-3xl font-bold {{ $analysis['withDuration'] > 0 ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $analysis['withDuration'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Mit Dauer</div>
                    <div class="text-xs text-gray-500">{{ $analysis['total'] > 0 ? round($analysis['withDuration'] / $analysis['total'] * 100) : 0 }}%</div>
                </div>
                
                <div class="text-center">
                    <div class="text-3xl font-bold {{ $analysis['withBranch'] > 0 ? 'text-success-600' : 'text-danger-600' }}">
                        {{ $analysis['withBranch'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Mit Filiale</div>
                    <div class="text-xs text-gray-500">{{ $analysis['total'] > 0 ? round($analysis['withBranch'] / $analysis['total'] * 100) : 0 }}%</div>
                </div>
                
                <div class="text-center">
                    <div class="text-3xl font-bold {{ $analysis['withAppointment'] > 0 ? 'text-info-600' : 'text-gray-600' }}">
                        {{ $analysis['withAppointment'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Mit Termin</div>
                    <div class="text-xs text-gray-500">{{ $analysis['total'] > 0 ? round($analysis['withAppointment'] / $analysis['total'] * 100) : 0 }}%</div>
                </div>
                
                <div class="text-center">
                    <div class="text-3xl font-bold {{ $analysis['withRecording'] > 0 ? 'text-info-600' : 'text-gray-600' }}">
                        {{ $analysis['withRecording'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Mit Aufnahme</div>
                    <div class="text-xs text-gray-500">{{ $analysis['total'] > 0 ? round($analysis['withRecording'] / $analysis['total'] * 100) : 0 }}%</div>
                </div>
                
                <div class="text-center">
                    <div class="text-3xl font-bold {{ $analysis['withAnalysis'] > 0 ? 'text-info-600' : 'text-gray-600' }}">
                        {{ $analysis['withAnalysis'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Mit Analyse</div>
                    <div class="text-xs text-gray-500">{{ $analysis['total'] > 0 ? round($analysis['withAnalysis'] / $analysis['total'] * 100) : 0 }}%</div>
                </div>
                
                <div class="text-center">
                    <div class="text-3xl font-bold {{ $analysis['withCost'] > 0 ? 'text-warning-600' : 'text-gray-600' }}">
                        {{ $analysis['withCost'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Mit Kosten</div>
                    <div class="text-xs text-gray-500">{{ $analysis['total'] > 0 ? round($analysis['withCost'] / $analysis['total'] * 100) : 0 }}%</div>
                </div>
            </div>
        </x-filament::section>
        
        <!-- Missing Data Report -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-500" />
                    <span>Fehlende Daten Report</span>
                </div>
            </x-slot>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="text-left py-2">Call ID</th>
                            <th class="text-left py-2">Erstellt</th>
                            <th class="text-left py-2">Fehlende Felder</th>
                            <th class="text-right py-2">Vollständigkeit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @foreach($this->getMissingDataReport() as $report)
                            <tr>
                                <td class="py-2">
                                    <a href="{{ route('filament.admin.resources.calls.view', $report['id']) }}" 
                                       class="text-primary-600 hover:underline">
                                        {{ $report['call_id'] }}
                                    </a>
                                </td>
                                <td class="py-2">{{ $report['created'] }}</td>
                                <td class="py-2">
                                    @foreach($report['missing'] as $field)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger-100 text-danger-800 dark:bg-danger-800 dark:text-danger-100 mr-1">
                                            {{ $field }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="py-2 text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                        {{ $report['completeness'] >= 80 ? 'bg-success-100 text-success-800 dark:bg-success-800 dark:text-success-100' : 
                                           ($report['completeness'] >= 50 ? 'bg-warning-100 text-warning-800 dark:bg-warning-800 dark:text-warning-100' : 
                                            'bg-danger-100 text-danger-800 dark:bg-danger-800 dark:text-danger-100') }}">
                                        {{ round($report['completeness']) }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
        
        <!-- Webhook Structure -->
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-code-bracket class="w-5 h-5" />
                    <span>Webhook Struktur Analyse</span>
                </div>
            </x-slot>
            
            @php
                $webhookData = $this->getWebhookData();
                $sampleStructure = $this->getSampleWebhookStructure();
            @endphp
            
            @if($webhookData['hasData'])
                <div class="mb-4">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Letzter Webhook empfangen:</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $webhookData['timestamp'] }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Payload Keys: <code class="text-xs">{{ implode(', ', $webhookData['payloadKeys']) }}</code>
                    </p>
                </div>
            @else
                <p class="text-gray-600 dark:text-gray-400">{{ $webhookData['message'] }}</p>
            @endif
            
            @if($sampleStructure['hasData'])
                <div class="mt-6">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Datenstruktur (Call ID: {{ $sampleStructure['callId'] }}):</h4>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 overflow-x-auto">
                        <pre class="text-xs"><code>{{ json_encode($sampleStructure['structure'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                    </div>
                </div>
            @endif
        </x-filament::section>
        
        <!-- Actions -->
        <x-filament::section>
            <div class="flex gap-3">
                <x-filament::button 
                    wire:click="$refresh"
                    icon="heroicon-o-arrow-path">
                    Aktualisieren
                </x-filament::button>
                
                <x-filament::button 
                    href="{{ route('filament.admin.resources.calls.index') }}"
                    color="gray"
                    icon="heroicon-o-phone">
                    Zu den Anrufen
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>