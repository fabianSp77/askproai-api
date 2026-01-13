<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ $this->getHeading() }}
        </x-slot>

        <x-slot name="description">
            {{ $this->getDescription() }}
        </x-slot>

        @php
            $alerts = $this->getAlerts();
        @endphp

        <div class="space-y-3">
            @forelse($alerts as $alert)
                <div @class([
                    'flex items-start gap-3 p-3 rounded-lg border transition-colors',
                    'bg-danger-50 dark:bg-danger-950/50 border-danger-200 dark:border-danger-800' => $alert['color'] === 'danger',
                    'bg-warning-50 dark:bg-warning-950/50 border-warning-200 dark:border-warning-800' => $alert['color'] === 'warning',
                    'bg-success-50 dark:bg-success-950/50 border-success-200 dark:border-success-800' => $alert['color'] === 'success',
                    'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700' => $alert['color'] === 'gray',
                    'bg-info-50 dark:bg-info-950/50 border-info-200 dark:border-info-800' => $alert['color'] === 'info',
                ])>
                    {{-- Icon --}}
                    <div @class([
                        'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center',
                        'bg-danger-100 dark:bg-danger-900/50 text-danger-600 dark:text-danger-400' => $alert['color'] === 'danger',
                        'bg-warning-100 dark:bg-warning-900/50 text-warning-600 dark:text-warning-400' => $alert['color'] === 'warning',
                        'bg-success-100 dark:bg-success-900/50 text-success-600 dark:text-success-400' => $alert['color'] === 'success',
                        'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400' => $alert['color'] === 'gray',
                        'bg-info-100 dark:bg-info-900/50 text-info-600 dark:text-info-400' => $alert['color'] === 'info',
                    ])>
                        <x-dynamic-component :component="$alert['icon']" class="w-4 h-4" />
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <h4 @class([
                                'font-medium text-sm',
                                'text-danger-700 dark:text-danger-300' => $alert['color'] === 'danger',
                                'text-warning-700 dark:text-warning-300' => $alert['color'] === 'warning',
                                'text-success-700 dark:text-success-300' => $alert['color'] === 'success',
                                'text-gray-700 dark:text-gray-300' => $alert['color'] === 'gray',
                                'text-info-700 dark:text-info-300' => $alert['color'] === 'info',
                            ])>
                                {{ $alert['title'] }}
                            </h4>

                            @if($alert['type'] === 'critical')
                                <x-filament::badge color="danger" size="sm">
                                    Kritisch
                                </x-filament::badge>
                            @endif
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                            {{ $alert['message'] }}
                        </p>

                        @if(!empty($alert['detail']))
                            <p @class([
                                'text-xs mt-1 font-medium',
                                'text-danger-600 dark:text-danger-400' => $alert['color'] === 'danger',
                                'text-warning-600 dark:text-warning-400' => $alert['color'] === 'warning',
                                'text-success-600 dark:text-success-400' => $alert['color'] === 'success',
                                'text-gray-500 dark:text-gray-500' => $alert['color'] === 'gray',
                                'text-info-600 dark:text-info-400' => $alert['color'] === 'info',
                            ])>
                                {{ $alert['detail'] }}
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-bell-slash class="w-10 h-10 mx-auto mb-2 opacity-50" />
                    <p class="text-sm">Keine Alerts vorhanden</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
