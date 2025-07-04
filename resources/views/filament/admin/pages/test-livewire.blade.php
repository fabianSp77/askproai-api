<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4">Test Livewire Components</h2>
            
            <div class="space-y-8">
                <div>
                    <h3 class="text-lg font-semibold mb-2">1. Simple Counter Test</h3>
                    @livewire(\App\Livewire\TestLivewire::class)
                </div>
                
                <hr class="border-gray-300 dark:border-gray-700">
                
                <div>
                    <h3 class="text-lg font-semibold mb-2">2. Global Branch Selector</h3>
                    @livewire(\App\Livewire\GlobalBranchSelector::class)
                </div>
                
                <hr class="border-gray-300 dark:border-gray-700">
                
                <div>
                    <h3 class="text-lg font-semibold mb-2">3. JavaScript Console</h3>
                    <div class="bg-gray-100 dark:bg-gray-900 p-4 rounded">
                        <p class="text-sm mb-2">Open browser console to see debug messages.</p>
                        <button onclick="console.log('Livewire:', window.Livewire); console.log('Alpine:', window.Alpine);" class="px-4 py-2 bg-gray-500 text-white rounded">
                            Log Livewire Status
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>