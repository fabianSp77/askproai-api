<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Appointments Card -->
            <div class="relative overflow-hidden rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Termine heute
                        </p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ $appointmentsToday }}
                        </p>
                    </div>
                    <div class="rounded-full bg-primary-100 p-3 dark:bg-primary-900">
                        <x-heroicon-o-calendar class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    @if($appointmentsTrend > 0)
                        <x-heroicon-m-arrow-trending-up class="h-4 w-4 text-success-600" />
                        <span class="ml-1 text-success-600">{{ abs($appointmentsTrend) }}%</span>
                    @elseif($appointmentsTrend < 0)
                        <x-heroicon-m-arrow-trending-down class="h-4 w-4 text-danger-600" />
                        <span class="ml-1 text-danger-600">{{ abs($appointmentsTrend) }}%</span>
                    @else
                        <x-heroicon-m-minus class="h-4 w-4 text-gray-400" />
                        <span class="ml-1 text-gray-400">0%</span>
                    @endif
                    <span class="ml-2 text-gray-500 dark:text-gray-400">vs. gestern</span>
                </div>
                <!-- Loading skeleton -->
                <div wire:loading wire:target="poll" class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-800/50">
                    <x-filament::loading-indicator class="h-5 w-5" />
                </div>
            </div>

            <!-- Calls Card -->
            <div class="relative overflow-hidden rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Anrufe heute
                        </p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ $callsToday }}
                        </p>
                    </div>
                    <div class="rounded-full bg-success-100 p-3 dark:bg-success-900">
                        <x-heroicon-o-phone class="h-6 w-6 text-success-600 dark:text-success-400" />
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    @if($callsTrend > 0)
                        <x-heroicon-m-arrow-trending-up class="h-4 w-4 text-success-600" />
                        <span class="ml-1 text-success-600">{{ abs($callsTrend) }}%</span>
                    @elseif($callsTrend < 0)
                        <x-heroicon-m-arrow-trending-down class="h-4 w-4 text-danger-600" />
                        <span class="ml-1 text-danger-600">{{ abs($callsTrend) }}%</span>
                    @else
                        <x-heroicon-m-minus class="h-4 w-4 text-gray-400" />
                        <span class="ml-1 text-gray-400">0%</span>
                    @endif
                    <span class="ml-2 text-gray-500 dark:text-gray-400">vs. gestern</span>
                </div>
                <div wire:loading wire:target="poll" class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-800/50">
                    <x-filament::loading-indicator class="h-5 w-5" />
                </div>
            </div>

            <!-- New Customers Card -->
            <div class="relative overflow-hidden rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Neue Kunden
                        </p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ $newCustomersToday }}
                        </p>
                    </div>
                    <div class="rounded-full bg-warning-100 p-3 dark:bg-warning-900">
                        <x-heroicon-o-user-plus class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    @if($customersTrend > 0)
                        <x-heroicon-m-arrow-trending-up class="h-4 w-4 text-success-600" />
                        <span class="ml-1 text-success-600">{{ abs($customersTrend) }}%</span>
                    @elseif($customersTrend < 0)
                        <x-heroicon-m-arrow-trending-down class="h-4 w-4 text-danger-600" />
                        <span class="ml-1 text-danger-600">{{ abs($customersTrend) }}%</span>
                    @else
                        <x-heroicon-m-minus class="h-4 w-4 text-gray-400" />
                        <span class="ml-1 text-gray-400">0%</span>
                    @endif
                    <span class="ml-2 text-gray-500 dark:text-gray-400">vs. gestern</span>
                </div>
                <div wire:loading wire:target="poll" class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-800/50">
                    <x-filament::loading-indicator class="h-5 w-5" />
                </div>
            </div>

            <!-- Revenue Card -->
            <div class="relative overflow-hidden rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Umsatz heute
                        </p>
                        <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-gray-100">
                            {{ Number::currency($revenueToday, 'EUR') }}
                        </p>
                    </div>
                    <div class="rounded-full bg-info-100 p-3 dark:bg-info-900">
                        <x-heroicon-o-currency-euro class="h-6 w-6 text-info-600 dark:text-info-400" />
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm">
                    @if($revenueTrend > 0)
                        <x-heroicon-m-arrow-trending-up class="h-4 w-4 text-success-600" />
                        <span class="ml-1 text-success-600">{{ abs($revenueTrend) }}%</span>
                    @elseif($revenueTrend < 0)
                        <x-heroicon-m-arrow-trending-down class="h-4 w-4 text-danger-600" />
                        <span class="ml-1 text-danger-600">{{ abs($revenueTrend) }}%</span>
                    @else
                        <x-heroicon-m-minus class="h-4 w-4 text-gray-400" />
                        <span class="ml-1 text-gray-400">0%</span>
                    @endif
                    <span class="ml-2 text-gray-500 dark:text-gray-400">vs. gestern</span>
                </div>
                <div wire:loading wire:target="poll" class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-800/50">
                    <x-filament::loading-indicator class="h-5 w-5" />
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>