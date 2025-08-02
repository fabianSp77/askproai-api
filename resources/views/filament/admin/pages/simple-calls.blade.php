<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Stats Summary --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Calls</h3>
                <p class="text-2xl font-semibold mt-1">{{ $debugInfo['call_count'] ?? 0 }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Company ID</h3>
                <p class="text-2xl font-semibold mt-1">{{ $debugInfo['company_id'] ?? 'N/A' }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">User</h3>
                <p class="text-lg font-semibold mt-1">{{ $debugInfo['user_email'] ?? 'N/A' }}</p>
            </div>
        </div>
        
        {{-- Calls Table --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow">
            <div class="px-6 py-4 border-b dark:border-gray-700">
                <h2 class="text-xl font-semibold">Recent Calls (No Livewire)</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Call ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($calls as $call)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $call['id'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ substr($call['call_id'], 0, 8) }}...</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $call['phone_number'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $call['customer_name'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $call['duration'] }}s</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $call['created_at'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No calls found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Manual Refresh Button --}}
        <div class="flex justify-end">
            <a href="{{ url('/admin/simple-calls') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                Refresh Page
            </a>
        </div>
    </div>
</x-filament-panels::page>