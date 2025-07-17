@props(['customer', 'currentCallId'])

@php
    $recentCalls = $customer->calls()
        ->where('id', '!=', $currentCallId)
        ->with(['appointment', 'mlPrediction'])
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    $upcomingAppointments = $customer->appointments()
        ->where('starts_at', '>', now())
        ->orderBy('starts_at')
        ->limit(3)
        ->get();
    
    $stats = [
        'totalCalls' => $customer->calls()->count(),
        'completedAppointments' => $customer->appointments()->where('status', 'completed')->count(),
        'noShows' => $customer->appointments()->where('status', 'no_show')->count(),
        'avgCallDuration' => round($customer->calls()->avg('duration_sec') ?? 0),
        'lastContactDays' => $customer->calls()->latest()->first()?->created_at->diffInDays(now()) ?? 0,
    ];
    
    $riskLevel = $stats['noShows'] >= 3 ? 'high' : ($stats['noShows'] >= 1 ? 'medium' : 'low');
@endphp

<div class="space-y-4">
    {{-- Customer Stats Overview --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">Anrufe gesamt</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $stats['totalCalls'] }}</p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">Termine</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $stats['completedAppointments'] }}</p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">No-Shows</p>
            <p class="text-lg font-semibold text-{{ $riskLevel === 'high' ? 'red' : ($riskLevel === 'medium' ? 'amber' : 'gray') }}-600 dark:text-{{ $riskLevel === 'high' ? 'red' : ($riskLevel === 'medium' ? 'amber' : 'gray') }}-400">
                {{ $stats['noShows'] }}
            </p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">Ø Anrufdauer</p>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ gmdate('i:s', $stats['avgCallDuration']) }}</p>
        </div>
    </div>
    
    {{-- Risk Indicator --}}
    @if($riskLevel !== 'low')
        <div class="flex items-center gap-2 p-3 rounded-lg {{ $riskLevel === 'high' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-amber-50 dark:bg-amber-900/20' }}">
            <svg class="w-5 h-5 {{ $riskLevel === 'high' ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <span class="text-sm {{ $riskLevel === 'high' ? 'text-red-700 dark:text-red-300' : 'text-amber-700 dark:text-amber-300' }}">
                {{ $riskLevel === 'high' ? 'Hohe No-Show-Gefahr' : 'Mittlere No-Show-Gefahr' }}
            </span>
        </div>
    @endif
    
    {{-- Upcoming Appointments --}}
    @if($upcomingAppointments->isNotEmpty())
        <div>
            <h4 class="text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                Kommende Termine
            </h4>
            <div class="space-y-2">
                @foreach($upcomingAppointments as $appointment)
                    <div class="flex items-center justify-between p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $appointment->starts_at->format('d.m.Y H:i') }}
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $appointment->service?->name ?? 'Allgemein' }}
                                </p>
                            </div>
                        </div>
                        <span class="text-xs text-blue-600 dark:text-blue-400">
                            {{ $appointment->starts_at->diffForHumans() }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    {{-- Recent Interactions Timeline --}}
    <div>
        <h4 class="text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
            Letzte Interaktionen
        </h4>
        <div class="relative">
            {{-- Timeline line --}}
            <div class="absolute left-4 top-6 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
            
            <div class="space-y-4">
                @forelse($recentCalls as $call)
                    <div class="relative flex items-start gap-4">
                        {{-- Timeline dot --}}
                        <div class="relative z-10 flex items-center justify-center w-8 h-8 rounded-full {{ $call->appointment ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-700' }}">
                            <svg class="w-4 h-4 {{ $call->appointment ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        
                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    Anruf ({{ gmdate('i:s', $call->duration_sec) }})
                                </p>
                                <time class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $call->created_at->format('d.m.Y H:i') }}
                                </time>
                            </div>
                            
                            @if($call->summary || $call->call_summary)
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                    {{ Str::limit($call->summary ?? $call->call_summary, 100) }}
                                </p>
                            @endif
                            
                            <div class="flex items-center gap-3 mt-1">
                                @if($call->mlPrediction)
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $call->mlPrediction->sentiment_label === 'positive' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : ($call->mlPrediction->sentiment_label === 'negative' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400') }}">
                                        {{ ucfirst($call->mlPrediction->sentiment_label) }}
                                    </span>
                                @endif
                                
                                @if($call->appointment)
                                    <span class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        Termin gebucht
                                    </span>
                                @elseif($call->appointment_requested)
                                    <span class="text-xs text-amber-600 dark:text-amber-400">
                                        Termin angefragt
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                        Keine vorherigen Anrufe gefunden
                    </p>
                @endforelse
            </div>
        </div>
    </div>
    
    {{-- Customer Preferences --}}
    @if($customer->metadata && isset($customer->metadata['preferences']))
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <h4 class="text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                Kundenpräferenzen
            </h4>
            <div class="space-y-1">
                @foreach($customer->metadata['preferences'] as $key => $value)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">{{ ucfirst($key) }}:</span>
                        <span class="text-gray-900 dark:text-white">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>