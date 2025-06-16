<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Session Information
        </x-slot>
        
        <dl class="space-y-4">
            @foreach($this->getSessionInfo() as $key => $value)
                <div class="border-b border-gray-200 dark:border-gray-700 pb-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $key }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                        @if(is_array($value))
                            <pre class="bg-gray-100 dark:bg-gray-800 p-2 rounded text-xs">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                        @elseif(is_bool($value))
                            <span class="{{ $value ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $value ? 'TRUE' : 'FALSE' }}
                            </span>
                        @else
                            {{ $value ?? 'NULL' }}
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </x-filament::section>
    
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Test Actions
        </x-slot>
        
        <div class="flex gap-4">
            <x-filament::button wire:click="testAction">
                Test Session Write
            </x-filament::button>
            
            <x-filament::button wire:click="$refresh" color="gray">
                Refresh
            </x-filament::button>
        </div>
    </x-filament::section>
    
    @if(session('test_value'))
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                Test Result
            </x-slot>
            
            <p>Session test value: <strong>{{ session('test_value') }}</strong></p>
        </x-filament::section>
    @endif
</x-filament-panels::page>