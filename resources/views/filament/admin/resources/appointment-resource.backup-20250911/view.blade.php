@php
    use App\Helpers\GermanFormatter;
    use Illuminate\Support\Str;
    $formatter = new GermanFormatter();
    
    // Berechne Fortschritt für aktive Termine
    $progress = 0;
    $isActive = false;
    $timeRemaining = null;
    if ($record->starts_at && $record->ends_at) {
        $now = now();
        if ($now->between($record->starts_at, $record->ends_at)) {
            $isActive = true;
            $totalMinutes = $record->starts_at->diffInMinutes($record->ends_at);
            $elapsedMinutes = $record->starts_at->diffInMinutes($now);
            $progress = min(100, ($elapsedMinutes / $totalMinutes) * 100);
            $timeRemaining = $now->diffInMinutes($record->ends_at);
        }
    }
    
    // Parse booking metadata
    $bookingMetadata = is_string($record->booking_metadata) 
        ? json_decode($record->booking_metadata, true) 
        : $record->booking_metadata;
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Status Bar für aktive Termine --}}
        @if($isActive)
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </span>
                    <span class="text-sm font-semibold text-green-800 dark:text-green-200">Termin läuft</span>
                </div>
                <span class="text-sm text-green-600 dark:text-green-400">
                    Noch {{ $timeRemaining }} Minuten
                </span>
            </div>
            <div class="w-full bg-green-200 dark:bg-green-800 rounded-full h-2">
                <div class="bg-green-600 dark:bg-green-400 h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
            </div>
        </div>
        @endif

        {{-- Enhanced Header mit Buchungstyp und Status --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white">
                            {{ $isActive ? '🔴' : '📅' }} Termindetails
                        </h2>
                        <p class="mt-1 text-blue-100">
                            {{ $formatter->formatDateTime($record->starts_at) }}
                        </p>
                    </div>
                    <div class="flex space-x-2">
                        {{-- Buchungstyp Badge --}}
                        @if($record->booking_type === 'recurring')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                </svg>
                                Serientermin
                            </span>
                        @elseif($record->booking_type === 'group')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                                <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                </svg>
                                Gruppentermin
                            </span>
                        @elseif($record->booking_type === 'package')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M11 17a1 1 0 001.447.894l4-2A1 1 0 0017 15V9.236a1 1 0 00-1.447-.894l-4 2a1 1 0 00-.553.894V17zM15.211 6.276a1 1 0 000-1.788l-4.764-2.382a1 1 0 00-.894 0L4.789 4.488a1 1 0 000 1.788l4.764 2.382a1 1 0 00.894 0l4.764-2.382zM4.447 8.342A1 1 0 003 9.236V15a1 1 0 00.553.894l4 2A1 1 0 009 17v-5.764a1 1 0 00-.553-.894l-4-2z"/>
                                </svg>
                                Paket
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-teal-100 text-teal-800">
                                <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                </svg>
                                Einzeltermin
                            </span>
                        @endif
                        
                        {{-- Status Badge --}}
                        @if($record->status === 'confirmed')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                ✓ Bestätigt
                            </span>
                        @elseif($record->status === 'pending')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                ⏳ Ausstehend
                            </span>
                        @elseif($record->status === 'cancelled')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                ✗ Storniert
                            </span>
                        @elseif($record->status === 'completed')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                ✓ Abgeschlossen
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                {{ ucfirst($record->status ?? 'scheduled') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Zeitinformationen --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            🕒 Zeitangaben
                        </h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Datum</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $formatter->formatDate($record->starts_at) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Beginn</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $formatter->formatTime($record->starts_at) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ende</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $formatter->formatTime($record->ends_at) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dauer</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $record->duration_minutes }} Minuten
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Kundeninformationen --}}
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            👤 Kunde
                        </h3>
                        <dl class="space-y-3">
                            @if($record->customer)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $record->customer->name ?? 'Nicht zugewiesen' }}
                                    </dd>
                                </div>
                                @if($record->customer->email)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">E-Mail</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            <a href="mailto:{{ $record->customer->email }}" class="text-blue-600 hover:text-blue-800">
                                                {{ $record->customer->email }}
                                            </a>
                                        </dd>
                                    </div>
                                @endif
                                @if($record->customer->phone)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Telefon</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                            {{ $formatter->formatPhoneNumber($record->customer->phone) }}
                                        </dd>
                                    </div>
                                @endif
                            @else
                                <div>
                                    <dd class="text-sm text-gray-500 dark:text-gray-400">
                                        Kein Kunde zugewiesen
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- Service und Mitarbeiter Details --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                💼 Service & Personal
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Service --}}
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Service</dt>
                    <dd class="mt-1">
                        @if($record->service)
                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                {{ $record->service->name }}
                            </div>
                            @if($record->price)
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Preis: {{ $formatter->formatCurrency($record->price) }}
                                </div>
                            @endif
                        @else
                            <span class="text-gray-500">Kein Service zugewiesen</span>
                        @endif
                    </dd>
                </div>

                {{-- Mitarbeiter --}}
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Mitarbeiter</dt>
                    <dd class="mt-1">
                        @if($record->staff)
                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                {{ $record->staff->name }}
                            </div>
                            @if($record->staff->email)
                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $record->staff->email }}
                                </div>
                            @endif
                        @else
                            <span class="text-gray-500">Kein Mitarbeiter zugewiesen</span>
                        @endif
                    </dd>
                </div>

                {{-- Filiale --}}
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Filiale</dt>
                    <dd class="mt-1">
                        @if($record->branch)
                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                {{ $record->branch->name }}
                            </div>
                            @if($record->branch->address)
                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $record->branch->address }}
                                </div>
                            @endif
                        @else
                            <span class="text-gray-500">Keine Filiale zugewiesen</span>
                        @endif
                    </dd>
                </div>
            </div>
        </div>

        {{-- Buchungsdetails für spezielle Typen --}}
        @if($record->booking_type !== 'single')
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                📋 Buchungsdetails
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if($record->booking_type === 'recurring' && $record->series_id)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Serien-ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">
                            {{ $record->series_id }}
                        </dd>
                    </div>
                @endif
                
                @if($record->booking_type === 'group' && $record->group_booking_id)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Gruppenbuchungs-ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">
                            {{ $record->group_booking_id }}
                        </dd>
                    </div>
                @endif

                @if($bookingMetadata && is_array($bookingMetadata))
                    @foreach($bookingMetadata as $key => $value)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                {{ ucfirst(str_replace('_', ' ', $key)) }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                @if(is_bool($value))
                                    {{ $value ? 'Ja' : 'Nein' }}
                                @elseif(is_array($value))
                                    {{ json_encode($value) }}
                                @else
                                    {{ $value }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
        @endif

        {{-- Notizen --}}
        @if($record->notes || $record->internal_notes)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                📝 Notizen
            </h3>
            @if($record->notes)
                <div class="mb-4">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Kundennotiz</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 rounded p-3">
                        {{ $record->notes }}
                    </dd>
                </div>
            @endif
            @if($record->internal_notes)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Interne Notiz</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100 bg-yellow-50 dark:bg-yellow-900/20 rounded p-3 border border-yellow-200 dark:border-yellow-800">
                        {{ $record->internal_notes }}
                    </dd>
                </div>
            @endif
        </div>
        @endif

        {{-- Integration IDs --}}
        @if($record->calcom_booking_id || $record->calcom_v2_booking_id || $record->external_id)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                🔗 Integrationen
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @if($record->calcom_booking_id)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cal.com v1 ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">
                            {{ $record->calcom_booking_id }}
                        </dd>
                    </div>
                @endif
                @if($record->calcom_v2_booking_id)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cal.com v2 ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">
                            {{ $record->calcom_v2_booking_id }}
                        </dd>
                    </div>
                @endif
                @if($record->external_id)
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Externe ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono">
                            {{ $record->external_id }}
                        </dd>
                    </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Timeline --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                ⏱️ Timeline
            </h3>
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    {{-- Erstellung --}}
                    <li>
                        <div class="relative pb-8">
                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                        <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">
                                            Termin erstellt
                                        </p>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $formatter->formatDateTime($record->created_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    {{-- Letzte Aktualisierung --}}
                    @if($record->updated_at && $record->updated_at != $record->created_at)
                    <li>
                        <div class="relative pb-8">
                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-yellow-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                        <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">
                                            Termin aktualisiert
                                        </p>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $formatter->formatDateTime($record->updated_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endif

                    {{-- Terminstart --}}
                    <li>
                        <div class="relative pb-8">
                            @if(!$record->is_past)
                                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full {{ $record->is_past ? 'bg-gray-400' : ($isActive ? 'bg-green-500' : 'bg-gray-300') }} flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                        <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                    <div>
                                        <p class="text-sm {{ $isActive ? 'text-green-600 font-semibold' : 'text-gray-900' }} dark:text-gray-100">
                                            Terminbeginn
                                        </p>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $formatter->formatDateTime($record->starts_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>

                    {{-- Terminende --}}
                    <li>
                        <div class="relative">
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full {{ $record->is_past ? 'bg-green-500' : 'bg-gray-300' }} flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                        <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-gray-100">
                                            Terminende
                                        </p>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $formatter->formatDateTime($record->ends_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        {{-- System Informationen --}}
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 text-xs text-gray-500 dark:text-gray-400">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <span class="font-medium">ID:</span> #{{ $record->id }}
                </div>
                <div>
                    <span class="font-medium">Quelle:</span> {{ $record->source ?? 'System' }}
                </div>
                <div>
                    <span class="font-medium">Erstellt:</span> {{ $formatter->formatDateTime($record->created_at) }}
                </div>
                <div>
                    <span class="font-medium">Aktualisiert:</span> {{ $formatter->formatDateTime($record->updated_at) }}
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>