<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-book-open class="w-5 h-5"/>
                <span>📖 Termin-Lebenslauf</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Chronologische Geschichte dieses Termins von Erstellung bis heute
        </x-slot>

        <div class="space-y-4">
            @php
                // ERROR HANDLING: Wrap in try-catch for robustness
                try {
                    $timelineData = $this->getTimelineData();
                } catch (\Exception $e) {
                    $timelineData = [];
                    \Log::error('Timeline Widget Error', [
                        'error' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'appointment_id' => $this->record->id ?? 'unknown'
                    ]);
                }
            @endphp

            @if(empty($timelineData))
                <div class="text-gray-700 dark:text-gray-300 text-center py-8">
                    <x-heroicon-o-information-circle class="w-12 h-12 mx-auto mb-2 text-gray-600 dark:text-gray-300"/>
                    <p>Keine Historie verfügbar</p>
                </div>
            @else
                <div class="relative">
                    {{-- Timeline vertical line --}}
                    <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                    {{-- Timeline events --}}
                    <div class="space-y-6">
                        @foreach($timelineData as $index => $event)
                            <div class="relative pl-14">
                                {{-- Timeline dot with icon --}}
                                <div class="absolute left-0 flex items-center justify-center w-12 h-12 rounded-full
                                    @if($event['color'] === 'success') bg-success-100 text-success-700 dark:bg-success-700 dark:text-success-100
                                    @elseif($event['color'] === 'info') bg-info-100 text-info-700 dark:bg-info-700 dark:text-info-100
                                    @elseif($event['color'] === 'warning') bg-warning-100 text-warning-700 dark:bg-warning-700 dark:text-warning-100
                                    @elseif($event['color'] === 'danger') bg-danger-100 text-danger-700 dark:bg-danger-700 dark:text-danger-100
                                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-100
                                    @endif">
                                    <x-dynamic-component :component="$event['icon']" class="w-6 h-6"/>
                                </div>

                                {{-- Event card --}}
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                                    {{-- Header: Icon + Type Badge + Timestamp (NO title duplication) --}}
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex items-start gap-3">
                                            {{-- Large colored icon --}}
                                            @php
                                                // FIX 2025-10-11: Pre-compute classes to avoid Blade parser issues with multi-line conditionals
                                                $iconBgClass = match($event['type']) {
                                                    'created', 'create' => 'bg-success-100 dark:bg-success-900/30',
                                                    'rescheduled', 'reschedule' => 'bg-info-100 dark:bg-info-900/30',
                                                    'cancelled', 'cancel' => 'bg-danger-100 dark:bg-danger-900/30',
                                                    default => 'bg-gray-100 dark:bg-gray-900/30',
                                                };
                                                $iconColorClass = match($event['type']) {
                                                    'created', 'create' => 'text-success-600',
                                                    'rescheduled', 'reschedule' => 'text-info-600',
                                                    'cancelled', 'cancel' => 'text-danger-600',
                                                    default => 'text-gray-600',
                                                };
                                            @endphp
                                            <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $iconBgClass }}">
                                                <x-dynamic-component
                                                    :component="$event['icon']"
                                                    class="w-5 h-5 {{ $iconColorClass }}"
                                                />
                                            </div>

                                            {{-- Title + Timestamp --}}
                                            <div>
                                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ $event['title'] }}
                                                </h4>
                                                <p class="text-xs text-gray-700 dark:text-gray-300 mt-0.5">
                                                    <x-heroicon-o-clock class="w-3 h-3 inline mr-1"/>
                                                    @php
                                                        // FIX 2025-10-11: NULL safety for timestamp parsing
                                                        try {
                                                            $formattedTime = $event['timestamp']
                                                                ? \Carbon\Carbon::parse($event['timestamp'])->format('d.m.Y H:i')
                                                                : 'Unbekannt';
                                                        } catch (\Exception $e) {
                                                            $formattedTime = 'Ungültiges Datum';
                                                        }
                                                    @endphp
                                                    {{ $formattedTime }} Uhr
                                                </p>
                                            </div>
                                        </div>

                                        {{-- REMOVED: Duplicate type badge - already in title --}}
                                    </div>

                                    {{-- Description (allow HTML for formatting) --}}
                                    <div class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                        {!! $event['description'] !!}
                                    </div>

                                    {{-- Footer: Actor + Call Link --}}
                                    <div class="flex items-center justify-between text-xs text-gray-700 dark:text-gray-300 pt-3 border-t border-gray-100 dark:border-gray-700">
                                        <div class="flex items-center gap-4">
                                            {{-- Actor --}}
                                            <div class="flex items-center gap-1">
                                                <x-heroicon-o-user class="w-3 h-3"/>
                                                <span>{{ $event['actor'] }}</span>
                                            </div>

                                            {{-- Call link (if exists) --}}
                                            @if(isset($event['call_id']) && $event['call_id'])
                                                <div>
                                                    {!! $this->getCallLink($event['call_id']) !!}
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Metadata badges (fees only - policy removed as redundant with click-to-expand) --}}
                                        @if(isset($event['metadata']))
                                            <div class="flex items-center gap-2">
                                                {{-- REMOVED: Redundant policy badge (already have "📋 Richtliniendetails anzeigen" below) --}}

                                                @if(isset($event['metadata']['fee_charged']) && $event['metadata']['fee_charged'] > 0)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-danger-50 text-danger-700">
                                                        💰 Gebühr: {{ number_format($event['metadata']['fee_charged'], 2) }} €
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Policy Details (Click to expand) --}}
                                    @if(isset($event['metadata']['within_policy']))
                                        @php
                                            // FIX 2025-10-11: Pre-compute policy tooltip OUTSIDE <details> to prevent lazy-loading errors
                                            // When user scrolls, Livewire may re-render and lose $this context inside <details>
                                            $policyTooltip = $this->getPolicyTooltip($event) ?? '';
                                            $policyLines = explode("\n", $policyTooltip);
                                        @endphp
                                        <details class="mt-3 text-xs">
                                            <summary class="cursor-pointer text-primary-600 hover:text-primary-800 dark:text-primary-400 font-medium">
                                                📋 Richtliniendetails anzeigen
                                            </summary>
                                            <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-900 rounded space-y-1.5">
                                                @foreach($policyLines as $line)
                                                    @if(trim($line))
                                                        <div class="text-gray-900 dark:text-white">{{ $line }}</div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif

                                    {{-- Expandable metadata details (for debugging/admin) --}}
                                    @if(isset($event['metadata']['details']) && !empty($event['metadata']['details']))
                                        <details class="mt-3 text-xs">
                                            <summary class="cursor-pointer text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100">
                                                Technische Details anzeigen
                                            </summary>
                                            <pre class="mt-2 p-2 bg-gray-50 dark:bg-gray-900 rounded text-xs overflow-auto">{{ json_encode($event['metadata']['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Summary footer --}}
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>
                                <strong>{{ count($timelineData) }}</strong> Ereignisse insgesamt (inkl. Erstellung)
                            </span>
                            <span>
                                Erstellt: {{ $this->record->created_at->format('d.m.Y H:i') }} Uhr
                            </span>
                        </div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 italic">
                            ℹ️ Für erweiterte Filter und Datenexport siehe Tab "Änderungs-Audit" oben
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
