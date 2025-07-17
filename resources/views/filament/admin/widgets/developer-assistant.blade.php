<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-code-bracket class="h-5 w-5 text-gray-500" />
                Developer Assistant
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::button
                size="xs"
                wire:click="openDevTools"
                color="gray"
            >
                <x-heroicon-m-wrench-screwdriver class="h-4 w-4 mr-1" />
                Dev Tools
            </x-filament::button>
        </x-slot>

        {{-- Tabs --}}
        <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
            <nav class="-mb-px flex space-x-4">
                <button
                    wire:click="switchTab('suggestions')"
                    class="py-2 px-1 border-b-2 font-medium text-sm transition-colors
                        {{ $selectedTab === 'suggestions' 
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Suggestions
                </button>
                <button
                    wire:click="switchTab('recent')"
                    class="py-2 px-1 border-b-2 font-medium text-sm transition-colors
                        {{ $selectedTab === 'recent' 
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Recent Generations
                </button>
                <button
                    wire:click="switchTab('tools')"
                    class="py-2 px-1 border-b-2 font-medium text-sm transition-colors
                        {{ $selectedTab === 'tools' 
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    Quick Tools
                </button>
            </nav>
        </div>

        {{-- Content --}}
        <div class="space-y-4">
            {{-- Suggestions Tab --}}
            @if($selectedTab === 'suggestions')
                @if(count($suggestions) > 0)
                    <div class="space-y-2">
                        @foreach($suggestions as $suggestion)
                        <div class="flex items-start gap-2 p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800">
                            <div class="flex-shrink-0 mt-0.5">
                                @php
                                    $icon = match($suggestion['priority'] ?? 'medium') {
                                        'high' => 'heroicon-o-exclamation-circle',
                                        'low' => 'heroicon-o-information-circle',
                                        default => 'heroicon-o-light-bulb'
                                    };
                                    $color = match($suggestion['priority'] ?? 'medium') {
                                        'high' => 'text-red-500',
                                        'low' => 'text-blue-500',
                                        default => 'text-yellow-500'
                                    };
                                @endphp
                                <x-dynamic-component :component="$icon" class="h-5 w-5 {{ $color }}" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $suggestion['task'] ?? 'Suggestion' }}
                                </p>
                                @if(isset($suggestion['reason']))
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $suggestion['reason'] }}
                                </p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">No immediate suggestions available.</p>
                @endif
            @endif

            {{-- Recent Generations Tab --}}
            @if($selectedTab === 'recent')
                @if(count($recentGenerations) > 0)
                    <div class="space-y-2">
                        @foreach($recentGenerations as $generation)
                        <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-800">
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-sparkles class="h-4 w-4 text-purple-500" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ ucfirst($generation['type']) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $generation['description'] }}
                                    </p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($generation['timestamp'])->diffForHumans() }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">No recent code generations.</p>
                @endif
            @endif

            {{-- Quick Tools Tab --}}
            @if($selectedTab === 'tools')
                <div class="grid grid-cols-2 gap-2">
                    <x-filament::button
                        wire:click="generateCode"
                        color="primary"
                        size="sm"
                        class="w-full"
                    >
                        <x-heroicon-m-sparkles class="h-4 w-4 mr-1" />
                        Generate Code
                    </x-filament::button>

                    <x-filament::button
                        wire:click="analyzeCode"
                        color="info"
                        size="sm"
                        class="w-full"
                    >
                        <x-heroicon-m-magnifying-glass class="h-4 w-4 mr-1" />
                        Analyze Code
                    </x-filament::button>
                </div>

                <div class="mt-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quick Commands</h4>
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 space-y-1 text-xs font-mono">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Generate service:</span>
                            <code class="text-blue-600 dark:text-blue-400">php artisan dev bp --type=service</code>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Analyze file:</span>
                            <code class="text-blue-600 dark:text-blue-400">php artisan dev analyze --file=</code>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Find similar:</span>
                            <code class="text-blue-600 dark:text-blue-400">php artisan dev similar</code>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Get suggestions:</span>
                            <code class="text-blue-600 dark:text-blue-400">php artisan dev suggest</code>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>