{{--
    Action Items Viewer Component
    Displays AI-identified follow-up actions as a checklist
    Used in: CallResource ViewCall - Analyse Tab

    Accessibility: WCAG 2.1 AA compliant
    - role="list" with aria-label for screen readers
    - aria-hidden="true" on decorative SVGs
    - Filament Badge component for consistent styling
--}}
@php
    $items = $getState();
    if (empty($items)) {
        $items = [];
    }

    // Handle double-encoded JSON (bug fix: some entries stored as "[\"item1\",\"item2\"]")
    if (is_string($items)) {
        $decoded = json_decode($items, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $items = $decoded;
        } else {
            $items = []; // Invalid data, show empty state
        }
    }

    if (!is_array($items)) {
        $items = [];
    }

    // Filter out empty items
    $items = array_filter($items, fn($item) => !empty($item));
@endphp

@if(empty($items))
    {{-- Empty State --}}
    <div class="text-center py-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
            <svg class="w-7 h-7 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
        </div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Keine Aktionspunkte</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Es wurden keine Follow-up-Aktionen identifiziert.</p>
    </div>
@else
    <ul role="list" aria-label="AI-identifizierte Aktionspunkte" class="space-y-2">
        @foreach($items as $index => $item)
            <li class="flex items-start gap-3 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-800">
                {{-- Checkbox Icon --}}
                <div class="flex-shrink-0 mt-0.5">
                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <span class="text-gray-700 dark:text-gray-300">
                        @if(is_array($item))
                            {{-- Handle structured action items --}}
                            @if(isset($item['description']))
                                {{ $item['description'] }}
                            @elseif(isset($item['text']))
                                {{ $item['text'] }}
                            @elseif(isset($item['action']))
                                {{ $item['action'] }}
                            @elseif(isset($item['title']))
                                <strong>{{ $item['title'] }}</strong>
                                @if(isset($item['details']))
                                    <span class="text-sm text-gray-500 dark:text-gray-400 block mt-1">{{ $item['details'] }}</span>
                                @endif
                            @else
                                {{ json_encode($item) }}
                            @endif

                            {{-- Priority Badge using Filament component --}}
                            @if(isset($item['priority']))
                                <x-filament::badge
                                    :color="match(strtolower($item['priority'])) {
                                        'high', 'hoch' => 'danger',
                                        'medium', 'mittel' => 'warning',
                                        'low', 'niedrig' => 'success',
                                        default => 'gray'
                                    }"
                                    size="sm"
                                    class="ml-2"
                                >
                                    {{ ucfirst($item['priority']) }}
                                </x-filament::badge>
                            @endif

                            {{-- Due Date --}}
                            @if(isset($item['due_date']) || isset($item['deadline']))
                                <span class="ml-2 text-xs text-gray-500 dark:text-gray-400 inline-flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ $item['due_date'] ?? $item['deadline'] }}
                                </span>
                            @endif
                        @else
                            {{-- Simple string item --}}
                            {{ $item }}
                        @endif
                    </span>
                </div>

                {{-- Index Number --}}
                <div class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-500 font-mono">
                    #{{ $index + 1 }}
                </div>
            </li>
        @endforeach
    </ul>

    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <span>{{ count($items) }} {{ count($items) === 1 ? 'Aktionspunkt' : 'Aktionspunkte' }} identifiziert</span>
            </div>
            <span class="text-xs text-gray-400">Von AI extrahiert</span>
        </div>
    </div>
@endif
