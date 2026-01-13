<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-clock class="w-5 h-5 text-primary-500" />
                <span>Aktivitätsverlauf</span>
                <x-filament::badge color="gray" size="sm">
                    {{ count($this->getActivities()) }}
                </x-filament::badge>
            </div>
        </x-slot>

        <x-slot name="description">
            Chronologische Geschichte dieses Service Cases
        </x-slot>

        @php
            $activities = $this->getActivities();
        @endphp

        @if(empty($activities))
            <div class="text-center py-12 text-gray-500 dark:text-gray-400" role="status" aria-label="Keine Aktivitäten vorhanden">
                <x-heroicon-o-inbox class="w-12 h-12 mx-auto mb-3 opacity-50" aria-hidden="true" />
                <p class="text-sm font-medium">Keine Aktivitäten vorhanden</p>
                <p class="text-xs mt-1">Aktivitäten werden automatisch erfasst</p>
            </div>
        @else
            <div class="relative" role="region" aria-label="Aktivitätsverlauf mit {{ count($activities) }} Einträgen">
                {{-- Timeline vertical line --}}
                <div class="absolute left-6 top-4 bottom-4 w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></div>

                <ol class="space-y-4" role="list" aria-label="Chronologische Aktivitäten">
                    @foreach($activities as $index => $activity)
                        @php
                            $isFirst = $index === 0;
                            $isLast = $index === count($activities) - 1;

                            // Color mapping
                            $colorClasses = match($activity['color']) {
                                'success' => [
                                    'dot' => 'bg-success-500',
                                    'ring' => 'ring-success-200 dark:ring-success-800',
                                    'icon' => 'text-success-600 dark:text-success-400',
                                    'bg' => 'bg-success-50 dark:bg-success-950',
                                ],
                                'danger' => [
                                    'dot' => 'bg-danger-500',
                                    'ring' => 'ring-danger-200 dark:ring-danger-800',
                                    'icon' => 'text-danger-600 dark:text-danger-400',
                                    'bg' => 'bg-danger-50 dark:bg-danger-950',
                                ],
                                'warning' => [
                                    'dot' => 'bg-warning-500',
                                    'ring' => 'ring-warning-200 dark:ring-warning-800',
                                    'icon' => 'text-warning-600 dark:text-warning-400',
                                    'bg' => 'bg-warning-50 dark:bg-warning-950',
                                ],
                                'info' => [
                                    'dot' => 'bg-info-500',
                                    'ring' => 'ring-info-200 dark:ring-info-800',
                                    'icon' => 'text-info-600 dark:text-info-400',
                                    'bg' => 'bg-info-50 dark:bg-info-950',
                                ],
                                default => [
                                    'dot' => 'bg-gray-400',
                                    'ring' => 'ring-gray-200 dark:ring-gray-700',
                                    'icon' => 'text-gray-600 dark:text-gray-400',
                                    'bg' => 'bg-gray-50 dark:bg-gray-800',
                                ],
                            };
                        @endphp

                        <li class="relative pl-14 group timeline-item" style="animation-delay: {{ $index * 0.1 }}s" aria-label="{{ $activity['title'] }} - {{ $activity['timestamp']->format('d.m.Y H:i') }}">
                            {{-- Timeline dot with icon --}}
                            <div class="absolute left-0 flex items-center justify-center w-12 h-12 rounded-full {{ $colorClasses['bg'] }} ring-4 ring-white dark:ring-gray-900 {{ $colorClasses['ring'] }} shadow-sm transition-transform group-hover:scale-110" aria-hidden="true">
                                <x-dynamic-component :component="$activity['icon']" class="w-6 h-6 {{ $colorClasses['icon'] }}" aria-hidden="true" />
                            </div>

                            {{-- Event card --}}
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 transition-all hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-900 dark:text-white">
                                            {{ $activity['title'] }}
                                        </h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 leading-relaxed">
                                            {{ $activity['description'] }}
                                        </p>
                                    </div>

                                    <div class="flex-shrink-0 text-right">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ $activity['timestamp']->format('d.m.Y') }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $activity['timestamp']->format('H:i') }} Uhr
                                        </div>
                                    </div>
                                </div>

                                {{-- Actor footer --}}
                                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                        <x-heroicon-o-user-circle class="w-4 h-4" aria-hidden="true" />
                                        <span>{{ $activity['actor'] }}</span>
                                    </div>
                                    <time class="text-xs text-gray-400 dark:text-gray-500" datetime="{{ $activity['timestamp']->toIso8601String() }}">
                                        {{ $activity['timestamp']->diffForHumans() }}
                                    </time>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

<style>
    .timeline-item {
        animation: slideInLeft 0.3s ease-out forwards;
        opacity: 0;
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
</style>
