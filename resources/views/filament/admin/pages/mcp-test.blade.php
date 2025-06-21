<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Test Form --}}
        <x-filament::card>
            <div class="space-y-4">
                <h3 class="text-lg font-semibold">MCP Test Console</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Test MCP services and operations directly from the admin panel.
                </p>
                
                <form wire:submit="executeTest">
                    {{ $this->form }}
                    
                    <div class="mt-4 flex gap-2">
                        <x-filament::button type="submit">
                            Execute Test
                        </x-filament::button>
                        
                        @if($response)
                            <x-filament::button 
                                type="button" 
                                color="gray"
                                wire:click="clearResponse">
                                Clear Response
                            </x-filament::button>
                        @endif
                    </div>
                </form>
            </div>
        </x-filament::card>
        
        {{-- Response Display --}}
        @if($response)
            <x-filament::card>
                <div class="space-y-2">
                    <h3 class="text-lg font-semibold">Response</h3>
                    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-4 overflow-x-auto">
                        <pre class="text-sm text-gray-800 dark:text-gray-200">{{ $response }}</pre>
                    </div>
                </div>
            </x-filament::card>
        @endif
        
        {{-- Help Section --}}
        <x-filament::card>
            <div class="space-y-4">
                <h3 class="text-lg font-semibold">Example Parameters</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300">Webhook Stats</h4>
                        <pre class="bg-gray-100 dark:bg-gray-800 p-2 rounded mt-1">{"days": 7}</pre>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300">Database Query</h4>
                        <pre class="bg-gray-100 dark:bg-gray-800 p-2 rounded mt-1">{"sql": "SELECT COUNT(*) FROM calls", "bindings": []}</pre>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300">Cal.com Event Types</h4>
                        <pre class="bg-gray-100 dark:bg-gray-800 p-2 rounded mt-1">{"company_id": 1}</pre>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300">Stripe Payment Overview</h4>
                        <pre class="bg-gray-100 dark:bg-gray-800 p-2 rounded mt-1">{"company_id": 1, "period": "month"}</pre>
                    </div>
                </div>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>