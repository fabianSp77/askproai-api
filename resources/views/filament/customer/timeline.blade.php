<div class="space-y-6">
    <\!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Kunden-Timeline
        </h2>
        <span class="text-sm text-gray-500 dark:text-gray-400">
            {{ $customer->created_at->format('d.m.Y') }} - Heute
        </span>
    </div>

    <\!-- Timeline -->
    <div class="relative">
        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
        
        @php
            $activities = collect();
            
            // Add appointments
            foreach($customer->appointments as $appointment) {
                $activities->push([
                    'type' => 'appointment',
                    'date' => $appointment->starts_at,
                    'status' => $appointment->status,
                    'data' => $appointment,
                ]);
            }
            
            // Add calls
            foreach($customer->calls as $call) {
                $activities->push([
                    'type' => 'call',
                    'date' => $call->created_at,
                    'data' => $call,
                ]);
            }
            
            // Add customer creation
            $activities->push([
                'type' => 'created',
                'date' => $customer->created_at,
                'data' => $customer,
            ]);
            
            // Sort by date descending
            $activities = $activities->sortByDesc('date');
        @endphp
        
        <div class="space-y-6">
            @foreach($activities as $activity)
                <div class="relative flex items-start space-x-3">
                    <\!-- Icon -->
                    <div class="relative">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full ring-4 ring-white dark:ring-gray-900
                            @if($activity['type'] === 'appointment')
                                @if($activity['status'] === 'completed') bg-green-500 @elseif($activity['status'] === 'cancelled') bg-red-500 @else bg-blue-500 @endif
                            @elseif($activity['type'] === 'call')
                                bg-purple-500
                            @else
                                bg-gray-500
                            @endif">
                            @if($activity['type'] === 'appointment')
                                <x-heroicon-m-calendar-days class="h-4 w-4 text-white" />
                            @elseif($activity['type'] === 'call')
                                <x-heroicon-m-phone class="h-4 w-4 text-white" />
                            @else
                                <x-heroicon-m-user class="h-4 w-4 text-white" />
                            @endif
                        </div>
                    </div>
                    
                    <\!-- Content -->
                    <div class="flex-1 -mt-1.5">
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    @if($activity['type'] === 'appointment')
                                        Termin: {{ $activity['data']->service?->name ?? 'Unbekannter Service' }}
                                    @elseif($activity['type'] === 'call')
                                        Anruf von {{ $activity['data']->from_number }}
                                    @else
                                        Kunde registriert
                                    @endif
                                </h4>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $activity['date']->diffForHumans() }}
                                </span>
                            </div>
                            
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                @if($activity['type'] === 'appointment')
                                    <div class="space-y-1">
                                        <div>{{ $activity['date']->format('d.m.Y H:i') }} Uhr</div>
                                        <div>Mitarbeiter: {{ $activity['data']->staff?->name ?? 'Nicht zugewiesen' }}</div>
                                        <div>Filiale: {{ $activity['data']->branch?->name ?? 'Nicht zugewiesen' }}</div>
                                        @if($activity['data']->price)
                                            <div>Preis: €{{ number_format($activity['data']->price, 2, ',', '.') }}</div>
                                        @endif
                                        <div class="mt-2">
                                            @if($activity['status'] === 'completed')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    Abgeschlossen
                                                </span>
                                            @elseif($activity['status'] === 'cancelled')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    Storniert
                                                </span>
                                            @elseif($activity['status'] === 'no_show')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                                    Nicht erschienen
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    Geplant
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($activity['type'] === 'call')
                                    <div class="space-y-1">
                                        <div>Dauer: {{ gmdate('i:s', $activity['data']->duration_sec ?? 0) }}</div>
                                        @if($activity['data']->analysis && isset($activity['data']->analysis['sentiment']))
                                            <div>Stimmung: {{ ucfirst($activity['data']->analysis['sentiment']) }}</div>
                                        @endif
                                        @if($activity['data']->appointment)
                                            <div class="text-green-600 dark:text-green-400">
                                                → Termin gebucht
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div>
                                        Neuer Kunde wurde im System angelegt.
                                        @if($customer->phone)
                                            <div>Telefon: {{ $customer->phone }}</div>
                                        @endif
                                        @if($customer->email)
                                            <div>E-Mail: {{ $customer->email }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    
    <\!-- Summary -->
    <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $customer->appointments()->count() }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Termine gesamt</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ $customer->appointments()->where('status', 'completed')->count() }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Abgeschlossen</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ $customer->appointments()->whereIn('status', ['cancelled', 'no_show'])->count() }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Storniert/No-Show</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    {{ $customer->calls()->count() }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Anrufe</div>
            </div>
        </div>
    </div>
</div>
