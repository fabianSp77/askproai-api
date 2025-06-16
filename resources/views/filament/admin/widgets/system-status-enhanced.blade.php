<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    System Status
                </h3>
                <button
                    wire:click="checkSystemStatus"
                    class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                >
                    <x-heroicon-m-arrow-path class="h-4 w-4" />
                </button>
            </div>
            
            @if($errorMessage)
                <div class="rounded-md bg-danger-50 p-3 text-sm text-danger-800 dark:bg-danger-900/20 dark:text-danger-200">
                    {{ $errorMessage }}
                </div>
            @endif
            
            <div class="space-y-3">
                @foreach($statuses as $service => $status)
                    <div class="flex items-center justify-between rounded-lg border p-3 
                        @if($status['status'] === 'online') border-success-200 bg-success-50 dark:border-success-800 dark:bg-success-900/20
                        @elseif($status['status'] === 'warning') border-warning-200 bg-warning-50 dark:border-warning-800 dark:bg-warning-900/20
                        @else border-danger-200 bg-danger-50 dark:border-danger-800 dark:bg-danger-900/20
                        @endif">
                        <div class="flex items-center space-x-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full 
                                @if($status['status'] === 'online') bg-success-100 dark:bg-success-900
                                @elseif($status['status'] === 'warning') bg-warning-100 dark:bg-warning-900
                                @else bg-danger-100 dark:bg-danger-900
                                @endif">
                                <x-dynamic-component 
                                    :component="$status['icon']" 
                                    class="h-4 w-4 
                                        @if($status['status'] === 'online') text-success-600 dark:text-success-400
                                        @elseif($status['status'] === 'warning') text-warning-600 dark:text-warning-400
                                        @else text-danger-600 dark:text-danger-400
                                        @endif"
                                />
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ ucfirst($service) }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $status['message'] }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            @if($status['status'] === 'online')
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-success-500"></span>
                                </span>
                            @elseif($status['status'] === 'warning')
                                <x-heroicon-m-exclamation-triangle class="h-5 w-5 text-warning-500" />
                            @else
                                <x-heroicon-m-x-circle class="h-5 w-5 text-danger-500" />
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if($isLoading)
                <div class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-800/50">
                    <x-filament::loading-indicator class="h-5 w-5" />
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>