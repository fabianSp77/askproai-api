{{--
    Cost Breakdown Viewer Component
    Displays detailed cost breakdown in a responsive grid format
    Used in: CallResource ViewCall - Kosten & Profit Tab
--}}
@php
    $breakdown = $getState();
    if (empty($breakdown)) return;

    // Handle double-encoded JSON (bug fix: some entries stored as "{\"key\":\"value\"}")
    if (is_string($breakdown)) {
        $decoded = json_decode($breakdown, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $breakdown = $decoded;
        } else {
            return; // Invalid data, skip rendering
        }
    }

    if (!is_array($breakdown)) return;
@endphp

<div role="list" aria-label="Kostenaufschlüsselung" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    @foreach($breakdown as $key => $value)
        <div role="listitem" class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">
                {{ Str::headline(str_replace('_', ' ', $key)) }}
            </div>
            <div class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
                @if(is_numeric($value))
                    @if($value < 1)
                        {{ number_format($value * 100, 2) }} ct
                    @else
                        {{ number_format($value, 4) }} EUR
                    @endif
                @elseif(is_bool($value))
                    <span class="{{ $value ? 'text-green-600' : 'text-red-600' }} inline-flex items-center gap-1">
                        @if($value)
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                        {{ $value ? 'Ja' : 'Nein' }}
                    </span>
                @elseif(is_array($value))
                    <span class="text-sm">{{ json_encode($value) }}</span>
                @else
                    {{ $value }}
                @endif
            </div>
        </div>
    @endforeach
</div>

@if(count($breakdown) > 0)
    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ count($breakdown) }} Kostenkomponenten</span>
        </div>
    </div>
@endif
