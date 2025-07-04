<x-filament-panels::page>
    @if(!$agentId)
        <div class="text-center p-8">
            <p class="text-gray-500">No agent ID provided</p>
            <a href="/admin/retell-ultimate-control-center" class="text-primary-600 hover:text-primary-500">
                Back to Control Center
            </a>
        </div>
    @else
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold">
                        {{ $agent['agent_name'] ?? 'Agent Editor' }}
                    </h2>
                    <p class="text-sm text-gray-500">
                        Agent ID: {{ $agentId }}
                    </p>
                </div>
                <a href="/admin/retell-ultimate-control-center" 
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    ← Back
                </a>
            </div>

            @if($selectedVersionData)
                <!-- Main Content -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Basic Info -->
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Basic Information</h3>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Agent Name</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['agent_name'] ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Version</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['version'] ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['status'] ?? 'inactive' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Voice Settings -->
                        <div>
                            <h3 class="text-lg font-semibold mb-4">Voice Settings</h3>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Voice ID</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100 font-mono">
                                        {{ $selectedVersionData['voice_id'] ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Language</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['language'] ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Voice Model</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['voice_model'] ?? 'N/A' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Response Engine -->
                    @if(isset($selectedVersionData['response_engine']))
                        <div class="mt-6 pt-6 border-t">
                            <h3 class="text-lg font-semibold mb-4">Response Engine</h3>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Type</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['response_engine']['type'] ?? 'N/A' }}
                                    </dd>
                                </div>
                                @if(isset($selectedVersionData['response_engine']['llm_id']))
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">LLM ID</dt>
                                        <dd class="text-sm text-gray-900 dark:text-gray-100 font-mono">
                                            {{ $selectedVersionData['response_engine']['llm_id'] }}
                                        </dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif

                    <!-- LLM Configuration if available -->
                    @if(isset($selectedVersionData['llm_configuration']))
                        <div class="mt-6 pt-6 border-t">
                            <h3 class="text-lg font-semibold mb-4">LLM Configuration</h3>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Model</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $selectedVersionData['llm_configuration']['model'] ?? 'N/A' }}
                                    </dd>
                                </div>
                                @if(isset($selectedVersionData['llm_configuration']['general_prompt']))
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">General Prompt</dt>
                                        <dd class="mt-1">
                                            <pre class="text-xs bg-gray-100 dark:bg-gray-900 p-4 rounded overflow-x-auto">{{ $selectedVersionData['llm_configuration']['general_prompt'] }}</pre>
                                        </dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif

                    <!-- Raw JSON -->
                    <details class="mt-6 pt-6 border-t">
                        <summary class="cursor-pointer text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900">
                            View Raw Configuration
                        </summary>
                        <pre class="mt-4 text-xs bg-gray-100 dark:bg-gray-900 p-4 rounded overflow-x-auto">{{ json_encode($selectedVersionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                </div>

                <!-- Version List -->
                @if(count($versions) > 1)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Available Versions</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                            @foreach($versions as $version)
                                <a href="?agent_id={{ $agentId }}&version={{ $version['version'] }}"
                                   class="px-3 py-2 text-sm rounded {{ $selectedVersion == $version['version'] ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                    v{{ $version['version'] }}
                                    @if($version['is_published'] ?? false)
                                        <span class="text-xs">●</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                    <p class="text-gray-500">Loading agent data...</p>
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>