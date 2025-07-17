@php
    use App\Services\Customer\CustomerMatchingService;
    use Carbon\Carbon;
    
    $record = $getRecord();
    $matchingService = app(CustomerMatchingService::class);
    
    // Hole verwandte Interaktionen nur wenn Kunde vorhanden
    $interactions = null;
    if ($record->customer_id && $record->customer) {
        $interactions = $matchingService->getRelatedInteractions($record->customer);
    }
    
    // Extrahiere Kundendaten aus dem Call für Matching
    $phoneNumber = $record->from_number;
    $companyName = $record->metadata['customer_data']['company'] ?? 
                   $record->extracted_company ?? 
                   $record->customer?->company_name ?? 
                   null;
    $customerNumber = $record->metadata['customer_data']['customer_number'] ?? 
                      $record->customer?->customer_number ?? 
                      null;
    
    // Finde potenzielle Matches wenn kein direkter Kunde zugeordnet ist
    $potentialMatches = collect();
    if (!$record->customer_id && $phoneNumber) {
        $potentialMatches = $matchingService->findRelatedCustomers(
            $record->company_id,
            $record->to_number,
            $phoneNumber,
            $companyName,
            $customerNumber
        );
    }
@endphp

<div class="customer-interaction-history">
    @if($interactions && ($interactions['total_calls'] > 1 || $interactions['total_appointments'] > 0))
        {{-- Hauptkunde hat Historie --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Kundenhistorie
                    </h3>
                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                        {{ $interactions['customer']->name }} 
                        @if($interactions['customer']->company_name)
                            ({{ $interactions['customer']->company_name }})
                        @endif
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                        {{ $interactions['total_calls'] }}
                    </div>
                    <div class="text-xs text-blue-700 dark:text-blue-300">
                        {{ $interactions['total_calls'] == 1 ? 'Anruf' : 'Anrufe' }}
                    </div>
                </div>
            </div>
            
            {{-- Statistiken --}}
            <div class="grid grid-cols-3 gap-3 mb-3">
                <div class="text-center">
                    <div class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                        {{ $interactions['total_appointments'] }}
                    </div>
                    <div class="text-xs text-blue-700 dark:text-blue-300">
                        {{ $interactions['total_appointments'] == 1 ? 'Termin' : 'Termine' }}
                    </div>
                </div>
                
                @if($interactions['last_interaction'])
                    <div class="text-center">
                        <div class="text-xs text-blue-700 dark:text-blue-300">Letzter Kontakt</div>
                        <div class="text-xs font-medium text-blue-900 dark:text-blue-100">
                            @php
                                $lastDate = $interactions['last_interaction']['type'] === 'call' 
                                    ? ($interactions['last_interaction']['data']->start_timestamp ?? $interactions['last_interaction']['data']->created_at)
                                    : $interactions['last_interaction']['data']->starts_at;
                            @endphp
                            {{ Carbon::parse($lastDate)->diffForHumans() }}
                        </div>
                    </div>
                @endif
                
                @if(count($interactions['related_customers']) > 0)
                    <div class="text-center">
                        <div class="text-lg font-semibold text-blue-900 dark:text-blue-100">
                            {{ count($interactions['related_customers']) }}
                        </div>
                        <div class="text-xs text-blue-700 dark:text-blue-300">
                            Verknüpfungen
                        </div>
                    </div>
                @endif
            </div>
            
            {{-- Quick Links --}}
            <div class="flex flex-wrap gap-2 pt-3 border-t border-blue-200 dark:border-blue-700">
                <a href="{{ \App\Filament\Admin\Resources\CallResource::getUrl('index', [
                    'tableFilters[customer][value]' => $interactions['customer']->id
                ]) }}" 
                   target="_blank"
                   class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-700 bg-white dark:bg-blue-800 dark:text-blue-200 rounded hover:bg-blue-100 dark:hover:bg-blue-700 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    Alle Anrufe
                </a>
                
                @if($interactions['total_appointments'] > 0)
                    <a href="{{ \App\Filament\Admin\Resources\AppointmentResource::getUrl('index', [
                        'tableFilters[customer][value]' => $interactions['customer']->id
                    ]) }}" 
                       target="_blank"
                       class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-700 bg-white dark:bg-blue-800 dark:text-blue-200 rounded hover:bg-blue-100 dark:hover:bg-blue-700 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Alle Termine
                    </a>
                @endif
                
                <a href="{{ \App\Filament\Admin\Resources\CustomerResource::getUrl('view', ['record' => $interactions['customer']->id]) }}" 
                   target="_blank"
                   class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-700 bg-white dark:bg-blue-800 dark:text-blue-200 rounded hover:bg-blue-100 dark:hover:bg-blue-700 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Kundenprofil
                </a>
            </div>
            
            {{-- Letzte Interaktionen --}}
            @if($interactions['recent_calls']->count() > 1 || $interactions['recent_appointments']->count() > 0)
                <div class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-700">
                    <h4 class="text-xs font-medium text-blue-900 dark:text-blue-100 mb-2">Letzte Aktivitäten</h4>
                    <div class="space-y-1">
                        @foreach($interactions['recent_calls']->take(3) as $call)
                            @if($call->id !== $record->id)
                                <div class="text-xs text-blue-700 dark:text-blue-300 flex items-center gap-2">
                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    <span>
                                        {{ Carbon::parse($call->start_timestamp ?? $call->created_at)->format('d.m.Y H:i') }}
                                        - {{ $call->duration_sec ? sprintf('%d:%02d', floor($call->duration_sec / 60), $call->duration_sec % 60) : '0:00' }}
                                    </span>
                                </div>
                            @endif
                        @endforeach
                        
                        @foreach($interactions['recent_appointments']->take(2) as $appointment)
                            <div class="text-xs text-blue-700 dark:text-blue-300 flex items-center gap-2">
                                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span>
                                    {{ $appointment->starts_at->format('d.m.Y H:i') }}
                                    - {{ $appointment->service?->name ?? 'Termin' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        
    @elseif($potentialMatches->count() > 0)
        {{-- Potenzielle Übereinstimmungen gefunden --}}
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
            <h3 class="text-sm font-semibold text-amber-900 dark:text-amber-100 flex items-center gap-2 mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Mögliche Kundenübereinstimmungen
            </h3>
            
            <div class="space-y-2">
                @foreach($potentialMatches->take(3) as $match)
                    <div class="flex items-start justify-between p-2 bg-white dark:bg-amber-800/20 rounded">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-amber-900 dark:text-amber-100">
                                {{ $match->name }}
                                @if($match->company_name)
                                    <span class="text-xs text-amber-700 dark:text-amber-300">
                                        ({{ $match->company_name }})
                                    </span>
                                @endif
                            </div>
                            <div class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    {{ $match->phone }}
                                </span>
                                @if($match->call_count > 0)
                                    <span class="ml-2">{{ $match->call_count }} {{ $match->call_count == 1 ? 'Anruf' : 'Anrufe' }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium px-2 py-1 rounded-full 
                                {{ $match->match_confidence >= 90 ? 'bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 
                                   ($match->match_confidence >= 70 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300' : 
                                    'bg-gray-100 text-gray-700 dark:bg-gray-900/20 dark:text-gray-300') }}">
                                {{ $match->match_confidence }}%
                            </span>
                            <a href="{{ \App\Filament\Admin\Resources\CustomerResource::getUrl('view', ['record' => $match->id]) }}" 
                               target="_blank"
                               class="p-1 hover:bg-amber-100 dark:hover:bg-amber-700 rounded transition-colors"
                               title="Kundenprofil anzeigen">
                                <svg class="w-4 h-4 text-amber-700 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if(!$record->customer_id)
                <div class="mt-3 pt-3 border-t border-amber-200 dark:border-amber-700">
                    <p class="text-xs text-amber-700 dark:text-amber-300">
                        Tipp: Ordnen Sie diesen Anruf einem Kunden zu, um die vollständige Historie zu sehen.
                    </p>
                </div>
            @endif
        </div>
        
    @elseif($record->customer_id && $interactions)
        {{-- Kunde hat keine weitere Historie --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Dies ist der erste Kontakt mit diesem Kunden.
            </p>
        </div>
        
    @else
        {{-- Kein Kunde zugeordnet und keine Matches --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Kein Kunde zugeordnet. Verwenden Sie die Aktion "Kunde zuordnen" um die Historie anzuzeigen.
            </p>
        </div>
    @endif
</div>

<style>
.customer-interaction-history {
    @apply mt-4;
}
</style>