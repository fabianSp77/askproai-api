<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-xl font-semibold mb-4">Retell Ultimate Dashboard</h2>
            
            @if($error)
                <div class="p-4 bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 rounded-lg mb-4">
                    {{ $error }}
                </div>
            @endif
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4">
                    <h3 class="font-medium text-primary-900 dark:text-primary-100">Total Agents</h3>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ count($agents) }}</p>
                </div>
            </div>
            
            @if(count($agents) > 0)
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-medium mb-4">Agents</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach(array_slice($agents, 0, 6) as $agent)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <h4 class="font-medium">{{ $agent['agent_name'] ?? 'Unknown' }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">ID: {{ \Illuminate\Support\Str::limit($agent['agent_id'] ?? '', 20) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>No agents found. Please configure your Retell API key.</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>