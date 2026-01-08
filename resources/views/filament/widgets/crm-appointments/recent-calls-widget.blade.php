<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Neueste Anrufe
        </x-slot>

        @php
            $calls = $this->getCalls();
            $showCompany = $this->shouldShowCompanyColumn();
        @endphp

        @if($calls->isEmpty())
            {{-- Enhanced empty state with helpful message --}}
            <div class="flex flex-col items-center justify-center p-8 text-center" role="status" aria-live="polite">
                <x-heroicon-o-phone class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" aria-hidden="true" />
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Keine Anrufe gefunden
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Versuche einen anderen Zeitraum auszuwählen
                </p>
            </div>
        @else
            <div class="overflow-x-auto -mx-2 md:mx-0">
                <table class="w-full text-sm text-left" role="table" aria-label="Neueste Anrufe">
                    <caption class="sr-only">Liste der letzten 10 Anrufe mit Status, Dauer und Termininformationen</caption>
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-2 py-2 md:px-4 md:py-3" aria-label="Anrufzeitpunkt">Zeit</th>
                            <th scope="col" class="px-2 py-2 md:px-4 md:py-3" aria-label="Kundenname">Kunde</th>
                            <th scope="col" class="hidden sm:table-cell px-2 py-2 md:px-4 md:py-3" aria-label="Anrufdauer">Dauer</th>
                            <th scope="col" class="px-2 py-2 md:px-4 md:py-3" aria-label="Anrufstatus">Status</th>
                            <th scope="col" class="hidden md:table-cell px-2 py-2 md:px-4 md:py-3" aria-label="Terminvereinbarung">Termin</th>
                            @if($showCompany)
                                <th scope="col" class="hidden lg:table-cell px-2 py-2 md:px-4 md:py-3" aria-label="Unternehmensname">Unternehmen</th>
                            @endif
                            <th scope="col" class="hidden xl:table-cell px-2 py-2 md:px-4 md:py-3" aria-label="Agent-Name">Agent</th>
                            <th scope="col" class="px-2 py-2 md:px-4 md:py-3"><span class="sr-only">Aktionen</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($calls as $call)
                            <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 {{ $this->hasAppointment($call) ? 'bg-green-50/30 dark:bg-green-900/10' : '' }}">
                                {{-- Time (relative) --}}
                                <td class="px-2 py-2 md:px-4 md:py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap"
                                    title="{{ $call->created_at->format('d.m.Y H:i:s') }}">
                                    {{ $call->created_at->diffForHumans() }}
                                </td>

                                {{-- Customer Name --}}
                                <td class="px-2 py-2 md:px-4 md:py-3 text-gray-900 dark:text-white max-w-[100px] sm:max-w-[150px] truncate"
                                    title="{{ $this->getCustomerName($call) }}">
                                    {{ Str::limit($this->getCustomerName($call), 20) }}
                                </td>

                                {{-- Duration - Hidden on smallest screens --}}
                                <td class="hidden sm:table-cell px-2 py-2 md:px-4 md:py-3 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    {{ $this->formatDuration($call->duration_sec) }}
                                </td>

                                {{-- Status Badge --}}
                                <td class="px-2 py-2 md:px-4 md:py-3">
                                    <x-filament::badge
                                        :color="$this->getStatusColor($call->status)"
                                        size="sm"
                                    >
                                        {{ $this->getStatusLabel($call->status) }}
                                    </x-filament::badge>
                                </td>

                                {{-- Appointment Indicator - Hidden on mobile --}}
                                <td class="hidden md:table-cell px-2 py-2 md:px-4 md:py-3 text-center">
                                    @if($this->hasAppointment($call))
                                        <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400" role="status">
                                            <x-heroicon-s-calendar-days class="w-4 h-4" aria-hidden="true" />
                                            <span class="hidden lg:inline text-xs">Termin</span>
                                            <span class="sr-only">Termin vereinbart</span>
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500" aria-label="Kein Termin">—</span>
                                    @endif
                                </td>

                                {{-- Company (conditional) - Hidden on mobile/tablet --}}
                                @if($showCompany)
                                    <td class="hidden lg:table-cell px-2 py-2 md:px-4 md:py-3 text-gray-600 dark:text-gray-400 max-w-[100px] truncate">
                                        {{ Str::limit($call->company?->name ?? '—', 15) }}
                                    </td>
                                @endif

                                {{-- Agent - Hidden until xl --}}
                                <td class="hidden xl:table-cell px-2 py-2 md:px-4 md:py-3 text-gray-600 dark:text-gray-400 max-w-[100px] truncate">
                                    {{ Str::limit($this->getAgentName($call), 15) }}
                                </td>

                                {{-- Actions --}}
                                <td class="px-2 py-2 md:px-4 md:py-3">
                                    <a href="{{ $this->getCallUrl($call) }}"
                                       aria-label="Anruf von {{ $this->getCustomerName($call) }} öffnen"
                                       class="inline-flex items-center justify-center gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.75rem] min-w-[2.75rem] px-2 md:px-3 py-2 text-sm text-gray-700 bg-white border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                                        <x-heroicon-m-eye class="w-4 h-4" aria-hidden="true" />
                                        <span class="hidden sm:inline">Öffnen</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
