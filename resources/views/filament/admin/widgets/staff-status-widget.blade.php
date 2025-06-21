<x-filament-widgets::widget>
    <x-filament::card>
        <div class="space-y-4">
            {{-- Current Status --}}
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</h3>
                <div class="flex items-center gap-2">
                    <x-dynamic-component
                        :component="$status['icon']"
                        class="w-5 h-5 text-{{ $status['color'] }}-500"
                    />
                    <span class="text-sm font-medium text-{{ $status['color'] }}-600 dark:text-{{ $status['color'] }}-400">
                        {{ $status['label'] }}
                    </span>
                </div>
            </div>

            {{-- Working Hours --}}
            @if($workingHours)
                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Arbeitszeiten heute</h3>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-300">
                            {{ $workingHours['start'] }} - {{ $workingHours['end'] }}
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ $workingHours['total'] }}
                        </span>
                    </div>
                </div>
            @endif

            {{-- Break Time --}}
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Pausenzeiten</h3>
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-300">Nächste Pause</span>
                        <span class="font-medium">{{ $breakTime['next'] }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-300">Genommen</span>
                        <span class="font-medium">{{ $breakTime['taken'] }} Min</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-300">Verbleibend</span>
                        <span class="font-medium text-primary-600">{{ $breakTime['remaining'] }} Min</span>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-2 gap-2">
                    <x-filament::button
                        size="sm"
                        color="success"
                        outlined
                        class="w-full"
                    >
                        <x-heroicon-m-play class="w-4 h-4 mr-1" />
                        Starten
                    </x-filament::button>
                    <x-filament::button
                        size="sm"
                        color="warning"
                        outlined
                        class="w-full"
                    >
                        <x-heroicon-m-pause class="w-4 h-4 mr-1" />
                        Pause
                    </x-filament::button>
                </div>
            </div>
        </div>
    </x-filament::card>
</x-filament-widgets::widget>