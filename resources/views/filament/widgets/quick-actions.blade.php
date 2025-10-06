<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-bolt class="w-5 h-5 text-primary-500" />
                <span>{{ $heading }}</span>
            </div>
        </x-slot>

        <x-slot name="description">
            Häufig verwendete Aktionen für schnellen Zugriff
        </x-slot>

        @if(empty($actions))
            <div class="text-center text-gray-500 py-8">
                <p>Keine Schnellaktionen verfügbar</p>
            </div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @foreach ($actions as $action)
                    @php
                        // Safe color mapping to avoid dynamic Tailwind classes
                        $colorClasses = [
                            'success' => [
                                'bg' => 'bg-success-100 dark:bg-success-900/20',
                                'hover' => 'hover:bg-success-200 dark:hover:bg-success-900/30',
                                'text' => 'text-success-600 dark:text-success-400',
                                'hover_text' => 'hover:text-success-600 dark:hover:text-success-400',
                                'border' => 'hover:border-success-500',
                                'badge' => 'bg-success-500'
                            ],
                            'primary' => [
                                'bg' => 'bg-primary-100 dark:bg-primary-900/20',
                                'hover' => 'hover:bg-primary-200 dark:hover:bg-primary-900/30',
                                'text' => 'text-primary-600 dark:text-primary-400',
                                'hover_text' => 'hover:text-primary-600 dark:hover:text-primary-400',
                                'border' => 'hover:border-primary-500',
                                'badge' => 'bg-primary-500'
                            ],
                            'info' => [
                                'bg' => 'bg-info-100 dark:bg-info-900/20',
                                'hover' => 'hover:bg-info-200 dark:hover:bg-info-900/30',
                                'text' => 'text-info-600 dark:text-info-400',
                                'hover_text' => 'hover:text-info-600 dark:hover:text-info-400',
                                'border' => 'hover:border-info-500',
                                'badge' => 'bg-info-500'
                            ],
                            'warning' => [
                                'bg' => 'bg-warning-100 dark:bg-warning-900/20',
                                'hover' => 'hover:bg-warning-200 dark:hover:bg-warning-900/30',
                                'text' => 'text-warning-600 dark:text-warning-400',
                                'hover_text' => 'hover:text-warning-600 dark:hover:text-warning-400',
                                'border' => 'hover:border-warning-500',
                                'badge' => 'bg-warning-500'
                            ],
                            'purple' => [
                                'bg' => 'bg-purple-100 dark:bg-purple-900/20',
                                'hover' => 'hover:bg-purple-200 dark:hover:bg-purple-900/30',
                                'text' => 'text-purple-600 dark:text-purple-400',
                                'hover_text' => 'hover:text-purple-600 dark:hover:text-purple-400',
                                'border' => 'hover:border-purple-500',
                                'badge' => 'bg-purple-500'
                            ],
                            'gray' => [
                                'bg' => 'bg-gray-100 dark:bg-gray-900/20',
                                'hover' => 'hover:bg-gray-200 dark:hover:bg-gray-900/30',
                                'text' => 'text-gray-600 dark:text-gray-400',
                                'hover_text' => 'hover:text-gray-600 dark:hover:text-gray-400',
                                'border' => 'hover:border-gray-500',
                                'badge' => 'bg-gray-500'
                            ],
                        ];

                        $color = $action['color'] ?? 'gray';
                        $classes = $colorClasses[$color] ?? $colorClasses['gray'];
                    @endphp

                    <a
                        href="{{ $action['url'] ?? '#' }}"
                        class="group relative flex flex-col items-center justify-center p-4 text-center rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 {{ $classes['border'] }} transition-all duration-200 hover:shadow-lg hover:scale-105"
                    >
                        @if (!empty($action['badge']))
                            <span class="absolute -top-2 -right-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white {{ $classes['badge'] }} rounded-full">
                                {{ $action['badge'] }}
                            </span>
                        @endif

                        <div class="mb-2">
                            <div class="w-12 h-12 mx-auto flex items-center justify-center rounded-full {{ $classes['bg'] }} {{ $classes['hover'] }} transition-colors">
                                @if(!empty($action['icon']))
                                    <x-dynamic-component
                                        :component="$action['icon']"
                                        class="w-6 h-6 {{ $classes['text'] }}"
                                    />
                                @else
                                    <x-heroicon-o-squares-2x2 class="w-6 h-6 {{ $classes['text'] }}" />
                                @endif
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white {{ $classes['hover_text'] }}">
                                {{ $action['label'] ?? 'Aktion' }}
                            </h3>
                            @if(!empty($action['description']))
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $action['description'] }}
                                </p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>