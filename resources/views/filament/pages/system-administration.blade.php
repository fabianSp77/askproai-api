<x-filament-panels::page>
    <div class="space-y-6">
        {{-- System Health Alert --}}
        @if(count($systemHealth['issues']) > 0)
            <div class="space-y-2">
                @foreach($systemHealth['issues'] as $issue)
                    <div class="p-4 rounded-lg flex items-center space-x-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                        <span class="text-2xl">⚠️</span>
                        <span class="text-sm font-medium text-red-800 dark:text-red-200">{{ $issue }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-4 rounded-lg flex items-center space-x-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800">
                <span class="text-2xl">✅</span>
                <span class="text-sm font-medium text-green-800 dark:text-green-200">Alle Systeme funktionieren normal</span>
            </div>
        @endif

        {{-- Quick Actions --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚡ Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button
                        wire:click="clearCache"
                        class="px-4 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>Cache leeren</span>
                    </button>

                    <button
                        wire:click="clearViewCache"
                        class="px-4 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <span>View Cache leeren</span>
                    </button>

                    <button
                        wire:click="optimizeApp"
                        class="px-4 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition-colors flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>App optimieren</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- System Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- PHP Version --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">PHP Version</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $systemStats['php_version'] }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $systemStats['environment'] }}
                            </p>
                        </div>
                        <div class="flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full">
                            <span class="text-2xl">🐘</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Laravel Version --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Laravel</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $systemStats['laravel_version'] }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Framework
                            </p>
                        </div>
                        <div class="flex items-center justify-center w-12 h-12 bg-red-100 dark:bg-red-900 rounded-full">
                            <span class="text-2xl">🔥</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Disk Usage --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Disk Usage</p>
                            <p class="mt-2 text-2xl font-bold
                                @if($systemStats['disk_usage']['percent'] > 90) text-red-600 dark:text-red-400
                                @elseif($systemStats['disk_usage']['percent'] > 80) text-yellow-600 dark:text-yellow-400
                                @else text-green-600 dark:text-green-400
                                @endif">
                                {{ $systemStats['disk_usage']['percent'] }}%
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ $systemStats['disk_usage']['used'] }} / {{ $systemStats['disk_usage']['total'] }}
                            </p>
                        </div>
                        <div class="flex items-center justify-center w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-full">
                            <span class="text-2xl">💾</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Memory Usage --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Memory</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ round($systemStats['memory_usage'] / 1024 / 1024, 0) }} MB
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Peak: {{ round($systemStats['memory_peak'] / 1024 / 1024, 0) }} MB
                            </p>
                        </div>
                        <div class="flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full">
                            <span class="text-2xl">🧠</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Database & Security Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Database Stats --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <span class="text-2xl mr-2">🗄️</span>
                        Database Statistiken
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Users</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $databaseStats['total_users'] }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Companies</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $databaseStats['total_companies'] }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Resellers</span>
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $databaseStats['resellers'] }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Customers</span>
                            <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ $databaseStats['customers'] }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Calls</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($databaseStats['total_calls'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Calls Today</span>
                            <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ $databaseStats['calls_today'] }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Database Size</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $databaseStats['database_size'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Security Stats --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <span class="text-2xl mr-2">🔒</span>
                        Security & Access
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Super Admins</span>
                            <span class="text-sm font-bold text-red-600 dark:text-red-400">{{ $securityStats['super_admins'] }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Resellers</span>
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $securityStats['resellers'] }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Customers</span>
                            <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ $securityStats['customers'] }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Failed Logins (7d)</span>
                            <span class="text-sm font-bold
                                @if($securityStats['failed_logins_7d'] > 10) text-red-600 dark:text-red-400
                                @elseif($securityStats['failed_logins_7d'] > 5) text-yellow-600 dark:text-yellow-400
                                @else text-green-600 dark:text-green-400
                                @endif">
                                {{ $securityStats['failed_logins_7d'] }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">SSL Enabled</span>
                            <span class="text-sm font-bold">{{ $securityStats['ssl_enabled'] }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Debug Mode</span>
                            <span class="text-sm font-bold">{{ $systemStats['debug_mode'] }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Cache Status</span>
                            <span class="text-sm font-bold">{{ $cacheStats['status'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- System Health Details --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <span class="text-2xl mr-2">💚</span>
                    System Health
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="text-center">
                        <div class="text-3xl mb-2">{{ $systemHealth['status']['database'] }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Database</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl mb-2">{{ $systemHealth['status']['cache'] }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Cache</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl mb-2">{{ $systemHealth['status']['storage'] }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Storage</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl mb-2">{{ $systemHealth['status']['queue'] }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Queue</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl mb-2">{{ $systemHealth['status']['overall'] }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">Overall</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Recent Users --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <span class="text-2xl mr-2">👥</span>
                        Recent Users (Last 10)
                    </h3>
                    <div class="space-y-2">
                        @foreach($recentActivity['users'] as $user)
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 text-xs">
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 dark:text-white truncate">{{ $user['name'] }}</div>
                                    <div class="text-gray-500 dark:text-gray-400 truncate">{{ $user['email'] }}</div>
                                    <div class="text-gray-400 dark:text-gray-500 text-xs">{{ $user['roles'] }}</div>
                                </div>
                                <div class="ml-2 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $user['created_at'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Recent Companies --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <span class="text-2xl mr-2">🏢</span>
                        Recent Companies (Last 10)
                    </h3>
                    <div class="space-y-2">
                        @foreach($recentActivity['companies'] as $company)
                            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 text-xs">
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 dark:text-white truncate">{{ $company['name'] }}</div>
                                    <div class="text-gray-500 dark:text-gray-400">
                                        <span class="px-2 py-1 rounded text-xs
                                            @if($company['type'] === 'Reseller') bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                                            @else bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                            @endif">
                                            {{ $company['type'] }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-2 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $company['created_at'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- System Info --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <span class="text-2xl mr-2">ℹ️</span>
                    System Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Environment:</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $systemStats['environment'] }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Timezone:</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $systemStats['timezone'] }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Locale:</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $systemStats['locale'] }}</span>
                    </div>
                    <div class="md:col-span-3">
                        <span class="text-gray-600 dark:text-gray-400">App URL:</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $systemStats['url'] }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600 dark:text-gray-400">Cache Driver:</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $cacheStats['driver'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
