<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Aktivitätsverlauf
        </x-slot>

        <x-slot name="description">
            Chronologische Übersicht aller Interaktionen
        </x-slot>

        <x-slot name="headerEnd">
            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-1.5">
                    <span class="font-medium">{{ $stats['total'] }}</span>
                    <span>Gesamt</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="font-medium">{{ $stats['last_7_days'] }}</span>
                    <span>Letzte 7 Tage</span>
                </div>
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- Filter Tabs --}}
            <div class="flex flex-wrap gap-2">
                <button onclick="filterTimeline('all')"
                        class="timeline-filter active px-3 py-1.5 text-sm font-medium rounded-lg transition-colors bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300">
                    Alle ({{ $stats['total'] }})
                </button>
                <button onclick="filterTimeline('call')"
                        class="timeline-filter px-3 py-1.5 text-sm font-medium rounded-lg transition-colors bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">
                    📞 Anrufe ({{ $stats['calls'] }})
                </button>
                <button onclick="filterTimeline('appointment')"
                        class="timeline-filter px-3 py-1.5 text-sm font-medium rounded-lg transition-colors bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">
                    📅 Termine ({{ $stats['appointments'] }})
                </button>
                @if($stats['notes'] > 0)
                <button onclick="filterTimeline('note')"
                        class="timeline-filter px-3 py-1.5 text-sm font-medium rounded-lg transition-colors bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">
                    📝 Notizen ({{ $stats['notes'] }})
                </button>
                @endif
            </div>

            {{-- Timeline --}}
            <div class="relative space-y-4 max-h-[600px] overflow-y-auto pr-2">
                @php
                    $lastDate = null;
                @endphp

                @forelse($activities as $activity)
                    @php
                        $timestamp = \Carbon\Carbon::parse($activity['timestamp']);
                        $currentDate = $timestamp->format('Y-m-d');
                        $showDateDivider = $lastDate !== $currentDate;
                        $lastDate = $currentDate;

                        $dateLabel = match(true) {
                            $timestamp->isToday() => 'Heute',
                            $timestamp->isYesterday() => 'Gestern',
                            $timestamp->diffInDays(now()) < 7 => $timestamp->diffForHumans(),
                            default => $timestamp->format('d.m.Y'),
                        };
                    @endphp

                    {{-- Date Divider --}}
                    @if($showDateDivider)
                        <div class="flex items-center gap-3 py-2">
                            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                {{ $dateLabel }}
                            </div>
                            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                    @endif

                    {{-- Timeline Item --}}
                    <div class="timeline-item" data-type="{{ $activity['type'] }}">
                        <div class="flex gap-4">
                            {{-- Icon & Timeline Line --}}
                            <div class="flex flex-col items-center">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-lg
                                    @if($activity['color'] === 'success') bg-success-100 dark:bg-success-900/30
                                    @elseif($activity['color'] === 'warning') bg-warning-100 dark:bg-warning-900/30
                                    @elseif($activity['color'] === 'danger') bg-danger-100 dark:bg-danger-900/30
                                    @elseif($activity['color'] === 'info') bg-info-100 dark:bg-info-900/30
                                    @elseif($activity['color'] === 'primary') bg-primary-100 dark:bg-primary-900/30
                                    @else bg-gray-100 dark:bg-gray-800
                                    @endif">
                                    {{ $activity['icon'] }}
                                </div>
                                @if(!$loop->last)
                                    <div class="w-px flex-1 bg-gray-200 dark:bg-gray-700 min-h-[20px] my-2"></div>
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 pb-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $activity['title'] }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $activity['description'] }}
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ $timestamp->format('H:i') }}
                                    </div>
                                </div>

                                {{-- Call-specific Actions --}}
                                @if($activity['type'] === 'call')
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        @if($activity['is_failed_booking'])
                                            <a href="{{ route('filament.admin.resources.appointments.create', ['customer_id' => $customer_id, 'call_id' => $activity['data']['id']]) }}"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-warning-100 dark:bg-warning-900/30 text-warning-700 dark:text-warning-300 hover:bg-warning-200 dark:hover:bg-warning-900/50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                </svg>
                                                Termin nachbuchen
                                            </a>
                                        @endif

                                        @if($activity['has_transcript'])
                                            <button onclick="openTranscriptModal({{ $activity['data']['id'] }})"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-info-100 dark:bg-info-900/30 text-info-700 dark:text-info-300 hover:bg-info-200 dark:hover:bg-info-900/50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                Transcript
                                            </button>
                                        @endif

                                        @if($activity['has_recording'])
                                            <a href="{{ $activity['data']['recording_url'] }}" target="_blank"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 hover:bg-primary-200 dark:hover:bg-primary-900/50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Aufnahme
                                            </a>
                                        @endif

                                        <a href="{{ route('filament.admin.resources.calls.edit', $activity['data']['id']) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                            Details anzeigen
                                        </a>
                                    </div>
                                @endif

                                {{-- Appointment-specific Actions --}}
                                @if($activity['type'] === 'appointment')
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        @if($activity['is_upcoming'])
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-success-100 dark:bg-success-900/30 text-success-700 dark:text-success-300">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                {{ $timestamp->diffForHumans() }}
                                            </span>
                                        @endif

                                        <a href="{{ route('filament.admin.resources.appointments.edit', $activity['data']['id']) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                                            Details anzeigen
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                        <div class="text-5xl mb-4">📭</div>
                        <div class="font-medium">Noch keine Aktivitäten</div>
                        <div class="text-sm mt-1">Aktivitäten werden hier chronologisch angezeigt</div>
                    </div>
                @endforelse
            </div>
        </div>
    </x-filament::section>

    <script>
        // Define functions in global scope for inline onclick handlers
        // Use OR operator to allow redefinition if needed (fixes Livewire re-mount issues)
        window.filterTimeline = window.filterTimeline || function(type) {
            // Update button states
            document.querySelectorAll('.timeline-filter').forEach(btn => {
                btn.classList.remove('active', 'bg-primary-100', 'dark:bg-primary-900', 'text-primary-700', 'dark:text-primary-300');
                btn.classList.add('bg-gray-100', 'dark:bg-gray-800', 'text-gray-700', 'dark:text-gray-300');
            });

            event.target.classList.remove('bg-gray-100', 'dark:bg-gray-800', 'text-gray-700', 'dark:text-gray-300');
            event.target.classList.add('active', 'bg-primary-100', 'dark:bg-primary-900', 'text-primary-700', 'dark:text-primary-300');

            // Filter timeline items
            document.querySelectorAll('.timeline-item').forEach(item => {
                if (type === 'all' || item.dataset.type === type) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        };

        window.openTranscriptModal = window.openTranscriptModal || function(callId) {
            // This would trigger Filament's modal system
            // Implementation depends on how Filament handles modals
            console.log('Opening transcript for call:', callId);
            // Alternative: navigate to calls table and open that row's transcript action
            window.location.href = '#calls-relation';
        };
    </script>
</x-filament-widgets::widget>
