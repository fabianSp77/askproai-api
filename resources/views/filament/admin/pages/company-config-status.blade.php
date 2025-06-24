<x-filament-panels::page>
    @if($company)
        <div class="space-y-6">
            {{-- Basic Information --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Basic Information</h2>
                
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Company Name</dt>
                        <dd class="mt-1 text-sm">{{ $status['basic']['name'] }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Slug</dt>
                        <dd class="mt-1 text-sm">{{ $status['basic']['slug'] }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                        <dd class="mt-1">
                            @if($status['basic']['active'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                    Inactive
                                </span>
                            @endif
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Branches</dt>
                        <dd class="mt-1 text-sm">{{ $status['basic']['branches'] }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone Numbers</dt>
                        <dd class="mt-1 text-sm">{{ $status['basic']['phones'] }}</dd>
                    </div>
                </dl>
            </div>
            
            {{-- Integration Status --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Cal.com Status --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Cal.com Integration
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm">API Key</span>
                            @if($status['calcom']['has_api_key'])
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm">Team Slug</span>
                            @if($status['calcom']['has_team_slug'])
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <span class="text-xs text-gray-500">Optional</span>
                            @endif
                        </div>
                        
                        <div class="pt-3 mt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium">Event Types</span>
                                <span class="text-sm font-semibold">{{ $status['calcom']['event_types'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Retell.ai Status --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        Retell.ai Integration
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm">API Key</span>
                            @if($status['retell']['has_api_key'])
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm">Agent ID</span>
                            @if($status['retell']['has_agent_id'])
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </div>
                        
                        <div class="pt-3 mt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium">Total Calls</span>
                                <span class="text-sm font-semibold">{{ $status['retell']['calls_count'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Configuration Guide --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-3">Configuration Guide</h3>
                
                <div class="prose prose-sm dark:prose-invert max-w-none">
                    <ol>
                        <li>
                            <strong>Cal.com Setup:</strong>
                            <ul>
                                <li>Login to Cal.com</li>
                                <li>Go to Settings → Developer → API Keys</li>
                                <li>Create a new API key and copy it</li>
                                <li>Add the API key to your company settings</li>
                            </ul>
                        </li>
                        <li>
                            <strong>Retell.ai Setup:</strong>
                            <ul>
                                <li>Login to Retell.ai</li>
                                <li>Go to Settings → API Keys</li>
                                <li>Create a new API key and copy it</li>
                                <li>Create an agent and copy the agent ID</li>
                                <li>Add both to your company settings</li>
                            </ul>
                        </li>
                        <li>
                            <strong>Webhook Configuration:</strong>
                            <ul>
                                <li>In Retell.ai, add webhook URL: <code>https://api.askproai.de/api/retell/webhook</code></li>
                                <li>In Cal.com, add webhook URL: <code>https://api.askproai.de/api/calcom/webhook</code></li>
                            </ul>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-12">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No Company Found</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                No company is associated with your account. Please contact an administrator.
            </p>
        </div>
    @endif
</x-filament-panels::page>