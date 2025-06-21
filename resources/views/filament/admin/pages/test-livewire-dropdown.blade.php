<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="submit">
            {{ $this->form }}
            
            <div class="mt-6 flex items-center gap-4">
                <x-filament::button type="submit">
                    Submit
                </x-filament::button>
                
                <x-filament::button 
                    type="button"
                    color="gray"
                    wire:click="clearDebugLog"
                >
                    Clear Debug Log
                </x-filament::button>
            </div>
        </form>
        
        @if($debugMode)
            <div class="mt-8">
                <h3 class="text-lg font-medium mb-2">Debug Log</h3>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 max-h-96 overflow-y-auto">
                    @forelse($debugLog as $log)
                        <div class="text-sm font-mono mb-1">
                            <span class="text-gray-500">{{ $log['time'] }}</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ $log['message'] }}</span>
                        </div>
                    @empty
                        <p class="text-gray-500">No debug logs yet</p>
                    @endforelse
                </div>
            </div>
        @endif
        
        <div class="mt-6">
            <h3 class="text-lg font-medium mb-2">Current State</h3>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Company ID</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $company_id ?? 'Not selected' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Branch ID</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $branch_id ?? 'Not selected' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Form Data</dt>
                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                        <pre>{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
    
    @push('scripts')
    <script>
        // Listen for company change events
        window.addEventListener('company-changed', event => {
            console.log('Company changed event received:', event.detail);
            
            // Force Livewire to update
            Livewire.emit('refreshComponent');
        });
    </script>
    @endpush
</x-filament-panels::page>