<div class="w-full space-y-6">
    @php
        $record = $record ?? null;
        $analysis = $record?->analysis ?? [];
        $entities = $analysis['entities'] ?? [];
        
        // Performance Scores
        $conversionScore = $analysis['conversion_score'] ?? 0;
        $sentiment = $analysis['sentiment'] ?? 'neutral';
        $sentimentScore = match($sentiment) {
            'positive' => 100,
            'neutral' => 50,
            'negative' => 0,
            default => 50
        };
        $urgencyScore = match($analysis['urgency'] ?? 'normal') {
            'high' => 100,
            'medium' => 66,
            'low' => 33,
            'normal' => 50,
            default => 50
        };
        
        // Entity completion score
        $requiredEntities = ['name', 'email', 'phone', 'date', 'time', 'service'];
        $completedEntities = count(array_filter($requiredEntities, fn($key) => !empty($entities[$key])));
        $completionScore = ($completedEntities / count($requiredEntities)) * 100;
        
        // Call efficiency (shorter calls with positive outcome are more efficient)
        $targetDuration = 180; // 3 minutes ideal
        $efficiencyScore = $record?->duration_sec ? min(100, ($targetDuration / $record->duration_sec) * 100) : 0;
        if ($sentiment === 'negative') {
            $efficiencyScore *= 0.5; // Penalize negative calls
        }
    @endphp
    
    <!-- Performance Metrics Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Conversion Score -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Conversion</span>
                <span class="text-xs text-gray-500">{{ number_format($conversionScore, 0) }}%</span>
            </div>
            <div class="relative pt-1">
                <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                    <div style="width:{{ $conversionScore }}%" 
                         class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center 
                                {{ $conversionScore >= 70 ? 'bg-green-500' : ($conversionScore >= 40 ? 'bg-yellow-500' : 'bg-red-500') }}">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sentiment Score -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Stimmung</span>
                <span class="text-xs text-gray-500">{{ $sentiment }}</span>
            </div>
            <div class="relative pt-1">
                <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                    <div style="width:{{ $sentimentScore }}%" 
                         class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center 
                                {{ $sentimentScore >= 70 ? 'bg-green-500' : ($sentimentScore >= 40 ? 'bg-yellow-500' : 'bg-red-500') }}">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Data Completion -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Daten</span>
                <span class="text-xs text-gray-500">{{ $completedEntities }}/{{ count($requiredEntities) }}</span>
            </div>
            <div class="relative pt-1">
                <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                    <div style="width:{{ $completionScore }}%" 
                         class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center 
                                {{ $completionScore >= 70 ? 'bg-green-500' : ($completionScore >= 40 ? 'bg-yellow-500' : 'bg-red-500') }}">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Efficiency Score -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Effizienz</span>
                <span class="text-xs text-gray-500">{{ gmdate('i:s', $record?->duration_sec ?? 0) }}</span>
            </div>
            <div class="relative pt-1">
                <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                    <div style="width:{{ $efficiencyScore }}%" 
                         class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center 
                                {{ $efficiencyScore >= 70 ? 'bg-green-500' : ($efficiencyScore >= 40 ? 'bg-yellow-500' : 'bg-red-500') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Call Flow Visualization -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Anrufverlauf</h4>
        
        <div class="relative">
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-300 dark:bg-gray-600"></div>
            
            <div class="space-y-6">
                <!-- Call Start -->
                <div class="relative flex items-start">
                    <div class="absolute left-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                    </div>
                    <div class="ml-12">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Anruf gestartet</p>
                        <p class="text-xs text-gray-500">{{ $record?->created_at?->format('H:i:s') ?? '--:--:--' }}</p>
                    </div>
                </div>
                
                @if(!empty($entities['name']))
                <div class="relative flex items-start">
                    <div class="absolute left-0 w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-12">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Name erfasst</p>
                        <p class="text-xs text-gray-500">{{ $entities['name'] }}</p>
                    </div>
                </div>
                @endif
                
                @if(!empty($entities['service']))
                <div class="relative flex items-start">
                    <div class="absolute left-0 w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div class="ml-12">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Service gewählt</p>
                        <p class="text-xs text-gray-500">{{ $entities['service'] }}</p>
                    </div>
                </div>
                @endif
                
                @if(!empty($entities['date']) && !empty($entities['time']))
                <div class="relative flex items-start">
                    <div class="absolute left-0 w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-12">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Termin gewünscht</p>
                        <p class="text-xs text-gray-500">{{ $entities['date'] }} um {{ $entities['time'] }}</p>
                    </div>
                </div>
                @endif
                
                @if($record?->appointment_id)
                <div class="relative flex items-start">
                    <div class="absolute left-0 w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="ml-12">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Termin gebucht</p>
                        <p class="text-xs text-gray-500">{{ $record->appointment?->starts_at?->format('d.m.Y H:i') ?? 'Datum unbekannt' }}</p>
                    </div>
                </div>
                @endif
                
                <!-- Call End -->
                <div class="relative flex items-start">
                    <div class="absolute left-0 w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"></path>
                        </svg>
                    </div>
                    <div class="ml-12">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Anruf beendet</p>
                        <p class="text-xs text-gray-500">
                            {{ $record?->created_at?->addSeconds($record->duration_sec)?->format('H:i:s') ?? '--:--:--' }}
                            ({{ $record?->disconnection_reason === 'customer_hung_up' ? 'Kunde aufgelegt' : 'Agent beendet' }})
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Key Insights -->
    @if(!empty($analysis['key_points']) || !empty($analysis['action_items']))
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Wichtige Erkenntnisse</h4>
        
        <div class="grid md:grid-cols-2 gap-6">
            @if(!empty($analysis['key_points']))
            <div>
                <h5 class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Kernpunkte</h5>
                <ul class="space-y-2">
                    @foreach($analysis['key_points'] as $point)
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-blue-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $point }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            
            @if(!empty($analysis['action_items']))
            <div>
                <h5 class="text-xs font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-2">Nächste Schritte</h5>
                <ul class="space-y-2">
                    @foreach($analysis['action_items'] as $action)
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-amber-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $action }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>