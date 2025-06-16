<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Calls Debug Information
        </x-slot>
        
        <dl class="space-y-4">
            @foreach($this->getDebugInfo() as $key => $value)
                <div class="border-b border-gray-200 dark:border-gray-700 pb-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $key }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                        @if(is_array($value))
                            <pre class="bg-gray-100 dark:bg-gray-800 p-2 rounded">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                        @elseif(is_bool($value))
                            <span class="{{ $value ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $value ? 'TRUE' : 'FALSE' }}
                            </span>
                        @else
                            {{ $value }}
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    </x-filament::section>
    
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Actions
        </x-slot>
        
        <div class="flex gap-4">
            <x-filament::button wire:click="$refresh">
                Refresh
            </x-filament::button>
            
            <x-filament::link href="/admin/calls" tag="a">
                <x-filament::button color="gray">
                    Try to Access Calls Page
                </x-filament::button>
            </x-filament::link>
        </div>
    </x-filament::section>
</x-filament-panels::page>