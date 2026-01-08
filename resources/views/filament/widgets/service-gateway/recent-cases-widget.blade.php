<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Neueste Cases
        </x-slot>

        @php
            $cases = $this->getCases();
            $showCompany = $this->shouldShowCompanyColumn();
        @endphp

        @if($cases->isEmpty())
            {{-- Enhanced empty state with helpful message --}}
            <div class="flex flex-col items-center justify-center p-8 text-center" role="status" aria-live="polite">
                <x-heroicon-o-inbox class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" aria-hidden="true" />
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Keine Cases gefunden
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Versuche einen anderen Zeitraum auszuwählen
                </p>
            </div>
        @else
            <div class="overflow-x-auto -mx-2 md:mx-0">
                <table class="w-full text-sm text-left" role="table" aria-label="Neueste Service Cases">
                    <caption class="sr-only">Liste der neuesten 10 Service Cases mit Status, Priorität und Zuweisungsinformationen</caption>
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-2 py-2 md:px-4 md:py-3" aria-label="Case-Nummer">Case #</th>
                            @if($showCompany)
                                <th scope="col" class="hidden lg:table-cell px-2 py-2 md:px-4 md:py-3" aria-label="Unternehmensname">Unternehmen</th>
                            @endif
                            <th scope="col" class="px-2 py-2 md:px-4 md:py-3" aria-label="Kurzbeschreibung">Beschreibung</th>
                            <th scope="col" class="px-2 py-2 md:px-4 md:py-3" aria-label="Bearbeitungsstatus">Status</th>
                            <th scope="col" class="hidden sm:table-cell px-2 py-2 md:px-4 md:py-3" aria-label="Prioritätsstufe">Priorität</th>
                            <th scope="col" class="hidden lg:table-cell px-2 py-2 md:px-4 md:py-3" aria-label="Servicekategorie">Kategorie</th>
                            <th scope="col" class="hidden xl:table-cell px-2 py-2 md:px-4 md:py-3" aria-label="Zuständiger Bearbeiter">Zugewiesen an</th>
                            <th scope="col" class="hidden md:table-cell px-2 py-2 md:px-4 md:py-3" aria-label="Erstellungsdatum">Erstellt</th>
                            <th scope="col" class="px-2 py-2 md:px-4 md:py-3"><span class="sr-only">Aktionen</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($cases as $case)
                            <tr class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                {{-- Case Number --}}
                                <td class="px-2 py-2 md:px-4 md:py-3">
                                    <a href="{{ $this->getCaseUrl($case) }}"
                                       class="font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                        {{ $case->case_number }}
                                    </a>
                                </td>

                                {{-- Company (conditional) - Hidden on mobile/tablet --}}
                                @if($showCompany)
                                    <td class="hidden lg:table-cell px-2 py-2 md:px-4 md:py-3 text-gray-600 dark:text-gray-400">
                                        {{ Str::limit($case->company?->name ?? '—', 15) }}
                                    </td>
                                @endif

                                {{-- Description - Responsive max-width --}}
                                <td class="px-2 py-2 md:px-4 md:py-3 text-gray-900 dark:text-white max-w-[120px] sm:max-w-[180px] md:max-w-xs truncate"
                                    title="{{ $case->short_description }}">
                                    {{ Str::limit($case->short_description, 30) }}
                                </td>

                                {{-- Status Badge --}}
                                <td class="px-2 py-2 md:px-4 md:py-3">
                                    <x-filament::badge
                                        :color="$this->getStatusColor($case->status)"
                                        size="sm"
                                    >
                                        {{ $this->getStatusLabel($case->status) }}
                                    </x-filament::badge>
                                </td>

                                {{-- Priority Badge - Hidden on smallest screens --}}
                                <td class="hidden sm:table-cell px-2 py-2 md:px-4 md:py-3">
                                    <x-filament::badge
                                        :color="$this->getPriorityColor($case->priority)"
                                        size="sm"
                                    >
                                        {{ $this->getPriorityLabel($case->priority) }}
                                    </x-filament::badge>
                                </td>

                                {{-- Category - Hidden on mobile/tablet --}}
                                <td class="hidden lg:table-cell px-2 py-2 md:px-4 md:py-3 text-gray-600 dark:text-gray-400">
                                    {{ Str::limit($case->category?->name ?? '—', 20) }}
                                </td>

                                {{-- Assigned To - Hidden until xl --}}
                                <td class="hidden xl:table-cell px-2 py-2 md:px-4 md:py-3 {{ $this->hasDirectAssignment($case) ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $this->getAssignedToText($case) }}
                                </td>

                                {{-- Created At - Hidden on mobile --}}
                                <td class="hidden md:table-cell px-2 py-2 md:px-4 md:py-3 text-gray-600 dark:text-gray-400"
                                    title="{{ $case->created_at->format('d.m.Y H:i') }}">
                                    {{ $case->created_at->diffForHumans() }}
                                </td>

                                {{-- Actions - Icon only on mobile --}}
                                <td class="px-2 py-2 md:px-4 md:py-3">
                                    <a href="{{ $this->getCaseUrl($case) }}"
                                       aria-label="Case {{ $case->case_number }} öffnen"
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
