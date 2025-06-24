<div class="space-y-6">
    {{-- Header Section --}}
    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ $record->customer?->name ?? 'Unbekannter Anrufer' }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ $record->from_number }}
                </p>
            </div>
            @if($record->analysis['sentiment'] ?? null)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    {{ match($record->analysis['sentiment']) {
                        'positive' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                        'negative' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                    } }}">
                    {{ match($record->analysis['sentiment']) {
                        'positive' => 'Positiv',
                        'negative' => 'Negativ',
                        default => 'Neutral'
                    } }}
                </span>
            @endif
        </div>
    </div>

    {{-- Call Information --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Anrufdatum</h3>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                {{ $record->start_timestamp?->format('d.m.Y H:i:s') ?? $record->created_at->format('d.m.Y H:i:s') }}
            </p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Dauer</h3>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                {{ gmdate('i:s', $record->duration_sec ?? 0) }}
            </p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Filiale</h3>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                {{ $record->branch?->name ?? 'Keine Filiale zugeordnet' }}
            </p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h3>
            <p class="mt-1 text-sm text-gray-900 dark:text-white">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                    {{ $record->appointment_id ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                    {{ $record->appointment_id ? 'Termin gebucht' : 'Kein Termin' }}
                </span>
            </p>
        </div>
    </div>

    {{-- Summary --}}
    @if($record->analysis['summary'] ?? null)
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Zusammenfassung</h3>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-sm text-gray-900 dark:text-white">
                    {{ $record->analysis['summary'] }}
                </p>
            </div>
        </div>
    @endif

    {{-- Transcript --}}
    @if($record->transcript ?? null)
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Transkript</h3>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 max-h-64 overflow-y-auto">
                <div class="space-y-3">
                    @if(is_array($record->transcript))
                        @foreach($record->transcript as $entry)
                            <div class="flex items-start gap-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-medium
                                    {{ $entry['role'] === 'agent' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $entry['role'] === 'agent' ? 'AI' : 'K' }}
                                </span>
                                <p class="text-sm text-gray-900 dark:text-white flex-1">
                                    {{ $entry['content'] ?? '' }}
                                </p>
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-900 dark:text-white">
                            {{ $record->transcript }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Entities --}}
    @if($record->analysis['entities'] ?? null)
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Erkannte Informationen</h3>
            <div class="grid grid-cols-2 gap-3">
                @foreach($record->analysis['entities'] as $key => $value)
                    @if($value)
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ str_replace('_', ' ', ucfirst($key)) }}
                            </h4>
                            <p class="text-sm text-gray-900 dark:text-white mt-1">
                                {{ is_array($value) ? json_encode($value) : $value }}
                            </p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Actions --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if($record->audio_url)
                    <button
                        type="button"
                        wire:click="mountTableAction('play_recording', '{{ $record->id }}')"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500"
                    >
                        <svg class="mr-2 -ml-0.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Aufzeichnung abspielen
                    </button>
                @endif

                @if(!$record->appointment_id && $record->customer_id)
                    <button
                        type="button"
                        wire:click="mountTableAction('create_appointment', '{{ $record->id }}')"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500"
                    >
                        <svg class="mr-2 -ml-0.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Termin erstellen
                    </button>
                @endif
            </div>

            @if($record->appointment)
                <a
                    href="{{ \App\Filament\Admin\Resources\AppointmentResource::getUrl('view', ['record' => $record->appointment]) }}"
                    class="text-sm text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300"
                >
                    Termin anzeigen →
                </a>
            @endif
        </div>
    </div>
</div>