<div class="space-y-6">
    @if (session()->has('message'))
        <div class="rounded-lg bg-success-50 dark:bg-success-900/10 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-s-check-circle class="h-5 w-5 text-success-400" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-success-800 dark:text-success-400">
                        {{ session('message') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Agent Information Section --}}
    <x-filament::section>
        <x-slot name="heading">
            Agent Information
        </x-slot>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-white">{{ $agent->id }}</div>
            </div>
            
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-white">{{ $agent->name }}</div>
            </div>
            
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Agent ID</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $agent->agent_id }}</div>
            </div>
            
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Company</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-white">
                    @if($agent->company)
                        <a href="{{ route('filament.admin.resources.companies.view', $agent->company) }}" 
                           class="text-primary-600 hover:text-primary-700 dark:text-primary-400">
                            {{ $agent->company->name }}
                        </a>
                    @else
                        <span class="text-gray-400">Keine Company zugeordnet</span>
                    @endif
                </div>
            </div>
            
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone Number ID</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ $agent->phone_number_id ?: 'Nicht zugeordnet' }}
                </div>
            </div>
            
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Version</div>
                <div class="mt-1">
                    <x-filament::badge color="info">
                        v{{ $agent->version }}
                    </x-filament::badge>
                    @if($agent->version_title)
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ $agent->version_title }}</span>
                    @endif
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Status Section --}}
    <x-filament::section>
        <x-slot name="heading">
            Status
        </x-slot>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Status</div>
                <div class="mt-1">
                    <x-filament::badge color="{{ $agent->is_active ? 'success' : 'danger' }}">
                        {{ $agent->is_active ? 'Active' : 'Inactive' }}
                    </x-filament::badge>
                </div>
            </div>
            
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Published</div>
                <div class="mt-1">
                    <x-filament::badge color="{{ $agent->is_published ? 'success' : 'warning' }}">
                        {{ $agent->is_published ? 'Published' : 'Draft' }}
                    </x-filament::badge>
                </div>
            </div>
            
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Sync Status</div>
                <div class="mt-1">
                    @php
                        $syncColor = match($agent->sync_status) {
                            'synced' => 'success',
                            'pending' => 'warning',
                            'failed' => 'danger',
                            default => 'gray'
                        };
                    @endphp
                    <x-filament::badge color="{{ $syncColor }}">
                        {{ ucfirst($agent->sync_status ?? 'Unknown') }}
                    </x-filament::badge>
                </div>
            </div>
        </div>
        
        @if($agent->last_synced_at)
            <div class="mt-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Synced</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ $agent->last_synced_at->format('d.m.Y H:i:s') }}
                    <span class="text-gray-500">({{ $agent->last_synced_at->diffForHumans() }})</span>
                </div>
            </div>
        @endif
    </x-filament::section>

    {{-- Configuration Section --}}
    @if($agent->configuration)
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                Configuration
            </x-slot>
            
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 overflow-x-auto">
                <pre class="text-xs text-gray-700 dark:text-gray-300">{{ $this->getFormattedConfiguration() }}</pre>
            </div>
        </x-filament::section>
    @endif

    {{-- Settings Section --}}
    @if($agent->settings)
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                Settings
            </x-slot>
            
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 overflow-x-auto">
                <pre class="text-xs text-gray-700 dark:text-gray-300">{{ $this->getFormattedSettings() }}</pre>
            </div>
        </x-filament::section>
    @endif

    {{-- Timestamps Section --}}
    <x-filament::section>
        <x-slot name="heading">
            Timestamps
        </x-slot>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ $agent->created_at->format('d.m.Y H:i:s') }}
                    <span class="text-gray-500">({{ $agent->created_at->diffForHumans() }})</span>
                </div>
            </div>
            
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Updated At</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ $agent->updated_at->format('d.m.Y H:i:s') }}
                    <span class="text-gray-500">({{ $agent->updated_at->diffForHumans() }})</span>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Additional Details Section --}}
    <x-filament::section collapsible>
        <x-slot name="heading">
            Additional Details
        </x-slot>
        
        <div class="space-y-4">
            <div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Flag</div>
                <div class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ $agent->active ? 'Yes' : 'No' }}
                </div>
            </div>
            
            @if($agent->getRawOriginal())
                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Database Fields</div>
                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                        Total fields: {{ count($agent->getAttributes()) }}
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</div>