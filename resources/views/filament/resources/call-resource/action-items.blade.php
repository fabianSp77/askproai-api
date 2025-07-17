<div class="space-y-3">
    @php
        $actionItems = [];
        $analysis = $record->analysis ?? [];
        
        // Priority 1: Negative sentiment
        if (($analysis['sentiment'] ?? null) === 'negative') {
            $actionItems[] = [
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'danger',
                'title' => 'Dringend nachfassen!',
                'description' => 'Kunde war unzufrieden - sofortiger Rückruf planen empfohlen'
            ];
        }
        
        // Priority 2: High urgency without appointment
        if (($analysis['urgency'] ?? 'normal') === 'high' && !$record->appointment_id) {
            $actionItems[] = [
                'icon' => 'heroicon-o-clock',
                'color' => 'warning',
                'title' => 'Schnell Termin anbieten',
                'description' => 'Hohe Dringlichkeit erkannt - innerhalb 24h kontaktieren'
            ];
        }
        
        // Priority 3: Good conversion chance
        if (($analysis['conversion_score'] ?? 0) >= 70 && !$record->appointment_id) {
            $actionItems[] = [
                'icon' => 'heroicon-o-trophy',
                'color' => 'success',
                'title' => 'Gute Chance nutzen',
                'description' => 'Hohe Abschlusswahrscheinlichkeit - Termin vorschlagen'
            ];
        }
        
        // Priority 4: Missing information
        $missingInfo = [];
        $entities = $analysis['entities'] ?? [];
        if (empty($entities['name'])) $missingInfo[] = 'Name';
        if (empty($entities['email'])) $missingInfo[] = 'E-Mail';
        if (empty($entities['date']) && empty($entities['time'])) $missingInfo[] = 'Terminwunsch';
        
        if (!empty($missingInfo)) {
            $actionItems[] = [
                'icon' => 'heroicon-o-information-circle',
                'color' => 'info',
                'title' => 'Informationen vervollständigen',
                'description' => 'Fehlend: ' . implode(', ', $missingInfo)
            ];
        }
        
        // Default action if no specific items
        if (empty($actionItems)) {
            if ($record->appointment_id) {
                $actionItems[] = [
                    'icon' => 'heroicon-o-check-circle',
                    'color' => 'success',
                    'title' => 'Termin bestätigen',
                    'description' => '24h vor Termin Erinnerungs-SMS senden'
                ];
            } else {
                $actionItems[] = [
                    'icon' => 'heroicon-o-calendar',
                    'color' => 'gray',
                    'title' => 'Standard Follow-up',
                    'description' => 'In 2-3 Tagen erneut kontaktieren'
                ];
            }
        }
    @endphp
    
    @foreach($actionItems as $item)
    <div class="flex items-start space-x-3 p-3 rounded-lg bg-{{ $item['color'] }}-50 dark:bg-{{ $item['color'] }}-900/20 border border-{{ $item['color'] }}-200 dark:border-{{ $item['color'] }}-800">
        <div class="flex-shrink-0">
            <svg class="w-6 h-6 text-{{ $item['color'] }}-600 dark:text-{{ $item['color'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                @if($item['icon'] === 'heroicon-o-exclamation-triangle')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                @elseif($item['icon'] === 'heroicon-o-clock')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                @elseif($item['icon'] === 'heroicon-o-trophy')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                @elseif($item['icon'] === 'heroicon-o-information-circle')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                @elseif($item['icon'] === 'heroicon-o-check-circle')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                @endif
            </svg>
        </div>
        <div class="flex-1">
            <h4 class="text-sm font-semibold text-{{ $item['color'] }}-900 dark:text-{{ $item['color'] }}-100">
                {{ $item['title'] }}
            </h4>
            <p class="text-sm text-{{ $item['color'] }}-700 dark:text-{{ $item['color'] }}-300 mt-1">
                {{ $item['description'] }}
            </p>
        </div>
    </div>
    @endforeach
    
    <!-- Recommended Action Summary -->
    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border-l-4 border-primary-500">
        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
            {{ $recommendedAction }}
        </p>
    </div>
</div>