<x-filament-widgets::widget>
    @if($hasAlerts)
        <div class="space-y-2">
            @foreach($alerts as $alert)
                <div 
                    x-data="{ show: true }" 
                    x-show="show"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-90"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-90"
                    class="relative"
                >
                    <x-filament::card 
                        class="border-2 {{ $alert['type'] === 'danger' ? 'border-danger-300 bg-danger-50' : ($alert['type'] === 'warning' ? 'border-warning-300 bg-warning-50' : 'border-info-300 bg-info-50') }}"
                    >
                        <div class="flex items-start gap-3">
                            {{-- Icon --}}
                            <div class="flex-shrink-0">
                                @if($alert['type'] === 'danger')
                                    <x-heroicon-o-x-circle class="w-6 h-6 text-danger-600" />
                                @elseif($alert['type'] === 'warning')
                                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-warning-600" />
                                @else
                                    <x-heroicon-o-information-circle class="w-6 h-6 text-info-600" />
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-semibold {{ $alert['type'] === 'danger' ? 'text-danger-900' : ($alert['type'] === 'warning' ? 'text-warning-900' : 'text-info-900') }}">
                                    {{ $alert['title'] }}
                                </h3>
                                <p class="mt-1 text-sm {{ $alert['type'] === 'danger' ? 'text-danger-700' : ($alert['type'] === 'warning' ? 'text-warning-700' : 'text-info-700') }}">
                                    {{ $alert['message'] }}
                                </p>
                                @if(isset($alert['action']) && isset($alert['actionUrl']))
                                    <div class="mt-2">
                                        <a 
                                            href="{{ $alert['actionUrl'] }}"
                                            class="text-sm font-medium {{ $alert['type'] === 'danger' ? 'text-danger-600 hover:text-danger-500' : ($alert['type'] === 'warning' ? 'text-warning-600 hover:text-warning-500' : 'text-info-600 hover:text-info-500') }}"
                                        >
                                            {{ $alert['action'] }} →
                                        </a>
                                    </div>
                                @endif
                            </div>

                            {{-- Dismiss Button --}}
                            <div class="flex-shrink-0">
                                <button
                                    type="button"
                                    @click="show = false; $wire.call('handleAlertDismiss', '{{ $alert['id'] }}')"
                                    class="rounded-md p-1.5 {{ $alert['type'] === 'danger' ? 'text-danger-500 hover:bg-danger-100' : ($alert['type'] === 'warning' ? 'text-warning-500 hover:bg-warning-100' : 'text-info-500 hover:bg-info-100') }} focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $alert['type'] === 'danger' ? 'focus:ring-danger-500' : ($alert['type'] === 'warning' ? 'focus:ring-warning-500' : 'focus:ring-info-500') }}"
                                >
                                    <span class="sr-only">Schließen</span>
                                    <x-heroicon-o-x-mark class="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </x-filament::card>
                </div>
            @endforeach
        </div>
    @else
        {{-- Keine Alerts --}}
        <x-filament::card class="text-center py-6">
            <x-heroicon-o-check-circle class="mx-auto h-12 w-12 text-success-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                Alles in Ordnung
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Keine kritischen Meldungen vorhanden.
            </p>
        </x-filament::card>
    @endif
</x-filament-widgets::widget>