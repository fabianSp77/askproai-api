<x-filament-panels::page>
    <div class="space-y-6">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Total Components: {{ count($components) }}
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($components as $component)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        {{ $component['name'] }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ $component['category'] }}
                    </p>
                    <div class="mt-3 flex justify-between items-center">
                        @if(isset($component['type']))
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                @if($component['type'] == 'alpine') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($component['type'] == 'livewire') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                @elseif($component['type'] == 'react-converted') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @endif">
                                {{ ucfirst($component['type']) }}
                            </span>
                        @endif
                        <span class="text-xs text-gray-400">{{ $component['size'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
        
        @if(empty($components))
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                No components found
            </div>
        @endif
    </div>
</x-filament-panels::page>
