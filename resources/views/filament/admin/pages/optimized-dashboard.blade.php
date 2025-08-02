<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold">Performance Optimized Dashboard</h2>
                    <p class="text-sm text-gray-500 mt-1">Minimal queries, no auto-refresh</p>
                </div>
                <a href="{{ url()->current() }}" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 inline-block">
                    Refresh Stats
                </a>
            </div>
            @if($lastRefreshed)
                <div class="text-xs text-gray-400 mt-2">
                    Last updated: {{ $lastRefreshed }}
                </div>
            @endif
        </div>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="text-2xl font-bold">{{ $stats['total_users'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Total Users</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="text-2xl font-bold">{{ $stats['total_companies'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Companies</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="text-2xl font-bold">{{ $stats['total_branches'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Branches</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="text-2xl font-bold">{{ $stats['today_appointments'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Today's Appointments</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="text-2xl font-bold">{{ $stats['today_calls'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Today's Calls</div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
                <div class="text-2xl font-bold">{{ $stats['pending_appointments'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Pending Appointments</div>
            </div>
        </div>

        {{-- Info Section --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h3 class="text-lg font-semibold mb-4">Performance Features</h3>
            <ul class="space-y-2 text-sm">
                <li class="flex items-center">
                    <span class="text-green-500 mr-2">✓</span>
                    No auto-refresh or polling
                </li>
                <li class="flex items-center">
                    <span class="text-green-500 mr-2">✓</span>
                    Minimal database queries
                </li>
                <li class="flex items-center">
                    <span class="text-green-500 mr-2">✓</span>
                    Manual refresh only
                </li>
                <li class="flex items-center">
                    <span class="text-green-500 mr-2">✓</span>
                    Lightweight components
                </li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>