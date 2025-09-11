<div class="fi-page-content">
    <div class="space-y-6">
        <!-- Integration Header -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn">
                <div class="fi-section-content p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                                {{ $integration->name }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Type: {{ ucfirst($integration->type) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($integration->is_active)
                                <span class="fi-badge flex items-center gap-x-1 rounded-md text-xs font-medium px-2 py-1 bg-success-50 text-success-600 dark:bg-success-400/10 dark:text-success-400">
                                    Active
                                </span>
                            @else
                                <span class="fi-badge flex items-center gap-x-1 rounded-md text-xs font-medium px-2 py-1 bg-danger-50 text-danger-600 dark:bg-danger-400/10 dark:text-danger-400">
                                    Inactive
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Integration Details -->
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn">
                <div class="fi-section-content p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Integration Details</h3>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Integration ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $integration->id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ucfirst($integration->type) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Company ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $integration->company_id ?? 'Not assigned' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tenant ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $integration->tenant_id ?? 'Not assigned' }}
                            </dd>
                        </div>
                        @if($integration->api_key)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">API Key</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                                {{ Str::limit($integration->api_key, 20) }}...
                            </dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $integration->created_at->format('d.m.Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Updated</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $integration->updated_at->format('d.m.Y H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $integration->is_active ? 'Active' : 'Inactive' }}
                            </dd>
                        </div>
                    </dl>
                    
                    @if($integration->config && count($integration->config) > 0)
                    <div class="mt-6">
                        <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 mb-2">Configuration</h4>
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <pre class="text-xs text-gray-700 dark:text-gray-300 overflow-x-auto">{{ json_encode($integration->config, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>