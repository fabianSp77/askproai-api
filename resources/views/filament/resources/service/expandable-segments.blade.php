@php
    use Illuminate\Support\Str;

    $service = $getRecord();

    if (!$service->composite || empty($service->segments)) {
        return;
    }

    $segments = is_string($service->segments)
        ? json_decode($service->segments, true)
        : $service->segments;

    if (!is_array($segments) || empty($segments)) {
        return;
    }

    // Calculate totals
    $totalActive = 0;
    $totalGaps = 0;

    foreach ($segments as $segment) {
        $totalActive += (int)($segment['durationMin'] ?? $segment['duration'] ?? 0);
        $totalGaps += (int)($segment['gapAfterMin'] ?? $segment['gap_after'] ?? 0);
    }

    $totalTime = $totalActive + $totalGaps;
@endphp

<div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
    <div class="space-y-4">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            {{-- Total Duration --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <div class="text-xs font-medium text-blue-700 dark:text-blue-300">Gesamtdauer</div>
                        <div class="text-lg font-bold text-blue-900 dark:text-blue-100">{{ $totalTime }} min</div>
                    </div>
                </div>
            </div>

            {{-- Active Duration --}}
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <div class="flex-1">
                        <div class="text-xs font-medium text-green-700 dark:text-green-300">Aktive Behandlung</div>
                        <div class="text-lg font-bold text-green-900 dark:text-green-100">{{ $totalActive }} min</div>
                    </div>
                </div>
            </div>

            {{-- Gaps --}}
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1">
                        <div class="text-xs font-medium text-amber-700 dark:text-amber-300">Pausen / Einwirkzeit</div>
                        <div class="text-lg font-bold text-amber-900 dark:text-amber-100">{{ $totalGaps }} min</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Segments Flow --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                Behandlungsablauf ({{ count($segments) }} Schritte)
            </h4>

            <div class="space-y-2">
                @foreach ($segments as $index => $segment)
                    @php
                        $duration = (int)($segment['durationMin'] ?? $segment['duration'] ?? 0);
                        $gap = (int)($segment['gapAfterMin'] ?? $segment['gap_after'] ?? 0);
                        $name = $segment['name'] ?? "Schritt " . ($index + 1);
                        $key = $segment['key'] ?? chr(65 + $index); // A, B, C, D...
                        $isLast = $index === count($segments) - 1;
                    @endphp

                    <div class="relative">
                        {{-- Segment Card --}}
                        <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                            {{-- Key Badge --}}
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 flex items-center justify-center text-white font-bold text-sm shadow-md">
                                    {{ $key }}
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">
                                            {{ $name }}
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ $duration }} min
                                            </span>

                                            @if ($gap > 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-200">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    +{{ $gap }} min Pause
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Step Number --}}
                                    <div class="text-xs font-semibold text-gray-400 dark:text-gray-600">
                                        {{ $index + 1 }}/{{ count($segments) }}
                                    </div>
                                </div>

                                {{-- Progress Bar --}}
                                @if ($totalActive > 0)
                                    @php
                                        $percentage = round(($duration / $totalActive) * 100);
                                    @endphp
                                    <div class="mt-2">
                                        <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-full transition-all" style="width: {{ $percentage }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Arrow Connector --}}
                        @if (!$isLast)
                            <div class="flex justify-center py-1">
                                <svg class="w-6 h-6 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Booking Policy Info --}}
        @if ($service->pause_bookable_policy)
            <div class="bg-gray-100 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="flex-1 text-xs text-gray-700 dark:text-gray-300">
                        <span class="font-medium">Buchungsregel während Pausen:</span>
                        @if ($service->pause_bookable_policy === 'free')
                            <span class="text-green-600 dark:text-green-400">Frei buchbar</span> - Andere Services können parallel gebucht werden
                        @elseif ($service->pause_bookable_policy === 'flexible')
                            <span class="text-amber-600 dark:text-amber-400">Flexibel</span> - Abhängig von Auslastung
                        @else
                            <span class="text-red-600 dark:text-red-400">Reserviert</span> - Zeitfenster während Pausen blockiert
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
