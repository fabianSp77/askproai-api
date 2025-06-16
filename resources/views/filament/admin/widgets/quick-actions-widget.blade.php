<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Schnellzugriffe
        </x-slot>
        
        <x-slot name="description">
            Häufig verwendete Aktionen für einen schnellen Zugriff
        </x-slot>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach($this->getQuickActions() as $action)
                <a 
                    href="{{ $action['url'] }}" 
                    class="group relative rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg transition-all duration-200 hover:scale-105 hover:border-{{ $action['color'] }}-500 dark:hover:border-{{ $action['color'] }}-400"
                >
                    <div class="flex flex-col items-center text-center space-y-2">
                        <div class="p-3 rounded-full bg-{{ $action['color'] }}-100 dark:bg-{{ $action['color'] }}-900/20 text-{{ $action['color'] }}-600 dark:text-{{ $action['color'] }}-400 group-hover:bg-{{ $action['color'] }}-200 dark:group-hover:bg-{{ $action['color'] }}-900/40 transition-colors">
                            <x-dynamic-component 
                                :component="$action['icon']" 
                                class="w-6 h-6"
                            />
                        </div>
                        
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-{{ $action['color'] }}-600 dark:group-hover:text-{{ $action['color'] }}-400">
                                {{ $action['label'] }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $action['description'] }}
                            </p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>