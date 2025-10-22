<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Customer Journey
        </x-slot>

        <x-slot name="description">
            Aktueller Status und nächste Schritte
        </x-slot>

        <div class="space-y-6">
            {{-- Journey Progress Bar --}}
            @if (!$isNegativePath)
            <div class="relative">
                {{-- Progress Line --}}
                <div class="absolute top-8 left-0 w-full h-1 bg-gray-200 dark:bg-gray-700"></div>
                <div class="absolute top-8 left-0 h-1 bg-primary-500 transition-all duration-500"
                     style="width: {{ ($currentStageInfo['order'] ?? 1) / 6 * 100 }}%"></div>

                {{-- Stages --}}
                <div class="relative flex justify-between">
                    @foreach ($journeyStages as $stageKey => $stage)
                    <div class="flex flex-col items-center" style="flex: 1">
                        {{-- Stage Icon --}}
                        <div class="w-16 h-16 rounded-full flex items-center justify-center text-2xl mb-2 z-10 transition-all
                            @if ($stageKey === $currentStage)
                                bg-primary-500 text-white ring-4 ring-primary-200 dark:ring-primary-800 scale-110
                            @elseif (($stage['order'] ?? 0) < ($currentStageInfo['order'] ?? 0))
                                bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400
                            @else
                                bg-gray-100 dark:bg-gray-800 text-gray-400
                            @endif">
                            {{ $stage['icon'] }}
                        </div>

                        {{-- Stage Label --}}
                        <div class="text-center">
                            <div class="font-semibold text-sm
                                @if ($stageKey === $currentStage) text-primary-600 dark:text-primary-400 @endif">
                                {{ $stage['label'] }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $stage['description'] }}
                            </div>
                        </div>

                        {{-- Current Indicator --}}
                        @if ($stageKey === $currentStage)
                        <div class="mt-2 px-3 py-1 bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 rounded-full text-xs font-medium">
                            Aktuell
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            {{-- Negative Path Display --}}
            <div class="rounded-lg border-2 border-{{ $currentStage === 'churned' ? 'gray' : 'danger' }}-300 dark:border-{{ $currentStage === 'churned' ? 'gray' : 'danger' }}-700 p-6 bg-{{ $currentStage === 'churned' ? 'gray' : 'danger' }}-50 dark:bg-{{ $currentStage === 'churned' ? 'gray' : 'danger' }}-900/20">
                <div class="flex items-center gap-4">
                    <div class="text-5xl">{{ $currentStageInfo['icon'] }}</div>
                    <div class="flex-1">
                        <div class="text-xl font-bold text-{{ $currentStage === 'churned' ? 'gray' : 'danger' }}-800 dark:text-{{ $currentStage === 'churned' ? 'gray' : 'danger' }}-200">
                            {{ $currentStageInfo['label'] }}
                        </div>
                        <div class="text-sm text-{{ $currentStage === 'churned' ? 'gray' : 'danger' }}-600 dark:text-{{ $currentStage === 'churned' ? 'gray' : 'danger' }}-400 mt-1">
                            {{ $currentStageInfo['description'] }}
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Next Steps --}}
            @if (count($nextSteps) > 0)
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    Nächste Schritte
                </h3>

                <div class="space-y-3">
                    @foreach ($nextSteps as $step)
                    <div class="flex items-start gap-3 p-3 rounded-lg
                        @if ($step['priority'] === 'critical') bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800
                        @elseif ($step['priority'] === 'high') bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-800
                        @elseif ($step['priority'] === 'medium') bg-info-50 dark:bg-info-900/20 border border-info-200 dark:border-info-800
                        @else bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                        @endif">
                        <div class="text-2xl">{{ $step['icon'] }}</div>
                        <div class="flex-1">
                            <div class="font-medium
                                @if ($step['priority'] === 'critical') text-danger-800 dark:text-danger-200
                                @elseif ($step['priority'] === 'high') text-warning-800 dark:text-warning-200
                                @elseif ($step['priority'] === 'medium') text-info-800 dark:text-info-200
                                @else text-gray-800 dark:text-gray-200
                                @endif">
                                {{ $step['text'] }}
                            </div>
                            <div class="text-xs mt-1 font-medium
                                @if ($step['priority'] === 'critical') text-danger-600 dark:text-danger-400
                                @elseif ($step['priority'] === 'high') text-warning-600 dark:text-warning-400
                                @elseif ($step['priority'] === 'medium') text-info-600 dark:text-info-400
                                @else text-gray-600 dark:text-gray-400
                                @endif">
                                Priorität: {{ ucfirst($step['priority']) }}
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Journey History --}}
            @if (count($journeyHistory) > 1)
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Änderungsverlauf
                </h3>

                <div class="space-y-2">
                    @foreach (array_reverse($journeyHistory) as $history)
                    <div class="flex items-center gap-3 text-sm">
                        <div class="text-gray-500 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($history['changed_at'])->format('d.m.Y H:i') }}
                        </div>
                        <div class="flex-1">
                            @if ($history['from'])
                            <span class="text-gray-600 dark:text-gray-400">{{ $history['from'] }}</span>
                            <span class="text-gray-400 dark:text-gray-600">→</span>
                            @endif
                            <span class="font-medium">{{ $history['to'] }}</span>
                            @if (!empty($history['note']))
                            <span class="text-gray-500 dark:text-gray-400 italic ml-2">{{ $history['note'] }}</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
