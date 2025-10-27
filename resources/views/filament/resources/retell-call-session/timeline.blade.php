@php
    use App\Services\Retell\CallTrackingService;

    $session = $getRecord();
    $trackingService = app(CallTrackingService::class);

    // Get timeline with all events
    $timeline = $trackingService->getCallTimeline($session->call_id);

    // Get function call chain
    $functionChain = $trackingService->getFunctionCallChain($session->call_id);

    // Get errors
    $errorSummary = $trackingService->getErrorSummary($session->call_id);
@endphp

<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <div class="text-sm text-blue-600 dark:text-blue-400 font-medium">Total Events</div>
            <div class="text-2xl font-bold text-blue-900 dark:text-blue-100 mt-1">{{ count($timeline) }}</div>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <div class="text-sm text-green-600 dark:text-green-400 font-medium">Function Calls</div>
            <div class="text-2xl font-bold text-green-900 dark:text-green-100 mt-1">{{ count($functionChain) }}</div>
        </div>

        <div class="bg-{{ $errorSummary['total_errors'] > 0 ? 'red' : 'gray' }}-50 dark:bg-{{ $errorSummary['total_errors'] > 0 ? 'red' : 'gray' }}-900/20 rounded-lg p-4">
            <div class="text-sm text-{{ $errorSummary['total_errors'] > 0 ? 'red' : 'gray' }}-600 dark:text-{{ $errorSummary['total_errors'] > 0 ? 'red' : 'gray' }}-400 font-medium">Errors</div>
            <div class="text-2xl font-bold text-{{ $errorSummary['total_errors'] > 0 ? 'red' : 'gray' }}-900 dark:text-{{ $errorSummary['total_errors'] > 0 ? 'red' : 'gray' }}-100 mt-1">
                {{ $errorSummary['total_errors'] }}
                @if($errorSummary['critical_errors'] > 0)
                    <span class="text-sm text-red-600">({{ $errorSummary['critical_errors'] }} critical)</span>
                @endif
            </div>
        </div>

        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
            <div class="text-sm text-purple-600 dark:text-purple-400 font-medium">Avg Response</div>
            <div class="text-2xl font-bold text-purple-900 dark:text-purple-100 mt-1">
                {{ $session->avg_response_time_ms ?? 0 }}ms
            </div>
        </div>
    </div>

    {{-- Function Call Chain --}}
    @if(count($functionChain) > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                🔧 Function Call Chain
            </h3>
        </div>
        <div class="p-4 space-y-3">
            @foreach($functionChain as $func)
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 {{ $func['status'] === 'error' ? 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-700' : 'bg-gray-50 dark:bg-gray-700' }}">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-mono text-gray-500 dark:text-gray-400">#{{ $func['sequence'] }}</span>
                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ $func['function'] }}</span>
                        @if($func['status'] === 'success')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                ✓ Success
                            </span>
                        @elseif($func['status'] === 'error')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                ✗ Error
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                ⏳ {{ ucfirst($func['status']) }}
                            </span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <span class="font-mono">{{ $func['duration_ms'] ?? '?' }}ms</span>
                        <span class="text-xs ml-2">{{ $func['started_at']->format('H:i:s.u') }}</span>
                    </div>
                </div>

                {{-- Input Parameters --}}
                @if($func['input'])
                <details class="mt-2">
                    <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                        📥 Input Parameters
                    </summary>
                    <pre class="mt-2 p-3 bg-gray-100 dark:bg-gray-900 rounded text-xs overflow-x-auto"><code>{{ json_encode($func['input'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </details>
                @endif

                {{-- Output Result --}}
                @if($func['output'])
                <details class="mt-2">
                    <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
                        📤 Output Result
                    </summary>
                    <pre class="mt-2 p-3 bg-gray-100 dark:bg-gray-900 rounded text-xs overflow-x-auto"><code>{{ json_encode($func['output'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </details>
                @endif

                {{-- Error Details --}}
                @if($func['error'])
                <div class="mt-2 p-3 bg-red-100 dark:bg-red-900/30 rounded">
                    <div class="text-sm font-medium text-red-800 dark:text-red-200 mb-1">❌ Error Details:</div>
                    <pre class="text-xs text-red-700 dark:text-red-300 overflow-x-auto"><code>{{ json_encode($func['error'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Complete Timeline --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                ⏱️ Complete Timeline ({{ count($timeline) }} events)
            </h3>
        </div>
        <div class="p-4">
            <div class="space-y-2">
                @foreach($timeline as $event)
                <div class="flex items-start space-x-3 border-l-2 {{
                    $event['type'] === 'function_call' ? 'border-blue-500' :
                    ($event['type'] === 'transcript' ? 'border-green-500' :
                    ($event['type'] === 'error' ? 'border-red-500' : 'border-gray-300'))
                }} pl-4 py-2">

                    {{-- Timestamp --}}
                    <div class="flex-shrink-0 w-32">
                        <div class="text-xs font-mono text-gray-500 dark:text-gray-400">
                            {{ $event['timestamp']->format('H:i:s.u') }}
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                            +{{ $event['offset_ms'] }}ms
                        </div>
                    </div>

                    {{-- Event Content --}}
                    <div class="flex-1 min-w-0">
                        @if($event['type'] === 'function_call')
                            {{-- Function Call Event --}}
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400">🔧 FUNCTION CALL</span>
                                    <span class="font-bold text-blue-900 dark:text-blue-100">{{ $event['data']['function'] ?? 'Unknown' }}</span>
                                    @if(isset($event['data']['status']))
                                        <span class="text-xs px-2 py-0.5 rounded {{
                                            $event['data']['status'] === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                        }}">
                                            {{ $event['data']['status'] }}
                                        </span>
                                    @endif
                                    @if(isset($event['data']['duration_ms']))
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $event['data']['duration_ms'] }}ms</span>
                                    @endif
                                </div>
                                @if(isset($event['data']['arguments']))
                                <details class="mt-1">
                                    <summary class="cursor-pointer text-xs text-blue-700 dark:text-blue-300">View arguments</summary>
                                    <pre class="mt-1 text-xs bg-white dark:bg-gray-900 p-2 rounded overflow-x-auto"><code>{{ json_encode($event['data']['arguments'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                </details>
                                @endif
                            </div>

                        @elseif($event['type'] === 'transcript')
                            {{-- Transcript Event --}}
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="text-xs font-medium text-green-600 dark:text-green-400">
                                        {{ $event['data']['role'] === 'agent' ? '🤖 AGENT' : '👤 USER' }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $event['data']['text'] ?? '' }}
                                </div>
                            </div>

                        @elseif($event['type'] === 'flow_transition')
                            {{-- Flow Transition Event --}}
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3">
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-medium text-purple-600 dark:text-purple-400">🔄 FLOW</span>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ $event['data']['from'] ?? '?' }} → {{ $event['data']['to'] ?? '?' }}
                                    </span>
                                </div>
                            </div>

                        @elseif($event['type'] === 'error')
                            {{-- Error Event --}}
                            <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                                <div class="flex items-center space-x-2 mb-1">
                                    <span class="text-xs font-medium text-red-600 dark:text-red-400">❌ ERROR</span>
                                    <span class="text-xs text-red-800 dark:text-red-200">{{ $event['data']['code'] ?? 'unknown' }}</span>
                                </div>
                                <div class="text-sm text-red-900 dark:text-red-100">
                                    {{ $event['data']['message'] ?? 'Unknown error' }}
                                </div>
                            </div>

                        @else
                            {{-- Unknown Event Type --}}
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $event['type'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach

                @if(count($timeline) === 0)
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No timeline events recorded yet.
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Error Summary --}}
    @if($errorSummary['total_errors'] > 0)
    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-700 overflow-hidden">
        <div class="bg-red-100 dark:bg-red-900/40 px-4 py-3 border-b border-red-200 dark:border-red-700">
            <h3 class="text-lg font-semibold text-red-900 dark:text-red-100">
                ⚠️ Error Summary ({{ $errorSummary['total_errors'] }} errors)
            </h3>
        </div>
        <div class="p-4 space-y-2">
            @foreach($errorSummary['errors'] as $error)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-red-200 dark:border-red-700">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-0.5 rounded text-xs font-medium {{
                            $error['severity'] === 'critical' ? 'bg-red-600 text-white' :
                            ($error['severity'] === 'high' ? 'bg-red-500 text-white' : 'bg-yellow-500 text-white')
                        }}">
                            {{ strtoupper($error['severity']) }}
                        </span>
                        <span class="font-mono text-sm text-red-800 dark:text-red-200">{{ $error['code'] }}</span>
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $error['occurred_at']->format('H:i:s') }}
                    </span>
                </div>
                <div class="text-sm text-gray-900 dark:text-gray-100 mb-1">{{ $error['message'] }}</div>
                @if($error['function'])
                <div class="text-xs text-gray-600 dark:text-gray-400">Function: {{ $error['function'] }}</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
