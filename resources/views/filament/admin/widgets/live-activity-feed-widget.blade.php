<x-filament-widgets::widget>
    <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm">
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    Live Feed
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                </h3>
                <span class="text-xs text-gray-500">Auto-Update: 10s</span>
            </div>
        </div>
        
        {{-- Activity List --}}
        <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-96 overflow-y-auto">
            @forelse($activities as $activity)
                <div class="px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="flex items-start gap-3">
                        {{-- Icon --}}
                        <div class="flex-shrink-0 mt-0.5">
                            <div class="p-1.5 rounded-lg bg-{{ $activity['color'] }}-100 dark:bg-{{ $activity['color'] }}-900/20">
                                <x-dynamic-component 
                                    :component="$activity['icon']" 
                                    class="w-4 h-4 text-{{ $activity['color'] }}-600 dark:text-{{ $activity['color'] }}-400"
                                />
                            </div>
                        </div>
                        
                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 dark:text-white">
                                {{ $activity['message'] }}
                            </p>
                        </div>
                        
                        {{-- Time --}}
                        <div class="flex-shrink-0">
                            <span class="text-xs text-gray-500">
                                {{ $activity['time'] }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <x-heroicon-o-inbox class="mx-auto h-12 w-12 text-gray-400" />
                    <p class="mt-2 text-sm text-gray-500">
                        Keine aktuellen Aktivitäten
                    </p>
                </div>
            @endforelse
        </div>
        
        {{-- Footer with Quick Stats --}}
        @if($hasActivities)
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                    <span>{{ $activities->count() }} Aktivitäten in den letzten 30 Minuten</span>
                    <button 
                        wire:click="$refresh"
                        class="text-primary-600 hover:text-primary-700 font-medium"
                    >
                        Jetzt aktualisieren
                    </button>
                </div>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>