<x-filament-widgets::widget>
    <x-filament::card>
        <div class="relative">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Gesundheitsscore
                </h2>
                <x-filament::icon-button
                    icon="heroicon-o-information-circle"
                    x-tooltip.raw="Der Gesundheitsscore zeigt die Gesamtperformance Ihres Unternehmens"
                    color="gray"
                    size="sm"
                />
            </div>

            {{-- Score Display --}}
            <div class="text-center mb-6">
                <div class="relative inline-flex items-center justify-center">
                    {{-- Circular Progress --}}
                    <svg class="w-32 h-32 transform -rotate-90">
                        <circle
                            cx="64"
                            cy="64"
                            r="56"
                            stroke="currentColor"
                            stroke-width="8"
                            fill="none"
                            class="text-gray-200 dark:text-gray-700"
                        />
                        <circle
                            cx="64"
                            cy="64"
                            r="56"
                            stroke="currentColor"
                            stroke-width="8"
                            fill="none"
                            stroke-dasharray="{{ 2 * 3.14159 * 56 }}"
                            stroke-dashoffset="{{ 2 * 3.14159 * 56 * (1 - $score / 100) }}"
                            class="text-{{ $status['color'] }}-600 transition-all duration-1000 ease-out"
                        />
                    </svg>
                    
                    {{-- Score Number --}}
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-bold text-gray-900 dark:text-white">
                            {{ $score }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            von 100
                        </span>
                    </div>
                </div>

                {{-- Status Label --}}
                <div class="mt-4 flex items-center justify-center gap-2">
                    <x-dynamic-component
                        :component="'heroicon-o-' . str_replace('heroicon-o-', '', $status['icon'])"
                        class="w-5 h-5 text-{{ $status['color'] }}-600"
                    />
                    <span class="text-sm font-medium text-{{ $status['color'] }}-600">
                        {{ $status['label'] }}
                    </span>
                </div>

                {{-- Trend Indicator --}}
                @if($trend !== 0)
                    <div class="mt-2 flex items-center justify-center gap-1">
                        @if($trend > 0)
                            <x-heroicon-o-arrow-trending-up class="w-4 h-4 text-success-600" />
                            <span class="text-sm text-success-600">+{{ $trend }}%</span>
                        @else
                            <x-heroicon-o-arrow-trending-down class="w-4 h-4 text-danger-600" />
                            <span class="text-sm text-danger-600">{{ $trend }}%</span>
                        @endif
                        <span class="text-xs text-gray-500">vs. letzte Woche</span>
                    </div>
                @endif
            </div>

            {{-- Component Breakdown --}}
            <div class="space-y-3">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Komponenten
                </h3>

                {{-- Conversion Rate --}}
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Konversionsrate</span>
                        <span class="font-medium">{{ round($components['conversion']) }}%</span>
                    </div>
                    <div class="mt-1 w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-primary-600 h-2 rounded-full transition-all duration-500"
                             style="width: {{ $components['conversion'] }}%"></div>
                    </div>
                </div>

                {{-- No-Show Rate (inverted) --}}
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Erscheinungsquote</span>
                        <span class="font-medium">{{ round($components['no_show']) }}%</span>
                    </div>
                    <div class="mt-1 w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-primary-600 h-2 rounded-full transition-all duration-500"
                             style="width: {{ $components['no_show'] }}%"></div>
                    </div>
                </div>

                {{-- Occupancy Rate --}}
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Auslastung</span>
                        <span class="font-medium">{{ round($components['occupancy']) }}%</span>
                    </div>
                    <div class="mt-1 w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-primary-600 h-2 rounded-full transition-all duration-500"
                             style="width: {{ $components['occupancy'] }}%"></div>
                    </div>
                </div>

                {{-- Customer Satisfaction --}}
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Kundenzufriedenheit</span>
                        <span class="font-medium">{{ round($components['satisfaction']) }}%</span>
                    </div>
                    <div class="mt-1 w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-primary-600 h-2 rounded-full transition-all duration-500"
                             style="width: {{ $components['satisfaction'] }}%"></div>
                    </div>
                </div>

                {{-- System Availability --}}
                <div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Systemverfügbarkeit</span>
                        <span class="font-medium">{{ round($components['availability']) }}%</span>
                    </div>
                    <div class="mt-1 w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-primary-600 h-2 rounded-full transition-all duration-500"
                             style="width: {{ $components['availability'] }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Refresh Indicator --}}
            <div class="mt-4 text-center">
                <span class="text-xs text-gray-400">
                    Aktualisiert vor {{ now()->diffInMinutes(cache()->get("health-score-updated-{$companyId}-{$selectedBranchId}", now())) }} Minuten
                </span>
            </div>
        </div>
    </x-filament::card>
</x-filament-widgets::widget>