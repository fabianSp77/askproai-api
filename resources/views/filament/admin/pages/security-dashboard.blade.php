<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- Security Status Overview -->
        <div class="col-span-full">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    Security Layer Status
                </h2>
                
                @php
                    $status = $this->getSecurityStatus();
                @endphp
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded">
                        <div class="text-2xl mb-1">{{ $status['encryption']['enabled'] ? '✅' : '❌' }}</div>
                        <div class="text-sm font-medium">Encryption</div>
                        <div class="text-xs text-gray-500">{{ $status['encryption']['algorithm'] }}</div>
                    </div>
                    
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded">
                        <div class="text-2xl mb-1">{{ $status['rate_limiting']['enabled'] ? '✅' : '❌' }}</div>
                        <div class="text-sm font-medium">Rate Limiting</div>
                        <div class="text-xs text-gray-500">{{ $status['rate_limiting']['endpoints_protected'] }} endpoints</div>
                    </div>
                    
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded">
                        <div class="text-2xl mb-1">{{ $status['threat_detection']['enabled'] ? '✅' : '❌' }}</div>
                        <div class="text-sm font-medium">Threat Detection</div>
                        <div class="text-xs text-gray-500">{{ $status['threat_detection']['patterns_monitored'] }} patterns</div>
                    </div>
                    
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded">
                        <div class="text-2xl mb-1">{{ $status['audit_logging']['enabled'] ? '✅' : '❌' }}</div>
                        <div class="text-sm font-medium">Audit Logging</div>
                        <div class="text-xs text-gray-500">{{ $status['audit_logging']['retention_days'] }} days</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Threat Statistics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Threat Detection</h3>
            
            @php
                $threats = $this->getThreatStatistics();
            @endphp
            
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Today</span>
                    <span class="text-xl font-bold {{ $threats['today_count'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $threats['today_count'] }}
                    </span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Yesterday</span>
                    <span class="text-lg">{{ $threats['yesterday_count'] }}</span>
                </div>
                
                @if(!empty($threats['threat_types']))
                    <div class="mt-4 pt-4 border-t">
                        <div class="text-sm font-medium mb-2">Threat Types</div>
                        @foreach($threats['threat_types'] as $type => $count)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $type)) }}</span>
                                <span class="font-medium">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Rate Limiting Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Rate Limiting</h3>
            
            @php
                $rateLimit = $this->getRateLimitStatistics();
            @endphp
            
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Blocked Today</span>
                    <span class="text-xl font-bold text-orange-600">{{ $rateLimit['blocked_today'] }}</span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Blocked Yesterday</span>
                    <span class="text-lg">{{ $rateLimit['blocked_yesterday'] }}</span>
                </div>
                
                <div class="mt-4">
                    <div class="text-xs text-gray-500">
                        @if($rateLimit['blocked_today'] > $rateLimit['blocked_yesterday'])
                            <span class="text-red-600">↑ {{ round(($rateLimit['blocked_today'] - $rateLimit['blocked_yesterday']) / max(1, $rateLimit['blocked_yesterday']) * 100) }}% increase</span>
                        @else
                            <span class="text-green-600">↓ {{ round(($rateLimit['blocked_yesterday'] - $rateLimit['blocked_today']) / max(1, $rateLimit['blocked_yesterday']) * 100) }}% decrease</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Backup Status</h3>
            
            @php
                $backup = $this->getBackupStatus();
            @endphp
            
            <div class="space-y-3">
                <div>
                    <div class="text-sm text-gray-600">Last Backup</div>
                    <div class="font-medium {{ $backup['last_backup'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ $backup['last_backup'] ?? 'Never' }}
                    </div>
                </div>
                
                <div>
                    <div class="text-sm text-gray-600">Next Scheduled</div>
                    <div class="font-medium">{{ $backup['next_scheduled'] ?? 'Not scheduled' }}</div>
                </div>
                
                <div>
                    <div class="text-sm text-gray-600">Total Size</div>
                    <div class="font-medium">{{ $backup['backup_size'] }}</div>
                </div>
            </div>
        </div>

        <!-- System Vulnerabilities -->
        <div class="col-span-full">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">System Vulnerabilities</h3>
                
                @php
                    $vulnerabilities = $this->getSystemVulnerabilities();
                @endphp
                
                @if(empty($vulnerabilities))
                    <div class="text-green-600 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        No vulnerabilities detected
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($vulnerabilities as $vuln)
                            <div class="flex items-start p-3 rounded 
                                {{ $vuln['severity'] === 'critical' ? 'bg-red-50 dark:bg-red-900/20' : '' }}
                                {{ $vuln['severity'] === 'high' ? 'bg-orange-50 dark:bg-orange-900/20' : '' }}
                                {{ $vuln['severity'] === 'medium' ? 'bg-yellow-50 dark:bg-yellow-900/20' : '' }}
                            ">
                                <div class="flex-shrink-0">
                                    @if($vuln['severity'] === 'critical')
                                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                        </svg>
                                    @elseif($vuln['severity'] === 'high')
                                        <svg class="w-5 h-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="ml-3 flex-grow">
                                    <div class="font-medium">{{ $vuln['issue'] }}</div>
                                    <div class="text-sm text-gray-600">{{ $vuln['recommendation'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Security Events -->
        <div class="col-span-full">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Recent Security Events</h3>
                
                @php
                    $events = $this->getRecentSecurityEvents();
                @endphp
                
                @if(empty($events))
                    <p class="text-gray-500">No recent security events</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($events as $event)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ \Carbon\Carbon::parse($event['time'])->diffForHumans() }}</td>
                                        <td class="px-4 py-2 text-sm font-medium">{{ $event['description'] }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $event['causer'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500">
                                            @if(!empty($event['properties']))
                                                <details>
                                                    <summary class="cursor-pointer">View</summary>
                                                    <pre class="text-xs mt-2">{{ json_encode($event['properties'], JSON_PRETTY_PRINT) }}</pre>
                                                </details>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        
    </div>
</x-filament-panels::page>