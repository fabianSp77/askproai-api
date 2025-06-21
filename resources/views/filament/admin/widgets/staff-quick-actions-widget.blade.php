<x-filament-widgets::widget>
    <x-filament::card>
        <div class="relative">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Schnellzugriff
            </h2>
            
            <div class="grid grid-cols-1 gap-3">
                @foreach($actions as $action)
                    <a 
                        href="{{ $action['url'] }}"
                        class="group relative flex items-start gap-3 p-3 rounded-lg transition-all hover:bg-gray-50 dark:hover:bg-gray-800 hover:shadow-sm border border-transparent hover:border-gray-200 dark:hover:border-gray-700"
                    >
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-{{ $action['color'] }}-100 dark:bg-{{ $action['color'] }}-900/20 group-hover:bg-{{ $action['color'] }}-200 dark:group-hover:bg-{{ $action['color'] }}-900/30 transition-colors">
                                <x-dynamic-component
                                    :component="$action['icon']"
                                    class="w-5 h-5 text-{{ $action['color'] }}-600 dark:text-{{ $action['color'] }}-400"
                                />
                            </div>
                        </div>
                        
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-{{ $action['color'] }}-600 dark:group-hover:text-{{ $action['color'] }}-400 transition-colors">
                                {{ $action['label'] }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $action['description'] }}
                            </p>
                        </div>
                        
                        <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                            <x-heroicon-o-arrow-right class="w-5 h-5 text-gray-400" />
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </x-filament::card>
</x-filament-widgets::widget>