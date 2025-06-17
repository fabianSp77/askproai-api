<x-filament-panels::page>
    @php
        $results = $this->getTestResults();
    @endphp
    
    <div class="space-y-6">
        <!-- Status Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $results['api_configured'] ? 'bg-success-50 dark:bg-success-400/10' : 'bg-danger-50 dark:bg-danger-400/10' }}">
                            <x-heroicon-o-key class="h-5 w-5 {{ $results['api_configured'] ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">API Key</p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $results['api_configured'] ? 'Configured' : 'Missing' }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $results['webhook_configured'] ? 'bg-success-50 dark:bg-success-400/10' : 'bg-warning-50 dark:bg-warning-400/10' }}">
                            <x-heroicon-o-link class="h-5 w-5 {{ $results['webhook_configured'] ? 'text-success-600 dark:text-success-400' : 'text-warning-600 dark:text-warning-400' }}" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Webhook Secret</p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $results['webhook_configured'] ? 'Configured' : 'Not Set' }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-400/10">
                            <x-heroicon-o-calendar-days class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Synced Appointments</p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($results['appointments_synced']) }}
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-info-50 dark:bg-info-400/10">
                            <x-heroicon-o-clock class="h-5 w-5 text-info-600 dark:text-info-400" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Sync</p>
                        <p class="text-sm font-semibold tracking-tight text-gray-950 dark:text-white">
                            @if($results['last_sync'])
                                {{ $results['last_sync']->diffForHumans() }}
                            @else
                                Never
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Test Form -->
        <form wire:submit="save">
            {{ $this->form }}
        </form>
        
        <!-- Test Instructions -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-4">Test Instructions</h3>
            
            <div class="prose prose-sm dark:prose-invert max-w-none">
                <ol>
                    <li><strong>Test Connection:</strong> Verifies that the API key is valid and can connect to Cal.com</li>
                    <li><strong>Get Bookings:</strong> Retrieves the latest 10 bookings from your Cal.com account</li>
                    <li><strong>Check Availability:</strong> Shows available time slots for the selected date</li>
                    <li><strong>Sync Now:</strong> Runs a full synchronization of all Cal.com bookings</li>
                </ol>
                
                <h4>Troubleshooting</h4>
                <ul>
                    <li>If connection fails with 403 error: Your API key may be invalid or lack permissions</li>
                    <li>If no bookings are found: Check if your Cal.com account has any bookings</li>
                    <li>If availability check fails: Verify the Event Type ID exists in your Cal.com account</li>
                </ul>
                
                <h4>API Information</h4>
                <ul>
                    <li>This integration uses Cal.com API v2</li>
                    <li>API v1 endpoints may return 403 errors with v2-only keys</li>
                    <li>Webhook URL: <code>{{ url('/api/calcom/webhook') }}</code></li>
                </ul>
            </div>
        </div>
    </div>
</x-filament-panels::page>