<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-semibold mb-4">Retell Ultimate Dashboard - Simplified</h2>
            <p>If you can see this, the page is loading correctly.</p>
            
            @if($error)
                <div class="mt-4 p-4 bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 rounded-lg">
                    Error: {{ $error }}
                </div>
            @endif
            
            <div class="mt-6">
                <button wire:click="loadAgents" class="px-4 py-2 bg-primary-600 text-white rounded-lg">
                    Load Agents
                </button>
            </div>
            
            @if(count($agents) > 0)
                <div class="mt-4">
                    <p>Found {{ count($agents) }} agents</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>