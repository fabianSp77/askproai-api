<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
            <h1 class="text-3xl font-bold mb-2">🔬 Cal.com Integration Testing Dashboard</h1>
            <p class="opacity-90">Run comprehensive tests for all Cal.com functions (9 test suites)</p>
            <p class="text-sm opacity-75 mt-2">⚠️ Restricted access: admin@askproai.de only</p>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::button
                wire:click="runAllTests"
                :disabled="$this->isRunning"
                icon="heroicon-o-play"
                color="success"
                size="lg"
                class="w-full"
            >
                @if($this->isRunning)
                    Running Tests...
                @else
                    Run All Tests
                @endif
            </x-filament::button>

            <x-filament::button
                wire:click="exportReport"
                icon="heroicon-o-arrow-down-tray"
                color="info"
                size="lg"
                class="w-full"
            >
                Export Report (JSON)
            </x-filament::button>

            <x-filament::button
                wire:click="downloadDocumentation"
                icon="heroicon-o-document-text"
                color="primary"
                size="lg"
                class="w-full"
            >
                Download Test Plan
            </x-filament::button>
        </div>

        <!-- Test Suite Buttons -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold mb-4 flex items-center">
                <span class="inline-block w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mr-2">⚙️</span>
                Cal.com Integration Tests (9 Suites)
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @php
                    $testTypes = [
                        'event_id_verification' => '1. Event-ID Verification',
                        'availability_check' => '2. Availability Check',
                        'appointment_booking' => '3. Appointment Booking',
                        'appointment_reschedule' => '4. Appointment Reschedule',
                        'appointment_cancellation' => '5. Appointment Cancellation',
                        'appointment_query' => '6. Appointment Query',
                        'bidirectional_sync' => '7. Bidirectional Sync',
                        'v2_api_compatibility' => '8. V2 API Compatibility',
                        'multi_tenant_isolation' => '9. Multi-Tenant Isolation',
                    ];
                @endphp

                @foreach($testTypes as $typeKey => $typeLabel)
                    <x-filament::button
                        wire:click="runTest('{{ $typeKey }}')"
                        :disabled="$this->isRunning"
                        icon="heroicon-o-play-circle"
                        size="md"
                        class="w-full"
                    >
                        {{ $typeLabel }}
                    </x-filament::button>
                @endforeach
            </div>
        </div>

        <!-- Live Output -->
        @if($this->isRunning || count($this->liveOutput) > 0)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center">
                    <span class="inline-block w-8 h-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center mr-2">📊</span>
                    Live Test Output
                    @if($this->isRunning)
                        <span class="ml-auto">
                            <x-filament::badge color="warning">
                                Running...
                            </x-filament::badge>
                        </span>
                    @endif
                </h2>

                <div class="bg-gray-900 text-gray-100 rounded-lg p-4 font-mono text-sm overflow-x-auto max-h-96 overflow-y-auto">
                    @if($this->isRunning)
                        <div class="flex items-center space-x-2">
                            <span class="animate-spin">⏳</span>
                            <span>Running {{ $this->getTestLabel($this->currentTest) }}...</span>
                        </div>
                    @endif

                    @forelse($this->liveOutput as $line)
                        @if(is_array($line))
                            <div class="mb-2">
                                <span class="text-green-400">✓</span>
                                <span>{{ json_encode($line) }}</span>
                            </div>
                        @else
                            <div class="mb-2">{{ $line }}</div>
                        @endif
                    @empty
                        <span class="text-gray-500">No output yet...</span>
                    @endforelse
                </div>
            </div>
        @endif

        <!-- Test History -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <h2 class="text-lg font-semibold p-6 border-b border-gray-200 flex items-center">
                <span class="inline-block w-8 h-8 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mr-2">📋</span>
                Test History
            </h2>

            @if(count($this->testRunHistory) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Test Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Result</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($this->testRunHistory as $testRun)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $this->getTestLabel($testRun['test_type']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-filament::badge :color="$this->getStatusColor($testRun['status'])">
                                            {{ ucfirst($testRun['status']) }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $testRun['duration_ms'] ? round($testRun['duration_ms'] / 1000, 2) . 's' : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($testRun['error_message'])
                                            <x-filament::badge color="danger">
                                                Failed
                                            </x-filament::badge>
                                        @else
                                            <x-filament::badge color="success">
                                                Passed
                                            </x-filament::badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($testRun['created_at'])->format('Y-m-d H:i:s') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-6 text-center text-gray-500">
                    No test history yet. Run your first test!
                </div>
            @endif
        </div>

        <!-- Information Panel -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-blue-50 rounded-lg border border-blue-200 p-4">
                <h3 class="font-semibold text-blue-900 mb-2">ℹ️ About This Dashboard</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>✅ Test all 9 Cal.com integration functions</li>
                    <li>✅ Multi-tenant isolation verification (AskProAI, Friseur 1)</li>
                    <li>✅ V2 API compatibility checks</li>
                    <li>✅ Live test output monitoring</li>
                    <li>✅ Test history and reporting</li>
                </ul>
            </div>

            <div class="bg-green-50 rounded-lg border border-green-200 p-4">
                <h3 class="font-semibold text-green-900 mb-2">📊 Teams & Event-IDs</h3>
                <ul class="text-sm text-green-800 space-y-1">
                    <li><strong>AskProAI</strong> (Team 39203)</li>
                    <li class="ml-4">Events: 3664712, 2563193</li>
                    <li><strong>Friseur 1</strong> (Team 34209)</li>
                    <li class="ml-4">Events: 2942413, 3672814</li>
                </ul>
            </div>
        </div>
    </div>

    @script
        <script>
            Livewire.on('test-completed', (data) => {
                console.log('Test completed:', data);
                Livewire.dispatch('$refresh');
            });

            Livewire.on('all-tests-completed', (data) => {
                console.log('All tests completed:', data);
                Livewire.dispatch('$refresh');
            });
        </script>
    @endscript
</x-filament-panels::page>
