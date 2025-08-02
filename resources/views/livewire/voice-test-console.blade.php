<div class="space-y-6">
    {{-- Test Configuration Form --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-beaker class="w-5 h-5 text-primary-600" />
                Voice Test Console
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Test your AI agent configuration with real phone calls
            </p>
        </div>

        <form wire:submit="initiateTestCall" class="space-y-4">
            {{ $this->form }}

            <div class="flex items-center gap-3 mt-6">
                @if(!$activeCallId)
                    <x-filament::button 
                        type="submit" 
                        icon="heroicon-o-phone"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove>Start Test Call</span>
                        <span wire:loading>
                            <x-filament::loading-indicator class="w-4 h-4" />
                            Initiating...
                        </span>
                    </x-filament::button>
                @else
                    <x-filament::button 
                        type="button" 
                        color="danger"
                        icon="heroicon-o-stop"
                        wire:click="stopTest"
                    >
                        Stop Monitoring
                    </x-filament::button>
                    
                    <x-filament::button 
                        type="button" 
                        color="gray"
                        icon="heroicon-o-x-mark"
                        wire:click="clearTest"
                    >
                        Clear
                    </x-filament::button>
                @endif
            </div>
        </form>
    </div>

    {{-- Active Call Monitoring --}}
    @if($activeCallId && $callDetails)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Active Test Call
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Call ID: {{ $activeCallId }}
                    </p>
                </div>
                
                <div class="flex items-center gap-2">
                    @if($autoRefresh)
                        <span class="flex items-center text-sm text-green-600 dark:text-green-400">
                            <x-filament::loading-indicator class="w-4 h-4 mr-1" />
                            Live Monitoring
                        </span>
                    @endif
                    
                    <x-filament::badge :color="match($callDetails['status']) {
                        'in-progress' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'no-answer' => 'gray',
                        default => 'secondary',
                    }">
                        {{ ucfirst($callDetails['status']) }}
                    </x-filament::badge>
                </div>
            </div>

            {{-- Call Details Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</dt>
                    <dd class="mt-1 text-lg font-semibold">
                        {{ $callDetails['duration'] ?? 0 }}s
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">To Number</dt>
                    <dd class="mt-1 text-sm">{{ $callDetails['to_number'] }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">From Number</dt>
                    <dd class="mt-1 text-sm">{{ $callDetails['from_number'] }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Started At</dt>
                    <dd class="mt-1 text-sm">
                        {{ \Carbon\Carbon::parse($callDetails['started_at'])->format('H:i:s') }}
                    </dd>
                </div>
            </div>

            {{-- Transcript Section --}}
            @if($callDetails['transcript'])
                <div class="border-t pt-4">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-medium text-gray-900 dark:text-white">Call Transcript</h4>
                        <x-filament::button
                            size="sm"
                            color="gray"
                            icon="heroicon-o-eye"
                            wire:click="$toggle('showTranscript')"
                        >
                            {{ $showTranscript ? 'Hide' : 'Show' }} Transcript
                        </x-filament::button>
                    </div>
                    
                    @if($showTranscript)
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 max-h-64 overflow-y-auto">
                            <pre class="text-sm whitespace-pre-wrap">{{ $callDetails['transcript'] }}</pre>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Test Analysis (if completed) --}}
            @if(isset($callDetails['test_analysis']))
                <div class="border-t pt-4 mt-4">
                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Test Analysis</h4>
                    
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            @if($callDetails['test_analysis']['scenario_success'])
                                <x-heroicon-m-check-circle class="w-5 h-5 text-green-600" />
                                <span class="text-green-600 font-medium">Scenario Passed</span>
                            @else
                                <x-heroicon-m-x-circle class="w-5 h-5 text-red-600" />
                                <span class="text-red-600 font-medium">Scenario Failed</span>
                            @endif
                        </div>
                        
                        @if(count($callDetails['test_analysis']['issues_found']) > 0)
                            <div>
                                <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Issues Found:</h5>
                                <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400">
                                    @foreach($callDetails['test_analysis']['issues_found'] as $issue)
                                        <li>{{ $issue }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        @if(count($callDetails['test_analysis']['recommendations']) > 0)
                            <div>
                                <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Recommendations:</h5>
                                <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400">
                                    @foreach($callDetails['test_analysis']['recommendations'] as $recommendation)
                                        <li>{{ $recommendation }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Recording Link (if available) --}}
            @if($callDetails['recording_url'])
                <div class="border-t pt-4 mt-4">
                    <x-filament::link
                        href="{{ $callDetails['recording_url'] }}"
                        target="_blank"
                        icon="heroicon-m-speaker-wave"
                    >
                        Listen to Recording
                    </x-filament::link>
                </div>
            @endif
        </div>
    @endif

    {{-- Test History --}}
    @if(count($testHistory) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Recent Test History
            </h3>
            
            <div class="space-y-2">
                @foreach(array_reverse($testHistory) as $test)
                    <div class="flex justify-between items-center py-2 border-b last:border-0">
                        <div>
                            <span class="text-sm font-medium">{{ ucfirst(str_replace('_', ' ', $test['scenario'])) }}</span>
                            <span class="text-xs text-gray-500 ml-2">{{ $test['phone_number'] }}</span>
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ \Carbon\Carbon::parse($test['started_at'])->diffForHumans() }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            let monitoringInterval = null;
            
            // Start monitoring when call is initiated
            window.addEventListener('start-call-monitoring', () => {
                if (monitoringInterval) {
                    clearInterval(monitoringInterval);
                }
                
                monitoringInterval = setInterval(() => {
                    @this.call('refreshCallStatus');
                }, 2000); // Refresh every 2 seconds
            });
            
            // Stop monitoring
            window.addEventListener('stop-call-monitoring', () => {
                if (monitoringInterval) {
                    clearInterval(monitoringInterval);
                    monitoringInterval = null;
                }
            });
            
            // Cleanup on component destroy
            document.addEventListener('livewire:navigated', () => {
                if (monitoringInterval) {
                    clearInterval(monitoringInterval);
                }
            });
        </script>
    @endpush
</div>