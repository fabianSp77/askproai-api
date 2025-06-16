<div class="space-y-6">
    @if($appointment)
        {{-- Header mit Status --}}
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Termin #{{ $appointment->id }}
            </h3>
            <x-filament::badge :color="match($appointment->status) {
                'confirmed' => 'success',
                'pending' => 'warning',
                'completed' => 'gray',
                'cancelled' => 'danger',
                'no_show' => 'danger',
                default => 'gray'
            }">
                {{ match($appointment->status) {
                    'confirmed' => 'Bestätigt',
                    'pending' => 'Ausstehend',
                    'completed' => 'Abgeschlossen',
                    'cancelled' => 'Abgesagt',
                    'no_show' => 'Nicht erschienen',
                    default => $appointment->status
                } }}
            </x-filament::badge>
        </div>
        
        {{-- Kunde --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kunde</h4>
            <div class="space-y-1">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ $appointment->customer->name }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <x-heroicon-m-phone class="inline-block w-4 h-4 mr-1" />
                    {{ $appointment->customer->phone }}
                </p>
                @if($appointment->customer->email)
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <x-heroicon-m-envelope class="inline-block w-4 h-4 mr-1" />
                        {{ $appointment->customer->email }}
                    </p>
                @endif
            </div>
        </div>
        
        {{-- Service & Mitarbeiter --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Service</h4>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ $appointment->service->name }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $appointment->service->duration }} Minuten
                </p>
                <p class="text-sm font-semibold text-green-600 dark:text-green-400">
                    €{{ number_format($appointment->service->price, 2, ',', '.') }}
                </p>
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mitarbeiter</h4>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ $appointment->staff->name }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $appointment->branch->name }}
                </p>
            </div>
        </div>
        
        {{-- Zeit & Datum --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Termin</h4>
            <div class="flex items-center space-x-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Datum</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $appointment->starts_at->format('d.m.Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Zeit</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ $appointment->starts_at->format('H:i') }} - {{ $appointment->ends_at->format('H:i') }}
                    </p>
                </div>
                @if($appointment->checked_in_at)
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Check-in</p>
                        <p class="text-sm font-semibold text-green-600 dark:text-green-400">
                            <x-heroicon-m-check-badge class="inline-block w-4 h-4 mr-1" />
                            {{ $appointment->checked_in_at->format('H:i') }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
        
        {{-- Notizen --}}
        @if($appointment->notes)
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notizen</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ $appointment->notes }}</p>
            </div>
        @endif
        
        {{-- Metadaten --}}
        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
            <p>Erstellt: {{ $appointment->created_at->format('d.m.Y H:i') }}</p>
            @if($appointment->updated_at->ne($appointment->created_at))
                <p>Zuletzt geändert: {{ $appointment->updated_at->format('d.m.Y H:i') }}</p>
            @endif
            @if($appointment->booking_source)
                <p>Buchungsquelle: {{ $appointment->booking_source }}</p>
            @endif
        </div>
    @else
        <p class="text-gray-500 dark:text-gray-400">Termin nicht gefunden.</p>
    @endif
</div>