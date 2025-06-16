<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Widget Status
            </x-slot>
            
            <div class="space-y-4">
                @foreach($this->getWidgetErrors() as $widget => $status)
                    <div class="flex items-center justify-between p-4 rounded-lg {{ $status === 'OK' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <div>
                            <h3 class="font-semibold {{ $status === 'OK' ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                {{ class_basename($widget) }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $widget }}</p>
                        </div>
                        <div>
                            @if($status === 'OK')
                                <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                            @else
                                <div class="text-right">
                                    <x-heroicon-o-x-circle class="w-6 h-6 text-red-600 dark:text-red-400" />
                                    <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $status }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
        
        <x-filament::section>
            <x-slot name="heading">
                Dashboard Configuration
            </x-slot>
            
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User ID</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ auth()->id() ?? 'Not authenticated' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Company ID</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ auth()->user()->company_id ?? 'None' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cache Driver</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ config('cache.default') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Queue Driver</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ config('queue.default') }}</dd>
                </div>
            </dl>
        </x-filament::section>
        
        <x-filament::section>
            <x-slot name="heading">
                Actions
            </x-slot>
            
            <div class="flex gap-4">
                <x-filament::button wire:click="$refresh">
                    Refresh
                </x-filament::button>
                
                <form action="{{ url('/admin') }}" method="GET">
                    <x-filament::button type="submit" color="gray">
                        Back to Dashboard
                    </x-filament::button>
                </form>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>