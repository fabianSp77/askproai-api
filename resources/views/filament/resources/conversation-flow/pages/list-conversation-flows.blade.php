<x-filament-panels::page>
    {{-- Debug output --}}
    <div class="mb-4 p-4 bg-yellow-100 border border-yellow-400 rounded">
        <strong>Debug Info:</strong><br>
        flowData type: {{ gettype($flowData) }}<br>
        flowData count: {{ is_array($flowData) ? count($flowData) : 'N/A' }}<br>
        flowData empty: {{ empty($flowData) ? 'YES' : 'NO' }}<br>
        @if(!empty($flowData))
            Name: {{ $flowData['name'] ?? 'N/A' }}<br>
            Nodes: {{ $flowData['total_nodes'] ?? 'N/A' }}<br>
            Transitions: {{ $flowData['total_transitions'] ?? 'N/A' }}<br>
            Timestamp: {{ $flowData['timestamp'] ?? 'N/A' }}
        @endif
    </div>

    @if(!empty($flowData))
        <div class="space-y-6">
            {{-- Flow Information Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $flowData['name'] }}
                    </h3>
                </div>

                <div class="px-6 py-4">
                    <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Nodes</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $flowData['total_nodes'] }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Transitions</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                    {{ $flowData['total_transitions'] }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">LLM Model</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ $flowData['model'] }}
                                </span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    {{ ucfirst($flowData['status']) }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Actions Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Actions
                    </h3>
                </div>

                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {{-- Download JSON --}}
                        <a href="{{ route('conversation-flow.download-json') }}"
                           target="_blank"
                           class="flex items-center justify-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download JSON
                        </a>

                        {{-- Setup Guide --}}
                        <a href="{{ route('conversation-flow.download-guide') }}"
                           target="_blank"
                           class="flex items-center justify-center px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Setup Guide
                        </a>

                        {{-- Deploy to Retell --}}
                        <button
                            wire:click="$dispatch('open-modal', { id: 'deploy-modal' })"
                            class="flex items-center justify-center px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            Deploy to Retell
                        </button>
                    </div>
                </div>
            </div>

            {{-- Impact Metrics --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Estimated Impact
                    </h3>
                </div>

                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                            <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Success Rate Improvement</dt>
                            <dd class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">52.1% → 83% (+26 pp)</dd>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Scenario 4 Improvement</dt>
                            <dd class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">25% → 85% (+60 pp)</dd>
                        </div>

                        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                            <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Hallucination Reduction</dt>
                            <dd class="mt-1 text-2xl font-bold text-purple-600 dark:text-purple-400">-70%</dd>
                        </div>

                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                            <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Revenue Increase (Monthly)</dt>
                            <dd class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400">+€3,360</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    @else
        {{-- No Flow Generated --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12">
            <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No Conversation Flow Generated</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Click "Regenerate Agent" in the header to generate a new conversation flow.</p>
            </div>
        </div>
    @endif
</x-filament-panels::page>
